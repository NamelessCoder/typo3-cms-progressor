/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Progressor/Progressor
 */
define(['jquery',
    'bootstrap'
], function($, bootstrap) {
    'use strict';

    /**
     * @type {settings: {}}
     * @exports TYPO3/CMS/Progressor/Progressor
     */
    var Progressor = {
        settings: {},
    };

    if ($('.topbar-header-site').length === 0) {
        // This instance is not the outermost frame renderer - return a dummy.
        return Progressor;
    }

    if ($('.topbar-header-site > .progressor.progressor-container').length > 0) {
        // This instance is not the first to be loaded, so it will be a dummy. Only one instance allowed.
        return Progressor;
    }

    $('<div class="progressor progressor-container"></div>').insertBefore($('.topbar-header-site > :first'));
    var container = $('.topbar-header-site .progressor.progressor-container');
    container.css({display: 'inline-block', padding: '14px 10px', width: '100%'});
    container.hide();

    // expose as global object
    TYPO3.Progressor = Progressor;

    // Interval starts at every 30 seconds. A response which contains progress info reduces this to 5 seconds.
    // A response without progress info resets it to 30 seconds.
    var interval = 10000;
    // The factor is used to divide the interval, the result becomes the interval when there are pending items.
    // A value of 6 means 30000/6 = 50000.
    var activeIntervalFactor = 5;

    var updateProgressBars = function() {
        $.ajax({
            url: TYPO3.settings.ajaxUrls['progressor_progress'],
            method: 'post',
            complete: function(e) {
                if (e.responseText.length === 0) {
                    setTimeout(updateProgressBars, interval);
                    container.hide();
                    return;
                }

                var items = $.parseJSON(e.responseText);

                if (items.length === 0) {
                    setTimeout(updateProgressBars, interval);
                    container.hide();
                    return;
                }

                var newContent = '';
                var nextInterval = interval;
                var width = Math.floor(98 / items.length);
                
                for (var index = 0; index < items.length; ++index) {
                    var percentFloored = Math.floor(items[index].ratio * 100);
                    var verboseOutput = '';
                    if (items.length < 3) {
                        verboseOutput = ' (' + items[index].counted + ' / ' + items[index].expected + ')';
                    }
                    newContent += '<div class="progress" style="display: inline-block; width: ' + width + '%; margin-right: 1em;">' +
                        '  <div class="progress-bar" role="progressbar" aria-valuenow="' + percentFloored + '"' +
                        '  aria-valuemin="0" aria-valuemax="100" style="width:' + percentFloored + '%">' +
                        items[index].label + ': ' + percentFloored + '%' + verboseOutput +
                        '  </div>' +
                        '</div>';
                    if (percentFloored < 100) {
                        nextInterval = Math.floor(interval / activeIntervalFactor);
                    }
                }
                container.html(newContent).show();
                setTimeout(updateProgressBars, nextInterval);
            },
            error: function() {
                container.html('').hide();
                return false;
            }
        });
    };

    updateProgressBars();

    return Progressor;
});
