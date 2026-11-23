SET NAMES utf8mb4;
-- Dados de cobrança de quem compra planos (CPF e endereço). Um registro por usuário, reaproveitado nas próximas compras.
CREATE TABLE IF NOT EXISTS dados_cobranca (
  usuario_id INT PRIMARY KEY,
  nome_completo VARCHAR(120) NOT NULL,
  cpf CHAR(11) NOT NULL,
  cep CHAR(8) NOT NULL,
  logradouro VARCHAR(150) NOT NULL,
  numero VARCHAR(20) NOT NULL,
  complemento VARCHAR(80) DEFAULT NULL,
  bairro VARCHAR(80) NOT NULL,
  cidade VARCHAR(80) NOT NULL,
  uf CHAR(2) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
