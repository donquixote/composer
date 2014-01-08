<?php


namespace Composer\Package;


class PackageMap
{
    /**
     * @var PackageInterface
     */
    protected $mainPackage;

    /**
     * @var array
     */
    protected $packageMap = array();

    /**
     * @var array
     */
    protected $sortedPackageMap;

    /**
     * @param PackagePathFinderInterface $packagePathFinder
     * @param PackageInterface[] $packages
     * @param PackageInterface $mainPackage
     */
    function __construct(PackagePathFinderInterface $packagePathFinder, array $packages, PackageInterface $mainPackage = null)
    {
        $this->mainPackage = $mainPackage;

        foreach ($packages as $package) {
            if ($package instanceof AliasPackage) {
                continue;
            }
            $this->validatePackage($package);

            $this->packageMap[] = array(
                $package,
                $packagePathFinder->getInstallPath($package)
            );
        }

        $this->sortedPackageMap = $this->sortPackageMap($this->packageMap);

        if ($mainPackage) {
            $this->sortedPackageMap[] = array($mainPackage, '');
            array_unshift($this->packageMap, array($mainPackage, ''));
        }
    }

    /**
     * @param PackageConsumerInterface $consumer
     */
    public function processPackageConsumer(PackageConsumerInterface $consumer)
    {
        $map = ($consumer instanceof SortedPackageConsumerInterface)
          ? $this->sortedPackageMap
          : $this->packageMap;
        foreach ($map as $item) {
            /** @var PackageInterface $package */
            list($package, $installPath) = $item;
            $consumer->addPackage($package, $installPath, $package === $this->mainPackage);
        }
    }

    /**
     * @param PackageInterface $package
     *
     * @throws \InvalidArgumentException Throws an exception, if the package has illegal settings.
     */
    protected function validatePackage(PackageInterface $package)
    {
        $autoload = $package->getAutoload();
        if (!empty($autoload['psr-4']) && null !== $package->getTargetDir()) {
            $name = $package->getName();
            $package->getTargetDir();
            throw new \InvalidArgumentException("PSR-4 autoloading is incompatible with the target-dir property, remove the target-dir in package '$name'.");
        }
        if (!empty($autoload['psr-4'])) {
            foreach ($autoload['psr-4'] as $namespace => $dirs) {
                if ($namespace !== '' && '\\' !== substr($namespace, -1)) {
                    throw new \InvalidArgumentException("psr-4 namespaces must end with a namespace separator, '$namespace' does not, use '$namespace\\'.");
                }
            }
        }
    }

    /**
     * Sort the package map so that packages without dependencies are first, and
     * packages only depend on previous packages.
     *
     * @param array $packageMap
     *   Each array value is of the form array($package, $installPath).
     * @return array
     *   Each array value is of the form array($package, $installPath).
     */
    protected function sortPackageMap(array $packageMap)
    {
        $positions = array();
        $names = array();
        $indexes = array();

        foreach ($packageMap as $position => $item) {
            /** @var PackageInterface $package */
            $package = $item[0];
            $mainName = $package->getName();
            $names = array_merge(array_fill_keys($package->getNames(), $mainName), $names);
            $names[$mainName] = $mainName;
            $indexes[$mainName] = $positions[$mainName] = $position;
        }

        foreach ($packageMap as $item) {
            /** @var PackageInterface $package */
            $package = $item[0];
            $position = $positions[$package->getName()];
            /** @var Link $link */
            foreach (array_merge($package->getRequires(), $package->getDevRequires()) as $link) {
                $target = $link->getTarget();
                if (!isset($names[$target])) {
                    continue;
                }

                $target = $names[$target];
                if ($positions[$target] <= $position) {
                    continue;
                }

                foreach ($positions as $key => $value) {
                    if ($value >= $position) {
                        break;
                    }
                    $positions[$key]--;
                }

                $positions[$target] = $position - 1;
            }
            asort($positions);
        }

        $sortedPackageMap = array();
        foreach (array_keys($positions) as $packageName) {
            $sortedPackageMap[] = $packageMap[$indexes[$packageName]];
        }

        return $sortedPackageMap;
    }
} 
