<?php

namespace AkeneoDevBox\Command\PoolAbstract;

use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Library\Registry;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Akeneo installation
 */
abstract class AbstractAkeneoInstall extends CommandAbstract
{
    /**
     * Perform delayed configuration
     *
     * @return void
     */
    public function postConfigure()
    {
        parent::configure();
    }
}
