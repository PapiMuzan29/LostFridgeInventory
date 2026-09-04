document.addEventListener('DOMContentLoaded', () => {
    let ultimoIdMensaje = 0;
    let chatAbierto = false;
    let idDestinoActual = null;
    let todosLosUsuariosGlobales = [];
    let chatsActivosGuardados = [];

    const CONTROLLER_URL = '../../Controllers/ChatController.php';

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

    const viewContacts = document.getElementById('view-contacts');
    const viewConversation = document.getElementById('view-conversation');

    // --- Lógica de Arrastre (Drag and Drop) ---
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

            if (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5) {
                hasMoved = true;
                if (e.cancelable) e.preventDefault();
            }

            let newLeft = initialLeft + deltaX;
            let newTop = initialTop + deltaY;

            const maxLeft = window.innerWidth - btn.offsetWidth;
            const maxTop = window.innerHeight - btn.offsetHeight;

            newLeft = Math.max(0, Math.min(newLeft, maxLeft));
            newTop = Math.max(0, Math.min(newTop, maxTop));

            btn.style.left = `${newLeft}px`;
            btn.style.top = `${newTop}px`;
            btn.style.bottom = 'auto';
            btn.style.right = 'auto';
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

    hacerBotonArrastrable(btnToggleChat);

    // --- Lógica del Chat ---
    function verificarNotificacionesGlobales() {
        fetch(`${CONTROLLER_URL}?action=obtenerTotalNoLeidos`)
            .then(res => res.json())
            .then(data => {
                if (data.success) actualizarBadgeGlobal(data.total);
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
            usersListContainer.innerHTML = '<div class="chat-placeholder" style="padding:20px;">No se encontraron contactos</div>';
            return;
        }

        lista.forEach(u => {
            const div = document.createElement('div');
            div.className = 'chat-user-item';
            div.dataset.id = u.idCuenta;

            const badgeHTML = (u.noLeidos && u.noLeidos > 0) 
                ? `<span class="chat-user-badge">${u.noLeidos}</span>` 
                : '';

            div.innerHTML = `
                <div class="chat-user-info">
                    <i class="fa-solid fa-circle-user"></i>
                    <span>${u.apodoUsuario}</span>
                </div>
                ${badgeHTML}
            `;

            div.addEventListener('click', () => abrirConversacion(u));
            usersListContainer.appendChild(div);
        });
    }

    function abrirConversacion(usuario) {
        idDestinoActual = usuario.idCuenta;
        ultimoIdMensaje = 0;
        chatContainer.innerHTML = '';
        chatHeaderTitle.textContent = usuario.apodoUsuario;

        viewContacts.style.display = 'none';
        viewConversation.style.display = 'flex';
        btnBackToUsers.style.display = 'inline-block';

        inputMensaje.disabled = false;
        btnSend.disabled = false;

        marcarMensajesComoLeidos();
        cargarMensajes();
    }

    function regresarAContactos() {
        idDestinoActual = null;
        viewConversation.style.display = 'none';
        viewContacts.style.display = 'flex';
        btnBackToUsers.style.display = 'none';
        chatHeaderTitle.textContent = 'Contactos';

        if (searchInput) searchInput.value = '';
        cargarUsuarios();
    }

    if (btnBackToUsers) btnBackToUsers.addEventListener('click', regresarAContactos);

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const busqueda = e.target.value.toLowerCase().trim();
            if (busqueda === '') {
                if (sidebarTitle) sidebarTitle.textContent = "Chats Activos";
                renderizarListaUsuarios(chatsActivosGuardados);
            } else {
                if (sidebarTitle) sidebarTitle.textContent = "Resultados";
                const filtrados = todosLosUsuariosGlobales.filter(u => 
                    u.apodoUsuario.toLowerCase().includes(busqueda) || 
                    (u.nombreUsuario && u.nombreUsuario.toLowerCase().includes(busqueda))
                );
                renderizarListaUsuarios(filtrados);
            }
        });
    }

    function cargarMensajes() {
        if (!idDestinoActual) return;

        fetch(`${CONTROLLER_URL}?action=obtenerMensajes&ultimo_id=${ultimoIdMensaje}&idDestino=${idDestinoActual}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (chatAbierto && data.noLeidos > 0) {
                        marcarMensajesComoLeidos();
                    }

                    if (Array.isArray(data.mensajes) && data.mensajes.length > 0) {
                        data.mensajes.forEach(msg => {
                            renderizarMensaje(msg, data.idCuentaActual);
                            if (parseInt(msg.idChatMensaje) > ultimoIdMensaje) {
                                ultimoIdMensaje = parseInt(msg.idChatMensaje);
                            }
                        });
                        scroolAlFondo();
                    }
                }
            })
            .catch(err => console.error('Error al obtener mensajes:', err));
    }

    function marcarMensajesComoLeidos() {
        if (!idDestinoActual) return;
        const formData = new FormData();
        formData.append('idDestino', idDestinoActual);

        fetch(`${CONTROLLER_URL}?action=marcarComoLeidos`, { method: 'POST', body: formData })
            .then(() => verificarNotificacionesGlobales())
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

    function toggleChatModal(abrir) {
        chatAbierto = typeof abrir === 'boolean' ? abrir : !chatAbierto;
        chatModal.style.display = chatAbierto ? 'flex' : 'none';
        if (chatAbierto) {
            cargarTodosLosUsuarios();
            regresarAContactos();
        }
    }

    if (btnToggleChat) btnToggleChat.addEventListener('click', () => toggleChatModal());
    if (btnCloseChat) btnCloseChat.addEventListener('click', () => toggleChatModal(false));

    if (formChat) {
        formChat.addEventListener('submit', (e) => {
            e.preventDefault();
            const mensaje = inputMensaje.value.trim();
            if (!mensaje || !idDestinoActual) return;

            const formData = new FormData();
            formData.append('action', 'enviarMensaje');
            formData.append('mensaje', mensaje);
            formData.append('idDestino', idDestinoActual);

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

    function scroolAlFondo() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function formatearHora(fechaStr) {
        if (!fechaStr) return '';
        const fecha = new Date(fechaStr);
        return isNaN(fecha.getTime()) ? fechaStr : fecha.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    verificarNotificacionesGlobales();
    setInterval(() => {
        verificarNotificacionesGlobales();
        if (chatAbierto) {
            if (!idDestinoActual) cargarUsuarios();
            else cargarMensajes();
        }
    }, 3000);
});