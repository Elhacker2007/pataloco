<?php
require 'db.php';
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $chk->execute([$email]);
    if($chk->rowCount() > 0) { $msg = "Correo ya existe."; } 
    else {
        $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')")->execute([$user, $email, $pass]);
        echo "<script>alert('¡Listo! Ingresa.'); window.location='index.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-mode">
    <div class="overlay"></div>
    <div class="login-box">
        <h2>NUEVO RECLUTA</h2>
        <?php if($msg): ?><div style="color:red;"><?= $msg ?></div><?php endif; ?>
        <form method="POST">
            <div class="inp-group"><input type="text" name="username" placeholder="Usuario" required><i class="fas fa-id-card"></i></div>
            <div class="inp-group"><input type="email" name="email" placeholder="Correo" required><i class="fas fa-envelope"></i></div>
            <div class="inp-group"><input type="password" name="password" placeholder="Clave" required><i class="fas fa-lock"></i></div>
            <button type="submit" class="btn-login">REGISTRARME</button>
        </form>
        <div style="margin-top:20px;"><a href="index.php" style="color:#00d2ff;">Volver</a></div>
    </div>
</body>
</html>