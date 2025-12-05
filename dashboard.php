<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: index.php");exit;}
if($_SESSION['role']=='admin'){header("Location: admin.php");exit;}
require 'db.php';
$st=$pdo->prepare("SELECT career FROM users WHERE id=?"); $st->execute([$_SESSION['user_id']]);
$career=$st->fetch()['career'] ?? 'Carrera';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Instituto Hermanos Cárcamo - Estudiante</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        body{background:#f2f2f7;font-family:sans-serif;margin:0;padding-bottom:70px;}
        .head{background:#007AFF;color:white;padding:15px;display:flex;justify-content:space-between;}
        .card{background:white;margin:15px;padding:15px;border-radius:15px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        #map{height:200px;background:#ddd;border-radius:10px;}
        .btn{width:100%;padding:15px;background:#34C759;color:white;border:none;border-radius:10px;margin-top:10px;font-weight:bold;}
        .tabs{position:fixed;bottom:0;width:100%;background:white;display:flex;padding:10px 0;border-top:1px solid #ccc;}
        .tab{flex:1;text-align:center;color:#999;} .tab.active{color:#007AFF;}
        .v{display:none;} .v.active{display:block;}
        #chat-box{height:300px;overflow-y:auto;background:#eee;padding:10px;}
        .msg{background:white;padding:8px;border-radius:10px;margin:5px 0;width:fit-content;}
        .me{background:#007AFF;color:white;align-self:flex-end;margin-left:auto;}
    </style>
</head>
<body>
<div class="head"><b>Portal Estudiante – <?=$career?></b><a href="logout.php" style="color:white;"><i class="fas fa-power-off"></i></a></div>

<div id="v1" class="v active">
    <div class="card">
        <h3>GPS</h3><div id="map"></div>
        <button class="btn" onclick="gps()">ACTIVAR</button>
        <div id="st" style="text-align:center;margin-top:5px;">Inactivo</div>
    </div>
    <div class="card">
        <h3>Foto</h3>
        <form id="ff"><input type="file" name="foto" capture="environment" required><br><input type="text" name="descripcion" placeholder="Desc" style="width:100%;margin:10px 0;padding:10px;"><button class="btn" style="background:#007AFF">ENVIAR</button></form>
    </div>
</div>

<div id="v2" class="v">
    <div class="card"><h3>Chat</h3><div id="chat-box" style="display:flex;flex-direction:column;"></div>
    <input id="tm" style="width:70%;padding:10px;"><button onclick="send()" style="width:25%;padding:10px;">></button></div>
</div>

<div class="tabs">
    <div class="tab active" onclick="sw('1',this)"><i class="fas fa-map"></i></div>
    <div class="tab" onclick="sw('2',this)"><i class="fas fa-comment"></i></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function sw(n,e){document.querySelectorAll('.v').forEach(x=>x.classList.remove('active'));document.getElementById('v'+n).classList.add('active');document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));e.classList.add('active');}
var map=L.map('map').setView([-12,-77],13); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map); var mk=L.marker([-12,-77]).addTo(map);
function gps(){
    if(!navigator.geolocation)return alert('Sin GPS'); document.getElementById('st').innerText='Conectando...';
    navigator.geolocation.watchPosition(p=>{
        var lat=p.coords.latitude, lng=p.coords.longitude;
        mk.setLatLng([lat,lng]); map.panTo([lat,lng]);
        document.getElementById('st').innerHTML='<b style="color:green">EN VIVO</b>';
        fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'actualizar_gps',lat:lat,lng:lng})});
    },null,{enableHighAccuracy:true});
}
const me="<?=$_SESSION['username']?>";
function ch(){fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'leer_chat'})}).then(r=>r.json()).then(d=>{
    let h=''; d.forEach(m=>{h+=`<div class="msg ${m.username==me?'me':''}">${m.mensaje}</div>`});
    document.getElementById('chat-box').innerHTML=h;
})}
function send(){let t=document.getElementById('tm').value;if(t){fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'enviar_mensaje',mensaje:t})}).then(()=>{document.getElementById('tm').value='';ch()});}}
document.getElementById('ff').onsubmit=function(e){e.preventDefault();fetch('backend_supervision.php',{method:'POST',body:new FormData(this)}).then(r=>r.text()).then(d=>{if(d.includes('Subido'))alert('Ok');this.reset()})};
setInterval(ch,2000); ch();
</script>
</body>
</html>
