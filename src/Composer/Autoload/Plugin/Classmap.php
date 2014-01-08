<?php


namespace Composer\Autoload\Plugin;


use Composer\Autoload\BuildInterface;
use Composer\Autoload\ClassLoader;

/**
 * Registers an accumulated classmap to the class loader, based on sources added
 * with addClassmapSource().
 *
 * This plugin does NOT look into ['autoload']['classmap'] in composer.json,
 * this is the job of the sources. See ClassmapPackageConsumer.
 */
class Classmap implements PluginInterface
{
    /**
     * @var ExposeClassmapInterface[]
     */
    private $sources = array();

    /**
     * @param ExposeClassmapInterface $source
     */
    function addClassmapSource(ExposeClassmapInterface $source)
    {
        $this->sources[] = $source;
    }

    /**
     * @param ClassLoader $classLoader
     * @param bool $prependAutoloader
     */
    public function initClassLoader(ClassLoader $classLoader, $prependAutoloader)
    {
        // Attention, this is expensive!
        $classMap = $this->buildCombinedClassMap();
        $classLoader->addClassMap($classMap);
    }

    /**
     * @param BuildInterface $build
     */
    public function generate(BuildInterface $build)
    {
        $phpRows = $this->buildPhpRows($build);
        if (!isset($phpRows)) {
            return;
        }
        $build->addArraySourceFile('autoload_classmap.php', $phpRows);
        $build->addPhpSnippet($this->getSnippet());
    }

    /**
     * @param BuildInterface $build
     * @return string
     */
    protected function buildPhpRows($build)
    {
        $phpRows = '';
        foreach ($this->buildCombinedClassMap($build) as $class => $file) {
            $code = $build->getPathCode($file);
            $phpRows .= '    ' . var_export($class, true) . ' => ' . $code . ",\n";
        }

        if (empty($phpRows)) {
            // Suppress the class map.
            # return null;
        }

        return $phpRows;
    }

    /**
     * @param BuildInterface $build
     * @return string[]
     */
    public function buildCombinedClassMap($build = NULL)
    {
        $classMap = array();
        foreach ($this->sources as $source) {
            $classMap += $source->buildClassMap($build);
        }
        ksort($classMap);
        return $classMap;
    }

    /**
     * @return string
     */
    protected function getSnippet() {
        return <<<'PSR4'
        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }


PSR4;
    }
}
