<?php
// ==========================================
// RECUPERACIÓN INTELIGENTE (Detecta si hay librería)
// ==========================================
require 'db.php';

$mensaje = "";
$tipo_alerta = "";
$link_simulado = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_usuario = trim($_POST['email']);
    
    // 1. Verificar usuario en BD
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email_usuario]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(50));
        $expDate = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Guardar Token
        $pdo->prepare("INSERT INTO password_resets (email, token, exp_date) VALUES (?, ?, ?)")
            ->execute([$email_usuario, $token, $expDate]);

        // Generar Link (Detectando Ngrok o Localhost)
        $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $dominio = $_SERVER['HTTP_HOST'];
        $link = "$protocolo://$dominio/pataloco/recuperar.php?token=$token&email=$email_usuario";

        // 2. INTENTAR ENVIAR CORREO (VERIFICANDO SI EXISTE LA LIBRERÍA)
        if (file_exists('PHPMailer/PHPMailer.php')) {
            // --- BLOQUE PHPMAILER ---
            require 'PHPMailer/Exception.php';
            require 'PHPMailer/PHPMailer.php';
            require 'PHPMailer/SMTP.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'misaelpintado7@gmail.com'; // <--- TU CORREO
                $mail->Password   = 'pboy ikvo inoj pyeo';      // <--- TU CLAVE
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('misaelpintado7@gmail.com', 'Soporte Pataloco');
                $mail->addAddress($email_usuario);
                $mail->isHTML(true);
                $mail->Subject = 'Recuperar Clave';
                $mail->Body = "Hola <b>{$usuario['username']}</b>, clic aquí: <br><a href='$link'>$link</a>";
                
                $mail->send();
                $mensaje = "¡Correo enviado! Revisa tu bandeja.";
                $tipo_alerta = "success";
            } catch (Exception $e) {
                $mensaje = "Falló el envío, pero aquí tienes el link: <br><a href='$link' style='color:#fff'>CLICK AQUÍ PARA RECUPERAR</a>";
                $tipo_alerta = "warning";
            }
        } else {
            // --- MODO SIMULACIÓN (SI FALTA LA CARPETA) ---
            // Esto evita el FATAL ERROR que te salía antes
            $mensaje = "Modo Local (Sin librería de correo).<br>Usa este enlace para continuar:";
            $link_simulado = $link;
            $tipo_alerta = "success";
        }

    } else {
        $mensaje = "Si el correo existe, se enviaron instrucciones.";
        $tipo_alerta = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acceso</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .msg-box { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align: left; word-break: break-all; }
        .msg-success { background: rgba(0, 255, 136, 0.2); border: 1px solid #00ff88; color: #ccffdd; }
        .msg-warning { background: rgba(255, 165, 0, 0.2); border: 1px solid orange; color: orange; }
        .sim-btn { display:block; margin-top:10px; background:#00d2ff; color:white; padding:10px; text-decoration:none; border-radius:5px; text-align:center; font-weight:bold; }
    </style>
</head>
<body class="login-mode">
    <div class="login-overlay"></div>
    <div class="login-box">
        <div style="font-size: 3rem; margin-bottom: 10px; color: #00d2ff;"><i class="fas fa-shield-alt"></i></div>
        <h2>Recuperar</h2>
        
        <?php if($mensaje): ?>
            <div class="msg-box <?= ($tipo_alerta == 'success') ? 'msg-success' : 'msg-warning' ?>">
                <?= $mensaje ?>
                <?php if($link_simulado): ?>
                    <a href="<?= $link_simulado ?>" class="sim-btn">ABRIR ENLACE DE RECUPERACIÓN</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Ingresa tu correo registrado</p>
        <?php endif; ?>

        <?php if(!$link_simulado): ?>
        <form method="POST">
            <div class="inp-group">
                <input type="email" name="email" placeholder="Correo" required>
                <i class="fas fa-envelope"></i>
            </div>
            <button type="submit" class="btn-login">ENVIAR</button>
        </form>
        <?php endif; ?>

        <div style="margin-top: 25px;"><a href="index.php" style="color: #ccc;">Volver al Login</a></div>
    </div>
</body>
</html>