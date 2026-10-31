export class APIManager {
    constructor() {
        
        this.endpoint = 'backend/controllers/AIController.php';
    }

    
    async askQuestion(conversationHistory) {
        let attempt = 0;
        const maxRetries = 3; 

        while (attempt < maxRetries) {
            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversationHistory: conversationHistory
                    })
                });

                if (!response.ok) {
                    
                    if (response.status >= 500 || response.status === 429 || response.status === 503) {
                        console.warn(`[API] Servidor congestionado (Error ${response.status}). Reintento ${attempt + 1}/${maxRetries} en ${delay}ms...`);
                        attempt++;
                        if (attempt >= maxRetries) break; 

                        await new Promise(res => setTimeout(res, delay));
                        delay *= 2; 
                        continue;
                    }

                    
                    if (response.status === 401) {
                        return "Necesitas iniciar sesión para hablar conmigo. 🐾";
                    }

                    
                    return `Ups, Error ${response.status}: Algo falló con la estructura de la consulta. 🔧`;
                }

                const data = await response.json();
                if (data.candidates && data.candidates.length > 0) {
                    return data.candidates[0].content.parts[0].text;
                } else {
                    return "Me quedé en blanco por un momento... ¿Puedes repetirlo? 🐾";
                }

            } catch (error) {
                console.error("Error de Red en APIManager:", error);
                attempt++;
                if (attempt >= maxRetries) {
                    return "No me pude conectar a internet tras varios intentos. Revisa tu conexión. 🌧️";
                }
                await new Promise(res => setTimeout(res, delay));
                delay *= 2;
            }
        }

        
        return "El servidor está muy congestionado en este momento (Error 503). Por favor, intenta de nuevo en unos segundos. 🐈";
    }

    
    async askQuiz(conversationHistory) {
        let attempt = 0;
        const maxRetries = 3;
        let delay = 1000;

        while (attempt < maxRetries) {
            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversationHistory: conversationHistory,
                        modo: 'quiz'
                    })
                });

                if (!response.ok) {
                    if (response.status >= 500 || response.status === 429 || response.status === 503) {
                        attempt++;
                        if (attempt >= maxRetries) break;
                        await new Promise(res => setTimeout(res, delay));
                        delay *= 2;
                        continue;
                    }
                    return null; 
                }

                const data = await response.json();
                if (data.candidates && data.candidates.length > 0) {
                    const rawText = data.candidates[0].content.parts[0].text;
                    try {
                        return JSON.parse(rawText);
                    } catch (parseError) {
                        console.error("[API] El quiz no vino en JSON válido:", parseError, rawText);
                        return null;
                    }
                }
                return null;

            } catch (error) {
                console.error("Error de Red pidiendo el quiz:", error);
                attempt++;
                if (attempt >= maxRetries) return null;
                await new Promise(res => setTimeout(res, delay));
                delay *= 2;
            }
        }

        return null;
    }
}
