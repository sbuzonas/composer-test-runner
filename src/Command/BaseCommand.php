<?php

namespace SLB\Composer\TestRunner\Command;

use Composer\Command\BaseCommand as ComposerCommand;
use Composer\Factory;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;

abstract class BaseCommand extends ComposerCommand
{

    protected function getRootPackagePath()
    {
        return dirname(realpath(Factory::getComposerFile()));
    }

    protected function makePathRelativeToRoot($path)
    {
        $executor = new ProcessExecutor($this->getIO());
        $fs = new Filesystem($executor);

        $relativePath = $fs->findShortestPath($this->getRootPackagePath(), $path, true);

        return rtrim($relativePath, DIRECTORY_SEPARATOR);
    }

    protected function enableOptionalPackage($name, $constraint = '*')
    {
        if (!$package = $this->getComposerPackage($name, $constraint)) {
            if ('*' !== $constraint) {
                $name = sprintf('%s:%s', $name, $constraint);
            }

            throw new \UnexpectedValueException(sprintf('The "%s" command depends on "%s" which could not be loaded.', $this->getName(), $name));
        }

        $pm = $this->getComposer()->getPluginManager();

        // HACK: Composer expects packages registered in the PluginManager to
        // be plugins themselves, we want to optionally autoload extra
        // dependencies if they are available
        $extra = $package->getExtra();
        if (empty($extra['class'])) {
            $prefix = $classDummy = '_dummy';
            $dummyCounter = 0;
            // Find a unique unloaded class name so we can fail to load it
            while (class_exists($classDummy, false)) {
                $classDummy = sprintf('%s_%d', $prefix, $dummyCounter++);
            }
            $extra['class'] = $classDummy;
            $package->setExtra($extra);
        }

        $pm->registerPackage($package);
    }

    protected function isPackageInstalled($name, $constraint = '*')
    {
        return null !== $this->getComposerPackage($name, $constraint);
    }

    private function getComposerPackage($name, $constraint = '*')
    {
        $localRepo = $this->getComposer()->getRepositoryManager()->getLocalRepository();
        return $localRepo->findPackage($name, $constraint);
    }
}
