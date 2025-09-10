document.addEventListener("DOMContentLoaded", () => {
    const confirmModal = new bootstrap.Modal(document.getElementById("confirmModal"));
    const modalMessage = document.getElementById("modal-message");
    const modalConfirmBtn = document.getElementById("modal-confirm-btn");

    let currentForm = null;

    // Confirmación al reservar
    document.getElementById("btn-reservar")?.addEventListener("click", () => {
        currentForm = document.getElementById("form-reserva");

        // Validación antes del modal
        const aula = currentForm.querySelector("[name='id_aula']").value;
        const fecha = currentForm.querySelector("[name='fecha']").value;
        const horaInicio = currentForm.querySelector("[name='hora_inicio']").value;
        const horaFin = currentForm.querySelector("[name='hora_fin']").value;

        if (!aula || !fecha || !horaInicio || !horaFin) {
            alert("⚠️ Por favor completa todos los campos antes de reservar.");
            return;
        }

        modalMessage.textContent = "¿Seguro que deseas realizar esta reserva?";
        modalConfirmBtn.onclick = () => currentForm.submit();
        confirmModal.show();
    });

    // Confirmación al cancelar
    document.querySelectorAll(".cancelar-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            currentForm = e.target.closest("form");
            modalMessage.textContent = "⚠️ ¿Seguro que deseas cancelar esta reserva?";
            modalConfirmBtn.onclick = () => currentForm.submit();
            confirmModal.show();
        });
    });
});
