# Sistema de Gerenciamento de Biblioteca

Este projeto é um sistema backend em PHP para gerenciamento de bibliotecas de pequeno porte. O sistema conta com funcionalidades de cadastro, autenticação, empréstimo, devolução, reserva e listagem de livros e leitores. O sistema utiliza autenticação JWT e um banco de dados MySQL.

O público alvo do sistema são bibliotecas de instituições de ensino e organizações de pequeno porte, especialmente seus responsáveis pela gestão do acervo, como bibliotecários, professores e gestores escolares.

## Funcionalidades

- *Autenticação*: Login de bibliotecários com geração de token JWT (login.php, auth.php).
- *Cadastro de Livros*: Permite registrar novos livros na base de dados (registro_livro.php).
- *Cadastro de Leitores*: Permite registrar novos leitores/alunos (registro_leitor.php).
- *Reserva de Livros*: Leitores podem reservar livros disponíveis (reserva.php).
- *Empréstimo de Livros*: Realiza o empréstimo de livros, respeitando reservas e bloqueios (emprestimo.php).
- *Devolução de Livros*: Processa a devolução de livros e aplica bloqueio ao leitor em caso de atraso (devolucao.php).
- *Listagem*:
  - Livros cadastrados (lista_livros.php)
  - Leitores cadastrados (lista_leitor.php)
  - Empréstimos realizados (lista_emprestimo.php)
  - Reservas realizadas (lista_reserva.php)

## Estrutura dos Arquivos

- config.php: Configuração de conexão com o banco de dados e definição da chave JWT.
- auth.php: Funções para codificação e decodificação de tokens JWT.
- banco.sql: Script SQL para criação das tabelas e inserção de um usuário administrador.
- login.php: Endpoint para autenticação de bibliotecários.
- registro_livro.php: Endpoint para cadastro de livros.
- registro_leitor.php: Endpoint para cadastro de leitores.
- reserva.php: Endpoint para reserva de livros.
- emprestimo.php: Endpoint para empréstimo de livros.
- devolucao.php: Endpoint para devolução de livros.
- lista_livros.php: Endpoint para listagem de livros.
- lista_leitor.php: Endpoint para listagem de leitores.
- lista_emprestimo.php: Endpoint para listagem de empréstimos.
- lista_reserva.php: Endpoint para listagem de reservas.

## Banco de Dados

O arquivo banco.sql contém a estrutura das tabelas necessárias:
- bibliotecario: Usuários do sistema.
- livro: Livros cadastrados.
- leitor: Leitores/alunos.
- reservar: Reservas de livros.
- pegar_emprestado: Empréstimos de livros.
- Tabelas auxiliares para gerenciamento de relacionamentos.

## Requisitos
- PHP 7.4+
- MySQL

## Como usar
1. Importe o arquivo banco.sql no seu MySQL.
2. Configure as credenciais do banco em config.php.
3. Utilize ferramentas como Postman para testar os endpoints (todos retornam JSON).
4. Autentique-se via login.php para obter o token JWT e utilize-o nos demais endpoints.

## Observações
- Todos os endpoints (exceto login) exigem autenticação via JWT.
- O sistema aplica bloqueio automático ao leitor em caso de devolução atrasada.
- O projeto não inclui frontend, apenas a API backend.

---
Desenvolvido para fins acadêmicos.