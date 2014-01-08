<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassMapGenerator;

abstract class AbstractPlugin extends AbstractPackageConsumer implements PluginInterface
{
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
