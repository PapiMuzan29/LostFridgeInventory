document.addEventListener('DOMContentLoaded', () => {
            const style = document.createElement('style');
            style.innerHTML = `
                body.no-scroll {
                    overflow: hidden !important;
                }
            `;
            document.head.appendChild(style);

            const observer = new MutationObserver(() => {
                const modalesAbiertos = document.querySelectorAll(
                    '.modal-overlay.active, ' + 
                    '.modal-alert-overlay[style*="display: flex"], ' + 
                    '.modal-overlay[style*="display: flex"]'
                );
                
                if (modalesAbiertos.length > 0) {
                    document.body.classList.add('no-scroll');
                } else {
                    document.body.classList.remove('no-scroll');
                }
            });

            document.querySelectorAll('.modal-overlay, .modal-alert-overlay').forEach(modal => {
                observer.observe(modal, { attributes: true, attributeFilter: ['class', 'style'] });
            });
        });document.addEventListener('DOMContentLoaded', () => {
            const style = document.createElement('style');
            style.innerHTML = `
                body.no-scroll {
                    overflow: hidden !important;
                }
            `;
            document.head.appendChild(style);

            const observer = new MutationObserver(() => {
                const modalesAbiertos = document.querySelectorAll(
                    '.modal-overlay.active, ' + 
                    '.modal-alert-overlay[style*="display: flex"], ' + 
                    '.modal-overlay[style*="display: flex"]'
                );
                
                if (modalesAbiertos.length > 0) {
                    document.body.classList.add('no-scroll');
                } else {
                    document.body.classList.remove('no-scroll');
                }
            });

            document.querySelectorAll('.modal-overlay, .modal-alert-overlay').forEach(modal => {
                observer.observe(modal, { attributes: true, attributeFilter: ['class', 'style'] });
            });
        }); 
 // Gestor de Modo Oscuro con LocalStorage
        function toggleDarkMode(isDark) {
            if (isDark) {
                document.body.classList.add('dark-mode');
                document.documentElement.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                document.documentElement.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        }

        document.getElementById('btn-add-obs').addEventListener('click', function() {
            const cont = document.getElementById('contenedor-observacion');
            cont.style.display = 'block';
            document.getElementById('observacion_especial').focus();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme');
            const toggleInput = document.getElementById('toggle-dark-mode');
            
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
                if (toggleInput) toggleInput.checked = true;
            }


            document.querySelectorAll('.producto-search').forEach(input => {
                if (input.value.trim() !== '') {
                    capturarIdProducto(input, true);
                }
            });


            // Escucha global: si se escribe en cualquier input de kilos, piezas o buscador, se recalcula el ticket
            document.getElementById('products-container').addEventListener('input', function(e) {
                if (e.target.classList.contains('input-kilos') || 
                    e.target.classList.contains('input-piezas') || 
                    e.target.classList.contains('producto-search')) {
                    calcularTicket();
                }
            });
            
            calcularTicket();
        });

        // Navegación entre Pestañas
        function switchTab(tabName, btnElement) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            document.querySelectorAll('.nav-item').forEach(nav => {
                nav.classList.remove('active');
            });

            if (tabName === 'inicio') {
                document.getElementById('tab-inicio').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Módulo de Captura de Pedidos';
            } else if (tabName === 'notas') {
                document.getElementById('tab-notas').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Notas en Espera y Rechazadas';
            } else if (tabName === 'config') {
                document.getElementById('tab-config').classList.add('active');
                document.getElementById('header-subtitle').textContent = 'Configuración del Sistema';
            }

            btnElement.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function procesarAperturaCaja(btn) {
            const idCaja = btn.getAttribute('data-id');
            const modal = document.getElementById('modal-abrir-caja');
            
            modal.setAttribute('data-id-caja', idCaja);
            document.getElementById('peso-caja-input').value = '';
            modal.style.display = 'flex';
            setTimeout(() => document.getElementById('peso-caja-input').focus(), 100);
        }

        function cerrarModalCaja() {
            document.getElementById('modal-abrir-caja').style.display = 'none';
        }

        // Se le agrega el parámetro fromLoad para evitar que borre los números si viene de una recarga de página
        function capturarIdProducto(inputSearch, fromLoad = false) {
            const val = inputSearch.value.trim().toLowerCase();
            const options = document.querySelectorAll('#lista-productos option');
            const row = inputSearch.closest('.product-item');
            const hiddenInput = row.querySelector('.producto-id-hidden');
            const inputKilos = row.querySelector('.input-kilos');
            const inputPiezas = row.querySelector('.input-piezas');
            const contenedorBotonCaja = row.querySelector('.contenedor-abrir-caja');
            
            // Si NO venimos de una recarga, borramos los campos para que escriba
            if (!fromLoad) {
                hiddenInput.value = '';
                inputKilos.value = '';
                inputPiezas.value = '';
            }
            
            inputKilos.disabled = false;
            inputKilos.readOnly = false;
            inputPiezas.disabled = false;
            inputPiezas.readOnly = false;
            inputPiezas.oninput = null; 
            
            if (contenedorBotonCaja) contenedorBotonCaja.style.display = 'none';

            let matchFound = false;

            options.forEach(opt => {
                const optVal = opt.value.trim().toLowerCase();
                
                if (optVal === val) {
                    matchFound = true;
                    const idProducto = opt.getAttribute('data-id');
                    hiddenInput.value = idProducto;
                    
                    // Formateamos a 2 decimales para que "1.0000" se vea como "1.00"
                    const pesoCrudo = parseFloat(opt.getAttribute('data-stock-peso') || '0');
                    const stockKilos = pesoCrudo.toFixed(2); 

                    const stockCajas = parseInt(opt.getAttribute('data-stock-cajas')) || 0;
                    const stockPiezas = parseInt(opt.getAttribute('data-stock-piezas')) || 0;
                    const stockUnidades = stockCajas > 0 ? stockCajas : stockPiezas;
                    
                    const esMazo = optVal.includes('mazo');
                    const esManteca = optVal.includes('manteca');
                    const esSal = optVal.includes('sal');

                    if (optVal === "caja pechos") {
                        inputKilos.readOnly = false;
                        inputKilos.disabled = false;
                        inputKilos.placeholder = 'Kilos totales';
                        inputKilos.required = true; 
                        
                        inputPiezas.readOnly = false;
                        inputPiezas.disabled = false; 
                        inputPiezas.placeholder = `Max: ${stockUnidades} cajas`;
                        inputPiezas.required = true;

                        if (contenedorBotonCaja) {
                            contenedorBotonCaja.style.display = 'block';
                            const btnAbrir = contenedorBotonCaja.querySelector('.btn-abrir-caja');
                            btnAbrir.setAttribute('data-id', idProducto);
                            btnAbrir.innerHTML = '<i class="fa-solid fa-box-open"></i> Abrir 1 Caja a Granel';
                        }
                        
                    } else if (esSal) { 
                        const stockCajas = parseInt(opt.getAttribute('data-stock-cajas')) || 0;
                        const stockKilosBD = parseFloat(opt.getAttribute('data-stock-peso')) || 0;
                        const totalKilosDisponibles = (stockCajas * 10) + stockKilosBD;

                        inputKilos.readOnly = false;
                        inputKilos.disabled = false;
                        inputKilos.required = true;
                        inputKilos.min = "1"; // Fuerza mínimo 1 kilo
                        inputKilos.placeholder = `Max: ${totalKilosDisponibles.toFixed(2)} kg`;
                        
                        inputPiezas.readOnly = true;
                        inputPiezas.disabled = true;
                        inputPiezas.required = false;
                        inputPiezas.placeholder = `${stockCajas} bultos disp.`;
                        inputPiezas.value = '';

                    } else if (esManteca) {
                        const matchNumeros = optVal.match(/\d+/);
                        const kilosPorPieza = matchNumeros ? parseFloat(matchNumeros[0]) : 0;

                        inputKilos.readOnly = true; 
                        inputKilos.placeholder = 'Auto-calculado';
                        inputKilos.required = true;
                        
                        inputPiezas.disabled = false;
                        inputPiezas.required = true;
                        inputPiezas.placeholder = `Max: ${stockUnidades}`;

                        inputPiezas.oninput = function() {
                            const cantPiezas = parseInt(this.value) || 0;
                            inputKilos.value = (cantPiezas * kilosPorPieza).toFixed(2);
                            calcularTicket();
                        };

                    } else if (esMazo) {
                        inputKilos.readOnly = true;
                        inputKilos.value = '0.00';
                        inputKilos.required = false;
                        
                        inputPiezas.disabled = false;
                        inputPiezas.required = true;
                        inputPiezas.placeholder = `Max: ${stockUnidades}`;
                        
                    } else {
                        inputKilos.disabled = false;
                        inputKilos.required = true;
                        inputKilos.placeholder = `Max: ${stockKilos} kg`;
                        
                        inputPiezas.disabled = false;
                        inputPiezas.placeholder = 'Piezas físicas';
                    }
                }
            });

            if (!matchFound && inputSearch.value !== '' && !fromLoad) {
                inputSearch.value = '';
                mostrarAlerta('Por favor selecciona un producto válido de la lista.', 'error');
            }
        }

        function confirmarAperturaCaja(e) {
            const inputPeso = document.getElementById('peso-caja-input').value;
            const peso = parseFloat(inputPeso);
            const idCaja = document.getElementById('modal-abrir-caja').getAttribute('data-id-caja');
            
            if (isNaN(peso) || peso <= 0) {
                mostrarAlerta('Debes ingresar un peso válido mayor a 0.', 'error');
                return;
            }
            
            const btnTarget = e ? e.target : event.target;
            btnTarget.disabled = true;
            btnTarget.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
            
            const form = document.getElementById('formVendedor');
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="accion_especial" value="abrir_caja">`);
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="id_caja" value="${idCaja}">`);
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="peso" value="${peso}">`);
            
            form.submit();
        }
                
        function actualizarNumeracionYNombres() {
            const items = document.querySelectorAll('.product-item');
            items.forEach((item, index) => {
                const titulo = item.querySelector('.product-number');
                if (titulo) titulo.textContent = `Producto #${index + 1}`;

                const inputSearch = item.querySelector('.producto-search');
                const inputHidden = item.querySelector('.producto-id-hidden');
                const inputKilos = item.querySelector('.input-kilos');
                const inputPiezas = item.querySelector('.input-piezas');

                if (inputSearch) inputSearch.name = `productos[${index}][nombre_producto]`;
                if (inputHidden) inputHidden.name = `productos[${index}][id_producto]`;
                if (inputKilos) inputKilos.name = `productos[${index}][kilos]`;
                if (inputPiezas) inputPiezas.name = `productos[${index}][piezas]`;
            });
        }

        document.getElementById('btn-add-product').addEventListener('click', function() {
            const container = document.getElementById('products-container');
            const items = container.querySelectorAll('.product-item');
            
            const ultimoItem = items[items.length - 1];
            const ultimoId = ultimoItem.querySelector('.producto-id-hidden').value;
            
            if (ultimoId === '') {
                mostrarAlerta('Por favor, selecciona un producto en la fila actual antes de agregar uno nuevo.', 'error');
                return; 
            }
            const firstItem = items[0];
            const newItem = firstItem.cloneNode(true);

            newItem.classList.remove('removing');
            newItem.querySelector('.producto-search').value = '';
            newItem.querySelector('.producto-id-hidden').value = '';
            
            const nKilos = newItem.querySelector('.input-kilos');
            const nPiezas = newItem.querySelector('.input-piezas');
            
            if (nKilos) {
                nKilos.value = '';
                nKilos.disabled = false;
                nKilos.placeholder = '0.00';
            }
            
            if (nPiezas) {
                nPiezas.value = '';
                nPiezas.disabled = false;
                nPiezas.placeholder = '0';
            }
            
            const nBotonCaja = newItem.querySelector('.contenedor-abrir-caja');
            if (nBotonCaja) {
                nBotonCaja.style.display = 'none';
            }

            container.appendChild(newItem);
            actualizarNumeracionYNombres();
        });

        function removeProduct(btn) {
            const items = document.querySelectorAll('.product-item');
            if (items.length > 1) {
                const itemToRemove = btn.closest('.product-item');
                
                itemToRemove.classList.add('removing');

                setTimeout(() => {
                    itemToRemove.remove();
                    actualizarNumeracionYNombres();
                    calcularTicket();
                }, 300);

            } else {
                mostrarAlerta('Debe haber al menos un producto en la nota.', 'error');
            }
        }

        // FUNCIÓN DE SEGURIDAD PARA EDITAR NOTAS SIN PERDER EL PROGRESO ACTUAL
       function confirmarEditarNota(event, urlEditar) {
            event.preventDefault(); // Evitamos que abra el enlace de inmediato
            
            const inputCliente = document.getElementById('cliente').value.trim();
            const primerProductoInput = document.querySelector('.product-item .producto-search');
            const tieneProducto = primerProductoInput && primerProductoInput.value.trim() !== '';

            // Verificamos si hay información activa en el formulario actual
            if (inputCliente !== '' || tieneProducto) {
                // USAMOS EL MODAL PERSONALIZADO
                mostrarAlerta(
                    "Tienes una nota activa en el formulario actual que no has guardado.\n\n¿Deseas guardarla automáticamente en espera antes de abrir la otra nota?",
                    'confirmacion',
                    function(acepta) {
                        if (acepta) {
                            const form = document.getElementById('formVendedor');
                            
                            const inputAccion = document.createElement('input');
                            inputAccion.type = 'hidden';
                            inputAccion.name = 'accion_boton';
                            inputAccion.value = 'guardar_espera';
                            form.appendChild(inputAccion);

                            const inputRedireccion = document.createElement('input');
                            inputRedireccion.type = 'hidden';
                            inputRedireccion.name = 'redirigir_a';
                            inputRedireccion.value = urlEditar;
                            form.appendChild(inputRedireccion);

                            form.submit();
                        } else {
                            window.location.href = urlEditar;
                        }
                    }
                );
            } else {
                window.location.href = urlEditar;
            }
        }


        // FUNCIONES PARA MOSTRAR ALERTAS BONITAS EN MODAL
        function mostrarAlerta(mensaje, tipo = 'error', callbackAceptar = null) {
            const modal = document.getElementById('modal-alerta-global');
            const mensajeEl = document.getElementById('modal-alerta-mensaje');
            const tituloEl = document.getElementById('modal-alert-title');
            const iconEl = document.getElementById('modal-alert-icon-container');
            const footerEl = document.getElementById('modal-alert-footer-buttons');

            mensajeEl.textContent = mensaje;
            window._callbackAlertaAceptar = callbackAceptar;

            if (tipo === 'error') {
                tituloEl.textContent = 'Atención';
                iconEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color: #e53e3e;"></i>';
            } else if (tipo === 'exito') {
                tituloEl.textContent = '¡Éxito!';
                iconEl.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #38a169;"></i>';
            } else if (tipo === 'confirmacion') {
                tituloEl.textContent = 'Confirmación';
                iconEl.innerHTML = '<i class="fa-solid fa-circle-question" style="color: #d97706;"></i>';
                
                // Si es confirmación, cambiamos los botones para tener Sí / No
                footerEl.innerHTML = `
                    <button type="button" class="btn-secondary-sm" style="flex:1;" onclick="cerrarAlertaGlobal(false)">Cancelar</button>
                    <button type="button" class="btn-primary" style="flex:1; background-color: #d97706;" onclick="cerrarAlertaGlobal(true)">Sí, continuar</button>
                `;
                modal.style.display = 'flex';
                return;
            }

            // Botón estándar de aceptar
            footerEl.innerHTML = `<button type="button" class="btn-primary" style="width: 100%; padding: 10px;" onclick="cerrarAlertaGlobal(true)">Aceptar</button>`;
            modal.style.display = 'flex';
        }

        function cerrarAlertaGlobal(resultado) {
            const modal = document.getElementById('modal-alerta-global');
            modal.style.display = 'none';

            if (window._callbackAlertaAceptar && typeof window._callbackAlertaAceptar === 'function') {
                window._callbackAlertaAceptar(resultado);
                window._callbackAlertaAceptar = null;
            }
        }

        function calcularTicket() {
            const items = document.querySelectorAll('.product-item');
            const ticketContainer = document.getElementById('ticket-items');
            const totalContainer = document.getElementById('ticket-total');
            const options = document.querySelectorAll('#lista-productos option');
            
            let htmlTicket = '';
            let granTotal = 0;

            items.forEach(row => {
                const inputSearch = row.querySelector('.producto-search').value.trim().toLowerCase();
                const inputKilos = parseFloat(row.querySelector('.input-kilos').value) || 0;
                const inputPiezas = parseInt(row.querySelector('.input-piezas').value) || 0;
                
                if (inputSearch !== '') {
                    // Extraer el precio del Datalist
                    let precio = 0;
                    let nombreReal = inputSearch;
                    
                    for(let opt of options) {
                        if (opt.value.trim().toLowerCase() === inputSearch) {
                            precio = parseFloat(opt.getAttribute('data-precio')) || 0;
                            nombreReal = opt.value;
                            break;
                        }
                    }

                    const esMazo = inputSearch.includes('mazo');
                    
                    // Condición: Mostrar el producto si ya escribieron Kilos o Piezas
                    const tieneCantidad = esMazo ? (inputPiezas > 0) : (inputKilos > 0 || inputPiezas > 0);

                    if (tieneCantidad) {
                        if (precio <= 0) {
                            // ESCENARIO A: PRODUCTO SIN PRECIO EN BD
                            const cantTexto = esMazo ? `${inputPiezas} pzas` : `${inputKilos.toFixed(2)} kg`;
                            htmlTicket += `
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #edf2f7; padding-bottom: 4px;">
                                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;">${nombreReal}</span>
                                    <span style="color: #e53e3e; font-size: 0.8rem; text-align: right; font-weight: bold;">
                                        ${cantTexto} <i class="fa-solid fa-circle-exclamation"></i> Sin precio
                                    </span>
                                </div>
                            `;
                        } else {
                            // ESCENARIO B: PRODUCTO CON PRECIO NORMAL
                            let subtotal = 0;
                            let textoCalculo = '';

                            if (esMazo) {
                                subtotal = inputPiezas * precio;
                                textoCalculo = `${inputPiezas} pzas x $${precio.toFixed(2)}`;
                            } else {
                                subtotal = inputKilos * precio;
                                textoCalculo = `${inputKilos.toFixed(2)} kg x $${precio.toFixed(2)}`;
                            }

                            granTotal += subtotal;

                            htmlTicket += `
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #edf2f7; padding-bottom: 4px;">
                                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500;">${nombreReal}</span>
                                    <span style="color: #718096; font-size: 0.8rem; width: 120px; text-align: right; padding-right: 10px;">${textoCalculo}</span>
                                    <strong style="width: 80px; text-align: right; color: #2d3748;">$${subtotal.toFixed(2)}</strong>
                                </div>
                            `;
                        }
                    }
                }
            });

            if (htmlTicket === '') {
                htmlTicket = '<span style="color: #a0aec0; font-style: italic;">Agrega pesos o piezas para calcular...</span>';
            }

            ticketContainer.innerHTML = htmlTicket;
            totalContainer.textContent = `$${granTotal.toFixed(2)}`;
        }
