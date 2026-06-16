<?php
session_start();
include "conexion.php";

$id_pedido = $_GET['id'];
$sql_pedido = "SELECT p.*, u.nombre as cliente_nombre, u.email 
               FROM pedido p 
               INNER JOIN usuario u ON p.id_usuario = u.id_usuario 
               WHERE p.id_pedido = $id_pedido";
$pedido = mysqli_fetch_assoc(mysqli_query($enlace, $sql_pedido));

$sql_detalle = "SELECT dp.*, pr.nombre as producto_nombre FROM detallepedido dp INNER JOIN producto pr ON dp.id_producto = pr.id_producto WHERE dp.id_pedido = $id_pedido";
$detalles = mysqli_query($enlace, $sql_detalle);

$dom_nombre = "";
if ($pedido['id_domiciliario']) {
    $res_dom = mysqli_query($enlace, "SELECT nombre FROM domiciliario WHERE id_domiciliario = " . $pedido['id_domiciliario']);
    $dom = mysqli_fetch_assoc($res_dom);
    $dom_nombre = $dom['nombre'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Pedido #<?= $id_pedido ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>.container{max-width:800px;margin:40px auto;background:#11111a;border-radius:20px;border:1px solid #00f5ff;padding:30px;}.info{background:#1c1c28;padding:15px;border-radius:10px;margin-bottom:20px;}.total{font-size:1.3rem;color:#00f5ff;text-align:right;margin-top:20px;}</style>
</head>
<body>
<div class="container">
    <h1> Detalle Pedido #<?= $id_pedido ?></h1>
    <div class="info">
        <p><strong> Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nombre']) ?></p>
        <p><strong> Teléfono:</strong> <?= $pedido['telefono'] ?></p>
        <p><strong> Dirección:</strong> <?= $pedido['direccion'] ?></p>
        <p><strong> Fecha:</strong> <?= $pedido['fecha_pedido'] ?></p>
        <p><strong> Método de pago:</strong> <?= ucfirst($pedido['metodo_pago'] ?? 'Efectivo') ?></p>
        <p><strong> Domiciliario:</strong> <?= $dom_nombre ?: 'No asignado' ?></p>
        <p><strong> Observaciones:</strong> <?= $pedido['observaciones'] ?: 'Ninguna' ?></p>
    </div>
    <h3>🛒 Productos</h3>
    <table><thead><tr><th>Producto</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
    <tbody><?php while($item=mysqli_fetch_assoc($detalles)): ?>
    <tr><td><?=htmlspecialchars($item['producto_nombre'])?></td><td><?=$item['cantidad']?></td><td>$<?=number_format($item['subtotal'])?></td></tr>
    <?php endwhile; ?></tbody></table>
    <div class="total">Total: $<?= number_format($pedido['total']) ?></div>
    <a href="pedidos.php" class="btn btn-primary">← Volver a Pedidos</a>
</div>
</body>
</html>