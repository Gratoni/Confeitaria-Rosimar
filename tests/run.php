<?php
require_once __DIR__ . '/../lib/cart.php';

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

// normalizeQuantity
assertEqual(normalizeQuantity(2.2, 'un'), 2.0, 'normalizeQuantity unit rounds');
assertEqual(normalizeQuantity(0.2, 'un'), 1.0, 'normalizeQuantity unit min 1');
assertEqual(normalizeQuantity(1.0, 'kg'), 1.0, 'normalizeQuantity kg keeps');
assertEqual(normalizeQuantity(0.2, 'kg'), 0.5, 'normalizeQuantity kg min 0.5');

// formatQuantityDisplay
assertEqual(formatQuantityDisplay(1.0, 'kg'), '1,0kg', 'formatQuantityDisplay kg');
assertEqual(formatQuantityDisplay(2.0, 'un'), '2x', 'formatQuantityDisplay unit');

// calculateSubtotal
assertEqual(calculateSubtotal(10.0, 2.5), 25.0, 'calculateSubtotal');

if ($failures > 0) {
  echo PHP_EOL . "Tests failed: {$failures}" . PHP_EOL;
  exit(1);
}

echo PHP_EOL . "All tests passed." . PHP_EOL;

echo PHP_EOL . "Running validation tests..." . PHP_EOL;
require_once __DIR__ . '/validation_test.php';

echo PHP_EOL . "Running integration cart tests..." . PHP_EOL;
require_once __DIR__ . '/integration_cart_test.php';

echo PHP_EOL . "Running privacy tests..." . PHP_EOL;
require_once __DIR__ . '/privacy_test.php';
