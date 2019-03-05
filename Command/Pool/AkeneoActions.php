<?php

namespace AkeneoDevBox\Command\Pool;

use CoreDevBoxScripts\Command\CoreActionsAbstract;

/**
 * Class AkeneoActions
 *
 * @package AkeneoDevBox\Command\Pool
 */
class AkeneoActions extends CoreActionsAbstract
{
    protected $configFile = '';

    protected $commandCode = 'akeneo2';
    protected $toolsName = 'Akeneo 2 commands';
    protected $commandDesc = 'Akeneo 2 commands list';
    protected $commandHelp = 'This command allows you to execute any of predefined actions to setup website';

    /**
     * @return array|\Symfony\Component\Console\Command\Command[]
     */
    protected function getApplicationCommands()
    {
        $coreCommands = $this->getApplication()->all('core');
        $platformCommands = $this->getApplication()->all('akeneo2');

        return array_merge($coreCommands, $platformCommands);
    }

    protected function beforeExecute($input, $output, $io)
    {
        parent::beforeExecute($input, $output, $io);

        if ($this->getJoke()) {
            $io->block($this->getJoke());
        }
    }

    /**
     * @return bool
     */
    public function getJoke()
    {

        try {
            $ans = file_get_contents('http://api.icndb.com/jokes/random');
            $ansO = json_decode($ans);
            if ($ansO->type == 'success') {
                return $ansO->value->joke;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
