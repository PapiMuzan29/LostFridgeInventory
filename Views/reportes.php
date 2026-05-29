<div class="modulo-reportes">

    <div class="panel-controles">
        
        <div class="filtros-busqueda">
            <div class="grupo-input">
                <label for="tipoReporte">Tipo de reporte</label>
                <div class="input-con-icono">
                    <select id="tipoReporte" name="tipoReporte">
                        <option value="todos">Todos los reportes</option>
                    </select>
                </div>
            </div>

            <div class="grupo-input">
                <label for="fechaInicio">Fecha inicio</label>
                <div class="input-fecha">
                    <input type="date" id="fechaInicio" name="fechaInicio" value="2026-05-01">
                </div>
            </div>

            <div class="grupo-input">
                <label for="fechaFin">Fecha fin</label>
                <div class="input-fecha">
                    <input type="date" id="fechaFin" name="fechaFin" value="2026-05-08">
                </div>
            </div>

            <div class="acciones-filtro">
                <button type="button" class="btn btn-filtrar">
                    <i class="icono-filtro"></i> Filtrar
                </button>
                <button type="button" class="btn btn-limpiar">
                    <i class="icono-limpiar"></i> Limpiar
                </button>
            </div>
        </div>

        <div class="acciones-principales">
            <button type="button" class="btn btn-generar">
                <i class="icono-mas"></i> Generar Nuevo Reporte
            </button>
        </div>

    </div>

    <div class="contenedor-tabla">
        <table class="tabla-reportes">
            <thead>
                <tr>
                    <th>Reporte</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Generado por</th>
                    <th>Fecha</th>
                    <th>Periodo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            
            <tbody id="tabla-documentos-body" >
                <tr><td>Cargando Reportes...</td></tr>                
            </tbody>
        </table>
    </div>

</div>