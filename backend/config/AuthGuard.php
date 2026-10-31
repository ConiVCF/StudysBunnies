<?php


class AuthGuard {

    const ROL_GENERAL = 0;
    const ROL_ADMIN = 1;
    const ROL_MODERADOR = 2;

    private static function asegurarSesionIniciada() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    
    public static function requireLogin($redirectTo = 'index.php') {
        self::asegurarSesionIniciada();

        if (!isset($_SESSION['id_usuario'])) {
            header("Location: {$redirectTo}?error=sesion_requerida");
            exit;
        }
    }

    
    public static function requireRole($rolRequerido, $redirectTo = 'index.php') {
        self::requireLogin($redirectTo);

        if ((int)($_SESSION['rol'] ?? -1) !== (int)$rolRequerido) {
            header("Location: {$redirectTo}?error=acceso_denegado");
            exit;
        }
    }

    
    public static function requireAnyRole(array $rolesPermitidos, $redirectTo = 'index.php') {
        self::requireLogin($redirectTo);

        if (!in_array((int)($_SESSION['rol'] ?? -1), $rolesPermitidos, true)) {
            header("Location: {$redirectTo}?error=acceso_denegado");
            exit;
        }
    }

    
    public static function requireLoginApi() {
        self::asegurarSesionIniciada();

        if (!isset($_SESSION['id_usuario'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Debes iniciar sesión para usar esta función.']);
            exit;
        }
    }

    
    public static function requireRoleApi($rolRequerido) {
        self::requireLoginApi();

        if ((int)($_SESSION['rol'] ?? -1) !== (int)$rolRequerido) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No tenés permisos para realizar esta acción.']);
            exit;
        }
    }

    
    public static function requireAnyRoleApi(array $rolesPermitidos) {
        self::requireLoginApi();

        if (!in_array((int)($_SESSION['rol'] ?? -1), $rolesPermitidos, true)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No tenés permisos para realizar esta acción.']);
            exit;
        }
    }

    
    public static function tieneRol($rolRequerido) {
        self::asegurarSesionIniciada();
        return isset($_SESSION['rol']) && (int)$_SESSION['rol'] === (int)$rolRequerido;
    }

    
    public static function esStaff() {
        self::asegurarSesionIniciada();
        return in_array((int)($_SESSION['rol'] ?? -1), [self::ROL_ADMIN, self::ROL_MODERADOR], true);
    }

    
    /**
     * Determina si un usuario con rol $rolGestor puede gestionar a un usuario
     * con rol $rolObjetivo, según la jerarquía de permisos del sistema:
     * - Administrador: gestiona Estudiantes y Moderadores (nunca a otros Administradores).
     * - Moderador: gestiona únicamente Estudiantes.
     */
    public static function puedeGestionarRol($rolGestor, $rolObjetivo) {
        $rolGestor = (int) $rolGestor;
        $rolObjetivo = (int) $rolObjetivo;

        if ($rolGestor === self::ROL_ADMIN) {
            return in_array($rolObjetivo, [self::ROL_GENERAL, self::ROL_MODERADOR], true);
        }

        if ($rolGestor === self::ROL_MODERADOR) {
            return $rolObjetivo === self::ROL_GENERAL;
        }

        return false;
    }
}
