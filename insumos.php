<?php
session_start();
include "conexion.php";

// Verificar si existe la acción
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    // Agregar insumo
    if ($accion == 'agregar') {
        $nombre = mysqli_real_escape_string($enlace, $_POST['nombre']);
        $unidad = $_POST['unidad_medida'];
        $cantidad = $_POST['cantidad_disponible'];
        $minima = $_POST['cantidad_minima'];
        mysqli_query($enlace, "INSERT INTO insumo (nombre, unidad_medida, cantidad_disponible, cantidad_minima) VALUES ('$nombre', '$unidad', $cantidad, $minima)");
        header("Location: insumos.php");
        exit;
    }
    
    // Editar insumo
    if ($accion == 'editar') {
        $id = $_POST['id_insumo'];
        $nombre = mysqli_real_escape_string($enlace, $_POST['nombre']);
        $unidad = $_POST['unidad_medida'];
        $cantidad = $_POST['cantidad_disponible'];
        $minima = $_POST['cantidad_minima'];
        mysqli_query($enlace, "UPDATE insumo SET nombre='$nombre', unidad_medida='$unidad', cantidad_disponible=$cantidad, cantidad_minima=$minima WHERE id_insumo=$id");
        header("Location: insumos.php");
        exit;
    }
    
    // Registrar movimiento (entrada/salida)
    if ($accion == 'movimiento') {
        $id_insumo = $_POST['id_insumo'];
        $tipo = $_POST['tipo'];
        $cantidad = $_POST['cantidad'];
        $descripcion = mysqli_real_escape_string($enlace, $_POST['descripcion']);
        
        // Actualizar stock
        if ($tipo == 'entrada') {
            mysqli_query($enlace, "UPDATE insumo SET cantidad_disponible = cantidad_disponible + $cantidad WHERE id_insumo = $id_insumo");
        } else {
            mysqli_query($enlace, "UPDATE insumo SET cantidad_disponible = cantidad_disponible - $cantidad WHERE id_insumo = $id_insumo");
        }
        
        // Registrar movimiento
        mysqli_query($enlace, "INSERT INTO movimientoinventario (tipo, cantidad, descripcion, id_insumo) VALUES ('$tipo', $cantidad, '$descripcion', $id_insumo)");
        header("Location: insumos.php");
        exit;
    }
}

// Eliminar insumo (por GET)
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($enlace, "DELETE FROM insumo WHERE id_insumo = $id");
    header("Location: insumos.php");
    exit;
}

