<?php


require_once __DIR__ . '/backend/config/AuthGuard.php';


AuthGuard::requireAnyRole([AuthGuard::ROL_ADMIN, AuthGuard::ROL_MODERADOR]);
$rolActorPanel = (int) ($_SESSION['rol'] ?? -1);
$paginaActual = 'admin';
$tituloPagina = $rolActorPanel === AuthGuard::ROL_MODERADOR ? 'Panel de Moderador' : 'Panel de Administrador';
$isLoggedIn = true;
$esAdmin = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study's Bunnies - Panel de Administrador</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php require __DIR__ . '/includes/header.php'; ?>

    <main class="container admin-dashboard">
        <section class="admin-hero">
            <div>
                <span class="admin-eyebrow">Panel de control</span>
                <h1><?php echo $rolActorPanel === AuthGuard::ROL_MODERADOR ? 'Moderación' : 'Administración'; ?> Study's Bunnies</h1>
                <p>Gestioná estudiantes, misiones y seguimiento académico desde un espacio más claro y ordenado.</p>
            </div>
            <div class="admin-summary">
                <article>
                    <span id="admin-total-usuarios">0</span>
                    <small>Usuarios</small>
                </article>
                <article>
                    <span id="admin-total-misiones">0</span>
                    <small>Misiones</small>
                </article>
                <article>
                    <span id="admin-total-eventos">0</span>
                    <small>Eventos</small>
                </article>
            </div>
        </section>

        <div id="admin-mensaje"></div>

        <section class="admin-panel admin-panel--users admin-panel--wide">
                <div class="admin-panel__header">
                    <span class="panel-icon">👥</span>
                    <div>
                        <h2>Gestor de usuarios</h2>
                        <p>Usuarios, tareas y eventos en una única interfaz.</p>
                    </div>
                </div>

                <div class="admin-user-tools">
                    <label class="admin-search-box">
                        <span>Buscar por nombre</span>
                        <input type="text" id="admin-user-search" placeholder="Escribí un nombre..." autocomplete="off">
                    </label>
                </div>

                <div id="admin-users-body" class="admin-user-cards" data-rol-actor="<?php echo $rolActorPanel; ?>"></div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="js/systems/AdminPanel.js"></script>
</body>
</html>
