<?php
session_start(); require 'db.php';
if(!isset($_SESSION['user_id'])){header("Location: index.php");exit;}
$stmt=$pdo->prepare("SELECT role,career FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]);
$u=$stmt->fetch(); $isAdmin=($u['role']==='admin'); $isProfessor=($u['role']==='professor');
if(!$isAdmin && !$isProfessor){header("Location: dashboard.php");exit;}
$myCareer = $u['career'] ?: 'Administración de Negocios Internacionales';
$defaultCareer = $isAdmin ? ($_GET['career'] ?? 'Todos') : $myCareer;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instituto Hermanos Cárcamo - Supervisión</title>
    <link rel="stylesheet" href="estilos.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-mode">
<div class="sidebar">
    <div class="brand"><i class="fas fa-eye"></i> Supervisión</div>
    <div style="padding:10px">
        <?php if($isAdmin): ?>
            <select id="carSel" style="width:100%;padding:10px;background:#111;color:#0f0;border:1px solid #333;">
                <option <?= $defaultCareer==='Todos'? 'selected':'' ?>>Todos</option>
                <option <?= $defaultCareer==='Administración de Negocios Internacionales'? 'selected':'' ?>>Administración de Negocios Internacionales</option>
                <option <?= $defaultCareer==='Arquitectura de Plataformas y Servicios de T.I'? 'selected':'' ?>>Arquitectura de Plataformas y Servicios de T.I</option>
                <option <?= $defaultCareer==='Contabilidad'? 'selected':'' ?>>Contabilidad</option>
                <option <?= $defaultCareer==='Desarrollo Pesquero y Acuícola'? 'selected':'' ?>>Desarrollo Pesquero y Acuícola</option>
            </select>
        <?php else: ?>
            <div style="color:#0f0;margin-bottom:6px">Tu carrera: <b><?= htmlspecialchars($myCareer,ENT_QUOTES,'UTF-8') ?></b></div>
        <?php endif; ?>
    </div>
    <div class="user-list" id="ulist"></div>
    <div style="padding:10px;text-align:center;"><a href="logout.php" style="color:#666;">SALIR</a></div>
</div>
<div class="main">
    <div id="map"></div>
    <div class="map-btn" onclick="verTodos()">VER GLOBAL</div>
    <div class="dock">
        <div class="col"><div class="head">CHAT</div><div class="chat-scroll" id="chat"></div>
            <div style="padding:8px;border-top:1px solid #333;display:flex;gap:6px;">
                <input id="adm_msg" style="flex:1;padding:8px;background:#111;color:#0f0;border:1px solid #333;">
                <button onclick="admSend()" style="padding:8px 12px;background:#222;color:#0f0;border:1px solid #333;">Enviar</button>
            </div>
        </div>
        <div class="col" style="flex:1.5;"><div class="head">FOTOS</div><div class="gal-scroll">
            <?php 
            $car = $_GET['career'] ?? '';
            $q = "SELECT e.*,u.username FROM evidencias e JOIN users u ON e.user_id=u.id";
            $p = [];
            if($car && $car!=='Todos'){ $q .= " WHERE u.career=?"; $p = [$car]; }
            $q .= " ORDER BY e.fecha DESC";
            $st=$pdo->prepare($q); $st->execute($p); $ev=$st->fetchAll();
            foreach($ev as $e): ?>
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
var DEF_CAR = "<?= addslashes($defaultCareer) ?>";
var IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

function up(){
    var c=(document.getElementById('carSel')?document.getElementById('carSel').value:DEF_CAR);
    fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'leer_todos_gps',career:c})})
    .then(r=>r.json()).then(d=>{
        let h='', curB=L.latLngBounds();
        d.forEach(u=>{
            let lat=parseFloat(u.latitud), lng=parseFloat(u.longitud);
            let col = u.st=='online'?'#0f0':(u.st=='away'?'orange':'#555');
            if(markers[u.username]) markers[u.username].setLatLng([lat,lng]);
            else {
                let i=L.divIcon({className:'p',html:`<div style="background:${col};width:10px;height:10px;border-radius:50%;box-shadow:0 0 10px ${col};border:2px solid white"></div>`});
                markers[u.username]=L.marker([lat,lng],{icon:i}).addTo(map).bindPopup(u.username);
            }
            curB.extend([lat,lng]);
            h+=`<div class="user-card" onclick="map.flyTo([${lat},${lng}],16)">
                <div class="uc-info"><b>${u.username}</b><span>${u.career||''}</span><span>GPS:${lat.toFixed(3)}</span></div>
                <div class="badge ${u.st=='online'?'b-on':'b-off'}">${u.tx}</div></div>`;
        });
        document.getElementById('ulist').innerHTML=h;
        bounds=curB;
        if(!window.l && d.length>0){ map.fitBounds(bounds); window.l=true; }
    });
}
function chat(){
    var c=(document.getElementById('carSel')?document.getElementById('carSel').value:DEF_CAR);
    fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'leer_chat',career:c})})
    .then(r=>r.json()).then(d=>{
        let h=''; d.forEach(m=>{
            let lat=m.latitud?parseFloat(m.latitud).toFixed(3):'-'; let lng=m.longitud?parseFloat(m.longitud).toFixed(3):'-';
            h+=`<div class="chat-row"><i class="fas fa-times del-btn" onclick="delC(${m.id})"></i><strong>${m.username}</strong> <span style="color:#0f0">${m.tx}</span> <span style="color:#999">GPS:${lat},${lng}</span><br>${m.mensaje}</div>`;
        });
        document.getElementById('chat').innerHTML=h;
    });
}
window.delC=function(id){if(confirm('Borrar?'))fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'borrar_mensaje',id:id})}).then(chat)};
window.delEv=function(id,e){if(confirm('Borrar?'))fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'borrar_evidencia',id:id})}).then(()=>e.parentElement.remove())};
window.verTodos=function(){if(bounds.isValid())map.fitBounds(bounds)};
if(document.getElementById('carSel')){
    document.getElementById('carSel').onchange=function(){
        var c=this.value;
        if(IS_ADMIN){
            fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'set_admin_career',career:c})}).then(()=>{history.replaceState(null,'',`?career=${encodeURIComponent(c)}`); up(); chat(); window.l=false;});
        } else { up(); chat(); window.l=false; }
    };
}
setInterval(up,2000); setInterval(chat,2000); up(); chat();
function admSend(){var t=document.getElementById('adm_msg').value; if(t){fetch('backend_supervision.php',{method:'POST',body:new URLSearchParams({accion:'enviar_mensaje',mensaje:t})}).then(()=>{document.getElementById('adm_msg').value=''; chat();});}}
</script>
</body>
</html>
