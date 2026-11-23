SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;
-- Agenda interativa: eventos da turma (representante) e pessoais (cada um), com prioridade, conclusão e lembrete.
-- Dia a dia (agenda, horário, aviso do dia, contagem) incluso do Anual para cima; Trimestral e Semestral compram como adicional.

ALTER TABLE eventos_calendario
  MODIFY turma_id INT NULL,
  ADD COLUMN visibilidade ENUM('turma','pessoal') NOT NULL DEFAULT 'turma' AFTER turma_id,
  MODIFY tipo ENUM('entrega','prova','tarefa','estudo','evento','reuniao','feriado','outro') NOT NULL DEFAULT 'evento',
  ADD COLUMN prioridade ENUM('baixa','media','alta','urgente') NOT NULL DEFAULT 'media' AFTER tipo,
  ADD COLUMN lembrete TINYINT(1) NOT NULL DEFAULT 1 AFTER local,
  ADD KEY idx_evento_criador (criado_por, data);

CREATE TABLE IF NOT EXISTS evento_conclusoes (
  evento_id INT NOT NULL,
  usuario_id INT NOT NULL,
  concluido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (evento_id, usuario_id),
  FOREIGN KEY (evento_id) REFERENCES eventos_calendario(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE planos
  ADD COLUMN inclui_dia_a_dia TINYINT(1) NOT NULL DEFAULT 1 AFTER tabela_parcelas,
  ADD COLUMN preco_dia_a_dia DECIMAL(10,2) DEFAULT NULL AFTER inclui_dia_a_dia;
UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 39.90 WHERE codigo = 'trimestral';
UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 59.90 WHERE codigo = 'semestral';
UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 27.90 WHERE codigo = 'g_trimestral';
UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 41.90 WHERE codigo = 'g_semestral';

ALTER TABLE assinaturas ADD COLUMN dia_a_dia TINYINT(1) NOT NULL DEFAULT 0 AFTER parcelas;
ALTER TABLE pagamentos ADD COLUMN tipo ENUM('parcela','adicional') NOT NULL DEFAULT 'parcela' AFTER numero;
