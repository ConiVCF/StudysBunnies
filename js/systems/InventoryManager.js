export class InventoryManager {
    constructor(gameManager) {
        this.game = gameManager; 
        
        this.modal = document.getElementById('inventory-modal');
        this.openBtn = document.getElementById('btn-inventory');
        this.closeBtn = document.getElementById('btn-close-inventory');
        this.saveBtn = document.getElementById('btn-save-inventory'); 

       
        this.previewBase = document.getElementById('preview-base');
        this.previewClothing = document.getElementById('preview-clothing');
        this.previewAccessory = document.getElementById('preview-accessory');

       
        this.tempAppearance = { base: 'copo', clothing: 'none', accessory: 'none' };
        
        this.setupEvents();
    }

    openModal() {
        
        this.tempAppearance = { ...this.game.player.appearance };
        this.updatePreview();
        this.modal.classList.remove('hidden');
        this.game.audio.playSFX('click');
    }

    updatePreview() {
        
        this.previewBase.src = `assets/sprites/bases/${this.tempAppearance.base}_idle.gif`;
        
        if (this.tempAppearance.clothing !== 'none') {
            this.previewClothing.src = `assets/sprites/clothing/${this.tempAppearance.clothing}_idle.gif`;
            this.previewClothing.classList.remove('hidden');
        } else {
            this.previewClothing.classList.add('hidden');
        }

        if (this.tempAppearance.accessory !== 'none') {
            this.previewAccessory.src = `assets/sprites/accessories/${this.tempAppearance.accessory}_idle.gif`;
            this.previewAccessory.classList.remove('hidden');
        } else {
            this.previewAccessory.classList.add('hidden');
        }
    }

    async saveChanges() {
        this.game.audio.playSFX('click');
        
        
        await this.game.updateAvatarProperty('base', this.tempAppearance.base);
        await this.game.updateAvatarProperty('clothing', this.tempAppearance.clothing);
        await this.game.updateAvatarProperty('accessory', this.tempAppearance.accessory);
        
       
        this.game.player.setAppearance('base', this.tempAppearance.base);
        this.game.player.setAppearance('clothing', this.tempAppearance.clothing);
        this.game.player.setAppearance('accessory', this.tempAppearance.accessory);

      
        this.modal.classList.add('hidden');
    }

    setupEvents() {
       
        this.openBtn?.addEventListener('click', () => this.openModal());
        this.closeBtn?.addEventListener('click', () => {
            this.modal.classList.add('hidden');
            this.game.audio.playSFX('click'); // Si cierras con la X, no se guarda nada
        });
        this.saveBtn?.addEventListener('click', () => this.saveChanges());

       
        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.tempAppearance.base = e.target.dataset.base;
                this.updatePreview();
                this.game.audio.playSFX('click');
            });
        });

        
        document.querySelectorAll('#clothing-picker .item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.tempAppearance.clothing = e.target.dataset.item;
                this.updatePreview();
                this.game.audio.playSFX('click');
            });
        });

       
        document.querySelectorAll('#accessory-picker .item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.tempAppearance.accessory = e.target.dataset.item;
                this.updatePreview();
                this.game.audio.playSFX('click');
            });
        });
    }
}