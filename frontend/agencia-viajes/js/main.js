// Funcionalidades generales para el frontend del cliente
document.addEventListener('DOMContentLoaded', function() {
    initializeDatePickers();
    initializePassengerForms();
    initializeFormValidations();
    initializeSmoothScrolling();
});

// Configurar datepickers con fechas disponibles
function initializeDatePickers() {
    const today = new Date().toISOString().split('T')[0];
    const departureDateInput = document.getElementById('departure_date');
    const returnDateInput = document.getElementById('return_date');
    
    if (departureDateInput) {
        departureDateInput.min = today;
        
        // Actualizar fecha mínima de regreso cuando cambia la fecha de ida
        departureDateInput.addEventListener('change', function() {
            if (returnDateInput) {
                returnDateInput.min = this.value;
                if (returnDateInput.value < this.value) {
                    returnDateInput.value = this.value;
                }
                updateAvailableDates(this.value, 'departure');
            }
        });
    }
    
    if (returnDateInput) {
        returnDateInput.min = today;
    }
    
    // Inicializar selector de solo ida
    const oneWayCheckbox = document.getElementById('one_way');
    if (oneWayCheckbox) {
        oneWayCheckbox.addEventListener('change', function() {
            if (this.checked) {
                returnDateInput.disabled = true;
                returnDateInput.value = '';
            } else {
                returnDateInput.disabled = false;
                if (departureDateInput.value) {
                    returnDateInput.min = departureDateInput.value;
                    returnDateInput.value = departureDateInput.value;
                }
            }
        });
    }
}

// Actualizar fechas disponibles basado en origen y destino
function updateAvailableDates(selectedDate, type) {
    const origin = document.getElementById('origin')?.value;
    const destination = document.getElementById('destination')?.value;
    const packageType = document.getElementById('package_type')?.value;
    
    if (!origin || !destination || !selectedDate) return;
    
    // En una implementación real, haríamos una petición AJAX al servidor
    // para obtener las fechas disponibles según el origen y destino
    console.log(`Buscando fechas disponibles para ${origin} -> ${destination} en ${selectedDate}`);
}

// Manejar formularios de pasajeros dinámicos
function initializePassengerForms() {
    const addPassengerBtn = document.getElementById('add-passenger');
    const passengersContainer = document.getElementById('passengers-container');
    
    if (addPassengerBtn && passengersContainer) {
        let passengerCount = passengersContainer.children.length;
        
        addPassengerBtn.addEventListener('click', function() {
            passengerCount++;
            const passengerHTML = `
                <div class="passenger-form" id="passenger-${passengerCount}">
                    <h3>Pasajero ${passengerCount}</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="passenger_name_${passengerCount}">Nombre Completo</label>
                            <input type="text" id="passenger_name_${passengerCount}" name="passengers[${passengerCount}][name]" required>
                        </div>
                        <div class="form-group">
                            <label for="passenger_birthdate_${passengerCount}">Fecha de Nacimiento</label>
                            <input type="date" id="passenger_birthdate_${passengerCount}" name="passengers[${passengerCount}][birthdate]" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="passenger_gender_${passengerCount}">Género</label>
                            <select id="passenger_gender_${passengerCount}" name="passengers[${passengerCount}][gender]" required>
                                <option value="">Seleccionar</option>
                                <option value="male">Masculino</option>
                                <option value="female">Femenino</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="passenger_country_${passengerCount}">País de Nacimiento</label>
                            <input type="text" id="passenger_country_${passengerCount}" name="passengers[${passengerCount}][country]" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-passenger" data-passenger="${passengerCount}">Eliminar Pasajero</button>
                </div>
            `;
            
            passengersContainer.insertAdjacentHTML('beforeend', passengerHTML);
        });
        
        // Eliminar pasajero
        passengersContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-passenger')) {
                const passengerId = e.target.getAttribute('data-passenger');
                const passengerElement = document.getElementById(`passenger-${passengerId}`);
                if (passengerElement) {
                    passengerElement.remove();
                }
            }
        });
    }
}

