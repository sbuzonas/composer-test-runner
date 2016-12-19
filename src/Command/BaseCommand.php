<?php

/*
 * This file is part of the "sbuzonas/composer-test-runner" package.
 *
 * Copyright (c) 2016 Steve Buzonas <steve@fancyguy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SLB\Composer\TestRunner\Command;

use Composer\Command\BaseCommand as ComposerCommand;
use Composer\Factory;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use SLB\Composer\TestRunner\Util\PackageManager;

abstract class BaseCommand extends ComposerCommand
{
    private $packageManager;

    protected function getRootPackagePath()
    {
        return dirname(realpath(Factory::getComposerFile()));
    }

    protected function makePathRelativeToRoot($path)
    {
        $executor = new ProcessExecutor($this->getIO());
        $fs       = new Filesystem($executor);

        $relativePath = $fs->findShortestPath($this->getRootPackagePath(), $path, true);

        return rtrim($relativePath, DIRECTORY_SEPARATOR);
    }

    protected function loadRuntimeDependency($name, $constraint = '*')
    {
        $this->getPackageManager()->registerPackage($name, $constraint);
    }

    protected function isPackageInstalled($name, $constraint = '*')
    {
        return $this->getPackageManager()->isPackageInstalled($name, $constraint);
    }

    protected function getPackageManager()
    {
        if ( ! $this->packageManager) {
            $this->packageManager = new PackageManager($this->getComposer());
        }

        return $this->packageManager;
    }
}
