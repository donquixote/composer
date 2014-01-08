<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

class RegisterLoader implements PluginInterface
{
    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     * @internal param $order
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        // Nothing to do here.
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        $classLoader->register($prependAutoloader);
    }

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        $prependAutoloader = $build->prependAutoloader() ? 'true' : 'false';

        $snippet = <<<EOF
        \$loader->register($prependAutoloader);


EOF;
        $build->addPhpSnippet($snippet);
    }
}
