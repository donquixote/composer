<?php


namespace Composer\Package;


interface PackageConsumerInterface
{
    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     */
    function addPackage(PackageInterface $package, $installPath, $isMainPackage);
} 
