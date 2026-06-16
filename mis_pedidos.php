<?php
session_start();
include "conexion.php";

$id_usuario = $_SESSION['id_usuario'] ?? 1;

$sql = "SELECT p.*, 
               (SELECT COUNT(*) FROM detallepedido WHERE id_pedido = p.id_pedido) as total_items
        FROM pedido p 
        WHERE p.id_usuario = $id_usuario 
        ORDER BY p.fecha_pedido DESC";
$resultado = mysqli_query($enlace, $sql);
$pedidos = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    // Obtener productos de cada pedido
    $sql_detalle = "SELECT dp.*, pr.nombre 
                    FROM detallepedido dp 
                    JOIN producto pr ON dp.id_producto = pr.id_producto 
                    WHERE dp.id_pedido = " . $row['id_pedido'];
    $res_detalle = mysqli_query($enlace, $sql_detalle);
    $productos_pedido = [];
    while ($detalle = mysqli_fetch_assoc($res_detalle)) {
        $productos_pedido[] = $detalle;
    }
    $row['productos'] = $productos_pedido;
    $pedidos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos - El Sitio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="mis_pedidos.css">
</head>
<body>
<div class="container">
    <h1> Mis Pedidos</h1>
    
    <?php if (empty($pedidos)): ?>
        <div class="sin-pedidos">
            <span style="font-size: 4rem;"></span>
            <p style="margin: 20px 0;">Aún no has realizado ningún pedido</p>
            <a href="Menu.php" class="btn-volver">Ver Menú</a>
        </div>
    <?php else: ?>
        <?php foreach ($pedidos as $pedido): ?>
            <div class="pedido-card">
                <div class="pedido-header">
                    <span class="pedido-fecha"> <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></span>
                    <span class="estado estado-<?= strtolower($pedido['estado'] ?? 'pendiente') ?>">
                        <?= ucfirst($pedido['estado'] ?? 'Pendiente') ?>
                    </span>
                </div>
                
                <div class="productos-lista">
                    <strong style="color: #ffe600;"> Productos:</strong>
                    <?php foreach ($pedido['productos'] as $producto): ?>
                        <div class="producto-item">
                            <span class="producto-nombre"><?= htmlspecialchars($producto['nombre']) ?></span>
                            <span class="producto-cantidad">x<?= $producto['cantidad'] ?></span>
                            <span class="producto-subtotal">$<?= number_format($producto['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="pedido-total"> Total: $<?= number_format($pedido['total']) ?></div>
                <div style="color: #aaa; font-size: 0.8rem; margin-top: 10px;">
                     Pago: <?= ucfirst($pedido['metodo_pago'] ?? 'efectivo') ?>
                </div>
                <?php if (!empty($pedido['observaciones'])): ?>
                    <div style="color: #888; font-size: 0.8rem; margin-top: 8px;">
                         Observación: <?= htmlspecialchars($pedido['observaciones']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <a href="Menu.php" class="btn-volver">← Seguir comprando</a>
    <?php endif; ?>
</div>
</body>
</html>