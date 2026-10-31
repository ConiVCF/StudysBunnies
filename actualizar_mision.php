<?php


require_once __DIR__ . '/backend/controllers/MisionController.php';

$controller = new MisionController();
$accion = $_POST['accion'] ?? 'estado';

if ($accion === 'editar') {
    $controller->editarPropia();
} else {
    $controller->actualizarEstado();
}
