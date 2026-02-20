<?php
require_once __DIR__ . '/../lib/validation.php';

$failures = 0;

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

// sanitizePhone
assertEqual(sanitizePhone('(11) 99999-8888'), '11999998888', 'sanitizePhone digits');

// validateCheckoutInput
$errors = validateCheckoutInput('Jo', '11999998888');
assertEqual(count($errors) > 0, true, 'validateCheckoutInput short name');

$errors = validateCheckoutInput('Joao Silva', '123');
assertEqual(count($errors) > 0, true, 'validateCheckoutInput invalid phone');

$errors = validateCheckoutInput('Joao Silva', '11999998888', str_repeat('a', 501));
assertEqual(count($errors) > 0, true, 'validateCheckoutInput obs too long');

$errors = validateCheckoutInput('Joao Silva', '11999998888', 'ok');
assertEqual($errors, [], 'validateCheckoutInput ok');

if ($failures > 0) {
  echo PHP_EOL . "Tests failed: {$failures}" . PHP_EOL;
  exit(1);
}

echo PHP_EOL . "All validation tests passed." . PHP_EOL;
