<?php
declare(strict_types=1);


namespace App\Logging;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerHolder implements LoggerInterface
{
    private IoLogger $ioLogger;

    public function __construct(
        private readonly Logger $fileLogger
    )
    {

    }

    public function setIoLogger(IoLogger $ioLogger): void
    {
        $this->ioLogger = $ioLogger;
    }

    public function emergency($message, array $context = []): void
    {
        $this->ioLogger->log('emergency', $message, $context);
        $this->fileLogger->emergency($message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->ioLogger->log('alert', $message, $context);
        $this->fileLogger->alert($message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->ioLogger->log('critical', $message, $context);
        $this->fileLogger->critical($message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->ioLogger->log('error', $message, $context);
        $this->fileLogger->error($message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->ioLogger->log('warning', $message, $context);
        $this->fileLogger->warning($message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->ioLogger->log('notice', $message, $context);
        $this->fileLogger->notice($message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->ioLogger->log('info', $message, $context);
        $this->fileLogger->info($message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->ioLogger->log('debug', $message, $context);
        $this->fileLogger->debug($message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->ioLogger->log($level, $message, $context);
        $this->fileLogger->log($level, $message, $context);
    }
}
