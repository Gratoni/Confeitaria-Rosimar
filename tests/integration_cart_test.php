<?php
require_once __DIR__ . '/../lib/cart.php';

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

// Integration-ish: simulate cart math with different units
$produtoKg = ['id' => 1, 'nome' => 'Bolo', 'preco' => 70.0, 'unidade' => 'kg'];
$produtoUn = ['id' => 2, 'nome' => 'Bolo', 'preco' => 40.0, 'unidade' => 'un'];

$qtdKg = normalizeQuantity(1.2, $produtoKg['unidade']);
$qtdUn = normalizeQuantity(2.7, $produtoUn['unidade']);

assertEqual($qtdKg, 1.0, 'integration kg rounds to half-step');
assertEqual($qtdUn, 3.0, 'integration unit rounds');

$subtotalKg = calculateSubtotal($produtoKg['preco'], $qtdKg);
$subtotalUn = calculateSubtotal($produtoUn['preco'], $qtdUn);

assertEqual($subtotalKg, 70.0, 'integration subtotal kg');
assertEqual($subtotalUn, 120.0, 'integration subtotal unit');

if ($failures > 0) {
  echo PHP_EOL . "Tests failed: {$failures}" . PHP_EOL;
  exit(1);
}

echo PHP_EOL . "Integration cart tests passed." . PHP_EOL;
