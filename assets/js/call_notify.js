/**
 * call_notify.js — Notificación global de llamada entrante
 * Incluir en topbar.php y chat.php (o cualquier página que necesite recibir llamadas)
 */
(function(){
    var el=document.createElement('div');
    el.id='callNotify';
    el.innerHTML='<audio id="ringtone" src="assets/audio/tonodellamada.mp3" loop preload="auto"></audio>'+
        '<div id="cnRing" style="position:absolute;inset:-4px;border-radius:20px;border:2px solid #34C759;animation:cnRing 1.5s ease-out infinite;pointer-events:none"></div>'+
        '<div id="cnBody" style="display:flex;align-items:center;gap:12px;background:#fff;border-radius:16px;padding:16px 18px;box-shadow:0 12px 40px rgba(0,0,0,.2)">'+
        '<div style="font-size:28px;animation:cnBounce .6s ease-in-out infinite alternate">📞</div>'+
        '<div style="flex:1;min-width:0"><div id="cnName" style="font-size:14px;font-weight:700;color:#1a1a2e"></div><div style="font-size:12px;color:#9ca3af">te está llamando...</div></div>'+
        '<div style="display:flex;gap:8px">'+
        '<button id="cnDecline" style="width:40px;height:40px;border-radius:50%;border:none;cursor:pointer;font-size:16px;font-weight:700;background:#fee2e2;color:#dc2626">✕</button>'+
        '<button id="cnAccept" style="width:40px;height:40px;border-radius:50%;border:none;cursor:pointer;font-size:16px;font-weight:700;background:#dcfce7;color:#16a34a">✓</button>'+
        '</div></div>';
    el.style.cssText='display:none;position:fixed;bottom:24px;right:24px;z-index:999999;max-width:360px;animation:cnSlide .35s cubic-bezier(.25,.8,.25,1)';
    document.body.appendChild(el);

    var style=document.createElement('style');
    style.textContent='@keyframes cnRing{0%{inset:-4px;opacity:1}100%{inset:-14px;opacity:0}}@keyframes cnSlide{from{opacity:0;transform:translateX(80px) scale(.9)}to{opacity:1;transform:translateX(0) scale(1)}}@keyframes cnBounce{from{transform:scale(1)}to{transform:scale(1.15)}}html.dark-mode #cnBody{background:#1f2937;box-shadow:0 12px 40px rgba(0,0,0,.5)}html.dark-mode #cnName{color:#f3f4f6}html.dark-mode #cnDecline{background:rgba(220,38,38,.15)}html.dark-mode #cnAccept{background:rgba(22,163,74,.15)}';
    document.head.appendChild(style);

    var callId=null,callerName='',callerUid=null,hangupInt=null;

    function show(c){
        callId=c.id;callerName=c.llamante_nombre||'Alguien';callerUid=c.llamante_id;
        el.style.display='block';el.querySelector('#cnName').textContent=callerName;
        var a=document.getElementById('ringtone');if(a){a.currentTime=0;a.play().catch(function(){})}
        hangupInt=setInterval(function(){
            if(!callId)return;
            fetch('api/call_api.php?action=check_hangup&call_id='+callId).then(function(r){return r.json()}).then(function(d){
                if(d.estado==='finalizada'||d.estado==='rechazada')hide();
            }).catch(function(){});
        },2000);
    }

    function hide(){
        el.style.display='none';callId=null;clearInterval(hangupInt);
        var a=document.getElementById('ringtone');if(a){a.pause();a.currentTime=0}
    }

    el.querySelector('#cnAccept').onclick=function(){
        var c=callId,n=callerName,u=callerUid;hide();
        fetch('api/call_api.php?action=responder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({call_id:c,respuesta:'activa'})});
        if(window.location.pathname.indexOf('chat.php')>-1){
            if(typeof answerCall==='function'){window.currentIncoming={id:c,llamante_nombre:n};answerCall();}
        }else{
            window.location.href='chat.php?con='+u+'&answer='+c;
        }
    };

    el.querySelector('#cnDecline').onclick=function(){
        var c=callId;if(c)fetch('api/call_api.php?action=responder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({call_id:c,respuesta:'rechazada'})});hide();
    };

    setInterval(function(){
        if(callId||(typeof currentIncoming!=='undefined'&&currentIncoming))return;
        fetch('api/call_api.php?action=check').then(function(r){return r.json()}).then(function(d){
            if(d.ok&&d.call&&!callId)show(d.call);
        }).catch(function(){});
    },2000);
})();
