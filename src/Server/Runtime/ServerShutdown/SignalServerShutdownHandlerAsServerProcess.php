<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime\ServerShutdown;

use K911\Swoole\Process\ProcessInterface;
use K911\Swoole\Process\ProcessManagerInterface;
use K911\Swoole\Process\Signal\Signal;
use K911\Swoole\Process\Signal\SignalHandlerInterface;
use K911\Swoole\Server\HttpServer;
use Swoole\Process;

/**
 * Registers signal handlers as process for SIGINT and SIGTERM signals
 * Used when running with "reactor" mode.
 */
final class SignalServerShutdownHandlerAsServerProcess implements ProcessInterface
{
    private HttpServer $server;
    private SignalHandlerInterface $signalHandler;

    private ProcessManagerInterface $processManager;

    public function __construct(HttpServer $server, SignalHandlerInterface $signalHandler, ProcessManagerInterface $processManager)
    {
        $this->server = $server;
        $this->signalHandler = $signalHandler;
        $this->processManager = $processManager;
    }

    public function run(Process $self): void
    {
        $run = true;
        $srv = $this->server->getServer();
        $this->signalHandler->register(function (int $sigNo) use (&$run, $srv): void {
            dump(\sprintf('process hit 2; master: %d, manager: %d, mypid: %d', $srv->master_pid, $srv->manager_pid, \getmypid()));
            $run = false;
            $this->server->shutdown();
        }, Signal::int(), Signal::term());

        $sleepTimes = 0;
        while ($run) {
            ++$sleepTimes;
            \sleep(1);
            if (0 === $sleepTimes % 5 && !$this->processManager->runningStatus($srv->master_pid)) {
                break;
            }
        }
    }
}
