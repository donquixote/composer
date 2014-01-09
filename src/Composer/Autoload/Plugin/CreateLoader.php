<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;

class CreateLoader implements PluginInterface
{
    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        // Nothing to do here.
    }

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        $vendorPathCode = $build->getVendorPathCode();
        $appBaseDirCode = $build->getAppDirBaseCode();
        $suffix = $build->getSuffix();
        $prependAutoloader = $build->prependAutoloader() ? 'true' : 'false';

        $build->addPhpSnippet(<<<EOT
        if (null !== self::\$loader) {
            return self::\$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit$suffix', 'loadClassLoader'), true, $prependAutoloader);
        self::\$loader = \$loader = new \\Composer\\Autoload\\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit$suffix', 'loadClassLoader'));

        \$vendorDir = $vendorPathCode;
        \$baseDir = $appBaseDirCode;


EOT
        );
    }
}
