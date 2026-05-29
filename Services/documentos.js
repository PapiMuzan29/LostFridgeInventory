function rendederizarFilas(datos){
    const tbody = documetn.getElementById('tabla-documentos-body');
    tbody.innerHTML = '';
    
    if(datos.length === 0){
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px;">No se encontraron registros.</td></tr>`;
        return;
    }

    datos.forEach(doc => {
        
    });
        
    
}