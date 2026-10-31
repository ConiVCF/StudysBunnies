<?php

require_once __DIR__ . '/../config/Database.php';

class JugadorOnline {
    private $conn;
    private $table_name = "jugadores_online";

    
    const SEGUNDOS_ACTIVO = 8;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
        $this->asegurarColumnasPerfil();
    }

    private function columnaExiste($tabla, $columna) {
        $query = "SELECT COUNT(*)
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = :tabla
                    AND COLUMN_NAME = :columna";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tabla', $tabla, PDO::PARAM_STR);
        $stmt->bindParam(':columna', $columna, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function asegurarColumnasPerfil() {
        if (!$this->columnaExiste('usuarios', 'edad')) {
            $this->conn->exec("ALTER TABLE usuarios ADD edad TINYINT UNSIGNED NULL AFTER email");
        }

        if (!$this->columnaExiste($this->table_name, 'materia_estudio')) {
            $this->conn->exec("ALTER TABLE " . $this->table_name . " ADD materia_estudio VARCHAR(80) NULL AFTER avatar_accessory");
        }

        if (!$this->columnaExiste($this->table_name, 'tiempo_estudio_segundos')) {
            $this->conn->exec("ALTER TABLE " . $this->table_name . " ADD tiempo_estudio_segundos INT UNSIGNED NOT NULL DEFAULT 0 AFTER materia_estudio");
        }
    }

    
    public function actualizar($idUsuario, $nombre, $x, $y, $direccion, $estado, array $avatar, $idSala = null, $materia = null, $tiempoEstudio = 0) {
        $query = "INSERT INTO " . $this->table_name . "
                    (id_usuario, id_sala, nombre_usuario, pos_x, pos_y, direccion, estado, avatar_base, avatar_clothing, avatar_accessory, materia_estudio, tiempo_estudio_segundos, ultima_actualizacion)
                  VALUES
                    (:id_usuario, :id_sala, :nombre, :x, :y, :direccion, :estado, :base, :clothing, :accessory, :materia, :tiempo_estudio, NOW())
                  ON DUPLICATE KEY UPDATE
                    id_sala = VALUES(id_sala),
                    nombre_usuario = VALUES(nombre_usuario),
                    pos_x = VALUES(pos_x),
                    pos_y = VALUES(pos_y),
                    direccion = VALUES(direccion),
                    estado = VALUES(estado),
                    avatar_base = VALUES(avatar_base),
                    avatar_clothing = VALUES(avatar_clothing),
                    avatar_accessory = VALUES(avatar_accessory),
                    materia_estudio = VALUES(materia_estudio),
                    tiempo_estudio_segundos = VALUES(tiempo_estudio_segundos),
                    ultima_actualizacion = NOW()";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        if ($idSala === null) {
            $stmt->bindValue(':id_sala', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
        }
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindParam(':x', $x);
        $stmt->bindParam(':y', $y);
        $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindParam(':base', $avatar['base'], PDO::PARAM_STR);
        $stmt->bindParam(':clothing', $avatar['clothing'], PDO::PARAM_STR);
        $stmt->bindParam(':accessory', $avatar['accessory'], PDO::PARAM_STR);
        $stmt->bindParam(':materia', $materia, PDO::PARAM_STR);
        $stmt->bindParam(':tiempo_estudio', $tiempoEstudio, PDO::PARAM_INT);

        return $stmt->execute();
    }

    
    public function listarActivos($idUsuarioPropio, $idSala = null) {
        $query = "SELECT jo.id_usuario, jo.nombre_usuario, jo.pos_x, jo.pos_y, jo.direccion, jo.estado,
                         jo.avatar_base, jo.avatar_clothing, jo.avatar_accessory,
                         jo.materia_estudio, jo.tiempo_estudio_segundos, u.edad
                  FROM " . $this->table_name . " jo
                  INNER JOIN usuarios u ON u.id_usuario = jo.id_usuario
                  WHERE jo.ultima_actualizacion >= (NOW() - INTERVAL " . self::SEGUNDOS_ACTIVO . " SECOND)";
        $query .= $idSala ? " AND jo.id_sala = :id_sala" : " AND jo.id_sala IS NULL";
        $stmt = $this->conn->prepare($query);
        if ($idSala) {
            $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function eliminar($idUsuario) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
