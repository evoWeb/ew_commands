<?php

declare(strict_types=1);

namespace Evoweb\EwCommands\Database\Schema;

// Copied from helhum/typo3-console and modified to work with the ew_console

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List of database schema update types
 */
class SchemaUpdateResultRenderer
{
    /**
     * Mapping of schema update types to human-readable labels
     *
     * @var array
     */
    private static $schemaUpdateTypeLabels = [
        SchemaUpdateType::FIELD_ADD => 'Add fields',
        SchemaUpdateType::FIELD_CHANGE => 'Change fields',
        SchemaUpdateType::FIELD_PREFIX => 'Prefix fields',
        SchemaUpdateType::FIELD_DROP => 'Drop fields',
        SchemaUpdateType::TABLE_ADD => 'Add tables',
        SchemaUpdateType::TABLE_CHANGE => 'Change tables',
        SchemaUpdateType::TABLE_PREFIX => 'Prefix tables',
        SchemaUpdateType::TABLE_DROP => 'Drop tables',
    ];

    /**
     * Renders a table for a schema update result
     *
     * @param SchemaUpdateResult $result Result of the schema update
     * @param OutputInterface $output
     * @param bool $includeStatements
     * @param int $maxStatementLength
     */
    public function render(
        SchemaUpdateResult $result,
        OutputInterface $output,
        $includeStatements = false,
        $maxStatementLength = 60
    ) {
        $tableRows = [];

        foreach ($result->getPerformedUpdates() as $type => $performedUpdates) {
            $row = [self::$schemaUpdateTypeLabels[(string)$type], count($performedUpdates)];
            if ($includeStatements) {
                $row = [
                    self::$schemaUpdateTypeLabels[(string)$type],
                    implode(
                        chr(10) . chr(10),
                        $this->getTruncatedQueries($performedUpdates, $maxStatementLength)
                    )
                ];
            }
            $tableRows[] = $row;
        }
        $tableHeader = ['Type', 'Updates'];
        if ($includeStatements) {
            $tableHeader = ['Type', 'SQL Statements'];
        }
        if (!empty($tableRows)) {
            $table = new Table($output);
            $table->setHeaders($tableHeader)->setRows($tableRows)->render();
        }
    }

    /**
     * Renders a table for a schema update result
     *
     * @param SchemaUpdateResult $result Result of the schema update
     * @param OutputInterface $output
     * @param bool $includeStatements
     * @param int $maxStatementLength
     */
    public function renderErrors(
        SchemaUpdateResult $result,
        OutputInterface $output,
        $includeStatements = false,
        $maxStatementLength = 90
    ) {
        $tableRows = [];
        $messageLength = $includeStatements ? (int)($maxStatementLength * .3) : $maxStatementLength;
        $statementLength = (int)($maxStatementLength * 0.6);
        foreach ($result->getErrors() as $type => $errors) {
            $typeLabel = self::$schemaUpdateTypeLabels[(string)$type];
            foreach ($errors as $error) {
                $row = [$typeLabel, implode(PHP_EOL, $this->getTruncatedQueries([$error['message']], $messageLength))];
                if ($includeStatements) {
                    $row = [
                        $typeLabel,
                        implode(PHP_EOL, $this->getTruncatedQueries([$error['statement']], $statementLength)),
                        implode(PHP_EOL, $this->getTruncatedQueries([$error['message']], $messageLength))
                    ];
                }
                $tableRows[] = $row;
                $tableRows[] = $includeStatements ? ['', '', ''] : ['', ''];
                $typeLabel = '';
            }
        }
        $tableHeader = ['Type', 'Message'];
        if ($includeStatements) {
            $tableHeader = ['Type', 'SQL Statement', 'Message'];
        }
        $table = new Table($output);
        $table->setHeaders($tableHeader)->setRows($tableRows)->render();
    }

    /**
     * Truncate (wrap) query strings at a certain number of characters
     *
     * @param array $queries
     * @param int $truncateAt
     *
     * @return array
     */
    protected function getTruncatedQueries(array $queries, int $truncateAt): array
    {
        foreach ($queries as &$query) {
            $truncatedLines = [];
            foreach (explode(chr(10), $query) as $line) {
                $truncatedLines[] = wordwrap($line, $truncateAt, chr(10), true);
            }
            $query = implode(chr(10), $truncatedLines);
        }

        return $queries;
    }
}
