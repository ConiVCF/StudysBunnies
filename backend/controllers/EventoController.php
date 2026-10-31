<?php


require_once __DIR__ . '/../config/AuthGuard.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Usuario.php';

class EventoController {

    private $eventoModel;
    private $usuarioModel;

    public function __construct() {
        $this->eventoModel = new Evento();
        $this->usuarioModel = new Usuario();
    }

    
    private function rolesVisiblesParaActor($rolActor) {
        return $rolActor === AuthGuard::ROL_MODERADOR
            ? [AuthGuard::ROL_GENERAL]
            : [AuthGuard::ROL_GENERAL, AuthGuard::ROL_MODERADOR];
    }

    
    private function puedeGestionarEvento($rolActor, $idEvento) {
        $idDueno = $this->eventoModel->obtenerIdUsuario($idEvento);
        if ($idDueno === null) {
            return false;
        }

        $rolDueno = $this->usuarioModel->obtenerRolPorId($idDueno);
        return $rolDueno !== null && AuthGuard::puedeGestionarRol($rolActor, $rolDueno);
    }

    
    public function crear() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $recordatoriosRaw = trim($_POST['recordatorios'] ?? '');

        if ($titulo === '' || $fecha === '') {
            http_response_code(400);
            echo "Error: faltan datos para agendar el evento.";
            return;
        }

        if (mb_strlen($titulo) > 100) {
            http_response_code(400);
            echo "Error: el título es demasiado largo (máximo 100 caracteres).";
            return;
        }

        if (!in_array($tipo, Evento::TIPOS_VALIDOS, true)) {
            http_response_code(400);
            echo "Error: tipo de evento inválido.";
            return;
        }

        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
            http_response_code(400);
            echo "Error: fecha inválida.";
            return;
        }

        
        $recordatorios = $recordatoriosRaw === ''
            ? []
            : array_filter(array_map('intval', explode(',', $recordatoriosRaw)), fn($n) => $n >= 0);

        $idEvento = $this->eventoModel->crear($idUsuario, $titulo, $tipo, $fecha, $recordatorios);

        if ($idEvento !== false) {
            echo "Exito|" . $idEvento;
        } else {
            http_response_code(500);
            echo "Error: no se pudo guardar el evento en la base de datos.";
        }
    }

    
    public function listar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $eventos = $this->eventoModel->listarPorUsuario($idUsuario);

        header('Content-Type: application/json');
        echo json_encode($eventos);
    }

    
    public function eliminar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $idEvento = (int) ($_POST['id_evento'] ?? 0);

        if ($idEvento <= 0) {
            http_response_code(400);
            echo "Error: falta el id del evento.";
            return;
        }

        $ok = $this->eventoModel->eliminar($idEvento, $idUsuario);
        echo $ok ? "Exito" : "Error: no se pudo eliminar el evento.";
    }

    public function editarPropio() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $idEvento = (int) ($_POST['id_evento'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $recordatorios = $this->normalizarRecordatorios(trim($_POST['recordatorios'] ?? ''));

        if ($idEvento <= 0 || !$this->validarDatosEvento($idUsuario, $titulo, $tipo, $fecha)) {
            http_response_code(422);
            echo "Error: revisa los datos del evento.";
            return;
        }

        $ok = $this->eventoModel->editarPropio($idEvento, $idUsuario, $titulo, $tipo, $fecha, $recordatorios);
        echo $ok ? "Exito" : "Error: no se pudo editar el evento.";
    }

    private function validarDatosEvento($idUsuario, $titulo, $tipo, $fecha) {
        if ($idUsuario <= 0 || $titulo === '' || $fecha === '' || mb_strlen($titulo) > 100) {
            return false;
        }

        if (!in_array($tipo, Evento::TIPOS_VALIDOS, true)) {
            return false;
        }

        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        return $fechaObj && $fechaObj->format('Y-m-d') === $fecha;
    }

    private function normalizarRecordatorios($recordatoriosRaw) {
        return $recordatoriosRaw === ''
            ? []
            : array_filter(array_map('intval', explode(',', $recordatoriosRaw)), fn($n) => $n >= 0);
    }

    public function listarAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');
        echo json_encode($this->eventoModel->listarTodasConUsuario($this->rolesVisiblesParaActor($rolActor)));
    }

    public function crearAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $recordatorios = $this->normalizarRecordatorios(trim($_POST['recordatorios'] ?? ''));
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');
        if (!$this->validarDatosEvento($idUsuario, $titulo, $tipo, $fecha)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'RevisÃ¡ los datos del evento.']);
            return;
        }

        $rolObjetivo = $this->usuarioModel->obtenerRolPorId($idUsuario);
        if ($rolObjetivo === null || !AuthGuard::puedeGestionarRol($rolActor, $rolObjetivo)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés permisos para agendar eventos a este usuario.']);
            return;
        }

        $idEvento = $this->eventoModel->crear($idUsuario, $titulo, $tipo, $fecha, $recordatorios);
        echo json_encode(['ok' => $idEvento !== false, 'id_evento' => $idEvento]);
    }

    public function editarAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idEvento = (int) ($_POST['id_evento'] ?? 0);
        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $recordatorios = $this->normalizarRecordatorios(trim($_POST['recordatorios'] ?? ''));
        $rolActor = (int) ($_SESSION['rol'] ?? -1);

        header('Content-Type: application/json');
        if ($idEvento <= 0 || !$this->validarDatosEvento($idUsuario, $titulo, $tipo, $fecha)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'RevisÃ¡ los datos del evento.']);
            return;
        }

        if (!$this->puedeGestionarEvento($rolActor, $idEvento) || !AuthGuard::puedeGestionarRol($rolActor, $this->usuarioModel->obtenerRolPorId($idUsuario) ?? -1)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés permisos sobre este evento.']);
            return;
        }

        echo json_encode(['ok' => $this->eventoModel->editarAdmin($idEvento, $idUsuario, $titulo, $tipo, $fecha, $recordatorios)]);
    }

    public function eliminarAdmin() {
        AuthGuard::requireAnyRoleApi([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);

        $idEvento = (int) ($_POST['id_evento'] ?? 0);
        $rolActor = (int) ($_SESSION['rol'] ?? -1);
        header('Content-Type: application/json');

        if ($idEvento <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Evento invÃ¡lido.']);
            return;
        }

        if (!$this->puedeGestionarEvento($rolActor, $idEvento)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés permisos sobre este evento.']);
            return;
        }

        echo json_encode(['ok' => $this->eventoModel->eliminarAdmin($idEvento)]);
    }
}
