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
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ClearCacheService;

class CacheFlushCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Flushes all caches.')
            ->setHelp(
                <<<'EOH'
Clears caches that are registered in the cache manager configuration

<b>Example</b>
Call it like this: <code>%command.full_name%</code>
EOH
            );
    }

    /**
     * Flush cache
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ClearCacheService $ClearCacheService */
        $clearCacheService = GeneralUtility::makeInstance(ClearCacheService::class);
        $clearCacheService->clearAll();

        /** @var OpcodeCacheService $opcodeCacheService */
        $opcodeCacheService = GeneralUtility::makeInstance(OpcodeCacheService::class);
        $opcodeCacheService->clearAllActive();

        $output->writeln('<info>Flushed all caches.</info>');

        return 0;
    }
}
