<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class Files extends AbstractPlugin
{
    /**
     * @param PackageInterface $package
     *
     * @return array|null
     */
    protected function getPackageAutoloads(PackageInterface $package)
    {
        $autoload = $package->getAutoload();

        if (!isset($autoload['files']) || !is_array($autoload['files'])) {
            // Skip this package.
            return null;
        }

        return $autoload['files'];
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
            // remove target-dir from file paths of the root package
            $targetDir = str_replace('\\<dirsep\\>', '[\\\\/]', preg_quote(str_replace(array('/', '\\'), '<dirsep>', $targetDir)));
            return ltrim(preg_replace('{^'.$targetDir.'}', '', ltrim($path, '\\/')), '\\/');
        }
        else {
            // add target-dir from file paths that don't have it
            return $targetDir . '/' . $path;
        }
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        foreach ($this->map as $files) {
            foreach ($files as $file) {
                require $file;
            }
        }
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return 'autoload_files.php';
    }

    /**
     * @param BuildInterface $build
     * @return string
     */
    protected function buildPhpRows($build)
    {
        $filesCode = '';
        foreach ($this->map as $files) {
            foreach ($files as $file) {
                $filesCode .= '    ' . $build->getPathCode($file) . ",\n";
            }
        }

        if (!$filesCode) {
            return null;
        }

        return $filesCode;
    }

    /**
     * @return string
     */
    protected function getSnippet() {
        return <<<'PSR4'
        $includeFiles = require __DIR__ . '/autoload_files.php';
        foreach ($includeFiles as $file) {
            require $file;
        }


PSR4;
    }
}