<?php
require_once __DIR__.'/config/constantes.php';require_once __DIR__.'/config/database.php';require_once __DIR__.'/clases/Database.php';require_once __DIR__.'/clases/Usuario.php';require_once __DIR__.'/clases/Chat.php';
requerirSesion();$uid=(int)$_SESSION['usuario_id'];$conversaciones=Chat::obtenerConversacionesRecientes($uid);$noLeidos=Chat::contarNoLeidosPorContacto($uid);$contactoInicial=isset($_GET['con'])?(int)$_GET['con']:0;
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Chat</title><link rel="stylesheet" href="assets/css/estilo.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f0f2f5;height:100vh;height:100dvh;display:flex;flex-direction:column;overflow:hidden}
.nav{display:flex;align-items:center;justify-content:center;padding:0 18px;height:50px;background:#fff;border-bottom:1px solid #e5e7eb;flex-shrink:0}
.nav a{color:var(--acento);text-decoration:none;font-size:14px;font-weight:500}
.c{flex:1;display:flex;min-height:0}
.l{width:340px;background:#fff;border-right:1px solid #e5e7eb;display:flex;flex-direction:column}
.ls{padding:8px 14px;flex-shrink:0}.ls input{width:100%;padding:9px 14px;border:none;border-radius:20px;background:#f0f2f5;font-size:13px;outline:none}
.ll{flex:1;overflow-y:auto;min-height:0}
.li{display:flex;align-items:center;gap:12px;padding:12px 18px;cursor:pointer;border-bottom:1px solid #f0f2f5}.li:hover{background:#f5f6f7}.li.sel{background:#e7f3ff}
.la{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0}
.ln{flex:1;min-width:0}.ln strong{font-size:14px;color:#111827}.ln p{font-size:12px;color:#65676b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
.lb{min-width:20px;height:20px;border-radius:10px;background:var(--acento);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 6px}
.lt{font-size:10px;color:#8a8d91;flex-shrink:0}
.r{flex:1;display:flex;flex-direction:column;min-width:0}
.re{flex:1;display:flex;align-items:center;justify-content:center;color:#64748b;flex-direction:column;gap:12px;background:#f8fafc}.re span{font-size:48px;opacity:.5}
.rh{display:none;align-items:center;justify-content:space-between;padding:10px 18px;background:#fff;border-bottom:1px solid #e5e7eb;flex-shrink:0}
.rh .back{display:none;width:34px;height:34px;border-radius:50%;border:none;background:0 0;font-size:18px;cursor:pointer;color:var(--acento);margin-right:8px}
.ri{display:flex;align-items:center;gap:10px}
.ra{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0}
.rn{font-size:15px;font-weight:600;color:#111827}.rs{font-size:12px;color:#34C759}
.rm{flex:1;overflow-y:auto;padding:16px 18px;min-height:0;background:#efeae2;background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23d4c8b8' fill-opacity='.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");scroll-behavior:smooth;overscroll-behavior:contain}
.rf{flex-shrink:0;display:none;align-items:center;gap:8px;padding:8px 16px;background:#fff;border-top:1px solid #e5e7eb}
.rf input{flex:1;padding:9px 14px;border:none;border-radius:20px;background:#f0f2f5;font-size:14px;outline:none;font-family:inherit}
.rf button{width:36px;height:36px;border-radius:50%;border:none;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.rf .send{background:var(--acento);color:#fff}.rf .send:hover{filter:brightness(1.1)}
.msg{display:flex;margin-bottom:6px}.msg.in{justify-content:flex-start}.msg.out{justify-content:flex-end}
.msg .b{max-width:72%;padding:8px 14px;border-radius:16px;font-size:14px;line-height:1.5;word-break:break-word}
.msg.out .b{background:var(--acento);color:#fff;border-bottom-right-radius:4px}
.msg.in .b{background:#fff;color:#111827;border-bottom-left-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04)}
.msg .bt{font-size:10px;opacity:.7;margin-top:3px;text-align:right}
@media(max-width:768px){body{padding-bottom:0!important}.rf{display:none!important}.r.act .rf{display:flex!important;position:fixed;bottom:0;left:0;right:0;z-index:100;padding:10px 16px calc(10px + env(safe-area-inset-bottom, 0px));border-top:1px solid #e5e7eb;background:#fff;box-shadow:0 -2px 10px rgba(0,0,0,.06)}.r.act .rm{padding-bottom:100px!important}.l{width:100%}.l.hid{display:none}.r{display:none}.r.act{display:flex}.rh .back{display:flex}}
html.dark-mode .nav{background:#242526;border-color:#3e4042}
html.dark-mode .nav a{color:#e4e6eb}
html.dark-mode .l{background:#242526;border-color:#3e4042}
html.dark-mode .ls input{background:#3a3b3c;color:#e4e6eb}
html.dark-mode .li{border-color:#3e4042}
html.dark-mode .li:hover{background:#3a3b3c}
html.dark-mode .li.sel{background:rgba(45,136,255,.15)}
html.dark-mode .li strong{color:#e4e6eb}
html.dark-mode .li p{color:#b0b3b8}
html.dark-mode .lt{color:#8a8d91}
html.dark-mode .re{background:#18191a;color:#b0b3b8}
html.dark-mode .rh{background:#242526;border-color:#3e4042}
html.dark-mode .rh .rn{color:#e4e6eb}
html.dark-mode .rm{background:#18191a;background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23444951' fill-opacity='.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
html.dark-mode .rf{background:#242526;border-color:#3e4042}
html.dark-mode .rf input{background:#3a3b3c;color:#e4e6eb}
html.dark-mode .r.act .rf{box-shadow:0 -2px 12px rgba(0,0,0,.4)!important}
html.dark-mode .msg.in .b{background:#3a3b3c;color:#e4e6eb}
html.dark-mode .msg.out .b{background:var(--acento)}
html.dark-mode .msg .bt{opacity:.6}
html.dark-mode .call-btn-extra{color:#b0b3b8!important}
html.dark-mode .call-btn-extra:hover{background:#3a3b3c!important}
</style></head><body>
<div class="nav"><strong style="font-size:16px">Mensajes</strong></div>
<div class="c">
<div class="l" id="s">
    <div class="ls" style="display:flex;align-items:center;gap:10px"><a href="index.php" style="text-decoration:none;color:var(--acento);font-size:13px;font-weight:500;white-space:nowrap;flex-shrink:0">← Inicio</a><input type="text" id="sq" placeholder="🔍 Buscar..." oninput="fq()" style="flex:1"></div>
    <div class="ll" id="cl">
        <?php if($contactoInicial):$ci=Usuario::obtenerPorId($contactoInicial);if($ci):?>
        <div class="li sel" onclick='o(<?=$contactoInicial?>,<?=json_encode($ci['nombre'], JSON_UNESCAPED_UNICODE)?>,<?=json_encode($ci['avatar_color'])?>)' data-n="<?=htmlspecialchars($ci['nombre'])?>">
            <div class="la" style="background:<?=$ci['avatar_color']?>"><?=mb_strtoupper(mb_substr($ci['nombre'],0,1))?></div>
            <div class="ln"><strong><?=htmlspecialchars($ci['nombre'])?></strong><p>Nueva conversación</p></div></div>
        <?php endif;endif;
        foreach($conversaciones as $c):if($contactoInicial&&(int)$c['contacto_id']===$contactoInicial)continue;$p=$noLeidos[(int)$c['contacto_id']]??0;?>
        <div class="li" onclick='o(<?=$c['contacto_id']?>,<?=json_encode($c['contacto_nombre'], JSON_UNESCAPED_UNICODE)?>,<?=json_encode($c['contacto_avatar'])?>)' data-n="<?=htmlspecialchars($c['contacto_nombre'])?>">
            <div class="la" style="background:<?=$c['contacto_avatar']?>"><?=mb_strtoupper(mb_substr($c['contacto_nombre'],0,1))?></div>
            <div class="ln"><strong><?=htmlspecialchars($c['contacto_nombre'])?></strong><p><?=htmlspecialchars(mb_substr($c['mensaje'],0,40))?></p></div>
            <div style="text-align:right;flex-shrink:0"><div class="lt"><?=date('H:i',strtotime($c['creado_en']))?></div><?php if($p):?><div class="lb"><?=$p>9?'9+':$p?></div><?php endif;?></div></div>
        <?php endforeach;?>
    </div>
</div>
<div class="r" id="m">
    <div class="re" id="e"><span>💬</span><h3>Tus Mensajes</h3><p>Selecciona un chat</p></div>
    <div class="rh" id="h">
        <div class="ri"><button class="back" onclick="x()">←</button><div class="ra" id="a"></div><div><div class="rn" id="an"></div><div class="rs" id="as">En línea</div></div></div>
        <div style="display:flex;gap:6px">
            <button class="ra" style="width:36px;height:36px;font-size:16px;cursor:pointer;border:none;background:transparent" onclick="startCall('audio')" title="Llamada">📞</button>
        </div>
    </div>
    <div class="rm" id="msgs"></div>
    <div class="rf"><input type="text" id="mi" placeholder="Escribe un mensaje..." onkeydown="if(event.key==='Enter')s()"><button class="send" onclick="s()">➤</button></div>
</div>
</div>
<div id="callModal" style="display:none;position:fixed;bottom:20px;right:20px;z-index:9999;background:#1e293b;border-radius:16px;padding:20px 24px;color:#fff;box-shadow:0 12px 40px rgba(0,0,0,.5);min-width:280px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="font-size:24px">📞</div>
            <div><div style="font-size:14px;font-weight:600" id="callName"></div><div style="font-size:11px;color:#94a3b8" id="callStatus">Conectando...</div></div>
        </div>
        <div style="font-size:13px;color:#94a3b8" id="callTimer">00:00</div>
    </div>
    <button onclick="hangUp()" style="width:100%;padding:8px;border-radius:8px;background:#dc2626;border:none;color:#fff;font-size:14px;cursor:pointer;margin-top:8px">Colgar</button>
</div>
<script>
(function(){var m;try{m=JSON.parse(localStorage.getItem('cv_modo'))}catch(e){}if(m==='oscuro'){document.documentElement.classList.add('dark-mode')}else if(!m||m==='sistema'){if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.classList.add('dark-mode')}}window.matchMedia('(prefers-color-scheme:dark)').addEventListener('change',function(e){if(!m||m==='sistema'){document.documentElement.classList.toggle('dark-mode',e.matches)}})})();
var u=<?=$uid?>,cid=null,lid=0,iv=null,stInt=null;
var callId=null,ls=null,callInt=null,currentIncoming=null;
var servers={iceServers:[
    {urls:['stun:stun.l.google.com:19302','stun:stun1.l.google.com:19302']},
    {urls:['stun:stun.services.mozilla.com:3478']},
    {urls:'turn:openrelay.metered.ca:80',username:'openrelayproject',credential:'openrelayproject'},
    {urls:'turn:openrelay.metered.ca:443',username:'openrelayproject',credential:'openrelayproject'},
    {urls:'turn:openrelay.metered.ca:443?transport=tcp',username:'openrelayproject',credential:'openrelayproject'}
]};

function o(id,nm,cl){cid=id;lid=0;clearInterval(iv);document.getElementById('h').style.display='flex';document.getElementById('e').style.display='none';document.querySelector('#m .rf').style.display='flex';document.getElementById('a').innerHTML='<div style="width:100%;height:100%;border-radius:50%;background:'+cl+';display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff">'+nm.charAt(0).toUpperCase()+'</div>';document.getElementById('an').textContent=nm;document.querySelectorAll('.li').forEach(function(el){el.classList.toggle('sel',el.dataset.n===nm)});if(window.innerWidth<768){document.getElementById('s').classList.add('hid');document.getElementById('m').classList.add('act')}h();checkStatus()}
function x(){document.getElementById('s').classList.remove('hid');document.getElementById('m').classList.remove('act');document.querySelector('#m .rf').style.display='';clearInterval(iv);clearInterval(stInt)}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')}
function ft(d){var t=new Date(d.replace(' ','T'));return isNaN(t.getTime())?'':t.toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'})}
async function h(){var el=document.getElementById('msgs');el.innerHTML='<div style="text-align:center;padding:20px;color:#8a8d91">Cargando...</div>';try{var r=await fetch('api/chat_api.php?receptor_id='+cid);var d=await r.json();if(!d.ok)return;el.innerHTML='';if(!d.mensajes.length){el.innerHTML='<div style="text-align:center;padding:40px;color:#8a8d91">👋 Di hola</div>'}else{var df=document.createDocumentFragment();d.mensajes.forEach(function(m){var o=m.emisor_id==u,w=document.createElement('div');w.className='msg '+(o?'out':'in');w.innerHTML='<div class="b">'+esc(m.mensaje)+'</div>';df.appendChild(w)});el.appendChild(df);lid=d.mensajes[d.mensajes.length-1].id;scrollBottom()}iv=setInterval(poll,1000)}catch(e){}}
function scrollBottom(){var el=document.getElementById('msgs');if(!el)return;requestAnimationFrame(function(){requestAnimationFrame(function(){el.scrollTop=el.scrollHeight})})}
function p(m){var o=m.emisor_id==u,el=document.getElementById('msgs'),v=m.visto_en?'<span style="color:#34C759;font-size:11px;margin-left:3px">✓✓</span>':o?'<span style="font-size:11px;margin-left:3px">✓</span>':'',w=document.createElement('div');w.className='msg '+(o?'out':'in');w.innerHTML='<div class="b">'+esc(m.mensaje)+'<div class="bt">'+ft(m.creado_en)+v+'</div></div>';el.appendChild(w);scrollBottom()}
async function poll(){if(!cid||document.hidden)return;try{var r=await fetch('api/chat_api.php?receptor_id='+cid+'&desde_id='+lid);var d=await r.json();if(d.ok&&d.mensajes.length){d.mensajes.forEach(function(m){p(m)});lid=d.mensajes[d.mensajes.length-1].id}}catch(e){}}
async function s(){var i=document.getElementById('mi'),t=i.value.trim();if(!t||!cid)return;i.value='';p({emisor_id:u,mensaje:t,creado_en:new Date().toISOString().slice(0,19).replace('T',' ')});try{var r=await fetch('api/chat_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({receptor_id:cid,mensaje:t})});var d=await r.json();if(d.ok&&d.id)lid=Math.max(lid,d.id)}catch(e){}}
function fq(){var q=document.getElementById('sq').value.toLowerCase();document.querySelectorAll('.li').forEach(function(el){el.style.display=el.dataset.n.toLowerCase().includes(q)?'':'none'})}
async function checkStatus(){clearInterval(stInt);async function update(){try{var r=await fetch('api/chat_api.php?estado='+cid);var d=await r.json();var el=document.getElementById('as');if(d.online){el.textContent='🟢 En línea';el.style.color='#34C759'}else{el.textContent='Últ. vez '+d.ultima;el.style.color='#8a8d91'}}catch(e){}}update();stInt=setInterval(update,3000)}
function touchStatus(){fetch('api/chat_api.php?touch=1').catch(function(){})}
setTimeout(touchStatus,500);setInterval(touchStatus,45000);

// Llamadas con WebRTC + ICE exchange
var dc=null,iceFrom=0,iceInt=null,connTimeout=null,remoteAudio=null;
function logStatus(msg){var el=document.getElementById('callStatus');if(el)el.textContent=msg}
function getRemoteSDP(callId,role){return fetch('api/call_api.php?action=get_signal&call_id='+callId+'&role='+role).then(function(r){return r.json()}).then(function(d){return d.ok&&d.sdp?JSON.parse(d.sdp):null}).catch(function(){return null})}
function sendIce(callId,candidate){fetch('api/call_api.php?action=add_ice',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({call_id:callId,candidate:JSON.stringify(candidate)})}).catch(function(){})}
function startIcePoll(callId){
    iceFrom=0;clearInterval(iceInt);
    // Fetch accumulated candidates immediately
    fetchPendingIce();
    iceInt=setInterval(fetchPendingIce,600);
    function fetchPendingIce(){
        if(!callId||!pc||pc.iceConnectionState==='connected'||pc.iceConnectionState==='completed'){clearInterval(iceInt);return}
        fetch('api/call_api.php?action=get_ice&call_id='+callId+'&from='+iceFrom).then(function(r){return r.json()}).then(function(d){
            if(d.ok&&d.candidates.length){d.candidates.forEach(function(c){try{pc.addIceCandidate(new RTCIceCandidate(c)).catch(function(){})}catch(e){}});iceFrom=d.total}
        }).catch(function(){});
    }
}
function ensureRemoteAudio(){
    if(remoteAudio)return remoteAudio;
    remoteAudio=document.createElement('audio');
    remoteAudio.id='ra';remoteAudio.autoplay=true;remoteAudio.playsInline=true;
    remoteAudio.style.display='none';
    document.body.appendChild(remoteAudio);
    remoteAudio.play().catch(function(){});
    document.addEventListener('click',function ua(){remoteAudio.play().catch(function(){});document.removeEventListener('click',ua)},{once:true});
    return remoteAudio;
}
function setupPeer(localStream){
    pc=new RTCPeerConnection(servers);
    localStream.getTracks().forEach(function(t){pc.addTrack(t,localStream)});
    pc.onicecandidate=function(e){if(e.candidate&&callId)sendIce(callId,e.candidate)};
    pc.onicegatheringstatechange=function(){if(pc.iceGatheringState==='complete')logStatus('Señal lista...')};
    pc.oniceconnectionstatechange=function(){
        if(pc.iceConnectionState==='connected'){clearTimeout(connTimeout);logStatus('🎤 Conectado')}
        if(pc.iceConnectionState==='failed'){logStatus('Fallo de conexión - intenta de nuevo')}
        if(pc.iceConnectionState==='disconnected'){logStatus('Conexión perdida');setTimeout(function(){if(pc&&pc.iceConnectionState==='disconnected'){pc.restartIce();logStatus('Reconectando...')}},2000)}
    };
    pc.ontrack=function(e){var a=ensureRemoteAudio();if(e.streams[0]){a.srcObject=e.streams[0];logStatus('🎤 Conectado');clearTimeout(connTimeout)}};
    clearTimeout(connTimeout);
    connTimeout=setTimeout(function(){logStatus('Sin respuesta del otro dispositivo')},35000);
    return pc;
}
async function startCall(tipo){
    if(!cid)return;
    try{
        ls=await navigator.mediaDevices.getUserMedia({audio:true,video:false});
        ensureRemoteAudio();
        var r=await fetch('api/call_api.php?action=iniciar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({receptor_id:cid,tipo:'audio'})});
        var d=await r.json();
        if(!d.ok)return;
        callId=d.id;
        document.getElementById('callModal').style.display='block';
        document.getElementById('callName').textContent=document.getElementById('an')?document.getElementById('an').textContent:'Contacto';
        logStatus('Llamando...');
        var seg=0;clearInterval(callInt);callInt=setInterval(function(){seg++;var m=Math.floor(seg/60),s=seg%60;document.getElementById('callTimer').textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0')},1000);
        pc=setupPeer(ls);
        var offer=await pc.createOffer();await pc.setLocalDescription(offer);
        await fetch('api/call_api.php?action=signal',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({call_id:callId,sdp:JSON.stringify(pc.localDescription),role:'caller'})});
        // Esperar respuesta y luego iniciar ICE polling
        clearInterval(dc);
        dc=setInterval(async function(){
            if(!callId||!pc||pc.currentRemoteDescription){clearInterval(dc);return}
            var sdp=await getRemoteSDP(callId,'caller');
            if(sdp&&pc&&!pc.currentRemoteDescription){
                await pc.setRemoteDescription(sdp);
                clearInterval(dc);
                logStatus('Conectando...');
                startIcePoll(callId);
            }
        },600);
    }catch(e){logStatus('Error: '+e.message)}
}
async function answerCall(){
    if(!currentIncoming)return;
    callId=currentIncoming.id;
    fetch('api/call_api.php?action=responder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({call_id:callId,respuesta:'activa'})});
    try{
        ls=await navigator.mediaDevices.getUserMedia({audio:true,video:false});
        ensureRemoteAudio();
        document.getElementById('callModal').style.display='block';
        document.getElementById('callName').textContent=currentIncoming.llamante_nombre||document.getElementById('an').textContent||'Contacto';
        logStatus('Conectando audio...');
        var seg=0;clearInterval(callInt);callInt=setInterval(function(){seg++;var m=Math.floor(seg/60),s=seg%60;document.getElementById('callTimer').textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0')},1000);
        pc=setupPeer(ls);
        clearInterval(dc);
        dc=setInterval(async function(){
            if(!callId||!pc||pc.currentRemoteDescription){clearInterval(dc);return}
            var sdp=await getRemoteSDP(callId,'callee');
            if(sdp&&pc&&!pc.currentRemoteDescription){
                await pc.setRemoteDescription(sdp);
                var answer=await pc.createAnswer();await pc.setLocalDescription(answer);
                await fetch('api/call_api.php?action=signal',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({call_id:callId,sdp:JSON.stringify(pc.localDescription),role:'callee'})});
                clearInterval(dc);
                startIcePoll(callId);
            }
        },600);
    }catch(e){logStatus('Error: '+e.message)}
}
function hangUp(){
    clearInterval(callInt);clearInterval(dc);clearInterval(iceInt);document.getElementById('callModal').style.display='none';
    if(ls){ls.getTracks().forEach(function(t){t.stop()});ls=null}
    if(pc){pc.close();pc=null}
    var a=document.getElementById('ra');if(a)a.remove();
    if(callId){var cid=callId;callId=null;fetch('api/call_api.php?action=colgar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({call_id:cid})})}
}
setInterval(function(){
    if(!callId||!u)return;
    fetch('api/call_api.php?action=check_hangup&call_id='+callId).then(function(r){return r.json()}).then(function(d){
        if(d.estado==='finalizada'||d.estado==='rechazada'){clearInterval(callInt);clearInterval(dc);clearInterval(iceInt);document.getElementById('callModal').style.display='none';if(ls){ls.getTracks().forEach(function(t){t.stop()});ls=null}if(pc){pc.close();pc=null}var a=document.getElementById('ra');if(a)a.remove();callId=null}
    }).catch(function(){});
},2000);
document.addEventListener('visibilitychange',function(){if(!document.hidden&&cid)h()});
// Llamada entrante
</script>
<script src="assets/js/call_notify.js"></script>
<?php if($contactoInicial):$ci=Usuario::obtenerPorId($contactoInicial);if($ci):?>
<script>setTimeout(function(){o(<?=$contactoInicial?>,<?=json_encode($ci['nombre'], JSON_UNESCAPED_UNICODE)?>,<?=json_encode($ci['avatar_color'])?>)},300)</script>
<?php endif;endif;?>
<?php if(isset($_GET['answer'])):$autoAnswerId=(int)$_GET['answer'];?>
<script>setTimeout(function(){incomingCallId=<?=$autoAnswerId?>;currentIncoming={id:<?=$autoAnswerId?>};if(typeof answerCall==='function')answerCall()},800)</script>
<?php endif;?>
</body></html>
