<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo encargado de persistir el progreso de juego (nivel, experiencia,
 * avatar, configuraciones, misiones en cache, etc.) de cada usuario.
 *
 * El progreso se guarda como JSON en la columna "progreso_json" de la
 * tabla "usuarios", siempre atado al id_usuario de la sesión autenticada.
 * De esta forma cada usuario tiene su propio progreso, completamente
 * independiente del resto.
 */
class Progreso {
    private $conn;
    private $table_name = "usuarios";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Devuelve el progreso guardado (como string JSON) para el usuario indicado,
     * o null si todavía no tiene progreso guardado.
     */
    public function obtener($idUsuario) {
        $query = "SELECT progreso_json FROM " . $this->table_name . " WHERE id_usuario = :id_usuario LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila || $fila['progreso_json'] === null) {
            return null;
        }

        return $fila['progreso_json'];
    }

    /**
     * Guarda (sobrescribe) el progreso del usuario indicado.
     * $progresoJson debe ser un string JSON ya validado/serializado.
     */
    public function guardar($idUsuario, $progresoJson) {
        $query = "UPDATE " . $this->table_name . " SET progreso_json = :progreso WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':progreso', $progresoJson, PDO::PARAM_STR);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Modifica únicamente el nivel dentro del progreso_json del usuario indicado,
     * preservando el resto de los datos guardados (experiencia, avatar, etc.).
     * Reutiliza obtener()/guardar() para no duplicar lógica de acceso a datos.
     */
    public function modificarNivel($idUsuario, $nivel) {
        $progresoJson = $this->obtener($idUsuario);
        $data = $progresoJson !== null ? json_decode($progresoJson, true) : null;

        if (!is_array($data)) {
            $data = [];
        }

        if (!isset($data['stats']) || !is_array($data['stats'])) {
            $data['stats'] = [];
        }

        $data['stats']['level'] = (int) $nivel;

        return $this->guardar($idUsuario, json_encode($data));
    }
}
