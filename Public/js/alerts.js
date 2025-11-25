// Public/js/alerts.js
// Sistema centralizado de alertas con SweetAlert2

/**
 * Configuración global de SweetAlert2
 */
const SwalConfig = {
    customClass: {
        popup: 'swal-brand-popup',
        title: 'swal-brand-title',
        confirmButton: 'btn btn-brand',
        cancelButton: 'btn btn-secondary',
        denyButton: 'btn btn-danger'
    },
    buttonsStyling: false,
    confirmButtonText: 'Aceptar',
    cancelButtonText: 'Cancelar',
    denyButtonText: 'No'
};

/**
 * Alerta de éxito
 */
function showSuccess(title, text = '', timer = 3000) {
    return Swal.fire({
        ...SwalConfig,
        icon: 'success',
        title: title,
        text: text,
        timer: timer,
        showConfirmButton: timer > 5000,
        timerProgressBar: true
    });
}

/**
 * Alerta de error
 */
function showError(title, text = '') {
    return Swal.fire({
        ...SwalConfig,
        icon: 'error',
        title: title,
        text: text,
        confirmButtonText: 'Entendido'
    });
}

/**
 * Alerta de advertencia
 */
function showWarning(title, text = '') {
    return Swal.fire({
        ...SwalConfig,
        icon: 'warning',
        title: title,
        text: text
    });
}

/**
 * Alerta informativa
 */
function showInfo(title, text = '') {
    return Swal.fire({
        ...SwalConfig,
        icon: 'info',
        title: title,
        text: text
    });
}

/**
 * Confirmación simple
 */
function showConfirm(title, text = '', confirmText = 'Sí, continuar') {
    return Swal.fire({
        ...SwalConfig,
        icon: 'question',
        title: title,
        text: text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar'
    });
}

/**
 * Confirmación peligrosa (para eliminaciones)
 */
function showDangerConfirm(title, text = '', confirmText = 'Sí, eliminar') {
    return Swal.fire({
        ...SwalConfig,
        icon: 'warning',
        title: title,
        text: text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        customClass: {
            ...SwalConfig.customClass,
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        reverseButtons: true
    });
}

/**
 * Confirmación doble (para acciones críticas)
 */
async function showDoubleConfirm(title, text, confirmText = 'Sí, estoy seguro') {
    const first = await Swal.fire({
        ...SwalConfig,
        icon: 'warning',
        title: title,
        text: text,
        showCancelButton: true,
        confirmButtonText: 'Continuar',
        customClass: {
            ...SwalConfig.customClass,
            confirmButton: 'btn btn-warning'
        }
    });

    if (first.isConfirmed) {
        return Swal.fire({
            ...SwalConfig,
            icon: 'error',
            title: '⚠️ ¿Estás COMPLETAMENTE seguro?',
            text: 'Esta acción no se puede deshacer',
            showCancelButton: true,
            confirmButtonText: confirmText,
            customClass: {
                ...SwalConfig.customClass,
                confirmButton: 'btn btn-danger'
            },
            reverseButtons: true
        });
    }
    
    return { isConfirmed: false };
}

/**
 * Alerta con input
 */
function showPrompt(title, inputLabel, inputPlaceholder = '', inputType = 'text') {
    return Swal.fire({
        ...SwalConfig,
        title: title,
        input: inputType,
        inputLabel: inputLabel,
        inputPlaceholder: inputPlaceholder,
        showCancelButton: true,
        inputValidator: (value) => {
            if (!value) {
                return 'Este campo es obligatorio';
            }
        }
    });
}

/**
 * Alerta con textarea
 */
function showTextarea(title, inputLabel, inputPlaceholder = '', minLength = 10) {
    return Swal.fire({
        ...SwalConfig,
        title: title,
        input: 'textarea',
        inputLabel: inputLabel,
        inputPlaceholder: inputPlaceholder,
        showCancelButton: true,
        inputValidator: (value) => {
            if (!value) {
                return 'Este campo es obligatorio';
            }
            if (value.length < minLength) {
                return `Debe tener al menos ${minLength} caracteres`;
            }
        }
    });
}

/**
 * Loading (spinner)
 */
function showLoading(title = 'Procesando...', text = 'Por favor espera') {
    Swal.fire({
        title: title,
        text: text,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Cerrar loading
 */
function closeLoading() {
    Swal.close();
}

/**
 * Toast (notificación pequeña)
 */
function showToast(icon, title, position = 'top-end', timer = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: position,
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    return Toast.fire({
        icon: icon,
        title: title
    });
}

/**
 * Toast de éxito
 */
function toastSuccess(title) {
    return showToast('success', title);
}

/**
 * Toast de error
 */
function toastError(title) {
    return showToast('error', title, 'top-end', 4000);
}

/**
 * Toast de información
 */
function toastInfo(title) {
    return showToast('info', title);
}

/**
 * Toast de advertencia
 */
function toastWarning(title) {
    return showToast('warning', title);
}

/**
 * Alerta con HTML personalizado
 */
function showHTML(title, html) {
    return Swal.fire({
        ...SwalConfig,
        title: title,
        html: html,
        confirmButtonText: 'Cerrar'
    });
}

/**
 * Alerta de procesando con progreso
 */
function showProgress(title, text = '') {
    let timerInterval;
    return Swal.fire({
        title: title,
        html: text + '<br><b></b> segundos transcurridos',
        timer: 0,
        timerProgressBar: true,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
            const b = Swal.getHtmlContainer().querySelector('b');
            let seconds = 0;
            timerInterval = setInterval(() => {
                seconds++;
                b.textContent = seconds;
            }, 1000);
        },
        willClose: () => {
            clearInterval(timerInterval);
        }
    });
}

/**
 * Alerta con lista de items
 */
function showList(title, items, icon = 'info') {
    const html = '<ul class="text-start">' + 
                 items.map(item => `<li>${item}</li>`).join('') + 
                 '</ul>';
    
    return Swal.fire({
        ...SwalConfig,
        icon: icon,
        title: title,
        html: html
    });
}

/**
 * Alerta de paso a paso (wizard)
 */
const SwalSteps = {
    currentStep: 1,
    totalSteps: 1,
    
    async showStep(stepNumber, title, html, confirmText = 'Siguiente', isLast = false) {
        return Swal.fire({
            ...SwalConfig,
            title: `Paso ${stepNumber}/${this.totalSteps}: ${title}`,
            html: html,
            confirmButtonText: isLast ? 'Finalizar' : confirmText,
            showCancelButton: true,
            cancelButtonText: stepNumber > 1 ? 'Anterior' : 'Cancelar',
            progressSteps: Array.from({length: this.totalSteps}, (_, i) => String(i + 1)),
            currentProgressStep: stepNumber - 1
        });
    }
};

/**
 * Reemplazar alert() nativo
 */
window.alert = function(message) {
    showInfo('Información', message);
};

/**
 * Reemplazar confirm() nativo
 */
const originalConfirm = window.confirm;
window.confirm = async function(message) {
    const result = await showConfirm('Confirmación', message, 'Sí');
    return result.isConfirmed;
};

// Restaurar confirm original para casos específicos
window.confirmOriginal = originalConfirm;
