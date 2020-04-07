<?php

namespace Evoweb\EwCommands\Command;

/*
 * This file is part of the evoweb console.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Evoweb\EwCommands\Database\Schema\SchemaUpdateResultRenderer;
use Evoweb\EwCommands\Database\Schema\SchemaUpdateType;
use Evoweb\EwCommands\Service\Database\SchemaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * CLI command for the 'scheduler' extension which executes
 */
class UpdateschemaCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Update the database schema.')
            ->setHelp('If no parameter is given, the schema update executes in \'safe\' type.'
                . ' Call it like this: typo3/sysext/core/bin/typo3 evoweb:updateschema --type="*.add,*.change"')
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
     * Execute scheduler tasks
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var SchemaService $schemaService */
        $schemaService = $objectManager->get(SchemaService::class);
        /** @var SchemaUpdateResultRenderer $schemaUpdateResultRenderer */
        $schemaUpdateResultRenderer = $objectManager->get(SchemaUpdateResultRenderer::class);

        $verbose = $output->isVerbose();
        $schemaUpdateTypes = explode(',', $input->getOption('type'));
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false;

        try {
            $expandedSchemaUpdateTypes = SchemaUpdateType::expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (InvalidEnumerationValueException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return 1;
        }

        $result = $schemaService->updateSchema($expandedSchemaUpdateTypes, $dryRun);

        if ($result->hasPerformedUpdates()) {
            $output->writeln(
                '<info>The following database schema updates %s performed:</info>',
                [$dryRun ? 'should be' : 'were']
            );
            $schemaUpdateResultRenderer->render($result, $output, $verbose);
        } else {
            $output->writeln(
                '<info>No schema updates %s performed for update type%s:%s</info>',
                [
                    $dryRun ? 'must be' : 'were',
                    count($expandedSchemaUpdateTypes) > 1 ? 's' : '',
                    PHP_EOL . '"' . implode('", "', $expandedSchemaUpdateTypes) . '"',
                ]
            );
        }

        if ($result->hasErrors()) {
            $output->writeln('');
            $output->writeln('<error>The following errors occurred:</error>');
            $schemaUpdateResultRenderer->renderErrors($result, $output, $verbose);
            return 1;
        }

        return 0;
    }
}
