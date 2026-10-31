<?php
session_start();
$isLoggedIn = isset($_SESSION['id_usuario']);

if (isset($_GET['salas']) && !$isLoggedIn) {
    header('Location: index.php?error=sesion_requerida');
    exit;
}

$mostrarMenu = !$isLoggedIn || isset($_GET['menu']);
$mostrarSalas = $isLoggedIn && !$mostrarMenu;
$roomParam = isset($_GET['room']) ? trim((string)$_GET['room']) : '';
$mostrarSalaActiva = $mostrarSalas && $roomParam !== '';
$paginaActual = 'inicio';
$tituloPagina = '';
$esAdmin = (int)($_SESSION['rol'] ?? 0) === 1;
?>

<!DOCTYPE html>
<html lang="es" class="<?php echo $mostrarMenu ? 'is-home-page' : 'is-game-page'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study's Bunnies</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/game.css">
    <link rel="stylesheet" href="css/ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>
<body class="<?php echo $mostrarMenu ? 'is-home-page' : 'is-game-page'; ?>">

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'registro_exitoso'): ?>
        <div style="position:absolute; z-index:9999; top:20px; left:50%; transform:translateX(-50%); background:#8fbc8f; padding:15px; border:4px solid #2c1a16; font-family:'Press Start 2P'; font-size:10px; color:#2c1a16;">&iexcl;REGISTRO EXITOSO! INICIA SESION.</div>
    <?php endif; ?>
    
    <?php
        $erroresConocidos = [
            'credenciales_invalidas' => 'ERROR: DATOS INCORRECTOS.',
            'sesion_requerida'       => 'DEBES INICIAR SESION PARA CONTINUAR.',
            'acceso_denegado'        => 'NO TENES PERMISOS PARA ACCEDER A ESA PAGINA.',
        ];
        if (isset($_GET['error']) && isset($erroresConocidos[$_GET['error']])):
    ?>
        <div style="position:absolute; z-index:9999; top:20px; left:50%; transform:translateX(-50%); background:#e57373; padding:15px; border:4px solid #2c1a16; font-family:'Press Start 2P'; font-size:10px; color:#2c1a16;"><?php echo $erroresConocidos[$_GET['error']]; ?></div>
    <?php endif; ?>

    <div id="main-menu-view" class="screen-home <?php echo $mostrarMenu ? '' : 'hidden'; ?>">
        <?php require __DIR__ . '/includes/header.php'; ?>

        <div class="top-left-nav">
            <img src="assets/img/boton-sonido.png" id="btn-musica-menu" class="btn-sonido-img" alt="Musica" title="Musica del menu">
        </div>

        <div class="top-right-nav">
            <button type="button" id="login-btn-nav" class="btn-pixel top-nav-btn">
                <span id="btn-usuario-texto">Iniciar Sesion</span>
            </button>

            <div id="user-account-nav" class="user-account-nav hidden">
                <div class="user-account-nav__profile">
                    <img id="user-account-avatar" src="assets/sprites/bases/copo_idle.gif" alt="Avatar del usuario" class="user-account-nav__avatar">
                    <span id="user-account-name" class="user-account-nav__name">Usuario</span>
                </div>
                <a href="backend/controllers/AuthController.php?action=logout" id="logout-btn-nav" class="btn-pixel top-nav-btn top-nav-btn--danger">Cerrar Sesion</a>
            </div>
        </div>

        <section class="home-hero" aria-label="Inicio de Bunny World">
            <img src="assets/img/Titulo.gif" alt="Study's Bunnies" class="titulo-animado">

            <div class="home-content">
                <img src="assets/img/IrAEstudiar.png" alt="Ir a Estudiar" id="btn-start-game" class="btn-start-img">
            </div>
        </section>

        <main class="home-sections">
            <section class="home-section home-section--intro" id="quienes-somos">
                <div class="container home-section__grid">
                    <div>
                        <span class="home-section__eyebrow">Proyecto academico</span>
                        <h1>Quienes somos</h1>
                    </div>
                    <div class="home-section__copy">
                        <p>Study'sBunnies es una aplicacion de estudio gamificada pensada para estudiantes que necesitan organizar tareas, sostener rutinas y mantener la motivacion sin perder un ambiente amable.</p>
                        <p>El objetivo es transformar el seguimiento academico en una experiencia visual y cercana: crear un conejito, avanzar con misiones, usar sesiones Pomodoro y recibir acompanamiento del Profesor Michi cuando una actividad se vuelve dificil.</p>
                        <p>Busca resolver la dispersion diaria que aparece al estudiar: tareas sueltas, falta de continuidad, poca claridad sobre el progreso y momentos en los que una ayuda rapida puede destrabar el aprendizaje.</p>
                        <div class="about-developers">
                            <article>
                                <strong>Franco</strong>
                                <span>Desarrollo, integracion y experiencia de juego.</span>
                            </article>
                            <article>
                                <strong>Coti</strong>
                                <span>Diseno visual, contenido y experiencia de usuario.</span>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section class="home-section home-section--developers">
                <div class="container">
                    <div class="home-section__heading">
                        <span class="home-section__eyebrow">Equipo</span>
                        <h2>Desarrolladores</h2>
                    </div>
                    <div class="developer-grid">
                        <article class="developer-card">
                            <div class="developer-avatar" aria-hidden="true">
                                <img src="assets/img/franco.png" alt="Franco">
                            </div>
                            <h3>Franco</h3>
                            <p>Desarrollo, integracion y experiencia de juego.</p>
                        </article>
                        <article class="developer-card">
                            <div class="developer-avatar developer-avatar--warm" aria-hidden="true">
                                <img src="assets/img/coti.png" alt="Coti">
                            </div>
                            <h3>Coti</h3>
                            <p>Diseno visual, contenido y experiencia de usuario.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="home-section home-section--social">
                <div class="container social-section">
                    <div class="home-section__heading">
                        <span class="home-section__eyebrow">Comunidad</span>
                        <h2>Redes sociales</h2>
                    </div>
                    <div class="social-links" aria-label="Redes sociales de demostracion">
                        <a href="#" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="5"></rect><circle cx="12" cy="12" r="3.5"></circle><circle cx="17" cy="7" r="1"></circle></svg>
                            <span>Instagram</span>
                        </a>
                        <a href="#" aria-label="YouTube">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5c0-1.4 1.1-2.5 2.5-2.5h11C18.9 6 20 7.1 20 8.5v7c0 1.4-1.1 2.5-2.5 2.5h-11C5.1 18 4 16.9 4 15.5v-7z"></path><path d="M10 9l5 3-5 3V9z"></path></svg>
                            <span>YouTube</span>
                        </a>
                        <a href="#" aria-label="Discord">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7.5A13 13 0 0 1 12 6a13 13 0 0 1 5 1.5l1.2 8.2A9.5 9.5 0 0 1 14.5 17l-.7-1.1a6 6 0 0 1-3.6 0L9.5 17a9.5 9.5 0 0 1-3.7-1.3L7 7.5z"></path><circle cx="9.5" cy="12" r="1"></circle><circle cx="14.5" cy="12" r="1"></circle></svg>
                            <span>Discord</span>
                        </a>
                    </div>
                </div>
            </section>

            <section class="home-section home-section--video">
                <div class="container video-section">
                    <div class="home-section__heading">
                        <span class="home-section__eyebrow">Presentacion</span>
                        <h2>Video del proyecto</h2>
                        <p>Mira el video de presentacion de Study's Bunnies.</p>
                    </div>
                    <div class="video-placeholder">
                        <iframe src="https://drive.google.com/file/d/1srzVvRDS7aWZU5bLk8iROeZ9k4Knm0La/preview" title="Video de presentacion de Study Bunny's" allow="autoplay" allowfullscreen></iframe>
                    </div>
                </div>
            </section>
        </main>

        <div id="login-modal" class="modal-overlay hidden">
            <div class="login-wrapper" style="text-align: center; position: relative;">
                <button id="btn-close-login" class="close-btn" style="position: absolute; top: -10px; right: -40px; z-index: 20;">X</button>
                
                <form action="backend/controllers/AuthController.php" method="POST" id="login-box" class="login-box-img registro">
                    
                    <input type="hidden" name="auth_action" id="auth_action" value="register">
                    
                    <input type="text" name="usuario_reg" id="reg-user" placeholder="Usuario" class="input-overlay user-pos" autocomplete="off" required>
                    
                    <input type="email" name="email_reg" id="reg-email" placeholder="Email" class="input-overlay email-pos" autocomplete="off" required>
                    
                    <input type="password" name="password_reg" id="reg-pass" placeholder="Contrasena" class="input-overlay pass-pos" autocomplete="off" required>
                    
                    <button type="button" class="btn-eye-hitbox" id="btn-toggle-pass"></button>
                    
                    <button type="submit" class="btn-hitbox" id="btn-submit-auth"></button>
                </form>
                <button id="toggle-link" class="btn-link">Ya tienes cuenta? Entra aqui</button>
            </div>
        </div>

        <?php require __DIR__ . '/includes/footer.php'; ?>

    </div>

        <div id="game-view" class="<?php echo $mostrarSalas ? '' : 'hidden'; ?>">
            <?php
                $headerModo = 'sala';
                $tituloPagina = '';
                require __DIR__ . '/includes/header.php';
                $headerModo = 'normal';
            ?>
            
        <section id="room-selector" class="room-selector <?php echo $mostrarSalaActiva ? 'hidden' : ''; ?>">
            <div class="room-selector__panel">
                <span class="room-selector__eyebrow">Explorador de salas</span>
                <h1>Selecciona una sala de estudio</h1>
                <p>Elegi una sala publica o entra a una privada con sus datos de acceso.</p>

                <div class="room-grid">
                    <button type="button" class="room-card selected" data-room="biblioteca" data-subject="Programacion">
                        <span class="room-card__icon">&#128218;</span>
                        <strong>Biblioteca</strong>
                        <small>Programacion</small>
                    </button>
                    <button type="button" class="room-card" data-room="aula" data-subject="Base de Datos">
                        <span class="room-card__icon">&#127979;</span>
                        <strong>Aula</strong>
                        <small>Base de Datos</small>
                    </button>
                    <button type="button" class="room-card" data-room="cafeteria" data-subject="Metodologia">
                        <span class="room-card__icon">&#9749;</span>
                        <strong>Cafeteria</strong>
                        <small>Metodologia</small>
                    </button>
                    <button type="button" class="room-card" data-room="silenciosa" data-subject="Libre">
                        <span class="room-card__icon">&#127769;</span>
                        <strong>Sala silenciosa</strong>
                        <small>Libre</small>
                    </button>
                </div>

                <div class="private-room-panel">
                    <div class="private-room-panel__title">
                        <span>&#128274;</span>
                        <strong>Explorar salas</strong>
                    </div>
                    <div class="private-room-layout">
                        <div class="private-room-create">
                            <span class="private-room-section-title">Crear sala</span>
                            <form id="private-room-form" class="private-room-form">
                                <input type="text" id="private-room-name" maxlength="80" placeholder="Nombre de la sala">
                                <select id="private-room-privacy">
                                    <option value="privada">Privada</option>
                                    <option value="publica">Publica</option>
                                </select>
                                <input type="password" id="private-room-password" maxlength="60" placeholder="Contrasena privada">
                                <button type="submit" class="btn-pixel-small">Crear sala</button>
                            </form>
                        </div>
                        <div class="private-room-browse">
                            <span class="private-room-section-title">Salas disponibles</span>
                            <input type="text" id="private-room-search" class="private-room-search" placeholder="Buscar sala..." autocomplete="off">
                            <div id="private-room-list" class="private-room-list"></div>
                            <form id="private-room-access-form" class="private-access-form">
                                <input type="number" id="private-room-access-id" min="1" placeholder="ID sala privada">
                                <input type="password" id="private-room-access-password" maxlength="60" placeholder="Contrasena">
                                <button type="submit" class="btn-pixel-small">Ingresar</button>
                                <span id="private-room-access-status" class="private-access-status"></span>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="room-selector__footer">
                    <div>
                        <span>Sala elegida</span>
                        <strong id="selected-room-label">Biblioteca - Programacion</strong>
                    </div>
                    <button type="button" id="btn-enter-room" class="btn-pixel">Entrar a estudiar</button>
                </div>
            </div>
        </section>

        <div id="game-stage" class="<?php echo $mostrarSalaActiva ? '' : 'hidden'; ?>">
        <div id="game-container">
            <div id="world-container">
                <img id="bg-room" src="assets/maps/Untitled_05-06-2026_04-48-51.gif" alt="Fondo">
                
                <img id="profesor-gatito" src="assets/sprites/Proffesor/gatito.gif" alt="Profesor Gatito">
                
                
            </div>
            <div id="lighting-overlay"></div>
        </div>

        <main id="ui-layer">
            <header class="top-bar">
                <div class="user-stats">
                    <div class="level-badge">LVL 1</div>
                    <div class="exp-bar-container">
                        <div class="exp-bar"><div class="exp-fill" style="width: 0%;"></div></div>
                        <div class="exp-text">0 / 100 EXP</div>
                    </div>
                </div>
                
                <div class="timer-container">
                    <div id="timer-mode-indicator" class="mode-label">Modo: Estudio</div>
                    <div class="pomodoro-widget" id="timerDisplay">25:00</div>
                    <div class="timer-controls">
                        <button id="btn-timer-setup" class="btn-pixel" title="Configurar">&#9881;</button>
                        <button id="btn-start-timer" class="btn-pixel" title="Iniciar">&#9654;</button>
                        <button id="btn-pause-timer" class="btn-pixel" title="Pausar">&#9208;</button>
                        <button id="btn-reset-timer" class="btn-pixel" title="Reiniciar">&#8635;</button>
                    </div>
                </div>
            </header>

            <div id="multiplayer-panel" class="online-users-widget">
                <div class="online-header">&#128994; Sala de Estudio (<span id="online-count">1</span>)</div>
                <ul id="online-list" class="online-list"></ul>
            </div>

            <aside id="active-room-info" class="active-room-info hidden">
                <div>
                    <span>Nombre</span>
                    <strong id="active-room-name">Biblioteca</strong>
                </div>
                <div>
                    <span>Tipo</span>
                    <strong id="active-room-type">Publica</strong>
                </div>
                <div>
                    <span>ID</span>
                    <strong id="active-room-id">-</strong>
                </div>
                <div id="active-room-private-data" class="active-room-private-data hidden">
                    <span>Contrasena</span>
                    <button type="button" id="active-room-password" class="room-password-chip" aria-label="Ver contrasena de la sala">********</button>
                </div>
                <button type="button" id="copy-room-data" class="room-copy-btn" aria-label="Copiar informacion de la sala" title="Copiar informacion">Copiar</button>
            </aside>

            <div class="action-menu">
                <button id="btn-inventory" class="btn-pixel btn-action">Ropero</button>
                <button id="btn-missions" class="btn-pixel btn-action">Pizarra</button>
                <button id="btn-calendar" class="btn-pixel btn-action">Agenda</button>
                <button id="btn-room-chat" class="btn-pixel btn-action">Chat</button>
            </div>

            <div id="timer-setup-modal" class="modal-overlay hidden">
                <div class="modal-content pixel-border setup-modal">
                    
                    <header class="modal-header" style="position: relative;">
                        <button id="btn-close-timer" 
                            class="close-btn" style="
                                color: #ff4c4c; 
                                position: absolute; 
                                right: 15px; 
                                top: 8px;
                                text-shadow: 1px 1px 0px #555;">X</button>
                        <h2>Planifica tu Sesion</h2>
                    </header>
                    <div class="modal-body timer-setup-body">
                        <p class="setup-text">Cuanto tiempo te concentraras hoy?</p>
                        <div class="timer-settings-large">
                            <label>Estudio: <input type="number" id="input-study" min="1" max="120" value="25"> min</label>
                            <label>Descanso: <input type="number" id="input-break" min="1" max="15" value="5"> min</label>
                        </div>
                    </div>
                    <footer class="modal-footer">
                        <button id="btn-confirm-timer" class="btn-pixel save-btn">Comenzar Mision</button>
                    </footer>
                </div>
            </div>

            <div id="inventory-modal" class="modal-overlay hidden">
                <div class="modal-content pixel-border modal-large">
                    <header class="modal-header">
                        <h2>Personaliza tu Avatar</h2>
                        <button id="btn-close-inventory" class="close-btn">X</button>
                    </header>
                    <div class="modal-body with-preview">
                        <div class="preview-panel">
                            <div class="preview-container">
                                <img id="preview-base" src="assets/sprites/bases/copo_idle.gif" alt="Conejo">
                                <img id="preview-clothing" src="" alt="Ropa" class="hidden">
                                <img id="preview-accessory" src="" alt="Accesorio" class="hidden">
                            </div>
                        </div>
                        <div class="options-panel">
                            <div class="customization-section">
                                <h3>Color del Conejo</h3>
                                <div class="color-options" id="color-picker">
                                    <button class="color-btn" data-base="copo" style="background-color: #FFFFFF;" title="Copo"></button>
                                    <button class="color-btn" data-base="mochi" style="background-color: #333333;" title="Mochi"></button> 
                                    <button class="color-btn" data-base="canela" style="background-color: #A0522D;" title="Canela"></button> 
                                </div>
                            </div>
                            <div class="customization-section">
                                <h3>Ropa</h3>
                                <div class="item-grid" id="clothing-picker">
                                    <button class="item-btn" data-item="none">Nada</button>
                                    <button class="item-btn" data-item="conjunto1">Camisa Roja</button>
                                    <button class="item-btn" data-item="cozy_sweater">Sueter Rojo</button>
                                </div>
                            </div>
                            <div class="customization-section">
                                <h3>Accesorios</h3>
                                <div class="item-grid" id="accessory-picker">
                                    <button class="item-btn" data-item="none">Nada</button>
                                    <button class="item-btn" data-item="round_glasses">Lentes Redondos</button>
                                    <button class="item-btn" data-item="sombrero">Sombrero</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <footer class="modal-footer">
                        <button id="btn-save-inventory" class="btn-pixel save-btn">Guardar Cambios</button>
                    </footer>
                </div>
            </div>

            <div id="mission-modal" class="modal-overlay hidden">
                <div class="modal-content pixel-border corkboard-modal">
                    <header class="modal-header wood-header">
                        <h2>Pizarra de Misiones</h2>
                        <button id="btn-close-missions" class="close-btn">X</button>
                    </header>
                    <div class="modal-body">
                        <div class="add-mission-wrapper">
                            <input type="text" id="new-mission-input" placeholder="Anadir nueva tarea academica..." autocomplete="off">
                            <button id="btn-add-mission" class="btn-pixel-small">Agregar</button>
                        </div>

                        <div class="add-mission-wrapper">
                            <input type="text" id="mission-search-input" placeholder="Buscar en mis misiones..." autocomplete="off">
                            <button id="btn-search-mission" class="btn-pixel-small">Buscar</button>
                            <button id="btn-clear-search-mission" class="btn-pixel-small">Ver todas</button>
                        </div>

                        <div class="mission-stats">
                            <span id="mission-counter">0/0 Completadas</span>
                        </div>

                        <ul id="mission-list" class="mission-list"></ul>
                    </div>
                </div>
            </div>

            <div id="calendar-modal" class="modal-overlay hidden">
                <div class="modal-content pixel-border agenda-modal">
                    <header class="modal-header wood-header">
                        <h2>Agenda Academica</h2>
                        <button id="btn-close-calendar" class="close-btn">X</button>
                    </header>
                    <div class="modal-body with-preview">
                        <div class="agenda-form-panel">
                            <h3>Nuevo Evento</h3>
                            <input type="text" id="event-title" placeholder="Ej: Parcial Probabilidad y Estadistica" class="agenda-input">
                            
                            <div class="agenda-row">
                                <select id="event-type" class="agenda-input">
                                    <option value="Examen">Examen / Parcial</option>
                                    <option value="Final">Final</option>
                                    <option value="TP">Entrega de TP</option>
                                    <option value="Proyecto">Proyecto</option>
                                    <option value="Personal">Objetivo Personal</option>
                                </select>
                                <input type="date" id="event-date" class="agenda-input">
                            </div>

                            <div class="reminders-config">
                                <span>Recordatorios por Email:</span>
                                <div class="checkbox-grid">
                                    <label><input type="checkbox" value="30"> 30 dias</label>
                                    <label><input type="checkbox" value="15"> 15 dias</label>
                                    <label><input type="checkbox" value="7" checked> 7 dias</label>
                                    <label><input type="checkbox" value="3" checked> 3 dias</label>
                                    <label><input type="checkbox" value="1" checked> 24 hs</label>
                                    <label><input type="checkbox" value="0" checked> Mismo dia</label>
                                </div>
                            </div>
                            <button id="btn-add-event" class="btn-pixel save-btn" style="width: 100%; margin-top: 10px;">Guardar Fecha</button>
                        </div>
                        
                        <div class="agenda-list-panel">
                            <h3>Proximos Eventos</h3>
                            <ul id="event-list" class="event-list"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="ai-chat-box hidden" id="aiChat">
                <div class="ai-resize-handle" id="ai-resize-handle" aria-hidden="true"></div>
                <div class="chat-header">
                    <span>Profesor Michi</span>
                    <div class="text-size-controls">
                        <button id="btn-txt-minus" class="txt-btn" title="Achicar letra">A-</button>
                        <input type="range" id="slider-txt-size" min="8" max="24" value="10" step="1" title="Tamano de letra">
                        <button id="btn-txt-plus" class="txt-btn" title="Agrandar letra">A+</button>
                    </div>
                </div>
                <div class="chat-history" id="chatHistory"><p class="ai-msg">Miau! Que tema vamos a estudiar en esta sesion?</p></div>
                <div id="attachment-preview" class="hidden"><span id="attachment-name">archivo.pdf</span><button id="btn-remove-attachment" title="Quitar archivo">X</button></div>
                <div class="chat-input-wrapper">
                    <button id="btn-attach" class="btn-pixel-small" title="Subir archivo (PDF, JPG, PNG)">&#128274;</button>
                    <button id="btn-quiz" class="btn-pixel-small" title="Ponme a prueba con una pregunta">&#128274;</button>
                    <input type="file" id="aiAttachment" accept="image/png, image/jpeg, application/pdf" class="hidden">
                    <input type="text" id="aiInput" placeholder="Pregunta algo al profesor..." autocomplete="off">
                </div>
            </aside>

            <aside class="room-chat-box hidden" id="roomChat">
                <div class="chat-header">
                    <span id="room-chat-title">Chat de sala</span>
                    <div class="room-chat-controls">
                        <button id="btn-minimize-room-chat" class="txt-btn" type="button" title="Minimizar">_</button>
                        <button id="btn-close-room-chat" class="txt-btn" type="button" title="Cerrar">X</button>
                    </div>
                </div>
                <div class="chat-history" id="roomChatHistory">
                    <p class="room-chat-empty">Entra a una sala para conversar.</p>
                </div>
                <form class="chat-input-wrapper" id="room-chat-form">
                    <input type="text" id="roomChatInput" maxlength="500" placeholder="Escribi en la sala..." autocomplete="off">
                    <button class="btn-pixel-small" type="submit">Enviar</button>
                </form>
            </aside>

            <div class="audio-widget">
                <div id="audio-panel" class="environment-controls hidden">
                    <div class="control-group"><button id="btn-music">Lofi</button><input type="range" id="vol-music" min="0" max="1" step="0.01" value="0.3" title="Volumen Lofi"></div>
                    <div class="control-group"><button id="btn-birds">Aves</button><input type="range" id="vol-birds" min="0" max="1" step="0.01" value="0.6" title="Volumen Aves"></div>
                    <div class="control-group"><button id="btn-typing">Escribiendo</button><input type="range" id="vol-typing" min="0" max="1" step="0.01" value="0.6" title="Volumen Escritura"></div>
                    <div class="control-group"><span class="control-label">Efectos</span><input type="range" id="vol-sfx" min="0" max="1" step="0.01" value="0.6" title="Volumen Pasos"></div>
                </div>
                <button id="btn-toggle-audio" class="btn-pixel">Ambiente</button>
            </div>

            <div class="game-room-scroll-spacer" aria-hidden="true"></div>

            <footer class="site-footer game-room-footer">
                <div class="container site-footer__grid">
                    <section class="site-footer__section">
                        <h2>Study'sBunnies</h2>
                        <p>Study'sBunnies combina estudio, misiones academicas, Pomodoro y acompanamiento con IA en una sala pixel-art.</p>
                    </section>

                    <section class="site-footer__section">
                        <h2>Contacto</h2>
                        <p>cozystudy@bunnyworld.com</p>
                        <p>Buenos Aires, Argentina</p>
                    </section>

                    <section class="site-footer__section">
                        <h2>Soporte</h2>
                        <a href="contacto.php">Formulario de ayuda</a>
                        <a href="index.php?menu=1#quienes-somos">Quienes somos</a>
                    </section>

                    <section class="site-footer__section">
                        <h2>Redes</h2>
                        <a href="#" aria-label="Instagram">Instagram</a>
                        <a href="#" aria-label="Facebook">Facebook</a>
                        <a href="#" aria-label="WhatsApp">WhatsApp</a>
                        <a href="#" aria-label="X Twitter">X (Twitter)</a>
                    </section>

                    <section class="site-footer__section">
                        <h2>Creditos</h2>
                        <p>Proyecto academico de Desarrollo Web.</p>
                        <div class="site-footer__social">
                            <span>UTN</span>
                            <span>Haedo</span>
                            <span>Programacion</span>
                        </div>
                    </section>

                    <section class="site-footer__section">
                        <h2>Desarrolladores</h2>
                        <p>Franco - Desarrollo e integracion.</p>
                        <p>Coti - Diseno visual y contenido.</p>
                    </section>
                </div>

                <div class="container site-footer__bottom">
                    <span>&copy; <?php echo date('Y'); ?> Study's Bunnies</span>
                    <span>Hecho para aprender mejor, una mision a la vez.</span>
                </div>
            </footer>

        </main>
        </div>
    </div>

    <script>
        window.SESION_PHP = {
            activa: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            nombre: <?php echo json_encode($_SESSION['nombre'] ?? null); ?>,
            rol: <?php echo json_encode($_SESSION['rol'] ?? null); ?>,
            idUsuario: <?php echo json_encode($_SESSION['id_usuario'] ?? null); ?>
        };
        window.INITIAL_VIEW = {
            menu: <?php echo $mostrarMenu ? 'true' : 'false'; ?>,
            salas: <?php echo $mostrarSalas ? 'true' : 'false'; ?>,
            room: <?php echo json_encode($mostrarSalaActiva ? $roomParam : null); ?>
        };
    </script>
    <script type="module" src="js/core/GameManager.js"></script>
    <script type="module" src="js/systems/SalaManager.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleLink = document.getElementById('toggle-link');
            const authAction = document.getElementById('auth_action');
            const emailInput = document.getElementById('reg-email');

            if (toggleLink) {
                toggleLink.addEventListener('click', (e) => {
                    e.preventDefault(); 

                    if (authAction.value === 'register') {
                        
                        authAction.value = 'login';
                        emailInput.removeAttribute('required'); 
                        emailInput.style.opacity = '0'; 
                        emailInput.style.pointerEvents = 'none'; 
                        toggleLink.innerText = 'No tienes cuenta? Registrate aqui';
                    } else {
                        authAction.value = 'register';
                        emailInput.setAttribute('required', 'required'); 
                        emailInput.style.opacity = '1'; 
                        emailInput.style.pointerEvents = 'auto'; 
                        toggleLink.innerText = 'Ya tienes cuenta? Entra aqui';
                    }
                });
            }

            // Navegacion "Quienes somos": logica unica usada tanto por el link
            // del Header como por el/los link(s) del Footer (misma logica de navegacion).
            if (window.location.hash === '#quienes-somos') {
                const quienesSomosSection = document.getElementById('quienes-somos');
                if (quienesSomosSection) {
                    requestAnimationFrame(() => {
                        quienesSomosSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }
            }
        });
    </script>


</body>
</html>
