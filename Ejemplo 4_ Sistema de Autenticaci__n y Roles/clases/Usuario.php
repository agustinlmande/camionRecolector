// clases/Usuario.php
class Usuario {
    private int $id;
    private string $email;
    private string $password_hash;
    private string $rol; // 'admin', 'supervisor', 'conductor'
    private string $nombre;
    private bool $activo;

    public function __construct(string $email, string $password, string $rol, string $nombre) {
        $this->id = rand(1000, 9999);
        $this->email = $email;
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
        $this->setRol($rol);
        $this->nombre = $nombre;
        $this->activo = true;
    }

    public function verificarPermiso(string $accion): bool {
        $permisos = [
            'admin' => ['crear_camion', 'eliminar_camion', 'ver_reportes', 'gestionar_usuarios'],
            'supervisor' => ['crear_camion', 'ver_reportes', 'asignar_rutas'],
            'conductor' => ['ver_rutas', 'registrar_recoleccion']
        ];

        return in_array($accion, $permisos[$this->rol] ?? []);
    }

    public function puedeEditarCamion(Camion $camion): bool {
        return $this->rol === 'admin' || 
               ($this->rol === 'supervisor' && $camion->getConductor() === $this->nombre);
    }
}

// clases/Auth.php
class Auth {
    private array $usuarios;

    public function login(string $email, string $password): ?Usuario {
        $usuario = $this->buscarUsuarioPorEmail($email);
        
        if ($usuario && password_verify($password, $usuario->getPasswordHash())) {
            $_SESSION['usuario_id'] = $usuario->getId();
            return $usuario;
        }
        
        return null;
    }

    public function middleware(string $rolRequerido): void {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /login.php');
            exit;
        }

        $usuario = $this->obtenerUsuarioActual();
        if (!$usuario->verificarPermiso($rolRequerido)) {
            throw new Exception('Acceso denegado');
        }
    }
}