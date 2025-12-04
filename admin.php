<?php
session_start(); require 'db.php';
if(!isset($_SESSION['user_id'])){header("Location: index.php");exit;}
$stmt=$pdo->prepare("SELECT role FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]);
if($stmt->fetch()['role']!=='admin'){header("Location: dashboard.php");exit;}
$ev=$pdo->query("SELECT e.*,u.username FROM evidencias e JOIN users u ON e.user_id=u.id ORDER BY e.fecha DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OJO DE DIOS</title>
    <link rel="stylesheet" href="estilos.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-mode">
<div class="sidebar">
    <div class="brand"><i class="fas fa-eye"></i> OJO DE DIOS</div>
    <div class="user-list" id="ulist"></div>
    <div style="padding:10px;text-align:center;"><a href="logout.php" style="color:#666;">SALIR</a></div>
</div>
<div class="main">
    <div id="map"></div>
    <div class="map-btn" onclick="verTodos()">VER GLOBAL</div>
    <div class="dock">
        <div class="col"><div class="head">CHAT</div><div class="chat-scroll" id="chat"></div></div>
        <div class="col" style="flex:1.5;"><div class="head">FOTOS</div><div class="gal-scroll">
            <?php foreach($ev as $e): ?>
                <div class="photo">
                    <a href="<?=$e['ruta_foto']?>" target="_blank"><img src="<?=$e['ruta_foto']?>"></a>
                    <div class="photo-del" onclick="delEv(<?=$e['id']?>,this)">x</div>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var map=L.map('map').setView([-12,-77],5);
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);
var markers={}, bounds=L.latLngBounds();

function up(){
    fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'leer_todos_gps'})})
    .then(r=>r.json()).then(d=>{
        let h='', curB=L.latLngBounds();
        d.forEach(u=>{
            let lat=parseFloat(u.latitud), lng=parseFloat(u.longitud);
            let col = u.status=='online'?'#0f0':(u.status=='away'?'orange':'#555');
            if(markers[u.username]) markers[u.username].setLatLng([lat,lng]);
            else {
                let i=L.divIcon({className:'p',html:`<div style="background:${col};width:10px;height:10px;border-radius:50%;box-shadow:0 0 10px ${col};border:2px solid white"></div>`});
                markers[u.username]=L.marker([lat,lng],{icon:i}).addTo(map).bindPopup(u.username);
            }
            curB.extend([lat,lng]);
            h+=`<div class="user-card" onclick="map.flyTo([${lat},${lng}],16)">
                <div class="uc-info"><b>${u.username}</b><span>GPS:${lat.toFixed(3)}</span></div>
                <div class="badge ${u.status=='online'?'b-on':'b-off'}">${u.tx}</div></div>`;
        });
        document.getElementById('ulist').innerHTML=h;
        bounds=curB;
        if(!window.l && d.length>0){ map.fitBounds(bounds); window.l=true; }
    });
}
function chat(){
    fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'leer_chat'})})
    .then(r=>r.json()).then(d=>{
        let h=''; d.forEach(m=>{
            h+=`<div class="chat-row"><i class="fas fa-times del-btn" onclick="delC(${m.id})"></i><strong>${m.username}:</strong> ${m.mensaje}</div>`;
        });
        document.getElementById('chat').innerHTML=h;
    });
}
window.delC=function(id){if(confirm('Borrar?'))fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'borrar_mensaje',id:id})}).then(chat)};
window.delEv=function(id,e){if(confirm('Borrar?'))fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'borrar_evidencia',id:id})}).then(()=>e.parentElement.remove())};
window.verTodos=function(){if(bounds.isValid())map.fitBounds(bounds)};
setInterval(up,2000); setInterval(chat,2000); up(); chat();
</script>
</body>
</html>