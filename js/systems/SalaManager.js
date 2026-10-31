import { Sanitizer } from '../utils/Sanitizer.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('private-room-form');
    const nameInput = document.getElementById('private-room-name');
    const privacyInput = document.getElementById('private-room-privacy');
    const passwordInput = document.getElementById('private-room-password');
    const accessForm = document.getElementById('private-room-access-form');
    const accessIdInput = document.getElementById('private-room-access-id');
    const accessPasswordInput = document.getElementById('private-room-access-password');
    const accessStatus = document.getElementById('private-room-access-status');
    const searchInput = document.getElementById('private-room-search');
    const list = document.getElementById('private-room-list');
    const selectedLabel = document.getElementById('selected-room-label');
    const btnChat = document.getElementById('btn-room-chat');
    const chatBox = document.getElementById('roomChat');
    const closeChat = document.getElementById('btn-close-room-chat');
    const minimizeChat = document.getElementById('btn-minimize-room-chat');
    const chatTitle = document.getElementById('room-chat-title');
    const chatHistory = document.getElementById('roomChatHistory');
    const chatForm = document.getElementById('room-chat-form');
    const chatInput = document.getElementById('roomChatInput');
    const chatHeader = chatBox?.querySelector('.chat-header');
    const roomInfo = document.getElementById('active-room-info');
    const roomInfoName = document.getElementById('active-room-name');
    const roomInfoType = document.getElementById('active-room-type');
    const roomPrivateData = document.getElementById('active-room-private-data');
    const roomInfoId = document.getElementById('active-room-id');
    const roomPasswordChip = document.getElementById('active-room-password');
    const copyRoomData = document.getElementById('copy-room-data');

    let salas = [];
    let salaActual = null;
    let salaPendiente = null;
    let salaPassword = '';
    let chatTimer = null;
    let ultimoMensajeChat = 0;
    let arrastrandoChat = false;
    let chatDragOffsetX = 0;
    let chatDragOffsetY = 0;
    let chatPosicion = null;
    let dialogoProfesorActivo = false;

    async function pedirJson(url, opciones = {}) {
        const res = await fetch(url, { credentials: 'same-origin', ...opciones });
        const data = await res.json();
        if (!res.ok || data.ok === false) {
            throw new Error(data.error || 'No se pudo completar la accion.');
        }
        return data;
    }

    function mostrarEstadoAcceso(texto, tipo = '') {
        if (!accessStatus) return;
        accessStatus.textContent = texto;
        accessStatus.className = `private-access-status ${tipo}`.trim();
    }

    function marcarSalaSeleccionada(sala, boton = null) {
        document.querySelectorAll('.room-card, .private-room-card').forEach(item => item.classList.remove('selected'));
        const botonFinal = boton || Array.from(document.querySelectorAll('.private-room-card'))
            .find(item => Number(item.dataset.roomId) === Number(sala.id_sala));
        botonFinal?.classList.add('selected');
    }

    function actualizarInfoSala(sala = null) {
        if (!roomInfo) return;

        if (!sala || dialogoProfesorActivo) {
            roomInfo.classList.add('hidden');
            return;
        }

        const esPrivada = sala.privacidad === 'privada';
        roomInfo.classList.remove('hidden');
        roomInfoName.textContent = sala.nombre || 'Sala';
        roomInfoType.textContent = esPrivada ? 'Privada' : 'Publica';
        roomInfoId.textContent = sala.id_sala || '-';
        roomPrivateData.classList.toggle('hidden', !esPrivada);
        copyRoomData.textContent = 'Copiar';

        if (esPrivada) {
            roomPasswordChip.dataset.password = salaPassword || 'Contrasena oculta';
            roomPasswordChip.textContent = '********';
        } else {
            roomPasswordChip.dataset.password = '';
            roomPasswordChip.textContent = '********';
        }
    }

    function entrarAlJuego() {
        document.getElementById('btn-enter-room')?.click();
    }

    async function cargarSalas() {
        if (!list) return;
        try {
            salas = await pedirJson('sala_ajax.php?accion=listar');
            renderSalas();
        } catch (error) {
            list.innerHTML = '<p class="private-room-empty">No se pudieron cargar las salas.</p>';
        }
    }

    async function asegurarSalaParaChat() {
        if (salaActual?.id_sala) return salaActual;

        const seleccionGuardada = JSON.parse(localStorage.getItem('cozyStudySelectedRoom') || '{}');
        const nombreSala = seleccionGuardada.label || 'Biblioteca';
        const materiaSala = seleccionGuardada.subject || 'Publica';
        let salaChat = salas.find(sala =>
            sala.privacidad === 'publica' &&
            String(sala.nombre).toLowerCase() === String(nombreSala).toLowerCase()
        );

        if (!salaChat) {
            const datos = new FormData();
            datos.append('accion', 'crear');
            datos.append('nombre', nombreSala);
            datos.append('privacidad', 'publica');
            datos.append('password', '');
            const creada = await pedirJson('sala_ajax.php', { method: 'POST', body: datos });
            salaChat = creada.sala || {
                id_sala: creada.id_sala,
                nombre: nombreSala,
                privacidad: 'publica',
                propietario: window.SESION_PHP?.nombre || 'Usuario'
            };
            salas = [salaChat, ...salas.filter(sala => Number(sala.id_sala) !== Number(salaChat.id_sala))];
            renderSalas();
        }

        salaChat = {
            ...salaChat,
            nombre: salaChat.nombre || nombreSala,
            privacidad: salaChat.privacidad || 'publica',
            materia: materiaSala
        };
        await activarSala(salaChat, '');
        return salaChat;
    }

    function renderSalas() {
        const termino = searchInput?.value.trim().toLowerCase() || '';
        const visibles = salas.filter(sala =>
            sala.nombre.toLowerCase().includes(termino) ||
            sala.propietario.toLowerCase().includes(termino) ||
            String(sala.id_sala).includes(termino)
        );

        list.innerHTML = '';
        if (visibles.length === 0) {
            list.innerHTML = '<p class="private-room-empty">No hay salas con ese criterio.</p>';
            return;
        }

        visibles.forEach(sala => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'private-room-card';
            btn.dataset.roomId = sala.id_sala;
            btn.innerHTML = `
                <span>${sala.privacidad === 'privada' ? 'Priv.' : 'Pub.'}</span>
                <strong>${Sanitizer.escapeHtml(sala.nombre)}</strong>
                <small>ID ${Sanitizer.escapeHtml(sala.id_sala)} - ${Sanitizer.escapeHtml(sala.propietario)}</small>`;
            btn.addEventListener('click', () => seleccionarSala(sala, btn));
            list.appendChild(btn);
        });
    }

    async function activarSala(sala, password = '', boton = null) {
        salaActual = sala;
        salaPendiente = null;
        salaPassword = password;
        ultimoMensajeChat = 0;
        marcarSalaSeleccionada(sala, boton);
        selectedLabel.textContent = `${sala.nombre} - ${sala.privacidad === 'privada' ? 'Privada' : 'Publica'}`;
        chatTitle.textContent = `Chat - ${sala.nombre}`;
        localStorage.setItem('cozyStudySelectedRoom', JSON.stringify({
            room: `sala-${sala.id_sala}`,
            idSala: sala.id_sala,
            subject: sala.privacidad,
            label: sala.nombre
        }));
        mostrarEstadoAcceso('');
        actualizarInfoSala(sala);
        await cargarChat();
    }

    async function seleccionarSala(sala, boton = null) {
        if (sala.privacidad === 'privada') {
            salaPendiente = sala;
            marcarSalaSeleccionada(sala, boton);
            accessIdInput.value = sala.id_sala;
            accessPasswordInput.value = '';
            accessPasswordInput.focus();
            selectedLabel.textContent = `${sala.nombre} - Privada`;
            mostrarEstadoAcceso('Ingresa ID y contrasena para entrar.');
            return;
        }

        try {
            await activarSala(sala, '', boton);
            entrarAlJuego();
        } catch (error) {
            chatHistory.innerHTML = `<p class="room-chat-empty">${Sanitizer.escapeHtml(error.message)}</p>`;
        }
    }

    async function accederSalaPrivada(e) {
        e.preventDefault();
        const idSala = Number(accessIdInput.value);
        const password = accessPasswordInput.value;
        const sala = salas.find(item => Number(item.id_sala) === idSala) || salaPendiente;

        if (!idSala || !password.trim()) {
            mostrarEstadoAcceso('Completa ID y contrasena.', 'error');
            return;
        }

        const datos = new FormData();
        datos.append('accion', 'acceder');
        datos.append('id_sala', String(idSala));
        datos.append('password', password);

        try {
            const acceso = await pedirJson('sala_ajax.php', { method: 'POST', body: datos });
            const salaAccedida = (sala && Number(sala.id_sala) === idSala)
                ? sala
                : acceso.sala || {
                    id_sala: idSala,
                    nombre: `Sala ${idSala}`,
                    privacidad: 'privada',
                    propietario: ''
                };
            await activarSala(salaAccedida, password);
            mostrarEstadoAcceso('Sala privada desbloqueada.', 'ok');
            entrarAlJuego();
        } catch (error) {
            mostrarEstadoAcceso(error.message, 'error');
        }
    }

    async function crearSala(e) {
        e.preventDefault();
        const nombre = nameInput.value.trim();
        const privacidad = privacyInput.value;
        const password = passwordInput.value;
        if (!nombre) return;
        if (privacidad === 'privada' && password.trim().length < 4) {
            passwordInput.value = '';
            passwordInput.placeholder = 'La contrasena debe tener al menos 4 caracteres';
            passwordInput.focus();
            return;
        }

        const datos = new FormData();
        datos.append('accion', 'crear');
        datos.append('nombre', nombre);
        datos.append('privacidad', privacidad);
        datos.append('password', password);

        try {
            const creada = await pedirJson('sala_ajax.php', { method: 'POST', body: datos });
            const salaCreada = creada.sala || {
                id_sala: creada.id_sala,
                nombre,
                privacidad,
                propietario: window.SESION_PHP?.nombre || 'Usuario'
            };
            salas = [salaCreada, ...salas.filter(sala => Number(sala.id_sala) !== Number(salaCreada.id_sala))];
            renderSalas();
            nameInput.value = '';
            passwordInput.value = '';
            await activarSala(salaCreada, privacidad === 'privada' ? password : '');
            selectedLabel.textContent = `Sala creada - ID ${creada.id_sala}`;
            entrarAlJuego();
        } catch (error) {
            nameInput.placeholder = error.message;
        }
    }

    async function cargarChat() {
        if (!salaActual?.id_sala) return;
        try {
            const mensajes = await pedirJson(`sala_ajax.php?accion=listar_chat&id_sala=${encodeURIComponent(salaActual.id_sala)}`);
            const mensajesNuevos = ultimoMensajeChat > 0
                ? mensajes.filter(m => Number(m.id_mensaje) > ultimoMensajeChat)
                : [];
            chatHistory.innerHTML = mensajes.length === 0
                ? '<p class="room-chat-empty">La sala esta lista.</p>'
                : mensajes.map(m => `
                    <div class="room-chat-msg">
                        <strong>${Sanitizer.escapeHtml(m.nombre_usuario)}</strong>
                        <p>${Sanitizer.escapeHtml(m.mensaje)}</p>
                    </div>`).join('');
            chatHistory.scrollTop = chatHistory.scrollHeight;
            mensajesNuevos.forEach(m => mostrarBurbujaJugador(m.nombre_usuario, m.mensaje));
            ultimoMensajeChat = mensajes.reduce((max, m) => Math.max(max, Number(m.id_mensaje) || 0), ultimoMensajeChat);
        } catch (error) {
            chatHistory.innerHTML = '<p class="room-chat-empty">No se pudo cargar el chat.</p>';
        }
    }

    async function enviarMensaje(e) {
        e.preventDefault();
        const mensaje = chatInput.value.trim();
        if (!mensaje) return;
        let salaChat = null;
        try {
            salaChat = await asegurarSalaParaChat();
        } catch (error) {
            chatInput.placeholder = error.message || 'No se pudo preparar el chat.';
            return;
        }
        if (!salaChat?.id_sala) return;

        const datos = new FormData();
        datos.append('accion', 'enviar_chat');
        datos.append('id_sala', salaChat.id_sala);
        datos.append('mensaje', mensaje);
        try {
            await pedirJson('sala_ajax.php', { method: 'POST', body: datos });
            chatInput.value = '';
            chatForm.classList.add('is-collapsed');
            chatInput.blur();
            await cargarChat();
        } catch (error) {
            chatInput.placeholder = error.message;
        }
    }

    function abrirChat({ paraEscribir = false, minimizado = false } = {}) {
        chatBox?.classList.remove('hidden');
        chatBox?.classList.toggle('is-minimized', minimizado);
        if (!salaActual?.id_sala) {
            chatHistory.innerHTML = '<p class="room-chat-empty">Selecciona una sala del explorador para conversar.</p>';
        }
        if (paraEscribir) {
            chatForm?.classList.remove('is-collapsed');
            requestAnimationFrame(() => chatInput?.focus());
        }
        if (salaActual?.id_sala) {
            cargarChat();
            iniciarActualizacionChat();
        }
        if (chatBox && chatPosicion) {
            requestAnimationFrame(() => fijarPosicionChat(chatPosicion.x, chatPosicion.y));
        }
    }

    function abrirChatParaEscribir() {
        abrirChat({ paraEscribir: true });
    }

    function minimizarChat() {
        if (!chatBox || chatBox.classList.contains('hidden')) {
            abrirChat({ minimizado: true });
            return;
        }
        const quedaraMinimizado = !chatBox.classList.contains('is-minimized');
        chatBox.classList.toggle('is-minimized', quedaraMinimizado);
        if (quedaraMinimizado) {
            chatForm?.classList.add('is-collapsed');
            chatInput?.blur();
        } else {
            cargarChat();
            iniciarActualizacionChat();
        }
    }

    function cerrarChat() {
        chatBox?.classList.add('hidden');
        chatBox?.classList.remove('is-minimized');
        chatForm?.classList.add('is-collapsed');
        chatInput?.blur();
        clearInterval(chatTimer);
    }

    function mostrarBurbujaJugador(nombreUsuario, mensaje) {
        window.dispatchEvent(new CustomEvent('roomChatBubble', {
            detail: {
                playerId: nombreUsuario,
                message: mensaje
            }
        }));
    }

    function iniciarActualizacionChat() {
        clearInterval(chatTimer);
        chatTimer = setInterval(cargarChat, 5000);
    }

    function limitarPosicionChat(x, y) {
        if (!chatBox) return { x, y };
        const rect = chatBox.getBoundingClientRect();
        const ancho = rect.width || chatBox.offsetWidth || 300;
        const alto = rect.height || chatBox.offsetHeight || 160;
        const maxX = Math.max(8, window.innerWidth - ancho - 8);
        const maxY = Math.max(8, window.innerHeight - alto - 8);
        return {
            x: Math.min(Math.max(8, x), maxX),
            y: Math.min(Math.max(8, y), maxY)
        };
    }

    function fijarPosicionChat(x, y) {
        if (!chatBox) return;
        const pos = limitarPosicionChat(x, y);
        chatBox.style.left = `${pos.x}px`;
        chatBox.style.top = `${pos.y}px`;
        chatBox.style.right = 'auto';
        chatBox.style.bottom = 'auto';
        chatPosicion = pos;
    }

    function iniciarArrastreChat(e) {
        if (!chatBox || e.target.closest('button, input')) return;
        const punto = e.touches?.[0] || e;
        const rect = chatBox.getBoundingClientRect();
        arrastrandoChat = true;
        chatDragOffsetX = punto.clientX - rect.left;
        chatDragOffsetY = punto.clientY - rect.top;
        chatBox.classList.add('is-dragging');
        e.preventDefault();
    }

    function moverChat(e) {
        if (!arrastrandoChat) return;
        const punto = e.touches?.[0] || e;
        fijarPosicionChat(punto.clientX - chatDragOffsetX, punto.clientY - chatDragOffsetY);
        e.preventDefault();
    }

    function terminarArrastreChat() {
        arrastrandoChat = false;
        chatBox?.classList.remove('is-dragging');
    }

    form?.addEventListener('submit', crearSala);
    accessForm?.addEventListener('submit', accederSalaPrivada);
    searchInput?.addEventListener('input', renderSalas);
    chatForm?.addEventListener('submit', enviarMensaje);
    btnChat?.addEventListener('click', () => {
        if (chatBox?.classList.contains('hidden') || chatBox?.classList.contains('is-minimized')) {
            abrirChat();
            return;
        }
        cerrarChat();
    });
    minimizeChat?.addEventListener('click', minimizarChat);
    closeChat?.addEventListener('click', cerrarChat);
    chatHeader?.addEventListener('mousedown', iniciarArrastreChat);
    chatHeader?.addEventListener('touchstart', iniciarArrastreChat, { passive: false });
    window.addEventListener('mousemove', moverChat);
    window.addEventListener('touchmove', moverChat, { passive: false });
    window.addEventListener('mouseup', terminarArrastreChat);
    window.addEventListener('touchend', terminarArrastreChat);
    window.addEventListener('resize', () => {
        if (!chatBox || chatBox.classList.contains('hidden')) return;
        const rect = chatBox.getBoundingClientRect();
        fijarPosicionChat(rect.left, rect.top);
    });
    copyRoomData?.addEventListener('click', async () => {
        if (!salaActual) return;
        const texto = salaActual.privacidad === 'privada'
            ? `Nombre: ${salaActual.nombre}\nTipo: Privada\nID: ${salaActual.id_sala}\nContrasena: ${salaPassword}`
            : `Nombre: ${salaActual.nombre}\nTipo: Publica\nID: ${salaActual.id_sala}`;
        try {
            await navigator.clipboard.writeText(texto);
            copyRoomData.textContent = 'Copiado';
        } catch (error) {
            copyRoomData.textContent = 'Error';
        }
        setTimeout(() => {
            copyRoomData.textContent = 'Copiar';
        }, 1600);
    });
    roomPasswordChip?.addEventListener('mouseenter', () => {
        roomPasswordChip.textContent = roomPasswordChip.dataset.password || 'Contrasena oculta';
    });
    roomPasswordChip?.addEventListener('mouseleave', () => {
        roomPasswordChip.textContent = '********';
    });
    roomPasswordChip?.addEventListener('focus', () => {
        roomPasswordChip.textContent = roomPasswordChip.dataset.password || 'Contrasena oculta';
    });
    roomPasswordChip?.addEventListener('blur', () => {
        roomPasswordChip.textContent = '********';
    });
    window.addEventListener('profesorChatVisibilityChanged', (e) => {
        dialogoProfesorActivo = Boolean(e.detail?.visible);
        actualizarInfoSala(salaActual);
    });
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.altKey || e.metaKey) return;
        if (document.getElementById('game-stage')?.classList.contains('hidden')) return;
        const tag = document.activeElement?.tagName?.toLowerCase();
        const escribiendoEnFormulario = ['input', 'textarea', 'select'].includes(tag) || document.activeElement?.isContentEditable;
        if (e.key.toLowerCase() === 't' && document.activeElement !== chatInput && !escribiendoEnFormulario) {
            e.preventDefault();
            abrirChatParaEscribir();
        }
        if (e.key === 'Escape' && document.activeElement === chatInput) {
            chatForm.classList.add('is-collapsed');
            chatInput.blur();
        }
    });
    document.querySelectorAll('.room-card').forEach(card => {
        card.addEventListener('click', () => {
            salaActual = null;
            salaPendiente = null;
            salaPassword = '';
            actualizarInfoSala(null);
            chatTitle.textContent = 'Chat de sala';
            chatHistory.innerHTML = '<p class="room-chat-empty">Selecciona una sala del explorador para conversar.</p>';
            mostrarEstadoAcceso('');
        });
    });

    privacyInput?.addEventListener('change', () => {
        const esPrivada = privacyInput.value === 'privada';
        passwordInput.classList.toggle('hidden', !esPrivada);
        passwordInput.required = esPrivada;
    });

    chatForm?.classList.add('is-collapsed');
    cargarSalas();

    // Se expone para que otros módulos (GameManager) puedan asegurarse de que
    // la sala rápida elegida (Biblioteca, Aula, Cafetería, Silenciosa) exista
    // en la base de datos antes de entrar a jugar, sin esperar a que el
    // usuario escriba en el chat.
    window.CozyStudySalas = window.CozyStudySalas || {};
    window.CozyStudySalas.asegurarSalaParaChat = asegurarSalaParaChat;
});
