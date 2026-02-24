CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL,
    imagem VARCHAR(255),
    unidade VARCHAR(10) DEFAULT 'un'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(255) NOT NULL,
    telefone VARCHAR(50) NOT NULL,
    observacoes TEXT,
    data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade DECIMAL(10, 3) NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO produtos (nome, descricao, preco, imagem, unidade) VALUES
('Bolo de Chocolate Trufado', 'Massa de chocolate 50% cacau, recheio cremoso e cobertura de ganache.', 78.00, 'assets/bolos/chocolate.jpg', 'kg'),
('Bolo de Morango com Nata', 'Pão de ló leve, recheio de nata e morangos frescos selecionados.', 82.00, 'assets/bolos/morango.jpg', 'kg'),
('Bolo de Ninho', 'Massa branca amanteigada com recheio de leite ninho e toque de baunilha.', 76.00, 'assets/bolos/ninho.jpg', 'kg'),
('Bolo de Limão', 'Cobertura cítrica e massa úmida para um sabor equilibrado e refrescante.', 48.00, 'assets/bolos/limao.jpg', 'un'),
('Bolo de Cenoura com Brigadeiro', 'Clássico caseiro com cobertura de brigadeiro cremoso.', 45.00, 'assets/bolos/cenoura.jpg', 'un'),
('Bolo de Fubá Caseiro', 'Receita tradicional de interior, macia e perfeita para café da tarde.', 36.00, 'assets/bolos/fuba.jpg', 'un'),
('Bolo de Milho Cremoso', 'Massa de milho verde com textura cremosa e sabor de fazenda.', 39.00, 'assets/bolos/milho.jpg', 'un'),
('Bolo de Banana com Canela', 'Banana caramelizada, especiarias e massa extremamente fofinha.', 42.00, 'assets/bolos/banana.jpg', 'un');
