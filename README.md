# SEDUC Smart - Automação de Registros Diários

Sistema de automação para preenchimento de registros diários no sistema da SEDUC RS utilizando scripts de navegador (Tampermonkey) e uma API PHP local/remota com banco de dados MySQL.

## 🚀 Funcionalidades
- **Automação Inteligente**: Preenchimento automático de registros baseado em sequências planejadas.
- **Painel Administrativo**: Interface visual para cadastrar aulas, turmas e disciplinas.
- **Multi-usuário**: Suporte para múltiplos professores com isolamento de dados por Token.
- **Flexibilidade**: Lida com feriados e imprevistos consumindo as aulas por demanda.

## 🛠️ Estrutura do Projeto
- `/backend/api/`: API PHP e Painel Admin.
- `/backend/docker-compose.yml`: Ambiente local isolado com Docker.
- `/seduc_automation.user.js`: Script Tampermonkey para instalação no navegador.
- `/init.sql`: Script de inicialização do Banco de Dados.

## 📦 Como Instalar (Local com Docker)
1. Certifique-se de ter o Docker instalado.
2. Na pasta do projeto, execute: `docker-compose up -d`.
3. Acesse o painel admin em: `http://localhost:8088/login.php`.
4. Use o login padrão: `admin@seduc.com` / `admin123`.
5. Copie seu Script Token na aba "Script Token".
6. Instale o script `seduc_automation.user.js` no seu navegador via Tampermonkey e cole o seu token no topo do script.

## ☁️ Como Instalar na Hostinger
1. Crie um Banco de Dados MySQL no painel da Hostinger.
2. Importe o arquivo `init.sql` no phpMyAdmin.
3. Suba o conteúdo da pasta `/backend/api/` para sua hospedagem.
4. Edite o arquivo `config.php` com as credenciais do banco criado.
5. Ajuste a URL no script do Tampermonkey.

---
Desenvolvido para facilitar a vida do professor! 🍎
