<?php

require_once __DIR__ . '/backend/controllers/EventoController.php';

$controller = new EventoController();
$accion = $_POST['accion'] ?? 'crear';

if ($accion === 'editar') {
    $controller->editarPropio();
} else {
    $controller->crear();
}
