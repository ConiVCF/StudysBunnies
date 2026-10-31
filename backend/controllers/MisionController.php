<?php


require_once __DIR__ . '/../config/AuthGuard.php';
require_once __DIR__ . '/../models/Mision.php';
require_once __DIR__ . '/../models/Usuario.php';

class MisionController {

    private $misionModel;
    private $usuarioModel;

    public function __construct() {
        $this->misionModel = new Mision();
        $this->usuarioModel = new Usuario();
    }

    
    /**
     * Roles visibles para el filtro de listados de gestión (Admin ve
     * Estudiantes y Moderadores; Moderador solo ve Estudiantes).
     */
    private function rolesVisiblesParaActor($rolActor) {
        return $rolActor === AuthGuard::ROL_MODERADOR
            ? [AuthGuard::ROL_GENERAL]
            : [AuthGuard::ROL_GENERAL, AuthGuard::ROL_MODERADOR];
    }

    
    public function crear() {
        
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $texto = trim($_POST['tarea'] ?? '');

        if ($texto === '') {
            http_response_code(400);
            echo "Error: la tarea no puede estar vacía.";
            return;
        }

        if (mb_strlen($texto) > 255) {
            http_response_code(400);
            echo "Error: la tarea es demasiado larga (máximo 255 caracteres).";
            return;
        }

        $idMision = $this->misionModel->crear($idUsuario, $texto);

        if ($idMision !== false) {
            echo "Exito|" . $idMision;
        } else {
            http_response_code(500);
            echo "Error: no se pudo guardar la misión en la base de datos.";
        }
    }

    
    public function listar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $misiones = $this->misionModel->listarPorUsuario($idUsuario);

        header('Content-Type: application/json');
        echo json_encode($misiones);
    }

    
    public function actualizarEstado() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $idMision = (int) ($_POST['id_mision'] ?? 0);
        $completado = (isset($_POST['completado']) && $_POST['completado'] === '1') ? 1 : 0;

        if ($idMision <= 0) {
            http_response_code(400);
            echo "Error: falta el id de la misión.";
            return;
        }

        $ok = $this->misionModel->actualizarEstado($idMision, $idUsuario, $completado);
        echo $ok ? "Exito" : "Error: no se pudo actualizar la misión.";
    }

    public function editarPropia() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $idMision = (int) ($_POST['id_mision'] ?? 0);
        $texto = trim($_POST['tarea'] ?? '');

        if ($idMision <= 0) {
            http_response_code(400);
            echo "Error: falta el id de la mision.";
            return;
        }

        if ($texto === '' || mb_strlen($texto) > 255) {
            http_response_code(422);
            echo "Error: la tarea no puede estar vacia ni superar 255 caracteres.";
            return;
        }

        $ok = $this->misionModel->editarPropia($idMision, $idUsuario, $texto);
        echo $ok ? "Exito" : "Error: no se pudo editar la mision.";
    }

    
    public function eliminar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $idMision = (int) ($_POST['id_mision'] ?? 0);

        if ($idMision <= 0) {
            http_response_code(400);
            echo "Error: falta el id de la misión.";
            return;
        }

        $ok = $this->misionModel->eliminar($idMision, $idUsuario);
        echo $ok ? "Exito" : "Error: no se pudo eliminar la misión.";
    }

   

    
    public function listarTodasAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $rolActor = (int) ($_SESSION['rol'] ?? -1);
        $misiones = $this->misionModel->listarTodasConUsuario($this->rolesVisiblesParaActor($rolActor));

        header('Content-Type: application/json');
        echo json_encode($misiones);
    }

    
    public function crearAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idUsuarioDestino = (int) ($_POST['id_usuario'] ?? 0);
        $texto = trim($_POST['tarea'] ?? '');
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        if ($idUsuarioDestino <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Debés elegir a qué usuario asignarle la tarea.']);
            return;
        }

        $rolObjetivo = $this->usuarioModel->obtenerRolPorId($idUsuarioDestino);
        if ($rolObjetivo === null || !AuthGuard::puedeGestionarRol($rolActor, $rolObjetivo)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tenés permisos para asignar tareas a este usuario.']);
            return;
        }

        if ($texto === '' || mb_strlen($texto) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'La tarea no puede estar vacía ni superar 255 caracteres.']);
            return;
        }

        $idMision = $this->misionModel->crear($idUsuarioDestino, $texto);

        header('Content-Type: application/json');
        if ($idMision !== false) {
            echo json_encode(['ok' => true, 'id_mision' => $idMision]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo crear la misión.']);
        }
    }

    
    public function actualizarEstadoAdminAccion() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idMision = (int) ($_POST['id_mision'] ?? 0);
        $completado = (isset($_POST['completado']) && $_POST['completado'] === '1') ? 1 : 0;
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');
        if ($idMision <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id de la misión.']);
            return;
        }

        if (!$this->puedeGestionarMision($rolActor, $idMision)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tenés permisos sobre esta misión.']);
            return;
        }

        $ok = $this->misionModel->actualizarEstadoAdmin($idMision, $completado);
        echo json_encode(['ok' => $ok]);
    }

    public function editarAdminAccion() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idMision = (int) ($_POST['id_mision'] ?? 0);
        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $texto = trim($_POST['tarea'] ?? '');
        $completado = (isset($_POST['completado']) && $_POST['completado'] === '1') ? 1 : 0;
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');
        if ($idMision <= 0 || $idUsuario <= 0 || $texto === '' || mb_strlen($texto) > 255) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'RevisÃ¡ los datos de la misiÃ³n.']);
            return;
        }

        if (!$this->puedeGestionarMision($rolActor, $idMision) || !AuthGuard::puedeGestionarRol($rolActor, $this->usuarioModel->obtenerRolPorId($idUsuario) ?? -1)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés permisos sobre esta misión.']);
            return;
        }

        echo json_encode(['ok' => $this->misionModel->editarAdmin($idMision, $idUsuario, $texto, $completado)]);
    }

    
    public function eliminarAdminAccion() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idMision = (int) ($_POST['id_mision'] ?? 0);
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');
        if ($idMision <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id de la misión.']);
            return;
        }

        if (!$this->puedeGestionarMision($rolActor, $idMision)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tenés permisos sobre esta misión.']);
            return;
        }

        $ok = $this->misionModel->eliminarAdmin($idMision);
        echo json_encode(['ok' => $ok]);
    }

    
    private function puedeGestionarMision($rolActor, $idMision) {
        $idDueno = $this->misionModel->obtenerIdUsuario($idMision);
        if ($idDueno === null) {
            return false;
        }

        $rolDueno = $this->usuarioModel->obtenerRolPorId($idDueno);
        return $rolDueno !== null && AuthGuard::puedeGestionarRol($rolActor, $rolDueno);
    }

    
    public function buscar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $termino = trim($_GET['q'] ?? '');

        header('Content-Type: application/json');

        if ($termino === '') {
            echo json_encode([]);
            return;
        }

        $resultados = $this->misionModel->buscarPorUsuario($idUsuario, $termino);
        echo json_encode($resultados);
    }

    
    public function buscarAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $termino = trim($_GET['q'] ?? '');
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');

        if ($termino === '') {
            echo json_encode([]);
            return;
        }

        $resultados = $this->misionModel->buscarTodas($termino);

        $resultados = array_values(array_filter($resultados, function ($fila) use ($rolActor) {
            $rolDueno = $this->usuarioModel->obtenerRolPorId((int) $fila['id_usuario']);
            return $rolDueno !== null && AuthGuard::puedeGestionarRol($rolActor, $rolDueno);
        }));

        echo json_encode($resultados);
    }
}
