import { Player } from '../entities/Player.js';
import { StudyTimer } from '../systems/StudyTimer.js';
import { Physics } from '../utils/Physics.js';
import { AITeacher } from '../entities/AITeacher.js';
import { SaveSystem } from '../systems/SaveSystem.js';
import { InventoryManager } from '../systems/InventoryManager.js';
import { AudioManager } from '../systems/AudioManager.js';
import { StudyRoom } from '../scenes/StudyRoom.js';
import { MissionManager } from '../systems/MissionManager.js';
import { CalendarManager } from '../systems/CalendarManager.js';
import { UIDialog } from '../systems/UIDialog.js';
import { Sanitizer } from '../utils/Sanitizer.js';

class GameManager {
    constructor() {
        this.worldContainer = document.getElementById('world-container');
        this.lastTime = 0;
        this.zoomLevel = 1.6; 
        
        this.saveSystem = new SaveSystem();
        this.room = new StudyRoom();
        
        this.player = null;
        this.timer = null;
        this.aiTeacher = null;
        this.inventory = null;
        this.audio = null;
        
        this.init();
    }

    async init() {
        this.playerProfile = await this.saveSystem.loadData();
        this.playerProfile.stats = {
            level: 1,
            exp: 0,
            coins: 0,
            studySeconds: 0,
            ...(this.playerProfile.stats || {})
        };
        this.studySecondsSinceSave = 0;
        this.inventory = new InventoryManager(this);
        this.audio = new AudioManager(this.playerProfile.settings);
        this.timer = new StudyTimer('timerDisplay');
        this.aiTeacher = new AITeacher();
        this.missionManager = new MissionManager(this);
        this.calendarManager = new CalendarManager(this);

        
        this.players = {};
        const localId = window.SESION_PHP?.nombre || 'Tu';
        
        
        this.player = new Player(localId, 200, 100, this.playerProfile.avatar, true);
        this.updateLocalPlayerInfo();
        this.players[localId] = this.player;

        
        this.iniciarMultijugador();

        this.updateOnlineUI();

        this.setupAuth(); 
        this.setupMenuMusic();
        this.setupAudioControls(); 
        this.setupGlobalAudioEvents(); 
        this.setupTimerControls();
        this.setupMenuControls();
        this.setupHistoryNavigation();
        this.setupAiChatResize();
        this.setupProgressionSystem();
        
        requestAnimationFrame((timestamp) => this.gameLoop(timestamp));
    }

    
    updateOnlineUI() {
        const countSpan = document.getElementById('online-count');
        const listUl = document.getElementById('online-list');
        
        if (countSpan && listUl) {
            const playerIds = Object.keys(this.players);
            countSpan.innerText = playerIds.length;
            
            listUl.innerHTML = '';
            playerIds.forEach(id => {
                const li = document.createElement('li');
                const nombreSeguro = Sanitizer.escapeHtml(id);
                li.innerHTML = `${id === this.player.id ? `<b>${nombreSeguro}</b>` : nombreSeguro}`;
                listUl.appendChild(li);
            });
        }
    }

   
    iniciarMultijugador() {
        
        this.intervaloEnvioPosicion = setInterval(() => this.enviarPosicion(), 400);

       
        this.intervaloConsultaJugadores = setInterval(() => this.actualizarJugadoresRemotos(), 1000);
        this.actualizarJugadoresRemotos(); // primera consulta inmediata, sin esperar el primer intervalo

        
        window.addEventListener('beforeunload', () => {
            if (this.playerProfile) this.saveSystem.saveData(this.playerProfile);
            navigator.sendBeacon('salir_online.php');
        });
    }

    getSelectedRoomData() {
        try {
            return JSON.parse(localStorage.getItem('cozyStudySelectedRoom') || '{}');
        } catch (error) {
            return {};
        }
    }

