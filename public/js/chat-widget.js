/**
 * Defensor Laboral IA - Chat widget
 * Consultas sobre Código de Trabajo y Contrato Colectivo SITRACABAÑA
 * API configurable: window.DEFENSOR_IA_API_URL o data-api-url en #defensor-chat-toggle
 */
(function () {
    const API_URL = (typeof window !== 'undefined' && window.DEFENSOR_IA_API_URL)
        || '/api/defensor/consulta';

    function $(sel, ctx = document) {
        return ctx.querySelector(sel);
    }

    function addMessage(container, text, isUser) {
        const div = document.createElement('div');
        div.className = 'defensor-msg ' + (isUser ? 'user' : 'bot');
        const content = document.createElement('div');
        content.className = 'defensor-content';
        content.textContent = text;
        div.appendChild(content);
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return div;
    }

    function addLoading(container) {
        const div = document.createElement('div');
        div.className = 'defensor-msg bot';
        div.innerHTML = '<div class="defensor-loading"><span></span><span></span><span></span></div>';
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return div;
    }

    function formatResponse(text) {
        return text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    }

    function init() {
        const toggleEl = $('#defensor-chat-toggle');
        const panel = $('#defensor-chat-panel');
        const closeBtn = $('#defensor-chat-close');
        const messages = $('#defensor-messages');
        const form = $('#defensor-chat-form');
        const input = $('#defensor-chat-input');
        const sendBtn = $('#defensor-chat-send');

        if (!toggleEl || !panel || !form) return;

        toggleEl.addEventListener('click', function () {
            panel.classList.toggle('open');
            if (panel.classList.contains('open') && messages.children.length === 0) {
                addMessage(messages, 'Hola. Soy el Defensor Laboral IA. Puedo responder tus dudas sobre el Código de Trabajo y el Contrato Colectivo de SITRACABAÑA (Ingenio La Cabaña). ¿En qué puedo ayudarte?', false);
            }
        });

        closeBtn.addEventListener('click', function () {
            panel.classList.remove('open');
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const pregunta = input.value.trim();
            if (!pregunta) return;

            addMessage(messages, pregunta, true);
            input.value = '';

            const loadingEl = addLoading(messages);
            sendBtn.disabled = true;

            const historial = [];
            const msgEls = messages.querySelectorAll('.defensor-msg');
            const maxHist = 6;
            for (let i = Math.max(0, msgEls.length - maxHist); i < msgEls.length; i++) {
                const el = msgEls[i];
                if (el.querySelector('.defensor-loading')) continue;
                const isUser = el.classList.contains('user');
                const content = el.querySelector('.defensor-content');
                const texto = content ? (content.textContent || content.innerText || '').trim() : '';
                if (texto) historial.push({ rol: isUser ? 'user' : 'bot', contenido: texto });
            }

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pregunta: pregunta, historial: historial })
                });
                const data = await res.json();

                loadingEl.remove();

                if (data.success && data.respuesta) {
                    const content = document.createElement('div');
                    content.className = 'defensor-content';
                    content.innerHTML = formatResponse(data.respuesta);
                    const msg = document.createElement('div');
                    msg.className = 'defensor-msg bot';
                    msg.appendChild(content);
                    messages.appendChild(msg);
                } else {
                    addMessage(messages, data.respuesta || data.message || 'No pude procesar tu consulta. Intenta de nuevo.', false);
                }
            } catch (err) {
                loadingEl.remove();
                addMessage(messages, 'No pude conectar con el asistente. Verifica tu conexión e intenta de nuevo.', false);
            }

            sendBtn.disabled = false;
            messages.scrollTop = messages.scrollHeight;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
