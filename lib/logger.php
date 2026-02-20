<?php

function logError(string $message, array $context = []): void
{
  $logDir = __DIR__ . '/../logs';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
  }

  $logFile = $logDir . '/app.log';
  $timestamp = date('Y-m-d H:i:s');
  $contextStr = $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
  $line = "[{$timestamp}] {$message}" . ($contextStr ? " | {$contextStr}" : '') . PHP_EOL;

  @file_put_contents($logFile, $line, FILE_APPEND);
}
