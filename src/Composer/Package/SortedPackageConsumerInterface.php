<?php


namespace Composer\Package;


interface SortedPackageConsumerInterface extends PackageConsumerInterface
{
    /**
     * @param PackageInterface $package
     * @param string $installPath
     * @param bool $isMainPackage
     */
    function addPackage(PackageInterface $package, $installPath, $isMainPackage);
} 
