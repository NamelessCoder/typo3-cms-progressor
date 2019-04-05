<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor\Controller;

use NamelessCoder\Progressor\Progressor;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

class ProgressController
{
    public function progressAction(RequestInterface $request, ResponseInterface $response)
    {
        $data = [];
        $items = Progressor::getAllQueueItems();
        foreach ($items as $item) {
            if (!$item->getExpectedUpdates()) {
                continue;
            }
            $data[] = $item->exportAttributes();
        }
        if (!class_exists(JsonResponse::class)) {
            // Fallback for TYPO3 8.7 - render the JSON directly and exit.
            header('Content-type: text/json');
            if (!empty($data)) {
                echo json_encode($data);
            }
            exit();
        }
        return new JsonResponse($data);
    }
}