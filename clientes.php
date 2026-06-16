<?php
session_start();
include "conexion.php";

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($enlace, "DELETE FROM cliente WHERE id_cliente = $id");
    header("Location: clientes.php");
    exit;
}

$resultado = mysqli_query($enlace, "SELECT * FROM cliente ORDER BY id_cliente DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Clientes</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div class="logo"><h2>EL SITIO</h2><p>Administrador</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin.php" class="nav-link"> Panel Principal</a></li>
            <li class="nav-item"><a href="clientes.php" class="nav-link active"> Clientes</a></li>
            <li class="nav-item"><a href="productos.php" class="nav-link"> Productos</a></li>
            <li class="nav-item"><a href="insumos.php" class="nav-link"> Insumos</a></li>
            <li class="nav-item"><a href="domiciliarios.php" class="nav-link"> Domiciliarios</a></li>
            <li class="nav-item"><a href="pedidos.php" class="nav-link"> Pedidos</a></li>
        </ul>
        <div><a href="Login.html" class="logout-btn"> Cerrar Sesión</a></div>
    </aside>
    <main class="main-content">
        <div class="page-header"><h1> Clientes</h1></div>
        <div class="table-container">
            <table><thead><tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Dirección</th><th>Correo</th><th>Acciones</th></tr></thead>
            <tbody><?php while($row=mysqli_fetch_assoc($resultado)): ?>
            <tr><td><?=$row['id_cliente']?></td><td><?=htmlspecialchars($row['nombre'])?></td><td><?=$row['telefono']?></td><td><?=$row['direccion']?></td><td><?=$row['correo']?></td>
            <td><a href="clientes.php?eliminar=<?=$row['id_cliente']?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este cliente?')">Eliminar</a></td></tr>
            <?php endwhile; ?></tbody></table>
        </div>
    </main>
</div>
</body>
</html>