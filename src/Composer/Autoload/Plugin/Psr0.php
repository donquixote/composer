<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class Psr0 extends AbstractPlugin implements ExposeClassmapInterface
{
    /**
     * @param PackageInterface $package
     * @internal param int $order
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
        $map = require __DIR__ . '/autoload_namespaces.php';
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }


PSR4;
    }

    /**
     * @param BuildInterface $build
     * @return string[]
     *   Class map.
     */
    public function buildClassMap(BuildInterface $build = NULL)
    {
        return $this->buildClassMapBase($build, 'psr-0');
    }
}
