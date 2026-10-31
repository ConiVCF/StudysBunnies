export class Player {
    
    constructor(id, x, y, savedAvatarData, isLocal = true) {
        this.id = id;
        this.x = x;
        this.y = y;
        this.isLocal = isLocal;
        
        this.width = 105; 
        this.height = 105;
        this.speed = 200; 
        this.velocityY = 0;
        this.gravity = 800; 
        this.facing = 'right'; 
        this.state = 'idle'; 
        this.stepTimer = 0;
        this.stepInterval = 550; 
        this.isAttentive = false; 
        this.chatBubbleTimer = null;
        this.info = {
            name: id,
            age: null,
            subject: null,
            studySeconds: 0
        };

        this.appearance = savedAvatarData || { base: 'copo', clothing: 'none', accessory: 'none' };
        
        
        this.createDOMStructure();

       
        this.keys = { a: false, d: false };
        if (this.isLocal) {
            this.setupInputs();
        }

        this.chatBubbleHandler = (e) => {
            if (e.detail?.playerId === this.id) {
                this.showChatBubble(e.detail.message);
            }
        };
        window.addEventListener('roomChatBubble', this.chatBubbleHandler);

        this.closePopoverHandler = (e) => {
            if (!this.playerContainer?.contains(e.target)) {
                this.closeInfoPopover();
            }
        };
        this.otherPopoverHandler = (e) => {
            if (e.detail?.playerId !== this.id) {
                this.closeInfoPopover();
            }
        };
        document.addEventListener('click', this.closePopoverHandler);
        window.addEventListener('playerPopoverOpen', this.otherPopoverHandler);
        
        this.updateLayers();
    }

    createDOMStructure() {
        
        this.playerContainer = document.createElement('div');
        this.playerContainer.className = 'player-entity';
        this.playerContainer.id = `player-${this.id}`;
        this.playerContainer.tabIndex = 0;
        this.playerContainer.setAttribute('role', 'button');
        this.playerContainer.setAttribute('aria-label', `Ver informacion de ${this.id}`);

        
        this.baseLayer = document.createElement('img');
        this.clothingLayer = document.createElement('img');
        this.clothingLayer.className = 'hidden';
        this.accessoryLayer = document.createElement('img');
        this.accessoryLayer.className = 'hidden';

      
        const nameTag = document.createElement('div');
        nameTag.className = 'player-nametag';
        nameTag.innerText = this.id;

       
        this.playerContainer.appendChild(this.baseLayer);
        this.playerContainer.appendChild(this.clothingLayer);
        this.playerContainer.appendChild(this.accessoryLayer);
        this.playerContainer.appendChild(nameTag);

        this.playerContainer.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleInfoPopover();
        });

        
        document.getElementById('world-container').appendChild(this.playerContainer);
    }

    setInfo(info = {}) {
        this.info = {
            ...this.info,
            ...info,
            name: info.name || this.info.name || this.id,
            studySeconds: Number(info.studySeconds ?? this.info.studySeconds ?? 0)
        };
        this.renderInfoPopover();
    }

    formatStudyTime(seconds) {
        const total = Math.max(0, Number(seconds) || 0);
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        if (hours > 0) return `${hours} h ${minutes} min`;
        return `${minutes} min`;
    }

    toggleInfoPopover() {
        if (this.infoPopover?.classList.contains('is-open')) {
            this.closeInfoPopover();
            return;
        }
        window.dispatchEvent(new CustomEvent('playerPopoverOpen', { detail: { playerId: this.id } }));
        this.renderInfoPopover();
        this.infoPopover.classList.add('is-open');
    }

    closeInfoPopover() {
        this.infoPopover?.classList.remove('is-open');
    }

    renderInfoPopover() {
        if (!this.playerContainer) return;

        if (!this.infoPopover) {
            this.infoPopover = document.createElement('div');
            this.infoPopover.className = 'player-info-popover';
            this.infoPopover.addEventListener('click', (e) => e.stopPropagation());
            this.playerContainer.appendChild(this.infoPopover);
        }

        const age = this.info.age ? `${this.info.age} anios` : 'No cargada';
        const subject = this.info.subject || 'No cargada';
        this.infoPopover.innerHTML = '';

        const avatar = document.createElement('div');
        avatar.className = 'player-info-popover__avatar';
        [this.baseLayer, this.clothingLayer, this.accessoryLayer].forEach(layer => {
            const clone = layer.cloneNode(false);
            if (layer.classList.contains('hidden')) clone.classList.add('hidden');
            avatar.appendChild(clone);
        });

        const body = document.createElement('div');
        body.className = 'player-info-popover__body';

        const name = document.createElement('strong');
        name.textContent = this.info.name || this.id;
        body.appendChild(name);

        [
            ['Edad', age],
            ['Materia', subject],
            ['Estudio', this.formatStudyTime(this.info.studySeconds)]
        ].forEach(([label, value]) => {
            const row = document.createElement('span');
            row.textContent = `${label}: ${value}`;
            body.appendChild(row);
        });

        this.infoPopover.appendChild(avatar);
        this.infoPopover.appendChild(body);
    }

    showChatBubble(message) {
        if (!this.playerContainer || !message) return;

        let bubble = this.playerContainer.querySelector('.player-chat-bubble');
        if (!bubble) {
            bubble = document.createElement('div');
            bubble.className = 'player-chat-bubble';
            this.playerContainer.appendChild(bubble);
        }

        bubble.textContent = String(message).slice(0, 90);
        bubble.classList.remove('is-fading');
        bubble.classList.add('is-visible');

        clearTimeout(this.chatBubbleTimer);
        this.chatBubbleTimer = setTimeout(() => {
            bubble.classList.add('is-fading');
            bubble.classList.remove('is-visible');
        }, 3600);
    }

    setAppearance(type, value) {
        this.appearance[type] = value;
        this.updateLayers(); 
    }

    updateLayers() {
        const stateName = this.state === 'walking' ? 'walk' : 'idle';
        
        this.baseLayer.src = `assets/sprites/bases/${this.appearance.base}_${stateName}.gif`;
        
        if (this.appearance.clothing !== 'none') {
            this.clothingLayer.src = `assets/sprites/clothing/${this.appearance.clothing}_${stateName}.gif`;
            this.clothingLayer.classList.remove('hidden');
        } else {
            this.clothingLayer.classList.add('hidden');
        }

        if (this.appearance.accessory !== 'none') {
            this.accessoryLayer.src = `assets/sprites/accessories/${this.appearance.accessory}_${stateName}.gif`;
            this.accessoryLayer.classList.remove('hidden');
        } else {
            this.accessoryLayer.classList.add('hidden');
        }
    }

    setupInputs() {
        window.addEventListener('keydown', (e) => {
            if (this.isAttentive) return; 
            if (this.isTypingInField()) return; 
            const key = e.key.toLowerCase();
            if (key === 'a') { this.keys.a = true; this.facing = 'left'; }
            if (key === 'd') { this.keys.d = true; this.facing = 'right'; }
        });
        
        window.addEventListener('keyup', (e) => {
            const key = e.key.toLowerCase();
            if (key === 'a') this.keys.a = false;
            if (key === 'd') this.keys.d = false;
        });
    }

    
    isTypingInField() {
        const el = document.activeElement;
        return !!el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.isContentEditable);
    }

    
    setAttentive(active) {
        if (!this.isLocal) return; 
        this.isAttentive = active;

        if (active) {
            this.keys.a = false;
            this.keys.d = false;

            if (this.state !== 'idle') {
                this.state = 'idle';
                this.updateLayers();
            }

            this.facing = 'right'; 
            this.playerContainer.classList.add('attentive');
        } else {
            this.playerContainer.classList.remove('attentive');
        }
    }

    getHitbox() {
        return { x: this.x + 20, y: this.y + this.height - 10, width: 40, height: 10 };
    }

    update(deltaTime, room) {
        const dt = deltaTime / 1000; 
        let moveX = 0;
        let stateChanged = false;

        
        if (this.isLocal && this.isTypingInField()) {
            this.keys.a = false;
            this.keys.d = false;
        }
        
        if (this.keys.a) moveX -= this.speed * dt;
        if (this.keys.d) moveX += this.speed * dt;
        this.x += moveX;

        if (moveX !== 0) {
            if (this.state !== 'walking') {
                this.state = 'walking';
                stateChanged = true;
            }
            
            if (this.isLocal) {
                this.stepTimer += deltaTime;
                if (this.stepTimer >= this.stepInterval) {
                    window.dispatchEvent(new CustomEvent('playSFX', { detail: 'step' }));
                    this.stepTimer = 0;
                }
            }
        } else {
            if (this.state !== 'idle') {
                this.state = 'idle';
                stateChanged = true;
            }
            this.stepTimer = this.stepInterval; 
        }

        if (stateChanged) this.updateLayers();

        this.velocityY += this.gravity * dt;
        this.y += this.velocityY * dt;

        if (this.y + this.height >= room.floorY) {
            this.y = room.floorY - this.height;
            this.velocityY = 0;
        }

        if (this.x < 0) this.x = 0;
        if (this.x + this.width > room.worldWidth) this.x = room.worldWidth - this.width;
    }

    draw() {
        const scaleX = this.facing === 'left' ? -1 : 1;
        this.playerContainer.style.transform = `translate(${this.x}px, ${this.y}px) scaleX(${scaleX})`;
        
        
        const nameTag = this.playerContainer.querySelector('.player-nametag');
        if (nameTag) {
            nameTag.style.transform = `translateX(-50%) scaleX(${scaleX})`;
        }

        const chatBubble = this.playerContainer.querySelector('.player-chat-bubble');
        if (chatBubble) {
            chatBubble.style.transform = `translateX(-50%) scaleX(${scaleX})`;
        }

        if (this.infoPopover) {
            this.infoPopover.style.transform = `scaleX(${scaleX})`;
        }
    }

   
    applyRemoteState(x, y, facing, state, avatar) {
        this.x = x;
        this.y = y;
        this.facing = facing;

        const cambioApariencia = avatar && (
            avatar.base !== this.appearance.base ||
            avatar.clothing !== this.appearance.clothing ||
            avatar.accessory !== this.appearance.accessory
        );

        if (cambioApariencia) {
            this.appearance = avatar;
        }

        if (cambioApariencia || state !== this.state) {
            this.state = state;
            this.updateLayers();
        }
    }

   
    destroy() {
        window.removeEventListener('roomChatBubble', this.chatBubbleHandler);
        document.removeEventListener('click', this.closePopoverHandler);
        window.removeEventListener('playerPopoverOpen', this.otherPopoverHandler);
        clearTimeout(this.chatBubbleTimer);
        if (this.playerContainer && this.playerContainer.parentNode) {
            this.playerContainer.parentNode.removeChild(this.playerContainer);
        }
    }
}
