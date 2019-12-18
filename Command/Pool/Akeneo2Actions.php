<?php

namespace AkeneoDevBox\Command\Pool;

use AkeneoDevBox\Command\PoolAbstract\AbstractAkeneoActions;

/**
 * Class AkeneoActions
 *
 * @package AkeneoDevBox\Command\Pool
 */
class Akeneo2Actions extends AbstractAkeneoActions
{
    protected $configFile = '';

    protected $commandCode = 'akeneo2';
    protected $commandNamespace = 'akeneo2';
    protected $toolsName = 'Akeneo 2 commands';
    protected $commandDesc = 'Akeneo 2 commands list';
}
