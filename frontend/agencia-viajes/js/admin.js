// Funcionalidades para el panel de administración
document.addEventListener('DOMContentLoaded', function() {
    // Confirmación para acciones de eliminación
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
    
    // Toggle para formularios de edición
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const formId = this.getAttribute('data-form');
            const form = document.getElementById(formId);
            if (form) {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }
        });
    });
    
    // Filtros para tablas
    const filterInputs = document.querySelectorAll('.table-filter');
    filterInputs.forEach(input => {
        input.addEventListener('input', function() {
            const filterValue = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filterValue) ? '' : 'none';
            });
        });
    });
    
    // Actualización de estadísticas en tiempo real
    function updateStats() {
        // En un sistema real, haríamos una petición AJAX para obtener estadísticas actualizadas
        console.log('Actualizando estadísticas...');
    }
    
    // Actualizar cada 30 segundos
    setInterval(updateStats, 30000);
    
    // Manejo de formularios con validación mejorada
    const adminForms = document.querySelectorAll('form');
    adminForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const numberFields = form.querySelectorAll('input[type="number"]');
            let valid = true;
            
            numberFields.forEach(field => {
                const min = parseFloat(field.getAttribute('min')) || 0;
                const max = parseFloat(field.getAttribute('max')) || Infinity;
                const value = parseFloat(field.value);
                
                if (value < min || value > max) {
                    valid = false;
                    field.style.borderColor = 'var(--danger)';
                    alert(`El valor de ${field.previousElementSibling.textContent} debe estar entre ${min} y ${max}`);
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
    
    // Funcionalidad de búsqueda avanzada
    const advancedSearchToggle = document.getElementById('advanced-search-toggle');
    const advancedSearch = document.getElementById('advanced-search');
    
    if (advancedSearchToggle && advancedSearch) {
        advancedSearchToggle.addEventListener('click', function() {
            advancedSearch.style.display = advancedSearch.style.display === 'none' ? 'block' : 'none';
            this.textContent = advancedSearch.style.display === 'none' ? 'Mostrar Búsqueda Avanzada' : 'Ocultar Búsqueda Avanzada';
        });
    }
});