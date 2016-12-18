<?php

namespace SLB\Composer\TestRunner\Command;

use JakubOnderka\PhpParallelLint\Manager;
use JakubOnderka\PhpParallelLint\Settings;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LintCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            ->setName('test:php-lint')
            ->setDescription('Lints the PHP code for the current package')
            ->setDefinition(array(
                new InputArgument('paths', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Paths to lint'),
                new InputOption('php', null, InputOption::VALUE_REQUIRED, 'Specify PHP executable to run'),
                new InputOption('short', 's', InputOption::VALUE_NONE, 'Set `short_open_tag` to On'),
                new InputOption('asp', 'a', InputOption::VALUE_NONE, 'Set `asp_tags` to On'),
                new InputOption('extensions', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Check only files with a specific extension', array('php', 'php3', 'php4', 'php5', 'phtml')),
                new InputOption('jobs', 'j', InputOption::VALUE_REQUIRED, 'Number of jobs to run in parallel', 10),
                new InputOption('blame', null, InputOption::VALUE_NONE, 'Try to show git blame for line with error'),
                new InputOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Paths to exclude'),
            ))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadRuntimeDependency('jakub-onderka/php-parallel-lint', '^0.9');

        $manager = new Manager;
        $result = $manager->run($this->getSettings($input));
    }

    public function isEnabled()
    {
        return $this->isPackageInstalled('jakub-onderka/php-parallel-lint', '^0.9');
    }

    protected function getPackagePaths()
    {
        $package = $this->getComposer()->getPackage();

        $generator = $this->getComposer()->getAutoloadGenerator();
        $map = $generator->parseAutoloads(array(
            array($package, $this->getRootPackagePath()),
        ), $package);

        unset($map['exclude-from-classmap']);

        $namespaces = array_reduce($map, function ($a, $b) {
            return array_merge($a, (array) $b);
        }, array());

        $paths = array_reduce($namespaces, function ($a, $b) {
            return array_merge($a, array_values((array) $b));
        }, array());

        $relativePaths = array_map(array($this, 'makePathRelativeToRoot'), $paths);

        return array_unique($relativePaths);
    }

    protected function getPackageExcludes()
    {
        $vendorDir = $this->getComposer()->getConfig()->get('vendor-dir');

        return array(
            $this->makePathRelativeToRoot($vendorDir),
        );
    }

    private function getSettings(InputInterface $input)
    {
        $settings = new Settings;

        if ($phpExecutable = $input->getOption('php')) {
            $settings->phpExecutable = $input->getOption('php');
        }

        if ($input->getOption('short')) {
            $settings->shortTag = true;
        }

        if ($input->getOption('asp')) {
            $settings->aspTags = true;
        }

        $settings->parallelJobs = $input->getOption('jobs');

        $settings->extensions = $input->getOption('extensions');

        $exclude = array_merge($input->getOption('exclude'), $this->getPackageExcludes());
        $settings->excluded = $exclude;

        if ($input->hasOption('ansi') && $input->getOption('ansi')) {
            $settings->colors = true;
        }

        if ($input->hasOption('no-ansi') && $input->getOption('no-ansi')) {
            $settings->colors = false;
        }

        $settings->blame = $input->getOption('blame');

        // TODO: Path to git executable for blame

        if ($paths = $input->getArgument('paths')) {
            $settings->addPaths($paths);
        } else {
            $settings->addPaths($this->getPackagePaths());
        }

        return $settings;
    }
}
