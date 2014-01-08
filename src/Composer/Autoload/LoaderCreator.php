<?php


namespace Composer\Autoload;

use Composer\Package\PackageMap;

class LoaderCreator
{
    /**
     * @param PackageMap $packageMap
     * @param bool $prependAutoloader
     * @return ClassLoader
     */
    function createLoader(PackageMap $packageMap, $prependAutoloader = true)
    {
        $plugins = $this->createPlugins();
        foreach ($plugins as $plugin) {
            $packageMap->processPackageConsumer($plugin);
        }
        $loader = new ClassLoader();
        foreach ($plugins as $plugin) {
            $plugin->initClassLoader($loader, $prependAutoloader);
        }
        return $loader;
    }

    /**
     * @return Plugin\PluginInterface[]
     */
    protected function createPlugins() {
        $plugins = array(
          new Plugin\CreateLoader,
          new Plugin\IncludePaths,
          new Plugin\Psr0,
          new Plugin\Psr4,
          $classmap = new Plugin\Classmap,
          new Plugin\TargetDirLoader,
          new Plugin\RegisterLoader,
          new Plugin\Files,
        );
        $classmap->addClassmapSource($classmap);
        return $plugins;
    }
} 
