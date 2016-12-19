<?php

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
