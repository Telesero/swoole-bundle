<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Process;

use PHPUnit\Framework\TestCase;

final class AbstractSignalHandlerTest extends TestCase
{
    /**
     * @var SignalHandlerMock
     */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new SignalHandlerMock();
    }

    public function testProcessIsRunning(): void
    {
        $existingPid = 999;
        $this->handler->existingPids[] = $existingPid;

        self::assertTrue($this->handler->runningStatus($existingPid));
        self::assertFalse($this->handler->runningStatus($existingPid - 1));
        self::assertFalse($this->handler->runningStatus($existingPid + 1));

        self::assertSame($this->handler->killedPairs, [
            [0, $existingPid],
            [0, $existingPid - 1],
            [0, $existingPid + 1],
        ]);
    }

    public function testGracefulTerminationForceKill(): void
    {
        $existingPid = 999;
        $this->handler->existingPids[] = $existingPid;

        $this->handler->gracefullyTerminate($existingPid, 10, 1 * 1000000);

        dump($this->handler->killedPairs);
    }
}
