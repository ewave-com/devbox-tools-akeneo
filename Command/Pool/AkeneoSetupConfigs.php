<?php


namespace AkeneoDevBox\Command\Pool;

use AkeneoDevBox\Command\Options\AkeneoOptions;
use CoreDevBoxScripts\Command\CommandAbstract;
use CoreDevBoxScripts\Library\EnvConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Command for downloading Akeneo sources
 */
class AkeneoSetupConfigs extends CommandAbstract
{
    protected $configFile = '';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->configFile = EnvConfig::getValue('PROJECT_CONFIGURATION_FILE');
        $this->setName('akeneo2:setup:configs')
            ->setDescription(
                'Download Akeneo Configs Files [' . $this->configFile . ' file will be used as configuration]'
            )
            ->setHelp(
                'Download Akeneo Configs Files [' . $this->configFile . ' file will be used as configuration]'
            );

        $this->questionOnRepeat = 'Try to update configs again?';

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executeRepeatedly('updateConfigs', $input, $output);
    }

    protected function updateConfigs(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->commandTitle($io, 'Configuration files sync.');

        $useExistingSources =
            $this->requestOption(AkeneoOptions::AKENEO_CONFIGS_REUSE, $input, $output, true);

        if (!$useExistingSources) {
            $output->writeln('<comment>Skipping this step.</comment>');
            return true;
        }

        $this->executeWrappedCommands(
            [
                'core:remote-files:download',
                'core:setup:permissions',
            ],
            $input,
            $output
        );

        $this->updateConfigsFiles($io, $input, $output);
    }

    public function updateConfigsFiles($io, InputInterface $input, OutputInterface $output)
    {
        $projectName = EnvConfig::getValue('PROJECT_NAME');
        $mysqlHost = EnvConfig::getValue('CONTAINER_MYSQL_NAME');
        $mysqlDbName = EnvConfig::getValue('CONTAINER_MYSQL_DB_NAME');
        $mysqlRootPasword = EnvConfig::getValue('CONTAINER_MYSQL_ROOT_PASS');
        $mysqlHost = $projectName . '_' . $mysqlHost;

        $projectPath = EnvConfig::getValue('WEBSITE_DOCUMENT_ROOT');
        $destinationProjectPath = $projectPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config';

        $configPath = sprintf('%s/parameters.yml', $destinationProjectPath);
        $config = Yaml::parseFile($configPath);

        $config['parameters']['database_host'] = $mysqlHost;
        $config['parameters']['database_name'] = $mysqlDbName;
        $config['parameters']['username'] = 'root';
        $config['parameters']['password'] = $mysqlRootPasword;

        file_put_contents($configPath, Yaml::dump($config));

        if (!isset($e)) {
            $io->success('Configs have been copied');
            return true;
        } else {
            $io->warning('Some issues appeared during configs updating');
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            AkeneoOptions::AKENEO_CONFIGS_REUSE => AkeneoOptions::get(AkeneoOptions::AKENEO_CONFIGS_REUSE),
        ];
    }
}
