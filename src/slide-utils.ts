function slideToObjectAndAttach(game: Game, object: HTMLElement, destinationId: string, posX?: number, posY?: number) {
    return new Promise((resolve) => {

        const destination = document.getElementById(destinationId);
        if (destination.contains(object)) {
            return resolve(false);
        }

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
    });
}