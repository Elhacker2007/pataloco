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
        if ($user['role'] === 'admin') header("Location: admin.php");
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
    <title>Acceso Pataloco</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-mode">
    <div class="overlay"></div>
    <div class="login-box">
        <h2>SISTEMA DE MANDO</h2>
        <p>Inicia sesión para continuar</p>
        <?php if($msg): ?><div style="color:red; margin-bottom:10px;"><?= $msg ?></div><?php endif; ?>
        <form method="POST">
            <div class="inp-group"><input type="email" name="email" placeholder="Correo" required><i class="fas fa-user"></i></div>
            <div class="inp-group"><input type="password" name="password" placeholder="Clave" required><i class="fas fa-lock"></i></div>
            <button type="submit" class="btn-login">INGRESAR</button>
        </form>
        <div style="margin-top:20px;">
            <a href="registro.php" style="color:#00d2ff;">Crear cuenta</a> | 
            <a href="olvide_password.php" style="color:#aaa;">Recuperar</a>
        </div>
    </div>
</body>
</html>