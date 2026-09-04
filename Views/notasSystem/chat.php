<!-- Módulo de Chat Privado (Móvil / Pantalla Única) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="CSS/chat.css">

<button id="btn-toggle-chat" class="chat-float-btn" type="button" aria-label="Abrir Chat Privado">
    <i class="fa-solid fa-comments"></i>
    <span id="chat-badge" class="chat-badge" style="display: none;">0</span>
</button>

<div id="chat-modal" class="chat-modal-container" style="display: none;">
    <!-- Encabezado -->
    <div class="chat-modal-header">
        <div class="chat-modal-title">
            <button type="button" id="btn-back-to-users" class="chat-back-btn" style="display: none;" title="Regresar a contactos">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <i class="fa-solid fa-comments" id="chat-header-icon"></i>
            <span id="chat-header-title">Contactos</span>
        </div>
        <button type="button" id="btn-close-chat" class="chat-close-btn" title="Cerrar chat">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="chat-layout-body">
        <!-- Vista 1: Lista de Contactos (Ancho Completo) -->
        <div id="view-contacts" class="chat-view-section active-view">
            <div class="chat-search-box">
                <i class="fa-solid fa-magnifying-glass chat-search-icon"></i>
                <input type="text" id="chat-search-input" placeholder="Buscar o empezar chat..." autocomplete="off">
            </div>
            <div class="chat-sidebar-header" id="chat-sidebar-title">Chats Activos</div>
            <div id="chat-users-list" class="chat-users-list">
                <!-- Se cargan dinámicamente -->
            </div>
        </div>

        <!-- Vista 2: Conversación Activa (Ancho Completo) -->
        <div id="view-conversation" class="chat-view-section" style="display: none;">
            <div id="chat-messages" class="chat-modal-body">
                <div class="chat-placeholder">Selecciona un contacto para chatear</div>
            </div>

            <form id="chat-form" class="chat-modal-footer">
                <input type="text" id="chat-input" placeholder="Escribe un mensaje..." autocomplete="off" maxlength="255" disabled required>
                <button type="submit" id="btn-send-chat" class="chat-send-btn" title="Enviar mensaje" disabled>
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="chat.js" defer></script>