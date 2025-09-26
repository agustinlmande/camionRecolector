<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'conductor';

    if ($nombre && $email && $password && $rol) {
        $usuariosFile = __DIR__ . '/usuarios.json';
        $usuarios = [];

        if (file_exists($usuariosFile)) {
            $usuarios = json_decode(file_get_contents($usuariosFile), true) ?? [];
        }

        // Verifica si el email ya existe
        foreach ($usuarios as $usuario) {
            if ($usuario['email'] === $email) {
                echo "<script>alert('El correo ya está registrado');window.location.href='register.html';</script>";
                exit;
            }
        }

        $nuevoUsuario = [
            'nombre' => $nombre,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => $rol,
            'activo' => true
        ];

        $usuarios[] = $nuevoUsuario;
        file_put_contents($usuariosFile, json_encode($usuarios, JSON_PRETTY_PRINT));

        echo "<script>alert('Registro exitoso');window.location.href='login.html';</script>";
        exit;
    }
    echo "<script>alert('Completa todos los campos');window.location.href='register.html';</script>";
}
?>