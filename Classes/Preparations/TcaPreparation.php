<?php

declare(strict_types=1);

namespace Ew\EwCommands\Preparations;

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

/**
 * Prepare TCA. Used in bootstrap and Flex Form Data Structures.
 *
 * @internal Class and API may change any time.
 */
class TcaPreparation extends \TYPO3\CMS\Core\Preparations\TcaPreparation
{
    /**
     * Quote all table and field names in definitions known to possibly have quoted identifiers
     * like '{#tablename}.{#columnname}='
     *
     * @param array $tca Incoming TCA
     * @return array Prepared TCA
     */
    protected function prepareQuotingOfTableNamesAndColumnNames(array $tca): array
    {
        $newTca = $tca;
        $configToPrepareQuoting = [
            'foreign_table_where',
            'MM_table_where',
            'search' => 'andWhere'
        ];
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $columnName => $columnConfig) {
                foreach ($configToPrepareQuoting as $level => $value) {
                    if (is_string($level)) {
                        $sqlQueryPartToPrepareQuotingIn = $columnConfig['config'][$level][$value] ?? '';
                    } else {
                        $sqlQueryPartToPrepareQuotingIn = $columnConfig['config'][$value] ?? '';
                    }
                    if (mb_strpos($sqlQueryPartToPrepareQuotingIn, '{#') !== false) {
                        $quoted = self::quoteDatabaseIdentifiers(
                            $this,
                            $sqlQueryPartToPrepareQuotingIn
                        );
                        if (is_string($level)) {
                            $newTca[$table]['columns'][$columnName]['config'][$level][$value] = $quoted;
                        } else {
                            $newTca[$table]['columns'][$columnName]['config'][$value] = $quoted;
                        }
                    }
                }
            }
        }

        return $newTca;
    }

    /**
     * Quote database table/column names indicated by {#identifier} markup in a SQL fragment string.
     * This is an intermediate step to make SQL fragments in Typoscript and TCA database agnostic.
     *
     * @param self $connection
     * @param string $sql
     * @return string
     */
    public static function quoteDatabaseIdentifiers($connection, string $sql): string
    {
        if (strpos($sql, '{#') !== false) {
            $sql = preg_replace_callback(
                '/{#(?P<identifier>[^}]+)}/',
                function (array $matches) use ($connection) {
                    return $connection->quoteIdentifier($matches['identifier']);
                },
                $sql
            );
        }

        return $sql;
    }

    /**
     * Quotes a string so that it can be safely used as a table or column name,
     * even if it is a reserved word of the platform. This also detects identifier
     * chains separated by dot and quotes them independently.
     *
     * NOTE: Just because you CAN use quoted identifiers doesn't mean
     * you SHOULD use them. In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str The identifier name to be quoted.
     *
     * @return string The quoted identifier string.
     */
    public function quoteIdentifier($str)
    {
        if (strpos($str, '.') !== false) {
            $parts = array_map([$this, 'quoteSingleIdentifier'], explode('.', $str));

            return implode('.', $parts);
        }

        return $this->quoteSingleIdentifier($str);
    }

    /**
     * Quotes a single identifier (no dot chain separation).
     *
     * @param string $str The identifier name to be quoted.
     *
     * @return string The quoted identifier string.
     */
    public function quoteSingleIdentifier($str)
    {
        $c = $this->getIdentifierQuoteCharacter();

        return $c . str_replace($c, $c . $c, $str) . $c;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierQuoteCharacter()
    {
        return '`';
    }
}
