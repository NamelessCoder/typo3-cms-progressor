Progressor for TYPO3 CMS
========================

Renders progress bars in the TYPO3 backend header for long-running tasks.

Installing
----------

Available only through Composer/Packagist:

```bash
composer require namelesscoder/typo3-cms-progressor
```

Features
--------

* Uses an absolute minimum of configuration to start a monitoring process.
* Fire-and-forget progress monitoring integration for your own tasks via one-liner method calls.
* Integration for DataHandler tasks which makes one progress bar per backend user.
* Integration with database tables via prepared QueryBuilder instances that select "pending" records.

*The DataHandler integration can be enabled via extension configuration*. The other features require writing code either
inside your own long-running tasks, or in `ext_localconf.php` files to register QueryBuilder instances that will then
be monitored automatically whenever there are pending records.

How to use: QueryBuilder integration
------------------------------------

Using the QueryBuilder integration to monitor pending records in any table requires creating and preparing the
QueryBuilder instance and then handing it off to Progressor - the rest is handled automatically.

Your QueryBuilder instance can be registered anywhere (as long as that place is executed during any request) - for
example in your `ext_localconf.php` file:

```php
// Example: consider all hidden records in table "pages" to be a "pending" queue item. This shows a progress bar if
// there are any hidden pages in the site and adds progress as each page gets enabled.
// Note that in order to select hidden records you do need to remove the restriction for such records.
$queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
    ->getQueryBuilderForTable('pages');
$queryBuilder->getRestrictions()->removeByType(\TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction::class);
$queryBuilder->select('uid')->from('pages')->where('hidden = 1');
\NamelessCoder\Progressor\Progressor::trackProgressWithQuery($queryBuilder, 60, 'Hidden Pages');
``` 

This registers that QueryBuilder instance as the way Progressor reads progress information. The query is fired and all
rows are counted, then this initial row count is stored in a transient storage which auto-expires and each time the
progress information is read from the TYPO3 backend the progress is calculated based on current number of pending rows
vs initial number of pending rows. Once no more pending records remain the progress bar expires after 60 seconds.

The query should be as light as possible - one way to do that is to make sure it selects a minimum of columns and
definitely not select `*`. A good column to select which is usually there, is the `uid` column. The query must return
the rows that are "pending" and must not use dynamic values that change throughout a request because this causes skew
in the expected number of updates. Aside from that, it has no other side effects such as inability to execute the query.

* The first parameter is the QueryBuilder instance.
* The second parameter is the expected maximum time between each "tick" (you should multiply by 1.5 for safety).
* The third parameters is a label for your progress bar.

How to use: Fire and forget API
-------------------------------

Another way to use Progressor is from inside your own code, for example in loops which handle large amounts of records
or files and does one iteration for each resource. By calling a single method from such places you can initialize and
continually update a progress bar with the same method:

```php
$expectedUpdates = 123; // This can be continually updated if loop skips/appends entries along the way
\NamelessCoder\Progressor\Progressor::recordProgress('myprogress', $expectedUpdates, 'My progress');
```

The first time this method is called, or if the `$expectedUpdates` changes, the internal counter is (re-)initialized
with that new value. If the counter already exists, `1` unit of progress is added. When your loop finishes it should
then have reached a state where the internal counter has counted exactly the number of items you specified as expected.

Like the other integrations, after 60 seconds such an ad-hoc progress bar is automatically removed.

* The first parameter for the `recordProgress` method is unique name for your instance of the progress bar.
* The second parameter is optional and can contain the expected number of updates.
* The third parameter is a label for your progress bar (which should remain the same throughout the loop)

Limitations
-----------

* Progressor's monitoring is relatively naive with minimal safeguards, but one of the safeguards is that any values that
  are out of range (for example causing a completeness above 100%) will be truncated to the maximum logical value.
* The memory of Progressor is currently based on the caching framework and requires a cache backend which supports tags.
  This unfortunately means that the default implementation is a database table and causes additional database queries.