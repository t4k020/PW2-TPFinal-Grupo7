-- DROP DATABASE IF EXISTS JuegoPreguntas;
create schema JuegoPreguntas;
use JuegoPreguntas;

CREATE TABLE JuegoPreguntas.Usuario
(
    id int auto_increment primary key,
    nombreCompleto varchar(255) not null,
    anioNacimiento date not null,
    sexo ENUM('Masculino', 'Femenino', 'Prefiero no decirlo') not null,
    pais varchar(100) not null,
    ciudad varchar(100) not null,
    mail varchar(255) not null unique,
    username varchar(50) not null unique,
    password varchar(255) not null,
    fotoPerfil varchar(255) default 'default-user.png',
    rol varchar(20) default 'JUGADOR', -- resuelve rol usuario
    puntaje int default 0,
    trampitas int default 0,
    validado boolean default false,
    token varchar(255),
    maestria VARCHAR(20) DEFAULT 'Aprendiz' -- maestriaUsuario
);

# Todas las contraseñas hasheadas en insert representan a 1234
INSERT INTO Usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, rol, validado)
VALUES
    ('Admin', '1990-05-12', 'Masculino', 'Argentina', 'Buenos Aires', 'admin@gmail.com', 'Admin', '$2y$10$vlbDuqv8RNDEe84bHSaj9e00AfameGKIM4gAiieTa9f6Nw20QCjl.', 'ADMIN', true),
    ('Editor', '1993-09-24', 'Masculino', 'Argentina', 'Moreno', 'editor@gmail.com', 'Editor', '$2y$10$vlbDuqv8RNDEe84bHSaj9e00AfameGKIM4gAiieTa9f6Nw20QCjl.', 'EDITOR', true),
    ('Usuario', '1998-01-05', 'Masculino', 'Argentina', 'San Justo', 'usuario@gmail.com', 'Usuario', '$2y$10$vlbDuqv8RNDEe84bHSaj9e00AfameGKIM4gAiieTa9f6Nw20QCjl.', 'JUGADOR', true);

CREATE TABLE IF NOT EXISTS Categoria
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Pregunta
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(255) NOT NULL,
    categoria_id INT NOT NULL,
    dificultad VARCHAR(20) NOT NULL DEFAULT 'FACIL',
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    FOREIGN KEY (categoria_id) REFERENCES Categoria(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE Pregunta
    ADD COLUMN Reportado ENUM('no reportado', 'Pregunta mal escrita', 'Respuesta equivocada')
NOT NULL
DEFAULT 'no reportado';

CREATE TABLE IF NOT EXISTS Respuesta
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    texto VARCHAR(255) NOT NULL,
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (pregunta_id) REFERENCES Pregunta(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert Categoria
INSERT INTO Categoria (nombre, color) VALUES
('Historia', '#DEC100'),
('Geografía', '#2196F3'),
('Ciencia', '#4CAF50');

-- Insert preguntas, se le agrego a todas las preguntas estado y dificultad
-- Insert Pregunta 1 (Historia)
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado) VALUES
    ('¿En qué año llegó Cristóbal Colón a América?', 1, 'FACIL', 'APROBADA');

-- Insert sus 4 opciones (La correcta es 1492)
INSERT INTO Respuesta (pregunta_id, texto, es_correcta) VALUES
(1, '1492', 1),
(1, '1789', 0),
(1, '1500', 0),
(1, '1453', 0);

-- Insert Pregunta 2 (Geografía)
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado) VALUES
    ('¿Cuál es el río más largo del mundo?', 2, 'MEDIO', 'APROBADA');

-- Insert sus 4 opciones (La correcta es el Amazonas)
INSERT INTO Respuesta (pregunta_id, texto, es_correcta) VALUES
(2, 'Río Nilo', 0),
(2, 'Río Amazonas', 1),
(2, 'Río Misisipi', 0),
(2, 'Río Paraná', 0);

-- Insert Pregunta 3
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado)
VALUES ('¿Quién fue el primer emperador de los romanos?', 1, 'MEDIO', 'APROBADA');

INSERT INTO Respuesta (texto, es_correcta, pregunta_id)
VALUES ('Julio César', 0, 3),
       ('Augusto (Octavio)', 1, 3),
       ('Nerón', 0, 3),
       ('Calígula', 0, 3);

-- Insert Pregunta 4
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado)
VALUES ('¿En qué año se declaró la Independencia de la Argentina?', 1, 'FACIL', 'APROBADA');

INSERT INTO Respuesta (texto, es_correcta, pregunta_id)
VALUES ('1810', 0, 4),
       ('1812', 0, 4),
       ('1816', 1, 4),
       ('1820', 0, 4);

-- Insert Pregunta 5 (Río Nilo - Variante)
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado)
VALUES ('¿Cuál es el río más largo de África?', 2, 'MEDIO', 'APROBADA');

INSERT INTO Respuesta (texto, es_correcta, pregunta_id)
VALUES ('Río Nilo', 1, 5),
       ('Río Amazonas', 0, 5),
       ('Río Misisipi', 0, 5),
       ('Río Yangtsé', 0, 5);

-- Insert Pregunta 6
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado)
VALUES ('¿Cuál es la capital de Australia?', 2, 'MEDIO', 'APROBADA');

INSERT INTO Respuesta (texto, es_correcta, pregunta_id)
VALUES ('Sídney', 0, 6),
       ('Melbourne', 0, 6),
       ('Canberra', 1, 6),
       ('Brisbane', 0, 6);

-- Insert Pregunta 7
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado)
VALUES ('¿Cuál es el elemento más abundante en el universo?', 3, 'MEDIO', 'APROBADA');

INSERT INTO Respuesta (texto, es_correcta, pregunta_id)
VALUES ('Oxígeno', 0, 7),
       ('Helio', 0, 7),
       ('Hidrógeno', 1, 7),
       ('Carbono', 0, 7);

-- Insert Pregunta 8
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado)
VALUES ('¿Qué tipo de carga eléctrica tiene un neutrón?', 3, 'MEDIO', 'APROBADA');

INSERT INTO Respuesta (texto, es_correcta, pregunta_id)
VALUES ('Positiva', 0, 8),
       ('Negativa', 0, 8),
       ('No tiene carga (es neutra)', 1, 8),
       ('Varía según el átomo', 0, 8);

-- Insert Pregunta 9
INSERT INTO Pregunta (texto, categoria_id, dificultad, estado)
VALUES ('¿Cuál es el planeta más grande de nuestro sistema solar?', 3, 'FACIL', 'APROBADA');

INSERT INTO Respuesta (texto, es_correcta, pregunta_id)
VALUES ('Saturno', 0, 9),
       ('Júpiter', 1, 9),
       ('Neptuno', 0, 9),
       ('La Tierra', 0, 9);

CREATE TABLE Partida (
id INT AUTO_INCREMENT PRIMARY KEY,
usuario_id INT NOT NULL,
puntaje INT NOT NULL,
fecha_partida DATETIME NOT NULL,
FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
);