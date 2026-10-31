<?php

require_once __DIR__ . '/backend/controllers/SalaController.php';

$controller = new SalaController();
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case 'listar':
        $controller->listar();
        break;
    case 'crear':
        $controller->crear();
        break;
    case 'acceder':
        $controller->acceder();
        break;
    case 'listar_chat':
        $controller->listarChat();
        break;
    case 'enviar_chat':
        $controller->enviarChat();
        break;
    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'AcciÃ³n no reconocida.']);
}
