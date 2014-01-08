<?php

namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;

interface PluginInterface
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
