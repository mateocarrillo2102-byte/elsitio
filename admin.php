<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$server = getenv('MYSQLHOST');
$user   = getenv('MYSQLUSER');
$pass   = getenv('MYSQLPASSWORD');
$bd     = getenv('MYSQLDATABASE');
$port   = (int) getenv('MYSQLPORT');
$enlace = new mysqli($server, $user, $pass, $bd, $port);

if ($enlace->connect_error) {
    die("Error de conexión: " . $enlace->connect_error);
}

// Total ventas
$res_total = $enlace->query("SELECT SUM(total) as total_ventas, COUNT(*) as num_pedidos FROM pedido");
$stats = $res_total->fetch_assoc();

// Pedidos recientes con cliente
$res_pedidos = $enlace->query("
    SELECT p.id_pedido, p.fecha_pedido, p.total, c.nombre, c.telefono, c.direccion
    FROM pedido p
    JOIN cliente c ON p.id_cliente = c.id_cliente
    ORDER BY p.fecha_pedido DESC
    LIMIT 50
");

// Clientes
$res_clientes = $enlace->query("
    SELECT c.id_cliente, c.nombre, c.telefono, c.correo, c.direccion,
           COUNT(p.id_pedido) as total_pedidos,
           SUM(p.total) as total_gastado
    FROM cliente c
    LEFT JOIN pedido p ON c.id_cliente = p.id_cliente
    GROUP BY c.id_cliente
    ORDER BY total_gastado DESC
");

// Productos más vendidos
$res_productos = $enlace->query("
    SELECT pr.nombre, SUM(dp.cantidad) as unidades, SUM(dp.subtotal) as ingresos
    FROM detallepedido dp
    JOIN producto pr ON dp.id_producto = pr.id_producto
    GROUP BY dp.id_producto
    ORDER BY unidades DESC
    LIMIT 10
");

function fmt($n) {
    return '$' . number_format($n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Panel · El Sitio</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#090909;
  --card:#111111;
  --card2:#161616;
  --cyan:#00F5FF;
  --pink:#FF2D95;
  --green:#39FF14;
  --yellow:#FFE600;
  --white:#F0F0F0;
  --muted:#666;
  --border:#1e1e1e;
}
body{
  font-family:'DM Sans',sans-serif;
  background:var(--bg);
  color:var(--white);
  min-height:100vh;
}

/* HEADER */
header{
  background:#0a0a0a;
  border-bottom:1px solid var(--border);
  padding:0 2rem;
  display:flex;
  align-items:center;
  justify-content:space-between;
  height:60px;
  position:sticky;top:0;z-index:100;
}
header::after{
  content:'';
  position:absolute;
  bottom:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,var(--pink),var(--cyan),var(--green));
  opacity:.4;
}
.header-logo{
  font-family:'Orbitron',sans-serif;
  font-size:.85rem;
  font-weight:900;
  color:var(--cyan);
  text-shadow:0 0 10px rgba(0,245,255,.4);
  letter-spacing:.08em;
}
.header-logo span{color:var(--pink);}
.logout{
  font-size:12px;
  color:var(--muted);
  text-decoration:none;
  padding:6px 14px;
  border:1px solid #222;
  border-radius:6px;
  transition:all .2s;
}
.logout:hover{color:var(--pink);border-color:var(--pink);}

/* NAV TABS */
.tabs{
  background:#0a0a0a;
  border-bottom:1px solid var(--border);
  display:flex;
  padding:0 2rem;
  gap:4px;
}
.tab{
  background:none;border:none;
  color:var(--muted);
  font-family:'DM Sans',sans-serif;
  font-size:13px;font-weight:500;
  padding:14px 18px;
  cursor:pointer;
  border-bottom:2px solid transparent;
  transition:all .2s;
}
.tab:hover{color:var(--white);}
.tab.active{color:var(--cyan);border-bottom-color:var(--cyan);}

/* MAIN */
.main{max-width:1100px;margin:0 auto;padding:2rem 1.5rem 4rem;}

/* STATS */
.stats-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
  gap:14px;
  margin-bottom:2rem;
}
.stat-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:12px;
  padding:20px 24px;
  position:relative;
  overflow:hidden;
}
.stat-card::before{
  content:'';
  position:absolute;top:0;left:0;right:0;height:2px;
}
.stat-card.s1::before{background:var(--cyan);}
.stat-card.s2::before{background:var(--green);}
.stat-card.s3::before{background:var(--pink);}
.stat-card.s4::before{background:var(--yellow);}
.stat-label{font-size:11px;color:var(--muted);letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px;}
.stat-value{
  font-family:'Orbitron',sans-serif;
  font-size:1.4rem;font-weight:700;
}
.s1 .stat-value{color:var(--cyan);text-shadow:0 0 12px rgba(0,245,255,.4);}
.s2 .stat-value{color:var(--green);text-shadow:0 0 12px rgba(57,255,20,.4);}
.s3 .stat-value{color:var(--pink);text-shadow:0 0 12px rgba(255,45,149,.4);}
.s4 .stat-value{color:var(--yellow);text-shadow:0 0 12px rgba(255,230,0,.4);}

/* SECTION */
.section{display:none;}
.section.active{display:block;}
.section-title{
  font-family:'Orbitron',sans-serif;
  font-size:1rem;font-weight:700;
  letter-spacing:.06em;
  color:var(--white);
  margin-bottom:1.2rem;
  display:flex;align-items:center;gap:10px;
}
.section-title::after{content:'';flex:1;height:1px;background:var(--border);}

/* TABLE */
.table-wrap{overflow-x:auto;border-radius:12px;border:1px solid var(--border);}
table{width:100%;border-collapse:collapse;}
thead tr{background:#0d0d0d;}
thead th{
  padding:12px 16px;
  text-align:left;
  font-size:11px;
  font-weight:600;
  letter-spacing:.1em;
  text-transform:uppercase;
  color:var(--muted);
  border-bottom:1px solid var(--border);
  white-space:nowrap;
}
tbody tr{
  border-bottom:1px solid #141414;
  transition:background .15s;
}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:#131313;}
tbody td{
  padding:12px 16px;
  font-size:13.5px;
  color:var(--white);
  vertical-align:middle;
}
.badge{
  display:inline-block;
  padding:3px 10px;
  border-radius:20px;
  font-size:11px;font-weight:600;
  letter-spacing:.05em;
}
.badge-cyan{background:rgba(0,245,255,.1);color:var(--cyan);border:1px solid rgba(0,245,255,.2);}
.badge-green{background:rgba(57,255,20,.1);color:var(--green);border:1px solid rgba(57,255,20,.2);}

/* BAR */
.bar-wrap{display:flex;align-items:center;gap:10px;}
.bar-bg{flex:1;height:6px;background:#1a1a1a;border-radius:3px;overflow:hidden;}
.bar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--cyan),var(--pink));}
.bar-num{font-size:12px;color:var(--muted);min-width:28px;text-align:right;}
</style>
</head>
<body>

