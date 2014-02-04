<?php

namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;

interface PluginInterface
{
    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     */
    public function addPackage(PackageInterface $package, $installPath, $isMainPackage);

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader);

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build);
} 