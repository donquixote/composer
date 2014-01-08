<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class UseGlobalIncludePath implements PluginInterface {

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     * @internal param $order
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        // Do nothing.
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
        if (!$build->useGlobalIncludePath()) {
            return;
        }

        $snippet = <<<'EOT'
        $loader->setUseIncludePath(true);

EOT;
        $build->addPhpSnippet($snippet);
    }
}
