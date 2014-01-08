<?php


namespace Composer\Autoload\Plugin;


use Composer\Package\PackageInterface;

abstract class AbstractSystemPlugin implements PluginInterface
{
    /**
     * @var bool
     */
    protected $prependAutoloader;

    /**
     * @param $prependAutoloader
     */
    public function __construct($prependAutoloader)
    {
        $this->prependAutoloader = $prependAutoloader;
    }

    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage)
    {
        // Nothing to do here.
    }
}