    getCurrentStudySubject() {
        const sala = this.getSelectedRoomData();
        const selectedCardSubject = document.querySelector('.room-card.selected')?.dataset.subject || '';
        const subject = String(sala.subject || selectedCardSubject).trim();
        if (!subject || ['privada', 'publica'].includes(subject.toLowerCase())) {
            return '';
        }
        return subject;
    }

    updateLocalPlayerInfo() {
        if (!this.player) return;
        this.player.setInfo({
            name: window.SESION_PHP?.nombre || this.player.id,
            age: window.SESION_PHP?.edad || this.player.info?.age || null,
            subject: this.getCurrentStudySubject(),
            studySeconds: this.playerProfile?.stats?.studySeconds || 0
        });
    }

    async enviarPosicion() {
        if (!window.SESION_PHP?.activa || !this.player) return;
        if (!this.isStudyRoomOpen()) return;

        this.updateLocalPlayerInfo();
        const sala = this.getSelectedRoomData();
        const datos = new FormData();
        datos.append('x', this.player.x);
        datos.append('y', this.player.y);
        datos.append('direccion', this.player.facing);
        datos.append('estado', this.player.state);
        datos.append('avatar_base', this.player.appearance.base);
        datos.append('avatar_clothing', this.player.appearance.clothing);
        datos.append('avatar_accessory', this.player.appearance.accessory);
        datos.append('materia_estudio', this.getCurrentStudySubject());
        datos.append('tiempo_estudio_segundos', this.playerProfile.stats.studySeconds || 0);
        if (sala.idSala) datos.append('id_sala', sala.idSala);

        try {
            await fetch('actualizar_posicion.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: datos
            });
        } catch (error) {
            console.error("No se pudo enviar la posicion al servidor:", error);
        }
    }

    async actualizarJugadoresRemotos() {
        if (!window.SESION_PHP?.activa) return;

        try {
            const sala = this.getSelectedRoomData();
            const query = sala.idSala ? `?id_sala=${encodeURIComponent(sala.idSala)}` : '';
            const respuesta = await fetch(`listar_jugadores.php${query}`, { credentials: 'same-origin' });
            if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);

            const activos = await respuesta.json();
            const idsActivos = new Set(activos
                .filter(j => j.nombre_usuario !== this.player.id)
                .map(j => j.nombre_usuario));
            let huboCambios = false;

            
            activos.forEach(j => {
                const info = {
                    name: j.nombre_usuario,
                    age: j.edad || null,
                    subject: j.materia_estudio || '',
                    studySeconds: Number(j.tiempo_estudio_segundos) || 0
                };

                if (j.nombre_usuario === this.player.id) {
                    this.player.setInfo(info);
                    return;
                }

                const avatar = {
                    base: j.avatar_base,
                    clothing: j.avatar_clothing,
                    accessory: j.avatar_accessory
                };

                if (this.players[j.nombre_usuario]) {
                    this.players[j.nombre_usuario].applyRemoteState(
                        parseFloat(j.pos_x), parseFloat(j.pos_y), j.direccion, j.estado, avatar
                    );
                    this.players[j.nombre_usuario].setInfo(info);
                } else {
                    this.players[j.nombre_usuario] = new Player(
                        j.nombre_usuario, parseFloat(j.pos_x), parseFloat(j.pos_y), avatar, false
                    );
                    this.players[j.nombre_usuario].setInfo(info);
                    huboCambios = true;
                }
            });

            
            Object.keys(this.players).forEach(id => {
                if (id !== this.player.id && !idsActivos.has(id)) {
                    this.players[id].destroy();
                    delete this.players[id];
                    huboCambios = true;
                }
            });

            if (huboCambios) this.updateOnlineUI();
        } catch (error) {
            console.error("No se pudieron actualizar los jugadores en linea:", error);
        }
    }

    
    setupMenuMusic() {
        const btnMusicaMenu = document.getElementById('btn-musica-menu');
        let interacted = false;

        
        const playOnFirstClick = () => {
            if (interacted) return;
            interacted = true;
            
            
            if (!document.getElementById('main-menu-view').classList.contains('hidden')) {
                if (!this.audio.isMusicPlaying) {
                    this.audio.toggleMusic();
                    if (btnMusicaMenu) btnMusicaMenu.style.opacity = "1";
                }
            }
            
            document.removeEventListener('click', playOnFirstClick);
        };

        document.addEventListener('click', playOnFirstClick);

        
        btnMusicaMenu?.addEventListener('click', (e) => {
            e.stopPropagation(); 
            interacted = true;   
            document.removeEventListener('click', playOnFirstClick);

            const isPlaying = this.audio.toggleMusic();
            btnMusicaMenu.style.opacity = isPlaying ? "1" : "0.5";
        });
    }

    
    setupAuth() {
        
        this.esModoRegistro = true;
        this.usuarioActivo = window.SESION_PHP?.nombre || null;

        const loginModal = document.getElementById('login-modal');
        const btnLoginNav = document.getElementById('login-btn-nav');
        const btnLogoutNav = document.getElementById('logout-btn-nav');
        const userAccountNav = document.getElementById('user-account-nav');
        const userAccountAvatar = document.getElementById('user-account-avatar');
        const userAccountName = document.getElementById('user-account-name');

        const btnCloseLogin = document.getElementById('btn-close-login');
        const btnTogglePass = document.getElementById('btn-toggle-pass');
        const btnToggleLink = document.getElementById('toggle-link');

        const loginBox = document.getElementById('login-box');
        const inputEmail = document.getElementById('reg-email');
        const inputPass = document.getElementById('reg-pass');

        const actualizarUI = () => {
            if (this.usuarioActivo) {
                btnLoginNav?.classList.add('hidden');
                userAccountNav?.classList.remove('hidden');
                if (userAccountName) userAccountName.textContent = this.usuarioActivo;
                if (userAccountAvatar) {
                    const baseAvatar = this.playerProfile?.avatar?.base || 'copo';
                    userAccountAvatar.src = `assets/sprites/bases/${baseAvatar}_idle.gif`;
                }
            } else {
                btnLoginNav?.classList.remove('hidden');
                userAccountNav?.classList.add('hidden');
            }
        };

        actualizarUI();

        btnLoginNav?.addEventListener('click', () => {
            if (!this.usuarioActivo) {
                this.audio.playSFX('click');
                loginModal.classList.remove('hidden');
            }
        });

        
        btnLogoutNav?.addEventListener('click', async (e) => {
            e.preventDefault();
            if (await UIDialog.confirm("Seguro que quieres cerrar sesion?", "!")) {
                window.location.href = btnLogoutNav.href;
            }
        });

        btnCloseLogin?.addEventListener('click', () => {
            this.audio.playSFX('click');
            loginModal.classList.add('hidden');
        });

        btnTogglePass?.addEventListener('click', () => {
            this.audio.playSFX('click');
            inputPass.type = inputPass.type === "password" ? "text" : "password";
        });

        
        btnToggleLink?.addEventListener('click', () => {
            this.audio.playSFX('click');
            this.esModoRegistro = !this.esModoRegistro;

            if (this.esModoRegistro) {
                loginBox.className = "login-box-img registro";
                inputEmail.style.display = "block";
                btnToggleLink.innerText = "Ya tienes cuenta? Entra aqui";
            } else {
                loginBox.className = "login-box-img login modo-login";
                inputEmail.style.display = "none";
                btnToggleLink.innerText = "Aun no tienes cuenta? Entra aqui";
            }
        });
    }

    
    setupProgressionSystem() {
        this.updateStatsUI();

        window.addEventListener('pomodoroCompleted', (e) => {
            if (e.detail.mode === 'study') {
                const expGained = e.detail.minutes * 3;
                this.addExperience(expGained); 
            }
        });

        window.addEventListener('pomodoroStudySecond', () => {
            this.playerProfile.stats.studySeconds = (Number(this.playerProfile.stats.studySeconds) || 0) + 1;
            this.studySecondsSinceSave++;
            this.updateLocalPlayerInfo();

            if (this.studySecondsSinceSave >= 15) {
                this.studySecondsSinceSave = 0;
                this.saveSystem.saveData(this.playerProfile);
            }
        });
    }

    addExperience(amount) {
        this.playerProfile.stats.exp += amount;
        const expNeeded = this.playerProfile.stats.level * 100;

        if (this.playerProfile.stats.exp >= expNeeded) {
            this.playerProfile.stats.level++;
            this.playerProfile.stats.exp -= expNeeded; 
            this.audio.playSFX('click'); 
            UIDialog.alert(`Felicidades! Has alcanzado el Nivel ${this.playerProfile.stats.level} !`, '!');
        }

        this.saveSystem.saveData(this.playerProfile);
        this.updateStatsUI();
    }

    updateStatsUI() {
        const levelBadge = document.querySelector('.level-badge');
        const expFill = document.querySelector('.exp-fill');
        const expText = document.querySelector('.exp-text'); 

        if (levelBadge && expFill && expText) {
            levelBadge.innerText = `LVL ${this.playerProfile.stats.level}`;

            const expNeeded = this.playerProfile.stats.level * 100;
            const expCurrent = this.playerProfile.stats.exp;
            const percentage = (expCurrent / expNeeded) * 100;
            
            expFill.style.transition = 'width 0.5s ease-in-out';
            expFill.style.width = `${Math.min(percentage, 100)}%`;
            expText.innerText = `${expCurrent} / ${expNeeded} EXP`;
        }
    }

    
    setupMenuControls() {
        const btnStart = document.getElementById('btn-start-game');
        const menuView = document.getElementById('main-menu-view');
        const gameView = document.getElementById('game-view');
        const gameStage = document.getElementById('game-stage');
        const roomSelector = document.getElementById('room-selector');
        const btnEnterRoom = document.getElementById('btn-enter-room');
        const selectedRoomLabel = document.getElementById('selected-room-label');
        const timerModal = document.getElementById('timer-setup-modal');
        const roomCards = document.querySelectorAll('.room-card');
        const roomInfo = document.getElementById('active-room-info');
        const roomInfoName = document.getElementById('active-room-name');
        const roomInfoType = document.getElementById('active-room-type');
        const roomPrivateData = document.getElementById('active-room-private-data');

        const actualizarSalaElegida = (card) => {
            roomCards.forEach(item => item.classList.remove('selected'));
            card.classList.add('selected');
            const roomName = card.querySelector('strong')?.textContent || 'Sala';
            const subject = card.dataset.subject || 'Libre';
            selectedRoomLabel.textContent = `${roomName} - ${subject}`;
            localStorage.setItem('cozyStudySelectedRoom', JSON.stringify({
                room: card.dataset.room,
                subject,
                label: roomName
            }));
            this.updateLocalPlayerInfo();
        };

        roomCards.forEach(card => {
            card.addEventListener('click', () => {
                this.audio.playSFX('click');
                actualizarSalaElegida(card);
            });
        });

        
        const showHomeMenu = () => {
            menuView?.classList.remove('hidden');
            gameView?.classList.add('hidden');
        };

        const showRoomSelector = () => {
            menuView?.classList.add('hidden');
            gameView?.classList.remove('hidden');
            roomSelector?.classList.remove('hidden');
            gameStage?.classList.add('hidden');
        };

        const showRoomStage = () => {
            const sala = this.getSelectedRoomData();
            if (roomInfo && roomInfoName && roomInfoType && !sala.idSala) {
                roomInfo.classList.remove('hidden');
                roomInfoName.textContent = sala.label || 'Biblioteca';
                roomInfoType.textContent = 'Publica';
                roomPrivateData?.classList.add('hidden');
            }
            menuView?.classList.add('hidden');
            gameView?.classList.remove('hidden');
            roomSelector?.classList.add('hidden');
            gameStage?.classList.remove('hidden');
            this.audio.cambiarMusica('assets/audio/lofi_sunset.mp3');

            if (!this.audio.isMusicPlaying) {
                this.audio.toggleMusic();
            }

            const btnMusic = document.getElementById('btn-music');
            if (btnMusic) btnMusic.style.backgroundColor = '#8fbc8f';
        };

        
        const getRoomUrlValue = (roomValue) => {
            if (roomValue) return roomValue;
            const sala = this.getSelectedRoomData();
            if (sala.idSala) return `sala-${sala.idSala}`;
            if (sala.room) return sala.room;
            return 'sala';
        };

        const buildViewUrl = (view, roomValue) => {
            if (view === 'selector') return 'index.php?salas=1#room-selector';
            if (view === 'room') return `index.php?salas=1&room=${encodeURIComponent(getRoomUrlValue(roomValue))}#sala-de-estudio`;
            return 'index.php?menu=1';
        };

        
        this.navigateTo = (view, { push = true, roomValue = null } = {}) => {
            if (view === 'room') showRoomStage();
            else if (view === 'selector') showRoomSelector();
            else showHomeMenu();

            const url = buildViewUrl(view, roomValue);
            const state = { view, room: view === 'room' ? getRoomUrlValue(roomValue) : null };

            if (push) {
                history.pushState(state, '', url);
            } else {
                history.replaceState(state, '', url);
            }
        };

        btnEnterRoom?.addEventListener('click', async () => {
            this.audio.playSFX('click');

            // Las salas de creación rápida (Biblioteca, Aula, Cafetería, Sala
            // silenciosa) solo existen en localStorage hasta este punto. Si
            // todavía no tienen id_sala, hay que crearlas/reutilizarlas en el
            // backend antes de entrar, para que queden registradas en la BD.
            const salaElegida = this.getSelectedRoomData();
            if (!salaElegida.idSala && window.CozyStudySalas?.asegurarSalaParaChat) {
                try {
                    await window.CozyStudySalas.asegurarSalaParaChat();
                } catch (error) {
                    console.error('No se pudo registrar la sala rápida en la base de datos:', error);
                }
            }

            this.navigateTo('room');
        });

        btnStart?.addEventListener('click', () => {
            this.audio.playSFX('click');

            if (!this.usuarioActivo) {
                UIDialog.alert("Hola! Por favor, registrate o inicia sesion para poder ir a estudiar.", "!");
                document.getElementById('login-modal').classList.remove('hidden');
                return; 
            }

            this.navigateTo('selector');
        });

        document.getElementById('btn-confirm-timer')?.addEventListener('click', () => {
            this.audio.playSFX('click');
            timerModal.classList.add('hidden'); 
            this.timer.reset(); 
            this.timer.start(); 
        });
    }

    
    setupHistoryNavigation() {
        window.addEventListener('popstate', (event) => {
            const view = event.state?.view || 'home';
            const roomValue = event.state?.room || null;
            this.navigateTo(view, { push: false, roomValue });
        });

        const initial = window.INITIAL_VIEW || { menu: true, salas: false, room: null };

        if (initial.room) {
            
            this.navigateTo('selector', { push: false });
            this.navigateTo('room', { push: true, roomValue: initial.room });
        } else if (initial.salas) {
            this.navigateTo('selector', { push: false });
        } else {
            this.navigateTo('home', { push: false });
        }
    }

    setupTimerControls() {
        const btnSetup = document.getElementById('btn-timer-setup');
        const modalTimer = document.getElementById('timer-setup-modal');
        const btnConfirm = document.getElementById('btn-confirm-timer');
        const btnCloseTimer = document.getElementById('btn-close-timer'); 

        
        if (btnSetup && modalTimer) {
            btnSetup.addEventListener('click', () => {
                this.audio.playSFX('click');
                modalTimer.classList.remove('hidden');
            });
        }

        
        if (btnCloseTimer && modalTimer) {
            btnCloseTimer.addEventListener('click', () => {
                this.audio.playSFX('click');
                modalTimer.classList.add('hidden');
            });
        }

        
        if (btnConfirm && modalTimer) {
            btnConfirm.addEventListener('click', () => {
                this.audio.playSFX('click');
                this.timer.reset(); 
                modalTimer.classList.add('hidden');
            });
        }

        
        const btnStart = document.getElementById('btn-start-timer');
        const btnPause = document.getElementById('btn-pause-timer');
        const btnReset = document.getElementById('btn-reset-timer');

        if (btnStart) btnStart.addEventListener('click', () => {
            this.audio.playSFX('click');
            this.timer.start();
        });
        if (btnPause) btnPause.addEventListener('click', () => {
            this.audio.playSFX('click');
            this.timer.pause();
        });
        if (btnReset) btnReset.addEventListener('click', () => {
            this.audio.playSFX('click');
            this.timer.reset();
        });
    }

    setupAiChatResize() {
        const aiChat = document.getElementById('aiChat');
        const handle = document.getElementById('ai-resize-handle');
        if (!aiChat || !handle) return;

        let resizing = false;
        let startX = 0;
        let startY = 0;
        let startWidth = 0;
        let startHeight = 0;

        const stopResize = () => {
            resizing = false;
            document.body.style.cursor = '';
        };

        handle.addEventListener('pointerdown', (e) => {
            const rect = aiChat.getBoundingClientRect();
            resizing = true;
            startX = e.clientX;
            startY = e.clientY;
            startWidth = rect.width;
            startHeight = rect.height;
            document.body.style.cursor = 'nesw-resize';
            handle.setPointerCapture?.(e.pointerId);
            e.preventDefault();
        });

        window.addEventListener('pointermove', (e) => {
            if (!resizing) return;
            const width = Math.min(Math.max(280, startWidth + (e.clientX - startX)), Math.min(800, window.innerWidth - 32));
            const height = Math.min(Math.max(250, startHeight - (e.clientY - startY)), window.innerHeight - 112);
            aiChat.style.width = `${width}px`;
            aiChat.style.height = `${height}px`;
            // El panel permanece anclado con "bottom" fijo (ver .ai-chat-box en css/ui.css),
            // por lo que no es necesario (ni conveniente) recalcular "top" manualmente aquí.
            e.preventDefault();
        });

        window.addEventListener('pointerup', stopResize);
        window.addEventListener('pointercancel', stopResize);
    }

    setupAudioControls() { 
        const btnToggleAudio = document.getElementById('btn-toggle-audio');
        const audioPanel = document.getElementById('audio-panel');
        
        btnToggleAudio?.addEventListener('click', () => {
            audioPanel.classList.toggle('hidden');
            this.audio.playSFX('click');
        });

        const btnMusic = document.getElementById('btn-music'); 
        const btnBirds = document.getElementById('btn-birds'); 
        const btnTyping = document.getElementById('btn-typing'); 

        btnMusic?.addEventListener('click', () => { 
            const isPlaying = this.audio.toggleMusic(); 
            btnMusic.style.backgroundColor = isPlaying ? '#8fbc8f' : ''; 
            this.audio.playSFX('click'); 
        }); 

        btnBirds?.addEventListener('click', () => { 
            const isPlaying = this.audio.toggleBirds(); 
            btnBirds.style.backgroundColor = isPlaying ? '#8fbc8f' : ''; 
            this.audio.playSFX('click'); 
        }); 

        btnTyping?.addEventListener('click', () => { 
            const isPlaying = this.audio.toggleTyping(); 
            btnTyping.style.backgroundColor = isPlaying ? '#8fbc8f' : ''; 
            this.audio.playSFX('click'); 
        }); 

        const volMusic = document.getElementById('vol-music');
        const volBirds = document.getElementById('vol-birds');
        const volTyping = document.getElementById('vol-typing');
        const volSFX = document.getElementById('vol-sfx');

        volMusic?.addEventListener('input', (e) => this.audio.setMusicVolume(e.target.value));
        volBirds?.addEventListener('input', (e) => this.audio.setBirdsVolume(e.target.value));
        volTyping?.addEventListener('input', (e) => this.audio.setTypingVolume(e.target.value));
        volSFX?.addEventListener('input', (e) => this.audio.setMasterSFXVolume(e.target.value));
    } 
    
    setupGlobalAudioEvents() { 
        window.addEventListener('playSFX', (e) => { this.audio.playSFX(e.detail); }); 
        window.addEventListener('pomodoroCompleted', () => { this.audio.playSFX('logro'); }); 

        
        window.addEventListener('aiThinkingChanged', (e) => {
            if (this.player) this.player.setAttentive(e.detail.thinking);
        });
    } 

    update(deltaTime) {
        if (!this.player) return; 
        if (!this.isStudyRoomOpen()) return;

       
        Object.values(this.players).forEach(p => {
            if (p.isLocal) p.update(deltaTime, this.room);
        });

        const playerHitbox = this.player.getHitbox();
        const aiChatBox = document.getElementById('aiChat');
        const roomInfo = document.getElementById('active-room-info');
        let nearDesk = false;

        for (let prop of this.room.props) {
            if (Physics.checkCollision(playerHitbox, prop) && prop.id === 'desk') {
                nearDesk = true;
            }
        }

        if (nearDesk && aiChatBox?.classList.contains('hidden')) {
            aiChatBox.classList.remove('hidden');
            roomInfo?.classList.add('hidden');
            window.dispatchEvent(new CustomEvent('profesorChatVisibilityChanged', { detail: { visible: true } }));
        }

        if (!nearDesk && aiChatBox && !aiChatBox.classList.contains('hidden')) {
            aiChatBox.classList.add('hidden');
            window.dispatchEvent(new CustomEvent('profesorChatVisibilityChanged', { detail: { visible: false } }));

            const sala = this.getSelectedRoomData();
            if (roomInfo && !sala.idSala) {
                const roomInfoName = document.getElementById('active-room-name');
                const roomInfoType = document.getElementById('active-room-type');
                const roomPrivateData = document.getElementById('active-room-private-data');
                roomInfoName.textContent = sala.label || 'Biblioteca';
                roomInfoType.textContent = 'Publica';
                roomPrivateData?.classList.add('hidden');
                roomInfo.classList.remove('hidden');
            }
        }
    }

    draw() {
        if (!this.player) return;
        if (!this.isStudyRoomOpen()) return;

        // Dibujamos a todos los jugadores.
        Object.values(this.players).forEach(p => p.draw());

        
        this.zoomLevel = window.innerHeight / this.room.worldHeight;
        const viewWidth = window.innerWidth / this.zoomLevel;

        let camX = (viewWidth / 2) - this.player.x - (this.player.width / 2);
        camX = Math.min(0, Math.max(camX, viewWidth - this.room.worldWidth));
        let camY = 0;

        this.worldContainer.style.transform = `scale(${this.zoomLevel}) translate(${camX}px, ${camY}px)`;
    }
    
    gameLoop(timestamp) {
        if (this.lastTime === 0) this.lastTime = timestamp; 
        
        const deltaTime = timestamp - this.lastTime;
        this.lastTime = timestamp;
        
        this.update(deltaTime);
        this.draw();
        
        requestAnimationFrame((timestamp) => this.gameLoop(timestamp));
    }

    async updateAvatarProperty(property, value) {
        this.playerProfile.avatar[property] = value;
        await this.saveSystem.saveData(this.playerProfile);
    }

    isStudyRoomOpen() {
        const gameView = document.getElementById('game-view');
        const gameStage = document.getElementById('game-stage');
        return Boolean(gameView && gameStage && !gameView.classList.contains('hidden') && !gameStage.classList.contains('hidden'));
    }
}

window.game = new GameManager();
