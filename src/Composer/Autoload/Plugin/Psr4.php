<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class Psr4 extends AbstractPlugin
{
    /**
     * @param PackageInterface $package
     *
     * @return array|null
     *
     * @throws \Exception
     */
    protected function getPackageAutoloads(PackageInterface $package)
    {
        $autoload = $package->getAutoload();

        if (!isset($autoload['psr-4']) || !is_array($autoload['psr-4'])) {
            // Skip this package.
            return NULL;
        }

        if (null !== $package->getTargetDir()) {
            throw new \Exception("The ['target-dir'] setting is incompatible with the ['psr-4'] setting.");
        }

        return $autoload['psr-4'];
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        krsort($this->map);

        foreach ($this->map as $namespace => $paths) {
            $classLoader->addPsr4($namespace, $paths);
        }
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return 'autoload_psr0.php';
    }

    /**
     * @return string
     */
    protected function getSnippet() {
        return <<<'PSR4'
        $map = require __DIR__ . '/autoload_psr4.php';
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }


PSR4;
    }
}