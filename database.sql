create schema JuegoPreguntas;
use JuegoPreguntas;
create table JuegoPreguntas.Usuario
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
    puntaje int default 0,
    trampitas int default 0,
    validado boolean default false,
    token varchar(255)
);

# Todas las contraseñas hasheadas en insert representan a 1234
    INSERT INTO Usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, validado)
VALUES
('Admin', '1990-05-12', 'Masculino', 'Argentina', 'Buenos Aires', 'admin@gmail.com',
 'Admin', '$2y$10$vlbDuqv8RNDEe84bHSaj9e00AfameGKIM4gAiieTa9f6Nw20QCjl.', true),

('Editor', '1993-09-24', 'Masculino', 'Argentina', 'Moreno', 'editor@gmail.com',
 'Editor', '$2y$10$vlbDuqv8RNDEe84bHSaj9e00AfameGKIM4gAiieTa9f6Nw20QCjl.', true),

('Usuario', '1998-01-05', 'Masculino', 'Argentina', 'San Justo', 'usuario@gmail.com',
 'Usuario', '$2y$10$vlbDuqv8RNDEe84bHSaj9e00AfameGKIM4gAiieTa9f6Nw20QCjl.', true);

