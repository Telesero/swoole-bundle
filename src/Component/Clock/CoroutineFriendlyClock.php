<?php

declare(strict_types=1);

namespace K911\Swoole\Component\Clock;

use Assert\Assertion;
use Swoole\Runtime;

final class CoroutineFriendlyClock implements ClockInterface
{
//    public function __construct()
//    {
//        Assertion::true((Runtime::getHookFlags() & SWOOLE_HOOK_SLEEP) === SWOOLE_HOOK_SLEEP, 'Swoole Coroutine hook "SWOOLE_HOOK_SLEEP" must be enabled');
//    }

    public function timeout(callable $condition, float $timeoutSeconds = 10, int $stepMicroseconds = 1000): bool
    {
        $now = $this->currentTime();
        $start = $now;
        $max = $start + $timeoutSeconds;

        do {
            if ($condition()) {
                return true;
            }

            $now = $this->currentTime();
            $this->microSleep($stepMicroseconds);
        } while ($now < $max);

        return false;
    }

    public function currentTime(): float
    {
        return \microtime(true);
    }

    public function microSleep(int $microseconds): void
    {
        \usleep($microseconds);
    }
}
