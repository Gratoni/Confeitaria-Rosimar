<?php

function logError(string $message, array $context = []): void
{
  $logDir = __DIR__ . '/../logs';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
  }

  $logFile = $logDir . '/app.log';
  $timestamp = date('Y-m-d H:i:s');
  $contextJson = $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
  $contextStr = is_string($contextJson) ? $contextJson : '';
  $line = "[{$timestamp}] {$message}" . ($contextStr ? " | {$contextStr}" : '') . PHP_EOL;

  @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
