<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor\Queue;

interface QueueItemInterface
{
    public function exportAttributes(): array;

    public function getCompletePercent(): float;

    public function getCompleteRatio(): float;

    public function getName(): string;

    public function getExpectedUpdates(): int;

    public function getCountedUpdates(): int;

    public function getLabel(): string;

    public function setLabel(?string $label): self;
}