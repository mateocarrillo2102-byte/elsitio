<?php
session_start();
include "conexion.php";

// Agregar relación producto-insumo
if (isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
    $id_producto = $_POST['id_producto'];
    $id_insumo = $_POST['id_insumo'];
    $cantidad = $_POST['cantidad_usada'];
    
    // Verificar si ya existe
    $check = mysqli_query($enlace, "SELECT * FROM productoinsumo WHERE id_producto = $id_producto AND id_insumo = $id_insumo");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($enlace, "INSERT INTO productoinsumo (id_producto, id_insumo, cantidad_usada) VALUES ($id_producto, $id_insumo, $cantidad)");
    }
    header("Location: producto_insumos.php?id=" . $id_producto);
    exit;
}

// Eliminar relación
if (isset($_GET['eliminar'])) {
    $id_producto = $_GET['id_producto'];
    $id_insumo = $_GET['eliminar'];
    mysqli_query($enlace, "DELETE FROM productoinsumo WHERE id_producto = $id_producto AND id_insumo = $id_insumo");
    header("Location: producto_insumos.php?id=" . $id_producto);
    exit;
}

// Obtener productos
$productos = mysqli_query($enlace, "SELECT * FROM producto ORDER BY nombre");

// Producto seleccionado
$id_producto = isset($_GET['id']) ? $_GET['id'] : 0;
$producto_actual = null;
$insumos_asignados = [];

