<?php

namespace Evoweb\EwCommands\Command;

/*
 * This file is part of the evoWeb commands.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Evoweb\EwCommands\Database\Process\MysqlCommand;
use Evoweb\EwCommands\Database\Schema\TableMatcher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseExportCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Export database to stdout')
            ->setHelp(
                <<<'EOH'
Export the database (all tables) directly to stdout.
The mysqldump binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

Tables to be excluded from the export can be specified fully qualified or with wildcards:

<b>Example:</b>
<code>%command.full_name% -c Default -e 'cf_*' -e 'cache_*' -e '[bf]e_sessions' -e sys_log</code>
EOH
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'TYPO3 database connection name',
                'Default'
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Full table name or wildcard expression to exclude from the export.',
                []
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Type of database server.',
                'mysql'
            );
    }

    /**
     * Fix folder structure
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = (string)$input->getOption('connection');
        $excludes = $input->getOption('exclude');
        $type = $input->getOption('type');

        $availableConnectionNames = $connectionNames = array_keys(
            array_filter(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'],
                function (array $connectionConfig) use ($type) {
                    return strpos($connectionConfig['driver'], $type) !== false;
                }
            )
        );
        $failureReason = '';
        if ($connection !== null) {
            $availableConnectionNames = array_intersect($connectionNames, [$connection]);
            $failureReason = sprintf(' Given connection "%s" is not configured as MySQL connection.', $connection);
        }
        if (empty($availableConnectionNames)) {
            $output->writeln(sprintf('<error>No MySQL connections found to export.%s</error>', $failureReason));

            return 2;
        }

        foreach ($availableConnectionNames as $connectionName) {
            $dbConfig = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName ? $connectionName : 'Default'];
            $mysqlCommand = new MysqlCommand($dbConfig, [], $output);
            $exitCode = $mysqlCommand->mysqldump(
                $this->buildArguments($connectionName, $dbConfig, $excludes, $output),
                null,
                $connectionName
            );

            if ($exitCode !== 0) {
                $output->writeln(
                    sprintf('<error>Could not dump SQL for connection "%s",</error>', $connectionName)
                );

                return $exitCode;
            }
        }

        return 0;
    }

    protected function buildArguments(
        string $connectionName,
        array $connectionConfiguration,
        array $excludes,
        OutputInterface $output
    ): array {
        $arguments = [
            '--opt',
            '--single-transaction',
        ];

        if ($output->isVerbose()) {
            $arguments[] = '--verbose';
        }

        foreach ($this->matchTables($excludes, $connectionName) as $table) {
            $arguments[] = sprintf('--ignore-table=%s.%s', $connectionConfiguration['dbname'], $table);
        }

        return $arguments;
    }

    protected function matchTables(array $excludes, string $connection): array
    {
        if (empty($excludes)) {
            return [];
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName($connection);

        return (new TableMatcher())->match($connection, ...$excludes);
    }
}
