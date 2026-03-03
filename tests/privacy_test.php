<?php

putenv('APP_DATA_KEY=base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=');
require_once __DIR__ . '/../lib/privacy.php';

$failures = 0;

if (!function_exists('assertEqual')) {
  function assertEqual($actual, $expected, string $message)
  {
    global $failures;
    if ($actual !== $expected) {
      $failures++;
      echo "[FAIL] {$message} | Expected: " . var_export($expected, true) . " Got: " . var_export($actual, true) . PHP_EOL;
    }
    else {
      echo "[PASS] {$message}" . PHP_EOL;
    }
  }
}

assertEqual(isDataProtectionEnabled(), true, 'isDataProtectionEnabled with valid key');

$plain = '11999998888';
$encrypted = encryptSensitiveData($plain);
$decrypted = decryptSensitiveData($encrypted);

assertEqual($encrypted !== $plain, true, 'encryptSensitiveData changes value');
assertEqual($decrypted, $plain, 'decryptSensitiveData restores original');
assertEqual(anonymizeIpAddress('192.168.0.42'), '192.168.0.0', 'anonymizeIpAddress ipv4');

if ($failures > 0) {
  echo PHP_EOL . "Tests failed: {$failures}" . PHP_EOL;
  exit(1);
}

echo PHP_EOL . "Privacy tests passed." . PHP_EOL;

