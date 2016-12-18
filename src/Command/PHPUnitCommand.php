<?php

namespace SLB\Composer\TestRunner\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PHPUnitCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            ->setName('test:phpunit')
            ->setDescription('Runs PHPUnit tests for the current package')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->enableOptionalPackage('phpunit/phpunit', '^4.5 || ^5.0.5');

        $runner = new \PHPUnit_TextUI_Command;

        $settings = $this->getSettings($input, $output);

        $runner->run($settings, false);
    }

    public function isEnabled()
    {
        return $this->isPackageInstalled('phpunit/phpunit', '^4.5 || ^5.0.5');
    }

    private function getSettings(InputInterface $input, OutputInterface $output)
    {
        return array();
    }
}
