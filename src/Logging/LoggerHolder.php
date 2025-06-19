<?php
declare(strict_types=1);


namespace App\Logging;

use Psr\Log\LoggerInterface;

final class LoggerHolder implements LoggerInterface
{
    private IoLogger $ioLogger;

    public function __construct()
    {

    }

    public function setIoLogger(IoLogger $ioLogger): void
    {
        $this->ioLogger = $ioLogger;
    }

    public function emergency($message, array $context = []): void
    {
        $this->ioLogger->log('emergency', $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->ioLogger->log('alert', $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->ioLogger->log('critical', $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->ioLogger->log('error', $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->ioLogger->log('warning', $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->ioLogger->log('notice', $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->ioLogger->log('info', $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->ioLogger->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->ioLogger->log($level, $message, $context);
    }
}
