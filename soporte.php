<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();
$uid = (int)$_SESSION['usuario_id'];
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Soporte · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
.ai-body{background:#f0f2f5;height:100vh;overflow:hidden;display:flex;flex-direction:column}
#hubTopbarShell{flex-shrink:0}
.ai-wrap{flex:1;display:flex;overflow:hidden}
.ai-sidebar{width:320px;background:#fff;border-right:1px solid #e5e7eb;display:flex;flex-direction:column;overflow:hidden;padding:20px}
.ai-sidebar h3{font-size:18px;font-weight:700;color:#111827;margin:0 0 12px}
.ai-sidebar p{font-size:13px;color:#64748b;line-height:1.6;margin:0 0 16px}
.ai-actions{display:flex;flex-direction:column;gap:8px}
.ai-action-btn{display:flex;align-items:center;gap:8px;padding:12px 14px;border-radius:10px;border:none;background:#f0f2f5;color:#111827;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:all .15s;text-align:left}
.ai-action-btn:hover{background:#e0e7ff;color:var(--acento)}
.ai-main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#fff}
.ai-header{padding:14px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:12px;flex-shrink:0}
.ai-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#3B5BDB,#8B5CF6);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0}
.ai-header-info h3{font-size:16px;font-weight:700;color:#111827;margin:0}
.ai-header-info span{font-size:12px;color:#34C759}
.ai-messages{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px;background:#f8fafc;min-height:0}
.ai-msg{display:flex;gap:8px;max-width:80%}
.ai-msg.user{margin-left:auto;justify-content:flex-end}
.ai-msg .ai-bubble{padding:10px 14px;border-radius:14px;font-size:14px;line-height:1.5}
.ai-msg.bot .ai-bubble{background:#fff;color:#111827;border:1px solid #e5e7eb}
.ai-msg.user .ai-bubble{background:var(--acento);color:#fff}
.ai-input-bar{flex-shrink:0;display:flex;gap:8px;padding:12px 16px;border-top:1px solid #e5e7eb;background:#fff}
.ai-input-bar input{flex:1;padding:10px 16px;border:1px solid #e5e7eb;border-radius:20px;font-size:14px;outline:none;font-family:inherit;background:#f8fafc}
.ai-input-bar input:focus{border-color:var(--acento)}
.ai-send-btn{width:40px;height:40px;border-radius:50%;border:none;background:var(--acento);color:#fff;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ai-call-modal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.9);align-items:center;justify-content:center;flex-direction:column;color:#fff}
.ai-call-modal.active{display:flex}
@media(max-width:768px){.ai-sidebar{display:none}.ai-msg{max-width:90%}.ai-wrap{padding:0}}
</style></head><body class="ai-body"><?php require __DIR__.'/vistas/topbar.php'; ?>
<div class="ai-wrap">
    <div class="ai-sidebar">
        <h3>🤖 CoreAI</h3>
        <p>Asistente virtual de CampusVirtual UNITRU. Resuelvo dudas, diagnostico problemas y conecto llamadas en tiempo real.</p>
        <div class="ai-actions">
            <button class="ai-action-btn" onclick="enviarMsg('Quiero hablar con un agente')">🎙️ Iniciar llamada de voz</button>
            <button class="ai-action-btn" onclick="enviarMsg('No funciona mi cámara')">📹 Problemas con cámara</button>
            <button class="ai-action-btn" onclick="enviarMsg('¿Cómo uso la plataforma?')">📖 Guía de uso</button>
        </div>
    </div>
    <div class="ai-main">
        <div class="ai-header">
            <div class="ai-avatar">AI</div>
            <div class="ai-header-info"><h3>CoreAI · Asistente</h3><span>🟢 En línea</span></div>
        </div>
        <div class="ai-messages" id="aiMessages">
            <div class="ai-msg bot"><div class="ai-bubble">👋 ¡Hola! Soy <b>CoreAI</b>, el asistente virtual de CampusVirtual UNITRU.<br><br>Puedo ayudarte con:<br>• Soporte técnico y dudas<br>• Diagnóstico de problemas<br>• Iniciar llamadas de voz/video<br><br>¿En qué puedo ayudarte hoy?</div></div>
        </div>
        <div class="ai-input-bar">
            <input type="text" id="aiInput" placeholder="Escribe tu mensaje..." onkeydown="if(event.key==='Enter')enviarMsg()">
            <button class="ai-send-btn" onclick="enviarMsg()">➤</button>
        </div>
    </div>
</div>
<div class="ai-call-modal" id="callModal">
    <div style="font-size:64px;margin-bottom:16px">📞</div>
    <div style="font-size:24px;font-weight:700;margin-bottom:8px">Llamada en curso</div>
    <div style="font-size:16px;color:#aaa;margin-bottom:24px" id="callStatus">Conectando...</div>
    <div style="font-size:32px;font-weight:300;margin-bottom:32px" id="callTimer">00:00</div>
    <button onclick="colgar()" style="width:64px;height:64px;border-radius:50%;background:#dc2626;border:none;font-size:28px;cursor:pointer;color:#fff">📞</button>
</div>
<script>
function addMsg(texto, tipo) {
    var el = document.getElementById('aiMessages');
    var div = document.createElement('div');
    div.className = 'ai-msg '+tipo;
    var bubble = document.createElement('div');
    bubble.className = 'ai-bubble';
    bubble.textContent = texto;
    div.appendChild(bubble);
    el.appendChild(div);
    el.scrollTop = el.scrollHeight;
}
function botReply(input) {
    var msg = input.toLowerCase();
    if (msg.includes('llamar') || msg.includes('hablar con') || msg.includes('agente') || msg.includes('voz')) {
        addMsg('Con gusto te conecto. Antes de iniciar, asegúrate de tener tu <b>micrófono conectado</b> y otorgar los permisos cuando tu navegador lo solicite.<br><br>¿Estás listo para iniciar la llamada de voz ahora mismo?', 'bot');
        document.getElementById('callModal').classList.add('active');
        var seg=0; window.callTimer=setInterval(function(){seg++;var m=Math.floor(seg/60),s=seg%60;document.getElementById('callTimer').textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0')},1000);
        setTimeout(function(){document.getElementById('callStatus').textContent='En llamada'},2000);
    } else if (msg.includes('cámara') || msg.includes('camara') || msg.includes('video')) {
        addMsg('Vamos a solucionarlo:<br><br>1. Revisa el icono del <b>candado</b> en la barra de direcciones<br>2. Asegúrate que el permiso de <b>Cámara</b> esté en <b>Permitir</b><br>3. Cierra otras apps (Zoom, Teams, Meet) que puedan estar usando tu cámara<br><br>¿Te funcionó?', 'bot');
    } else if (msg.includes('plataforma') || msg.includes('usar') || msg.includes('guia') || msg.includes('cómo')) {
        addMsg('CampusVirtual UNITRU es tu hub social universitario:<br><br>📰 <b>Feed</b> - Publicaciones de tus amigos<br>👥 <b>Amigos</b> - Conecta con compañeros<br>💬 <b>Chat</b> - Mensajería en tiempo real<br>🛒 <b>Marketplace</b> - Compra y venta<br>💼 <b>Empleos</b> - Bolsa de trabajo<br>🎓 <b>Grupos</b> - Estudia en equipo<br><br>¿Necesitas ayuda con algo específico?', 'bot');
    } else if (msg.includes('gracias') || msg.includes('ok') || msg.includes('bien')) {
        addMsg('¡De nada! 😊 Si necesitas algo más, aquí estoy.', 'bot');
    } else {
        addMsg('Entiendo tu consulta. ¿Te gustaría que te conecte con un <b>agente humano</b> vía llamada de voz para resolverlo más rápido?', 'bot');
    }
}
function enviarMsg(texto) {
    var input = document.getElementById('aiInput');
    var msg = texto || input.value.trim();
    if (!msg) return;
    addMsg(msg, 'user');
    if (!texto) input.value = '';
    setTimeout(function(){ botReply(msg) }, 500 + Math.random()*1000);
}
function colgar() {
    clearInterval(window.callTimer);
    document.getElementById('callModal').classList.remove('active');
    document.getElementById('callStatus').textContent='Conectando...';
    document.getElementById('callTimer').textContent='00:00';
    addMsg('Llamada finalizada. ¿Necesitas algo más?', 'bot');
}
</script></body></html>
