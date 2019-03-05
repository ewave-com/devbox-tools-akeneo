<?php

namespace AkeneoDevBox\Command\Pool;

use AkeneoDevBox\Command\Options\AkeneoOptions;
use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Library\EnvConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class AkeneoSetupFinalize extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('akeneo2:setup:finalize')
            ->setDescription('Cache clean, Generate assets, Run webpack')
            ->setHelp('Cache clean, Generate assets, Run webpack');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->commandTitle($io, 'Finalization');

        $projectPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');

        $headers = ['Parameter', 'Value'];
        $rows = [
            ['Project source code folder', $projectPath],
        ];
        $io->table($headers, $rows);

        $io->progressStart(2);

        if ($this->requestOption(AkeneoOptions::CLEAR_CACHE, $input, $output)) {
            try {
                $this->executeCommands(
                    sprintf('cd %s && rm -rf var/cache && php bin/console cache:clear -e prod', $projectPath),
                    $output
                );
            } catch (\Exception $e) {
                $io->note($e->getMessage());
                $io->note('Step skipped.');
            }
        }

        $output->writeln(["", $io->progressAdvance(1), ""]);

        if ($this->requestOption(AkeneoOptions::INSTALL_ASSETS, $input, $output)) {
            try {
                $this->executeCommands(
                    sprintf('cd %s && php bin/console pim:installer:assets -e prod --clean', $projectPath),
                    $output
                );

                $this->executeCommands(
                    sprintf('cd %s && yarn run webpack', $projectPath),
                    $output
                );
            } catch (\Exception $e) {
                $io->note($e->getMessage());
                $io->note('Step skipped.');
            }
        }

        $io->progressFinish();

        if (!isset($e)) {
            $io->success('Finalisation steps are passed');
        } else {
            $io->warning('Some issues appeared during finalization steps');
            return false;
        }

        return true;

    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            AkeneoOptions::CLEAR_CACHE => AkeneoOptions::get(AkeneoOptions::CLEAR_CACHE),
            AkeneoOptions::INSTALL_ASSETS => AkeneoOptions::get(AkeneoOptions::INSTALL_ASSETS),
        ];
    }

}
