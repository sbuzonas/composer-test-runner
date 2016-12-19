<?php

namespace SLB\Composer\TestRunner\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CodeSnifferFixCommand extends CodeSnifferCommand
{

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
}
