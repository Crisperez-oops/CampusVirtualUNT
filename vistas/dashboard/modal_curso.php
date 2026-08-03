<div class="modal-fondo" id="modal-curso">
    <div class="modal-caja">
        <div class="modal-titulo" id="modal-curso-titulo">Nuevo curso</div>
        <input type="hidden" id="modal-curso-id">
        <div class="modal-campo">
            <label>Nombre del curso</label>
            <input type="text" id="modal-curso-nombre" placeholder="ej: Programación Orientada a Objetos II">
        </div>
        <div class="modal-campo">
            <label>Docente (opcional)</label>
            <input type="text" id="modal-curso-docente" placeholder="ej: Ing. Pérez">
        </div>
        <div class="modal-campo">
            <label>Créditos (opcional)</label>
            <input type="number" id="modal-curso-creditos" min="1" max="10" placeholder="4">
        </div>
        <div class="modal-campo">
            <label>Color</label>
            <div class="modal-colores" id="modal-curso-colores"></div>
        </div>
        <div class="modal-acciones">
            <button onclick="cerrarModalCurso()">Cancelar</button>
            <button class="btn-guardar" onclick="guardarCursoModal()">Guardar curso</button>
        </div>
    </div>
</div>
