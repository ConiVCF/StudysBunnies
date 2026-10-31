<?php


require_once __DIR__ . '/backend/controllers/MisionController.php';
require_once __DIR__ . '/backend/controllers/UsuarioController.php';
require_once __DIR__ . '/backend/controllers/EventoController.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

$misionController = new MisionController();
$usuarioController = new UsuarioController();
$eventoController = new EventoController();

switch ($accion) {
    case 'listar_misiones':
        $misionController->listarTodasAdmin();
        break;

    case 'crear_mision':
        $misionController->crearAdmin();
        break;

    case 'actualizar_estado':
        $misionController->actualizarEstadoAdminAccion();
        break;

    case 'editar_mision':
        $misionController->editarAdminAccion();
        break;

    case 'eliminar_mision':
        $misionController->eliminarAdminAccion();
        break;

    case 'listar_usuarios':
        $usuarioController->listarGenerales();
        break;

    case 'listar_usuarios_admin':
        $usuarioController->listarAdmin();
        break;

    case 'crear_usuario':
        $usuarioController->crearAdmin();
        break;

    case 'editar_usuario':
        $usuarioController->editarAdmin();
        break;

    case 'deshabilitar_usuario':
        $usuarioController->deshabilitarAdmin();
        break;

    case 'activar_usuario':
        $usuarioController->activarAdmin();
        break;

    case 'eliminar_usuario':
        $usuarioController->eliminarAdmin();
        break;

    case 'cambiar_rol_usuario':
        $usuarioController->cambiarRol();
        break;

    case 'modificar_nivel_usuario':
        $usuarioController->modificarNivel();
        break;

    case 'marcar_usuario':
        $usuarioController->marcarUsuario();
        break;

    case 'listar_eventos_admin':
        $eventoController->listarAdmin();
        break;

    case 'crear_evento_admin':
        $eventoController->crearAdmin();
        break;

    case 'editar_evento_admin':
        $eventoController->editarAdmin();
        break;

    case 'eliminar_evento_admin':
        $eventoController->eliminarAdmin();
        break;

    case 'buscar_misiones':
        $misionController->buscarAdmin();
        break;

    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acción no reconocida.']);
}
