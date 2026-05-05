<?php
session_start();

$admin_user = "admin";
$admin_pass = "elsitio2024";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['usuario'] === $admin_user && $_POST['clave'] === $admin_pass) {
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin · El Sitio</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#090909;
  --card:#111111;
  --cyan:#00F5FF;
  --pink:#FF2D95;
  --green:#39FF14;
  --yellow:#FFE600;
  --white:#F0F0F0;
  --muted:#666;
}
body{
  font-family:'DM Sans',sans-serif;
  background:var(--bg);
  color:var(--white);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  overflow:hidden;
}
body::before{
  content:'';
  position:absolute;
  inset:0;
  background:radial-gradient(ellipse at 50% 40%, #0a001a 0%, #090909 70%);
}
.grid-bg{
  position:absolute;
  inset:0;
  background-image:
    linear-gradient(rgba(0,245,255,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,245,255,.03) 1px, transparent 1px);
  background-size:40px 40px;
}
.box{
  position:relative;
  z-index:1;
  background:var(--card);
  border:1px solid #1e1e1e;
  border-radius:16px;
  padding:48px 40px;
  width:100%;
  max-width:380px;
  box-shadow:0 0 60px rgba(0,245,255,.06), 0 30px 80px rgba(0,0,0,.6);
}
.box::before{
  content:'';
  position:absolute;
  top:0;left:0;right:0;
  height:2px;
  border-radius:16px 16px 0 0;
  background:linear-gradient(90deg, var(--pink), var(--cyan), var(--green));
}
.logo-area{
  text-align:center;
  margin-bottom:36px;
}
.logo-area .icon{
  font-size:2.5rem;
  margin-bottom:12px;
}
.logo-area h1{
  font-family:'Orbitron',sans-serif;
  font-size:1.1rem;
  font-weight:900;
  color:var(--cyan);
  text-shadow:0 0 16px rgba(0,245,255,.5);
  letter-spacing:.06em;
}
.logo-area p{
  font-size:12px;
  color:var(--muted);
  margin-top:4px;
  letter-spacing:.1em;
  text-transform:uppercase;
}
.field{
  margin-bottom:16px;
}
.field label{
  display:block;
  font-size:11px;
  font-weight:600;
  letter-spacing:.12em;
  text-transform:uppercase;
  color:var(--muted);
  margin-bottom:8px;
}
.field input{
  width:100%;
  background:#0d0d0d;
  border:1px solid #222;
  border-radius:8px;
  padding:12px 16px;
  color:var(--white);
  font-family:'DM Sans',sans-serif;
  font-size:15px;
  outline:none;
  transition:border-color .2s, box-shadow .2s;
}
.field input:focus{
  border-color:var(--cyan);
  box-shadow:0 0 12px rgba(0,245,255,.12);
}
.error{
  background:rgba(255,45,149,.1);
  border:1px solid rgba(255,45,149,.3);
  border-radius:8px;
  padding:10px 14px;
  font-size:13px;
  color:var(--pink);
  margin-bottom:16px;
  text-align:center;
}
.btn{
  width:100%;
  background:var(--cyan);
  color:#000;
  border:none;
  border-radius:8px;
  padding:13px;
  font-family:'DM Sans',sans-serif;
  font-size:15px;
  font-weight:700;
  cursor:pointer;
  margin-top:8px;
  letter-spacing:.04em;
  transition:background .2s, box-shadow .2s, transform .1s;
}
.btn:hover{
  background:#14e0ff;
  box-shadow:0 0 20px rgba(0,245,255,.3);
}
.btn:active{transform:scale(.98);}
</style>
</head>
<body>
<div class="grid-bg"></div>
<div class="box">
  <div class="logo-area">
    <div class="icon">🍔</div>
    <h1>EL SITIO</h1>
    <p>Panel de Administración</p>
  </div>
  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="field">
      <label>Usuario</label>
      <input type="text" name="usuario" placeholder="admin" required autofocus/>
    </div>
    <div class="field">
      <label>Contraseña</label>
      <input type="password" name="clave" placeholder="••••••••" required/>
    </div>
    <button class="btn" type="submit">Ingresar →</button>
  </form>
</div>
</body>
</html>
