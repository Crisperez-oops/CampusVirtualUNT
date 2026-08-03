/**
 * battles.js
 * Maneja el formulario "Unirme a una batalla" de battles.php.
 * Llama a api/batalla_unirse.php y redirige a jugar_batalla.php?codigo=...
 * (el parámetro es `codigo`, no `sala` — jugar_batalla.php espera el
 * código de 6 dígitos, no el id interno de la sala).
 */
(function () {
    const form        = document.getElementById('formUnirse');
    const inputCodigo = document.getElementById('codigoBatalla');
    const inputApodo  = document.getElementById('apodoBatalla');
    const boton       = document.getElementById('btnUnirse');
    const botonTexto  = document.getElementById('btnUnirseTexto');
    const errorBox    = document.getElementById('errorUnirse');

    if (!form) return; // por si el script se carga en otra página sin este formulario

    // Solo dígitos en el código, máx 6.
    inputCodigo.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    function mostrarError(msg) {
        errorBox.textContent = msg;
        errorBox.classList.remove('oculto');
    }

    function ocultarError() {
        errorBox.textContent = '';
        errorBox.classList.add('oculto');
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        ocultarError();

        const codigo = inputCodigo.value.trim();
        const apodo  = inputApodo.value.trim();

        if (codigo.length !== 6) {
            mostrarError('El código debe tener 6 dígitos.');
            inputCodigo.focus();
            return;
        }

        boton.disabled = true;
        botonTexto.textContent = 'Entrando…';

        try {
            const fd = new FormData();
            fd.append('codigo', codigo);
            if (apodo) fd.append('apodo', apodo);

            const res = await fetch('api/batalla_unirse.php', { method: 'POST', body: fd });

            // Leemos como texto primero: si el servidor devolvió un error PHP,
            // una redirección de login, o un 404, no será JSON válido, y así
            // podemos mostrar algo útil en vez de un "error de conexión" genérico.
            const textoBruto = await res.text();
            let data;
            try {
                data = JSON.parse(textoBruto);
            } catch (parseErr) {
                console.error('Respuesta no-JSON de batalla_unirse.php (HTTP ' + res.status + '):', textoBruto);
                mostrarError(`Error del servidor (HTTP ${res.status}). Revisa la consola para más detalle.`);
                return;
            }

            if (!data.ok) {
                mostrarError(data.error || 'No se pudo unir a la sala.');
                return;
            }

            // IMPORTANTE: jugar_batalla.php espera ?codigo=, no ?sala=
            window.location.href = `jugar_batalla.php?codigo=${encodeURIComponent(data.codigo)}`;

        } catch (err) {
            console.error('Fallo de red al llamar batalla_unirse.php:', err);
            mostrarError('Error de conexión. Intenta de nuevo.');
        } finally {
            boton.disabled = false;
            botonTexto.textContent = 'Unirme';
        }
    });
})();