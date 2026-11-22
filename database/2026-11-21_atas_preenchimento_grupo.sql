-- Ata pode ser preenchida só pelo diretor ou por todos os membros do grupo
ALTER TABLE atas
  ADD COLUMN preenchimento ENUM('diretor','grupo') NOT NULL DEFAULT 'diretor' AFTER status;

-- Relatórios de ata também podem ser escritos por alunos (no modo "grupo")
ALTER TABLE relatorios_ata
  MODIFY tipo_usuario ENUM('representante','diretor','aluno') NOT NULL;