// Validación de formularios
function initializeFormValidations() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'var(--danger)';
                    // Agregar mensaje de error
                    if (!field.nextElementSibling?.classList.contains('error-message')) {
                        const errorMsg = document.createElement('span');
                        errorMsg.className = 'error-message';
                        errorMsg.style.color = 'var(--danger)';
                        errorMsg.style.fontSize = '0.8rem';
                        errorMsg.textContent = 'Este campo es obligatorio';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.style.borderColor = '';
                    // Remover mensaje de error si existe
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg?.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            // Validación específica para fechas de nacimiento de niños
            const childrenBirthdates = form.querySelectorAll('[id*="passenger_birthdate"]');
            const today = new Date();
            const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            
            childrenBirthdates.forEach(input => {
                if (input.value) {
                    const birthdate = new Date(input.value);
                    if (birthdate > eighteenYearsAgo) {
                        // Es un niño, validar que la fecha sea válida
                        const passengerNumber = input.id.split('_').pop();
                        const isChild = passengerNumber > document.getElementById('adults')?.value;
                        
                        if (isChild && birthdate > eighteenYearsAgo) {
                            valid = false;
                            input.style.borderColor = 'var(--danger)';
                            alert('Los niños deben ser menores de 18 años');
                        }
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
                if (!form.querySelector('.alert-error')) {
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-error';
                    errorAlert.textContent = 'Por favor, complete todos los campos obligatorios correctamente.';
                    form.prepend(errorAlert);
                }
            }
        });
    });
}

// Animaciones suaves para enlaces
function initializeSmoothScrolling() {
    const smoothLinks = document.querySelectorAll('a[href^="#"]');
    smoothLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Función para mostrar/ocultar fecha de regreso basado en tipo de paquete
function toggleReturnDate() {
    const packageType = document.getElementById('package_type').value;
    const oneWay = document.getElementById('one_way');
    const returnDateGroup = document.getElementById('return_date_group');
    
    if (packageType === 'hotel_only') {
        if (returnDateGroup) returnDateGroup.style.display = 'none';
        if (oneWay) oneWay.checked = false;
    } else {
        if (returnDateGroup) returnDateGroup.style.display = 'block';
    }
    
    if (oneWay && oneWay.checked) {
        document.getElementById('return_date').disabled = true;
    } else {
        document.getElementById('return_date').disabled = false;
    }
}

// Función para actualizar destinos basados en el origen seleccionado
function updateDestinations() {
    const origin = document.getElementById('origin').value;
    const destinationSelect = document.getElementById('destination');
    
    if (origin) {
        // Mostrar loading
        destinationSelect.innerHTML = '<option value="">Cargando destinos...</option>';
        
        // Hacer una petición AJAX para obtener los destinos disponibles para ese origen
        fetch(`get_destinations.php?origin=${encodeURIComponent(origin)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                destinationSelect.innerHTML = '<option value="">Seleccionar destino</option>';
                data.forEach(dest => {
                    const option = document.createElement('option');
                    option.value = dest;
                    option.textContent = dest;
                    destinationSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                destinationSelect.innerHTML = '<option value="">Error al cargar destinos</option>';
            });
    } else {
        destinationSelect.innerHTML = '<option value="">Primero seleccione origen</option>';
    }
}

// Función para calcular totales en tiempo real
function calculateTotal() {
    const adults = parseInt(document.getElementById('adults')?.value) || 0;
    const children = parseInt(document.getElementById('children')?.value) || 0;
    const priceElement = document.querySelector('.price');
    
    if (priceElement) {
        const pricePerPerson = parseFloat(priceElement.dataset.price) || 0;
        const total = (adults + children) * pricePerPerson;
        document.getElementById('total-price').textContent = `$${total.toFixed(2)}`;
    }
}

// Inicializar tooltips
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltipText = this.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.style.position = 'absolute';
    tooltip.style.background = 'rgba(0,0,0,0.8)';
    tooltip.style.color = 'white';
    tooltip.style.padding = '5px 10px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '0.8rem';
    tooltip.style.zIndex = '1000';
    
    document.body.appendChild(tooltip);
    
    const rect = this.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    
    this.tooltipElement = tooltip;
}

function hideTooltip() {
    if (this.tooltipElement) {
        this.tooltipElement.remove();
        this.tooltipElement = null;
    }
}