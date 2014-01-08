<?php


namespace Composer\Autoload;


use Composer\Config;
use Composer\Util\Filesystem;

class Build implements BuildInterface
{
    //                                                        Constructor values
    // -------------------------------------------------------------------------

    /**
     * @var string
     */
    private $vendorPath;

    /**
     * @var string
     */
    private $targetDir;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $vendorPathCode;

    /**
     * @var string
     */
    private $vendorPathCode52;

    /**
     * @var string
     */
    private $vendorPathToTargetDirCode;

    /**
     * @var string
     */
    private $appBaseDirCode;

    /**
     * @var string
     */
    private $suffix;

    /**
     * @var bool
     */
    private $useGlobalIncludePath;

    /**
     * @var bool
     */
    private $prependAutoloader;

    //                                                          Collected values
    // -------------------------------------------------------------------------

    /**
     * PHP snippets for AutoloaderInit::getLoader().
     *
     * @var string[]
     */
    private $snippets = array();

    /**
     * @var string[]
     */
    private $files = array();

    /**
     * @var string[]
     */
    private $methods = array();

    //                                                               Constructor
    // -------------------------------------------------------------------------

    /**
     * @param Config $config
     * @param string $targetDir
     * @param string $suffix
     */
    public function __construct(Config $config, $targetDir, $suffix)
    {
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('vendor-dir'));

        $basePath = $filesystem->normalizePath(realpath(getcwd()));
        $vendorPath = $filesystem->normalizePath(realpath($config->get('vendor-dir')));

        $targetDir = $vendorPath . '/' . $targetDir;
        $filesystem->ensureDirectoryExists($targetDir);

        $vendorPathCode = $filesystem->findShortestPathCode(realpath($targetDir), $vendorPath, true);
        $vendorPathCode52 = str_replace('__DIR__', 'dirname(__FILE__)', $vendorPathCode);
        $vendorPathToTargetDirCode = $filesystem->findShortestPathCode($vendorPath, realpath($targetDir), true);

        $appBaseDirCode = $filesystem->findShortestPathCode($vendorPath, $basePath, true);
        $appBaseDirCode = str_replace('__DIR__', '$vendorDir', $appBaseDirCode);

        $useGlobalIncludePath = (bool) $config->get('use-include-path');
        $prependAutoloader = (false !== $config->get('prepend-autoloader'));

        $this->filesystem = $filesystem;
        $this->targetDir = $targetDir;
        $this->basePath = $basePath;
        $this->vendorPath = $vendorPath;
        $this->suffix = $suffix;
        $this->vendorPathCode = $vendorPathCode;
        $this->vendorPathCode52 = $vendorPathCode52;
        $this->vendorPathToTargetDirCode = $vendorPathToTargetDirCode;
        $this->appBaseDirCode = $appBaseDirCode;
        $this->useGlobalIncludePath = $useGlobalIncludePath;
        $this->prependAutoloader = $prependAutoloader;
    }

    //                                                                   Getters
    // -------------------------------------------------------------------------

    /**
     * @param string $path
     * @return string
     */
    public function getPathCode($path)
    {
        if (!$this->filesystem->isAbsolutePath($path)) {
            $path = $this->basePath . '/' . $path;
        }
        $path = $this->filesystem->normalizePath($path);

        $baseDir = '';
        if (strpos($path . '/', $this->vendorPath . '/') === 0) {
            $path = substr($path, strlen($this->vendorPath));
            $baseDir = '$vendorDir';

            if ($path !== false) {
                $baseDir .= " . ";
            }
        } else {
            $path = $this->filesystem->findShortestPath($this->basePath, $path, true);
            $path = $this->filesystem->normalizePath($path);
            if (!$this->filesystem->isAbsolutePath($path)) {
                $baseDir = '$baseDir . ';
                $path = '/' . $path;
            }
        }

        if (preg_match('/\.phar$/', $path)) {
            $baseDir = "'phar://' . " . $baseDir;
        }

        return $baseDir . (($path !== false) ? var_export($path, true) : "");
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getTargetDir()
    {
        return $this->targetDir;
    }

    /**
     * @return string
     */
    public function getAppDirBaseCode() {
        return $this->appBaseDirCode;
    }

    /**
     * @return string
     */
    public function getVendorPathCode() {
        return $this->vendorPathCode;
    }

    /**
     * @return bool
     */
    public function useGlobalIncludePath()
    {
        return $this->useGlobalIncludePath;
    }

    /**
     * @return bool
     */
    public function prependAutoloader()
    {
        return $this->prependAutoloader;
    }

    //                                    Methods to add parts to AutoloaderInit
    // -------------------------------------------------------------------------

    /**
     * Adds a PHP snippet to the AutoloaderInit::getLoader() method.
     *
     * @param string $snippet
     */
    public function addPhpSnippet($snippet)
    {
        $this->snippets[] = $snippet;
    }

    /**
     * Adds a file to be dumped in the vendor/composer/ directory.
     *
     * @param string $filename
     *   Name of the file, relative to vendor/composer/.
     * @param string $contents
     *   File contents.
     */
    public function addFile($filename, $contents)
    {
        $this->files[$filename] = $contents;
    }

    /**
     * Adds a php source file to be dumped in the vendor/composer/ directory.
     *
     * This file will have some headers automatically added.
     *
     * @param string $filename
     *   Name of the file, relative to vendor/composer/.
     * @param string $php
     *   PHP code to follow after the headers.
     */
    public function addSourceFile($filename, $php)
    {
        $vendorPathCode52 = str_replace('__DIR__', 'dirname(__FILE__)', $this->vendorPathCode);
        $headers = <<<EOF
<?php

// $filename @generated by Composer

\$vendorDir = $vendorPathCode52;
\$baseDir = $this->appBaseDirCode;

EOF;
        $this->addFile($filename, $headers . $php);
    }

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
    public function addArraySourceFile($filename, $phpRows)
    {
        $php = <<<EOF

return array(
$phpRows);

EOF;
        $this->addSourceFile($filename, $php);
    }

    /**
     * @param string $methodCode
     */
    public function addMethod($methodCode)
    {
        $this->methods[] = $methodCode;
    }

    // -------------------------------------------------------------------------

    /**
     * Generate the files.
     *
     * @return string[]
     *   File contents by file path.
     */
    public function generateFiles()
    {
        $files = array();

        foreach ($this->files as $filename => $contents) {
            $files[$this->targetDir . '/' . $filename] = $contents;
        }

        $files[$this->targetDir . '/autoload_real.php'] = $this->getAutoloadRealFile();
        $files[$this->vendorPath . '/autoload.php'] = $this->getAutoloadFile();

        return $files;
    }

    /**
     * @return string
     */
    private function getAutoloadRealFile()
    {
        $suffix = $this->getSuffix();
        $snippetsCode = implode('', $this->snippets);
        $methodsCode = implode('', $this->methods);

        $snippetsCode .= <<<EOT
        return \$loader;
EOT;

        return <<<EOT
<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit$suffix
{
    private static \$loader;

    public static function loadClassLoader(\$class)
    {
        if ('Composer\\Autoload\\ClassLoader' === \$class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    public static function getLoader()
    {
$snippetsCode
    }
$methodsCode}

EOT;
    }

    /**
     * @return string
     */
    protected function getAutoloadFile()
    {
        return <<<EOT
<?php

// autoload.php @generated by Composer

require_once $this->vendorPathToTargetDirCode . '/autoload_real.php';

return ComposerAutoloaderInit$this->suffix::getLoader();

EOT;
    }
}
