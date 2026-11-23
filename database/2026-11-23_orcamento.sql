-- database/2026-11-23_orcamento.sql
-- Orçamento dos projetos (itens previstos) e prestação de contas: lançamentos do caixa ligados ao projeto;
-- no grêmio, cada projeto pode ter uma diretoria responsável.
SET SQL_SAFE_UPDATES = 0;
ALTER TABLE projetos ADD COLUMN diretoria_id INT NULL AFTER turma_id,
  ADD CONSTRAINT fk_projeto_diretoria FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE SET NULL;
ALTER TABLE caixa_lancamentos ADD COLUMN projeto_id INT NULL AFTER diretoria_id,
  ADD CONSTRAINT fk_caixa_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL;
CREATE TABLE IF NOT EXISTS projeto_orcamento_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  descricao VARCHAR(150) NOT NULL,
  valor_previsto DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SET SQL_SAFE_UPDATES = 1;
