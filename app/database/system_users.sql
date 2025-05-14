CREATE TABLE system_users(
  id serial PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  login VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(50) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  cargo INT REFERENCES cargos(id),
  funcionario_id INT REFERENCES funcionarios(id),
  status BOOLEAN DEFAULT TRUE
)