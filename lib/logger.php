<?php

require_once __DIR__ . '/privacy.php';

function isSensitiveLogKey(string $key): bool
{
  $normalized = mb_strtolower($key, 'UTF-8');
  $sensitiveTokens = [
    'nome',
    'name',
    'telefone',
    'phone',
    'whatsapp',
    'obs',
    'observ',
    'mensagem',
    'message',
    'token',
    'csrf',
    'cookie',
    'session',
    'authorization',
    'senha',
    'password',
  ];

  foreach ($sensitiveTokens as $token) {
    if (str_contains($normalized, $token)) {
      return true;
    }
  }

  return false;
}

function sanitizeLogContextValue(mixed $value, string $key = ''): mixed
{
  if (is_array($value)) {
    $safe = [];
    foreach ($value as $childKey => $childValue) {
      $safe[(string)$childKey] = sanitizeLogContextValue($childValue, (string)$childKey);
    }
    return $safe;
  }

  if (is_object($value)) {
    return sanitizeLogContextValue((array)$value, $key);
  }

  if ($key !== '') {
    $normalized = mb_strtolower($key, 'UTF-8');
    if (str_contains($normalized, 'ip')) {
      return anonymizeIpAddress((string)$value);
    }
    if (isSensitiveLogKey($key)) {
      return '[REDACTED]';
    }
  }

  if (is_string($value)) {
    $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
    $value = trim($value);
    if (mb_strlen($value, 'UTF-8') > 250) {
      $value = mb_substr($value, 0, 250, 'UTF-8') . '...';
    }
    return $value;
  }

  if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
    return $value;
  }

  return '[UNSERIALIZABLE]';
}

function logError(string $message, array $context = []): void
{
  $logDir = __DIR__ . '/../logs';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
  }

  $logFile = $logDir . '/app.log';
  $timestamp = date('Y-m-d H:i:s');
  $safeContext = sanitizeLogContextValue($context);
  $contextJson = $safeContext ? json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
  $contextStr = is_string($contextJson) ? $contextJson : '';
  $safeMessage = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $message) ?? 'application error';
  $line = "[{$timestamp}] {$safeMessage}" . ($contextStr ? " | {$contextStr}" : '') . PHP_EOL;

  @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
