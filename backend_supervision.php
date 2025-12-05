<?php
session_start(); require 'db.php'; date_default_timezone_set('America/Lima');
$act = $_POST['accion'] ?? ''; $uid = $_SESSION['user_id'] ?? 0;
if($uid==0) die('No auth');
$stmt=$pdo->prepare("SELECT role FROM users WHERE id=?"); $stmt->execute([$uid]);
$roleRow=$stmt->fetch(); $role=$roleRow['role']??''; $adm=($role==='admin'); $prof=($role==='professor');

if($act=='actualizar_gps'){
    $sql="INSERT INTO ubicaciones (user_id,latitud,longitud,fecha) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE latitud=VALUES(latitud),longitud=VALUES(longitud),fecha=NOW()";
    $pdo->prepare($sql)->execute([$uid,$_POST['lat'],$_POST['lng']]);
}
if($act=='leer_todos_gps' && ($adm || $prof)){
    if($adm){
        $car = $_POST['career'] ?? '';
        $sql = "SELECT u.username, u.career, l.latitud, l.longitud, TIMESTAMPDIFF(SECOND,l.fecha,NOW()) as s FROM ubicaciones l JOIN users u ON l.user_id=u.id";
        $p=[];
        if($car && $car!=='Todos'){ $sql .= " WHERE u.career=?"; $p=[$car]; }
        $sql .= " ORDER BY u.username";
        $st=$pdo->prepare($sql); $st->execute($p);
        $res=array_map(function($u){
            $s=$u['s'];
            if($s<60){ $u['st']='online'; $u['tx']='EN VIVO'; }
            elseif($s<3600){ $u['st']='away'; $u['tx']='Hace '.intval($s/60).'m'; }
            else{ $u['st']='offline'; $u['tx']='Desconectado'; }
            return $u;
        }, $st->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode($res);
    } else { // profesor
        $sql = "SELECT u.username, u.career, l.latitud, l.longitud, TIMESTAMPDIFF(SECOND,l.fecha,NOW()) as s FROM ubicaciones l JOIN users u ON l.user_id=u.id WHERE u.career=(SELECT career FROM users WHERE id=?) ORDER BY u.username";
        $st=$pdo->prepare($sql); $st->execute([$uid]);
        $res=array_map(function($u){
            $s=$u['s'];
            if($s<60){ $u['st']='online'; $u['tx']='EN VIVO'; }
            elseif($s<3600){ $u['st']='away'; $u['tx']='Hace '.intval($s/60).'m'; }
            else{ $u['st']='offline'; $u['tx']='Desconectado'; }
            return $u;
        }, $st->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode($res);
    }
}
if($act=='enviar_mensaje'){ $pdo->prepare("INSERT INTO chat (user_id,mensaje) VALUES (?,?)")->execute([$uid,strip_tags($_POST['mensaje'])]); }
if($act=='leer_chat'){
    if($adm){
        $car = $_POST['career'] ?? '';
        $sql = "SELECT c.id,c.mensaje,c.fecha,u.username,u.career,l.latitud,l.longitud,TIMESTAMPDIFF(SECOND,l.fecha,NOW()) as s FROM chat c JOIN users u ON c.user_id=u.id LEFT JOIN ubicaciones l ON l.user_id=u.id";
        $p=[];
        if($car && $car!=='Todos'){ $sql .= " WHERE u.career=?"; $p=[$car]; }
        $sql .= " ORDER BY c.fecha DESC LIMIT 50";
        $st=$pdo->prepare($sql); $st->execute($p);
        $res=array_map(function($u){
            $s=$u['s']??999999; if($s<60){$u['st']='online';$u['tx']='EN VIVO';} elseif($s<3600){$u['st']='away';$u['tx']='Hace '.intval($s/60).'m';} else {$u['st']='offline';$u['tx']='Desconectado';}
            return $u;
        }, $st->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode(array_reverse($res));
    } else {
        $sql = "SELECT c.id,c.mensaje,c.fecha,u.username,u.career,l.latitud,l.longitud,TIMESTAMPDIFF(SECOND,l.fecha,NOW()) as s FROM chat c JOIN users u ON c.user_id=u.id LEFT JOIN ubicaciones l ON l.user_id=u.id WHERE u.career=(SELECT career FROM users WHERE id=?) ORDER BY c.fecha DESC LIMIT 50";
        $st=$pdo->prepare($sql); $st->execute([$uid]);
        $res=array_map(function($u){
            $s=$u['s']??999999; if($s<60){$u['st']='online';$u['tx']='EN VIVO';} elseif($s<3600){$u['st']='away';$u['tx']='Hace '.intval($s/60).'m';} else {$u['st']='offline';$u['tx']='Desconectado';}
            return $u;
        }, $st->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode(array_reverse($res));
    }
}
if($act=='set_admin_career' && $adm){
    $car = $_POST['career'] ?? '';
    $allowed = [
        'Administración de Negocios Internacionales',
        'Arquitectura de Plataformas y Servicios de T.I',
        'Contabilidad',
        'Desarrollo Pesquero y Acuícola'
    ];
    if(in_array($car,$allowed,true)){
        $pdo->prepare("UPDATE users SET career=? WHERE id=?")->execute([$car,$uid]);
        echo 'ok';
    } else { echo 'invalid'; }
}
if($act=='subir_foto' && isset($_FILES['foto'])){
    if(!is_dir('uploads')){mkdir('uploads',0777,true);} $n=time()."_".$_FILES['foto']['name']; move_uploaded_file($_FILES['foto']['tmp_name'],"uploads/$n");
    $pdo->prepare("INSERT INTO evidencias (user_id,ruta_foto,descripcion) VALUES (?,?,?)")->execute([$uid,"uploads/$n",$_POST['descripcion']]); echo "Subido";
}
if($act=='borrar_mensaje' && $adm){ $pdo->prepare("DELETE FROM chat WHERE id=?")->execute([$_POST['id']]); }
if($act=='borrar_evidencia' && $adm){ 
    $f=$pdo->prepare("SELECT ruta_foto FROM evidencias WHERE id=?"); $f->execute([$_POST['id']]);
    if($r=$f->fetch()){ if(file_exists($r['ruta_foto'])) unlink($r['ruta_foto']); }
    $pdo->prepare("DELETE FROM evidencias WHERE id=?")->execute([$_POST['id']]); 
}
?>
