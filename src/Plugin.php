<?php

/*
 * This file is part of the "sbuzonas/composer-test-runner" package.
 *
 * Copyright (c) 2016 Steve Buzonas <steve@fancyguy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SLB\Composer\TestRunner;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable
{
    private $enabled;

    public function activate(Composer $composer, IOInterface $io)
    {
        if ($this->isLocalPackage()) {
            $this->enabled = true;
        }

        $io->debug(sprintf('CarnegieLearning ComposerTestPlugin is %s', $this->enabled ? 'enabled' : 'disabled'));
    }

    public function getCapabilities()
    {
        if ( ! $this->enabled) {
            return array();
        }

        return array(
            'Composer\Plugin\Capability\CommandProvider' => 'SLB\Composer\TestRunner\Capability\CommandProvider',
        );
    }

    private function isLocalPackage()
    {
        $packageConfig = Factory::getComposerFile();
        if (is_string($packageConfig)) {
            $file = new JsonFile($packageConfig);

            return $file->exists();
        }

        return false;
    }
}
