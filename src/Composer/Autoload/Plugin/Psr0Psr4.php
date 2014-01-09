<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Autoload\ClassMapGenerator;
use Composer\Package\PackageInterface;

/**
 * Base class for PSR-0 and PSR-4 plugins.
 */
class Psr0Psr4 extends AbstractPackageConsumer implements PluginInterface, ClassmapProviderInterface
{
    /**
     * @var bool
     */
    protected $isPsr4;

    /**
     * @var string $filename
     *   Either 'autoload_namespaces.php' or 'autoload_psr4.php'.
     */
    protected $filename;

    /**
     * @param string $type
     *   Either 'psr-0' or 'psr-4'.
     *
     * @throws \Exception
     *   Argument was something other than 'psr-0' or 'psr-4'.
     */
    function __construct($type)
    {
        switch ($type) {
            case 'psr-0':
                $this->type = 'psr-0';
                $this->isPsr4 = false;
                $this->filename = 'autoload_namespaces.php';
                break;
            case 'psr-4':
                $this->type = 'psr-4';
                $this->isPsr4 = true;
                $this->filename = 'autoload_psr4.php';
                break;
            default:
                throw new \Exception("Invalid argument '$type'.");
        }
    }

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     *
     * @throws \Exception
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        if ($this->isPsr4 && null !== $package->getTargetDir()) {
            $autoload = $package->getAutoload();
            if (isset($autoload['psr-4']) && is_array($autoload['psr-4'])) {
                throw new \Exception("The ['target-dir'] setting is incompatible with the ['psr-4'] setting.");
            }
        }

        parent::addPackage($package, $installPath, $isMainPackage);
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        krsort($this->map);

        foreach ($this->map as $namespace => $paths) {
            if ($this->isPsr4) {
                $classLoader->addPsr4($namespace, $paths);
            }
            else {
                $classLoader->add($namespace, $paths);
            }
        }
    }

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        krsort($this->map);

        // Generate the autoload_*.php file.
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
        $build->addArraySourceFile($this->filename, $phpRows);

        $method = $this->isPsr4 ? 'setPsr4' : 'set';
        $build->addPhpSnippet(<<<EOT
        \$map = require __DIR__ . '/$this->filename';
        foreach (\$map as \$namespace => \$path) {
            \$loader->$method(\$namespace, \$path);
        }


EOT
        );
    }

    /**
     * Implements ClassmapProviderInterface::buildClassMap()
     *
     * @param BuildInterface $build
     * @return string[]
     *   Class map.
     */
    public function buildClassMap(BuildInterface $build = NULL)
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
                  ($this->isPsr4 || strpos($namespace, '_') === false)
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
