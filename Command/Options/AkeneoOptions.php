<?php

namespace AkeneoDevBox\Command\Options;

use CoreDevBoxScripts\Command\Options\AbstractOptions;

/**
 * Container for Akeneo options
 */
class AkeneoOptions extends AbstractOptions
{
    const AKENEO_CONFIGS_REUSE = 'akeneo-configs-reuse';
    const CLEAR_CACHE = 'akeneo-clear-cache';
    const INSTALL_ASSETS = 'akeneo-install-assets';
    const JOB_DAEMON_RUN = 'akeneo-daemon';
    const JOB_DAEMON_RUN_ONCE = 'akeneo-daemon-once';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::AKENEO_CONFIGS_REUSE => [
                'boolean' => true,
                'description' => 'Whether to use existing Magento config files',
                'question' => 'Do you want to update parameters.yml files from source? %default%',
                'default' => 'yes'
            ],
            static::CLEAR_CACHE => [
                'boolean' => true,
                'default' => true,
                'description' => 'Clear cache',
                'question' => 'Clear cache? %default%'
            ],
            static::INSTALL_ASSETS => [
                'boolean' => true,
                'default' => true,
                'description' => 'Install Assets',
                'question' => 'Install Assets? %default%'
            ],
            static::JOB_DAEMON_RUN => [
                'boolean' => true,
                'default' => true,
                'description' => 'Execute Job daemon permanently',
                'question' => 'Execute Job daemon? %default%'
            ],
            static::JOB_DAEMON_RUN_ONCE => [
                'boolean' => true,
                'default' => true,
                'description' => 'Execute Job daemon once',
                'question' => 'Execute Job daemon once? %default%'
            ],
        ];
    }
}
