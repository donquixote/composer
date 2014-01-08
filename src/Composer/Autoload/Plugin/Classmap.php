<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Autoload\ClassMapGenerator;
use Composer\Package\PackageInterface;

class Classmap extends AbstractPlugin
{
    /**
     * @param PackageInterface $package
     *
     * @return array|null
     */
    protected function getPackageAutoloads(PackageInterface $package)
    {
        $autoload = $package->getAutoload();

        if (!isset($autoload['classmap']) || !is_array($autoload['classmap'])) {
            // Skip this package.
            return null;
        }

        return $autoload['classmap'];
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        // Attention, this is expensive!
        $classMap = $this->buildClassMap();
        $classLoader->addClassMap($classMap);
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return 'autoload_classmap.php';
    }

    /**
     * @param BuildInterface $build
     * @return string
     */
    protected function buildPhpRows($build)
    {
        $phpRows = '';
        foreach ($this->buildClassMap() as $class => $file) {
            $code = $build->getPathCode($file);
            $phpRows .= '    ' . var_export($class, true) . ' => ' . $code . ",\n";
        }

        if (empty($phpRows)) {
            // Suppress the class map.
            return null;
        }

        return $phpRows;
    }

    /**
     * @return string[]
     */
    private function buildClassMap()
    {
        $classMap = array();
        // $map = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->map));
        foreach ($this->map as $paths) {
            foreach ($paths as $path) {
                foreach (ClassMapGenerator::createMap($path) as $class => $file) {
                    $classMap[$class] = $file;
                }
            }
        }
        ksort($classMap);
        return $classMap;
    }

    /**
     * @return string
     */
    protected function getSnippet() {
        return <<<'PSR4'
        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }


PSR4;
    }
}