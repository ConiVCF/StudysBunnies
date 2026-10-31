<?php

require_once __DIR__ . '/../config/Database.php';

class Mision {
    private $conn;
    private $table_name = "misiones";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    
    public function crear($idUsuario, $textoTarea) {
        $query = "INSERT INTO " . $this->table_name . " (id_usuario, texto_tarea) VALUES (:id_usuario, :texto_tarea)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':texto_tarea', $textoTarea, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return (int) $this->conn->lastInsertId();
        }
        return false;
    }

    
    public function listarPorUsuario($idUsuario) {
        $query = "SELECT id_mision, texto_tarea, estado_completado
                  FROM " . $this->table_name . "
                  WHERE id_usuario = :id_usuario
                  ORDER BY fecha_creacion ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function actualizarEstado($idMision, $idUsuario, $completado) {
        $query = "UPDATE " . $this->table_name . "
                  SET estado_completado = :estado
                  WHERE id_mision = :id_mision AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $completado, PDO::PARAM_INT);
        $stmt->bindParam(':id_mision', $idMision, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function editarPropia($idMision, $idUsuario, $textoTarea) {
        $query = "UPDATE " . $this->table_name . "
                  SET texto_tarea = :texto_tarea
                  WHERE id_mision = :id_mision AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':texto_tarea', $textoTarea, PDO::PARAM_STR);
        $stmt->bindParam(':id_mision', $idMision, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    
    public function eliminar($idMision, $idUsuario) {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_mision = :id_mision AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_mision', $idMision, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    

    
    public function listarTodasConUsuario($rolesVisibles = null) {
        $query = "SELECT
                        m.id_mision,
                        m.texto_tarea,
                        m.estado_completado,
                        m.fecha_creacion,
                        u.id_usuario,
                        u.nombre_usuario
                  FROM " . $this->table_name . " m
                  JOIN usuarios u ON m.id_usuario = u.id_usuario";

        if (is_array($rolesVisibles) && count($rolesVisibles) > 0) {
            $marcadores = implode(',', array_fill(0, count($rolesVisibles), '?'));
            $query .= " WHERE u.rol IN (" . $marcadores . ")";
        }

        $query .= " ORDER BY m.fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);

        if (is_array($rolesVisibles) && count($rolesVisibles) > 0) {
            foreach ($rolesVisibles as $indice => $rol) {
                $stmt->bindValue($indice + 1, (int) $rol, PDO::PARAM_INT);
            }
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function obtenerIdUsuario($idMision) {
        $query = "SELECT id_usuario FROM " . $this->table_name . " WHERE id_mision = :id_mision LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_mision', $idMision, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? (int) $fila['id_usuario'] : null;
    }

    
    public function actualizarEstadoAdmin($idMision, $completado) {
        $query = "UPDATE " . $this->table_name . "
                  SET estado_completado = :estado
                  WHERE id_mision = :id_mision";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $completado, PDO::PARAM_INT);
        $stmt->bindParam(':id_mision', $idMision, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function editarAdmin($idMision, $idUsuario, $textoTarea, $completado) {
        $query = "UPDATE " . $this->table_name . "
                  SET id_usuario = :id_usuario,
                      texto_tarea = :texto_tarea,
                      estado_completado = :estado
                  WHERE id_mision = :id_mision";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':texto_tarea', $textoTarea, PDO::PARAM_STR);
        $stmt->bindParam(':estado', $completado, PDO::PARAM_INT);
        $stmt->bindParam(':id_mision', $idMision, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    
    public function eliminarAdmin($idMision) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_mision = :id_mision";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_mision', $idMision, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }



    private function escaparLike($termino) {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $termino);
    }

    
    public function buscarPorUsuario($idUsuario, $termino) {
        $query = "SELECT id_mision, texto_tarea, estado_completado
                  FROM " . $this->table_name . "
                  WHERE id_usuario = :id_usuario AND texto_tarea LIKE :termino ESCAPE '\\\\'
                  ORDER BY fecha_creacion ASC";
        $stmt = $this->conn->prepare($query);
        $like = '%' . $this->escaparLike($termino) . '%';
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':termino', $like, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function buscarTodas($termino) {
        $query = "SELECT
                        m.id_mision, m.texto_tarea, m.estado_completado, m.fecha_creacion,
                        u.id_usuario, u.nombre_usuario
                  FROM " . $this->table_name . " m
                  JOIN usuarios u ON m.id_usuario = u.id_usuario
                  WHERE m.texto_tarea LIKE :termino ESCAPE '\\\\'
                     OR u.nombre_usuario LIKE :termino2 ESCAPE '\\\\'
                  ORDER BY m.fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $like = '%' . $this->escaparLike($termino) . '%';
        $stmt->bindParam(':termino', $like, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $like, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
