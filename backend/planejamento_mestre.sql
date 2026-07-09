-- SQL para o módulo de Planejamento Mestre
SET NAMES 'utf8mb4';
USE seduc_db;

-- Tabela de Planos Mestres
CREATE TABLE IF NOT EXISTS planos_mestres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_plano VARCHAR(255) NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    carga_horaria_total INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de Detalhes do Plano (Sequenciador de Aulas)
CREATE TABLE IF NOT EXISTS detalhes_plano (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plano_id INT NOT NULL,
    sequencia INT NOT NULL,
    topico VARCHAR(255) NOT NULL,
    descricao TEXT,
    atividades TEXT,
    tipo_avaliacao VARCHAR(100),
    FOREIGN KEY (plano_id) REFERENCES planos_mestres(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de Habilidades do Plano
CREATE TABLE IF NOT EXISTS habilidades_plano (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plano_id INT NOT NULL,
    codigo_habilidade VARCHAR(100) NOT NULL,
    descricao TEXT,
    FOREIGN KEY (plano_id) REFERENCES planos_mestres(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de Atribuições (Plano Mestre x Turmas)
CREATE TABLE IF NOT EXISTS atribuicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plano_id INT NOT NULL,
    turma_id INT NOT NULL,
    FOREIGN KEY (plano_id) REFERENCES planos_mestres(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    UNIQUE KEY (plano_id, turma_id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
