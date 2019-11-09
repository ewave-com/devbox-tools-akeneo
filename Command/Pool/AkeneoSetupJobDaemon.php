<?php

namespace AkeneoDevBox\Command\Pool;

use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Library\EnvConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class AkeneoSetupJobDaemon extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('akeneo:setup:job-daemon')
             ->setDescription('Run background Akeneo job daemon')
             ->setHelp('Run background Akeneo job daemon');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->commandTitle($io, 'Run Akeneo Daemon');

        $projectPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');

        shell_exec(
            sprintf(
                'cd %s && php bin/console akeneo:batch:job-queue-consumer-daemon > /dev/null 2>&1 &',
                $projectPath
            )
        );
        $output->writeln('Job daemon executed');

        return true;
    }
}
