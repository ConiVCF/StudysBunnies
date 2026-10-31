<?php

require_once __DIR__ . '/../config/Database.php';

class Evento {
    private $conn;
    private $table_name = "eventos";

    const TIPOS_VALIDOS = ['Examen', 'Final', 'TP', 'Proyecto', 'Personal'];

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    
    public function crear($idUsuario, $titulo, $tipo, $fecha, array $recordatorios) {
        $recordatoriosTexto = implode(',', array_map('intval', $recordatorios));

        $query = "INSERT INTO " . $this->table_name . "
                  (id_usuario, titulo, tipo, fecha, recordatorios)
                  VALUES (:id_usuario, :titulo, :tipo, :fecha, :recordatorios)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':recordatorios', $recordatoriosTexto, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return (int) $this->conn->lastInsertId();
        }
        return false;
    }

    
    public function listarPorUsuario($idUsuario) {
        $query = "SELECT id_evento, titulo, tipo, fecha, recordatorios
                  FROM " . $this->table_name . "
                  WHERE id_usuario = :id_usuario
                  ORDER BY fecha ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function eliminar($idEvento, $idUsuario) {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_evento = :id_evento AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function editarPropio($idEvento, $idUsuario, $titulo, $tipo, $fecha, array $recordatorios) {
        $recordatoriosTexto = implode(',', array_map('intval', $recordatorios));
        $query = "UPDATE " . $this->table_name . "
                  SET titulo = :titulo,
                      tipo = :tipo,
                      fecha = :fecha,
                      recordatorios = :recordatorios
                  WHERE id_evento = :id_evento AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':recordatorios', $recordatoriosTexto, PDO::PARAM_STR);
        $stmt->bindParam(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function listarTodasConUsuario($rolesVisibles = null) {
        $query = "SELECT
                        e.id_evento,
                        e.id_usuario,
                        e.titulo,
                        e.tipo,
                        e.fecha,
                        e.recordatorios,
                        u.nombre_usuario
                  FROM " . $this->table_name . " e
                  JOIN usuarios u ON e.id_usuario = u.id_usuario";

        if (is_array($rolesVisibles) && count($rolesVisibles) > 0) {
            $marcadores = implode(',', array_fill(0, count($rolesVisibles), '?'));
            $query .= " WHERE u.rol IN (" . $marcadores . ")";
        }

        $query .= " ORDER BY e.fecha ASC, e.fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);

        if (is_array($rolesVisibles) && count($rolesVisibles) > 0) {
            foreach ($rolesVisibles as $indice => $rol) {
                $stmt->bindValue($indice + 1, (int) $rol, PDO::PARAM_INT);
            }
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function obtenerIdUsuario($idEvento) {
        $query = "SELECT id_usuario FROM " . $this->table_name . " WHERE id_evento = :id_evento LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? (int) $fila['id_usuario'] : null;
    }

    public function editarAdmin($idEvento, $idUsuario, $titulo, $tipo, $fecha, array $recordatorios) {
        $recordatoriosTexto = implode(',', array_map('intval', $recordatorios));
        $query = "UPDATE " . $this->table_name . "
                  SET id_usuario = :id_usuario,
                      titulo = :titulo,
                      tipo = :tipo,
                      fecha = :fecha,
                      recordatorios = :recordatorios
                  WHERE id_evento = :id_evento";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':recordatorios', $recordatoriosTexto, PDO::PARAM_STR);
        $stmt->bindParam(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function eliminarAdmin($idEvento) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_evento = :id_evento";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
