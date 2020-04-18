<?php

namespace Ew\EwCommands\Utility;

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

use Ew\EwCommands\Preparations\TcaPreparation;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension Management functions
 *
 * This class is never instantiated, rather the methods inside is called as functions like
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('my_extension');
 */
class ExtensionManagementUtility extends \TYPO3\CMS\Core\Utility\ExtensionManagementUtility
{
    /**
     * Find all Configuration/TCA/* files of extensions and create base TCA from it.
     * The filename must be the table name in $GLOBALS['TCA'], and the content of
     * the file should return an array with content of a specific table.
     *
     * @see Extension core, extensionmanager and others for examples.
     */
    protected static function buildBaseTcaFromSingleFiles()
    {
        $GLOBALS['TCA'] = [];

        $activePackages = static::$packageManager->getActivePackages();

        // First load "full table" files from Configuration/TCA
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')
                    ->in($package->getPackagePath() . 'Configuration/TCA');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                $tcaOfTable = require $fileInfo->getPathname();
                if (is_array($tcaOfTable)) {
                    $tcaTableName = substr($fileInfo->getBasename(), 0, -4);
                    $GLOBALS['TCA'][$tcaTableName] = $tcaOfTable;
                }
            }
        }

        // Apply category stuff
        CategoryRegistry::getInstance()->applyTcaForPreRegisteredTables();

        // Execute override files from Configuration/TCA/Overrides
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')
                    ->in($package->getPackagePath() . 'Configuration/TCA/Overrides');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                require $fileInfo->getPathname();
            }
        }

        // Call the TcaMigration and log any deprecations.
        $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
        $GLOBALS['TCA'] = $tcaMigration->migrate($GLOBALS['TCA']);
        $messages = $tcaMigration->getMessages();
        if (!empty($messages)) {
            $context = 'Automatic TCA migration done during bootstrap. Please adapt TCA accordingly, these migrations'
                . ' will be removed. The backend module "Configuration -> TCA" shows the modified values.'
                . ' Please adapt these areas:';
            array_unshift($messages, $context);
            trigger_error(implode(LF, $messages), E_USER_DEPRECATED);
        }

        // TCA preparation
        $tcaPreparation = GeneralUtility::makeInstance(TcaPreparation::class);
        $GLOBALS['TCA'] = $tcaPreparation->prepare($GLOBALS['TCA']);

        static::dispatchTcaIsBeingBuiltEvent($GLOBALS['TCA']);
    }
}
