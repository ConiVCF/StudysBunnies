<?php
require_once __DIR__ . '/../backend/config/AuthGuard.php';
$paginaActual = $paginaActual ?? '';
$tituloPagina = $tituloPagina ?? 'Study\'s Bunnies';
$isLoggedIn = $isLoggedIn ?? isset($_SESSION['id_usuario']);
$esAdmin = $esAdmin ?? AuthGuard::esStaff();
$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$headerModo = $headerModo ?? 'normal';
?>

<header class="site-header site-header--<?php echo htmlspecialchars($headerModo, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="container site-header__inner">
        <a class="site-header__brand" href="index.php?menu=1" aria-label="Ir al inicio">
            <span class="site-header__logo">CS</span>
            <strong>Study's Bunnies</strong>
        </a>

        <nav class="site-header__nav" aria-label="Navegacion principal">
            <a class="<?php echo $paginaActual === 'inicio' ? 'active' : ''; ?>" href="index.php?menu=1">Inicio</a>
            <a href="index.php?salas=1#room-selector">Salas</a>
            <a href="index.php?menu=1#quienes-somos">Quienes somos</a>
            <a class="<?php echo $paginaActual === 'contacto' ? 'active' : ''; ?>" href="contacto.php">Soporte</a>
            <?php if ($esAdmin): ?>
                <a class="<?php echo $paginaActual === 'admin' ? 'active' : ''; ?>" href="admin.php">Admin</a>
            <?php endif; ?>
        </nav>

        <div class="site-header__user">
            <?php if ($isLoggedIn): ?>
                <button class="site-user-card" type="button">
                    <span class="site-user-card__avatar">
                        <img src="assets/sprites/bases/copo_idle.gif" alt="">
                    </span>
                    <span class="site-user-card__text">
                        <small>Usuario</small>
                        <strong><?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </span>
                </button>
                <div class="site-user-menu">
                    <a href="index.php?menu=1">Inicio</a>
                    <?php if ($esAdmin): ?>
                        <a href="admin.php">Panel admin</a>
                    <?php endif; ?>
                    <a href="backend/controllers/AuthController.php?action=logout" class="site-logout-link">Cerrar sesion</a>
                </div>
            <?php else: ?>
                <a class="site-login-link" href="index.php">Iniciar sesi&oacute;n</a>
            <?php endif; ?>
        </div>
    </div>
    <script>
    (function () {
        var header = document.currentScript.closest('header');
        if (!header) return;
        var logoutLink = header.querySelector('.site-logout-link');
        var userPanel = header.querySelector('.site-header__user');
        if (!logoutLink || !userPanel) return;

        logoutLink.addEventListener('click', function () {
            
            userPanel.innerHTML = '<a class="site-login-link" href="index.php">Iniciar sesi\u00f3n</a>';
        });
    })();
    </script>
</header>

<?php if ($tituloPagina !== ''): ?>
    <section class="page-heading container">
        <h1><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></h1>
    </section>
<?php endif; ?>
