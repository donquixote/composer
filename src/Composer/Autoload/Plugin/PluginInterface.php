<?php

namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageConsumerInterface;

interface PluginInterface extends PackageConsumerInterface
{
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
