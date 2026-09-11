document.addEventListener('DOMContentLoaded', () => {
    let ultimoIdMensaje = 0;
    let chatAbierto = false;
    let idDestinoActual = null;
    let esChatGrupal = false;
    let esPrimeraCargaChat = true;
    let todosLosUsuariosGlobales = [];
    let chatsActivosGuardados = [];
    let seleccionadosGrupo = new Set();
    
    let ultimoTotalNoLeidos = null; 

    const CONTROLLER_URL = '../../Controllers/ChatController.php';

    // Elementos del DOM
    const chatContainer = document.getElementById('chat-messages');
    const badgeNotificacion = document.getElementById('chat-badge');
    const inputMensaje = document.getElementById('chat-input');
    const btnSend = document.getElementById('btn-send-chat');
    const formChat = document.getElementById('chat-form');
    const chatModal = document.getElementById('chat-modal');
    const btnToggleChat = document.getElementById('btn-toggle-chat');
    const btnCloseChat = document.getElementById('btn-close-chat');
    const btnBackToUsers = document.getElementById('btn-back-to-users');
    const usersListContainer = document.getElementById('chat-users-list');
    const chatHeaderTitle = document.getElementById('chat-header-title');
    const searchInput = document.getElementById('chat-search-input');
    const sidebarTitle = document.getElementById('chat-sidebar-title');

    // Vistas
    const viewContacts = document.getElementById('view-contacts');
    const viewConversation = document.getElementById('view-conversation');
    const viewCreateGroup = document.getElementById('view-create-group');

    // Elementos de Grupo
    const btnOpenCreateGroup = document.getElementById('btn-open-create-group');
    const btnCancelGroup = document.getElementById('btn-cancel-group');
    const formGroup = document.getElementById('group-form');
    const groupNameInput = document.getElementById('group-name-input');
    const groupMembersList = document.getElementById('group-members-list');
    const groupUserSearch = document.getElementById('group-user-search');

    // --- SISTEMA DE NOTIFICACIÓN SONORA ---
    const RUTA_SONIDO_WAV = '../../SRC/sounds/noti.wav'; 
    const sonidoNotificacion = new Audio(RUTA_SONIDO_WAV);
    let usuarioInteractuo = false;

    function desbloquearAudio() {
        usuarioInteractuo = true;
    }

    ['click', 'keydown', 'touchstart'].forEach(evento => {
        window.addEventListener(evento, desbloquearAudio, { once: true, capture: true });
    });

    function reproducirSonidoNotificacion() {
        if (!usuarioInteractuo) return;

        try {
            sonidoNotificacion.currentTime = 0;
            sonidoNotificacion.volume = 0.5;

            const promesaPlay = sonidoNotificacion.play();
            if (promesaPlay !== undefined) {
                promesaPlay.catch(error => {
                    if (error.name !== 'NotAllowedError') {
                        console.warn("Error al reproducir audio:", error);
                    }
                });
            }
        } catch (e) {
            console.warn("Error con la notificación de audio:", e);
        }
    }

    // --- POSICIONAMIENTO Y ARRASTRE ---
    function inicializarPosicionBoton() {
        if (!btnToggleChat) return;
        const rect = btnToggleChat.getBoundingClientRect();
        btnToggleChat.style.position = 'fixed';
        btnToggleChat.style.left = `${rect.left}px`;
        btnToggleChat.style.top = `${rect.top}px`;
        btnToggleChat.style.bottom = 'auto';
        btnToggleChat.style.right = 'auto';
    }

    function posicionarVentanaOptima() {
        if (!btnToggleChat || !chatModal) return;

        const btnRect = btnToggleChat.getBoundingClientRect();
        const modalWidth = chatModal.offsetWidth || 380;
        const modalHeight = chatModal.offsetHeight || 520;

        const gap = 16;
        const margin = 12;
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        let left, top;

        const cabeIzquierda = btnRect.left - modalWidth - gap >= margin;
        const cabeDerecha   = btnRect.right + modalWidth + gap <= vw - margin;
        const cabeArriba    = btnRect.top - modalHeight - gap >= margin;
        const cabeAbajo     = btnRect.bottom + modalHeight + gap <= vh - margin;

        if (cabeIzquierda) {
            left = btnRect.left - modalWidth - gap;
            top = btnRect.top;
        } else if (cabeDerecha) {
            left = btnRect.right + gap;
            top = btnRect.top;
        } else if (cabeArriba) {
            top = btnRect.top - modalHeight - gap;
            left = btnRect.left + (btnRect.width / 2) - (modalWidth / 2);
        } else if (cabeAbajo) {
            top = btnRect.bottom + gap;
            left = btnRect.left + (btnRect.width / 2) - (modalWidth / 2);
        } else {
            left = (btnRect.left > vw / 2) 
                ? Math.max(margin, btnRect.left - modalWidth - gap) 
                : Math.min(vw - modalWidth - margin, btnRect.right + gap);
            top = btnRect.top;
        }

        left = Math.max(margin, Math.min(left, vw - modalWidth - margin));
        top  = Math.max(margin, Math.min(top, vh - modalHeight - margin));

        chatModal.style.position = 'fixed';
        chatModal.style.left = `${left}px`;
        chatModal.style.top = `${top}px`;
    }

    function hacerBotonArrastrable(btn) {
        if (!btn) return;

        let isDragging = false;
        let startX, startY, initialLeft, initialTop;
        let hasMoved = false;

        const onStart = (e) => {
            isDragging = true;
            hasMoved = false;

            const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

            startX = clientX;
            startY = clientY;

            const rect = btn.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onEnd);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('touchend', onEnd);
        };

        const onMove = (e) => {
            if (!isDragging) return;

            const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

            const deltaX = clientX - startX;
            const deltaY = clientY - startY;

            if (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4) {
                hasMoved = true;
                if (e.cancelable) e.preventDefault();
            }

            if (hasMoved) {
                let newLeft = initialLeft + deltaX;
                let newTop = initialTop + deltaY;

                const maxLeft = window.innerWidth - btn.offsetWidth;
                const maxTop = window.innerHeight - btn.offsetHeight;

                newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                newTop = Math.max(0, Math.min(newTop, maxTop));

                btn.style.position = 'fixed';
                btn.style.left = `${newLeft}px`;
                btn.style.top = `${newTop}px`;
                btn.style.bottom = 'auto';
                btn.style.right = 'auto';

                if (chatAbierto) {
                    posicionarVentanaOptima();
                }
            }
        };

        const onEnd = () => {
            isDragging = false;
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onEnd);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('touchend', onEnd);
        };

        btn.addEventListener('mousedown', onStart);
        btn.addEventListener('touchstart', onStart, { passive: false });

        btn.addEventListener('click', (e) => {
            if (hasMoved) {
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        }, true);
    }

    inicializarPosicionBoton();
    hacerBotonArrastrable(btnToggleChat);

    // --- LÓGICA DE CHAT Y NOTIFICACIONES ---
    function verificarNotificacionesGlobales() {
        fetch(`${CONTROLLER_URL}?action=obtenerTotalNoLeidos`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const nuevoTotal = parseInt(data.total, 10) || 0;

                    if (ultimoTotalNoLeidos === null) {
                        ultimoTotalNoLeidos = nuevoTotal;
                    } else {
                        if (nuevoTotal > ultimoTotalNoLeidos) {
                            reproducirSonidoNotificacion();
                        }
                        ultimoTotalNoLeidos = nuevoTotal;
                    }

                    actualizarBadgeGlobal(nuevoTotal);
                }
            })
            .catch(err => console.error('Error al consultar notificaciones:', err));
    }

    function actualizarBadgeGlobal(total) {
        if (!badgeNotificacion) return;
        if (total > 0) {
            badgeNotificacion.textContent = total > 99 ? '99+' : total;
            badgeNotificacion.style.display = 'inline-block';
        } else {
            badgeNotificacion.style.display = 'none';
        }
    }

    function cargarUsuarios() {
        if (searchInput && searchInput.value.trim() !== '') return;

        fetch(`${CONTROLLER_URL}?action=obtenerUsuarios`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.usuarios)) {
                    chatsActivosGuardados = data.usuarios;
                    if (sidebarTitle) sidebarTitle.textContent = "Chats Activos";
                    renderizarListaUsuarios(chatsActivosGuardados);
                }
            })
            .catch(err => console.error('Error al cargar contactos:', err));
    }

    function cargarTodosLosUsuarios() {
        fetch(`${CONTROLLER_URL}?action=obtenerTodosUsuarios`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.usuarios)) {
                    todosLosUsuariosGlobales = data.usuarios;
                }
            })
            .catch(err => console.error('Error al cargar usuarios globales:', err));
    }

    function renderizarListaUsuarios(lista) {
        usersListContainer.innerHTML = '';
        if (lista.length === 0) {
            usersListContainer.innerHTML = '<div class="chat-placeholder" style="padding:20px;">No se encontraron contactos ni grupos</div>';
            return;
        }

        lista.forEach(u => {
            const div = document.createElement('div');
            div.className = 'chat-user-item';
            div.dataset.id = u.idCuenta;

            const esGrupoItem = u.tipo === 'grupo';
            const icono = esGrupoItem ? 'fa-users' : 'fa-circle-user';
            const badgeHTML = (u.noLeidos && u.noLeidos > 0) 
                ? `<span class="chat-user-badge">${u.noLeidos}</span>` 
                : '';

            div.innerHTML = `
                <div class="chat-user-info">
                    <i class="fa-solid ${icono}"></i>
                    <span>${u.apodoUsuario}</span>
                </div>
                ${badgeHTML}
            `;

            div.addEventListener('click', () => abrirConversacion(u, esGrupoItem));
            usersListContainer.appendChild(div);
        });
    }

    function abrirConversacion(entidad, esGrupo = false) {
        idDestinoActual = entidad.idCuenta;
        esChatGrupal = esGrupo;
        ultimoIdMensaje = 0;
        esPrimeraCargaChat = true;
        chatContainer.innerHTML = '';

        const iconoHeader = esGrupo ? '👥 ' : '';
        chatHeaderTitle.textContent = `${iconoHeader}${entidad.apodoUsuario}`;

        viewContacts.style.display = 'none';
        viewCreateGroup.style.display = 'none';
        viewConversation.style.display = 'flex';
        btnBackToUsers.style.display = 'inline-block';

        inputMensaje.disabled = false;
        btnSend.disabled = false;

        if (!esChatGrupal) {
            marcarMensajesComoLeidos();
        }
        cargarMensajes();
    }

    function regresarAContactos() {
        idDestinoActual = null;
        esChatGrupal = false;
        viewConversation.style.display = 'none';
        viewCreateGroup.style.display = 'none';
        viewContacts.style.display = 'flex';
        btnBackToUsers.style.display = 'none';
        chatHeaderTitle.textContent = 'Contactos';

        if (searchInput) searchInput.value = '';
        cargarUsuarios();
    }

    if (btnBackToUsers) btnBackToUsers.addEventListener('click', regresarAContactos);

    // --- BÚSQUEDA CORREGIDA (Usuarios y Grupos) ---
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const busqueda = e.target.value.toLowerCase().trim();
            if (busqueda === '') {
                if (sidebarTitle) sidebarTitle.textContent = "Chats Activos";
                renderizarListaUsuarios(chatsActivosGuardados);
            } else {
                if (sidebarTitle) sidebarTitle.textContent = "Resultados";
                
                // Combina los chats activos (contactos + grupos) y todos los usuarios globales
                const mapaEntidades = new Map();

                chatsActivosGuardados.forEach(c => mapaEntidades.set(`${c.tipo}_${c.idCuenta}`, c));
                todosLosUsuariosGlobales.forEach(u => {
                    const key = `usuario_${u.idCuenta}`;
                    if (!mapaEntidades.has(key)) {
                        mapaEntidades.set(key, { ...u, tipo: 'usuario' });
                    }
                });

                const listaUnificada = Array.from(mapaEntidades.values());

                const filtrados = listaUnificada.filter(item => {
                    const apodoMatch = item.apodoUsuario && item.apodoUsuario.toLowerCase().includes(busqueda);
                    const nombreMatch = item.nombreUsuario && item.nombreUsuario.toLowerCase().includes(busqueda);
                    return apodoMatch || nombreMatch;
                });

                renderizarListaUsuarios(filtrados);
            }
        });
    }

    function cargarMensajes() {
        if (!idDestinoActual) return;

        const url = `${CONTROLLER_URL}?action=obtenerMensajes&ultimo_id=${ultimoIdMensaje}&idDestino=${idDestinoActual}&esGrupo=${esChatGrupal}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (chatAbierto && data.noLeidos > 0 && !esChatGrupal) {
                        marcarMensajesComoLeidos();
                    }

                    if (Array.isArray(data.mensajes) && data.mensajes.length > 0) {
                        let hayMensajesAjenosNuevos = false;

                        data.mensajes.forEach(msg => {
                            renderizarMensaje(msg, data.idCuentaActual);

                            if (String(msg.idCuenta) !== String(data.idCuentaActual) && parseInt(msg.idChatMensaje, 10) > ultimoIdMensaje) {
                                if (!esPrimeraCargaChat) { 
                                    hayMensajesAjenosNuevos = true;
                                }
                            }

                            if (parseInt(msg.idChatMensaje, 10) > ultimoIdMensaje) {
                                ultimoIdMensaje = parseInt(msg.idChatMensaje, 10);
                            }
                        });

                        esPrimeraCargaChat = false;

                        if (hayMensajesAjenosNuevos) {
                            reproducirSonidoNotificacion();
                        }

                        scroolAlFondo();
                    } else {
                        esPrimeraCargaChat = false;
                    }
                }
            })
            .catch(err => console.error('Error al obtener mensajes:', err));
    }

    function marcarMensajesComoLeidos() {
        if (!idDestinoActual || esChatGrupal) return;
        const formData = new FormData();
        formData.append('idDestino', idDestinoActual);

        fetch(`${CONTROLLER_URL}?action=marcarComoLeidos`, { method: 'POST', body: formData })
            .then(() => {
                fetch(`${CONTROLLER_URL}?action=obtenerTotalNoLeidos`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const nuevoTotal = parseInt(data.total, 10) || 0;
                            ultimoTotalNoLeidos = nuevoTotal;
                            actualizarBadgeGlobal(nuevoTotal);
                        }
                    });
            })
            .catch(err => console.error('Error al marcar como leídos:', err));
    }

    function renderizarMensaje(msg, idCuentaActual) {
        const esMio = String(msg.idCuenta) === String(idCuentaActual);
        const divMsg = document.createElement('div');
        divMsg.classList.add('chat-message', esMio ? 'message-own' : 'message-other');

        divMsg.innerHTML = `
            <div class="message-info">
                <span class="message-author">${msg.apodoUsuario}</span>
                <span class="message-time">${formatearHora(msg.fechaRegistro)}</span>
            </div>
            <div class="message-body">${msg.mensaje}</div>
        `;
        chatContainer.appendChild(divMsg);
    }

    if (formChat) {
        formChat.addEventListener('submit', (e) => {
            e.preventDefault();
            const mensaje = inputMensaje.value.trim();
            if (!mensaje || !idDestinoActual) return;

            const formData = new FormData();
            formData.append('action', 'enviarMensaje');
            formData.append('mensaje', mensaje);
            formData.append('idDestino', idDestinoActual);
            formData.append('esGrupo', esChatGrupal);

            fetch(CONTROLLER_URL, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        inputMensaje.value = '';
                        cargarMensajes();
                    }
                });
        });
    }

    // --- MANEJO DE VISTA DE GRUPOS ---
    function abrirVistaCrearGrupo() {
        viewContacts.style.display = 'none';
        viewConversation.style.display = 'none';
        viewCreateGroup.style.display = 'flex';
        btnBackToUsers.style.display = 'inline-block';
        chatHeaderTitle.textContent = 'Crear Grupo';

        seleccionadosGrupo.clear();
        if (groupUserSearch) groupUserSearch.value = '';
        renderizarCheckboxesUsuarios('');
    }

    function renderizarCheckboxesUsuarios(filtro = '') {
        groupMembersList.innerHTML = '';

        const listaFiltrada = todosLosUsuariosGlobales.filter(u => {
            const query = filtro.toLowerCase().trim();
            if (!query) return true;
            return (u.apodoUsuario && u.apodoUsuario.toLowerCase().includes(query)) ||
                   (u.nombreUsuario && u.nombreUsuario.toLowerCase().includes(query));
        });

        if (listaFiltrada.length === 0) {
            groupMembersList.innerHTML = '<div style="font-size:14px; color:#888; padding: 16px; text-align: center;">No se encontraron usuarios</div>';
            return;
        }

        listaFiltrada.forEach(u => {
            const estaMarcado = seleccionadosGrupo.has(parseInt(u.idCuenta, 10));
            const label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; gap: 14px; padding: 12px 10px; border-bottom: 1px solid #f0f0f0; cursor: pointer; font-size: 15px; color: #222; font-weight: 500; border-radius: 6px; transition: background 0.2s;';
            
            label.innerHTML = `
                <input type="checkbox" value="${u.idCuenta}" ${estaMarcado ? 'checked' : ''} style="width: 20px; height: 20px; cursor: pointer; accent-color: #28a745;">
                <div style="display:flex; flex-direction:column;">
                    <span style="font-size: 15px; font-weight: 600;">${u.apodoUsuario}</span>
                    ${u.nombreUsuario ? `<span style="font-size: 12px; color: #777;">${u.nombreUsuario}</span>` : ''}
                </div>
            `;

            const chk = label.querySelector('input');
            chk.addEventListener('change', (e) => {
                const id = parseInt(e.target.value, 10);
                if (e.target.checked) {
                    seleccionadosGrupo.add(id);
                } else {
                    seleccionadosGrupo.delete(id);
                }
            });

            groupMembersList.appendChild(label);
        });
    }

    if (groupUserSearch) {
        groupUserSearch.addEventListener('input', (e) => {
            renderizarCheckboxesUsuarios(e.target.value);
        });
    }

    if (btnOpenCreateGroup) btnOpenCreateGroup.addEventListener('click', abrirVistaCrearGrupo);
    if (btnCancelGroup) btnCancelGroup.addEventListener('click', regresarAContactos);

    if (formGroup) {
        formGroup.addEventListener('submit', (e) => {
            e.preventDefault();
            const nombreGrupo = groupNameInput.value.trim();
            const miembrosSeleccionados = Array.from(seleccionadosGrupo);

            if (!nombreGrupo) {
                alert('Por favor ingresa un nombre para el grupo.');
                return;
            }

            if (miembrosSeleccionados.length === 0) {
                alert('Debes seleccionar al menos un integrante para el grupo.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'crearGrupo');
            formData.append('nombreGrupo', nombreGrupo);
            formData.append('miembros', JSON.stringify(miembrosSeleccionados));

            fetch(CONTROLLER_URL, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        groupNameInput.value = '';
                        seleccionadosGrupo.clear();
                        regresarAContactos();
                        abrirConversacion({ idCuenta: data.idGrupo, apodoUsuario: nombreGrupo }, true);
                    } else {
                        alert(data.message || 'Error al crear el grupo.');
                    }
                })
                .catch(err => console.error('Error al crear el grupo:', err));
        });
    }

    // --- GENERALES Y MODAL ---
    function toggleChatModal(abrir) {
        chatAbierto = typeof abrir === 'boolean' ? abrir : !chatAbierto;
        
        if (chatAbierto) {
            chatModal.classList.add('show');
            posicionarVentanaOptima();
            
            setTimeout(() => {
                chatModal.classList.add('active');
            }, 10);

            cargarTodosLosUsuarios();
            regresarAContactos();
        } else {
            chatModal.classList.remove('active');
            
            setTimeout(() => {
                if (!chatAbierto) {
                    chatModal.classList.remove('show');
                }
            }, 250);
        }
    }

    if (btnToggleChat) btnToggleChat.addEventListener('click', () => toggleChatModal());
    if (btnCloseChat) btnCloseChat.addEventListener('click', () => toggleChatModal(false));

    function scroolAlFondo() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function formatearHora(fechaStr) {
        if (!fechaStr) return '';
        const fecha = new Date(fechaStr);
        return isNaN(fecha.getTime()) ? fechaStr : fecha.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    window.addEventListener('resize', () => {
        if (chatAbierto) posicionarVentanaOptima();
    });

    verificarNotificacionesGlobales();
    setInterval(() => {
        verificarNotificacionesGlobales();
        if (chatAbierto) {
            if (!idDestinoActual) cargarUsuarios();
            else cargarMensajes();
        }
    }, 3000);
});