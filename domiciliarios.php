<?php
session_start();
include "conexion.php";
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
// Agregar domiciliario
if ($_POST['accion'] == 'agregar') {
    $nombre = mysqli_real_escape_string($enlace, $_POST['nombre']);
    $telefono = $_POST['telefono'];
    $transporte = $_POST['medio_transporte'];
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    mysqli_query($enlace, "INSERT INTO domiciliario (nombre, telefono, medio_transporte, disponible) VALUES ('$nombre', '$telefono', '$transporte', $disponible)");
    header("Location: domiciliarios.php");
    exit;
}

// Editar domiciliario
if ($_POST['accion'] == 'editar') {
    $id = $_POST['id_domiciliario'];
    $nombre = mysqli_real_escape_string($enlace, $_POST['nombre']);
    $telefono = $_POST['telefono'];
    $transporte = $_POST['medio_transporte'];
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    mysqli_query($enlace, "UPDATE domiciliario SET nombre='$nombre', telefono='$telefono', medio_transporte='$transporte', disponible=$disponible WHERE id_domiciliario=$id");
    header("Location: domiciliarios.php");
    exit;
}

// Eliminar domiciliario
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($enlace, "DELETE FROM domiciliario WHERE id_domiciliario = $id");
    header("Location: domiciliarios.php");
    exit;
}
}

$sql = "SELECT * FROM domiciliario ORDER BY disponible DESC, nombre ASC";
$resultado = mysqli_query($enlace, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Domiciliarios</title>
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .page-header h1 { color: #00f5ff; }
        .btn-primary { background: #00f5ff; color: #0a0a0f; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; }
        .btn-warning { background: #ffe600; color: #0a0a0f; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; display: inline-block; margin: 0 3px; border: none; cursor: pointer; }
        .btn-danger { background: #ff2d9e; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        .table-container { background: #11111a; border: 1px solid #1c1c28; border-radius: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #1c1c28; }
        th { color: #00f5ff; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: #11111a; border: 1px solid #00f5ff; border-radius: 20px; padding: 30px; width: 400px; max-width: 90%; }
        .modal-content input, .modal-content select { width: 100%; padding: 12px; margin: 10px 0; background: #1c1c28; border: 1px solid #333; border-radius: 8px; color: white; }
        .modal-content button { width: 100%; padding: 12px; background: #00f5ff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .badge-disponible { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; background: #39ff14; color: #0a0a0f; }
        .badge-ocupado { background: #ff2d9e; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; }
        .logout-btn { margin-top: 30px; padding: 12px 20px; background: transparent; border: 1px solid #ff2d9e; color: #ff2d9e; text-decoration: none; display: flex; align-items: center; gap: 10px; border-radius: 10px; }
    </style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div class="logo"><h2>EL SITIO</h2><p>Administrador</p></div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"> Dashboard</a></li>
            <li class="nav-item"><a href="clientes.php" class="nav-link"> Clientes</a></li>
            <li class="nav-item"><a href="productos.php" class="nav-link"> Productos</a></li>
            <li class="nav-item"><a href="insumos.php" class="nav-link"> Insumos</a></li>
            <li class="nav-item"><a href="producto_insumos.php" class="nav-link"> Prod. × Insumos</a></li>
            <li class="nav-item"><a href="domiciliarios.php" class="nav-link active"> Domiciliarios</a></li>
            <li class="nav-item"><a href="pedidos.php" class="nav-link"> Pedidos</a></li>
        </ul>
        <div style="padding: 20px;"><a href="Login.html" class="logout-btn"> Cerrar Sesión</a></div>
    </aside>
    <main class="main-content">
        <div class="page-header">
            <h1> Domiciliarios</h1>
            <button class="btn-primary" id="openModalBtn">+ Agregar Domiciliario</button>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Transporte</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td><?= $row['id_domiciliario'] ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= $row['telefono'] ?></td>
                        <td>
                            <?php if ($row['medio_transporte'] == 'moto') echo ' Moto'; ?>
                            <?php if ($row['medio_transporte'] == 'bicicleta') echo ' Bicicleta'; ?>
                            <?php if ($row['medio_transporte'] == 'a pie') echo ' A pie'; ?>
                            <?php if ($row['medio_transporte'] == 'otro') echo ' Otro'; ?>
                        </td>
                        <td>
                            <?php if ($row['disponible']): ?>
                                <span class="badge-disponible"> Disponible</span>
                            <?php else: ?>
                                <span class="badge-ocupado"> Ocupado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-warning btn-sm" onclick="abrirModalEditar(<?= $row['id_domiciliario'] ?>, '<?= htmlspecialchars($row['nombre']) ?>', '<?= $row['telefono'] ?>', '<?= $row['medio_transporte'] ?>', <?= $row['disponible'] ?>)">✏ Editar</button>
                            <a href="domiciliarios.php?eliminar=<?= $row['id_domiciliario'] ?>" class="btn-danger btn-sm" onclick="return confirm('¿Eliminar este domiciliario?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal Agregar Domiciliario -->
<div class="modal" id="modalAgregar">
    <div class="modal-content">
        <h3 style="color:#00f5ff; margin-bottom:20px;"> Nuevo Domiciliario</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="agregar">
            <input type="text" name="nombre" placeholder="Nombre completo" required>
            <input type="text" name="telefono" placeholder="Teléfono" required>
            <select name="medio_transporte" required>
                <option value="moto"> Moto</option>
                <option value="bicicleta"> Bicicleta</option>
                <option value="a pie"> A pie</option>
                <option value="otro"> Otro</option>
            </select>
            <label style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
                <input type="checkbox" name="disponible" checked> Disponible
            </label>
            <button type="submit">Guardar Domiciliario</button>
        </form>
    </div>
</div>

<!-- Modal Editar Domiciliario -->
<div class="modal" id="modalEditar">
    <div class="modal-content">
        <h3 style="color:#00f5ff; margin-bottom:20px;"> Editar Domiciliario</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_domiciliario" id="edit_id">
            <input type="text" name="nombre" id="edit_nombre" required>
            <input type="text" name="telefono" id="edit_telefono" required>
            <select name="medio_transporte" id="edit_transporte" required>
                <option value="moto"> Moto</option>
                <option value="bicicleta"> Bicicleta</option>
                <option value="a pie"> A pie</option>
                <option value="otro"> Otro</option>
            </select>
            <label style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
                <input type="checkbox" name="disponible" id="edit_disponible"> Disponible
            </label>
            <button type="submit">Actualizar Domiciliario</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('openModalBtn').onclick = () => document.getElementById('modalAgregar').classList.add('active');
    document.getElementById('modalAgregar').onclick = (e) => { if (e.target === document.getElementById('modalAgregar')) document.getElementById('modalAgregar').classList.remove('active'); };
    document.getElementById('modalEditar').onclick = (e) => { if (e.target === document.getElementById('modalEditar')) document.getElementById('modalEditar').classList.remove('active'); };
    
    function abrirModalEditar(id, nombre, telefono, transporte, disponible) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_telefono').value = telefono;
        document.getElementById('edit_transporte').value = transporte;
        document.getElementById('edit_disponible').checked = disponible == 1;
        document.getElementById('modalEditar').classList.add('active');
    }
</script>
</body>
</html>