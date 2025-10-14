
<?php
                                           // Reemplace con su clave de API. Debe tener las APIs de Maps JS y Places habilitadas.
$claveApi = getenv('GOOGLE_MAPS_API_KEY') ?: 'aca_va_la clave';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Rutas Optimizadas — Ejemplo 2</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" /> 
    <link rel="stylesheet" href="../css/index.css">

    
</head>
<body>
<header>
    <div>
        <h1>Ejemplo 2 — Rutas Optimizadas (Google Maps)</h1>
    </div>
</header>

<main>
    <!-- MAPA -->
    <div id="mapa"></div>

    <!-- PANEL LATERAL -->
    <aside id="panel-lateral">
        <h2 style="margin:0 0 6px 0">Configurar ruta</h2>

        <!-- Contenedor donde se insertará PlaceAutocompleteElement (origen) -->
        <label for="origen">Origen</label>
        <div id="origen-contenedor" class="control">
            <!-- PlaceAutocompleteElement reemplazará este contenedor con su input -->
            <input id="origen-input-fallback" class="input-texto" placeholder="Escribí la dirección de origen (ej: Depósito)" />
        </div>

        <!-- Contenedor destino -->
        <label for="destino">Destino</label>
        <div id="destino-contenedor" class="control">
            <input id="destino-input-fallback" class="input-texto" placeholder="Escribí la dirección de destino (ej: Planta)" />
        </div>

        <!-- Paradas (inputs text simples, el usuario puede escribir direcciones) -->
        <label for="paradas">Paradas intermedias</label>
        <div class="small">Agrega direcciones separadas por coma o usa + Parada para entradas individuales.</div>
        <div class="fila" style="margin-top:8px">
            <button id="btnAgregarParada" class="boton">+ Parada</button>
            <button id="btnLimpiarParadas" class="boton secundario">Limpiar paradas</button>
        </div>

        <div id="contenedor-paradas" class="paradas-contenedor"></div>

        <div class="fila" style="margin-top:10px">
            <label style="display:flex; align-items:center; gap:8px"><input id="chkOptimizar" type="checkbox" checked /> <span class="small">Optimizar orden de paradas</span></label>
            <label style="display:flex; align-items:center; gap:8px"><input id="chkUsarTrafico" type="checkbox" checked /> <span class="small">Considerar tráfico (departureTime)</span></label>
        </div>

        <!-- Información del conductor y camión -->
        <label for="conductor">Conductor</label>
        <input id="conductor" class="input-texto" placeholder="Nombre del conductor" />

        <label for="camion">Camión</label>
        <input id="camion" class="input-texto" placeholder="Matrícula del camión" />

        <div class="fila" style="margin-top:12px">
            <button id="btnCalcular" class="boton">Calcular ruta</button>
            <button id="btnBorrarRuta" class="boton secundario">Borrar ruta</button>
        </div>

        <div id="panel-resultado">
            <div id="resumen-general" class="info">Aún no se calculó ninguna ruta.</div>

            <div id="estadisticas-detalladas" style="margin-top:8px; display:block">
                <div class="small">Datos:</div>
                <div id="datos-resumen" style="margin-top:6px"></div>

                <table id="tabla-tramos">
                    <thead>
                        <tr>
                            <th>Tramo</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Distancia</th>
                            <th>Duración</th>
                            <th>Hora llegada (tramo)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div style="margin-top:8px" class="small">Acciones:</div>
                <div class="acciones">
                    <button id="btnGuardar" class="boton">Guardar ruta (API)</button>
                    <button id="btnDescargar" class="boton secundario">Descargar JSON</button>
                </div>

                <div id="mensaje-guardar" class="small" style="margin-top:8px;color:#065f46"></div>
            </div>
        </div>

        <div style="margin-top:10px" class="small">
            Notas: activa <strong>Maps JavaScript API</strong>, <strong>Directions API</strong> y <strong>Places (New)</strong> en Google Cloud. Para cálculo de eficiencia se usa la biblioteca <em>geometry</em>.
        </div>
    </aside>
