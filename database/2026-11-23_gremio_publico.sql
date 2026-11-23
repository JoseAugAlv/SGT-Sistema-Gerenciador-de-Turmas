-- database/2026-11-23_gremio_publico.sql
-- Página pública do grêmio (sem login), em /g/{slug}. O presidente ativa e escolhe o que aparece.
CREATE TABLE IF NOT EXISTS gremio_paginas (
  turma_id INT PRIMARY KEY,
  slug VARCHAR(60) NOT NULL,
  ativa TINYINT(1) NOT NULL DEFAULT 0,
  apresentacao TEXT NULL,
  instagram VARCHAR(60) NULL,
  mostrar_diretorias TINYINT(1) NOT NULL DEFAULT 1,
  mostrar_eventos TINYINT(1) NOT NULL DEFAULT 1,
  mostrar_conquistas TINYINT(1) NOT NULL DEFAULT 1,
  mostrar_contas TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_gremio_slug (slug),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
