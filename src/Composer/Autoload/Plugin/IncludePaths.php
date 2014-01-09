<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageConsumerInterface;
use Composer\Package\PackageInterface;

class IncludePaths implements PluginInterface, PackageConsumerInterface
{
    /**
     * @var string[]|null
     */
    protected $includePaths;

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     * @internal param $order
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        $targetDir = $package->getTargetDir();

        if (null !== $targetDir && strlen($targetDir) > 0) {
            $installPath = substr($installPath, 0, -strlen('/' . $targetDir));
        }

        foreach ($package->getIncludePaths() as $includePath) {
            $includePath = trim($includePath, '/');
            $this->includePaths[] = empty($installPath)
              ? $includePath
              : $installPath . '/' . $includePath;
        }
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        if (!isset($this->includePaths)) {
            return;
        }

        $includePaths = $this->includePaths;
        array_push($includePaths, get_include_path());
        set_include_path(join(PATH_SEPARATOR, $includePaths));
    }

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        if (!isset($this->includePaths)) {
            return;
        }

        $includePathsCode = '';
        foreach ($this->includePaths as $path) {
            $includePathsCode .= "    " . $build->getPathCode($path) . ",\n";
        }
        $build->addArraySourceFile('include_paths.php', $includePathsCode);

        $build->addPhpSnippet(<<<'EOT'
        $includePaths = require __DIR__ . '/include_paths.php';
        array_push($includePaths, get_include_path());
        set_include_path(join(PATH_SEPARATOR, $includePaths));


EOT
        );
    }
}
