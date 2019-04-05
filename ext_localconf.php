<?php
defined('TYPO3_MODE') or die('Access denied');

(function($configuration) {

    if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['progressor'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['progressor'] = array(
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            'groups' => array('system'),
            'options' => [
                'defaultLifetime' => 600,
            ],
        );
    }

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['progressor']['setup'] = is_string($configuration) ? unserialize($configuration) : $configuration;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['progressor'] = \NamelessCoder\Progressor\Hooks\ScriptLoader::class . '->load';

    if ((bool) ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['progressor']['setup']['dataHandlerProgress'] ?? false)) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['progressor'] = \NamelessCoder\Progressor\Hooks\DataHandlerProgress::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['progressor'] = \NamelessCoder\Progressor\Hooks\DataHandlerProgress::class;
    }

})($_EXTCONF);
