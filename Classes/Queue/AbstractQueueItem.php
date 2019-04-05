<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor\Queue;

abstract class AbstractQueueItem implements QueueItemInterface
{
    protected $label = '';

    public function exportAttributes(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'expected' => $this->getExpectedUpdates(),
            'counted' => $this->getCountedUpdates(),
            'ratio' => $this->getCompleteRatio(),
        ];
    }

    public function getCompletePercent(): float
    {
        return (float)round($this->getCompleteRatio() * 100, 2);
    }

    public function getCompleteRatio(): float
    {
        $expectedUpdates = $this->getExpectedUpdates();
        if ($expectedUpdates === 0) {
            return 0;
        }
        return $this->getCountedUpdates() / $expectedUpdates;
    }

    public function getName(): string
    {
        return 'Unnamed queue item';
    }

    public function getExpectedUpdates(): int
    {
        return 0;
    }

    public function getCountedUpdates(): int
    {
        return 0;
    }

    public function setLabel(?string $label): QueueItemInterface
    {
        $this->label = $label;
        return $this;
    }
}