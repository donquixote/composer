<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassMapGenerator;
use Composer\Package\PackageConsumerInterface;
use Composer\Package\PackageInterface;

abstract class AbstractPlugin implements PluginInterface, PackageConsumerInterface
{
    /**
     * @var array
     */
    protected $map = array();

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     * @internal param $order
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        $packageAutoloads = $this->getPackageAutoloads($package);

        if (empty($packageAutoloads)) {
            // Skip this package.
            return;
        }

        $targetDir = $package->getTargetDir();
        if (null !== $targetDir && !$isMainPackage) {
            $installPath = substr($installPath, 0, -strlen('/' . $targetDir));
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
     * @internal param int $order
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

        return $phpRows;
    }

    /**
     * @return string
     */
    abstract protected function getSnippet();

    /**
     * @param BuildInterface $build
     * @param string $psrType
     *   Either 'psr-0' or 'psr-4'.
     * @return string[]
     *   Class map.
     */
    protected function buildClassMapBase(BuildInterface $build, $psrType)
    {
        $filesystem = $build->getFilesystem();
        $classMap = array();
        foreach ($this->map as $namespace => $paths) {
            foreach ($paths as $dir) {
                if (!$filesystem->isAbsolutePath($dir)) {
                    $dir = $build->getBasePath() . '/' . $dir;
                }
                $dir = $build->getFilesystem()->normalizePath($dir);
                if (!is_dir($dir)) {
                    continue;
                }
                $whitelist = sprintf(
                  '{%s/%s.+(?<!(?<!/)Test\.php)$}',
                  preg_quote($dir),
                  ($psrType === 'psr-4' || strpos($namespace, '_') === false)
                    ? preg_quote(strtr($namespace, '\\', '/'))
                    : ''
                );
                foreach (ClassMapGenerator::createMap($dir, $whitelist) as $class => $path) {
                    if ('' === $namespace || 0 === strpos($class, $namespace)) {
                        if (!isset($classMap[$class])) {
                            $classMap[$class] = $path;
                        }
                    }
                }

            }
        }
        return $classMap;
    }
}
