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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseImportCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Import mysql queries from stdin')
            ->setHelp(
                <<<'EOH'
This means that this can not only be used to pass insert statements,
it but works as well to pass SELECT statements to it.
The mysql binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

<b>Example (import):</b>
<code>ssh remote.server '/path/to/typo3 evoweb:database:export' | %command.full_name%</code>

<b>Example (select):</b>
<code>echo 'SELECT username from be_users WHERE admin=1;' | %command.full_name%</code>

<b>Example (interactive):</b>
<code>%command.full_name% --interactive</code>
EOH
            )
            ->addOption(
                'interactive',
                '',
                InputOption::VALUE_NONE,
                'Open an interactive mysql shell using the TYPO3 connection settings.'
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'TYPO3 database connection name',
                'Default'
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
        $interactive = $input->getOption('interactive');
        $type = $input->getOption('type');

        $availableConnectionNames = $connectionNames = array_keys(
            array_filter(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'],
                function (array $connectionConfig) use ($type) {
                    return strpos($connectionConfig['driver'], $type) !== false;
                }
            )
        );
        if (empty($availableConnectionNames) || !in_array($connection, $availableConnectionNames, true)) {
            $output->writeln('<error>No suitable MySQL connection found for import.</error>');

            return 2;
        }

        $connectionConfig = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connection ? $connection : 'Default'];
        $mysqlCommand = new MysqlCommand($connectionConfig, [], $output);
        $exitCode = $mysqlCommand->mysql(
            $interactive ? [] : ['--skip-column-names'],
            STDIN,
            null,
            $interactive
        );

        $output->writeln(sprintf('<info>%s command executed.</info>', $interactive ? 'Interactive' : 'Import'));

        return $exitCode;
    }
}
