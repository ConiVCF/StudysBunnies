<?php


require_once __DIR__ . '/../config/AuthGuard.php';
require_once __DIR__ . '/../models/JugadorOnline.php';
require_once __DIR__ . '/../models/Sala.php';

class JugadorOnlineController {

    private $model;

    public function __construct() {
        $this->model = new JugadorOnline();
    }

    
    public function actualizar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $nombre = $_SESSION['nombre'];

        $x = (float) ($_POST['x'] ?? 0);
        $y = (float) ($_POST['y'] ?? 0);
        $direccion = ($_POST['direccion'] ?? 'right') === 'left' ? 'left' : 'right';
        $estado = ($_POST['estado'] ?? 'idle') === 'walking' ? 'walking' : 'idle';
        $idSala = isset($_POST['id_sala']) && (int) $_POST['id_sala'] > 0 ? (int) $_POST['id_sala'] : null;
        $materia = trim($_POST['materia_estudio'] ?? '');
        $materia = $materia !== '' ? substr($materia, 0, 80) : null;
        $tiempoEstudio = max(0, (int) ($_POST['tiempo_estudio_segundos'] ?? 0));

        // Solo permitimos caracteres válidos de nombre de archivo de sprite
        $avatar = [
            'base'      => preg_replace('/[^a-z0-9_]/i', '', $_POST['avatar_base'] ?? 'copo'),
            'clothing'  => preg_replace('/[^a-z0-9_]/i', '', $_POST['avatar_clothing'] ?? 'none'),
            'accessory' => preg_replace('/[^a-z0-9_]/i', '', $_POST['avatar_accessory'] ?? 'none'),
        ];

        header('Content-Type: application/json');
        $ok = $this->model->actualizar($idUsuario, $nombre, $x, $y, $direccion, $estado, $avatar, $idSala, $materia, $tiempoEstudio);
        echo json_encode(['ok' => $ok]);
    }

   
    public function listar() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];
        $idSala = isset($_GET['id_sala']) && (int) $_GET['id_sala'] > 0 ? (int) $_GET['id_sala'] : null;
        $jugadores = $this->model->listarActivos($idUsuario, $idSala);

        header('Content-Type: application/json');
        echo json_encode($jugadores);
    }

    
    public function salir() {
        AuthGuard::requireLoginApi();

        $idUsuario = (int) $_SESSION['id_usuario'];

        header('Content-Type: application/json');
        $ok = $this->model->eliminar($idUsuario);
        (new Sala())->eliminarVacias(3);
        echo json_encode(['ok' => $ok]);
    }
}
