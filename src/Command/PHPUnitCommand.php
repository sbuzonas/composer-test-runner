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
use Symfony\Component\Console\Output\OutputInterface;

class PHPUnitCommand extends BaseCommand
{
    public function isEnabled()
    {
        return $this->isPackageInstalled('phpunit/phpunit', '^4.5 || ^5.0.5');
    }

    protected function configure()
    {
        $this
            ->setName('test:phpunit')
            ->setDescription('Runs PHPUnit tests for the current package')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadRuntimeDependency('phpunit/phpunit', '^4.5 || ^5.0.5');

        $runner = new \PHPUnit_TextUI_Command();

        $settings = $this->getSettings($input, $output);

        $runner->run($settings, false);
    }

    private function getSettings(InputInterface $input, OutputInterface $output)
    {
        return array();
    }
}
