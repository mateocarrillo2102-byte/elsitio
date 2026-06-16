<?php
session_start();

$email = $_POST['email'];
$password = $_POST['password'];

include "conexion.php";

if (!$enlace) {
    die("Error de conexión");
}

// Buscar el usuario por email
$sql = "SELECT * FROM usuario WHERE email='$email'";
$resultado = mysqli_query($enlace, $sql);

if (mysqli_num_rows($resultado) > 0) {
    $usuario = mysqli_fetch_assoc($resultado);
        if (password_verify($password, $usuario['password'])) {
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['id_usuario'] = $usuario['id_usuario'];  // ← NUEVO
        $sql_cliente = "SELECT id_cliente FROM cliente WHERE id_usuario = " . $usuario['id_usuario'];
        $res_cliente = mysqli_query($enlace, $sql_cliente);
        if (mysqli_num_rows($res_cliente) > 0) {
            $row_cliente = mysqli_fetch_assoc($res_cliente);
            $_SESSION['id_cliente'] = $row_cliente['id_cliente'];  // ← NUEVO
        }
        if ($usuario['rol'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: Menu.php");
        }
        exit();
    } else {
        // Contraseña incorrecta
        header("Location: login.html?error=1");
        exit();
    }
} else {
    // Usuario no existe
    header("Location: login.html?error=1");
    exit();
}
mysqli_close($enlace);
?>