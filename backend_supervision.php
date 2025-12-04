<?php
session_start(); require 'db.php'; date_default_timezone_set('America/Lima');
$act = $_POST['accion'] ?? ''; $uid = $_SESSION['user_id'] ?? 0;
if($uid==0) die('No auth');
$stmt=$pdo->prepare("SELECT role FROM users WHERE id=?"); $stmt->execute([$uid]); $adm=($stmt->fetch()['role']==='admin');

if($act=='actualizar_gps'){
    $sql="INSERT INTO ubicaciones (user_id,latitud,longitud,fecha) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE latitud=VALUES(latitud),longitud=VALUES(longitud),fecha=NOW()";
    $pdo->prepare($sql)->execute([$uid,$_POST['lat'],$_POST['lng']]);
}
if($act=='leer_todos_gps' && $adm){
    $sql="SELECT u.username, l.latitud, l.longitud, TIMESTAMPDIFF(SECOND,l.fecha,NOW()) as s FROM ubicaciones l JOIN users u ON l.user_id=u.id ORDER BY u.username";
    $res=array_map(function($u){
        $s=$u['s'];
        if($s<60){ $u['st']='online'; $u['tx']='EN VIVO'; }
        elseif($s<3600){ $u['st']='away'; $u['tx']='Hace '.intval($s/60).'m'; }
        else{ $u['st']='offline'; $u['tx']='Desconectado'; }
        return $u;
    }, $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
    echo json_encode($res);
}
if($act=='enviar_mensaje'){ $pdo->prepare("INSERT INTO chat (user_id,mensaje) VALUES (?,?)")->execute([$uid,strip_tags($_POST['mensaje'])]); }
if($act=='leer_chat'){ echo json_encode(array_reverse($pdo->query("SELECT c.id,c.mensaje,c.fecha,u.username FROM chat c JOIN users u ON c.user_id=u.id ORDER BY c.fecha DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC))); }
if($act=='subir_foto' && isset($_FILES['foto'])){
    $n=time()."_".$_FILES['foto']['name']; move_uploaded_file($_FILES['foto']['tmp_name'],"uploads/$n");
    $pdo->prepare("INSERT INTO evidencias (user_id,ruta_foto,descripcion) VALUES (?,?,?)")->execute([$uid,"uploads/$n",$_POST['descripcion']]); echo "Subido";
}
if($act=='borrar_mensaje' && $adm){ $pdo->prepare("DELETE FROM chat WHERE id=?")->execute([$_POST['id']]); }
if($act=='borrar_evidencia' && $adm){ 
    $f=$pdo->prepare("SELECT ruta_foto FROM evidencias WHERE id=?"); $f->execute([$_POST['id']]);
    if($r=$f->fetch()){ if(file_exists($r['ruta_foto'])) unlink($r['ruta_foto']); }
    $pdo->prepare("DELETE FROM evidencias WHERE id=?")->execute([$_POST['id']]); 
}
?>