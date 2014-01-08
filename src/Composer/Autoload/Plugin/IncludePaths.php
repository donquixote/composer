<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class IncludePaths extends AbstractPlugin
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
     * @return string
     */
    protected function getFileName()
    {
        return 'include_paths.php';
    }

    /**
     * @param BuildInterface $build
     * @return string|null
     */
    protected function buildPhpRows($build)
    {
        if (!isset($this->includePaths)) {
            return null;
        }

        $includePathsCode = '';
        foreach ($this->includePaths as $path) {
            $includePathsCode .= "    " . $build->getPathCode($path) . ",\n";
        }

        return $includePathsCode;
    }

    /**
     * @return string
     */
    protected function getSnippet() {
        return <<<'PSR4'
        $includePaths = require __DIR__ . '/include_paths.php';
        array_push($includePaths, get_include_path());
        set_include_path(join(PATH_SEPARATOR, $includePaths));


PSR4;
    }
}
