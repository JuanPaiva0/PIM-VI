CREATE TABLE cargos (id INT PRIMARY KEY,
                     cargo VARCHAR(50));

INSERT INTO cargos (id, cargo) VALUES (1, 'SUPERVISOR');
INSERT INTO cargos (id, cargo) VALUES (2, 'ATENDENTE');
INSERT INTO cargos (id, cargo) VALUES (3, 'ESTOQUISTA');

CREATE TABLE funcionarios(id SERIAL PRIMARY KEY,
              rg VARCHAR(9) UNIQUE,
              cpf VARCHAR(11) UNIQUE,
              nome VARCHAR(100),
              endereco VARCHAR(100),
              telefone VARCHAR(11),
              email VARCHAR(100), 
              senha VARCHAR(25),
              cargo_id INT REFERENCES cargos(id));

CREATE TABLE clientes(id SERIAL PRIMARY KEY,
                      rg VARCHAR(9) UNIQUE,
                      cpf VARCHAR(11) UNIQUE,
                      nome VARCHAR(50),
                      dataRegistro DATE,
                      endereco VARCHAR(100),
                      telefone VARCHAR(11),
                      email VARCHAR(100));

CREATE TABLE fabricantes(id SERIAL PRIMARY KEY,
                         nome VARCHAR(50));

CREATE TABLE categorias(id INT PRIMARY KEY,
                        nome VARCHAR(50));

INSERT INTO categorias (id, nome) VALUES (1, 'JOGO');
INSERT INTO categorias (id, nome) VALUES (2, 'ACESSORIO');
INSERT INTO categorias (id, nome) VALUES (3, 'GEEK');

CREATE TABLE produtos(id SERIAL PRIMARY KEY,
                      nome VARCHAR(50),
                      barcode VARCHAR(13) UNIQUE,
                      categoria_id INT REFERENCES categorias(id),
                      fabricante_id INT REFERENCES fabricantes(id),
                      plataforma VARCHAR(50),
                      prazoGarantia INT,
                      estoque INT,
                      preco FLOAT);

CREATE TABLE formasPagamento(id INT PRIMARY KEY,
                             forma VARCHAR(50));

INSERT INTO formasPagamento (id, forma) VALUES (1, 'DINHEIRO');
INSERT INTO formasPagamento (id, forma) VALUES (2, 'CARTAO_CREDITO');
INSERT INTO formasPagamento (id, forma) VALUES (3, 'CARTAO_DEBITO');
INSERT INTO formasPagamento (id, forma) VALUES (4, 'PIX');

CREATE TABLE statusPagamento(id INT PRIMARY KEY,
                             status VARCHAR(50));

INSERT INTO statusPagamento (id, status) VALUES (1, 'PENDENTE');
INSERT INTO statusPagamento (id, status) VALUES (2, 'PAGO');
INSERT INTO statusPagamento (id, status) VALUES (3, 'CANCELADO');


CREATE TABLE statusVenda(id INT PRIMARY KEY,
                         status VARCHAR(50));

INSERT INTO statusVenda (id, status) VALUES (1, 'EM_ANDAMENTO');
INSERT INTO statusVenda (id, status) VALUES (2, 'FINALIZADA');
INSERT INTO statusVenda (id, status) VALUES (3, 'CANCELADA');

CREATE TABLE vendas(id SERIAL PRIMARY KEY,
                    funcionario_id INT REFERENCES funcionarios(id),
                    cliente_id INT REFERENCES clientes(id),
                    dataVenda DATE,
                    valorTotal FLOAT,
                    formaPagamento INT REFERENCES formasPagamento(id),
                    statusPagamento INT REFERENCES statusPagamento(id),
                    statusVenda INT REFERENCES statusVenda(id));

CREATE TABLE intensVenda(id SERIAL PRIMARY KEY,
                         venda_id INT REFERENCES vendas(id) ON DELETE CASCADE,
                         produto_id INT NOT NULL REFERENCES produtos(id),
                         quantidade INT CHECK (quantidade > 0),
                         subtotal FLOAT);