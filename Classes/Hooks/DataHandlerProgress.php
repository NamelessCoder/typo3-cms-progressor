<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor\Hooks;

use NamelessCoder\Progressor\Progressor;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * DataHandler progress monitoring
 *
 * Initializes a Progressor monitoring when commands/updates begin and counts
 * progress towards completion as each of the "post" methods get fired.
 */
class DataHandlerProgress
{
    /**
     * @param string $command Command that was executed
     * @param string $table The table TCEmain is currently processing
     * @param string $id The records id (if any)
     * @param array $fieldArray The field names and their values to be processed
     * @param DataHandler $reference Reference to the parent object (TCEmain)
     * @return void
     */
    public function processDatamap_afterDatabaseOperations($command, $table, $id, $fieldArray, $reference)
    {
        Progressor::recordProgress($this->getCacheIdentifier());
    }

    public function processCmdmap_beforeStart(DataHandler $dataHandler)
    {
        $expectedUpdates = 0;
        foreach ($dataHandler->datamap as $touchedRecords) {
            $expectedUpdates += count($touchedRecords);
        }
        foreach ($dataHandler->cmdmap as $touchedRecords) {
            // Expected number of updates for COMMANDS is stack count * 2; both DB update and post-command will fire.
            $expectedUpdates += (count($touchedRecords) * 2);
        }

        Progressor::recordProgress($this->getCacheIdentifier(), $expectedUpdates, 'DataHandler');
    }

    /**
     * Command post processing method
     *
     * Like other pre/post methods this method calls the corresponding
     * method on Providers which match the table/id(record) being passed.
     *
     * In addition, this method also listens for paste commands executed
     * via the TYPO3 clipboard, since such methods do not necessarily
     * trigger the "normal" record move hooks (which we also subscribe
     * to and react to in moveRecord_* methods).
     *
     * @param string $command The TCEmain operation status, fx. 'update'
     * @param string $table The table TCEmain is currently processing
     * @param string $id The records id (if any)
     * @param string $relativeTo Filled if command is relative to another element
     * @param DataHandler $reference Reference to the parent object (TCEmain)
     * @param array $pasteUpdate
     * @param array $pasteDataMap
     * @return void
     */
    public function processCmdmap_postProcess(&$command, $table, $id, &$relativeTo, &$reference, &$pasteUpdate, &$pasteDataMap)
    {
        Progressor::recordProgress($this->getCacheIdentifier());
    }

    protected function getCacheIdentifier()
    {
        return 'datahandler-' . (string) $GLOBALS['BE_USER']->user['uid'];
    }
}