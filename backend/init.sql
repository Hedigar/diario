-- Define o charset para a conexão
SET NAMES 'utf8mb4';

USE seduc_db;

-- Limpa tabelas antigas para reconstruir com multi-usuário
DROP TABLE IF EXISTS aulas_planejadas;
DROP TABLE IF EXISTS turmas;
DROP TABLE IF EXISTS disciplinas;
DROP TABLE IF EXISTS usuarios;

-- Tabela de Usuários (Professores)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role ENUM('admin', 'professor') DEFAULT 'professor',
    api_token VARCHAR(64) UNIQUE, -- Token para o script Tampermonkey
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Insere o seu usuário Admin inicial (senha padrão: admin123)
-- Você deve trocar a senha assim que logar!
INSERT IGNORE INTO usuarios (nome, email, senha, role, api_token) VALUES 
('Professor Admin', 'admin@seduc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'token_mestre_123');

-- Tabela de Turmas vinculada ao usuário
CREATE TABLE IF NOT EXISTS turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, nome)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de Disciplinas vinculada ao usuário
CREATE TABLE IF NOT EXISTS disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, nome)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela para o plano de aulas vinculada ao usuário
DROP TABLE IF EXISTS aulas_planejadas;
CREATE TABLE IF NOT EXISTS aulas_planejadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    turma VARCHAR(50) NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    ordem INT NOT NULL,
    conteudo TEXT NOT NULL,
    data_uso DATE DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, turma, disciplina, ordem),
    UNIQUE KEY (usuario_id, turma, disciplina, data_uso)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Insere dados iniciais para o admin começar testando
INSERT IGNORE INTO turmas (usuario_id, nome) VALUES (1, '101'), (1, '102'), (1, '103'), (1, '104');
INSERT IGNORE INTO disciplinas (usuario_id, nome) VALUES (1, 'Matemática'), (1, 'Física');
