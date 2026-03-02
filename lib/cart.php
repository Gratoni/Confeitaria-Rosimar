<?php

const CART_MAX_DISTINCT_ITEMS = 30;
const CART_MAX_QTD_UN = 20;
const CART_MAX_QTD_KG = 10.0;

function normalizeUnit(string $unit): string
{
  return $unit === 'kg' ? 'kg' : 'un';
}

function getUnitStep(string $unit): float
{
  return normalizeUnit($unit) === 'kg' ? 0.5 : 1.0;
}

function getMaxQuantityForUnit(string $unit): float
{
  return normalizeUnit($unit) === 'kg' ? CART_MAX_QTD_KG : CART_MAX_QTD_UN;
}

function normalizeQuantity(float $qtd, string $unit): float
{
  $normalizedUnit = normalizeUnit($unit);

  if ($normalizedUnit !== 'kg') {
    $value = (float)max(1, (int)round($qtd));
    return min($value, CART_MAX_QTD_UN);
  }

  $halfStep = round($qtd * 2) / 2;
  $halfStep = max(0.5, $halfStep);
  return min($halfStep, CART_MAX_QTD_KG);
}

function isCartQuantityAllowed(float $qtd, string $unit): bool
{
  $normalized = normalizeQuantity($qtd, $unit);
  return abs($normalized - $qtd) < 0.001;
}

function formatQuantityDisplay(float $qtd, string $unit): string
{
  if (normalizeUnit($unit) === 'kg') {
    return number_format($qtd, 1, ',', '.') . 'kg';
  }

  return number_format($qtd, 0) . 'x';
}

function calculateSubtotal(float $precoUnit, float $qtd): float
{
  return $precoUnit * $qtd;
}
