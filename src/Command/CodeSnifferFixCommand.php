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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CodeSnifferFixCommand extends CodeSnifferCommand
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->getDefinition()->addOption(new InputOption('fix', null, InputOption::VALUE_NONE, 'Try to fix violations'));

        parent::run($input, $output);
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $input->setOption('fix', true);

        parent::initialize($input, $output);
    }

    protected function configure()
    {
        $this
            ->setName('test:phpcbf')
            ->setDescription('Run PHP_CodeSniffer against the current package (implies fix)')
            ->setDefinition(array_merge(
                $this->getCommonOptions(),
                $this->getFixerOptions()
            ))
        ;
    }
}