</main>

<script>
/*
  index.php (JavaScript)
  - Todas las variables, funciones y comentarios en español.
  - Usa PlaceAutocompleteElement + creación de Place (fetchFields) para obtener dirección y coordenadas.
  - Calcula ETA usando duration_in_traffic si está disponible, sino duration.
  - Presenta tabla de tramos con hora estimada por tramo y resumen global.
*/

// variables globales
let mapa;
let servicioDirecciones;
let renderizadorDirecciones;
let elementoAutocompleteOrigen = null;
let elementoAutocompleteDestino = null;
let datosOrigen = null;   // {direccion: string, location: {lat,lng}}
let datosDestino = null;  // {direccion: string, location: {lat,lng}}
let listaParadas = [];    // array de strings (direcciones)
/**
 * crearInputParada(valor)
 * Crea un input para una parada intermedia con botón de eliminar.
 */
function crearInputParada(valor = '') {
    const cont = document.createElement('div');
    cont.className = 'parada-item';

    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Dirección parada intermedia';
    input.value = valor;

    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.textContent = 'X';
    btnEliminar.title = 'Eliminar parada';
    btnEliminar.onclick = () => {
        cont.remove();
        sincronizarParadasDesdeDOM();
    };

    cont.appendChild(input);
    cont.appendChild(btnEliminar);
    return cont;
}

/**
 * sincronizarParadasDesdeDOM()
 * Lee los inputs y actualiza listaParadas
 */
function sincronizarParadasDesdeDOM() {
    const cont = document.getElementById('contenedor-paradas');
    const inputs = Array.from(cont.querySelectorAll('input'));
    listaParadas.length = 0;
    inputs.forEach(i => {
        const v = i.value.trim();
        if (v) listaParadas.push(v);
    });
}

/**
 * limpiarParadasDOM()
 */
function limpiarParadasDOM() {
    document.getElementById('contenedor-paradas').innerHTML = '';
    listaParadas.length = 0;
}

/**
 * agregarParadaDOM(valor)
 */
function agregarParadaDOM(valor = '') {
    const nodo = crearInputParada(valor);
    document.getElementById('contenedor-paradas').appendChild(nodo);
       // Obtenemos el <input> que acabamos de crear dentro del 'nodo' y le aplicamos el Autocomplete.
    const nuevoInput = nodo.querySelector('input');
    new google.maps.places.Autocomplete(nuevoInput);

    sincronizarParadasDesdeDOM();
}

/**
 * mostrarMensajeTemporal(texto, color)
 */
function mostrarMensajeTemporal(texto, color = '#064e3b') {
    const msg = document.getElementById('mensaje-guardar');
    msg.style.color = color;
    msg.textContent = texto;
    setTimeout(() => { msg.textContent = ''; }, 7000);
}

/**
 * iniciarMapa()
 * Callback invocado por la librería de Google Maps (js). Inicializa mapas y Autocomplete moderno.
 */
