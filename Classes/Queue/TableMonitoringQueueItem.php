<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor\Queue;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class TableMonitoringQueueItem extends AbstractQueueItem
{
    /**
     * @var QueryBuilder
     */
    protected $pendingQuery;

    /**
     * @var string|null
     */
    protected $label;

    protected $queryFired = false;
    protected $numberOfPendingRecords = 0;
    protected $numberOfRecords = 0;
    protected $expectedDuration = 0;

    public function __construct(QueryBuilder $pendingQuery, int $expectedDuration = 0, ?string $label = null)
    {
        $this->pendingQuery = $pendingQuery;
        $this->expectedDuration = $expectedDuration;
        $this->setLabel($label);
    }

    public function getName(): string
    {
        return trim((string)($this->pendingQuery->getQueryPart('from')[0]['table'] ?? 'invalid-table'), '`\'"`');
    }

    public function getLabel(): string
    {
        $tableName = $this->getName();
        return $this->label ?? LocalizationUtility::translate($GLOBALS['TCA'][$tableName]['title']) ?? 'Table: ' . $tableName;
    }

    public function getExpectedUpdates(): int
    {
        $this->executeQueriesIfNecessary();
        return $this->numberOfRecords;
    }

    public function getCountedUpdates(): int
    {
        $this->executeQueriesIfNecessary();
        return $this->numberOfRecords - $this->numberOfPendingRecords;
    }

    public function getCompleteRatio(): float
    {
        $this->executeQueriesIfNecessary();
        return $this->numberOfPendingRecords === 0 ? 1 : parent::getCompleteRatio();
    }

    protected function executeQueriesIfNecessary()
    {
        if (!$this->queryFired) {
            $this->queryFired = true;
            $this->numberOfPendingRecords = $this->pendingQuery->execute()->rowCount();
            $this->numberOfRecords = (int) $this->getCache()->get($this->getCacheIdentifier());

            if ($this->numberOfPendingRecords === 0 && $this->numberOfRecords > 0) {
                $this->storeStartingNumberOfRecords($this->numberOfRecords, -1);
            } elseif ($this->numberOfPendingRecords > $this->numberOfRecords) {
                $this->numberOfRecords = $this->numberOfPendingRecords;
                $this->storeStartingNumberOfRecords($this->numberOfPendingRecords, $this->getCacheLifetime());
            } elseif ($this->numberOfRecords > 0 && $this->numberOfPendingRecords < $this->numberOfRecords) {
                $this->storeStartingNumberOfRecords($this->numberOfRecords, $this->getCacheLifetime());
            }
        }
    }

    protected function storeStartingNumberOfRecords(int $numberOfRecords, int $lifetime)
    {
        $this->getCache()->set($this->getCacheIdentifier(), $numberOfRecords, [], $lifetime);
    }

    protected function getCacheLifetime(): int
    {
        return $this->expectedDuration ?: 60;
    }

    protected function getCacheIdentifier(): string
    {
        return 'table-' . $this->getName();
    }

    protected function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('progressor');
    }
}