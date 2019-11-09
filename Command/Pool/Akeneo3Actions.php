<?php

namespace AkeneoDevBox\Command\Pool;

use AkeneoDevBox\Command\PoolAbstract\AbstractAkeneoActions;

/**
 * Class AkeneoActions
 *
 * @package AkeneoDevBox\Command\Pool
 */
class Akeneo3Actions extends AbstractAkeneoActions
{
    protected $configFile = '';

    protected $commandCode = 'akeneo3';
    protected $commandNamespace = 'akeneo3';
    protected $toolsName = 'Akeneo 3 commands';
    protected $commandDesc = 'Akeneo 3 commands list';
}
