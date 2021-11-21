function slideToObjectAndAttach(game: Game, object: HTMLElement, destinationId: string, posX?: number, posY?: number) {
    return new Promise((resolve) => {

        const destination = document.getElementById(destinationId);
        if (destination.contains(object)) {
            return resolve(false);
        }
        if (document.visibilityState === 'hidden' || (game as any).instantaneousMode) {
            destination.appendChild(object);
            resolve(true);
        } else {

            object.style.zIndex = '10';
            const animation = (posX || posY) ? 
                (game as any).slideToObjectPos(object, destinationId, posX, posY) :
                (game as any).slideToObject(object, destinationId);

            dojo.connect(animation, 'onEnd', dojo.hitch(this, () => {
                object.style.top = 'unset';
                object.style.left = 'unset';
                object.style.position = 'relative';
                object.style.zIndex = 'unset';
                destination.appendChild(object);
                resolve(true);
            }));
            animation.play();
        }
    });
}

function transitionToObjectAndAttach(game: Game, object: HTMLElement, destinationId: string, zoom: number) {
    return new Promise((resolve) => {

        const destination = document.getElementById(destinationId);
        if (destination.contains(object)) {
            return resolve(false);
        }
        
        if (document.visibilityState === 'hidden' || (game as any).instantaneousMode) {
            destination.appendChild(object);
            resolve(true);
        } else {

            const destinationBR = document.getElementById(destinationId).getBoundingClientRect();
            const originBR = object.getBoundingClientRect();

            const deltaX = destinationBR.left - originBR.left;
            const deltaY = destinationBR.top - originBR.top;

            object.style.zIndex = '10';
            object.style.transition = `transform 0.5s linear`;
            object.style.transform = `translate(${deltaX/zoom}px, ${deltaY/zoom}px)`;

            setTimeout(() => {
                object.style.zIndex = null;
                object.style.transition = null;
                object.style.transform = null;
                destination.appendChild(object);
                resolve(true);
            }, 500);

    }
    });
}