async function iniciarMapa() {
    try {
        // Importar módulos modernos (recomendado)
        await google.maps.importLibrary('maps');
        const placesModule = await google.maps.importLibrary('places');
        await google.maps.importLibrary('geometry'); // para calcularDistanciaEntre

        // Crear mapa
        mapa = new google.maps.Map(document.getElementById('mapa'), {
        center: { lat: -31.6233, lng: -60.7050 },
        zoom: 12,
        mapTypeControl: false
        });


        servicioDirecciones = new google.maps.DirectionsService();
        renderizadorDirecciones = new google.maps.DirectionsRenderer({ suppressMarkers: false, preserveViewport: false });
        renderizadorDirecciones.setMap(mapa);
        renderizadorDirecciones.setPanel(null); // nosotros mostramos detalles en nuestra interfaz

        // Crear PlaceAutocompleteElement para ORIGEN
        try {
            elementoAutocompleteOrigen = new google.maps.places.PlaceAutocompleteElement();
            // Reemplazamos la entrada de respaldo por el widget (este widget crea su propia entrada)
            const contOrigen = document.getElementById('origen-contenedor');
            contOrigen.innerHTML = ''; // limpiamos
            contOrigen.appendChild(elementoAutocompleteOrigen);

            elementoAutocompleteOrigen.addEventListener('gmp-select', async ({ placePrediction }) => {
                // Crear Place a partir de placeId y solicitar campos
                try {
                    const { Place } = await google.maps.importLibrary('places');
                    const placeObj = new Place({ id: placePrediction.placeId });
                    await placeObj.fetchFields({ fields: ['formattedAddress', 'location'] });
                    const loc = placeObj.location?.toJSON ? placeObj.location.toJSON() : placeObj.location;
                    datosOrigen = { direccion: placeObj.formattedAddress || placePrediction.description || '', location: loc };
                    console.log('Origen seleccionado:', datosOrigen);
                } catch (err) {
                    console.error('Error obteniendo Place completo (origen):', err);
                    alert('No se pudo obtener datos completos del origen. Intentá escribir la dirección completa manualmente.');
                }
            });
        } catch (err) {
            console.warn('PlaceAutocompleteElement no disponible — intentando respaldo Autocomplete (legacy).', err);
            // Respaldo: asociar Autocomplete clásico a la entrada
            const inputFallback = document.getElementById('origen-input-fallback');
            const autoLegacyOrigin = new google.maps.places.Autocomplete(inputFallback);
            autoLegacyOrigin.addListener('place_changed', function () {
                const p = autoLegacyOrigin.getPlace();
                if (p && p.formatted_address) {
                    datosOrigen = { direccion: p.formatted_address, location: p.geometry ? p.geometry.location.toJSON() : null };
                } else if (p && p.name) {
                    datosOrigen = { direccion: p.name, location: p.geometry ? p.geometry.location.toJSON() : null };
                }
            });
        }

        // Crear PlaceAutocompleteElement para DESTINO
        try {
            elementoAutocompleteDestino = new google.maps.places.PlaceAutocompleteElement();
            const contDestino = document.getElementById('destino-contenedor');
            contDestino.innerHTML = '';
            contDestino.appendChild(elementoAutocompleteDestino);

            elementoAutocompleteDestino.addEventListener('gmp-select', async ({ placePrediction }) => {
                try {
                    const { Place } = await google.maps.importLibrary('places');
                    const placeObj = new Place({ id: placePrediction.placeId });
                    await placeObj.fetchFields({ fields: ['formattedAddress', 'location'] });
                    const loc = placeObj.location?.toJSON ? placeObj.location.toJSON() : placeObj.location;
                    datosDestino = { direccion: placeObj.formattedAddress || placePrediction.description || '', location: loc };
                    console.log('Destino seleccionado:', datosDestino);
                } catch (err) {
                    console.error('Error obteniendo Place completo (destino):', err);
                    alert('No se pudo obtener datos completos del destino. Intentá escribir la dirección completa manualmente.');
                }
            });
        } catch (err) {
            console.warn('PlaceAutocompleteElement no disponible para destino — respaldo Autocomplete (legacy).', err);
            const inputFallback = document.getElementById('destino-input-fallback');
            const autoLegacyDest = new google.maps.places.Autocomplete(inputFallback);
            autoLegacyDest.addListener('place_changed', function () {
                const p = autoLegacyDest.getPlace();
                if (p && p.formatted_address) {
                    datosDestino = { direccion: p.formatted_address, location: p.geometry ? p.geometry.location.toJSON() : null };
                } else if (p && p.name) {
                    datosDestino = { direccion: p.name, location: p.geometry ? p.geometry.location.toJSON() : null };
                }
            });
        }

        // Eventos de interfaz
        document.getElementById('btnAgregarParada').addEventListener('click', () => agregarParadaDOM(''));
        document.getElementById('btnLimpiarParadas').addEventListener('click', limpiarParadasDOM);
        document.getElementById('btnCalcular').addEventListener('click', calcularRutaOptimizada);
        document.getElementById('btnBorrarRuta').addEventListener('click', () => {
            renderizadorDirecciones.set('directions', null);
            document.getElementById('resumen-general').textContent = 'Ruta borrada.';
            document.querySelector('#tabla-tramos tbody').innerHTML = '';
            document.getElementById('datos-resumen').innerHTML = '';
        });
        document.getElementById('btnGuardar').addEventListener('click', guardarRutaAPI);
        document.getElementById('btnDescargar').addEventListener('click', descargarRutaJSON);

        // Mensaje inicial
        console.log('✅ Google Maps inicializado correctamente');
    } catch (err) {
        console.error('Error inicializando Google Maps:', err);
        alert('No se pudo inicializar Google Maps. Revisá la consola y la API Key.');
    }
}

