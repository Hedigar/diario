USE seduc_db;

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'seduc_db'
    AND TABLE_NAME = 'aulas_planejadas'
    AND COLUMN_NAME = 'slot'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE aulas_planejadas ADD COLUMN slot TINYINT NOT NULL DEFAULT 1 AFTER data_uso',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_old_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = 'seduc_db'
    AND TABLE_NAME = 'aulas_planejadas'
    AND INDEX_NAME = 'usuario_id_2'
);
SET @sql := IF(
  @idx_old_exists > 0,
  'ALTER TABLE aulas_planejadas DROP INDEX `usuario_id_2`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_legacy_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = 'seduc_db'
    AND TABLE_NAME = 'aulas_planejadas'
    AND INDEX_NAME = 'usuario_id_turma_disciplina_data_uso'
);
SET @sql := IF(
  @idx_legacy_exists > 0,
  'ALTER TABLE aulas_planejadas DROP INDEX `usuario_id_turma_disciplina_data_uso`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_slot_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = 'seduc_db'
    AND TABLE_NAME = 'aulas_planejadas'
    AND INDEX_NAME = 'usuario_id_turma_disciplina_data_uso_slot'
);
SET @sql := IF(
  @idx_slot_exists = 0,
  'ALTER TABLE aulas_planejadas ADD UNIQUE KEY `usuario_id_turma_disciplina_data_uso_slot` (usuario_id, turma, disciplina, data_uso, slot)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Migration complete!' AS message;
