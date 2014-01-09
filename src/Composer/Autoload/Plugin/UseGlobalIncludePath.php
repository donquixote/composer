<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;

class UseGlobalIncludePath implements PluginInterface
{
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

        $build->addPhpSnippet(<<<'EOT'
        $loader->setUseIncludePath(true);

EOT
        );
    }
}
