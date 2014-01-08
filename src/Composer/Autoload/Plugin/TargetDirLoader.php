<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class TargetDirLoader implements PluginInterface
{
    /**
     * @var string|null
     */
    private $targetDir;

    /**
     * @var array|null
     */
    private $mainPackagePsr0;

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     * @internal param $order
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        if (!$isMainPackage) {
            return;
        }

        $mainAutoload = $package->getAutoload();
        $targetDir = $package->getTargetDir();

        if (!$targetDir || empty($mainAutoload['psr-0'])) {
            return;
        }

        $this->targetDir = $targetDir;
        $this->mainPackagePsr0 = $mainAutoload['psr-0'];
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        // Do nothing.
    }

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        if (!isset($this->targetDir) || !isset($this->mainPackagePsr0)) {
            return;
        }

        $filesystem = $build->getFilesystem();

        $levels = count(explode('/', $filesystem->normalizePath($this->targetDir)));

        $prefixes = array();
        foreach (array_keys($this->mainPackagePsr0) as $prefix) {
            $prefixes[] = var_export($prefix, true);
        }
        $prefixes = implode(', ', $prefixes);

        $baseDirFromTargetDirCode = $filesystem->findShortestPathCode($build->getTargetDir(), $build->getBasePath(), true);

        $targetDirLoader = <<<EOF

    public static function autoload(\$class)
    {
        \$dir = $baseDirFromTargetDirCode . '/';
        \$prefixes = array($prefixes);
        foreach (\$prefixes as \$prefix) {
            if (0 !== strpos(\$class, \$prefix)) {
                continue;
            }
            \$path = \$dir . implode('/', array_slice(explode('\\\\', \$class), $levels)).'.php';
            if (!\$path = stream_resolve_include_path(\$path)) {
                return false;
            }
            require \$path;

            return true;
        }
    }

EOF;
        $build->addMethod($targetDirLoader);

        $suffix = $build->getSuffix();

        $snippet = <<<EOF
        spl_autoload_register(array('ComposerAutoloaderInit$suffix', 'autoload'), true, true);


EOF;
        $build->addPhpSnippet($snippet);
    }
}
