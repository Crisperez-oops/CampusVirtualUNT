/**
 * assets/js/dashboard.js - Dashboard optimizado sin sidebar
 */
(function(){
if(typeof HOY_MES==='undefined'){var d=new Date();window.HOY_MES=d.getMonth()+1;window.HOY_ANIO=d.getFullYear();window.HOY_DIA=d.getDate();}

let calMes=HOY_MES-1,calAnio=HOY_ANIO,calDiaSeleccionado=null,filtroTareas='todas';
const MESES=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
let tareas=[],eventos={},notas=[],cursos=[];

const API='api/dashboard_api.php';
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function formatFecha(iso){if(!iso)return'';const[a,m,d]=iso.split('-');return d+'/'+m+'/'+a;}

async function apiFetch(accion,data=null){
    try{const o={headers:{'Content-Type':'application/json'},method:data?'POST':'GET'};if(data)o.body=JSON.stringify(data);const r=await fetch(API+'?accion='+accion,o);return await r.json();}catch(e){return{ok:false};}
}

async function cargarDatos(){
    try{const[t,e,n,c]=await Promise.all([apiFetch('tareas'),apiFetch('eventos'),apiFetch('notas'),apiFetch('cursos')]);
    tareas=(t.ok?t.data:[]).map(i=>({id:i.id,texto:i.titulo,hecho:i.completada==1,prioridad:i.prioridad,fecha:i.fecha_entrega,cursoId:null}));
    eventos={};(e.ok?e.data:[]).forEach(ev=>{const k=ev.fecha_inicio?ev.fecha_inicio.substring(0,10):'';if(!eventos[k])eventos[k]=[];eventos[k].push({id:ev.id,titulo:ev.titulo,color:ev.color||'dot-azul'});});
    notas=(n.ok?n.data:[]).map(i=>({id:i.id,titulo:i.titulo,cuerpo:i.contenido||'',color:'nota-amber',fecha:i.creado_en?i.creado_en.substring(0,10):''}));
    cursos=(c.ok?c.data:[]).map(i=>({id:i.id,nombre:i.nombre,docente:i.docente||'',creditos:'',color:i.color||'#6C8CFF',progreso:0}));}catch(e){}
    actualizarSelectCursos();renderTareas();renderNotas();renderCursos();renderInicio();renderCalendario();
}

(function(){const el=document.getElementById('topbar-fecha');if(el)el.textContent=new Date().toLocaleDateString('es-PE',{weekday:'long',day:'numeric',month:'long',year:'numeric'});})();

function mostrarSeccion(id,btn){
    document.querySelectorAll('.dash-seccion').forEach(s=>s.classList.remove('activa'));
    const sec=document.getElementById('sec-'+id);if(sec)sec.classList.add('activa');
    document.querySelectorAll('.dash-tab').forEach(t=>t.classList.remove('active'));
    if(btn)btn.classList.add('active');
    const tit=document.getElementById('topbar-titulo');if(tit)tit.textContent={inicio:'Inicio',tareas:'Tareas',calendario:'Calendario',cursos:'Cursos',notas:'Notas'}[id]||id;
    if(id==='calendario')renderCalendario();if(id==='tareas')renderTareas();if(id==='notas')renderNotas();if(id==='cursos')renderCursos();if(id==='inicio')renderInicio();
}

function abrirModalCurso(id){const c=cursos.find(c=>c.id===id);const elTit=document.getElementById('modal-curso-titulo');if(elTit)elTit.textContent=c?'Editar curso':'Nuevo curso';const elId=document.getElementById('modal-curso-id');if(elId)elId.value=c?c.id:'';const elNom=document.getElementById('modal-curso-nombre');if(elNom)elNom.value=c?c.nombre:'';const elDoc=document.getElementById('modal-curso-docente');if(elDoc)elDoc.value=c?c.docente:'';document.getElementById('modal-curso').classList.add('abierto');}
function cerrarModalCurso(){document.getElementById('modal-curso').classList.remove('abierto');}
function guardarCursoModal(){const n=document.getElementById('modal-curso-nombre').value.trim();if(!n)return;const d={nombre:n,docente:document.getElementById('modal-curso-docente').value.trim()};const idVal=document.getElementById('modal-curso-id').value;if(idVal){d.id=parseInt(idVal);apiFetch('curso_editar',d).then(()=>{cerrarModalCurso();cargarDatos();});}else{apiFetch('curso_crear',d).then(()=>{cerrarModalCurso();cargarDatos();});}}
function eliminarCurso(id){if(confirm('¿Eliminar curso?'))apiFetch('curso_eliminar',{id}).then(()=>cargarDatos());}
function actualizarSelectCursos(){const s=document.getElementById('input-curso');if(!s)return;const a=s.value;s.innerHTML='<option value="">Sin curso</option>'+cursos.map(c=>'<option value="'+c.id+'">'+esc(c.nombre)+'</option>').join('');if([...s.options].some(o=>o.value===a))s.value=a;}
function renderCursos(){const g=document.getElementById('cursos-grid');if(!g)return;let h=cursos.map(c=>'<div class="curso-card"><div class="curso-card-franja" style="background:'+c.color+'"></div><div class="curso-card-cab"><div><div class="curso-card-nombre">'+esc(c.nombre)+'</div>'+(c.docente?'<div class="curso-card-docente">'+esc(c.docente)+'</div>':'')+'</div><div class="curso-card-menu"><button onclick="abrirModalCurso('+c.id+')">✎</button><button onclick="eliminarCurso('+c.id+')" class="borrar">✕</button></div></div></div>').join('');h+='<div class="curso-nuevo-card" onclick="abrirModalCurso()"><span style="font-size:22px">+</span> Agregar curso</div>';g.innerHTML=h;}

function agregarTarea(){const texto=document.getElementById('input-tarea').value.trim();if(!texto)return;apiFetch('tarea_crear',{titulo:texto,prioridad:document.getElementById('input-prioridad').value,fecha_entrega:document.getElementById('input-fecha-tarea').value}).then(()=>{document.getElementById('input-tarea').value='';document.getElementById('input-fecha-tarea').value='';cargarDatos();});}
function toggleTarea(id){const t=tareas.find(t=>t.id===id);if(!t)return;t.hecho=!t.hecho;apiFetch('tarea_editar',{id,titulo:t.texto,completada:t.hecho?1:0,prioridad:t.prioridad,fecha_entrega:t.fecha}).then(()=>cargarDatos());}
function eliminarTarea(id){if(confirm('¿Eliminar tarea?'))apiFetch('tarea_eliminar',{id}).then(()=>cargarDatos());}
function filtrarTareas(f,btn){filtroTareas=f;document.querySelectorAll('.filtro-btn').forEach(b=>b.classList.remove('activo'));if(btn)btn.classList.add('activo');renderTareas();}
function renderTareas(){let l=tareas;if(filtroTareas==='pendientes')l=tareas.filter(t=>!t.hecho);if(filtroTareas==='completadas')l=tareas.filter(t=>t.hecho);if(filtroTareas==='alta')l=tareas.filter(t=>t.prioridad==='alta'&&!t.hecho);const el=document.getElementById('tareas-lista');if(!el)return;if(!l.length){el.innerHTML='<p style="color:var(--texto-tenue);padding:12px;">No hay tareas.</p>';return;}el.innerHTML=l.map(t=>'<div class="tarea-item"><div class="tarea-check '+(t.hecho?'hecho':'')+'" onclick="toggleTarea('+t.id+')"></div><span class="tarea-texto '+(t.hecho?'hecho':'')+'">'+esc(t.texto)+'</span><span class="tarea-prioridad prio-'+t.prioridad+'">'+t.prioridad+'</span>'+(t.fecha?'<span class="tarea-fecha">'+formatFecha(t.fecha)+'</span>':'')+'<button class="tarea-eliminar" onclick="eliminarTarea('+t.id+')">✕</button></div>').join('');}

function renderCalendario(){const pd=new Date(calAnio,calMes,1),dm=new Date(calAnio,calMes+1,0).getDate(),is=pd.getDay();const tit=document.getElementById('cal-mes-titulo');if(tit)tit.textContent=MESES[calMes]+' '+calAnio;let h=DIAS.map(d=>'<div class="cal-nombre-dia">'+d+'</div>').join('');const da=new Date(calAnio,calMes,0).getDate();for(let i=is-1;i>=0;i--)h+='<div class="cal-dia otro-mes"><div class="cal-dia-num">'+(da-i)+'</div></div>';for(let d=1;d<=dm;d++){const eh=d===HOY_DIA&&calMes===HOY_MES-1&&calAnio===HOY_ANIO,k=calAnio+'-'+String(calMes+1).padStart(2,'0')+'-'+String(d).padStart(2,'0'),evs=eventos[k]||[],sel=calDiaSeleccionado===k;let dt='';if(evs.length){dt='<div class="cal-evento-dot '+evs[0].color+'" title="'+esc(evs[0].titulo)+'"></div>';}h+='<div class="cal-dia '+(eh?'hoy':'')+'" onclick="seleccionarDia('+d+')" style="'+(sel?'outline:2px solid var(--acento);':'')+'"><div class="cal-dia-num">'+d+'</div>'+dt+'</div>';}const tot=is+dm,cs=tot%7===0?tot:tot+(7-tot%7);for(let d=1;d<=cs-tot;d++)h+='<div class="cal-dia otro-mes"><div class="cal-dia-num">'+d+'</div></div>';const grid=document.getElementById('cal-grid');if(grid)grid.innerHTML=h;}
function cambiarMes(dir){if(dir===0){calMes=HOY_MES-1;calAnio=HOY_ANIO;}else{calMes+=dir;if(calMes<0){calMes=11;calAnio--;}if(calMes>11){calMes=0;calAnio++;}}renderCalendario();}
function seleccionarDia(d){calDiaSeleccionado=calAnio+'-'+String(calMes+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');const el=document.getElementById('cal-dia-seleccionado');if(el)el.textContent=d+' de '+MESES[calMes]+' '+calAnio;renderEventosDia();renderCalendario();}
function agregarEvento(){if(!calDiaSeleccionado)return alert('Selecciona un día primero.');const tit=document.getElementById('evento-titulo').value.trim();if(!tit)return;apiFetch('evento_crear',{titulo:tit,fecha_inicio:calDiaSeleccionado+'T00:00:00',color:document.getElementById('evento-color').value}).then(()=>{document.getElementById('evento-titulo').value='';cargarDatos();});}
function eliminarEvento(id){apiFetch('evento_eliminar',{id}).then(()=>cargarDatos());}
function renderEventosDia(){const l=document.getElementById('evento-lista-dia');if(!l)return;const e=eventos[calDiaSeleccionado]||[];if(!e.length){l.innerHTML='<p style="color:var(--texto-tenue);font-size:12px;margin-top:10px;">Sin eventos.</p>';return;}l.innerHTML=e.map(ev=>'<div class="evento-item"><div class="evento-item-color" style="background:'+(ev.color==='dot-verde'?'var(--ok)':ev.color==='dot-rojo'?'var(--error)':ev.color==='dot-amber'?'#F59E0B':'var(--acento)')+'"></div><div class="evento-item-titulo">'+esc(ev.titulo)+'</div><button class="evento-eliminar" onclick="eliminarEvento('+ev.id+')">✕</button></div>').join('');}

function nuevaNota(){apiFetch('nota_crear',{titulo:'Nueva nota',contenido:''}).then(()=>cargarDatos());}
function eliminarNota(id){apiFetch('nota_eliminar',{id}).then(()=>cargarDatos());}
function actualizarNota(id,campo,valor){const n=notas.find(n=>n.id===id);if(!n)return;n[campo]=valor;apiFetch('nota_editar',{id,titulo:n.titulo,contenido:n.cuerpo});}
function renderNotas(){const g=document.getElementById('notas-grid');if(!g)return;let h=notas.map(n=>'<div class="nota-card '+n.color+'"><input class="nota-titulo-input" value="'+esc(n.titulo)+'" placeholder="Título" onchange="actualizarNota('+n.id+',\'titulo\',this.value)"><textarea class="nota-cuerpo-input" rows="3" placeholder="Escribe aquí…" onchange="actualizarNota('+n.id+',\'cuerpo\',this.value)">'+esc(n.cuerpo)+'</textarea><div class="nota-actions"><button class="nota-eliminar" onclick="eliminarNota('+n.id+')">🗑</button></div></div>').join('');h+='<div class="nota-nueva" onclick="nuevaNota()"><span style="font-size:20px">+</span> Nueva nota</div>';g.innerHTML=h;}

function renderInicio(){const p=tareas.filter(t=>!t.hecho).length,c=tareas.filter(t=>t.hecho).length;const rp=document.getElementById('res-pendientes');if(rp)rp.textContent=p;const rc=document.getElementById('res-completadas');if(rc)rc.textContent=c;const cu=document.getElementById('res-cursos');if(cu)cu.textContent=cursos.length;const pr=tareas.filter(t=>!t.hecho).slice(0,5);const el=document.getElementById('inicio-tareas-lista');if(!el)return;if(!pr.length){el.innerHTML='<p style="color:var(--texto-tenue);">No hay tareas pendientes 🎉</p>';return;}el.innerHTML=pr.map(t=>'<div class="tarea-item"><div class="tarea-check" onclick="toggleTarea('+t.id+')"></div><span class="tarea-texto">'+esc(t.texto)+'</span><span class="tarea-prioridad prio-'+t.prioridad+'">'+t.prioridad+'</span>'+(t.fecha?'<span class="tarea-fecha">'+formatFecha(t.fecha)+'</span>':'')+'</div>').join('');}

document.addEventListener('DOMContentLoaded',()=>{cargarDatos();const inp=document.getElementById('input-tarea');if(inp)inp.addEventListener('keydown',e=>{if(e.key==='Enter')agregarTarea();});const modal=document.getElementById('modal-curso');if(modal)modal.addEventListener('click',e=>{if(e.target.id==='modal-curso')cerrarModalCurso();});});

// Exponer al scope global para onclick
window.mostrarSeccion=mostrarSeccion;
window.agregarTarea=agregarTarea;
window.toggleTarea=toggleTarea;
window.eliminarTarea=eliminarTarea;
window.filtrarTareas=filtrarTareas;
window.cambiarMes=cambiarMes;
window.seleccionarDia=seleccionarDia;
window.agregarEvento=agregarEvento;
window.abrirModalCurso=abrirModalCurso;
window.cerrarModalCurso=cerrarModalCurso;
window.guardarCursoModal=guardarCursoModal;
window.eliminarCurso=eliminarCurso;
window.nuevaNota=nuevaNota;
window.eliminarNota=eliminarNota;
window.actualizarNota=actualizarNota;
})();
