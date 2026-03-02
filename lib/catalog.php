<?php

const CATALOG_PLACEHOLDER_IMAGE = 'assets/placeholder.jpg';

/**
 * Catalogo base com bolos caseiros e artesanais.
 */
function getDefaultCatalogProducts(): array
{
  return [
    [
      'nome' => 'Bolo de Chocolate',
      'descricao' => 'Massa 50% cacau com recheio cremoso de brigadeiro.',
      'preco' => 75.00,
      'imagem' => 'assets/bolos/chocolate.jpg',
      'unidade' => 'kg',
    ],
    [
      'nome' => 'Bolo de Morango',
      'descricao' => 'Pao de lo leve com creme branco e morangos frescos.',
      'preco' => 78.00,
      'imagem' => 'assets/bolos/morango.jpg',
      'unidade' => 'kg',
    ],
    [
      'nome' => 'Bolo de Cenoura Caseiro',
      'descricao' => 'Massa fofinha de cenoura com cobertura de brigadeiro.',
      'preco' => 48.00,
      'imagem' => 'assets/bolos/cenoura.jpg',
      'unidade' => 'un',
    ],
    [
      'nome' => 'Bolo de Banana com Canela',
      'descricao' => 'Receita caseira umida com banana madura e canela.',
      'preco' => 44.00,
      'imagem' => 'assets/bolos/banana.jpg',
      'unidade' => 'un',
    ],
    [
      'nome' => 'Bolo de Fuba com Erva-Doce',
      'descricao' => 'Tradicional da tarde, com textura macia e sabor de casa.',
      'preco' => 42.00,
      'imagem' => 'assets/bolos/fuba.jpg',
      'unidade' => 'un',
    ],
    [
      'nome' => 'Bolo de Milho Cremoso',
      'descricao' => 'Receita cremosa, feita com milho e toque de coco.',
      'preco' => 46.00,
      'imagem' => 'assets/bolos/milho.jpg',
      'unidade' => 'un',
    ],
    [
      'nome' => 'Bolo de Limao',
      'descricao' => 'Massa leve com cobertura citrica e finalizacao delicada.',
      'preco' => 45.00,
      'imagem' => 'assets/bolos/limao.jpg',
      'unidade' => 'un',
    ],
    [
      'nome' => 'Bolo Ninho com Morango',
      'descricao' => 'Recheio de leite ninho e camada generosa de morangos.',
      'preco' => 82.00,
      'imagem' => 'assets/bolos/ninho.jpg',
      'unidade' => 'kg',
    ],
    [
      'nome' => 'Bolo de Cenoura Vulcao',
      'descricao' => 'Versao caseira com cobertura abundante e cremosa.',
      'preco' => 52.00,
      'imagem' => 'assets/bolos/cenoura.jpg',
      'unidade' => 'un',
    ],
    [
      'nome' => 'Bolo de Banana com Castanhas',
      'descricao' => 'Massa de banana com castanhas crocantes e toque de mel.',
      'preco' => 47.00,
      'imagem' => 'assets/bolos/banana.jpg',
      'unidade' => 'un',
    ],
    [
      'nome' => 'Bolo de Chocolate com Ninho',
      'descricao' => 'Camadas de chocolate e creme de ninho, ideal para festa.',
      'preco' => 85.00,
      'imagem' => 'assets/bolos/chocolate.jpg',
      'unidade' => 'kg',
    ],
    [
      'nome' => 'Bolo de Fuba Cremoso',
      'descricao' => 'Fuba com cobertura de coco, classico para cafe da tarde.',
      'preco' => 43.00,
      'imagem' => 'assets/bolos/fuba.jpg',
      'unidade' => 'un',
    ],
  ];
}

function normalizeCatalogKey(string $value): string
{
  $normalized = mb_strtolower(trim($value), 'UTF-8');
  if (function_exists('iconv')) {
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
    if ($ascii !== false) {
      $normalized = $ascii;
    }
  }
  return preg_replace('/[^a-z0-9]+/', '', $normalized) ?? '';
}

function inferProductCategory(string $name, string $unit): string
{
  $unit = $unit === 'kg' ? 'kg' : 'un';
  $normalized = normalizeCatalogKey($name);

  if ($unit === 'kg') {
    return 'festa';
  }

  if (str_contains($normalized, 'caseiro') || str_contains($normalized, 'fuba') || str_contains($normalized, 'banana') || str_contains($normalized, 'milho')) {
    return 'caseiro';
  }

  return 'tradicional';
}

