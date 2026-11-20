// Pequeñas ayudas de usabilidad en los formularios.
document.addEventListener('DOMContentLoaded', () => {

    // Vista previa de la imagen elegida
    const archivo = document.getElementById('f_imagen');
    const vista = document.getElementById('vista_previa');
    if (archivo && vista) {
        archivo.addEventListener('change', () => {
            const f = archivo.files[0];
            if (!f) return;
            if (f.size > 2 * 1024 * 1024) {
                archivo.setCustomValidity('La imagen pesa más de 2 MB.');
                archivo.reportValidity();
                archivo.value = '';
                return;
            }
            archivo.setCustomValidity('');
            vista.src = URL.createObjectURL(f);
        });
    }

    // Validación en el navegador con los estilos de Bootstrap
    document.querySelectorAll('form[data-valida]').forEach(form => {
        form.addEventListener('submit', ev => {
            if (!form.checkValidity()) {
                ev.preventDefault();
                ev.stopPropagation();
                form.classList.add('was-validated');
                const primero = form.querySelector(':invalid');
                if (primero) primero.focus();
            }
        });
    });

    // En el buscador, el campo cambia a fecha cuando se busca por fecha
    document.querySelectorAll('select[data-cambia-tipo]').forEach(sel => {
        const q = document.getElementById('q');
        sel.addEventListener('change', () => {
            const modo = sel.selectedOptions[0].dataset.modo;
            q.value = '';
            q.type = modo === 'fecha' ? 'date' : 'search';
            q.focus();
        });
    });
});
