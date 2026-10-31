export class SaveSystem {
    constructor() {
        // El progreso ya NO se guarda en localStorage (eso hacia que el progreso
        // fuera local al navegador y no correspondiera al usuario autenticado).
        // Ahora se guarda y se recupera desde el backend, usando la sesion del
        // usuario logueado, para que cada usuario tenga su propio progreso.
        this.loadEndpoint = 'cargar_progreso.php';
        this.saveEndpoint = 'guardar_progreso.php';

        this.defaultState = {
            uuid: this.generateUUID(),
            avatar: {
                base: 'copo',
                clothing: 'none',
                accessory: 'none'
            },
            stats: { level: 1, exp: 0, coins: 0, studySeconds: 0 },
            settings: { musicVolume: 0.5, sfxVolume: 0.8 },
            missions: []
        };
    }

    async loadData() {
        try {
            const respuesta = await fetch(this.loadEndpoint, {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (!respuesta.ok) {
                console.error('Error cargando el progreso del usuario:', respuesta.status);
                return { ...this.defaultState };
            }

            const resultado = await respuesta.json();

            if (resultado && resultado.ok && resultado.data) {
                // Combina lo guardado con los valores por defecto, por si se
                // agregan nuevos campos en el futuro que el usuario aun no tiene.
                const datosGuardados = resultado.data;
                return {
                    ...this.defaultState,
                    ...datosGuardados,
                    avatar: { ...this.defaultState.avatar, ...(datosGuardados.avatar || {}) },
                    stats: { ...this.defaultState.stats, ...(datosGuardados.stats || {}) },
                    settings: { ...this.defaultState.settings, ...(datosGuardados.settings || {}) },
                    missions: datosGuardados.missions || []
                };
            }

            // El usuario todavia no tiene progreso guardado: se crea uno nuevo (Nivel 1)
            // y se persiste inmediatamente para ese usuario.
            await this.saveData(this.defaultState);
            return { ...this.defaultState };
        } catch (error) {
            console.error('Error leyendo el progreso del usuario:', error);
            return { ...this.defaultState };
        }
    }

    async saveData(data) {
        try {
            const respuesta = await fetch(this.saveEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (!respuesta.ok) {
                console.error('Error guardando el progreso del usuario:', respuesta.status);
                return false;
            }

            const resultado = await respuesta.json();
            return Boolean(resultado && resultado.ok);
        } catch (error) {
            console.error('Error guardando datos:', error);
            return false;
        }
    }

    generateUUID() {
        return Math.random().toString(36).substring(2, 15);
    }
}
