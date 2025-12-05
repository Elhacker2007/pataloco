<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    $retGet = $_GET['return'] ?? '';
    if ($retGet && strpos($retGet,'admin.php')===0 && ($_SESSION['role']==='admin' || $_SESSION['role']==='professor')) { header("Location: $retGet"); exit(); }
    if ($retGet && strpos($retGet,'dashboard.php')===0 && $_SESSION['role']==='student') { header("Location: $retGet"); exit(); }
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'professor') header("Location: admin.php");
    else header("Location: dashboard.php");
    exit();
}

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['career'] = $user['career'] ?? '';
        $ret = $_POST['return'] ?? '';
        $tc = $_POST['target_career'] ?? '';
        if ($user['role'] === 'admin') {
            if ($ret && strpos($ret,'admin.php')===0) header("Location: $ret");
            elseif ($tc) header("Location: admin.php?career=".urlencode($tc));
            elseif(!empty($_SESSION['career'])) header("Location: admin.php?career=".urlencode($_SESSION['career']));
            else header("Location: admin.php");
        } elseif ($user['role'] === 'professor') {
            $car = $_SESSION['career'] ?: $tc;
            if ($ret && strpos($ret,'admin.php')===0) header("Location: $ret");
            else header("Location: admin.php?career=".urlencode($car));
        } else {
            if ($ret && strpos($ret,'dashboard.php')===0) header("Location: $ret");
            else header("Location: dashboard.php");
        }
        exit();
    } else {
        $msg = "Error: Credenciales incorrectas";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IHC – Sistema de Prácticas</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-mode">
    <div class="overlay"></div>
    <div class="login-box">
        <h2>IHC – Acceso</h2>
        <p>Conéctate por tu carrera y rol</p>
        <?php if($msg): ?><div style="color:red; margin-bottom:10px;<?= '' ?>"><?= $msg ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="return" value="<?= htmlspecialchars($_GET['return'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="inp-group"><input type="email" name="email" placeholder="Correo" required><i class="fas fa-user"></i></div>
            <div class="inp-group"><input type="password" name="password" placeholder="Clave" required><i class="fas fa-lock"></i></div>
            <?php $prefCar = $_GET['career'] ?? ''; $slug = $_GET['career_slug'] ?? ''; $map = [
                'negocios-internacionales'=>'Administración de Negocios Internacionales',
                'arquitectura-ti'=>'Arquitectura de Plataformas y Servicios de T.I',
                'contabilidad'=>'Contabilidad',
                'pesquero-acuicola'=>'Desarrollo Pesquero y Acuícola',
                'todos'=>'Todos'
            ]; if(!$prefCar && $slug && isset($map[$slug])) $prefCar=$map[$slug]; if($prefCar): ?>
            <input type="hidden" name="target_career" value="<?= htmlspecialchars($prefCar,ENT_QUOTES,'UTF-8') ?>">
            <div style="color:#00d2ff; text-align:center; margin-bottom:10px;">Carrera: <?= htmlspecialchars($prefCar,ENT_QUOTES,'UTF-8') ?></div>
            <?php else: ?>
            <div class="inp-group">
                <select name="target_career" style="width:100%;padding:12px;background:rgba(0,0,0,0.5);border:1px solid rgba(255,255,255,0.2);border-radius:30px;color:white;">
                    <option value="">(Seleccionar carrera para supervisión)</option>
                    <option>Administración de Negocios Internacionales</option>
                    <option>Arquitectura de Plataformas y Servicios de T.I</option>
                    <option>Contabilidad</option>
                    <option>Desarrollo Pesquero y Acuícola</option>
                    <option>Todos</option>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn-login">INGRESAR</button>
        </form>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;">
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Administración de Negocios Internacionales')">Adm. Negocios</button>
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Arquitectura de Plataformas y Servicios de T.I')">Arquitectura TI</button>
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Contabilidad')">Contabilidad</button>
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Desarrollo Pesquero y Acuícola')">Pesquero/Acuícola</button>
        </div>
        <div style="margin-top:12px; display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <a class="btn-login" style="text-align:center;background:#2b5876" href="registro.php?career=Administraci%C3%B3n%20de%20Negocios%20Internacionales">Registro Adm. Negocios</a>
            <a class="btn-login" style="text-align:center;background:#2b5876" href="registro.php?career=Arquitectura%20de%20Plataformas%20y%20Servicios%20de%20T.I">Registro Arquitectura TI</a>
            <a class="btn-login" style="text-align:center;background:#2b5876" href="registro.php?career=Contabilidad">Registro Contabilidad</a>
            <a class="btn-login" style="text-align:center;background:#2b5876" href="registro.php?career=Desarrollo%20Pesquero%20y%20Acu%C3%ADcola">Registro Pesquero/Acuícola</a>
        </div>
        <script>
        function setC(v){var s=document.querySelector('select[name="target_career"]'); if(s){s.value=v;}}
        </script>
        <div style="margin-top:20px;">
            <a href="registro.php" style="color:#00d2ff;">Crear cuenta</a> | 
            <a href="olvide_password.php" style="color:#aaa;">Recuperar</a>
        </div>
    </div>
</body>
</html>
