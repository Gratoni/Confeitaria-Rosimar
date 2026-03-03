<?php

function sanitizePhone(string $telefone): string
{
  return preg_replace('/\D+/', '', $telefone) ?? '';
}

function sanitizeCheckoutText(string $value, int $maxLength): string
{
  $value = trim($value);
  $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? '';
  $value = preg_replace('/\s+/', ' ', $value) ?? '';

  if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
    $value = mb_substr($value, 0, $maxLength);
  }

  return $value;
}

function hasLgpdConsent(mixed $value): bool
{
  if (is_bool($value)) {
    return $value;
  }

  $normalized = mb_strtolower(trim((string)$value), 'UTF-8');
  return in_array($normalized, ['1', 'true', 'on', 'sim', 'yes'], true);
}

function validateCheckoutInput(string $nome, string $telefone, string $obs = ''): array
{
  $errors = [];
  $nomeTrim = sanitizeCheckoutText($nome, 80);
  $telefoneDigits = sanitizePhone($telefone);
  $obsRawTrim = trim($obs);

  if ($nomeTrim === '' || mb_strlen($nomeTrim) < 3) {
    $errors[] = 'Nome precisa ter ao menos 3 caracteres.';
  }

  if (!preg_match('/^\d{10,11}$/', $telefoneDigits)) {
    $errors[] = 'Telefone/WhatsApp invalido.';
  }

  if (mb_strlen($obsRawTrim) > 500) {
    $errors[] = 'Observacoes muito longas (max. 500 caracteres).';
  }

  return $errors;
}
