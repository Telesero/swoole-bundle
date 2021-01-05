<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use K911\Swoole\Bridge\Symfony\Bundle\Exception\CouldNotCreatePidFileException;
use K911\Swoole\Bridge\Symfony\Bundle\Exception\PidFileNotAccessibleException;
use function K911\Swoole\get_object_property;
use K911\Swoole\Process\Signal\PcntlSignalHandler;
use K911\Swoole\Process\Signal\Signal;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ServerStartCommand extends AbstractServerStartCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Run Swoole HTTP server in the background.')
            ->addOption('pid-file', null, InputOption::VALUE_REQUIRED, 'Pid file', $this->parameterBag->get('kernel.project_dir').'/var/swoole.pid')
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        /** @var null|string $pidFile */
        $pidFile = $input->getOption('pid-file');
        $serverConfiguration->daemonize($pidFile);

        parent::prepareServerConfiguration($serverConfiguration, $input);
    }

    /**
     * {@inheritdoc}
     */
    protected function startServer(HttpServerConfiguration $serverConfiguration, HttpServer $server, SymfonyStyle $io): void
    {
        $signalHandler = new PcntlSignalHandler();
        $signalHandler->register(function (int $signalNo) use ($server): void {
            $swooleServer = $server->getServer();
            dump(\sprintf('Signal %d hit (process %d, swoole master pid %d, manager %d)', $signalNo, \getmypid(), $swooleServer->master_pid, $swooleServer->manager_pid));
            if (\getmypid() !== $swooleServer->master_pid) {
                try {
                    $server->shutdown();
                } catch (\Throwable $exception) {
                    dump(\sprintf('except (process: %d)', \getmypid()));
                }
            }
        }, Signal::term(), Signal::int());

        $pidFile = $serverConfiguration->getPidFile();

        if (!\touch($pidFile)) {
            throw PidFileNotAccessibleException::forFile($pidFile);
        }

        if (!\is_writable($pidFile)) {
            throw CouldNotCreatePidFileException::forPath($pidFile);
        }

        $this->closeSymfonyStyle($io);

        $server->start();
    }

    private function closeSymfonyStyle(SymfonyStyle $io): void
    {
        $output = get_object_property($io, 'output', OutputStyle::class);
        if ($output instanceof ConsoleOutput) {
            $this->closeConsoleOutput($output);
        } elseif ($output instanceof StreamOutput) {
            $this->closeStreamOutput($output);
        }
    }

    /**
     * Prevents usage of php://stdout or php://stderr while running in background.
     */
    private function closeConsoleOutput(ConsoleOutput $output): void
    {
        \fclose($output->getStream());

        /** @var StreamOutput $streamOutput */
        $streamOutput = $output->getErrorOutput();

        $this->closeStreamOutput($streamOutput);
    }

    private function closeStreamOutput(StreamOutput $output): void
    {
        $output->setVerbosity(PHP_INT_MIN);
        \fclose($output->getStream());
    }
}
