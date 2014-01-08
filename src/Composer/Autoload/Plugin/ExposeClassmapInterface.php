<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;

interface ExposeClassmapInterface {

    /**
     * @param BuildInterface $build
     * @return string[]
     *   Class map.
     */
    public function buildClassMap(BuildInterface $build = NULL);
} 
