<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassMapGenerator;
use Composer\Package\PackageInterface;
use Composer\Package\SortedPackageConsumerInterface;

/**
 * Scans the ['autoload']['classmap'] in composer.json, and exposes a classmap
 * via the ->buildClassMap() method.
 */
class ClassmapPackageConsumer extends AbstractPackageConsumer implements ExposeClassmapInterface, SortedPackageConsumerInterface
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
            return NULL;
        }

        return $autoload['classmap'];
    }

    /**
     * @param string $path
     * @param string $targetDir
     * @param bool $isMainPackage
     *
     * @return string
     */
    protected function pathResolveTargetDir($path, $targetDir, $isMainPackage)
    {
        if ($isMainPackage) {
            // remove target-dir from classmap entries of the root package
            $targetDir = str_replace('\\<dirsep\\>', '[\\\\/]', preg_quote(str_replace(array('/', '\\'), '<dirsep>', $targetDir)));
            return ltrim(preg_replace('{^'.$targetDir.'}', '', ltrim($path, '\\/')), '\\/');
        }
        else {
            // add target-dir to classmap entries that don't have it
            return $targetDir . '/' . $path;
        }
    }

    /**
     * @param BuildInterface $build
     * @return string[]
     *   Class map.
     */
    public function buildClassMap(BuildInterface $build = NULL)
    {
        ksort($this->map);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->map));
        $classMap = array();
        foreach ($iterator as $dir) {
            foreach (ClassMapGenerator::createMap($dir) as $class => $path) {
                $classMap[$class] = $path;
            }
        }
        return $classMap;
    }
}
