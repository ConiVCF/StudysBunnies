<?php
session_start();
require_once __DIR__ . '/../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioModel = new Usuario();
    
    
    $accion = $_POST['auth_action'] ?? 'login';
    
    
    if ($accion === 'register') {
        $nombre = trim($_POST['usuario_reg']);
        $email = trim($_POST['email_reg']);
        $password = $_POST['password_reg'];
        
        if ($usuarioModel->registrar($nombre, $email, $password)) {
            header("Location: ../../index.php?msg=registro_exitoso");
            exit;
        } else {
            header("Location: ../../index.php?error=credenciales_invalidas");
            exit;
        }
    }
    
    
    if ($accion === 'login') {
        
        $nombre = trim($_POST['usuario_reg']); 
        $password = $_POST['password_reg'];
        
        
        $userData = $usuarioModel->login($nombre, $password);
        
        if ($userData) {
            $_SESSION['id_usuario'] = $userData['id_usuario'];
            $_SESSION['nombre'] = $userData['nombre_usuario'];
            $_SESSION['rol'] = $userData['rol'];
            header("Location: ../../index.php");
            exit;
        } else {
            header("Location: ../../index.php?error=credenciales_invalidas");
            exit;
        }
    }
}


if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: ../../index.php");
    exit;
}
?>