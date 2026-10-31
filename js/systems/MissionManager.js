import { Sanitizer } from '../utils/Sanitizer.js';
import { UIDialog } from './UIDialog.js';

export class MissionManager {
    constructor(gameManager) {
        this.game = gameManager;
        this.missions = []; 

        
        this.modal = document.getElementById('mission-modal');
        this.btnOpen = document.getElementById('btn-missions');
        this.btnClose = document.getElementById('btn-close-missions');
        
        this.inputNew = document.getElementById('new-mission-input');
        this.btnAdd = document.getElementById('btn-add-mission');
        this.missionList = document.getElementById('mission-list');
        this.counterDisplay = document.getElementById('mission-counter');

        
        this.inputSearch = document.getElementById('mission-search-input');
        this.btnSearch = document.getElementById('btn-search-mission');
        this.btnClearSearch = document.getElementById('btn-clear-search-mission');

        this.setupEvents();
        this.init();
    }

    
    async init() {
        await this.cargarMisiones();
        this.renderMissions();
    }

    async cargarMisiones() {
        if (!window.SESION_PHP?.activa) {
            
            this.missions = this.game.playerProfile.missions || [];
            return;
        }

        try {
            const respuesta = await fetch('listar_misiones.php', {
                credentials: 'same-origin'
            });

            if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);

            const filas = await respuesta.json();
            this.missions = filas.map(fila => ({
                id: fila.id_mision,
                text: fila.texto_tarea,
                completed: Number(fila.estado_completado) === 1
            }));

            
            this.game.playerProfile.missions = this.missions;
            await this.game.saveSystem.saveData(this.game.playerProfile);
        } catch (error) {
            console.error("No se pudieron cargar las misiones desde la base de datos:", error);
            
            this.missions = this.game.playerProfile.missions || [];
        }
    }

    setupEvents() {
        this.btnOpen?.addEventListener('click', () => {
            this.game.audio.playSFX('click');
            this.modal.classList.remove('hidden');
        });

        this.btnClose?.addEventListener('click', () => {
            this.game.audio.playSFX('click');
            this.modal.classList.add('hidden');
        });

        this.btnAdd?.addEventListener('click', () => this.addMission());
        
        this.inputNew?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.addMission();
        });

       
        this.btnSearch?.addEventListener('click', () => this.buscarMisiones());

        this.inputSearch?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.buscarMisiones();
        });

        this.btnClearSearch?.addEventListener('click', () => {
            this.inputSearch.value = '';
            this.renderMissions();
        });
    }

    
    async buscarMisiones() {
        const termino = this.inputSearch.value.trim();

        if (!termino) {
            this.renderMissions();
            return;
        }

        try {
            const respuesta = await fetch(`buscar_misiones.php?q=${encodeURIComponent(termino)}`, {
                credentials: 'same-origin'
            });

            if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);

            const filas = await respuesta.json();
            const resultados = filas.map(fila => ({
                id: fila.id_mision,
                text: fila.texto_tarea,
                completed: Number(fila.estado_completado) === 1
            }));

            this.renderMissions(resultados);
        } catch (error) {
            console.error("Error buscando misiones:", error);
        }
    }

    
    async saveMissions() {
        this.game.playerProfile.missions = this.missions;
        await this.game.saveSystem.saveData(this.game.playerProfile);
        
    }

    
    async addMission() {
        const text = this.inputNew.value.trim();
        if (!text) return;

        const usuarioActual = window.SESION_PHP?.nombre;

        
        if (!window.SESION_PHP?.activa || !usuarioActual) {
            await UIDialog.alert("¡Debes iniciar sesión para guardar misiones en la base de datos!", "🔒");
            return;
        }

        
        const datos = new FormData();
        datos.append('usuario', usuarioActual);
        datos.append('tarea', text);

        try {
            
            const respuesta = await fetch('guardar_mision.php', {
                method: 'POST',
                credentials: 'same-origin', 
                body: datos
            });
            
            const resultado = await respuesta.text();

            
            if (resultado.startsWith("Exito")) {
                console.log("¡Misión guardada en MySQL!");

                
                const idReal = resultado.split('|')[1];

                const newMission = {
                    id: idReal ? Number(idReal) : Date.now(),
                    text: text,
                    completed: false
                };

                this.missions.push(newMission);
                this.inputNew.value = '';
                this.game.audio.playSFX('typing'); 
                
                await this.saveMissions();
                this.renderMissions();
            } else {
                console.error("Error del servidor:", resultado);
            }
        } catch (error) {
            console.error("Error de conexión:", error);
        }
    }

    async toggleMission(id) {
        const mission = this.missions.find(m => m.id === id);
        if (!mission) return;

        const nuevoEstado = !mission.completed;

        try {
            const datos = new FormData();
            datos.append('id_mision', id);
            datos.append('completado', nuevoEstado ? '1' : '0');

            const respuesta = await fetch('actualizar_mision.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: datos
            });

            const resultado = await respuesta.text();

            if (!resultado.startsWith("Exito")) {
                console.error("No se pudo actualizar la misión en la base de datos:", resultado);
                return; 
            }

            mission.completed = nuevoEstado;

            
            if (mission.completed) {
                this.game.audio.playSFX('step'); 
                this.game.addExperience(15); 
            } else {
                this.game.audio.playSFX('click');
            }

            await this.saveMissions();
            this.renderMissions();
        } catch (error) {
            console.error("Error de conexión al actualizar la misión:", error);
        }
    }

    async deleteMission(id) {
        if (!(await UIDialog.confirm("¿Eliminar este post-it?", "🗑️"))) return;

        try {
            const datos = new FormData();
            datos.append('id_mision', id);

            const respuesta = await fetch('eliminar_mision.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: datos
            });

            const resultado = await respuesta.text();

            if (!resultado.startsWith("Exito")) {
                console.error("No se pudo eliminar la misión en la base de datos:", resultado);
                return;
            }

            this.missions = this.missions.filter(m => m.id !== id);
            this.game.audio.playSFX('click');
            await this.saveMissions();
            this.renderMissions();
        } catch (error) {
            console.error("Error de conexión al eliminar la misión:", error);
        }
    }

    async editMission(id) {
        const mission = this.missions.find(m => m.id === id);
        if (!mission) return;

        const nuevoTexto = window.prompt('Editar mision', mission.text);
        if (nuevoTexto === null) return;

        const texto = nuevoTexto.trim();
        if (!texto || texto === mission.text) return;

        try {
            const datos = new FormData();
            datos.append('accion', 'editar');
            datos.append('id_mision', id);
            datos.append('tarea', texto);

            const respuesta = await fetch('actualizar_mision.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: datos
            });

            const resultado = await respuesta.text();

            if (!resultado.startsWith("Exito")) {
                console.error("No se pudo editar la mision en la base de datos:", resultado);
                return;
            }

            mission.text = texto;
            this.game.audio.playSFX('typing');
            await this.saveMissions();
            this.renderMissions();
        } catch (error) {
            console.error("Error de conexion al editar la mision:", error);
        }
    }

    renderMissions(listaOverride = null) {
        const lista = listaOverride ?? this.missions;
        this.missionList.innerHTML = '';

        
        const sortedMissions = [...lista].sort((a, b) => a.completed - b.completed);

        sortedMissions.forEach(mission => {
            const li = document.createElement('li');
            li.className = `mission-item ${mission.completed ? 'completed' : ''}`;
            
            li.innerHTML = `
                <span class="mission-text">${Sanitizer.escapeHtml(mission.text)}</span>
                <div class="mission-actions">
                    <button class="btn-icon check-btn" title="Marcar como lista">${mission.completed ? '↩️' : '✅'}</button>
                    <button class="btn-icon edit-btn" title="Editar">Editar</button>
                    <button class="btn-icon delete-btn" title="Tirar a la basura">🗑️</button>
                </div>
            `;

            
            li.querySelector('.check-btn').addEventListener('click', () => this.toggleMission(mission.id));
            li.querySelector('.edit-btn').addEventListener('click', () => this.editMission(mission.id));
            li.querySelector('.delete-btn').addEventListener('click', () => this.deleteMission(mission.id));

            this.missionList.appendChild(li);
        });

        
        const totalCompletadas = this.missions.filter(m => m.completed).length;
        this.counterDisplay.innerText = `${totalCompletadas}/${this.missions.length} Tareas Logradas`;
    }
}
