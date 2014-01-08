<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class Psr0 extends AbstractPlugin
{
    /**
     * @param PackageInterface $package
     *
     * @return array|null
     */
    protected function getPackageAutoloads(PackageInterface $package)
    {
        $autoload = $package->getAutoload();

        if (!isset($autoload['psr-0']) || !is_array($autoload['psr-0'])) {
            // Skip this package.
            return null;
        }

        return $autoload['psr-0'];
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        krsort($this->map);

        foreach ($this->map as $namespace => $paths) {
            $classLoader->add($namespace, $paths);
        }
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return 'autoload_namespaces.php';
    }

    /**
     * @return string
     */
    protected function getSnippet() {
        return <<<'PSR4'
        $map = require __DIR__ . '/autoload_psr0.php';
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }


PSR4;
    }
}