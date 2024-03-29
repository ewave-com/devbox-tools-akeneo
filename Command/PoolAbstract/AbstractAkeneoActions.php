<?php

namespace AkeneoDevBox\Command\PoolAbstract;

use CoreDevBoxScripts\Command\CoreActionsAbstract;

/**
 * Class AkeneoActions
 *
 * @package AkeneoDevBox\Command\Pool
 */
abstract class AbstractAkeneoActions extends CoreActionsAbstract
{
    protected $configFile = '';

    protected $commandCode;
    protected $commandNamespace;
    protected $toolsName;
    protected $commandDesc;

    /**
     * @return array|\Symfony\Component\Console\Command\Command[]
     */
    protected function getApplicationCommands()
    {
        $coreCommands = $this->getCoreCommands();
        $akeneoCommonCommands = $this->getApplication()->all('akeneo');
        $akeneoVersionCommands = $this->getApplication()->all($this->commandNamespace);

        return array_merge($coreCommands, $akeneoCommonCommands, $akeneoVersionCommands);
    }

    protected function beforeExecute($input, $output, $io)
    {
        parent::beforeExecute($input, $output, $io);

        if ($joke = $this->getJoke()) {
            $io->block($joke);
        }
    }

    /**
     * @return bool
     */
    public function getJoke()
    {
        try {
            $ans = @file_get_contents('https://api.chucknorris.io/jokes/random', 0, stream_context_create(["http"=>["timeout"=>0.5]]));
            $ansO = json_decode($ans);
            if (null !== $ans && (!empty($ansO->value))) {
                return $ansO->value;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
