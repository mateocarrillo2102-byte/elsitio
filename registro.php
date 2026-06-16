<?php
include 'conexion.php';
$enlace=mysqli_connect("localhost", "root", "", "dark_kitchen");
$nombre_cliente = $_POST['nombre_cliente'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$contrasena = $_POST['contrasena'];
$contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

mysqli_begin_transaction($enlace);

try {
    // Verificar si el correo ya existe
    $sql_cliente = "SELECT id_cliente FROM cliente WHERE correo = '$correo'";
    $res_cliente = mysqli_query($enlace, $sql_cliente);
    if (mysqli_num_rows($res_cliente) > 0) {
        echo "El cliente ya existe";
    } else {
        $sql_insert_cliente = "INSERT INTO cliente(nombre, telefono, direccion, correo)
        VALUES('$nombre_cliente', '$telefono', '$direccion', '$correo')";

        mysqli_query($enlace, $sql_insert_cliente);

        $sql_insert_usuario = "INSERT INTO usuario(nombre, email, password, rol)
        VALUES('$nombre_cliente', '$correo', '$contrasena_hash', 'cliente')";
        
        mysqli_query($enlace, $sql_insert_usuario);
        mysqli_commit($enlace);
        header("Location: Login.html?registro=exitoso");
        exit();
    }
} catch (Exception $e) {
    mysqli_rollback($enlace);
    echo "Error: " . $e->getMessage();
}
?>