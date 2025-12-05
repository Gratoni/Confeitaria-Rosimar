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
('Bolo de Morango', 'Pão de ló fofinho, recheio de creme e morangos frescos.', 65.00, 'assets/bolo-morango.jpg', 'kg'),
('Bolo de Chocolate', 'Massa de chocolate 50% cacau com recheio de ganache.', 70.00, 'assets/bolo-chocolate.jpg', 'kg'),
('Bolo Red Velvet', 'O clássico bolo vermelho com recheio de cream cheese.', 90.00, 'assets/bolo-redvelvet.jpg', 'kg'),
('Bolo de Cenoura', 'Cobertura de brigadeiro cremoso e massa caseira.', 45.00, 'assets/bolo-cenoura.jpg', 'un'),
('Bolo de Nozes', 'Recheio de doce de leite com nozes crocantes.', 75.00, 'assets/bolo-nozes.jpg', 'kg'),
('Bolo de Limão', 'Massa leve de limão com cobertura de mousse.', 55.00, 'assets/bolo-limao.jpg', 'un');
