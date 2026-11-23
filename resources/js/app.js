/*
 * Comportamiento compartido de la interfaz (sin dependencias).
 *
 *  - Menú lateral móvil:     [data-sidebar-toggle] / [data-sidebar-overlay]
 *  - Modales (<dialog>):     [data-modal-open="id"] / [data-modal-close]
 *                            <dialog data-open-on-load> se abre al cargar (errores de validación)
 *  - Confirmación:           <form data-confirm="mensaje"> pide confirmación antes de enviar
 *  - Indicador de carga:     los formularios deshabilitan sus botones de envío al enviarse
 *  - Avisos descartables:    [data-dismiss] cierra el aviso que lo contiene
 */

const sidebar = () => document.getElementById('app-sidebar');
const overlay = () => document.querySelector('[data-sidebar-overlay]');

function toggleSidebar(abrir) {
    const panel = sidebar();
    const fondo = overlay();

    if (!panel || !fondo) {
        return;
    }

    const mostrar = abrir ?? panel.classList.contains('-translate-x-full');

    panel.classList.toggle('-translate-x-full', !mostrar);
    fondo.classList.toggle('hidden', !mostrar);
    document.querySelectorAll('[data-sidebar-toggle]').forEach((boton) => {
        boton.setAttribute('aria-expanded', String(mostrar));
    });
}

document.addEventListener('click', (evento) => {
    const objetivo = evento.target instanceof Element ? evento.target : null;

    if (!objetivo) {
        return;
    }

    if (objetivo.closest('[data-sidebar-toggle]')) {
        toggleSidebar();
        return;
    }

    if (objetivo.closest('[data-sidebar-overlay]')) {
        toggleSidebar(false);
        return;
    }

    const abrir = objetivo.closest('[data-modal-open]');

    if (abrir) {
        const modal = document.getElementById(abrir.getAttribute('data-modal-open'));

        if (modal && typeof modal.showModal === 'function' && !modal.open) {
            modal.showModal();
        }

        return;
    }

    if (objetivo.closest('[data-modal-close]')) {
        objetivo.closest('dialog')?.close();
        return;
    }

    // Clic sobre el fondo oscuro del <dialog>: cierra el modal.
    if (objetivo instanceof HTMLDialogElement && objetivo.classList.contains('erp-modal')) {
        objetivo.close();
        return;
    }

    objetivo.closest('[data-dismiss]')?.closest('[role="alert"]')?.remove();
});

document.addEventListener('submit', (evento) => {
    const formulario = evento.target;

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const mensaje = formulario.getAttribute('data-confirm');

    if (mensaje && !window.confirm(mensaje)) {
        evento.preventDefault();
        return;
    }

    // Se deshabilita tras el envío para no perder el valor de los botones con nombre.
    window.setTimeout(() => {
        formulario.querySelectorAll('button[type="submit"]:not([data-no-loading])').forEach((boton) => {
            boton.disabled = true;
            boton.setAttribute('data-submitting', '');
            boton.setAttribute('aria-busy', 'true');
        });
    }, 0);
});

// Al volver con el botón "atrás" los botones no deben quedarse bloqueados.
window.addEventListener('pageshow', (evento) => {
    if (!evento.persisted) {
        return;
    }

    document.querySelectorAll('button[data-submitting]').forEach((boton) => {
        boton.disabled = false;
        boton.removeAttribute('data-submitting');
        boton.removeAttribute('aria-busy');
    });
});

/*
 * Desplegables dependientes: <select data-depende-de="campo"> con <option data-padre="valor">.
 * Al elegir un valor en el campo padre (mismo formulario) solo quedan visibles sus opciones;
 * si la opción elegida deja de corresponder, se vacía. Sin valor en el padre se muestran todas.
 * Solo es una ayuda: el servidor vuelve a validar la relación.
 */
function filtrarDependientes(padre) {
    const formulario = padre.form;
    if (!formulario || !padre.name) {
        return;
    }
    formulario.querySelectorAll(`select[data-depende-de="${padre.name}"]`).forEach((hijo) => {
        Array.from(hijo.options).forEach((opcion) => {
            const padreDeLaOpcion = opcion.getAttribute('data-padre');
            const visible = opcion.value === '' || padre.value === '' || padreDeLaOpcion === padre.value;
            opcion.hidden = !visible;
            opcion.disabled = !visible;
        });
        const elegida = hijo.selectedOptions[0];
        if (elegida && elegida.disabled) {
            hijo.value = '';
        }
        filtrarDependientes(hijo);
    });
}

document.addEventListener('change', (evento) => {
    if (evento.target instanceof HTMLSelectElement) {
        filtrarDependientes(evento.target);
    }
});

/*
 * Campos según la acción: los elementos con data-acciones="venta baja" solo se muestran (y envían)
 * cuando el <select data-selector-accion> del mismo formulario tiene una de esas acciones.
 */
function mostrarCamposDeAccion(selector) {
    const formulario = selector.form;
    if (!formulario) {
        return;
    }
    formulario.querySelectorAll('[data-acciones]').forEach((bloque) => {
        const visible = bloque.getAttribute('data-acciones').split(' ').includes(selector.value);
        bloque.hidden = !visible;
        bloque.querySelectorAll('input, select, textarea').forEach((campo) => {
            campo.disabled = !visible;
        });
    });
}

document.addEventListener('change', (evento) => {
    if (evento.target instanceof HTMLSelectElement && evento.target.hasAttribute('data-selector-accion')) {
        mostrarCamposDeAccion(evento.target);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select').forEach((campo) => {
        if (campo.form && campo.name && campo.form.querySelector(`select[data-depende-de="${campo.name}"]`)) {
            filtrarDependientes(campo);
        }
    });
    document.querySelectorAll('select[data-selector-accion]').forEach(mostrarCamposDeAccion);

    document.querySelectorAll('dialog[data-open-on-load]').forEach((modal) => {
        if (typeof modal.showModal === 'function' && !modal.open) {
            modal.showModal();
        }
    });
});

document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') {
        toggleSidebar(false);
    }
});
