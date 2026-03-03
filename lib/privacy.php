<?php

function getDataProtectionKey(): ?string
{
  static $cached = false;
  static $key = null;

  if ($cached) {
    return $key;
  }

  $cached = true;
  $raw = getenv('APP_DATA_KEY');
  if (!is_string($raw) || trim($raw) === '') {
    return null;
  }

  $raw = trim($raw);
  $decoded = null;

  if (str_starts_with($raw, 'base64:')) {
    $decoded = base64_decode(substr($raw, 7), true);
  } elseif (str_starts_with($raw, 'hex:')) {
    $decoded = hex2bin(substr($raw, 4));
  } else {
    if (preg_match('/^[A-Fa-f0-9]{64}$/', $raw) === 1) {
      $decoded = hex2bin($raw);
    } else {
      $decodedBase64 = base64_decode($raw, true);
      if (is_string($decodedBase64) && $decodedBase64 !== '') {
        $decoded = $decodedBase64;
      } else {
        $decoded = $raw;
      }
    }
  }

  if (!is_string($decoded) || strlen($decoded) !== 32) {
    return null;
  }

  $key = $decoded;
  return $key;
}

function isDataProtectionEnabled(): bool
{
  return is_string(getDataProtectionKey());
}

function encryptSensitiveData(string $value): string
{
  if ($value === '') {
    return '';
  }

  $key = getDataProtectionKey();
  if (!is_string($key)) {
    throw new RuntimeException('APP_DATA_KEY ausente ou invalida.');
  }

  if (function_exists('sodium_crypto_secretbox')) {
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = sodium_crypto_secretbox($value, $nonce, $key);
    return 'sb1:' . base64_encode($nonce . $cipher);
  }

  if (function_exists('openssl_encrypt')) {
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if (!is_string($cipher) || !is_string($tag) || $tag === '') {
      throw new RuntimeException('Falha ao cifrar dados sensiveis.');
    }

    return 'g1:' . base64_encode($iv . $tag . $cipher);
  }

  throw new RuntimeException('Nenhuma extensao de criptografia disponivel (sodium/openssl).');
}

function decryptSensitiveData(string $value): string
{
  if ($value === '' || !str_contains($value, ':')) {
    return $value;
  }

  $key = getDataProtectionKey();
  if (!is_string($key)) {
    return $value;
  }

  [$version, $payload] = explode(':', $value, 2);
  $decoded = base64_decode($payload, true);
  if (!is_string($decoded) || $decoded === '') {
    return $value;
  }

  if ($version === 'sb1' && function_exists('sodium_crypto_secretbox_open')) {
    $nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
    if (strlen($decoded) <= $nonceSize) {
      return $value;
    }

    $nonce = substr($decoded, 0, $nonceSize);
    $cipher = substr($decoded, $nonceSize);
    $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
    return is_string($plain) ? $plain : $value;
  }

  if ($version === 'g1' && function_exists('openssl_decrypt')) {
    if (strlen($decoded) <= 28) {
      return $value;
    }

    $iv = substr($decoded, 0, 12);
    $tag = substr($decoded, 12, 16);
    $cipher = substr($decoded, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return is_string($plain) ? $plain : $value;
  }

  return $value;
}

function anonymizeIpAddress(string $ip): string
{
  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $parts = explode('.', $ip);
    if (count($parts) === 4) {
      $parts[3] = '0';
      return implode('.', $parts);
    }
  }

  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $parts = explode(':', $ip);
    $parts = array_slice($parts, 0, 4);
    return implode(':', $parts) . '::';
  }

  return '';
}

