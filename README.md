# 🐰 Cozy Study - Bunny World

Proyecto Integrador (Segundo Parcial) - Desarrollo Web.
Sala de estudio gamificada: armá tu conejito, organizá tus tareas académicas
en una pizarra de misiones, usá un temporizador Pomodoro, llevá una agenda
de eventos y pedile ayuda a un tutor con IA cuando te trabes con algo.

## Tecnologías utilizadas

- HTML5 + CSS3 (Flexbox)
- JavaScript (ES6, POO, módulos)
- PHP (POO, patrón MVC)
- MySQL (con relaciones entre tablas y `JOIN`)
- XAMPP (Apache + MySQL local)
- Sesiones PHP + control de acceso por rol
- Contraseñas encriptadas (`password_hash` / `password_verify`)
- Git / GitHub

## Estructura del proyecto

```
Proyecto_3.0/
├── assets/            # imágenes, sprites, audio, mapas
├── backend/
│   ├── config/        # conexión a BD, configuración, control de acceso
│   ├── controllers/   # controladores MVC
│   └── models/        # modelos MVC (Usuario, Mision, Contacto)
├── css/
├── database/          # script SQL para crear e inicializar la BD
├── js/
│   ├── core/          # GameManager (orquesta todo el juego)
│   ├── entities/      # Player, AITeacher
│   ├── scenes/        # StudyRoom
│   ├── systems/       # Timer, Misiones, Calendario, Inventario, Audio, API, Admin
│   └── utils/         # Physics, Sanitizer
├── index.php          # página principal (home, login, registro, juego)
├── contacto.php        # página de contacto (form + mapa)
├── admin.php           # panel de Administrador (ABM de misiones de todos los usuarios)
└── *.php (raíz)        # endpoints puntuales (guardar/listar/actualizar/eliminar/buscar misiones)
```

## Instalación (XAMPP)

1. Copiar la carpeta `Proyecto_3.0` dentro de `htdocs`.
2. Crear la base de datos importando `database/cozy_study_limpio.sql` en phpMyAdmin.
3. Copiar `backend/config/config.example.php` como `backend/config/config.php`:
   ```
   cp backend/config/config.example.php backend/config/config.php
   ```
   y completar ahí tus propios datos:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: credenciales de tu MySQL local
     (con XAMPP por defecto son `localhost` / `cozy_study` / `root` / vacío).
   - `GEMINI_API_KEY`: tu propia API key de Gemini (para el chat con el
     Profesor Michi), gratuita en https://aistudio.google.com/apikey.

   `config.php` está excluido en `.gitignore` y nunca debe subirse al repo,
   ya que contiene credenciales y una API key sensibles.
4. Levantar Apache y MySQL desde el panel de XAMPP.
5. Entrar a `http://localhost/Proyecto_3.0/index.php`.

## Usuarios de prueba

| Rol       | Usuario         | Contraseña       |
|-----------|-----------------|------------------|
| Admin     | Profesor Admin  | Admin123!        |
| General   | juan            | Estudiante123!   |
| General   | maria           | Estudiante123!   |

## Roles y funcionalidades

- **Usuario General**: gestiona sus propias misiones (Altas, Bajas, Modificaciones)
  contra MySQL, con buscador propio.
- **Administrador**: panel con ABM de las misiones de **todos** los usuarios
  (`admin.php`), con buscador por tarea o por usuario.

## Seguridad

- Contraseñas encriptadas con `password_hash` / `password_verify`.
- Consultas SQL con *prepared statements* en todos los modelos.
- Control de acceso por sesión y por rol (`AuthGuard`), tanto para páginas
  completas como para endpoints AJAX.
- Textos de usuario escapados antes de insertarse en el DOM (`Sanitizer`),
  para prevenir XSS en la pizarra de misiones, el calendario y el chat de IA.
- Las credenciales de la base de datos y la API key de terceros (Gemini)
  viven únicamente en el servidor, centralizadas en `backend/config/config.php`
  (excluido de git vía `.gitignore`, se genera a partir de `config.example.php`), nunca en el frontend.


  <img width="1280" height="678" alt="image" src="https://github.com/user-attachments/assets/c62afe90-3e51-4000-b5b7-9359e8108f80" />

