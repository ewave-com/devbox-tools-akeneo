<?php

namespace AkeneoDevBox\Command\Pool;

use AkeneoDevBox\Command\PoolAbstract\AbstractAkeneoInstall;
use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Library\Registry;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Akeneo installation
 */
class Akeneo2Install extends AbstractAkeneoInstall
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('akeneo2:install')
             ->setDescription(
                 'Install existing akeneo Project : [Code Download]->[DB Download/Install/Configure]->[Configure env.php]->[Akeneo finalisation]'
             )
             ->setHelp('[Code Download]->[DB Download/Install/Configure]');
    }

    /**
     * {@inheritdoc}
     *
     * @throws CommandNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Registry::set(static::CHAINED_EXECUTION_FLAG, true);

        $this->executeWrappedCommands(
            [
                'core:setup:permissions',
                'core:setup:code',
                'core:setup:media',
                'akeneo:setup:configs',
                'core:setup:db',
                'akeneo2:setup:elastic',
                'core:setup:node-modules',
                'akeneo:setup:finalize',
                'akeneo:setup:job-daemon',
                'core:setup:permissions',
            ],
            $input,
            $output
        );
    }

}
