<?php

require_once __DIR__ . '/../config/AuthGuard.php';
require_once __DIR__ . '/../models/Progreso.php';

class ProgresoController {

    private $progresoModel;

    public function __construct() {
        $this->progresoModel = new Progreso();
    }

    /**
     * Devuelve el progreso del usuario autenticado (nunca el de otro usuario).
     * Si el usuario todavía no tiene progreso guardado, responde "data": null
     * para que el frontend use los valores por defecto (Nivel 1).
     */
    public function cargar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $progresoJson = $this->progresoModel->obtener($idUsuario);

        header('Content-Type: application/json');

        if ($progresoJson === null) {
            echo json_encode(['ok' => true, 'data' => null]);
            return;
        }

        $data = json_decode($progresoJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['ok' => true, 'data' => null]);
            return;
        }

        echo json_encode(['ok' => true, 'data' => $data]);
    }

    /**
     * Guarda el progreso enviado por el cliente para el usuario autenticado.
     * El id_usuario siempre se toma de la sesión del servidor, nunca de un
     * parámetro enviado por el cliente, para evitar que un usuario pueda
     * sobrescribir el progreso de otro.
     */
    public function guardar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];

        $crudo = file_get_contents('php://input');
        $payload = json_decode($crudo, true);

        header('Content-Type: application/json');

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Progreso inválido.']);
            return;
        }

        // Límite razonable de tamaño para evitar payloads abusivos.
        if (strlen($crudo) > 200000) {
            http_response_code(413);
            echo json_encode(['ok' => false, 'error' => 'Progreso demasiado grande.']);
            return;
        }

        $progresoJson = json_encode($payload);
        $ok = $this->progresoModel->guardar($idUsuario, $progresoJson);

        echo json_encode(['ok' => $ok]);
    }
}