/**
 * validarOrigenDestino()
 * Acepta tanto datosOrigen/datosDestino (selección via Place) como entradas manuales si los widgets de respaldo están presentes.
 */
function validarOrigenDestino() {
    // Si no tenemos datosOrigen por Place, intentar leer la entrada de respaldo (en caso de que PlaceAutocompleteElement no esté)
    if (!datosOrigen) {
        const fallback = document.getElementById('origen-input-fallback');
        if (fallback && fallback.value.trim()) {
            datosOrigen = { direccion: fallback.value.trim(), location: null };
        }
    }
    if (!datosDestino) {
        const fallback = document.getElementById('destino-input-fallback');
        if (fallback && fallback.value.trim()) {
            datosDestino = { direccion: fallback.value.trim(), location: null };
        }
    }

    if (!datosOrigen || !datosOrigen.direccion) {
        alert('Debés seleccionar o escribir un origen válido.');
        return false;
    }
    if (!datosDestino || !datosDestino.direccion) {
        alert('Debés seleccionar o escribir un destino válido.');
        return false;
    }
    return true;
}

/**
 * construirSolicitudDirecciones()
 * Construye y devuelve el objeto solicitud para DirectionsService.route()
 */
function construirSolicitudDirecciones() {
    sincronizarParadasDesdeDOM(); // actualizar listaParadas desde entradas
    const optimizar = document.getElementById('chkOptimizar').checked;
    const considerarTrafico = document.getElementById('chkUsarTrafico').checked;

    // construir puntos de parada (strings). Limitar a un número prudente (ej: 23 max en algunas cuentas)
    const waypoints = listaParadas.map(p => ({ location: p, stopover: true }));

    const solicitud = {
        origin: datosOrigen.direccion,
        destination: datosDestino.direccion,
        waypoints: waypoints,
        optimizeWaypoints: optimizar,
        travelMode: google.maps.TravelMode.DRIVING
    };

    if (considerarTrafico) {
    solicitud.drivingOptions = { 
        departureTime: new Date(),
        trafficModel: 'bestguess'   // ✅ correcto
    };
}


    return solicitud;
}

/**
 * calcularRutaOptimizada()
 * Llama a DirectionsService y procesa la respuesta para mostrar estadísticas.
 */
function calcularRutaOptimizada() {
    // validar
    if (!validarOrigenDestino()) return;

    const solicitud = construirSolicitudDirecciones();

    servicioDirecciones.route(solicitud, (respuesta, estado) => {
        if (estado === 'OK') {
            // renderizar en mapa
            try {
                renderizadorDirecciones.setDirections(respuesta);
            } catch (err) {
                console.error('setDirections falló:', err);
            }
            procesarRespuestaYMostrarEstadisticas(respuesta, solicitud);
        } else {
            console.error('DirectionsService fallo:', estado, respuesta);
            alert('No se pudo calcular la ruta. Estado: ' + estado);
        }
    });
}

/**
 * procesarRespuestaYMostrarEstadisticas(respuesta, solicitud)
 * Extrae legs, calcula totales, ETA, eficiencia, y actualiza UI.
 */
