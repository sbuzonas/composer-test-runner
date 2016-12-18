<?php

/*
 * This file is part of the "sbuzonas/composer-test-runner" package.
 *
 * Copyright (c) 2016 Steve Buzonas <steve@fancyguy.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SLB\Composer\TestRunner\Capability;

use Composer\Plugin\Capability\CommandProvider as CommandProviderInterface;
use SLB\Composer\TestRunner\Command;

class CommandProvider implements CommandProviderInterface
{
    public function getCommands()
    {
        return array(
            new Command\LintCommand,
            new Command\PHPUnitCommand,
        );
    }
}
