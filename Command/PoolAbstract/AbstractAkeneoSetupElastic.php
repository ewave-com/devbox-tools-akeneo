<?php

namespace AkeneoDevBox\Command\PoolAbstract;

use AkeneoDevBox\Command\Options\ElasticOptions;
use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Framework\Container;
use CoreDevBoxScripts\Framework\Downloader\DownloaderFactory;
use CoreDevBoxScripts\Library\EnvConfig;
use CoreDevBoxScripts\Library\JsonConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for Akeneo final steps
 */
abstract class AbstractAkeneoSetupElastic extends CommandAbstract
{
    const TYPE_MAPPING = 'mapping';
    const TYPE_ANALYZER = 'analyzer';
    const TYPE_DATA = 'data';

    protected $importedIndices = [];

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executeRepeatedly('updateEsData', $input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return $this
     * @throws \Exception
     */
    protected function updateEsData(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->commandTitle($io, 'Elasticsearch data updating.');

        try {

            if ($this->requestOption(ElasticOptions::INSTALL_FROM_DUMP, $input, $output)) {
                $this->installElasticsearchDump($io, $output);
            } elseif ($this->requestOption(ElasticOptions::INDEX_DATA, $input, $output)) {
                $this->executeElasticsearchReindexCommands($io, $output);
            } else {
                $this->commandTitle($io, 'Elasticsearch data updating skipped');
            }

        } catch (\Exception $e) {
            $io->note($e->getMessage());
            $io->note('Step skipped.');
        }

        $this->commandTitle($io, 'Elasticsearch data updating finished.');

        return $this;
    }

    /**
     * @param SymfonyStyle         $io
     * @param OutputInterface|null $output
     *
     * @return bool
     * @throws \Exception
     */
    protected function installElasticsearchDump(SymfonyStyle $io, OutputInterface $output = null)
    {
        $projectPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');

        $sourceType = JsonConfig::getConfig('sources->es->source_type');
        $source = JsonConfig::getConfig('sources->es->source_path');
        $localDumpsStorage = JsonConfig::getConfig('sources->es->local_temp_path');
        $downloadOptions = JsonConfig::getConfig('sources->es');

        $coreHost = EnvConfig::getValue('WEBSITE_HOST_NAME');
        $projectName = EnvConfig::getValue('PROJECT_NAME');

        $esHost = EnvConfig::getValue('CONTAINER_ELASTIC_NAME');
        $esHost = $projectName . '_' . $esHost;
        $esPort = '9200';

        $headers = ['Parameter', 'Value'];
        $rows = [
            ['Source', $source],
            ['Project URL', $coreHost],
            ['ES Host', $esHost],
            ['ES Port', $esPort],
            ['DB dumps temp folder', $localDumpsStorage],
        ];
        $io->table($headers, $rows);

        if (!trim($source)) {
            throw new \Exception('Source path is not set in .env file. Recheck DATABASE_SOURCE_PATH parameter');
        }

        //install elastucdump npm package
        $elasticdumpBinary =
            $localDumpsStorage . DIRECTORY_SEPARATOR . 'elasticdump/node_modules/elasticdump/bin/elasticdump';
        if (!file_exists($elasticdumpBinary)) {
            $edDir = $localDumpsStorage . DIRECTORY_SEPARATOR . 'elasticdump';
            $this->mkdir($edDir);
            $this->executeCommands(
                [
                    sprintf('cd %s && npm install elasticdump > /dev/null', $edDir),
                ],
                $output
            );
            if (!file_exists($elasticdumpBinary)) {
                throw new \Exception('Elasticdump binary not found at path ' . $elasticdumpBinary);
            }
        }

        //check and download dump file
        $isLocalFile = false;
        if (filter_var($source, FILTER_VALIDATE_URL) === false) {
            $isLocalFile = true;
        }

        $this->mkdir($localDumpsStorage);

        if (!$isLocalFile) {
            $fileFullPath = $localDumpsStorage . DIRECTORY_SEPARATOR . basename($source);

            /** @var DownloaderFactory $downloaderFactory */
            $downloaderFactory = Container::getContainer()->get(DownloaderFactory::class);

            try {
                $downloader = $downloaderFactory->get($sourceType);
                $downloader->download($source, $fileFullPath, $downloadOptions, $output);
                $io->success('Download completed');
            } catch (\Exception $e) {
                $io->warning([$e->getMessage()]);
                $io->warning('Some issues appeared during DB downloading.');

                return false;
            }
        } else {
            $fileFullPath = $source;
        }

        try {
            $dumpJsonPaths = $this->unpackJsonDump($fileFullPath, $output);
        } catch (\Exception $e) {
            $io->note($e->getMessage());

            return false;
        }

        //import indices data from dump json files
        $output->writeln('Indices importing');

        $commandTemplatesByType = [
            static::TYPE_MAPPING  => sprintf(
                '%s --type=mapping --input="{jsonPath}" --output=http://%s:%s/{indexName} --quiet=true',
                $elasticdumpBinary,
                $esHost,
                $esPort
            ),
//analyzer type import is not required after akeneo reset command executing
//            static::TYPE_ANALYZER => sprintf(
//                '%s --type=analyzer --input="{jsonPath}" --output=http://%s:%s/{indexName} --quiet=true',
//                $elasticdumpBinary,
//                $esHost,
//                $esPort
//            ),
            static::TYPE_DATA     => sprintf(
                '%s --type=data --input="{jsonPath}" --output=http://%s:%s/{indexName} --bulk=true --limit=1000 --quiet=true',
                $elasticdumpBinary,
                $esHost,
                $esPort
            ),
        ];

        $importCommands = [];
        $importCommands[] = sprintf(
            'cd %s && php bin/console akeneo:elasticsearch:reset-indexes --no-interaction --quiet',
            $projectPath
        );

        foreach ($this->importedIndices as $indexName) {
            foreach (array_keys($commandTemplatesByType) as $type) {
                foreach ($dumpJsonPaths as $jsonPath) {
                    if (false !== strpos($jsonPath, $indexName . '_' . $type)) {
                        $importCommand = str_replace('{jsonPath}', $jsonPath, $commandTemplatesByType[$type]);
                        $importCommand = str_replace('{indexName}', $indexName, $importCommand);
                        $importCommands[] = $importCommand;
                        break;
                    }
                }
            }
        }

        $this->executeCommands($importCommands, $output);

        $output->writeln('Indices import successfully finished');
    }


    /**
     * @param string          $filePath
     * @param OutputInterface $output
     *
     * @return array | false
     */
    public function unpackJsonDump($filePath, $output)
    {
        $path_parts = pathinfo($filePath);
        $fileDirectory = $path_parts['dirname'];
        $jsonDir = $fileDirectory . DIRECTORY_SEPARATOR . 'json';

        if (!is_dir($jsonDir)) {
            $this->mkdir($jsonDir, $output);
        } else {
            $this->executeCommands(sprintf('rm -rf %s%s*', $jsonDir, DIRECTORY_SEPARATOR), $output);
        }

        $newPath = $filePath;

        if ($path_parts['extension'] === 'gz') {
            $extractCommand = sprintf('tar -C %s -xf %s', $jsonDir, $filePath);
            $output->writeln('<comment>Unpacking file...</comment>');
            $this->executeCommands(
                $extractCommand,
                $output
            );
            $newPath = $fileDirectory . DIRECTORY_SEPARATOR . $path_parts['filename'];
            $output->writeln("<info>Extracted file: $newPath </info>");
        }

        return glob($jsonDir . DIRECTORY_SEPARATOR . '*.json');
    }

    /**
     * @param SymfonyStyle         $io
     * @param OutputInterface|null $output
     */
    protected function executeElasticsearchReindexCommands(SymfonyStyle $io, OutputInterface $output = null)
    {
        $projectPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');

        try {
            $this->executeCommands(
                sprintf(
                    'cd %s '
                    . ' && php bin/console akeneo:elasticsearch:reset-indexes -n'
                    . '&& php bin/console pim:product-model:index --all'
                    . '&& php bin/console pim:product:index --all'
                    . '&& php bin/console pimee:published-product:index'
                    . '&& php bin/console pimee:product-proposal:index'
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

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            ElasticOptions::INSTALL_FROM_DUMP => ElasticOptions::get(ElasticOptions::INSTALL_FROM_DUMP),
            ElasticOptions::INDEX_DATA        => ElasticOptions::get(ElasticOptions::INDEX_DATA),
            ElasticOptions::HOST              => ElasticOptions::get(ElasticOptions::HOST),
            ElasticOptions::PORT              => ElasticOptions::get(ElasticOptions::PORT),
        ];
    }
}
