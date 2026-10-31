<?php

require_once __DIR__ . '/../config/Database.php';

class SalaChat {
    private $conn;
    private $table_name = "sala_chat";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function guardar($idSala, $idUsuario, $mensaje) {
        $query = "INSERT INTO " . $this->table_name . " (id_sala, id_usuario, mensaje)
                  VALUES (:id_sala, :id_usuario, :mensaje)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':mensaje', $mensaje, PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function listar($idSala, $limite = 40) {
        $query = "SELECT sc.id_mensaje, sc.mensaje, sc.fecha_creacion, u.nombre_usuario
                  FROM " . $this->table_name . " sc
                  JOIN usuarios u ON sc.id_usuario = u.id_usuario
                  WHERE sc.id_sala = :id_sala
                  ORDER BY sc.id_mensaje DESC
                  LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sala', $idSala, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
