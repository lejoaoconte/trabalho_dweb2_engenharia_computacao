CREATE TABLE
    bibliotecario (
        id_bibliotecario VARCHAR(255) PRIMARY KEY,
        nome VARCHAR(255),
        email VARCHAR(255),
        senha VARCHAR(255)
    );

CREATE TABLE
    livro (
        id_livro VARCHAR(255) PRIMARY KEY,
        isbn VARCHAR(255),
        titulo VARCHAR(255),
        autor VARCHAR(255),
        categoria VARCHAR(255),
        status INT
    );

CREATE TABLE
    leitor (
        matricula VARCHAR(255) PRIMARY KEY,
        curso VARCHAR(255),
        nome VARCHAR(255),
        vinculo VARCHAR(255),
        dataBloqueio DATE,
        CPF VARCHAR(255)
    );

CREATE TABLE
    gerenciar_leitor (
        fk_bibliotecario VARCHAR(255),
        fk_leitor VARCHAR(255),
        FOREIGN KEY (fk_bibliotecario) REFERENCES bibliotecario (id_bibliotecario) ON DELETE RESTRICT,
        FOREIGN KEY (fk_leitor) REFERENCES leitor (matricula) ON DELETE SET NULL
    );

CREATE TABLE
    gerenciar_livro (
        fk_bibliotecario VARCHAR(255),
        fk_livro VARCHAR(255),
        FOREIGN KEY (fk_bibliotecario) REFERENCES bibliotecario (id_bibliotecario) ON DELETE RESTRICT,
        FOREIGN KEY (fk_livro) REFERENCES livro (id_livro) ON DELETE SET NULL
    );

CREATE TABLE
    reservar (
        id_reserva VARCHAR(255) PRIMARY KEY,
        fk_leitor VARCHAR(255),
        fk_livro VARCHAR(255),
        data DATE,
        FOREIGN KEY (fk_leitor) REFERENCES leitor (matricula) ON DELETE RESTRICT,
        FOREIGN KEY (fk_livro) REFERENCES livro (id_livro) ON DELETE SET NULL
    );

CREATE TABLE
    pegar_emprestado (
        id_emprestimo VARCHAR(255) PRIMARY KEY,
        fk_leitor VARCHAR(255),
        fk_livro VARCHAR(255),
        data DATE,
        data_devolucao DATE,
        data_estimada_devolucao DATE,
        FOREIGN KEY (fk_leitor) REFERENCES leitor (matricula) ON DELETE RESTRICT,
        FOREIGN KEY (fk_livro) REFERENCES livro (id_livro) ON DELETE SET NULL
    );

INSERT INTO bibliotecario (
  id_bibliotecario,
  nome,
  email,
  senha
) VALUES (
  '05359976047',            
  'Super Admin',               
  'admin@example.com',       
  MD5('123456') 
);