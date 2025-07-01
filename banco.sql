-- Script de criação do banco de dados para o sistema de biblioteca

-- Tabela de bibliotecários: armazena os usuários administradores do sistema
CREATE TABLE
    bibliotecario (
        id_bibliotecario VARCHAR(255) PRIMARY KEY, -- Identificador único do bibliotecário
        nome VARCHAR(255),                        -- Nome do bibliotecário
        email VARCHAR(255),                       -- E-mail para login
        senha VARCHAR(255)                        -- Senha (armazenada como hash MD5)
    );

-- Tabela de livros: armazena os livros cadastrados na biblioteca
CREATE TABLE
    livro (
        id_livro VARCHAR(255) PRIMARY KEY,        -- Identificador único do livro
        isbn VARCHAR(255),                        -- Código ISBN do livro
        titulo VARCHAR(255),                      -- Título do livro
        autor VARCHAR(255),                       -- Autor do livro
        categoria VARCHAR(255),                   -- Categoria/assunto
        status INT                                -- Status (ex: disponível, emprestado)
    );

-- Tabela de leitores: armazena os usuários que podem pegar livros emprestados
CREATE TABLE
    leitor (
        matricula VARCHAR(255) PRIMARY KEY,       -- Identificador único do leitor (matrícula)
        curso VARCHAR(255),                       -- Curso do leitor
        nome VARCHAR(255),                        -- Nome do leitor
        vinculo VARCHAR(255),                     -- Vínculo institucional
        dataBloqueio DATE,                        -- Data até a qual o leitor está bloqueado
        CPF VARCHAR(255)                          -- CPF do leitor
    );

-- Tabela de relacionamento entre bibliotecário e leitor (gestão de leitores)
CREATE TABLE
    gerenciar_leitor (
        fk_bibliotecario VARCHAR(255),            -- Chave estrangeira para bibliotecário
        fk_leitor VARCHAR(255),                   -- Chave estrangeira para leitor
        FOREIGN KEY (fk_bibliotecario) REFERENCES bibliotecario (id_bibliotecario) ON DELETE RESTRICT,
        FOREIGN KEY (fk_leitor) REFERENCES leitor (matricula) ON DELETE SET NULL
    );

-- Tabela de relacionamento entre bibliotecário e livro (gestão de livros)
CREATE TABLE
    gerenciar_livro (
        fk_bibliotecario VARCHAR(255),            -- Chave estrangeira para bibliotecário
        fk_livro VARCHAR(255),                    -- Chave estrangeira para livro
        FOREIGN KEY (fk_bibliotecario) REFERENCES bibliotecario (id_bibliotecario) ON DELETE RESTRICT,
        FOREIGN KEY (fk_livro) REFERENCES livro (id_livro) ON DELETE SET NULL
    );

-- Tabela de reservas de livros
CREATE TABLE
    reservar (
        id_reserva VARCHAR(255) PRIMARY KEY,      -- Identificador único da reserva
        fk_leitor VARCHAR(255),                   -- Leitor que fez a reserva
        fk_livro VARCHAR(255),                    -- Livro reservado
        data DATE,                                -- Data da reserva
        FOREIGN KEY (fk_leitor) REFERENCES leitor (matricula) ON DELETE RESTRICT,
        FOREIGN KEY (fk_livro) REFERENCES livro (id_livro) ON DELETE SET NULL
    );

-- Tabela de empréstimos de livros
CREATE TABLE
    pegar_emprestado (
        id_emprestimo VARCHAR(255) PRIMARY KEY,   -- Identificador único do empréstimo
        fk_leitor VARCHAR(255),                   -- Leitor que pegou o livro
        fk_livro VARCHAR(255),                    -- Livro emprestado
        data DATE,                                -- Data do empréstimo
        data_devolucao DATE,                      -- Data da devolução (pode ser nula)
        data_estimada_devolucao DATE,             -- Data prevista para devolução
        FOREIGN KEY (fk_leitor) REFERENCES leitor (matricula) ON DELETE RESTRICT,
        FOREIGN KEY (fk_livro) REFERENCES livro (id_livro) ON DELETE SET NULL
    );

-- Insere um bibliotecário padrão para acesso inicial ao sistema
INSERT INTO bibliotecario (
  id_bibliotecario,
  nome,
  email,
  senha
) VALUES (
  '05359976047',            -- CPF fictício
  'Super Admin',            -- Nome do administrador
  'admin@example.com',      -- E-mail de acesso
  MD5('123456')             -- Senha padrão (criptografada com MD5)
);