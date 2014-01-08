<?php


namespace Composer\Autoload;


use Composer\Package\PackageInterface;

class LoaderCreator
{
    /**
     * @var Plugin\PluginInterface[]
     */
    protected $plugins;

    function __construct() {
        $this->plugins = array(
            new Plugin\CreateLoader,
            new Plugin\IncludePaths,
            new Plugin\Psr0,
            new Plugin\Psr4,
            new Plugin\Classmap,
            new Plugin\TargetDirLoader,
            new Plugin\RegisterLoader,
            new Plugin\Files,
        );
    }

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     */
    function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        foreach ($this->plugins as $plugin) {
            $plugin->addPackage($package, $installPath, $isMainPackage);
        }
    }

    /**
     * @param bool $prependAutoloader
     * @return ClassLoader
     */
    function createLoader($prependAutoloader = true) {
        $loader = new ClassLoader();
        foreach ($this->plugins as $plugin) {
            $plugin->initClassLoader($loader, $prependAutoloader);
        }
        return $loader;
    }
} 