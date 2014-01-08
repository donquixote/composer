<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Package\PackageInterface;

abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var array
     */
    protected $map = array();

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     *
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        $packageAutoloads = $this->getPackageAutoloads($package);

        if (empty($packageAutoloads)) {
            // Skip this package.
            return;
        }

        $targetDir = $package->getTargetDir();
        if (null !== $targetDir && $isMainPackage) {
            $installPath = substr($installPath, 0, -strlen('/'.$package->getTargetDir()));
        }

        foreach ($packageAutoloads as $namespace => $paths) {
            foreach ((array) $paths as $path) {
                if ($targetDir && !is_readable($installPath . '/' . $path)) {
                    $path = $this->pathResolveTargetDir($path, $targetDir, $isMainPackage);
                }
                if (!empty($installPath)) {
                    $path = $installPath . '/' . $path;
                } elseif (empty($path)) {
                    $path = '.';
                }
                $this->map[$namespace][] = $path;
            }
        }
    }

    /**
     * @param PackageInterface $package
     * @return array|null
     */
    protected function getPackageAutoloads(PackageInterface $package)
    {
        return NULL;
    }

    /**
     * @param string $path
     * @param string $targetDir
     * @param bool $isMainPackage
     * @return string
     */
    protected function pathResolveTargetDir($path, $targetDir, $isMainPackage)
    {
        return $path;
    }

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        $phpRows = $this->buildPhpRows($build);
        if (!isset($phpRows)) {
            return;
        }
        $build->addArraySourceFile($this->getFileName(), $phpRows);
        $build->addPhpSnippet($this->getSnippet());
    }

    /**
     * @return string
     */
    abstract protected function getFileName();

    /**
     * @param BuildInterface $build
     * @return string|null
     */
    protected function buildPhpRows($build)
    {
        krsort($this->map);

        // Generate the autoload_psr4.php file.
        $phpRows = '';
        foreach ($this->map as $namespace => $paths) {
            $exportedPaths = array();
            foreach ($paths as $path) {
                $exportedPaths[] = $build->getPathCode($path);
            }
            $exportedPrefix = var_export($namespace, true);
            $phpRows .= "    $exportedPrefix => ";
            $phpRows .= "array(".implode(', ', $exportedPaths)."),\n";
        }
    }

    /**
     * @return string
     */
    abstract protected function getSnippet();
}