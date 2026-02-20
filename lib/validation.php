<?php

function sanitizePhone(string $telefone): string
{
  return preg_replace('/\D+/', '', $telefone) ?? '';
}

function validateCheckoutInput(string $nome, string $telefone, string $obs = ''): array
{
  $errors = [];
  $nomeTrim = trim($nome);
  $telefoneDigits = sanitizePhone($telefone);

  if ($nomeTrim === '' || mb_strlen($nomeTrim) < 3) {
    $errors[] = 'Nome precisa ter ao menos 3 caracteres.';
  }

  if ($telefoneDigits === '' || strlen($telefoneDigits) < 10 || strlen($telefoneDigits) > 13) {
    $errors[] = 'Telefone/WhatsApp inválido.';
  }

  if (mb_strlen($obs) > 500) {
    $errors[] = 'Observações muito longas (máx. 500 caracteres).';
  }

  return $errors;
}