<header>
  <div class="header-logo">EL <span>SITIO</span> · ADMIN</div>
  <a class="logout" href="?logout=1">Cerrar sesión</a>
</header>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<div class="tabs">
  <button class="tab active" onclick="showTab('pedidos',this)">📋 Pedidos</button>
  <button class="tab" onclick="showTab('clientes',this)">👥 Clientes</button>
  <button class="tab" onclick="showTab('productos',this)">🏆 Más Vendidos</button>
</div>

<div class="main">

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card s1">
      <div class="stat-label">Total Ventas</div>
      <div class="stat-value"><?= fmt($stats['total_ventas'] ?? 0) ?></div>
    </div>
    <div class="stat-card s2">
      <div class="stat-label">Pedidos</div>
      <div class="stat-value"><?= $stats['num_pedidos'] ?? 0 ?></div>
    </div>
    <div class="stat-card s3">
      <div class="stat-label">Clientes</div>
      <div class="stat-value"><?= $res_clientes->num_rows ?></div>
    </div>
    <div class="stat-card s4">
      <div class="stat-label">Promedio Pedido</div>
      <div class="stat-value"><?= $stats['num_pedidos'] > 0 ? fmt($stats['total_ventas'] / $stats['num_pedidos']) : '$0' ?></div>
    </div>
  </div>

  <!-- PEDIDOS -->
  <div class="section active" id="sec-pedidos">
    <div class="section-title">Pedidos Recientes <span style="font-family:'DM Sans';font-size:.8rem;color:var(--muted);font-weight:400;">últimos 50</span></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php while($p = $res_pedidos->fetch_assoc()): ?>
          <tr>
            <td><span class="badge badge-cyan">#<?= $p['id_pedido'] ?></span></td>
            <td style="color:var(--muted);font-size:12px;"><?= date('d/m/Y H:i', strtotime($p['fecha_pedido'])) ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($p['nombre']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($p['telefono']) ?></td>
            <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($p['direccion']) ?></td>
            <td style="font-family:'Orbitron';font-size:.9rem;color:var(--cyan);"><?= fmt($p['total']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- CLIENTES -->
  <div class="section" id="sec-clientes">
    <div class="section-title">Clientes Registrados</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Dirección</th>
            <th>Pedidos</th>
            <th>Total Gastado</th>
          </tr>
        </thead>
        <tbody>
          <?php while($c = $res_clientes->fetch_assoc()): ?>
          <tr>
            <td style="font-weight:600;"><?= htmlspecialchars($c['nombre']) ?></td>
            <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($c['correo']) ?></td>
            <td style="color:var(--muted);"><?= htmlspecialchars($c['telefono']) ?></td>
            <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($c['direccion']) ?></td>
            <td><span class="badge badge-green"><?= $c['total_pedidos'] ?></span></td>
            <td style="font-family:'Orbitron';font-size:.9rem;color:var(--green);"><?= fmt($c['total_gastado'] ?? 0) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PRODUCTOS MÁS VENDIDOS -->
  <div class="section" id="sec-productos">
    <div class="section-title">Productos Más Vendidos</div>
    <?php
    $productos_data = [];
    $max_unidades = 1;
    while($pr = $res_productos->fetch_assoc()) {
        $productos_data[] = $pr;
        if ($pr['unidades'] > $max_unidades) $max_unidades = $pr['unidades'];
    }
    ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Producto</th>
            <th>Unidades Vendidas</th>
            <th>Ingresos</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($productos_data as $i => $pr): ?>
          <tr>
            <td style="color:var(--muted);font-size:12px;"><?= $i+1 ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($pr['nombre']) ?></td>
            <td>
              <div class="bar-wrap">
                <div class="bar-bg">
                  <div class="bar-fill" style="width:<?= round($pr['unidades']/$max_unidades*100) ?>%"></div>
                </div>
                <div class="bar-num"><?= $pr['unidades'] ?></div>
              </div>
            </td>
            <td style="font-family:'Orbitron';font-size:.9rem;color:var(--pink);"><?= fmt($pr['ingresos']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
function showTab(name, btn) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById('sec-' + name).classList.add('active');
  btn.classList.add('active');
}
</script>
</body>
</html>
