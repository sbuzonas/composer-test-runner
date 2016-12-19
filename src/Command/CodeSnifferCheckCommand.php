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

class CodeSnifferCheckCommand extends CodeSnifferCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('test:phpcs')
            ->setDescription('Run PHP_CodeSniffer against the current package (check only)')
            ->setDefinition(array_merge(
                $this->getCommonOptions(),
                $this->getCheckOptions()
            ))
        ;
    }
}
