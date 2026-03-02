<?php

function sanitizeWhatsappNumber(string $phone): string
{
  $digits = preg_replace('/\D+/', '', $phone) ?? '';
  if ($digits === '') {
    return '';
  }

  if (!str_starts_with($digits, '55')) {
    $digits = '55' . ltrim($digits, '0');
  }

  return $digits;
}

function buildWhatsappOrderMessage(array $payload): string
{
  $clientName = trim((string)($payload['client_name'] ?? 'Cliente'));
  $clientPhone = trim((string)($payload['client_phone'] ?? 'Nao informado'));
  $notes = trim((string)($payload['notes'] ?? ''));
  $items = $payload['items'] ?? [];
  $total = (float)($payload['total'] ?? 0);
  $orderDate = trim((string)($payload['order_date'] ?? date('d/m/Y H:i')));

  $lines = [];
  $lines[] = '*NOVO PEDIDO - CONFEITARIA ROSIMAR*';
  $lines[] = 'Data: ' . $orderDate;
  $lines[] = str_repeat('-', 36);
  $lines[] = 'Cliente: ' . $clientName;
  $lines[] = 'Contato: ' . $clientPhone;
  $lines[] = 'Observacoes: ' . ($notes !== '' ? $notes : 'Nenhuma');
  $lines[] = '';
  $lines[] = 'ITENS:';

  foreach ($items as $item) {
    $qty = (string)($item['qty'] ?? '1x');
    $name = trim((string)($item['name'] ?? 'Produto'));
    $unitPrice = (float)($item['unit_price'] ?? 0);
    $lines[] = sprintf(
      '- %s %s (R$ %s)',
      $qty,
      $name,
      number_format($unitPrice, 2, ',', '.')
    );
  }

  $lines[] = str_repeat('-', 36);
  $lines[] = 'TOTAL: R$ ' . number_format($total, 2, ',', '.');
  $lines[] = '';
  $lines[] = 'Obrigada! Aguardo confirmacao para iniciar a producao.';

  return implode("\n", $lines);
}

function buildWhatsappOrderUrl(string $storePhone, string $message): string
{
  $normalizedPhone = sanitizeWhatsappNumber($storePhone);
  $encodedMessage = urlencode($message);
  return 'https://wa.me/' . $normalizedPhone . '?text=' . $encodedMessage;
}
