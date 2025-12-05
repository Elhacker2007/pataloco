<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') header("Location: admin.php");
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
        $tc = $_POST['target_career'] ?? '';
        if ($user['role'] === 'admin') {
            if ($tc) header("Location: admin.php?career=".urlencode($tc));
            else if(!empty($_SESSION['career'])) header("Location: admin.php?career=".urlencode($_SESSION['career']));
            else header("Location: admin.php");
        }
        else header("Location: dashboard.php");
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
            <div class="inp-group"><input type="email" name="email" placeholder="Correo" required><i class="fas fa-user"></i></div>
            <div class="inp-group"><input type="password" name="password" placeholder="Clave" required><i class="fas fa-lock"></i></div>
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
            <button type="submit" class="btn-login">INGRESAR</button>
        </form>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;">
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Administración de Negocios Internacionales')">Adm. Negocios</button>
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Arquitectura de Plataformas y Servicios de T.I')">Arquitectura TI</button>
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Contabilidad')">Contabilidad</button>
            <button type="button" class="btn-login" style="background:#3a7bd5" onclick="setC('Desarrollo Pesquero y Acuícola')">Pesquero/Acuícola</button>
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
