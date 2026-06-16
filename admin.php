<?php
session_start();
include "conexion.php";

$total_clientes = mysqli_num_rows(mysqli_query($enlace, "SELECT * FROM cliente"));
$total_productos = mysqli_num_rows(mysqli_query($enlace, "SELECT * FROM producto"));
$total_pedidos = mysqli_num_rows(mysqli_query($enlace, "SELECT * FROM pedido"));
$total_insumos = mysqli_num_rows(mysqli_query($enlace, "SELECT * FROM insumo"));
$total_domiciliarios = mysqli_num_rows(mysqli_query($enlace, "SELECT * FROM domiciliario"));

$sql_pedidos = "
    SELECT p.*, c.nombre as cliente_nombre 
    FROM pedido p 
    INNER JOIN cliente c ON p.id_cliente = c.id_cliente 
    ORDER BY p.id_pedido DESC 
    LIMIT 10
";
$resultado_pedidos = mysqli_query($enlace, $sql_pedidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Panel Principal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div class="logo"><h2>EL SITIO</h2><p>Administrador</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin.php" class="nav-link active">📊 Panel Principal</a></li>
            <li class="nav-item"><a href="clientes.php" class="nav-link">👥 Clientes</a></li>
            <li class="nav-item"><a href="productos.php" class="nav-link">🍔 Productos</a></li>
            <li class="nav-item"><a href="insumos.php" class="nav-link">📦 Insumos</a></li>
            <li class="nav-item"><a href="producto_insumos.php" class="nav-link">🔗 Prod. × Insumos</a></li>
            <li class="nav-item"><a href="domiciliarios.php" class="nav-link">🛵 Domiciliarios</a></li>
            <li class="nav-item"><a href="pedidos.php" class="nav-link">📦 Pedidos</a></li>
        </ul>
        <div><a href="login.php" class="logout-btn">🚪 Cerrar Sesión</a></div>
    </aside>
    <main class="main-content">
        <div class="page-header"><h1>Panel Principal</h1></div>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-number"><?= $total_clientes ?></div><div class="stat-label">Clientes</div></div>
            <div class="stat-card"><div class="stat-icon">🍔</div><div class="stat-number"><?= $total_productos ?></div><div class="stat-label">Productos</div></div>
            <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-number"><?= $total_pedidos ?></div><div class="stat-label">Pedidos</div></div>
            <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-number"><?= $total_insumos ?></div><div class="stat-label">Insumos</div></div>
            <div class="stat-card"><div class="stat-icon">🛵</div><div class="stat-number"><?= $total_domiciliarios ?></div><div class="stat-label">Domiciliarios</div></div>
        </div>
        <h2 style="color:#ffe600; margin-bottom:20px;">Últimos Pedidos</h2>
        <div class="table-container">
            <table><thead><tr><th>ID</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody><?php while($row=mysqli_fetch_assoc($resultado_pedidos)): ?>
            <tr><td><?=$row['id_pedido']?></td><td><?=htmlspecialchars($row['cliente_nombre'])?></td><td><?=$row['fecha_pedido']?></td><td>$<?=number_format($row['total'])?></td>
            <td><span class="estado estado-<?=strtolower($row['estado']??'recibido')?>"><?=ucfirst($row['estado']??'Recibido')?></span></td>
            <td><a href="ver_pedido.php?id=<?=$row['id_pedido']?>" class="btn btn-primary btn-sm">Ver</a>
            <a href="pedidos.php?eliminar=<?=$row['id_pedido']?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar?')">Eliminar</a></td></tr>
            <?php endwhile; ?></tbody></table>
        </div>
    </main>
</div>
</body>
</html>