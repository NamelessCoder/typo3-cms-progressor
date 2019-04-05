<?php
declare(strict_types=1);
namespace NamelessCoder\Progressor\Queue;

class NaiveQueueItem extends AbstractQueueItem
{
    protected $name = '';
    protected $expectedUpdates = 0;
    protected $countedUpdates = 0;

    /**
     * @var string|null
     */
    protected $label;

    public function __construct(string $name, int $expectedUpdates, ?string $label)
    {
        $this->name = $name;
        $this->expectedUpdates = $expectedUpdates;
        $this->label = $label;
    }

    public function addProgress(int $ticks = 1): self
    {
        $this->countedUpdates += $ticks;
        $this->expectedUpdates = max($this->expectedUpdates, $this->countedUpdates);
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpectedUpdates(): int
    {
        return $this->expectedUpdates;
    }

    public function getCountedUpdates(): int
    {
        return $this->countedUpdates;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->name;
    }

    public function setExpectedUpdates(int $expectedUpdates): self
    {
        $this->expectedUpdates = $expectedUpdates;
        return $this;
    }

    public function setLabel(?string $label): QueueItemInterface
    {
        $this->label = $label;
        return $this;
    }
}
