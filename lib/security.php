<?php

function isHttpsRequest(): bool
{
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    return true;
  }

  if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
    return true;
  }

  return false;
}

function getCspNonce(): string
{
  static $nonce = null;
  if (is_string($nonce) && $nonce !== '') {
    return $nonce;
  }

  $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
  return $nonce;
}

function applySecurityHeaders(): void
{
  if (headers_sent()) {
    return;
  }

  header('X-Frame-Options: SAMEORIGIN');
  header('X-Content-Type-Options: nosniff');
  header('X-Permitted-Cross-Domain-Policies: none');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Cross-Origin-Opener-Policy: same-origin');
  header('Cross-Origin-Resource-Policy: same-origin');
  header('Origin-Agent-Cluster: ?1');
  header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

  if (isHttpsRequest()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  }

  $nonce = getCspNonce();
  $csp = implode(' ', [
    "default-src 'self';",
    "script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com https://www.google-analytics.com;",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;",
    "img-src 'self' data: https://www.googletagmanager.com https://www.google-analytics.com;",
    "font-src 'self' https://fonts.gstatic.com data:;",
    "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com;",
    "frame-src https://www.googletagmanager.com;",
    "frame-ancestors 'self';",
    "object-src 'none';",
    "base-uri 'self'; form-action 'self' https://wa.me;",
  ]);
  header('Content-Security-Policy: ' . $csp);
}
