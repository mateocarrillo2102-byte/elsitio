<?php
session_start();
include "conexion.php";

// ========== VERIFICAR SI EXISTE LA ACCIÓN ==========
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    // Agregar producto
    if ($accion == 'agregar') {
        $nombre = mysqli_real_escape_string($enlace, $_POST['nombre']);
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $visible = isset($_POST['visible']) ? 1 : 0;
        mysqli_query($enlace, "INSERT INTO producto (nombre, precio, stock, visible) VALUES ('$nombre', $precio, $stock, $visible)");
        header("Location: productos.php");
        exit;
    }
    
    // Editar disponibilidad (visible)
    if ($accion == 'toggle_visible') {
        $id = $_POST['id_producto'];
        $visible = $_POST['visible'];
        mysqli_query($enlace, "UPDATE producto SET visible = $visible WHERE id_producto = $id");
        header("Location: productos.php");
        exit;
    }
}

// ========== ELIMINAR PRODUCTO (por GET) ==========
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($enlace, "DELETE FROM producto WHERE id_producto = $id");
    header("Location: productos.php");
    exit;
}

// ========== CAMBIAR VISIBILIDAD (por GET simple) ==========
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $visible = $_GET['visible'];
    mysqli_query($enlace, "UPDATE producto SET visible = $visible WHERE id_producto = $id");
    header("Location: productos.php");
    exit;
}

// ========== OBTENER PRODUCTOS ==========
$sql = "SELECT * FROM producto ORDER BY id_producto DESC";
$resultado = mysqli_query($enlace, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Productos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a0f; color: #fff; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #11111a; border-right: 1px solid rgba(0,245,255,0.2); padding: 30px 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo { text-align: center; padding: 0 20px 30px; border-bottom: 1px solid #1c1c28; }
        .logo h2 { background: linear-gradient(135deg, #00f5ff, #ff2d9e); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .nav-menu { list-style: none; }
        .nav-item { margin: 5px 15px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #aaa; text-decoration: none; border-radius: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(0,245,255,0.1); color: #00f5ff; }
        .main-content { flex: 1; margin-left: 280px; padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .page-header h1 { color: #00f5ff; }
        .btn-primary { background: #00f5ff; color: #0a0a0f; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
        .btn-warning { background: #ffe600; color: #0a0a0f; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.8rem; }
        .btn-danger { background: #ff2d9e; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: #11111a; border: 1px solid #00f5ff; border-radius: 20px; padding: 30px; width: 400px; }
        .modal-content input, .modal-content select { width: 100%; padding: 12px; margin: 10px 0; background: #1c1c28; border: 1px solid #333; border-radius: 8px; color: white; }
        .modal-content button { width: 100%; padding: 12px; background: #00f5ff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .table-container { background: #11111a; border: 1px solid #1c1c28; border-radius: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #1c1c28; }
        th { color: #00f5ff; }
        .badge-success { color: #39ff14; font-weight: bold; }
        .badge-danger { color: #ff2d9e; font-weight: bold; }
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
            <li class="nav-item"><a href="productos.php" class="nav-link active">🍔 Productos</a></li>
            <li class="nav-item"><a href="insumos.php" class="nav-link">📦 Insumos</a></li>
            <li class="nav-item"><a href="producto_insumos.php" class="nav-link">🔗 Prod. × Insumos</a></li>
            <li class="nav-item"><a href="domiciliarios.php" class="nav-link">🛵 Domiciliarios</a></li>
            <li class="nav-item"><a href="pedidos.php" class="nav-link">📦 Pedidos</a></li>
        </ul>
        <div style="padding: 20px;"><a href="../logout.php" class="logout-btn">🚪 Cerrar Sesión</a></div>
    </aside>
    <main class="main-content">
        <div class="page-header">
            <h1>🍔 Productos</h1>
            <button class="btn-primary" id="openModalBtn">+ Agregar Producto</button>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Disponibilidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td><?= $row['id_producto'] ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td>$<?= number_format($row['precio']) ?></td>
                        <td><?= $row['stock'] ?> uds</td>
                        <td>
                            <?php if ($row['visible'] == 1): ?>
                                <span class="badge-success">✅ Disponible</span>
                            <?php else: ?>
                                <span class="badge-danger">❌ No disponible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-warning" onclick="toggleVisible(<?= $row['id_producto'] ?>, <?= $row['visible'] ?>)">
                                <?= $row['visible'] == 1 ? 'Desactivar' : 'Activar' ?>
                            </button>
                            <a href="productos.php?eliminar=<?= $row['id_producto'] ?>" class="btn-danger" onclick="return confirm('¿Eliminar este producto?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal Agregar Producto -->
<div class="modal" id="productoModal">
    <div class="modal-content">
        <h3 style="color:#00f5ff; margin-bottom:20px;">➕ Nuevo Producto</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="agregar">
            <input type="text" name="nombre" placeholder="Nombre del producto" required>
            <input type="number" step="0.01" name="precio" placeholder="Precio" required>
            <input type="number" name="stock" placeholder="Stock inicial" required>
            <label style="display:flex; align-items:center; gap:10px; margin:10px 0;">
                <input type="checkbox" name="visible" checked> Disponible para venta
            </label>
            <button type="submit">Guardar Producto</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('openModalBtn').onclick = () => document.getElementById('productoModal').classList.add('active');
    document.getElementById('productoModal').onclick = (e) => { if (e.target === document.getElementById('productoModal')) document.getElementById('productoModal').classList.remove('active'); };
    
    function toggleVisible(id, actual) {
        var nuevo = actual == 1 ? 0 : 1;
        if (confirm(actual == 1 ? '¿Ocultar este producto del menú?' : '¿Mostrar este producto en el menú?')) {
            window.location.href = 'productos.php?toggle=' + id + '&visible=' + nuevo;
        }
    }
</script>
</body>
</html>