<?php

require_once __DIR__ . '/../config/Database.php';

class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    
    public function registrar($nombre, $email, $password) {
        
        $query = "INSERT INTO " . $this->table_name . " (nombre_usuario, email, password, rol) VALUES (:nombre, :email, :password, 0)";
        $stmt = $this->conn->prepare($query);
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);
        
        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false; 
        }
    }

    
    public function login($nombre_usuario, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE nombre_usuario = :nombre AND activo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre_usuario);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                return $row; 
            }
        }
        return false;
    }

    
    public function listarGenerales() {
        $query = "SELECT id_usuario, nombre_usuario FROM " . $this->table_name . " WHERE rol = 0 AND activo = 1 ORDER BY nombre_usuario ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAdmin($rolesVisibles = null) {
        $query = "SELECT id_usuario, nombre_usuario, email, rol, activo, marcado, fecha_registro
                  FROM " . $this->table_name;

        if (is_array($rolesVisibles) && count($rolesVisibles) > 0) {
            $marcadores = implode(',', array_fill(0, count($rolesVisibles), '?'));
            $query .= " WHERE rol IN (" . $marcadores . ")";
        }

        $query .= " ORDER BY activo DESC, rol DESC, nombre_usuario ASC";
        $stmt = $this->conn->prepare($query);

        if (is_array($rolesVisibles) && count($rolesVisibles) > 0) {
            foreach ($rolesVisibles as $indice => $rol) {
                $stmt->bindValue($indice + 1, (int) $rol, PDO::PARAM_INT);
            }
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function obtenerRolPorId($idUsuario) {
        $query = "SELECT rol FROM " . $this->table_name . " WHERE id_usuario = :id_usuario LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? (int) $fila['rol'] : null;
    }

    
    public function cambiarRol($idUsuario, $nuevoRol) {
        $query = "UPDATE " . $this->table_name . " SET rol = :rol WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rol', $nuevoRol, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    
    /**
     * Marca o desmarca a un usuario con la "tarjeta amarilla" por mal
     * comportamiento. No suspende, no elimina y no modifica sus permisos.
     */
    public function marcarUsuario($idUsuario, $marcado) {
        $query = "UPDATE " . $this->table_name . " SET marcado = :marcado WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $marcadoValor = $marcado ? 1 : 0;
        $stmt->bindParam(':marcado', $marcadoValor, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function crearAdmin($nombre, $email, $password, $rol) {
        $query = "INSERT INTO " . $this->table_name . " (nombre_usuario, email, password, rol, activo)
                  VALUES (:nombre, :email, :password, :rol, 1)";
        $stmt = $this->conn->prepare($query);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':rol', $rol, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function editarAdmin($idUsuario, $nombre, $email, $rol, $password = '') {
        $setPassword = '';
        if ($password !== '') {
            $setPassword = ", password = :password";
        }

        $query = "UPDATE " . $this->table_name . "
                  SET nombre_usuario = :nombre, email = :email, rol = :rol" . $setPassword . "
                  WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':rol', $rol, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        if ($password !== '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $password_hash);
        }

        try {
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function deshabilitarAdmin($idUsuario) {
        $query = "UPDATE " . $this->table_name . " SET activo = 0 WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function activarAdmin($idUsuario) {
        $query = "UPDATE " . $this->table_name . " SET activo = 1 WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function eliminarAdmin($idUsuario) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
