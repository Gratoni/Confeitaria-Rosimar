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
('Bolo de Chocolate', 'Massa 50% cacau com recheio cremoso de brigadeiro.', 75.00, 'assets/bolos/chocolate.jpg', 'kg'),
('Bolo de Morango', 'Pao de lo leve com creme branco e morangos frescos.', 78.00, 'assets/bolos/morango.jpg', 'kg'),
('Bolo de Cenoura Caseiro', 'Massa fofinha de cenoura com cobertura de brigadeiro.', 48.00, 'assets/bolos/cenoura.jpg', 'un'),
('Bolo de Banana com Canela', 'Receita caseira umida com banana madura e canela.', 44.00, 'assets/bolos/banana.jpg', 'un'),
('Bolo de Fuba com Erva-Doce', 'Tradicional da tarde, com textura macia e sabor de casa.', 42.00, 'assets/bolos/fuba.jpg', 'un'),
('Bolo de Milho Cremoso', 'Receita cremosa, feita com milho e toque de coco.', 46.00, 'assets/bolos/milho.jpg', 'un'),
('Bolo de Limao', 'Massa leve com cobertura citrica e finalizacao delicada.', 45.00, 'assets/bolos/limao.jpg', 'un'),
('Bolo Ninho com Morango', 'Recheio de leite ninho e camada generosa de morangos.', 82.00, 'assets/bolos/ninho.jpg', 'kg'),
('Bolo de Cenoura Vulcao', 'Versao caseira com cobertura abundante e cremosa.', 52.00, 'assets/bolos/cenoura.jpg', 'un'),
('Bolo de Banana com Castanhas', 'Massa de banana com castanhas crocantes e toque de mel.', 47.00, 'assets/bolos/banana.jpg', 'un'),
('Bolo de Chocolate com Ninho', 'Camadas de chocolate e creme de ninho, ideal para festa.', 85.00, 'assets/bolos/chocolate.jpg', 'kg'),
('Bolo de Fuba Cremoso', 'Fuba com cobertura de coco, classico para cafe da tarde.', 43.00, 'assets/bolos/fuba.jpg', 'un');
