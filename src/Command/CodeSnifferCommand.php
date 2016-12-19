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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CodeSnifferCommand extends BaseCommand
{
    private $diffFile;

    public function isEnabled()
    {
        return $this->isPackageInstalled('squizlabs/php_codesniffer', '^2.7.1');
    }

    protected function configure()
    {
        $this
            ->setName('test:code-sniffer')
            ->setDescription('Run PHP_CodeSniffer against the current package')
            ->setDefinition(array_merge(
                $this->getCommonOptions(),
                $this->getCheckOptions(),
                $this->getFixerOptions(),
                array(
                    new InputOption('print-style', null, InputOption::VALUE_OPTIONAL, 'Print the coding style in the specified format (Text, Markdown, HTML)', 'Text'),
                )
            ))
        ;
    }

    protected function getCommonOptions()
    {
        return array(
            new InputArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Paths to check'),
            new InputOption('sniffs', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, "Sniffs to include (or exclude if value starts with '-')"),
            new InputOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Paths to ignore'),
            new InputOption('bootstrap', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Files to include before beginning processing'),
            new InputOption('error-severity', null, InputOption::VALUE_REQUIRED, 'Minimum severity to display an error'),
            new InputOption('warning-severity', null, InputOption::VALUE_REQUIRED, 'Minimum severity to display a warning'),
            new InputOption('suppress-warnings', 's', InputOption::VALUE_NONE, 'Do not print warnings (shortcut for --warning-severity=0)'),
        );
    }

    protected function getCheckOptions()
    {
        return array(
            new InputOption('interactive', null, InputOption::VALUE_NONE, 'Run interactively'),
            new InputOption('explain', null, InputOption::VALUE_NONE, 'Explain the sniffs included in the standard'),
            new InputOption('show-rules', null, InputOption::VALUE_NONE, 'Include the rule name in reports'),
            new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write report to the specified file'),
            new InputOption('report', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The types of reports to output'),
            new InputOption('report-width', null, InputOption::VALUE_REQUIRED, 'Number of columns to limit the report output to', 'auto'),
        );
    }

    protected function getFixerOptions()
    {
        return array(
            new InputOption('fix', null, InputOption::VALUE_NONE, 'Try to fix violations'),
            new InputOption('no-patch', null, InputOption::VALUE_NONE, 'Do not make use of the "diff" or "patch" programs'),
            new InputOption('suffix', null, InputOption::VALUE_REQUIRED, 'Write modified files to a filename with the specified suffix'),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadRuntimeDependency('squizlabs/php_codesniffer', '^2.7.1');

        \PHP_CodeSniffer_Reporting::startTiming();

        $settings = $this->getConfig($input, $output);

        if ($input->hasOption('fix') && $input->getOption('fix') && defined('PHP_CODESNIFFER_CBF') === false) {
            define('PHP_CODESNIFFER_CBF', true);
        } elseif (defined('PHP_CODESNIFFER_CBF') === false) {
            define('PHP_CODESNIFFER_CBF', false);
        }

        $runner    = new \PHP_CodeSniffer_CLI();
        $numErrors = $runner->process($settings);

        // TODO: Exit code?

        if ($input->hasOption('fix') && $input->getOption('fix')) {
            $this->applyPatch();
        }
    }

    protected function applyPatch()
    {
        if ( ! $this->diffFile) {
            return;
        }

        if (filesize($this->diffFile) < 10) {
            return;
        }

        // TODO: ProcessExecutor
        $cmd    = "patch -p0 -ui \"$this->diffFile\"";
        $output = array();
        $retVal = null;
        exec($cmd, $output, $retVal);

        if (0 === $retVal) {
        } else {
            // FIXME: use output
            print_r($output);
            echo "Returned: $retVal" . PHP_EOL;
        }

        unlink($this->diffFile);
        $this->diffFile = null;
    }

    protected function getConfig(InputInterface $input, OutputInterface $output)
    {
        $settings = array();

        $settings['files'] = $input->getArgument('paths');
        // FIXME: why does this completely silence output?
        //        $settings['standard'] = $input->getOption('standard');
        $settings['verbosity'] = 0;

        switch (true) {
            case $output->isDebug():
                $settings['verbosity']++;
            case $output->isVeryVerbose():
                $settings['verbosity']++;
            case $output->isVerbose():
                $settings['verbosity']++;
                break;
            case $output->isQuiet():
                $settings['quiet']        = true;
            default:
                $settings['verbosity']    = 0;
                $settings['showProgress'] = false;
        }

        if ($input->hasOption('interactive') && $input->getOption('interactive')) {
            // TODO: Consider hijacking interactive output
            $settings['interactive'] = true;
        }

        if ($input->hasOption('no-ansi') && $input->getOption('no-ansi')) {
            $settings['colors'] = false;
        } elseif ($input->hasOption('ansi') && $input->getOption('ansi')) {
            $settings['colors'] = false;
        }

        if ($input->hasOption('explain')) {
            $settings['explain'] = $input->getOption('explain');
        }

        if ($input->hasOption('show-rules')) {
            $settings['showSources'] = $input->getOption('show-rules');
        }

        // TODO: extensions

        $sniffs = $input->getOption('sniffs');

        $settings['sniffs'] = $settings['exclude'] = array();

        foreach ($sniffs as $sniff) {
            if ('-' === $sniff[0]) {
                $settings['exclude'] = mb_substr($sniff, 1);
            } else {
                $settings['sniffs'] = $sniff;
            }
        }

        $settings['ignored'] = $input->getOption('exclude');

        if ($input->hasOption('output')) {
            $settings['reportFile'] = $input->getOption('output');
        }

        if ($input->hasOption('print-style') && $input->hasParameterOption('--print-style')) {
            $styleFormat           =  $input->getOption('print-style') ?: 'Text';
            $settings['generator'] = $styleFormat;
        }

        if ($input->hasOption('report')) {
            $settings['reports'] = $input->getOption('report');
        }

        if ($input->hasOption('report-width')) {
            $settings['reportWidth'] = $input->getOption('report-width');
        }

        $settings['bootstrap'] = $input->getOption('bootstrap');

        $settings['errorSeverity']   = $input->getOption('error-severity');
        $settings['warningSeverity'] = $input->getOption('warning-severity');

        if ($input->getOption('suppress-warnings')) {
            $settings['warningSeverity'] = 0;
        }

        if ($input->hasOption('suffix') && $input->getOption('suffix')) {
            $settings['suffix'] = $input->getOption('suffix');
        }

        if ($input->hasOption('no-patch') && $input->getOption('no-patch')) {
            $settings['no-patch'] = true;
        }

        // Override some of the settings that might break fixes.
        if ($input->hasOption('fix') && $input->getOption('fix')) {
            $settings['verbosity']    = 0;
            $settings['showProgress'] = false;
            $settings['generator']    = '';
            $settings['explain']      = false;
            $settings['interactive']  = false;
            $settings['showSources']  = false;
            $settings['reportFile']   = null;
            $settings['reports']      = array();

            if (empty($settings['suffix']) && empty($settings['no-patch'])) {
                $this->diffFile      = tempnam(sys_get_temp_dir(), 'phpcbf-');
                $settings['reports'] = array('diff' => $this->diffFile);
            } else {
                $settings['reports']       = array('cbf' => null);
                $settings['phpcbf-suffix'] = isset($settings['suffix']) ? $settings['suffix'] : '';
            }
        }

        return $settings;
    }
}
