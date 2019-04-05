<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor\Hooks;

use TYPO3\CMS\Core\Page\PageRenderer;

class ScriptLoader
{
    public function load(array $parameters, PageRenderer $caller)
    {
        static $loaded = false;
        if (!$loaded) {
            $caller->loadRequireJsModule('TYPO3/CMS/Progressor/Progressor');
            $loaded = true;
        }
    }
}
