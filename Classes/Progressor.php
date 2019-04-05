<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor;

use NamelessCoder\Progressor\Queue\NaiveQueueItem;
use NamelessCoder\Progressor\Queue\TableMonitoringQueueItem;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use NamelessCoder\Progressor\Queue\QueueItemInterface;

/**
 * Main API class for Progressor
 *
 * Static methods to be called from ext_localconf.php and XHR integration.
 */
abstract class Progressor
{
    /**
     * @var QueueItemInterface[]
     */
    protected static $staticQueueItems = [];

    /**
     * Register a query that will select pending records. The state of the
     * counter is managed internally and will reset itself when pending
     * count reaches zero, and set itself to an initial expected count when
     * the pending count changes above zero.
     *
     * @param QueryBuilder $pendingRowsQuery
     * @param int $expectedDuration Approximate maximum number of seconds each tick of progress in the table can take
     * @param string|null $label
     */
    public static function trackProgressWithQuery(QueryBuilder $pendingRowsQuery, int $expectedDuration, ?string $label = null)
    {
        $queueItem = GeneralUtility::makeInstance(TableMonitoringQueueItem::class, $pendingRowsQuery, $expectedDuration, $label);
        static::$staticQueueItems[$queueItem->getName()] = $queueItem;
    }

    /**
     * Report progress on processing of $queueName.
     * Timing, completeness etc. is based on what was passed to startProgress.
     *
     * Should be called once with the expected number of updates (and a label)
     * then can be called afterwards to record one "tick" of progress. Should
     * the expected number of updates change during processing that too can be
     * overridden by passing a new expected number of updates. Equally, the
     * label can be changed by passing a new label (if you do not wish to change
     * the expected number of updates but wish to change the label, pass zero
     * as number of expected updates).
     *
     * You do NOT need to create a label that contains percentage values or
     * number of items processed/remaining - the handler does this for you.
     *
     * @param string $queueName Name of queue to which progress is added
     * @param int $expectedNumberOfUpdates The number of updates the queue is expecting in total
     * @param string|null $label Optional label of the queue
     */
    public static function recordProgress(string $queueName, int $expectedNumberOfUpdates = 0, ?string $label = null)
    {
        $cache = static::getCache();
        $queueItem = static::getQueueItem($queueName);
        if (!$queueItem) {
            $queueItem = GeneralUtility::makeInstance(NaiveQueueItem::class, $queueName, $expectedNumberOfUpdates, $label);
        } else {
            if ($expectedNumberOfUpdates) {
                $queueItem->setExpectedUpdates($expectedNumberOfUpdates);
            }
            $queueItem->addProgress(1);
            if ($label) {
                $queueItem->setLabel($label);
            }
        }
        $cache->set(static::getCacheIdentifier($queueName), $queueItem, ['item'], $queueItem->getCompleteRatio() === 1 ? 60 : 300);
    }

    /**
     * Gets a queue item, by name.
     *
     * @param string $queueName
     * @return NaiveQueueItem|null
     */
    public static function getQueueItem(string $queueName): ?QueueItemInterface
    {
        return (static::$staticQueueItems[$queueName] ?? static::getCache()->get(static::getCacheIdentifier($queueName))) ?: null;
    }

    /**
     * Get every active queue item.
     *
     * @return \Generator|NaiveQueueItem[]
     */
    public static function getAllQueueItems(): \Generator
    {
        $cache = static::getCache();
        foreach ($cache->getByTag('item') as $item) {
            yield $item;
        };
        foreach (static::$staticQueueItems as $item) {
            yield $item;
        }
    }

    protected static function getCacheIdentifier(string $queueName): string
    {
        return 'queue-' . sha1($queueName);
    }

    protected static function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('progressor');
    }
}

