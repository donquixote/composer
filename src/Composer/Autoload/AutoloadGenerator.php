<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Autoload;

use Composer\Autoload\Plugin\PluginInterface;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Package\PackageConsumerInterface;
use Composer\Package\PackagePathFinderInterface;
use Composer\Package\PackageInterface;
use Composer\Package\PackageMap;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Script\ScriptEvents;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Andreas hennings <andreas@dqxtech.net>
 */
class AutoloadGenerator
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Config $config
     * @param WritableRepositoryInterface $localRepo
     * @param PackageInterface $mainPackage
     * @param PackagePathFinderInterface $packagePathFinder
     * @param string $targetDir
     * @param bool $scanPsr0Packages
     * @param string $suffix
     */
    public function dump(Config $config, WritableRepositoryInterface $localRepo, PackageInterface $mainPackage, PackagePathFinderInterface $packagePathFinder, $targetDir, $scanPsr0Packages = false, $suffix = '')
    {
        $this->eventDispatcher->dispatchScript(ScriptEvents::PRE_AUTOLOAD_DUMP);

        $packages = $localRepo->getCanonicalPackages();
        $packageMap = new PackageMap($packagePathFinder, $packages, $mainPackage);

        $plugins = $this->createPreparedPlugins($packageMap, $scanPsr0Packages);

        $build = new Build($config, $targetDir, $suffix);

        foreach ($plugins as $plugin) {
            $plugin->generate($build);
        }

        foreach ($files = $build->generateFiles() as $file => $contents) {
            file_put_contents($file, $contents);
        }

        // Use stream_copy_to_stream instead of copy,
        // to work around https://bugs.php.net/bug.php?id=64634
        $sourceLoader = fopen(__DIR__ . '/ClassLoader.php', 'r');
        $targetLoader = fopen($build->getTargetDir() . '/ClassLoader.php', 'w+');
        stream_copy_to_stream($sourceLoader, $targetLoader);
        fclose($sourceLoader);
        fclose($targetLoader);
        unset($sourceLoader, $targetLoader);

        $this->eventDispatcher->dispatchScript(ScriptEvents::POST_AUTOLOAD_DUMP);
    }

    /**
     * Creates and registers a class loader.
     *
     * @param PackageMap $packageMap
     * @param bool $prependAutoloader
     * @return ClassLoader
     */
    public function createLoader(PackageMap $packageMap, $prependAutoloader = true)
    {
        $plugins = $this->createPreparedPlugins($packageMap, false);
        $loader = new ClassLoader();
        foreach ($plugins as $plugin) {
            $plugin->initClassLoader($loader, $prependAutoloader);
        }
        return $loader;
    }

    /**
     * @param PackageMap $packageMap
     * @param bool $scanPsr0Packages
     *
     * @return PluginInterface[]
     */
    protected function createPreparedPlugins(PackageMap $packageMap, $scanPsr0Packages)
    {
        $plugins = array();
        foreach ($this->createPlugins($scanPsr0Packages) as $plugin) {
            if ($plugin instanceof PackageConsumerInterface) {
                $packageMap->processPackageConsumer($plugin);
            }
            if ($plugin instanceof PluginInterface) {
                $plugins[] = $plugin;
            }
        }
        return $plugins;
    }

    /**
     * @param bool $scanPsr0Packages
     *
     * @return object[]
     *   Each element is either an instance of PluginInterface, or
     *   PackageConsumerInterface, or both.
     */
    protected function createPlugins($scanPsr0Packages)
    {
        $plugins = array(
            new Plugin\CreateLoader,
            new Plugin\IncludePaths,
            $psr0 = new Plugin\Psr0,
            $psr4 = new Plugin\Psr4,
            $classmapPlugin = new Plugin\Classmap,
            $classmapPackageConsumer = new Plugin\ClassmapPackageConsumer,
            new Plugin\UseGlobalIncludePath,
            new Plugin\TargetDirLoader,
            new Plugin\RegisterLoader,
            new Plugin\Files,
        );
        if ($scanPsr0Packages) {
            $classmapPlugin->addClassmapSource($psr0);
            $classmapPlugin->addClassmapSource($psr4);
        }
        $classmapPlugin->addClassmapSource($classmapPackageConsumer);

        return $plugins;
    }
}