function inferCatalogImageByName(string $name): string
{
  $normalized = normalizeCatalogKey($name);

  $map = [
    'chocolate' => 'assets/bolos/chocolate.jpg',
    'cenoura' => 'assets/bolos/cenoura.jpg',
    'banana' => 'assets/bolos/banana.jpg',
    'fuba' => 'assets/bolos/fuba.jpg',
    'limao' => 'assets/bolos/limao.jpg',
    'milho' => 'assets/bolos/milho.jpg',
    'ninho' => 'assets/bolos/ninho.jpg',
    'morango' => 'assets/bolos/morango.jpg',
  ];

  foreach ($map as $keyword => $path) {
    if (str_contains($normalized, $keyword)) {
      return $path;
    }
  }

  return CATALOG_PLACEHOLDER_IMAGE;
}

function resolveCatalogImagePath(?string $image, string $name = ''): string
{
  $candidate = trim((string)$image);
  if ($candidate !== '') {
    $candidate = str_replace('\\', '/', $candidate);
    $candidate = ltrim($candidate, '/');
    $isAllowed = preg_match('/^[a-zA-Z0-9_\/\.\-]+$/', $candidate) === 1 && !str_contains($candidate, '..');
    if ($isAllowed && str_starts_with($candidate, 'assets/')) {
      $absolute = __DIR__ . '/../' . $candidate;
      if (is_file($absolute)) {
        return $candidate;
      }
    }
  }

  $suggested = inferCatalogImageByName($name);
  $absoluteSuggested = __DIR__ . '/../' . $suggested;
  if (is_file($absoluteSuggested)) {
    return $suggested;
  }

  return CATALOG_PLACEHOLDER_IMAGE;
}

function ensureDefaultCatalogProducts(mysqli $conn): void
{
  if (!tableExists($conn, 'produtos')) {
    return;
  }

  $existingByKey = [];
  $result = $conn->query("SELECT id, nome, imagem, unidade FROM produtos");
  while ($row = $result->fetch_assoc()) {
    $key = normalizeCatalogKey((string)$row['nome']);
    if ($key === '') {
      continue;
    }

    $existingByKey[$key] = $row;

    $currentImage = (string)($row['imagem'] ?? '');
    $resolvedImage = resolveCatalogImagePath($currentImage, (string)$row['nome']);
    $resolvedUnit = (($row['unidade'] ?? 'un') === 'kg') ? 'kg' : 'un';
    if ($resolvedImage !== $currentImage || $resolvedUnit !== ($row['unidade'] ?? '')) {
      $update = $conn->prepare("UPDATE produtos SET imagem = ?, unidade = ? WHERE id = ?");
      $id = (int)$row['id'];
      $update->bind_param('ssi', $resolvedImage, $resolvedUnit, $id);
      $update->execute();
      $update->close();
    }
  }
  $result->free();

  $insert = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, imagem, unidade) VALUES (?, ?, ?, ?, ?)");

  foreach (getDefaultCatalogProducts() as $product) {
    $key = normalizeCatalogKey($product['nome']);
    if (isset($existingByKey[$key])) {
      continue;
    }

    $nome = $product['nome'];
    $descricao = $product['descricao'];
    $preco = (float)$product['preco'];
    $imagem = resolveCatalogImagePath($product['imagem'], $nome);
    $unidade = $product['unidade'] === 'kg' ? 'kg' : 'un';

    $insert->bind_param('ssdss', $nome, $descricao, $preco, $imagem, $unidade);
    $insert->execute();
  }
  $insert->close();
}

function fetchCatalogProducts(mysqli $conn): array
{
  $stmt = $conn->prepare("SELECT id, nome, descricao, preco, imagem, unidade FROM produtos ORDER BY id ASC");
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  foreach ($rows as &$row) {
    $row['unidade'] = (($row['unidade'] ?? 'un') === 'kg') ? 'kg' : 'un';
    $row['imagem'] = resolveCatalogImagePath($row['imagem'] ?? '', (string)($row['nome'] ?? ''));
    $row['categoria'] = inferProductCategory((string)($row['nome'] ?? ''), $row['unidade']);
  }
  unset($row);

  return $rows;
}

function getCatalogProductById(mysqli $conn, int $id): ?array
{
  $stmt = $conn->prepare("SELECT id, nome, preco, unidade, descricao, imagem FROM produtos WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$row) {
    return null;
  }

  $row['unidade'] = (($row['unidade'] ?? 'un') === 'kg') ? 'kg' : 'un';
  $row['imagem'] = resolveCatalogImagePath($row['imagem'] ?? '', (string)($row['nome'] ?? ''));
  $row['categoria'] = inferProductCategory((string)($row['nome'] ?? ''), $row['unidade']);
  return $row;
}