if ($id_producto > 0) {
    $res = mysqli_query($enlace, "SELECT * FROM producto WHERE id_producto = $id_producto");
    $producto_actual = mysqli_fetch_assoc($res);
    
    $insumos_asignados = mysqli_query($enlace, "
        SELECT pi.*, i.nombre, i.unidad_medida, i.cantidad_disponible 
        FROM productoinsumo pi 
        INNER JOIN insumo i ON pi.id_insumo = i.id_insumo 
        WHERE pi.id_producto = $id_producto
    ");
}

// Obtener insumos disponibles (no asignados a este producto)
$insumos_disponibles = mysqli_query($enlace, "
    SELECT * FROM insumo 
    WHERE id_insumo NOT IN (
        SELECT id_insumo FROM productoinsumo WHERE id_producto = $id_producto
    )
    ORDER BY nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Insumos por Producto</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a0f; color: #fff; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #11111a; border-right: 1px solid rgba(0,245,255,0.2); padding: 30px 0; position: fixed; height: 100vh; }
        .logo { text-align: center; padding: 0 20px 30px; border-bottom: 1px solid #1c1c28; }
        .logo h2 { background: linear-gradient(135deg, #00f5ff, #ff2d9e); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .nav-menu { list-style: none; }
        .nav-item { margin: 5px 15px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #aaa; text-decoration: none; border-radius: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(0,245,255,0.1); color: #00f5ff; }
        .main-content { flex: 1; margin-left: 280px; padding: 30px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { color: #00f5ff; margin-bottom: 20px; }
        .btn-primary { background: #00f5ff; color: #0a0a0f; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
        .btn-danger { background: #ff2d9e; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        .btn-warning { background: #ffe600; color: #0a0a0f; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; }
        .table-container { background: #11111a; border: 1px solid #1c1c28; border-radius: 20px; overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #1c1c28; }
        th { color: #00f5ff; }
        select, input { background: #1c1c28; border: 1px solid #00f5ff; color: white; padding: 8px; border-radius: 6px; }
        .form-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin: 20px 0; }
        .producto-selector { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; background: #1c1c28; padding: 15px; border-radius: 15px; margin-bottom: 20px; }
        .logout-btn { margin-top: 30px; padding: 12px 20px; background: transparent; border: 1px solid #ff2d9e; color: #ff2d9e; text-decoration: none; display: flex; align-items: center; gap: 10px; border-radius: 10px; }
    </style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div class="logo"><h2>EL SITIO</h2><p>Administrador</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin.php" class="nav-link">📊 Panel Principal</a></li>
            <li class="nav-item"><a href="clientes.php" class="nav-link">👥 Clientes</a></li>
            <li class="nav-item"><a href="productos.php" class="nav-link">🍔 Productos</a></li>
            <li class="nav-item"><a href="insumos.php" class="nav-link">📦 Insumos</a></li>
            <li class="nav-item"><a href="producto_insumos.php" class="nav-link active">🔗 Prod. × Insumos</a></li>
            <li class="nav-item"><a href="domiciliarios.php" class="nav-link">🛵 Domiciliarios</a></li>
            <li class="nav-item"><a href="pedidos.php" class="nav-link">📦 Pedidos</a></li>
        </ul>
        <div style="padding: 20px;"><a href="login.php" class="logout-btn">🚪 Cerrar Sesión</a></div>
    </aside>
    <main class="main-content">
        <div class="page-header">
            <h1>🔗 Relacionar Productos con Insumos</h1>
        </div>

        <!-- Selector de producto -->
        <div class="producto-selector">
            <strong>Seleccionar producto:</strong>
            <select id="selectorProducto" onchange="location.href='producto_insumos.php?id='+this.value">
                <option value="">-- Seleccione un producto --</option>
                <?php while($prod = mysqli_fetch_assoc($productos)): ?>
                    <option value="<?= $prod['id_producto'] ?>" <?= ($id_producto == $prod['id_producto']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prod['nombre']) ?> - $<?= number_format($prod['precio']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <?php if ($producto_actual): ?>
            <h2 style="color:#ffe600; margin:20px 0;">🍔 <?= htmlspecialchars($producto_actual['nombre']) ?></h2>
            
            <!-- Formulario para agregar insumo -->
            <div class="form-inline">
                <form method="POST" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="id_producto" value="<?= $id_producto ?>">
                    <select name="id_insumo" required>
                        <option value="">Seleccionar insumo</option>
                        <?php while($insumo = mysqli_fetch_assoc($insumos_disponibles)): ?>
                            <option value="<?= $insumo['id_insumo'] ?>"><?= htmlspecialchars($insumo['nombre']) ?> (<?= $insumo['unidad_medida'] ?>) - Stock: <?= $insumo['cantidad_disponible'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <input type="number" step="0.01" name="cantidad_usada" placeholder="Cantidad necesaria" required style="width:150px;">
                    <button type="submit" class="btn-primary">+ Agregar Insumo</button>
                </form>
            </div>

            <!-- Tabla de insumos asignados -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Insumo</th><th>Unidad</th><th>Cantidad necesaria</th><th>Stock actual</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php while($item = mysqli_fetch_assoc($insumos_asignados)): 
                            $stock_suficiente = $item['cantidad_disponible'] >= $item['cantidad_usada'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nombre']) ?></td>
                            <td><?= $item['unidad_medida'] ?></td>
                            <td><?= $item['cantidad_usada'] ?></td>
                            <td><?= $item['cantidad_disponible'] ?></td>
                            <td style="color: <?= $stock_suficiente ? '#39ff14' : '#ff2d9e' ?>">
                                <?= $stock_suficiente ? '✓ Suficiente' : '⚠️ Stock bajo' ?>
                            </td>
                            <td><a href="producto_insumos.php?eliminar=<?= $item['id_insumo'] ?>&id_producto=<?= $id_producto ?>" class="btn-danger" onclick="return confirm('¿Eliminar este insumo del producto?')">Eliminar</a></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($insumos_asignados) == 0): ?>
                            <tr><td colspan="6" style="text-align:center; color:#666;">No hay insumos asignados a este producto</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:60px; background:#11111a; border-radius:20px;">
                <span style="font-size:4rem;">🔗</span>
                <p style="margin-top:20px;">Selecciona un producto para gestionar sus insumos</p>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>