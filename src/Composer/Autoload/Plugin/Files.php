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
            return FALSE;
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