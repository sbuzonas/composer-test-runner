<?php

namespace SLB\Composer\TestRunner\Util;

use Composer\Composer;
use Composer\DependencyResolver\Pool;
use Composer\Package\Package;
use Composer\Package\PackageInterface;

class PackageManager
{

    /**
     * @var Composer
     */
    private $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function isPackageInstalled($name, $constraint = '*')
    {
        return null !== $this->getPackage($name, $constraint);
    }

    public function registerPackage($name, $constraint = '*')
    {
        $package = $this->getPackage($name, $constraint);

        $pool = new Pool('dev');
        $pool->addRepository($this->getLocalInstalledRepository());

        if ($globalRepo = $this->getGlobalInstalledRepository()) {
            $pool->addRepository($globalRepo);
        }

        $autoloadPackages = array($package->getName() => $package);
        $autoloadPackages = $this->resolveDependencies($pool, $autoloadPackages, $package);

        $generator = $this->composer->getAutoloadGenerator();
        $autoloads = array();
        foreach ($autoloadPackages as $autoloadPackage) {
            $downloadPath = $this->getInstallPath($autoloadPackage, ($globalRepo && $globalRepo->hasPackage($autoloadPackage)));
            $autoloads[] = array($autoloadPackage, $downloadPath);
        }

        $map = $generator->parseAutoloads($autoloads, new Package('dummy', '1.0.0.0', '1.0.0'));
        $classLoader = $generator->createLoader($map);
        $classLoader->register(true);
    }

    private function resolveDependencies(Pool $pool, array $collected, PackageInterface $package)
    {
        foreach ($package->getRequires() as $requireLink) {
            $requiredPackage = $this->getPackage($requireLink->getTarget(), $requireLink->getConstraint());
            if ($requiredPackage && !isset($collected[$requiredPackage->getName()])) {
                $collected[$requiredPackage->getName()] = $requiredPackage;
                $collected = $this->resolveDependencies($pool, $collected, $requiredPackage);
            }
        }

        return $collected;
    }

    private function getPackage($name, $constraint)
    {
        $package = $this->getLocalInstalledRepository()->findPackage($name, $constraint);

        if (!$package && $this->getGlobalComposer()) {
            $package = $this->getGlobalInstalledRepository()->findPackage($name, $constraint);
        }

        return $package;
    }

    private function getLocalInstalledRepository()
    {
        return $this->getInstalledRepository($this->composer);
    }

    private function getGlobalInstalledRepository()
    {
        return $this->getInstalledRepository($this->getGlobalComposer());
    }

    private function getInstalledRepository(Composer $composer)
    {
        return $composer->getRepositoryManager()->getLocalRepository();
    }

    private function getGlobalComposer()
    {
        return $this->composer->getPluginManager()->getGlobalComposer();
    }

    private function getInstallPath(PackageInterface $package, $global = false)
    {
        if (!$global) {
            return $this->composer->getInstallationManager()->getInstallPath($package);
        }

        return $this->getGlobalComposer()->getInstallationManager()->getInstallPath($package);
    }
}
