<!-- Defensor Laboral IA - Chat flotante -->
<button type="button" id="defensor-chat-toggle" aria-label="Abrir asistente Defensor Laboral">
    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
</button>
<div id="defensor-chat-panel">
    <div class="defensor-chat-header">
        <div>
            <h3>Defensor Laboral IA</h3>
            <p>Código de Trabajo y Contrato Colectivo SITRACABAÑA</p>
        </div>
        <button type="button" class="defensor-chat-close" id="defensor-chat-close" aria-label="Cerrar">×</button>
    </div>
    <div class="defensor-chat-messages" id="defensor-messages"></div>
    <div class="defensor-chat-input-wrap">
        <form id="defensor-chat-form">
            <input type="text" class="defensor-chat-input" id="defensor-chat-input" placeholder="Escribe tu consulta..." autocomplete="off" maxlength="500">
            <button type="submit" class="defensor-chat-send" id="defensor-chat-send" aria-label="Enviar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </form>
    </div>
</div>
