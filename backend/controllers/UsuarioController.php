<?php

require_once __DIR__ . '/../config/AuthGuard.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Progreso.php';

class UsuarioController {

    private $usuarioModel;
    private $progresoModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->progresoModel = new Progreso();
    }

    public function listarGenerales() {
        AuthGuard::requireRoleApi(AuthGuard::ROL_ADMIN);

        $usuarios = $this->usuarioModel->listarGenerales();

        header('Content-Type: application/json');
        echo json_encode($usuarios);
    }

    public function listarAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        
        $rolesVisibles = $rolActor === AuthGuard::ROL_MODERADOR
            ? [AuthGuard::ROL_GENERAL]
            : [AuthGuard::ROL_GENERAL, AuthGuard::ROL_MODERADOR];

        $usuarios = $this->usuarioModel->listarAdmin($rolesVisibles);

        header('Content-Type: application/json');
        echo json_encode($usuarios);
    }

    public function crearAdmin() {
        AuthGuard::requireRoleApi(AuthGuard::ROL_ADMIN);

        $nombre = trim($_POST['nombre_usuario'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = (int) ($_POST['rol'] ?? 0);

        header('Content-Type: application/json');

        if ($nombre === '' || strlen($nombre) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || strlen($password) < 6) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Revisá nombre, email y contraseña.']);
            return;
        }

        $rol = in_array($rol, [AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR], true) ? $rol : AuthGuard::ROL_GENERAL;

        if ($this->usuarioModel->crearAdmin($nombre, $email, $password, $rol)) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'No se pudo crear. El email puede estar en uso.']);
        }
    }

    public function editarAdmin() {
        AuthGuard::requireRoleApi(AuthGuard::ROL_ADMIN);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $nombre = trim($_POST['nombre_usuario'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = (int) ($_POST['rol'] ?? 0);

        header('Content-Type: application/json');

        if ($idUsuario <= 0 || $nombre === '' || strlen($nombre) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Revisá los datos del usuario.']);
            return;
        }

        if ($password !== '' && strlen($password) < 6) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            return;
        }

        $rolActual = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolActual === null || !AuthGuard::puedeGestionarRol(AuthGuard::ROL_ADMIN, $rolActual)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No podés modificar a otro Administrador.']);
            return;
        }

        
        $rol = $rol === AuthGuard::ROL_MODERADOR ? AuthGuard::ROL_MODERADOR : AuthGuard::ROL_GENERAL;

        if ($this->usuarioModel->editarAdmin($idUsuario, $nombre, $email, $rol, $password)) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'No se pudo editar. El email puede estar en uso.']);
        }
    }

    public function deshabilitarAdmin() {
        AuthGuard::requireRoleApi(AuthGuard::ROL_ADMIN);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        header('Content-Type: application/json');

        if ($idUsuario <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Usuario inválido.']);
            return;
        }

        if ($idUsuario === (int) ($_SESSION['id_usuario'] ?? 0)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'No podés deshabilitar tu propio usuario activo.']);
            return;
        }

        $rolActual = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolActual === null || !AuthGuard::puedeGestionarRol(AuthGuard::ROL_ADMIN, $rolActual)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No podés suspender a otro Administrador.']);
            return;
        }

        echo json_encode(['ok' => $this->usuarioModel->deshabilitarAdmin($idUsuario)]);
    }

    public function activarAdmin() {
        AuthGuard::requireRoleApi(AuthGuard::ROL_ADMIN);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        header('Content-Type: application/json');

        if ($idUsuario <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Usuario inválido.']);
            return;
        }

        $rolActual = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolActual === null || !AuthGuard::puedeGestionarRol(AuthGuard::ROL_ADMIN, $rolActual)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No podés reactivar a otro Administrador.']);
            return;
        }

        echo json_encode(['ok' => $this->usuarioModel->activarAdmin($idUsuario)]);
    }

    public function eliminarAdmin() {
        AuthGuard::requireRoleApi(AuthGuard::ROL_ADMIN);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        header('Content-Type: application/json');

        if ($idUsuario <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Usuario inválido.']);
            return;
        }

        if ($idUsuario === (int) ($_SESSION['id_usuario'] ?? 0)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'No podés eliminar tu propio usuario activo.']);
            return;
        }

        $rolActual = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolActual === null || !AuthGuard::puedeGestionarRol(AuthGuard::ROL_ADMIN, $rolActual)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No podés eliminar a otro Administrador.']);
            return;
        }

        echo json_encode(['ok' => $this->usuarioModel->eliminarAdmin($idUsuario)]);
    }

    
    public function cambiarRol() {
        AuthGuard::requireRoleApi(AuthGuard::ROL_ADMIN);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $rol = (int) ($_POST['rol'] ?? -1);

        header('Content-Type: application/json');

        if ($idUsuario <= 0 || !in_array($rol, [AuthGuard::ROL_GENERAL, AuthGuard::ROL_MODERADOR], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Rol inválido.']);
            return;
        }

        $rolActual = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolActual === null || !AuthGuard::puedeGestionarRol(AuthGuard::ROL_ADMIN, $rolActual)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No podés cambiar el rol de este usuario.']);
            return;
        }

        echo json_encode(['ok' => $this->usuarioModel->cambiarRol($idUsuario, $rol)]);
    }

    
    /**
     * Modifica el nivel de progreso de un usuario gestionado.
     * Disponible tanto para Administrador como para Moderador, siempre
     * respetando la jerarquía de permisos (un Moderador solo puede
     * modificar a Estudiantes; un Administrador a Estudiantes y Moderadores).
     */
    public function modificarNivel() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $nivel = (int) ($_POST['nivel'] ?? 0);
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');

        if ($idUsuario <= 0 || $nivel < 1 || $nivel > 999) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Nivel inválido.']);
            return;
        }

        $rolObjetivo = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolObjetivo === null || !AuthGuard::puedeGestionarRol($rolActor, $rolObjetivo)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés permisos para modificar a este usuario.']);
            return;
        }

        echo json_encode(['ok' => $this->progresoModel->modificarNivel($idUsuario, $nivel)]);
    }

    
    /**
     * Marca o desmarca a un usuario con la "tarjeta amarilla" por mal
     * comportamiento. Disponible tanto para Administrador como para
     * Moderador, respetando la jerarquía de permisos: un Moderador solo
     * puede marcar Estudiantes; un Administrador puede marcar Estudiantes
     * y Moderadores. Ningún rol puede marcar a un Administrador.
     * No suspende, no elimina y no modifica el rol del usuario.
     */
    public function marcarUsuario() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $marcado = (int) ($_POST['marcado'] ?? -1);
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');

        if ($idUsuario <= 0 || !in_array($marcado, [0, 1], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        $rolObjetivo = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolObjetivo === null || !AuthGuard::puedeGestionarRol($rolActor, $rolObjetivo)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés permisos para marcar a este usuario.']);
            return;
        }

        echo json_encode(['ok' => $this->usuarioModel->marcarUsuario($idUsuario, $marcado)]);
    }
}
