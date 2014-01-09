<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\SortedPackageConsumerInterface;

class Files extends AbstractPackageConsumer implements SortedPackageConsumerInterface, PluginInterface
{
    /**
     * Overrides parent property.
     *
     * @var string
     */
    protected $type = 'files';

    /**
     * Overrides parent property.
     *
     * @var bool
     */
    protected $mustResolveTargetDir = true;

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
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        ksort($this->map);

        $filesCode = '';
        foreach ($this->map as $files) {
            foreach ($files as $file) {
                $filesCode .= '    ' . $build->getPathCode($file) . ",\n";
            }
        }

        if (!$filesCode) {
            return;
        }

        $build->addArraySourceFile('autoload_files.php', $filesCode);

        $build->addPhpSnippet(<<<'EOT'
        $includeFiles = require __DIR__ . '/autoload_files.php';
        foreach ($includeFiles as $file) {
            require $file;
        }


EOT
        );
    }
}
