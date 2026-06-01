/* create schema aldea_vikinga;
use aldea_vikinga;
create table aldea_vikinga.guerreros
(
    id        int auto_increment
        primary key,
    nombre    varchar(50)                           not null,
    apodo     varchar(50)                           null,
    clan      varchar(50)                           null,
    fuerza    int       default 0                   null,
    creado_en timestamp default current_timestamp() not null
);

INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (23, 'Aslaug', 'La Reina', 'Volsung', 82, '2026-04-28 21:52:34');
INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (24, 'Harald', 'Cabellera Hermosa', 'Noruega', 94, '2026-04-28 21:52:34');
INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (26, 'Astrid', 'La Valiente (casi)', 'Hedeby', 87, '2026-04-28 21:52:34');

 */


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
    fotoPerfil varchar(255) default 'default.png',
    puntaje int default 0,
    trampita int default 0
);

    INSERT INTO Usuario (nombreCompleto, anioNacimiento, sexo, pais, ciudad, mail, username, password, fotoPerfil)
VALUES
('Admin', '1990-05-12', 'Masculino', 'Argentina', 'Buenos Aires', 'admin@gmail.com', 'Admin', '1234', 'default.png'),

('Editor', '1993-09-24', 'Masculino', 'Argentina', 'Moreno', 'editor@gmail.com', 'Editor', '5678', 'default.png'),

('Usuario', '1998-01-05', 'Masculino', 'Argentina', 'San Justo', 'usuario@gmail.com', 'Usuario', 'Abcd', 'default.png');

