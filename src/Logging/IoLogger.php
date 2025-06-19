<?php
declare(strict_types=1);

namespace App\Logging;

use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Style\SymfonyStyle;

class IoLogger extends AbstractLogger
{
    public function __construct(
        private readonly SymfonyStyle $io
    )
    {
    }

    public function log($level, $message, array $context = []): void
    {
        $formatted = $context
            ? $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE)
            : $message;

        match ($level) {
            'emergency', 'alert', 'critical', 'error' => $this->io->error($formatted),
            'warning' => $this->io->warning($formatted),
            'notice' => $this->io->success($formatted),
            'info' => $this->io->note($formatted),
            'debug' => $this->io->writeln("<fg=gray>$formatted</>"),
            default => $this->io->writeln($formatted),
        };
    }
}
