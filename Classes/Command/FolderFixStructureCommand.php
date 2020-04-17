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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;

class FolderFixStructureCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Fixes the folder structure.')
            ->setHelp(
                <<<'EOH'
Automatically create files and folders, required for a TYPO3 installation.
This command creates the required folder structure needed for TYPO3 including extensions.

<b>Example</b>
Call it like this: <code>%command.full_name%</code>
EOH
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
        try {
            /** @var DefaultFactory $folderStructureFactory */
            $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
            $structureFacade = $folderStructureFactory->getStructure();
            $fixedStatusObjects = $structureFacade->fix();

            if (empty($fixedStatusObjects->toArray())) {
                $output->writeln('<info>No action were performed!</info>');
            } else {
                $output->writeln('<info>The following directory structure have been fixed!</info>');
                foreach ($fixedStatusObjects as $fixedStatusObject) {
                    $output->writeln($fixedStatusObject->getTitle());
                }
            }
        } catch (\Exception $exception) {
            $output->writeln('<error>Some error happened!</error>');
            $output->writeln($exception->getMessage());
        }

        return 0;
    }
}
