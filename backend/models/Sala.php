<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/JugadorOnline.php';

class Sala {
    private $conn;
    private $table_name = "salas";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function crear($nombre, $idPropietario, $privacidad, $passwordHash = null) {
        $query = "INSERT INTO " . $this->table_name . " (id_sala, nombre, id_propietario, privacidad, password_hash)
                  VALUES (:id_sala, :nombre, :id_propietario, :privacidad, :password_hash)";

        for ($intento = 0; $intento < 8; $intento++) {
            $idSala = random_int(100000, 999999);
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':id_propietario', $idPropietario, PDO::PARAM_INT);
            $stmt->bindParam(':privacidad', $privacidad, PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);

            try {
                if ($stmt->execute()) {
                    return $idSala;
                }
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
        return false;
    }

    public function listarDisponibles($idUsuario) {
        $query = "SELECT s.id_sala, s.nombre, s.id_propietario, s.privacidad, u.nombre_usuario AS propietario
                  FROM " . $this->table_name . " s
                  JOIN usuarios u ON s.id_propietario = u.id_usuario
                  ORDER BY s.fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($idSala) {
        $query = "SELECT s.id_sala, s.nombre, s.id_propietario, s.privacidad, u.nombre_usuario AS propietario
                  FROM " . $this->table_name . " s
                  JOIN usuarios u ON s.id_propietario = u.id_usuario
                  WHERE s.id_sala = :id_sala
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function eliminarVacias($segundosGracia = 0) {
        $query = "DELETE s FROM " . $this->table_name . " s
                  LEFT JOIN jugadores_online jo
                    ON jo.id_sala = s.id_sala
                   AND jo.ultima_actualizacion >= (NOW() - INTERVAL " . (int) JugadorOnline::SEGUNDOS_ACTIVO . " SECOND)
                  WHERE jo.id_usuario IS NULL";

        if ($segundosGracia > 0) {
            $query .= " AND s.fecha_creacion <= (NOW() - INTERVAL " . (int) $segundosGracia . " SECOND)";
        }

        return $this->conn->exec($query);
    }

    public function puedeAcceder($idSala, $idUsuario, $password = '') {
        $query = "SELECT id_propietario, privacidad, password_hash
                  FROM " . $this->table_name . "
                  WHERE id_sala = :id_sala
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
        $stmt->execute();
        $sala = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sala) {
            return false;
        }

        if ($sala['privacidad'] === 'publica') {
            return true;
        }

        if ((int) $sala['id_propietario'] === (int) $idUsuario) {
            return true;
        }

        return $sala['password_hash'] && password_verify($password, $sala['password_hash']);
    }
}
