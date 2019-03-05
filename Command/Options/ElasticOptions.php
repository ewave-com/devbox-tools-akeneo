<?php

namespace AkeneoDevBox\Command\Options;

use CoreDevBoxScripts\Command\Options\AbstractOptions;

/**
 * Container for ElasticSearch options
 */
class ElasticOptions extends AbstractOptions
{
    const INSTALL_FROM_DUMP = 'es-install-from-dump';
    const INDEX_DATA = 'es-index-data';
    const HOST = 'es-host';
    const PORT = 'es-port';

    /**
     * {@inheritdoc}
     */
    protected static function getOptions()
    {
        return [
            static::INSTALL_FROM_DUMP => [
                'boolean' => true,
                'description' => 'Install Elasticsearch data from dump',
                'question' => 'Install Elasticsearch data from dump? %default%',
                'default' => 'yes',
            ],
            static::INDEX_DATA => [
                'boolean' => true,
                'description' => 'Index product data into the ES',
                'question' => 'Index product data into the ES? %default%',
                'default' => 'yes',
            ],
            static::HOST => [
                'default' => 'es',
                'description' => 'ElasticSearch host.',
                'question' => 'Please enter ElasticSearch host %default%',
            ],
            static::PORT => [
                'default' => '9200',
                'description' => 'ElasticSearch port.',
                'question' => 'Please enter ElasticSearch port %default%',
            ],
        ];
    }
}
