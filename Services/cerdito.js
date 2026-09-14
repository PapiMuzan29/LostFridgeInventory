 const cerdito = document.getElementById('cerditoCursor');
    let ultimaPosicionX = 0;
    document.addEventListener('mousemove', (evento) => 
    {
        cerdito.style.left = evento.clientX + 'px';
        cerdito.style.top = evento.clientY + 'px';

        if (evento.clientX < ultimaPosicionX) 
        {
            cerdito.style.transform = 'translate(-50%, -50%) scaleX(-1)';
        } 
        else if (evento.clientX > ultimaPosicionX)
        {
            cerdito.style.transform = 'translate(-50%, -50%) scaleX(1)';
        }
        ultimaPosicionX = evento.clientX;
    });