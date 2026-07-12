
USE seduc_db;

-- Drop old unique indexes that don't include disciplina
ALTER TABLE aulas_planejadas DROP INDEX `usuario_id`; -- the (usuario_id, turma, ordem) index
ALTER TABLE aulas_planejadas DROP INDEX `usuario_id_2`; -- the (usuario_id, turma, data_uso) index

-- Add new unique indexes that include disciplina
ALTER TABLE aulas_planejadas ADD UNIQUE KEY `usuario_id_turma_disciplina_ordem` (usuario_id, turma, disciplina, ordem);
ALTER TABLE aulas_planejadas ADD UNIQUE KEY `usuario_id_turma_disciplina_data_uso` (usuario_id, turma, disciplina, data_uso);

SELECT 'Migration complete!' AS message;
