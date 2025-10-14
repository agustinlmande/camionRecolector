<?php
declare(strict_types=1);

/**
 * Clase GestorRutas - Gestiona múltiples rutas de recolección de residuos
 * 
 * Esta clase implementa el patrón Repository para manejar operaciones CRUD
 * y optimización de múltiples rutas de residuos.
 */
class GestorRutas {
    private array $rutas = [];
    private string $archivo_datos;
    private int $contador_id = 1;

    /**
     * Constructor de la clase
     * 
     * @param string $archivo_datos Ruta al archivo de persistencia de datos
     */
    public function __construct(string $archivo_datos = 'datos_rutas.json') {
        $this->archivo_datos = $archivo_datos;
        $this->cargarRutas();
    }

    /**
     * Crea una nueva ruta optimizada
     * 
     * @param array $puntos_recoleccion Puntos de recolección
     * @param DateTime $hora_salida Hora de salida
     * @param string $origen Dirección de origen
     * @param string $destino Dirección de destino
     * @param string $conductor Nombre del conductor
     * @param string $camion Matrícula del camión
     * @return int ID de la ruta creada
     */
    public function crearRuta(
        array $puntos_recoleccion,
        DateTime $hora_salida,
        string $origen = "",
        string $destino = "",
        string $conductor = "",
        string $camion = ""
    ): int {
        $ruta_optimizada = new RutaOptimizada(
            $puntos_recoleccion,
            $hora_salida,
            $origen,
            $destino
        );

        $ruta_data = [
            'id' => $this->contador_id++,
            'fecha_creacion' => new DateTime(),
            'conductor' => $conductor,
            'camion' => $camion,
            'origen' => $origen,
            'destino' => $destino,
            'hora_salida' => $hora_salida,
            'estado' => 'pendiente',
            'puntos_recoleccion' => $puntos_recoleccion,
            'ruta_optimizada' => $ruta_optimizada,
            'metricas' => $ruta_optimizada->obtenerResumen(),
            'instrucciones' => $ruta_optimizada->generarInstrucciones()
        ];

        $this->rutas[$ruta_data['id']] = $ruta_data;
        $this->guardarRutas();

        return $ruta_data['id'];
    }

    /**
     * Obtiene una ruta por su ID
     * 
     * @param int $id ID de la ruta
     * @return array|null Datos de la ruta o null si no existe
     */
    public function obtenerRuta(int $id): ?array {
        return $this->rutas[$id] ?? null;
    }

    /**
     * Obtiene todas las rutas
     * 
     * @param string $estado Filtro por estado (opcional)
     * @return array Lista de rutas
     */
    public function obtenerTodasLasRutas(string $estado = null): array {
        if ($estado === null) {
            return $this->rutas;
        }

        return array_filter($this->rutas, function($ruta) use ($estado) {
            return $ruta['estado'] === $estado;
        });
    }

    /**
     * Actualiza una ruta existente
     * 
     * @param int $id ID de la ruta
     * @param array $datos_nuevos Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function actualizarRuta(int $id, array $datos_nuevos): bool {
        if (!isset($this->rutas[$id])) {
            return false;
        }

        // Actualizar datos permitidos
        $campos_permitidos = ['conductor', 'camion', 'estado', 'hora_salida'];
        
        foreach ($campos_permitidos as $campo) {
            if (isset($datos_nuevos[$campo])) {
                $this->rutas[$id][$campo] = $datos_nuevos[$campo];
            }
        }

        // Si se actualizaron puntos de recolección, recalcular optimización
        if (isset($datos_nuevos['puntos_recoleccion'])) {
            $this->rutas[$id]['puntos_recoleccion'] = $datos_nuevos['puntos_recoleccion'];
            
            $ruta_optimizada = new RutaOptimizada(
                $datos_nuevos['puntos_recoleccion'],
                $this->rutas[$id]['hora_salida'],
                $this->rutas[$id]['origen'],
                $this->rutas[$id]['destino']
            );
            
            $this->rutas[$id]['ruta_optimizada'] = $ruta_optimizada;
            $this->rutas[$id]['metricas'] = $ruta_optimizada->obtenerResumen();
            $this->rutas[$id]['instrucciones'] = $ruta_optimizada->generarInstrucciones();
        }

        $this->rutas[$id]['fecha_actualizacion'] = new DateTime();
        $this->guardarRutas();

        return true;
    }

    /**
     * Elimina una ruta
     * 
     * @param int $id ID de la ruta
     * @return bool True si se eliminó correctamente
     */
    public function eliminarRuta(int $id): bool {
        if (!isset($this->rutas[$id])) {
            return false;
        }

        unset($this->rutas[$id]);
        $this->guardarRutas();

        return true;
    }

