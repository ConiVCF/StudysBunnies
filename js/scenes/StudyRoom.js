export class StudyRoom {
    constructor() {
        
        this.worldWidth = 1500; 
        this.worldHeight = 415; 
        this.floorY = 405; 

        this.props = [
            { 
                id: 'desk', 
                type: 'solid', 
                x: 1050, 
                y: 280, 
                width: 330, 
                height: 130 
            }
        ];
        
        this.isLoaded = true; 
    }

    draw() {
        
    }
}