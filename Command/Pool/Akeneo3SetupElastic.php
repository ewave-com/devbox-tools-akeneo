<?php

namespace AkeneoDevBox\Command\Pool;

use AkeneoDevBox\Command\PoolAbstract\AbstractAkeneoSetupElastic;
use CoreDevBoxScripts\Library\EnvConfig;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for Akeneo final steps
 */
class Akeneo3SetupElastic extends AbstractAkeneoSetupElastic
{
    protected $importedIndices = [
        'akeneo_pim_product',
        'akeneo_pim_product_proposal',
        'akeneo_pim_published_product',
        'akeneo_pim_published_product_and_product_model',
        'akeneo_pim_product_model',
        'akeneo_pim_product_and_product_model',
        'akeneo_referenceentity_record'
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('akeneo3:setup:elastic')
             ->setDescription('Update elasticsearch product data')
             ->setHelp('Update elasticsearch product data');

        $this->questionOnRepeat = 'Try to update Elasticsearch data again?';

        parent::configure();
    }


    protected function executeElasticsearchReindexCommands(SymfonyStyle $io, OutputInterface $output = null)
    {
        $projectPath = EnvConfig::getValue('WEBSITE_APPLICATION_ROOT') ?: EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');

        try {
            $this->executeCommands(
                sprintf(
                    'cd %s '
                    . ' && php bin/console akeneo:elasticsearch:reset-indexes -n '
                    . '&& php bin/console akeneo:reference-entity:index-records --all -n '
                    . '&& (php bin/console ewave:product-model:index --help >/dev/null 2>&1 && php bin/console ewave:product-model:index --all -n || php bin/console pim:product-model:index --all -n) '
                    . '&& (php bin/console ewave:product:index --help >/dev/null 2>&1 && php bin/console ewave:product:index --all -n || php bin/console pim:product:index --all -n) '
                    . '&& php bin/console pimee:published-product:index -n '
                    . '&& php bin/console pimee:product-proposal:index -n '
                    ,
                    $projectPath
                ),
                $output
            );
        } catch (\Exception $e) {
            $io->note($e->getMessage());
            $io->note('Step skipped.');
        }
    }
}
