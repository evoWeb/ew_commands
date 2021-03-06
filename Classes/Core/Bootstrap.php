<?php

namespace Evoweb\EwCommands\Core;

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

use Evoweb\EwCommands\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extend to use \Evoweb\EwCommands\Utility\ExtensionManagementUtility instead of core ExtensionManagementUtility
 */
class Bootstrap extends \TYPO3\CMS\Core\Core\Bootstrap
{
    /**
     * @var \TYPO3\CMS\Core\Core\Bootstrap
     */
    protected static $instance;

    /**
     * Load $TCA
     *
     * This will mainly set up $TCA through extMgm API.
     *
     * @param bool $allowCaching True, if loading TCA from cache is allowed
     * @param FrontendInterface $coreCache
     * @return \TYPO3\CMS\Core\Core\Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadBaseTca(bool $allowCaching = true, FrontendInterface $coreCache = null)
    {
        if ($allowCaching) {
            if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) < 10000000) {
                // @todo remove once TYPO3 9.5.x support is dropped
                $identifier = 'cache_core';
            } else {
                $identifier = 'core';
            }
            $coreCache = $coreCache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache($identifier);
        }
        ExtensionManagementUtility::loadBaseTca($allowCaching, $coreCache);
        return static::$instance;
    }
}