    /**
     * Cambia el estado de una ruta
     * 
     * @param int $id ID de la ruta
     * @param string $nuevo_estado Nuevo estado
     * @return bool True si se cambió correctamente
     */
    public function cambiarEstadoRuta(int $id, string $nuevo_estado): bool {
        $estados_validos = ['pendiente', 'en_proceso', 'completada', 'cancelada'];
        
        if (!in_array($nuevo_estado, $estados_validos)) {
            return false;
        }

        return $this->actualizarRuta($id, ['estado' => $nuevo_estado]);
    }

    /**
     * Optimiza múltiples rutas para un conductor específico
     * 
     * @param string $conductor Nombre del conductor
     * @return array Rutas optimizadas para el conductor
     */
    public function optimizarRutasConductor(string $conductor): array {
        $rutas_conductor = array_filter($this->rutas, function($ruta) use ($conductor) {
            return $ruta['conductor'] === $conductor && $ruta['estado'] === 'pendiente';
        });

        if (empty($rutas_conductor)) {
            return [];
        }

        // Ordenar por hora de salida
        uasort($rutas_conductor, function($a, $b) {
            return $a['hora_salida'] <=> $b['hora_salida'];
        });

        return $rutas_conductor;
    }

    /**
     * Obtiene estadísticas generales del sistema
     * 
     * @return array Estadísticas del sistema
     */
    public function obtenerEstadisticas(): array {
        $total_rutas = count($this->rutas);
        $rutas_por_estado = [];
        $total_distancia = 0;
        $total_tiempo = 0;
        $conductores = [];

        foreach ($this->rutas as $ruta) {
            $estado = $ruta['estado'];
            $rutas_por_estado[$estado] = ($rutas_por_estado[$estado] ?? 0) + 1;

            if (isset($ruta['metricas'])) {
                $total_distancia += $ruta['metricas']['distancia_total_km'] ?? 0;
                $total_tiempo += $ruta['metricas']['tiempo_total_minutos'] ?? 0;
            }

            if (!empty($ruta['conductor'])) {
                $conductores[] = $ruta['conductor'];
            }
        }

        return [
            'total_rutas' => $total_rutas,
            'rutas_por_estado' => $rutas_por_estado,
            'total_distancia_km' => round($total_distancia, 2),
            'total_tiempo_horas' => round($total_tiempo / 60, 2),
            'conductores_unicos' => count(array_unique($conductores)),
            'promedio_eficiencia' => $this->calcularPromedioEficiencia(),
            'fecha_actualizacion' => (new DateTime())->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Calcula el promedio de eficiencia de todas las rutas
     * 
     * @return float Promedio de eficiencia
     */
    private function calcularPromedioEficiencia(): float {
        if (empty($this->rutas)) {
            return 0.0;
        }

        $suma_eficiencia = 0;
        $rutas_con_eficiencia = 0;

        foreach ($this->rutas as $ruta) {
            if (isset($ruta['metricas']['eficiencia_estimada'])) {
                $suma_eficiencia += $ruta['metricas']['eficiencia_estimada'];
                $rutas_con_eficiencia++;
            }
        }

        return $rutas_con_eficiencia > 0 ? round($suma_eficiencia / $rutas_con_eficiencia, 1) : 0.0;
    }

    /**
     * Busca rutas por criterios específicos
     * 
     * @param array $criterios Criterios de búsqueda
     * @return array Rutas que coinciden con los criterios
     */
    public function buscarRutas(array $criterios): array {
        $resultados = $this->rutas;

        // Filtrar por conductor
        if (isset($criterios['conductor']) && !empty($criterios['conductor'])) {
            $resultados = array_filter($resultados, function($ruta) use ($criterios) {
                return stripos($ruta['conductor'], $criterios['conductor']) !== false;
            });
        }

        // Filtrar por estado
        if (isset($criterios['estado']) && !empty($criterios['estado'])) {
            $resultados = array_filter($resultados, function($ruta) use ($criterios) {
                return $ruta['estado'] === $criterios['estado'];
            });
        }

        // Filtrar por fecha
        if (isset($criterios['fecha_desde'])) {
            $fecha_desde = new DateTime($criterios['fecha_desde']);
            $resultados = array_filter($resultados, function($ruta) use ($fecha_desde) {
                return $ruta['fecha_creacion'] >= $fecha_desde;
            });
        }

        if (isset($criterios['fecha_hasta'])) {
            $fecha_hasta = new DateTime($criterios['fecha_hasta']);
            $resultados = array_filter($resultados, function($ruta) use ($fecha_hasta) {
                return $ruta['fecha_creacion'] <= $fecha_hasta;
            });
        }

        return $resultados;
    }

    /**
     * Carga las rutas desde el archivo de persistencia
     * 
     * @return void
     */
    private function cargarRutas(): void {
        if (!file_exists($this->archivo_datos)) {
            return;
        }

        $contenido = file_get_contents($this->archivo_datos);
        $datos = json_decode($contenido, true);

        if ($datos === null) {
            return;
        }

        $this->rutas = [];
        $max_id = 0;

        foreach ($datos as $ruta_data) {
            // Convertir fechas de string a DateTime
            if (isset($ruta_data['fecha_creacion'])) {
                $ruta_data['fecha_creacion'] = new DateTime($ruta_data['fecha_creacion']);
            }
            if (isset($ruta_data['fecha_actualizacion'])) {
                $ruta_data['fecha_actualizacion'] = new DateTime($ruta_data['fecha_actualizacion']);
            }
            if (isset($ruta_data['hora_salida'])) {
                $ruta_data['hora_salida'] = new DateTime($ruta_data['hora_salida']);
            }

            // Reconstruir objeto RutaOptimizada si existe
            if (isset($ruta_data['puntos_recoleccion']) && isset($ruta_data['hora_salida'])) {
                $ruta_optimizada = new RutaOptimizada(
                    $ruta_data['puntos_recoleccion'],
                    $ruta_data['hora_salida'],
                    $ruta_data['origen'] ?? '',
                    $ruta_data['destino'] ?? ''
                );
                $ruta_data['ruta_optimizada'] = $ruta_optimizada;
            }

            $this->rutas[$ruta_data['id']] = $ruta_data;
            $max_id = max($max_id, $ruta_data['id']);
        }

        $this->contador_id = $max_id + 1;
    }

    /**
     * Guarda las rutas en el archivo de persistencia
     * 
     * @return bool True si se guardó correctamente
     */
    private function guardarRutas(): bool {
        $datos_serializados = [];

        foreach ($this->rutas as $ruta) {
            $ruta_serializada = $ruta;
            
            // Convertir DateTime a string para JSON
            if ($ruta['fecha_creacion'] instanceof DateTime) {
                $ruta_serializada['fecha_creacion'] = $ruta['fecha_creacion']->format('Y-m-d H:i:s');
            }
            if (isset($ruta['fecha_actualizacion']) && $ruta['fecha_actualizacion'] instanceof DateTime) {
                $ruta_serializada['fecha_actualizacion'] = $ruta['fecha_actualizacion']->format('Y-m-d H:i:s');
            }
            if ($ruta['hora_salida'] instanceof DateTime) {
                $ruta_serializada['hora_salida'] = $ruta['hora_salida']->format('Y-m-d H:i:s');
            }

            // No serializar el objeto RutaOptimizada, solo los datos necesarios
            unset($ruta_serializada['ruta_optimizada']);

            $datos_serializados[] = $ruta_serializada;
        }

        $json = json_encode($datos_serializados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->archivo_datos, $json) !== false;
    }

    /**
     * Exporta las rutas a un formato específico
     * 
     * @param string $formato Formato de exportación (json, csv)
     * @return string Datos exportados
     */
    public function exportarRutas(string $formato = 'json'): string {
        switch (strtolower($formato)) {
            case 'csv':
                return $this->exportarCSV();
            case 'json':
            default:
                return json_encode($this->rutas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Exporta las rutas en formato CSV
     * 
     * @return string Datos en formato CSV
     */
    private function exportarCSV(): string {
        $output = fopen('php://temp', 'r+');
        
        // Encabezados
        fputcsv($output, [
            'ID', 'Conductor', 'Camión', 'Origen', 'Destino', 
            'Hora Salida', 'Estado', 'Puntos Recolección', 
            'Distancia Total', 'Tiempo Estimado', 'Eficiencia'
        ]);

        // Datos
        foreach ($this->rutas as $ruta) {
            fputcsv($output, [
                $ruta['id'],
                $ruta['conductor'],
                $ruta['camion'],
                $ruta['origen'],
                $ruta['destino'],
                $ruta['hora_salida']->format('H:i'),
                $ruta['estado'],
                count($ruta['puntos_recoleccion']),
                $ruta['metricas']['distancia_total_km'] ?? 0,
                $ruta['metricas']['tiempo_total_minutos'] ?? 0,
                $ruta['metricas']['eficiencia_estimada'] ?? 0
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