$sql = "SELECT * FROM insumo ORDER BY id_insumo DESC";
$resultado = mysqli_query($enlace, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Insumos</title>
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
        .btn-warning { background: #ffe600; color: #0a0a0f; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; display: inline-block; margin: 0 3px; border: none; cursor: pointer; }
        .btn-danger { background: #ff2d9e; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        .table-container { background: #11111a; border: 1px solid #1c1c28; border-radius: 20px; overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #1c1c28; }
        th { color: #00f5ff; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: #11111a; border: 1px solid #00f5ff; border-radius: 20px; padding: 30px; width: 450px; max-width: 90%; }
        .modal-content input, .modal-content select, .modal-content textarea { width: 100%; padding: 12px; margin: 10px 0; background: #1c1c28; border: 1px solid #333; border-radius: 8px; color: white; }
        .modal-content button { width: 100%; padding: 12px; background: #00f5ff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 10px; }
        .stock-bajo { color: #ff2d9e; font-weight: bold; }
        .logout-btn { margin-top: 30px; padding: 12px 20px; background: transparent; border: 1px solid #ff2d9e; color: #ff2d9e; text-decoration: none; display: flex; align-items: center; gap: 10px; border-radius: 10px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
        .badge-warning { background: #ffe600; color: #0a0a0f; }
        .badge-danger { background: #ff2d9e; color: white; }
    </style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div class="logo"><h2>EL SITIO</h2><p>Administrador</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="admin.php" class="nav-link active"> Panel Principal</a></li>
            <li class="nav-item"><a href="clientes.php" class="nav-link"> Clientes</a></li>
            <li class="nav-item"><a href="productos.php" class="nav-link"> Productos</a></li>
            <li class="nav-item"><a href="insumos.php" class="nav-link active"> Insumos</a></li>
            <li class="nav-item"><a href="producto_insumos.php" class="nav-link"> Prod. × Insumos</a></li>
            <li class="nav-item"><a href="domiciliarios.php" class="nav-link"> Domiciliarios</a></li>
            <li class="nav-item"><a href="pedidos.php" class="nav-link"> Pedidos</a></li>
        </ul>
        <div style="padding: 20px;"><a href="Login.html" class="logout-btn"> Cerrar Sesión</a></div>
    </aside>
    <main class="main-content">
        <div class="page-header">
            <h1> Insumos / Inventario</h1>
            <button class="btn-primary" id="openModalBtn"> Agregar Insumo</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Unidad</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($resultado)): 
                        $stock_bajo = $row['cantidad_disponible'] <= $row['cantidad_minima'];
                    ?>
                    <tr>
                        <td><?= $row['id_insumo'] ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= $row['unidad_medida'] ?></td>
                        <td class="<?= $stock_bajo ? 'stock-bajo' : '' ?>"><?= number_format($row['cantidad_disponible'], 2) ?></td>
                        <td><?= number_format($row['cantidad_minima'], 2) ?></td>
                        <td>
                            <?php if ($stock_bajo): ?>
                                <span class="badge badge-danger">⚠ Stock bajo</span>
                            <?php else: ?>
                                <span class="badge badge-warning">✓ Normal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-warning btn-sm" onclick="abrirModalMovimiento(<?= $row['id_insumo'] ?>, '<?= htmlspecialchars($row['nombre']) ?>')"> Movimiento</button>
                            <button class="btn-warning btn-sm" onclick="abrirModalEditar(<?= $row['id_insumo'] ?>, '<?= htmlspecialchars($row['nombre']) ?>', '<?= $row['unidad_medida'] ?>', <?= $row['cantidad_disponible'] ?>, <?= $row['cantidad_minima'] ?>)">✏ Editar</button>
                            <a href="insumos.php?eliminar=<?= $row['id_insumo'] ?>" class="btn-danger btn-sm" onclick="return confirm('¿Eliminar este insumo?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal Agregar Insumo -->
<div class="modal" id="modalAgregar">
    <div class="modal-content">
        <h3 style="color:#00f5ff; margin-bottom:20px;"> Nuevo Insumo</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="agregar">
            <input type="text" name="nombre" placeholder="Nombre del insumo" required>
            <select name="unidad_medida" required>
                <option value="kg">Kilogramos (kg)</option>
                <option value="g">Gramos (g)</option>
                <option value="litro">Litros (L)</option>
                <option value="ml">Mililitros (ml)</option>
                <option value="unidad">Unidades</option>
            </select>
            <input type="number" step="0.01" name="cantidad_disponible" placeholder="Cantidad disponible" required>
            <input type="number" step="0.01" name="cantidad_minima" placeholder="Cantidad mínima (alerta)" required>
            <button type="submit">Guardar Insumo</button>
        </form>
    </div>
</div>

<!-- Modal Editar Insumo -->
<div class="modal" id="modalEditar">
    <div class="modal-content">
        <h3 style="color:#00f5ff; margin-bottom:20px;"> Editar Insumo</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_insumo" id="edit_id">
            <input type="text" name="nombre" id="edit_nombre" required>
            <select name="unidad_medida" id="edit_unidad" required>
                <option value="kg">Kilogramos (kg)</option>
                <option value="g">Gramos (g)</option>
                <option value="litro">Litros (L)</option>
                <option value="ml">Mililitros (ml)</option>
                <option value="unidad">Unidades</option>
            </select>
            <input type="number" step="0.01" name="cantidad_disponible" id="edit_cantidad" required>
            <input type="number" step="0.01" name="cantidad_minima" id="edit_minima" required>
            <button type="submit">Actualizar Insumo</button>
        </form>
    </div>
</div>

<!-- Modal Movimiento Inventario -->
<div class="modal" id="modalMovimiento">
    <div class="modal-content">
        <h3 style="color:#00f5ff; margin-bottom:20px;"> Registrar Movimiento</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="movimiento">
            <input type="hidden" name="id_insumo" id="mov_id">
            <p style="margin:10px 0;"><strong id="mov_nombre"></strong></p>
            <select name="tipo" required>
                <option value="entrada"> Entrada (Compras)</option>
                <option value="salida"> Salida (Consumo)</option>
            </select>
            <input type="number" step="0.01" name="cantidad" placeholder="Cantidad" required>
            <textarea name="descripcion" rows="2" placeholder="Descripción (ej: Compra semanal, producción, etc.)"></textarea>
            <button type="submit">Registrar Movimiento</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('openModalBtn').onclick = () => document.getElementById('modalAgregar').classList.add('active');
    document.getElementById('modalAgregar').onclick = (e) => { if (e.target === document.getElementById('modalAgregar')) document.getElementById('modalAgregar').classList.remove('active'); };
    document.getElementById('modalEditar').onclick = (e) => { if (e.target === document.getElementById('modalEditar')) document.getElementById('modalEditar').classList.remove('active'); };
    document.getElementById('modalMovimiento').onclick = (e) => { if (e.target === document.getElementById('modalMovimiento')) document.getElementById('modalMovimiento').classList.remove('active'); };
    
    function abrirModalEditar(id, nombre, unidad, cantidad, minima) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_unidad').value = unidad;
        document.getElementById('edit_cantidad').value = cantidad;
        document.getElementById('edit_minima').value = minima;
        document.getElementById('modalEditar').classList.add('active');
    }
    
    function abrirModalMovimiento(id, nombre) {
        document.getElementById('mov_id').value = id;
        document.getElementById('mov_nombre').innerHTML = ' ' + nombre;
        document.getElementById('modalMovimiento').classList.add('active');
    }
</script>
</body>
</html>