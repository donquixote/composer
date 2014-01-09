<?php


namespace Composer\Autoload;


use Composer\Util\Filesystem;

interface BuildInterface
{
    //                                                                   Getters
    // -------------------------------------------------------------------------

    /**
     * @param string $path
     * @return string
     */
    public function getPathCode($path);

    /**
     * @return string
     */
    public function getSuffix();

    /**
     * @return Filesystem
     */
    public function getFilesystem();

    /**
     * @return string
     */
    public function getBasePath();

    /**
     * @return string
     */
    public function getTargetDir();

    /**
     * @return string
     */
    public function getAppDirBaseCode();

    /**
     * @return string
     */
    public function getVendorPathCode();

    /**
     * @return bool
     */
    public function useGlobalIncludePath();

    /**
     * @return bool
     */
    public function prependAutoloader();

    //                                    Methods to add parts to AutoloaderInit
    // -------------------------------------------------------------------------

    /**
     * Adds a PHP snippet to the AutoloaderInit::getLoader() method.
     *
     * @param string $snippet
     */
    public function addPhpSnippet($snippet);

    /**
     * Adds a php source file to be dumped in the vendor/composer/ directory.
     *
     * This file will have some headers automatically added.
     *
     * @param string $filename
     *   Name of the file, relative to vendor/composer/.
     * @param string $phpRows
     *   PHP code within the "return array(*)" statement.
     */
    public function addArraySourceFile($filename, $phpRows);

    /**
     * @param string $methodCode
     */
    public function addMethod($methodCode);
}
