<?php
session_start();
include "conexion.php";

// Función para descontar insumos cuando un pedido cambia a "listo" o "entregado"
function descontarInsumos($enlace, $id_pedido) {
    // Obtener productos del pedido
    $sql_detalle = "SELECT id_producto, cantidad FROM detallepedido WHERE id_pedido = $id_pedido";
    $resultado = mysqli_query($enlace, $sql_detalle);
    
    while ($item = mysqli_fetch_assoc($resultado)) {
        $id_producto = $item['id_producto'];
        $cantidad_producto = $item['cantidad'];
        
        // Obtener insumos necesarios para este producto
        $sql_insumos = "SELECT id_insumo, cantidad_usada FROM productoinsumo WHERE id_producto = $id_producto";
        $res_insumos = mysqli_query($enlace, $sql_insumos);
        
        while ($insumo = mysqli_fetch_assoc($res_insumos)) {
            $id_insumo = $insumo['id_insumo'];
            $cantidad_necesaria = $insumo['cantidad_usada'] * $cantidad_producto;
            
            // Descontar del stock
            mysqli_query($enlace, "UPDATE insumo SET cantidad_disponible = cantidad_disponible - $cantidad_necesaria WHERE id_insumo = $id_insumo");
            
            // Registrar movimiento
            mysqli_query($enlace, "INSERT INTO movimientoinventario (tipo, cantidad, descripcion, id_insumo) 
                                   VALUES ('salida', $cantidad_necesaria, 'Pedido #$id_pedido', $id_insumo)");
        }
    }
}
if (isset($_POST['cambiar_estado'])) {
    $id = $_POST['id_pedido'];
    $estado_nuevo = $_POST['estado'];
    
    // Obtener estado actual
    $res = mysqli_query($enlace, "SELECT estado FROM pedido WHERE id_pedido = $id");
    $pedido_actual = mysqli_fetch_assoc($res);
    $estado_actual = $pedido_actual['estado'];
    
    // Si el estado cambia a "listo" o "entregado" y antes no lo estaba, descontar insumos
    $estados_consumo = ['listo', 'entregado', 'en camino'];
    $estado_anterior_ya_consumio = in_array($estado_actual, $estados_consumo);
    
    if (in_array($estado_nuevo, $estados_consumo) && !$estado_anterior_ya_consumio) {
        descontarInsumos($enlace, $id);
    }
    
    mysqli_query($enlace, "UPDATE pedido SET estado = '$estado_nuevo' WHERE id_pedido = $id");
    header("Location: pedidos.php");
    exit;
}

if (isset($_POST['asignar_domiciliario'])) {
    $id_pedido = $_POST['id_pedido'];
    $id_domiciliario = $_POST['id_domiciliario'] ?: 'NULL';
    mysqli_query($enlace, "UPDATE pedido SET id_domiciliario = $id_domiciliario WHERE id_pedido = $id_pedido");
    header("Location: pedidos.php");
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($enlace, "DELETE FROM detallepedido WHERE id_pedido = $id");
    mysqli_query($enlace, "DELETE FROM pedido WHERE id_pedido = $id");
    header("Location: pedidos.php");
    exit;
}

$sql = "SELECT p.*, c.nombre as cliente_nombre FROM pedido p INNER JOIN cliente c ON p.id_cliente = c.id_cliente ORDER BY p.id_pedido DESC";
$resultado = mysqli_query($enlace, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Pedidos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <li class="nav-item"><a href="pedidos.php" class="nav-link active">📦 Pedidos</a></li>
        </ul>
        <div><a href="login.php" class="logout-btn">🚪 Cerrar Sesión</a></div>
    </aside>
    <main class="main-content">
        <div class="page-header"><h1>📦 Todos los Pedidos</h1></div>
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Cambiar Estado</th><th>Domiciliario</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php while($row=mysqli_fetch_assoc($resultado)):
                        $domis = mysqli_query($enlace, "SELECT id_domiciliario, nombre FROM domiciliario WHERE disponible=1");
                    ?>
                    <tr>
                        <td><?=$row['id_pedido']?></td>
                        <td><?=htmlspecialchars($row['cliente_nombre'])?></td>
                        <td><?=$row['fecha_pedido']?></td>
                        <td>$<?=number_format($row['total'])?></td>
                        <td><span class="estado estado-<?=strtolower($row['estado']??'recibido')?>"><?=ucfirst($row['estado']??'Recibido')?></span></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="id_pedido" value="<?=$row['id_pedido']?>">
                                <select name="estado">
                                    <option value="recibido" <?=($row['estado']??'recibido')=='recibido'?'selected':''?>>Recibido</option>
                                    <option value="en preparación" <?=($row['estado']??'')=='en preparación'?'selected':''?>>En preparación</option>
                                    <option value="listo" <?=($row['estado']??'')=='listo'?'selected':''?>>Listo</option>
                                    <option value="en camino" <?=($row['estado']??'')=='en camino'?'selected':''?>>En camino</option>
                                    <option value="entregado" <?=($row['estado']??'')=='entregado'?'selected':''?>>Entregado</option>
                                    <option value="cancelado" <?=($row['estado']??'')=='cancelado'?'selected':''?>>Cancelado</option>
                                </select>
                                <button type="submit" name="cambiar_estado" class="btn btn-warning btn-sm">Actualizar</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="id_pedido" value="<?=$row['id_pedido']?>">
                                <select name="id_domiciliario">
                                    <option value="">Sin asignar</option>
                                    <?php while($d=mysqli_fetch_assoc($domis)): ?>
                                        <option value="<?=$d['id_domiciliario']?>" <?=($row['id_domiciliario']==$d['id_domiciliario'])?'selected':''?>><?=htmlspecialchars($d['nombre'])?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" name="asignar_domiciliario" class="btn btn-primary btn-sm">Asignar</button>
                            </form>
                        </td>
                        <td><a href="ver_pedido.php?id=<?=$row['id_pedido']?>" class="btn btn-primary btn-sm">Ver</a>
                        <a href="pedidos.php?eliminar=<?=$row['id_pedido']?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar?')">Eliminar</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>