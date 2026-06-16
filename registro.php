<?php
session_start();
include 'conexion.php';

// ========== PROCESAR EL REGISTRO (si viene del formulario) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nombre_cliente = $_POST['nombre_cliente'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $contrasena = $_POST['contrasena'];
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    mysqli_begin_transaction($enlace);

    try {
        // Verificar si el correo ya existe
        $sql_check = "SELECT id_usuario FROM usuario WHERE email = '$correo'";
        $res_check = mysqli_query($enlace, $sql_check);
        
        if (mysqli_num_rows($res_check) > 0) {
            header("Location: registro.php?error=correo_existe");
            exit();
        }
        
        // Insertar en usuario
        $sql_usuario = "INSERT INTO usuario (nombre, email, password, rol) 
                        VALUES ('$nombre_cliente', '$correo', '$contrasena_hash', 'cliente')";
        
        if (!mysqli_query($enlace, $sql_usuario)) {
            throw new Exception("Error al insertar usuario");
        }
        
        $id_usuario = mysqli_insert_id($enlace);
        
        // Insertar en cliente
        $sql_cliente = "INSERT INTO cliente (nombre, telefono, direccion, correo, id_usuario) 
                        VALUES ('$nombre_cliente', '$telefono', '$direccion', '$correo', $id_usuario)";
        
        if (!mysqli_query($enlace, $sql_cliente)) {
            throw new Exception("Error al insertar cliente");
        }
        
        mysqli_commit($enlace);
        
        header("Location: Login.html?registro=exitoso");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($enlace);
        header("Location: registro.php?error=bd");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrarse - El Sitio</title>
<link rel="stylesheet" href="loginEstilo.css">
</head>
<body>
<div class="container">
    <h1>Registro</h1>
    
    <?php
    // Mostrar mensajes de error si existen
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'correo_existe') {
            echo '<div style="background: rgba(255,45,158,0.2); border: 1px solid #ff2d9e; color: #ff2d9e; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 20px;">El correo ya esta registrado</div>';
        } elseif ($_GET['error'] == 'bd') {
            echo '<div style="background: rgba(255,45,158,0.2); border: 1px solid #ff2d9e; color: #ff2d9e; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 20px;">Error en el servidor. Intenta mas tarde.</div>';
        }
    }
    ?>
    
    <form action="registro.php" method="POST">
        <label>Nombres y Apellidos:</label>
        <input type="text" name="nombre_cliente" required>

        <label>Correo Electronico:</label>
        <input type="email" name="correo" required>

        <label>Telefono:</label>
        <input type="text" name="telefono" required>

        <label>Direccion:</label>
        <input type="text" name="direccion" required>

        <label>Contraseña:</label>
        <input type="password" name="contrasena" required>

        <input type="submit" value="Registrarse">
    </form>
    
    <div class="login">
        ¿Ya tienes cuenta? <a href="Login.html">Inicia Sesion</a>
    </div>
</div>
</body>
</html>