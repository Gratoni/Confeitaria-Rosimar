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

function applySecurityHeaders(): void
{
  if (headers_sent()) {
    return;
  }

  header('X-Frame-Options: SAMEORIGIN');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

  if (isHttpsRequest()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  }

  $csp = implode(' ', [
    "default-src 'self';",
    "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com;",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;",
    "img-src 'self' data: https://www.googletagmanager.com https://www.google-analytics.com;",
    "font-src 'self' https://fonts.gstatic.com data:;",
    "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com;",
    "frame-src https://www.googletagmanager.com;",
    "base-uri 'self'; form-action 'self' https://wa.me;",
  ]);
  header('Content-Security-Policy: ' . $csp);
}
