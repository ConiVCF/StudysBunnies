document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);
    const mensaje = $('admin-mensaje');
    const userSearch = $('admin-user-search');
    const usersBody = $('admin-users-body');
    const totalUsuarios = $('admin-total-usuarios');
    const totalMisiones = $('admin-total-misiones');
    const totalEventos = $('admin-total-eventos');

    const ROL_GENERAL = 0;
    const ROL_ADMIN = 1;
    const ROL_MODERADOR = 2;
    const rolActor = Number(usersBody.dataset.rolActor);
    const esAdminActor = rolActor === ROL_ADMIN;

    let usuariosAdmin = [];
    let misionesAdmin = [];
    let eventosAdmin = [];
    let confirmOverlay = null;
    let infoOverlay = null;

    function mostrarMensaje(texto, tipo) {
        mensaje.textContent = texto;
        mensaje.className = tipo;
        setTimeout(() => {
            mensaje.textContent = '';
            mensaje.className = '';
        }, 3500);
    }

    function escapeHtml(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function confirmarAccion(texto) {
        if (!confirmOverlay) {
            confirmOverlay = document.createElement('div');
            confirmOverlay.className = 'admin-confirm-overlay hidden';
            confirmOverlay.innerHTML = `
                <div class="admin-confirm-box">
                    <p id="admin-confirm-texto"></p>
                    <div class="admin-confirm-actions">
                        <button type="button" id="admin-confirm-no">No</button>
                        <button type="button" id="admin-confirm-si">Si</button>
                    </div>
                </div>`;
            document.body.appendChild(confirmOverlay);
        }

        return new Promise((resolve) => {
            const btnSi = confirmOverlay.querySelector('#admin-confirm-si');
            const btnNo = confirmOverlay.querySelector('#admin-confirm-no');
            confirmOverlay.querySelector('#admin-confirm-texto').textContent = texto;
            const cerrar = (ok) => {
                confirmOverlay.classList.add('hidden');
                btnSi.removeEventListener('click', onSi);
                btnNo.removeEventListener('click', onNo);
                resolve(ok);
            };
            const onSi = () => cerrar(true);
            const onNo = () => cerrar(false);
            btnSi.addEventListener('click', onSi);
            btnNo.addEventListener('click', onNo);
            confirmOverlay.classList.remove('hidden');
        });
    }

    function mostrarInfo(texto) {
        if (!infoOverlay) {
            infoOverlay = document.createElement('div');
            infoOverlay.className = 'admin-confirm-overlay hidden';
            infoOverlay.innerHTML = `
                <div class="admin-confirm-box">
                    <p id="admin-info-texto"></p>
                    <div class="admin-confirm-actions">
                        <button type="button" id="admin-info-ok">Entendido</button>
                    </div>
                </div>`;
            document.body.appendChild(infoOverlay);
        }

        return new Promise((resolve) => {
            const btnOk = infoOverlay.querySelector('#admin-info-ok');
            infoOverlay.querySelector('#admin-info-texto').textContent = texto;
            const cerrar = () => {
                infoOverlay.classList.add('hidden');
                btnOk.removeEventListener('click', onOk);
                resolve();
            };
            const onOk = () => cerrar();
            btnOk.addEventListener('click', onOk);
            infoOverlay.classList.remove('hidden');
        });
    }

    async function pedirJson(url, opciones = {}) {
        const res = await fetch(url, { credentials: 'same-origin', ...opciones });
        const data = await res.json();
        if (!res.ok || data.ok === false) {
            throw new Error(data.error || 'No se pudo completar la accion.');
        }
        return data;
    }

    function crearBoton(texto, clase, handler) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = texto;
        if (clase) btn.className = clase;
        btn.addEventListener('click', handler);
        return btn;
    }

    function actualizarResumen() {
        totalUsuarios.textContent = usuariosAdmin.length;
        totalMisiones.textContent = misionesAdmin.length;
        totalEventos.textContent = eventosAdmin.length;
    }

    async function cargarTodo() {
        [usuariosAdmin, misionesAdmin, eventosAdmin] = await Promise.all([
            pedirJson('admin_ajax.php?accion=listar_usuarios_admin'),
            pedirJson('admin_ajax.php?accion=listar_misiones'),
            pedirJson('admin_ajax.php?accion=listar_eventos_admin')
        ]);
        actualizarResumen();
        renderUsuarios();
    }

    function renderUsuarios() {
        const termino = userSearch.value.trim().toLowerCase();
        const filtrados = usuariosAdmin.filter(u =>
            u.nombre_usuario.toLowerCase().includes(termino) ||
            u.email.toLowerCase().includes(termino)
        );

        usersBody.innerHTML = '';
        if (filtrados.length === 0) {
            usersBody.innerHTML = '<div class="empty-state">No hay usuarios para mostrar.</div>';
            return;
        }

        filtrados.forEach(usuario => {
            const activo = Number(usuario.activo) === 1;
            const marcado = Number(usuario.marcado) === 1;
            const rolUsuario = Number(usuario.rol);
            const claseRol = rolUsuario === ROL_ADMIN ? 'admin' : (rolUsuario === ROL_MODERADOR ? 'moderador' : '');
            const nombreRol = rolUsuario === ROL_ADMIN ? 'Admin' : (rolUsuario === ROL_MODERADOR ? 'Moderador' : 'Estudiante');
            const card = document.createElement('article');
            card.className = `admin-user-card ${activo ? '' : 'is-suspended'} ${marcado ? 'is-marcado' : ''}`;
            card.dataset.userId = usuario.id_usuario;
            card.innerHTML = `
                <div class="admin-user-card__main">
                    <div>
                        <span class="badge-usuario">${escapeHtml(usuario.nombre_usuario)}</span>
                        ${marcado ? '<span class="badge-marcado" title="Marcado por mal comportamiento">&#9888; Marcado</span>' : ''}
                        <p>${escapeHtml(usuario.email)}</p>
                    </div>
                    <div class="admin-user-card__nivel">
                        <input type="number" min="1" max="999" placeholder="Nivel" data-nivel-input>
                        <button type="button" data-guardar-nivel>Nivel</button>
                    </div>
                    <div class="acciones-fila admin-user-card__actions"></div>
                </div>
                <details class="admin-user-card__workspace">
                    <summary>Misiones y eventos</summary>
                    <div class="admin-user-card__workspace-body">
                        <p class="admin-user-card__rol-info">Rol: <span class="${claseRol}">${nombreRol}</span></p>
                        <section>
                            <h3>Misiones</h3>
                            <form class="admin-mini-form" data-kind="mision" data-user="${usuario.id_usuario}">
                                <input name="tarea" maxlength="255" placeholder="Nueva tarea">
                                <button type="submit">Agregar</button>
                            </form>
                            <div class="admin-user-list" data-list="misiones"></div>
                        </section>
                        <section>
                            <h3>Eventos</h3>
                            <form class="admin-mini-form admin-mini-form--event" data-kind="evento" data-user="${usuario.id_usuario}">
                                <input name="titulo" maxlength="100" placeholder="Nuevo evento">
                                <select name="tipo">
                                    <option value="Examen">Examen</option>
                                    <option value="Final">Final</option>
                                    <option value="TP">TP</option>
                                    <option value="Proyecto">Proyecto</option>
                                    <option value="Personal">Personal</option>
                                </select>
                                <input name="fecha" type="date">
                                <button type="submit">Agregar</button>
                            </form>
                            <div class="admin-user-list" data-list="eventos"></div>
                        </section>
                    </div>
                </details>`;

            const acciones = card.querySelector('.admin-user-card__actions');

            
            if (esAdminActor) {
                const nuevoRol = rolUsuario === ROL_MODERADOR ? ROL_GENERAL : ROL_MODERADOR;
                const textoCambio = rolUsuario === ROL_MODERADOR ? 'Volver Estudiante' : 'Hacer Moderador';
                acciones.appendChild(crearBoton(textoCambio, '', () => cambiarRolUsuario(usuario, nuevoRol)));
            }

            
            if (esAdminActor) {
                acciones.appendChild(crearBoton(activo ? 'Suspender' : 'Reactivar', activo ? 'btn-borrar' : 'btn-activar', () => cambiarEstadoUsuario(usuario, activo)));
                acciones.appendChild(crearBoton('Eliminar', 'btn-borrar', () => eliminarUsuario(usuario)));
            }

            
            if (rolUsuario !== ROL_ADMIN) {
                const btnMarcar = crearBoton('!', 'btn-marcar', () => marcarUsuarioAccion(usuario, marcado));
                btnMarcar.title = marcado ? 'Quitar marca por mal comportamiento' : 'Marcar por mal comportamiento';
                acciones.appendChild(btnMarcar);
            }

            const botonNivel = card.querySelector('[data-guardar-nivel]');
            const inputNivel = card.querySelector('[data-nivel-input]');
            botonNivel.addEventListener('click', () => {
                const nivel = parseInt(inputNivel.value, 10);
                if (!nivel || nivel < 1) {
                    mostrarMensaje('Ingresá un nivel válido.', 'error');
                    return;
                }
                modificarNivelUsuario(usuario.id_usuario, nivel);
            });

            renderMisionesUsuario(card, usuario.id_usuario);
            renderEventosUsuario(card, usuario.id_usuario);
            usersBody.appendChild(card);
        });
    }

    function renderMisionesUsuario(card, idUsuario) {
        const contenedor = card.querySelector('[data-list="misiones"]');
        const misiones = misionesAdmin.filter(m => Number(m.id_usuario) === Number(idUsuario));
        contenedor.innerHTML = misiones.length ? '' : '<p class="admin-empty-small">Sin misiones.</p>';
        misiones.forEach(m => {
            const item = document.createElement('div');
            item.className = 'admin-user-item';
            item.innerHTML = `
                <input value="${escapeHtml(m.texto_tarea)}" maxlength="255">
                <select>
                    <option value="0" ${Number(m.estado_completado) === 0 ? 'selected' : ''}>Pendiente</option>
                    <option value="1" ${Number(m.estado_completado) === 1 ? 'selected' : ''}>Completada</option>
                </select>
                <div class="acciones-fila"></div>`;
            const input = item.querySelector('input');
            const estado = item.querySelector('select');
            const acciones = item.querySelector('.acciones-fila');
            acciones.appendChild(crearBoton('Guardar', '', () => editarMision(m.id_mision, idUsuario, input.value, estado.value)));
            acciones.appendChild(crearBoton('Eliminar', 'btn-borrar', () => eliminarMision(m.id_mision)));
            contenedor.appendChild(item);
        });
    }

    function renderEventosUsuario(card, idUsuario) {
        const contenedor = card.querySelector('[data-list="eventos"]');
        const eventos = eventosAdmin.filter(e => Number(e.id_usuario) === Number(idUsuario));
        contenedor.innerHTML = eventos.length ? '' : '<p class="admin-empty-small">Sin eventos.</p>';
        eventos.forEach(e => {
            const item = document.createElement('div');
            item.className = 'admin-user-item admin-user-item--event';
            item.innerHTML = `
                <input value="${escapeHtml(e.titulo)}" maxlength="100">
                <select>
                    ${['Examen', 'Final', 'TP', 'Proyecto', 'Personal'].map(tipo => `<option value="${tipo}" ${e.tipo === tipo ? 'selected' : ''}>${tipo}</option>`).join('')}
                </select>
                <input type="date" value="${escapeHtml(e.fecha)}">
                <div class="acciones-fila"></div>`;
            const [titulo, fecha] = item.querySelectorAll('input');
            const tipo = item.querySelector('select');
            const acciones = item.querySelector('.acciones-fila');
            acciones.appendChild(crearBoton('Guardar', '', () => editarEvento(e.id_evento, idUsuario, titulo.value, tipo.value, fecha.value)));
            acciones.appendChild(crearBoton('Eliminar', 'btn-borrar', () => eliminarEvento(e.id_evento)));
            contenedor.appendChild(item);
        });
    }

    async function cambiarEstadoUsuario(usuario, activo) {
        if (!(await confirmarAccion(activo ? 'Suspender este usuario?' : 'Reactivar este usuario?'))) return;
        const datos = new FormData();
        datos.append('accion', activo ? 'deshabilitar_usuario' : 'activar_usuario');
        datos.append('id_usuario', usuario.id_usuario);
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        mostrarMensaje(activo ? 'Usuario suspendido.' : 'Usuario reactivado.', 'ok');
        await cargarTodo();
    }

    async function marcarUsuarioAccion(usuario, marcadoActual) {
        if (!marcadoActual) {
            await mostrarInfo('Esta opción se utiliza para darle una "tarjeta amarilla" al usuario por mal comportamiento.');
            if (!(await confirmarAccion('¿Estás seguro de que querés marcar a este usuario?'))) return;
        } else {
            if (!(await confirmarAccion('¿Querés quitar la marca a este usuario?'))) return;
        }

        const datos = new FormData();
        datos.append('accion', 'marcar_usuario');
        datos.append('id_usuario', usuario.id_usuario);
        datos.append('marcado', marcadoActual ? 0 : 1);
        try {
            await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
            mostrarMensaje(marcadoActual ? 'Marca retirada.' : 'Usuario marcado.', 'ok');
            await cargarTodo();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    async function cambiarRolUsuario(usuario, nuevoRol) {
        const texto = nuevoRol === ROL_MODERADOR ? 'Convertir a Moderador?' : 'Volver a Estudiante?';
        if (!(await confirmarAccion(texto))) return;
        const datos = new FormData();
        datos.append('accion', 'cambiar_rol_usuario');
        datos.append('id_usuario', usuario.id_usuario);
        datos.append('rol', nuevoRol);
        try {
            await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
            mostrarMensaje('Rol actualizado.', 'ok');
            await cargarTodo();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    async function modificarNivelUsuario(idUsuario, nivel) {
        const datos = new FormData();
        datos.append('accion', 'modificar_nivel_usuario');
        datos.append('id_usuario', idUsuario);
        datos.append('nivel', nivel);
        try {
            await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
            mostrarMensaje('Nivel actualizado.', 'ok');
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    async function eliminarUsuario(usuario) {
        if (!(await confirmarAccion('Eliminar este usuario y sus datos asociados?'))) return;
        const datos = new FormData();
        datos.append('accion', 'eliminar_usuario');
        datos.append('id_usuario', usuario.id_usuario);
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        mostrarMensaje('Usuario eliminado.', 'ok');
        await cargarTodo();
    }

    async function crearMision(idUsuario, tarea) {
        const datos = new FormData();
        datos.append('accion', 'crear_mision');
        datos.append('id_usuario', idUsuario);
        datos.append('tarea', tarea);
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        await cargarTodo();
    }

    async function editarMision(idMision, idUsuario, tarea, completado) {
        const datos = new FormData();
        datos.append('accion', 'editar_mision');
        datos.append('id_mision', idMision);
        datos.append('id_usuario', idUsuario);
        datos.append('tarea', tarea.trim());
        datos.append('completado', completado);
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        mostrarMensaje('Misión actualizada.', 'ok');
        await cargarTodo();
    }

    async function eliminarMision(idMision) {
        if (!(await confirmarAccion('Eliminar esta misión?'))) return;
        const datos = new FormData();
        datos.append('accion', 'eliminar_mision');
        datos.append('id_mision', idMision);
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        await cargarTodo();
    }

    async function crearEvento(idUsuario, titulo, tipo, fecha) {
        const datos = new FormData();
        datos.append('accion', 'crear_evento_admin');
        datos.append('id_usuario', idUsuario);
        datos.append('titulo', titulo);
        datos.append('tipo', tipo);
        datos.append('fecha', fecha);
        datos.append('recordatorios', '7,3,1');
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        await cargarTodo();
    }

    async function editarEvento(idEvento, idUsuario, titulo, tipo, fecha) {
        const datos = new FormData();
        datos.append('accion', 'editar_evento_admin');
        datos.append('id_evento', idEvento);
        datos.append('id_usuario', idUsuario);
        datos.append('titulo', titulo.trim());
        datos.append('tipo', tipo);
        datos.append('fecha', fecha);
        datos.append('recordatorios', '7,3,1');
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        mostrarMensaje('Evento actualizado.', 'ok');
        await cargarTodo();
    }

    async function eliminarEvento(idEvento) {
        if (!(await confirmarAccion('Eliminar este evento?'))) return;
        const datos = new FormData();
        datos.append('accion', 'eliminar_evento_admin');
        datos.append('id_evento', idEvento);
        await pedirJson('admin_ajax.php', { method: 'POST', body: datos });
        await cargarTodo();
    }

    userSearch.addEventListener('input', renderUsuarios);
    usersBody.addEventListener('submit', (e) => {
        e.preventDefault();
        const form = e.target;
        const idUsuario = form.dataset.user;
        if (form.dataset.kind === 'mision') {
            const tarea = form.elements.tarea.value.trim();
            if (tarea) crearMision(idUsuario, tarea).catch(error => mostrarMensaje(error.message, 'error'));
        }
        if (form.dataset.kind === 'evento') {
            const titulo = form.elements.titulo.value.trim();
            const fecha = form.elements.fecha.value;
            if (titulo && fecha) crearEvento(idUsuario, titulo, form.elements.tipo.value, fecha).catch(error => mostrarMensaje(error.message, 'error'));
        }
    });

    cargarTodo().catch(error => mostrarMensaje(error.message, 'error'));
});
