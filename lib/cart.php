<?php

function normalizeQuantity(float $qtd, string $unidade): float
{
  if ($unidade !== 'kg') {
    return (float)max(1, (int)round($qtd));
  }

  return (float)max(0.5, $qtd);
}

function formatQuantityDisplay(float $qtd, string $unidade): string
{
  if ($unidade === 'kg') {
    return number_format($qtd, 1, ',', '.') . 'kg';
  }

  return number_format($qtd, 0) . 'x';
}

function calculateSubtotal(float $precoUnit, float $qtd): float
{
  return $precoUnit * $qtd;
}
