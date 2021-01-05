<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Process;

use K911\Swoole\Process\Signal\AbstractSignalHandler;
use K911\Swoole\Process\Signal\Exception\SignalException;

final class SignalHandlerMock extends AbstractSignalHandler
{
    public $killedPairs = [];
    public $existingPids = [];
    public $pidsNotRespectingSigTerm = [];
    public $executionSeconds;

    public function register(callable $handler, int $signalNumber, int ...$moreSignalNumber): void
    {
    }

    public function kill(int $signalNumber, int $processId): void
    {
        $this->killedPairs[] = [$signalNumber, $processId];

        if (!\in_array($processId, $this->existingPids, true)) {
            throw SignalException::fromKillCommand($signalNumber, $processId);
        }
    }

    protected function timeout(callable $condition, int $timeoutSeconds = 10, int $stepMicroSeconds = 1000): bool
    {
        $this->executionSeconds = 0;
        while ($this->executionSeconds <= $timeoutSeconds) {
            if ($condition()) {
                return true;
            }

            ++$this->executionSeconds;
        }

        return false;
    }
}
