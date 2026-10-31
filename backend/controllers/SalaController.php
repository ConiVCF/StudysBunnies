<?php

require_once __DIR__ . '/../config/AuthGuard.php';
require_once __DIR__ . '/../models/Sala.php';
require_once __DIR__ . '/../models/SalaChat.php';

class SalaController {
    private $salaModel;
    private $chatModel;

    public function __construct() {
        $this->salaModel = new Sala();
        $this->chatModel = new SalaChat();
    }

    public function listar() {
        AuthGuard::requireLoginApi();

        header('Content-Type: application/json');
        echo json_encode($this->salaModel->listarDisponibles((int) $_SESSION['id_usuario']));
    }

    public function crear() {
        AuthGuard::requireLoginApi();

        $nombre = trim($_POST['nombre'] ?? '');
        $privacidad = ($_POST['privacidad'] ?? 'privada') === 'publica' ? 'publica' : 'privada';
        $password = trim($_POST['password'] ?? '');

        header('Content-Type: application/json');
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'El nombre de la sala es obligatorio.']);
            return;
        }

        if ($privacidad === 'privada' && strlen($password) < 4) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 4 caracteres.']);
            return;
        }

        $passwordHash = $privacidad === 'privada' ? password_hash($password, PASSWORD_DEFAULT) : null;
        $idSala = $this->salaModel->crear($nombre, (int) $_SESSION['id_usuario'], $privacidad, $passwordHash);
        $sala = $idSala ? $this->salaModel->buscarPorId($idSala) : null;

        // El creador de la sala queda autorizado en su sesión automáticamente.
        if ($idSala !== false) {
            $_SESSION['salas_autorizadas'][$idSala] = true;
        }

        echo json_encode(['ok' => $idSala !== false, 'id_sala' => $idSala, 'sala' => $sala]);
    }

    // Se llama UNA sola vez, al intentar entrar a una sala. Es el único lugar
    // donde viaja la contraseña. Si es correcta, queda autorizado en la sesión
    // y no hace falta volver a mandarla en el resto de las peticiones de esta sala.
    public function acceder() {
        AuthGuard::requireLoginApi();

        $idSala = (int) ($_POST['id_sala'] ?? 0);
        $password = trim($_POST['password'] ?? '');

        header('Content-Type: application/json');
        if ($idSala <= 0 || !$this->salaModel->puedeAcceder($idSala, (int) $_SESSION['id_usuario'], $password)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'ID o contraseña incorrectos.']);
            return;
        }

        $_SESSION['salas_autorizadas'][$idSala] = true;
        echo json_encode(['ok' => true, 'sala' => $this->salaModel->buscarPorId($idSala)]);
    }

    public function listarChat() {
        AuthGuard::requireLoginApi();

        $idSala = (int) ($_GET['id_sala'] ?? 0);
        header('Content-Type: application/json');

        if ($idSala <= 0 || !$this->tieneAccesoAutorizado($idSala, (int) $_SESSION['id_usuario'])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés acceso a esta sala.']);
            return;
        }

        echo json_encode($this->chatModel->listar($idSala));
    }

    public function enviarChat() {
        AuthGuard::requireLoginApi();

        $idSala = (int) ($_POST['id_sala'] ?? 0);
        $mensaje = trim($_POST['mensaje'] ?? '');

        header('Content-Type: application/json');
        if ($idSala <= 0 || !$this->tieneAccesoAutorizado($idSala, (int) $_SESSION['id_usuario'])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tenés acceso a esta sala.']);
            return;
        }

        if ($mensaje === '' || mb_strlen($mensaje) > 500) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'El mensaje no puede estar vacío.']);
            return;
        }

        echo json_encode(['ok' => $this->chatModel->guardar($idSala, (int) $_SESSION['id_usuario'], $mensaje)]);
    }

    // Sala pública, dueño de la sala, o ya autorizado en esta sesión (entró con
    // la contraseña correcta en algún momento de esta sesión): en cualquiera de
    // los tres casos, no hace falta volver a pedir la contraseña.
    private function tieneAccesoAutorizado($idSala, $idUsuario) {
        if (!empty($_SESSION['salas_autorizadas'][$idSala])) {
            return true;
        }

        $sala = $this->salaModel->buscarPorId($idSala);
        if (!$sala) {
            return false;
        }

        if ($sala['privacidad'] === 'publica') {
            return true;
        }

        return (int) $sala['id_propietario'] === (int) $idUsuario;
    }
}
