/**
 * Sistema de sidebar responsiva
 * Maneja el comportamiento de la barra lateral fija con toggle en móvil
 */

(function() {
    'use strict';

    // Elementos
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('[data-sidebar-toggle]');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    
    if (!sidebar) return; // No hay sidebar en esta página

    // Agregar overlay al body
    document.body.appendChild(overlay);

    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }

    // Cerrar sidebar
    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    // Event listeners
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    overlay.addEventListener('click', closeSidebar);

    // Cerrar al hacer clic en un enlace (solo en móvil)
    const sidebarLinks = sidebar.querySelectorAll('.nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });

    // Cerrar al presionar ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Cerrar sidebar al cambiar de móvil a escritorio
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });

})();