function procesarRespuestaYMostrarEstadisticas(respuesta, solicitud) {
    const ruta = respuesta.routes[0];
    if (!ruta) {
        alert('Respuesta de ruta vacía.');
        return;
    }

    // Resumen: legs
    const tramos = ruta.legs || [];
    let distanciaTotal_m = 0;
    let duracionTotal_s = 0;

    // usamos Date de salida (ahora) o la que el sistema quiera
    const horaSalida = new Date();

    // Limpiar tabla
    const tbody = document.querySelector('#tabla-tramos tbody');
    tbody.innerHTML = '';

    // recorrer tramos y determinar duración por tramo preferentemente con duration_in_traffic
    for (let i = 0, acumuladoAntes = 0; i < tramos.length; i++) {
        const tramo = tramos[i];
        const duracion_seg = (tramo.duration_in_traffic && tramo.duration_in_traffic.value) ? tramo.duration_in_traffic.value : tramo.duration.value;
        const duracion_texto = (tramo.duration_in_traffic && tramo.duration_in_traffic.text) ? tramo.duration_in_traffic.text : tramo.duration.text;
        distanciaTotal_m += tramo.distance.value;
        duracionTotal_s += duracion_seg;

        // calcular hora llegada del tramo (horaSalida + acumuladoAntes + duración_seg)
        const inicioTramo_ms = horaSalida.getTime() + (acumuladoAntes * 1000);
        const finTramo_ms = horaSalida.getTime() + ((acumuladoAntes + duracion_seg) * 1000);
        const horaLlegadaTramo = new Date(finTramo_ms);

        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${i + 1}</td>
            <td>${tramo.start_address}</td>
            <td>${tramo.end_address}</td>
            <td>${tramo.distance.text}</td>
            <td>${duracion_texto}</td>
            <td>${horaLlegadaTramo.toLocaleTimeString()}</td>
        `;
        tbody.appendChild(fila);

        acumuladoAntes += duracion_seg;
    }

    // calcular hora llegada total
    const horaLlegadaTotal = new Date(horaSalida.getTime() + duracionTotal_s * 1000);

    // calcular eficiencia: distancia en línea recta entre origen y destino / distancia total de ruta
    // usando geometry.spherical.computeDistanceBetween
    let distanciaRecta_m = 0;
    try {
        // si la respuesta incluye start_location y end_location
        const inicioLeg = tramos[0].start_location;
        const finLeg = tramos[tramos.length - 1].end_location;
        // crear LatLng si es necesario:
        const latLngInicio = new google.maps.LatLng(inicioLeg.lat || inicioLeg.lat(), inicioLeg.lng || inicioLeg.lng());
        const latLngFin = new google.maps.LatLng(finLeg.lat || finLeg.lat(), finLeg.lng || finLeg.lng());
        distanciaRecta_m = google.maps.geometry.spherical.computeDistanceBetween(latLngInicio, latLngFin);
    } catch (err) {
        console.warn('No se pudo calcular distancia recta (geometry).', err);
        distanciaRecta_m = 0;
    }

    const eficiencia = distanciaRecta_m > 0 ? (distanciaRecta_m / distanciaTotal_m) * 100 : 0;

    // velocidad promedio (km/h)
    const velocidadPromedio_kmh = (distanciaTotal_m / 1000) / (duracionTotal_s / 3600 || 1);

    // número de paradas (origen + paradas + destino)
    const numeroParadas = tramos.length + 1;

    // mostrar en UI
    document.getElementById('resumen-general').textContent = `Ruta calculada: ${tramos.length} tramos — ${numeroParadas} puntos.`;
    document.getElementById('datos-resumen').innerHTML = `
        <div><strong>Distancia total:</strong> ${(distanciaTotal_m / 1000).toFixed(2)} km</div>
        <div><strong>Duración total:</strong> ${Math.round(duracionTotal_s / 60)} min</div>
        <div><strong>Hora de salida:</strong> ${horaSalida.toLocaleTimeString()}</div>
        <div><strong>Hora estimada de llegada:</strong> ${horaLlegadaTotal.toLocaleTimeString()}</div>
        <div><strong>Eficiencia (recta / ruta):</strong> ${eficiencia.toFixed(2)}%</div>
        <div><strong>Velocidad promedio:</strong> ${velocidadPromedio_kmh.toFixed(2)} km/h</div>
    `;

    // almacenar en un objeto global para guardado o descarga
    window.ultimaRutaCalculada = {
        respuestaCompleta: respuesta,
        resumen: {
            distancia_total_m: distanciaTotal_m,
            duracion_total_s: duracionTotal_s,
            hora_salida_iso: horaSalida.toISOString(),
            hora_llegada_iso: horaLlegadaTotal.toISOString(),
            eficiencia: eficiencia,
            velocidad_promedio_kmh: velocidadPromedio_kmh,
            numero_tramos: tramos.length,
            numero_puntos: numeroParadas,
            waypoints: listaParadas.slice()
        }
    };

    console.log('Ruta procesada:', window.ultimaRutaCalculada);
}

/**
 * guardarRutaAPI()
 * Envía la ruta calculada a api-ruta.php (POST JSON). Si api-ruta.php no existe, muestra error.
 */
async function guardarRutaAPI() {
    if (!window.ultimaRutaCalculada) {
        alert('Primero calculá una ruta antes de guardarla.');
        return;
    }
    // Obtener datos del conductor y camión
    const conductor = document.getElementById('conductor').value.trim() || 'Conductor Sin Asignar';
    const camion = document.getElementById('camion').value.trim() || 'CAM-' + Math.floor(Math.random() * 999).toString().padStart(3, '0');
    
    const payload = {
        origin: datosOrigen ? datosOrigen.direccion : null,
        destination: datosDestino ? datosDestino.direccion : null,
        waypoints: listaParadas.map(p => ({ location: p, estimado_kg: 25, tipo_residuo: 'general', prioridad: 'normal' })),
        hora_salida: new Date().toISOString(),
        resumen: window.ultimaRutaCalculada.resumen,
        fecha_guardado: new Date().toISOString(),
        conductor: conductor,
        camion: camion
    };

    try {
        const resp = await fetch('api-ruta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (!resp.ok) {
            const text = await resp.text();
            mostrarMensajeTemporal(`Error guardando ruta: ${resp.status} ${text}`, '#b91c1c');
            return;
        }
        const data = await resp.json();
        mostrarMensajeTemporal('Ruta guardada correctamente (ID: ' + (data.id ?? 'sin-id') + ')', '#065f46');
    } catch (err) {
        console.error('Error al llamar api-ruta.php:', err);
        mostrarMensajeTemporal('Error al guardar ruta: revisá la consola.', '#b91c1c');
    }
}

/**
 * descargarRutaJSON()
 * Genera y descarga un archivo JSON con los detalles de la última ruta calculada.
 */
function descargarRutaJSON() {
    if (!window.ultimaRutaCalculada) {
        alert('No hay ruta calculada para descargar.');
        return;
    }
    const data = {
        origen: datosOrigen ? datosOrigen.direccion : null,
        destino: datosDestino ? datosDestino.direccion : null,
        resumen: window.ultimaRutaCalculada.resumen,
        respuesta: window.ultimaRutaCalculada.respuestaCompleta
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ruta_optimizada.json';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}

/* Exponer iniciarMapa globalmente para el callback de Google Maps */
window.iniciarMapa = iniciarMapa;
</script>

<!-- Cargar la librería: usamos libraries=places,geometry y callback=iniciarMapa
     Recomendado: restringir la API key por HTTP referrers en Google Cloud Console. -->
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($claveApi, ENT_QUOTES); ?>&libraries=places,geometry&callback=iniciarMapa&v=weekly&loading=async"
    async defer></script>
</body>
</html>
