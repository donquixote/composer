<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Autoload\ClassMapGenerator;
use Composer\Package\PackageInterface;

class Classmap extends AbstractPlugin implements ExposeClassmapInterface
{
    /**
     * @var ExposeClassmapInterface[]
     */
    private $sources = array();

    /**
     * @param ExposeClassmapInterface $source
     */
    function addClassmapSource(ExposeClassmapInterface $source)
    {
        $this->sources[] = $source;
    }

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
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        // Attention, this is expensive!
        $classMap = $this->buildCombinedClassMap();
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
        foreach ($this->buildCombinedClassMap($build) as $class => $file) {
            $code = $build->getPathCode($file);
            $phpRows .= '    ' . var_export($class, true) . ' => ' . $code . ",\n";
        }

        if (empty($phpRows)) {
            // Suppress the class map.
            # return null;
        }

        return $phpRows;
    }

    /**
     * @param BuildInterface $build
     * @return string[]
     */
    public function buildCombinedClassMap($build = NULL)
    {
        $classMap = array();
        foreach ($this->sources as $source) {
            $classMap += $source->buildClassMap($build);
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

    /**
     * @return string[]
     *   Paths to scan for class map.
     */
    function getClassmapPaths()
    {
        $pathsAll = array();
        foreach ($this->map as $paths) {
            foreach ($paths as $path) {
                $pathsAll[] = $path;
            }
        }
        return $pathsAll;
    }

    /**
     * @param BuildInterface $build
     * @return string[]
     *   Class map.
     */
    function buildClassMap(BuildInterface $build = NULL)
    {
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