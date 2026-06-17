<?php

//class MyDatabase
//{
//    private $conexion;
//
//    public function __construct($hostname, $username, $password, $database)
//    {
//        $this->conexion = new mysqli($hostname, $username, $password, $database);
//    }
//
//    public function query($sql, $params = [])
//    {
//        $stmt = $this->conexion->prepare($sql);
//        $stmt->execute($params);
//        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
//    }
//
//    public function execute($sql, $params = [])
//    {
//        $stmt = $this->conexion->prepare($sql);
//        $stmt->execute($params);
//        return $this->conexion->affected_rows;
//    }
//
//    public function __destruct()
//    {
//        $this->conexion->close();
//    }
//}



class MyDatabase
{
    private $conexion;

    public function __construct($hostname, $username, $password, $database)
    {
        $this->conexion = new mysqli($hostname, $username, $password, $database);
        $this->conexion->set_charset("utf8mb4");

        // Excelente práctica: si la conexión falla, frena acá con un mensaje claro
        if ($this->conexion->connect_error) {
            die("Error de conexión: " . $this->conexion->connect_error);
        }
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            die("Error en la consulta (prepare): " . $this->conexion->error);
        }

        // Si el array de parámetros tiene datos, los vinculamos dinámicamente
        if (!empty($params)) {
            $types = "";
            foreach ($params as $param) {
                if (is_int($param)) $types .= "i";
                elseif (is_double($param)) $types .= "d";
                else $types .= "s";
            }
            // El operador ... (splat) desempaqueta el array en argumentos individuales
            $stmt->bind_param($types, ...$params);
        }

        // MySQLi execute NUNCA lleva argumentos adentro de los paréntesis
        $stmt->execute();

        $result = $stmt->get_result();

        // Si la consulta fue un SELECT exitoso, devuelve los datos
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        return [];
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            die("Error en la ejecución (prepare): " . $this->conexion->error);
        }

        // Repetimos la lógica dinámica para INSERT, UPDATE o DELETE
        if (!empty($params)) {
            $types = "";
            foreach ($params as $param) {
                if (is_int($param)) $types .= "i";
                elseif (is_double($param)) $types .= "d";
                else $types .= "s";
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $this->conexion->affected_rows;
    }

    public function __destruct()
    {
        // Solo cerramos si realmente hay una conexión activa y válida
        if ($this->conexion && !$this->conexion->connect_error) {
            $this->conexion->close();
        }
    }
}