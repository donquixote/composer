<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;

class RegisterLoader implements PluginInterface
{
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

        $build->addPhpSnippet(<<<EOF
        \$loader->register($prependAutoloader);


EOF
        );
    }
}
