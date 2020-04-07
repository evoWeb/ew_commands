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

use Evoweb\EwCommands\Database\Schema\SchemaUpdateResult;
use Evoweb\EwCommands\Database\Schema\SchemaUpdateResultRenderer;
use Evoweb\EwCommands\Database\Schema\SchemaUpdateType;
use Evoweb\EwCommands\Service\Database\SchemaService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DatabaseUpdateSchemaCommand extends \Symfony\Component\Console\Command\Command
{
    /** @var ObjectManager */
    protected $objectManager;

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Update the database schema.')
            ->setHelp(
                <<<'EOH'
Compares the current database schema with schema definition
from extensions's ext_tables.sql files and updates the schema based on the definition.

Valid schema update types are:

- field.add
- field.change
- field.prefix
- field.drop
- table.add
- table.change
- table.prefix
- table.drop
- safe (includes all necessary operations, to add or change fields or tables)
- destructive (includes all operations which rename or drop fields or tables)

The list of schema update types supports wildcards to specify multiple types, e.g.:

- "<code>*</code>" (all updates)
- "<code>field.*</code>" (all field updates)
- "<code>*.add,*.change</code>" (all add/change updates)

To avoid shell matching all types with wildcards should be quoted.

<b>Example:</b>
Call it like this: <code>%command.full_name% --type="*.add,*.change"</code>
EOH
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                '',
                'safe'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the schema will not be modified, but only show the'
                . ' output which schema would have been modified.'
            );
    }

    /**
     * Update schema
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $schemaUpdateTypes = explode(',', $input->getOption('type'));
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false;

        try {
            $updateTypes = SchemaUpdateType::expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (InvalidEnumerationValueException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return 1;
        }

        /** @var SchemaService $schemaService */
        $schemaService = $this->objectManager->get(SchemaService::class);
        $result = $schemaService->updateSchema($updateTypes, $dryRun);

        $this->writeResult($output, $result, $updateTypes, $dryRun);

        return $result->hasErrors() ? 1 : 0;
    }

    protected function writeResult(
        OutputInterface $output,
        SchemaUpdateResult $result,
        array $updateTypes,
        bool $dryRun
    ) {
        /** @var SchemaUpdateResultRenderer $renderer */
        $renderer = $this->objectManager->get(SchemaUpdateResultRenderer::class);

        $verbose = $output->isVerbose();

        if ($result->hasPerformedUpdates()) {
            $output->writeln(vsprintf(
                '<info>The following database schema updates %s performed:</info>',
                [$dryRun ? 'should be' : 'were']
            ));
            $renderer->render($result, $output, $verbose);
        } else {
            $output->writeln(vsprintf(
                '<info>No schema updates %s performed for update %s:%s</info>',
                [
                    $dryRun ? 'must be' : 'were',
                    count($updateTypes) > 1 ? 'types' : 'type',
                    PHP_EOL . '"' . implode('", "', $updateTypes) . '"',
                ]
            ));
        }

        if ($result->hasErrors()) {
            $output->writeln('');
            $output->writeln('<error>The following errors occurred:</error>');
            $renderer->renderErrors($result, $output, $verbose);
        }
    }
}
