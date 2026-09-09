<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Meta tag clave para desactivar el auto-zoom en móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat Privado y Grupal</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="CSS/chat.css">
</head>
<body>

    <!-- Botón Flotante para Abrir Chat -->
    <button id="btn-toggle-chat" class="chat-float-btn" type="button" aria-label="Abrir Chat Privado">
        <i class="fa-solid fa-comments"></i>
        <span id="chat-badge" class="chat-badge" style="display: none;">0</span>
    </button>

    <!-- Contenedor Principal del Chat -->
    <div id="chat-modal" class="chat-modal-container" style="display: none;">
        <!-- Encabezado -->
        <div class="chat-modal-header">
            <div class="chat-modal-title">
                <button type="button" id="btn-back-to-users" class="chat-back-btn" style="display: none;" title="Regresar">
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
            <!-- Vista 1: Lista de Contactos y Grupos -->
            <div id="view-contacts" class="chat-view-section active-view">
                <div class="chat-actions-bar" style="padding: 10px; border-bottom: 1px solid #eee;">
                    <button type="button" id="btn-open-create-group" class="btn-create-group" style="width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                        <i class="fa-solid fa-users"></i> Crear Nuevo Grupo
                    </button>
                </div>
                <div class="chat-search-box">
                    <i class="fa-solid fa-magnifying-glass chat-search-icon"></i>
                    <input type="text" id="chat-search-input" placeholder="Buscar o empezar chat..." autocomplete="off">
                </div>
                <div class="chat-sidebar-header" id="chat-sidebar-title">Chats Activos</div>
                <div id="chat-users-list" class="chat-users-list">
                    <!-- Se cargan dinámicamente -->
                </div>
            </div>

            <!-- Vista 2: Conversación Activa -->
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

            <!-- Vista 3: Crear Grupo (Con Buscador Interno) -->
            <div id="view-create-group" class="chat-view-section" style="display: none; padding: 16px; flex-direction: column; height: 100%; box-sizing: border-box;">
                <form id="group-form" style="display: flex; flex-direction: column; height: 100%; justify-content: space-between;">
                    
                    <div style="display: flex; flex-direction: column; flex: 1; overflow: hidden; margin-bottom: 12px;">
                        <div style="margin-bottom: 14px;">
                            <label for="group-name-input" style="font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px; display: block;">Nombre del Grupo</label>
                            <input type="text" id="group-name-input" placeholder="Ej. Equipo de Proyecto" required style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; outline: none;" autocomplete="off">
                        </div>
                        
                        <span style="font-size: 14px; font-weight: 600; color: #333; margin-bottom: 8px; display: block;">Seleccionar Integrantes</span>
                        
                        <!-- Buscador dentro de Crear Grupo -->
                        <div class="chat-search-box" style="margin-bottom: 8px;">
                            <i class="fa-solid fa-magnifying-glass chat-search-icon"></i>
                            <input type="text" id="group-user-search" placeholder="Escribe para buscar un contacto..." autocomplete="off">
                        </div>

                        <div id="group-members-list" class="chat-users-list" style="flex: 1; border: 1px solid #e0e0e0; background: #fafafa; overflow-y: auto; padding: 8px 12px; border-radius: 6px;">
                            <!-- Se cargan dinámicamente -->
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; padding-top: 8px; border-top: 1px solid #eee;">
                        <button type="button" id="btn-cancel-group" style="flex: 1; padding: 10px; font-size: 14px; font-weight: 600; background-color: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Cancelar</button>
                        <button type="submit" id="btn-submit-group" style="flex: 1; padding: 10px; font-size: 14px; font-weight: 600; background-color: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer;">Crear</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="chat.js" defer></script>
</body>
</html>