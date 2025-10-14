<?php
declare(strict_types=1);

/**
 * Clase RutaOptimizada - Maneja la optimización de rutas de recolección de residuos
 * 
 * Esta clase implementa algoritmos de optimización para calcular la ruta más eficiente
 * entre múltiples puntos de recolección de residuos.
 */
class RutaOptimizada {
    // Constantes configurables
    public const VELOCIDAD_PROMEDIO_KMH = 25; // Velocidad urbana promedio para camiones de residuos
    public const TIEMPO_RECOLECCION_MINUTOS = 5; // Tiempo de recolección por punto
    public const DISTANCIA_POR_DEFECTO_KM = 10.0; // Distancia por defecto cuando no hay coordenadas
    
    private array $puntos_recoleccion;
    private array $ruta_optimizada;
    private float $distancia_total;
    private float $tiempo_estimado;
    private DateTime $hora_salida;
    private string $origen;
    private string $destino;
    private array $metricas_detalladas;

    /**
     * Constructor de la clase
     * 
     * @param array $puntos_recoleccion Array de puntos de recolección
     * @param DateTime $hora_salida Hora programada de salida
     * @param string $origen Dirección de origen
     * @param string $destino Dirección de destino
     */
    public function __construct(
        array $puntos_recoleccion, 
        DateTime $hora_salida,
        string $origen = "",
        string $destino = ""
    ) {
        $this->puntos_recoleccion = $puntos_recoleccion;
        $this->hora_salida = $hora_salida;
        $this->origen = $origen;
        $this->destino = $destino;
        $this->distancia_total = 0.0;
        $this->tiempo_estimado = 0.0;
        $this->ruta_optimizada = [];
        $this->metricas_detalladas = [];
        
        $this->optimizarRuta();
    }

    /**
     * Optimiza la ruta usando algoritmo TSP (Traveling Salesman Problem) simplificado
     * 
     * @return void
     */
    private function optimizarRuta(): void {
        if (empty($this->puntos_recoleccion)) {
            return;
        }

        // Algoritmo de optimización (TSP simplificado - Vecino Más Cercano)
        $puntos = $this->puntos_recoleccion;
        $ruta_optima = [];
        
        // Agregar punto de origen si existe
        if (!empty($this->origen)) {
            $ruta_optima[] = [
                'direccion' => $this->origen,
                'tipo' => 'origen',
                'orden' => 0
            ];
        }
        
        // Si hay puntos de recolección, optimizar
        if (!empty($puntos)) {
            $punto_actual = array_shift($puntos); // Punto de partida
            
            // Agregar punto de partida
            $ruta_optima[] = array_merge($punto_actual, [
                'tipo' => 'recoleccion',
                'orden' => count($ruta_optima)
            ]);
            
            // Optimizar puntos restantes
            while (!empty($puntos)) {
                $mas_cercano = $this->encontrarPuntoMasCercano($punto_actual, $puntos);
                $ruta_optima[] = array_merge($mas_cercano, [
                    'tipo' => 'recoleccion',
                    'orden' => count($ruta_optima)
                ]);
                $punto_actual = $mas_cercano;
                
                // Remover el punto seleccionado de la lista
                $puntos = array_filter($puntos, function($punto) use ($mas_cercano) {
                    return $punto !== $mas_cercano;
                });
                $puntos = array_values($puntos); // Reindexar arreglo
            }
        }
        
        // Agregar punto de destino si existe
        if (!empty($this->destino)) {
            $ruta_optima[] = [
                'direccion' => $this->destino,
                'tipo' => 'destino',
                'orden' => count($ruta_optima)
            ];
        }
        
        $this->ruta_optimizada = $ruta_optima;
        $this->calcularMetricas();
    }

    /**
     * Encuentra el punto más cercano al punto actual
     * 
     * @param array $punto_actual Punto de referencia
     * @param array $puntos_disponibles Puntos disponibles para visitar
     * @return array Punto más cercano
     */
    private function encontrarPuntoMasCercano(array $punto_actual, array $puntos_disponibles): array {
        $distancia_minima = PHP_FLOAT_MAX;
        $punto_mas_cercano = null;
        $indice_mas_cercano = 0;

        foreach ($puntos_disponibles as $indice => $punto) {
            $distancia = $this->calcularDistanciaEuclidiana($punto_actual, $punto);
            
            if ($distancia < $distancia_minima) {
                $distancia_minima = $distancia;
                $punto_mas_cercano = $punto;
                $indice_mas_cercano = $indice;
            }
        }

        return $punto_mas_cercano;
    }

    /**
     * Calcula la distancia euclidiana entre dos puntos
     * 
     * @param array $punto1 Primer punto
     * @param array $punto2 Segundo punto
     * @return float Distancia en kilómetros
     */
    private function calcularDistanciaEuclidiana(array $punto1, array $punto2): float {
        // Si los puntos tienen coordenadas lat/lng, usar fórmula de Haversine
        if (isset($punto1['lat'], $punto1['lng'], $punto2['lat'], $punto2['lng'])) {
            return $this->calcularDistanciaHaversine(
                $punto1['lat'], $punto1['lng'],
                $punto2['lat'], $punto2['lng']
            );
        }
        
        // Si no hay coordenadas, usar distancia estimada basada en dirección
        return $this->calcularDistanciaEstimada($punto1, $punto2);
    }

    /**
     * Calcula la distancia usando la fórmula de Haversine
     * 
     * @param float $lat1 Latitud del primer punto
     * @param float $lng1 Longitud del primer punto
     * @param float $lat2 Latitud del segundo punto
     * @param float $lng2 Longitud del segundo punto
     * @return float Distancia en kilómetros
     */
    private function calcularDistanciaHaversine(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $radio_tierra = 6371; // Radio de la Tierra en kilómetros
        
        $d_lat = deg2rad($lat2 - $lat1);
        $d_lng = deg2rad($lng2 - $lng1);
        
        $a = sin($d_lat/2) * sin($d_lat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($d_lng/2) * sin($d_lng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $radio_tierra * $c;
    }

    /**
     * Calcula distancia estimada basada en similitud de direcciones
     * 
     * @param array $punto1 Primer punto
     * @param array $punto2 Segundo punto
     * @return float Distancia estimada en kilómetros
     */
    private function calcularDistanciaEstimada(array $punto1, array $punto2): float {
        // Algoritmo simple basado en similitud de texto
        $direccion1 = strtolower($punto1['direccion'] ?? '');
        $direccion2 = strtolower($punto2['direccion'] ?? '');
        
        // Calcular similitud usando levenshtein
        $similitud = levenshtein($direccion1, $direccion2);
        $max_longitud = max(strlen($direccion1), strlen($direccion2));
        
        if ($max_longitud == 0) return self::DISTANCIA_POR_DEFECTO_KM;
        
        $porcentaje_similitud = 1 - ($similitud / $max_longitud);
        
        // Distancia estimada basada en similitud (más similar = más cercano)
        return (1 - $porcentaje_similitud) * 20; // Entre 0 y 20 km
    }

    /**
     * Calcula métricas detalladas de la ruta
     * 
     * @return void
     */
    private function calcularMetricas(): void {
        $this->distancia_total = 0.0;
        $this->tiempo_estimado = 0.0;
        $this->metricas_detalladas = [];

        for ($i = 0; $i < count($this->ruta_optimizada) - 1; $i++) {
            $punto_actual = $this->ruta_optimizada[$i];
            $punto_siguiente = $this->ruta_optimizada[$i + 1];
            
            $distancia_segmento = $this->calcularDistanciaEuclidiana($punto_actual, $punto_siguiente);
            $tiempo_segmento = $this->calcularTiempoEstimado($distancia_segmento, $punto_actual);
            
            $this->distancia_total += $distancia_segmento;
            $this->tiempo_estimado += $tiempo_segmento;
            
            $this->metricas_detalladas[] = [
                'desde' => $punto_actual['direccion'] ?? 'Origen',
                'hasta' => $punto_siguiente['direccion'] ?? 'Destino',
                'distancia_km' => round($distancia_segmento, 2),
                'tiempo_minutos' => round($tiempo_segmento, 1),
                'tipo_recoleccion' => $punto_actual['tipo'] ?? 'recoleccion'
            ];
        }
    }

    /**
     * Calcula el tiempo estimado para un segmento de ruta
     * 
     * @param float $distancia Distancia en kilómetros
     * @param array $punto Punto de partida
     * @return float Tiempo en minutos
     */
    private function calcularTiempoEstimado(float $distancia, array $punto): float {
        // Velocidad promedio en km/h
        $velocidad_promedio = self::VELOCIDAD_PROMEDIO_KMH;
        
        // Tiempo de recolección en minutos
        $tiempo_recoleccion = self::TIEMPO_RECOLECCION_MINUTOS;
        
        // Tiempo de viaje
        $tiempo_viaje = ($distancia / $velocidad_promedio) * 60; // Convertir a minutos
        
        // Si es punto de recolección, agregar tiempo de recolección
        if (($punto['tipo'] ?? '') === 'recoleccion') {
            $tiempo_viaje += $tiempo_recoleccion;
        }
        
        return $tiempo_viaje;
    }

    /**
     * Genera instrucciones detalladas para la ruta optimizada
     * 
     * @return array Instrucciones de la ruta
     */
    public function generarInstrucciones(): array {
        $instrucciones = [];
        
        foreach ($this->ruta_optimizada as $index => $punto) {
            $instrucciones[] = [
                'orden' => $punto['orden'] ?? $index + 1,
                'direccion' => $punto['direccion'] ?? 'Dirección no especificada',
                'tipo' => $punto['tipo'] ?? 'recoleccion',
                'estimado_residuos' => $punto['estimado_kg'] ?? 0,
                'instruccion' => $this->generarInstruccion($index, $punto),
                'tiempo_estimado' => $this->obtenerTiempoEstimadoPunto($index),
                'distancia_acumulada' => $this->obtenerDistanciaAcumulada($index)
            ];
        }
        
        return $instrucciones;
    }

    /**
     * Genera una instrucción específica para un punto
     * 
     * @param int $index Índice del punto
     * @param array $punto Datos del punto
     * @return string Instrucción generada
     */
    private function generarInstruccion(int $index, array $punto): string {
        $tipo = $punto['tipo'] ?? 'recoleccion';
        $orden = $punto['orden'] ?? $index + 1;
        
        switch ($tipo) {
            case 'origen':
                return "🚛 Iniciar ruta desde: {$punto['direccion']}";
            case 'destino':
                return "🏁 Finalizar ruta en: {$punto['direccion']}";
            case 'recoleccion':
                $kg = $punto['estimado_kg'] ?? 0;
                return "📦 Punto {$orden}: Recolectar residuos en {$punto['direccion']} (Estimado: {$kg} kg)";
            default:
                return "📍 Punto {$orden}: {$punto['direccion']}";
        }
    }

    /**
     * Obtiene el tiempo estimado para llegar a un punto específico
     * 
     * @param int $index Índice del punto
     * @return float Tiempo en minutos
     */
    private function obtenerTiempoEstimadoPunto(int $index): float {
        $tiempo_acumulado = 0;
        
        for ($i = 0; $i < $index && $i < count($this->metricas_detalladas); $i++) {
            $tiempo_acumulado += $this->metricas_detalladas[$i]['tiempo_minutos'];
        }
        
        return round($tiempo_acumulado, 1);
    }

    /**
     * Obtiene la distancia acumulada hasta un punto específico
     * 
     * @param int $index Índice del punto
     * @return float Distancia en kilómetros
     */
    private function obtenerDistanciaAcumulada(int $index): float {
        $distancia_acumulada = 0;
        
        for ($i = 0; $i < $index && $i < count($this->metricas_detalladas); $i++) {
            $distancia_acumulada += $this->metricas_detalladas[$i]['distancia_km'];
        }
        
        return round($distancia_acumulada, 2);
    }

    /**
     * Obtiene un resumen de la ruta optimizada
     * 
     * @return array Resumen de la ruta
     */
    public function obtenerResumen(): array {
        return [
            'total_puntos' => count($this->ruta_optimizada),
            'puntos_recoleccion' => count(array_filter($this->ruta_optimizada, fn($p) => ($p['tipo'] ?? '') === 'recoleccion')),
            'distancia_total_km' => round($this->distancia_total, 2),
            'tiempo_total_minutos' => round($this->tiempo_estimado, 1),
            'tiempo_total_horas' => round($this->tiempo_estimado / 60, 2),
            'hora_salida' => $this->hora_salida->format('H:i'),
            'hora_estimada_llegada' => $this->calcularHoraLlegada(),
            'eficiencia_estimada' => $this->calcularEficiencia()
        ];
    }

    /**
     * Calcula la hora estimada de llegada
     * 
     * @return string Hora de llegada en formato H:i
     */
    private function calcularHoraLlegada(): string {
        $hora_llegada = clone $this->hora_salida;
        $hora_llegada->add(new DateInterval('PT' . (int)$this->tiempo_estimado . 'M'));
        return $hora_llegada->format('H:i');
    }

    /**
     * Calcula la eficiencia de la ruta (0-100%)
     * 
     * @return float Porcentaje de eficiencia
     */
    private function calcularEficiencia(): float {
        if (empty($this->puntos_recoleccion)) {
            return 100.0;
        }
        
        // Eficiencia basada en distancia promedio por punto
        $distancia_promedio = $this->distancia_total / count($this->puntos_recoleccion);
        
        // Eficiencia ideal: menos de 5km por punto
        $eficiencia = max(0, 100 - ($distancia_promedio * 10));
        
        return round($eficiencia, 1);
    }

    // Getters
    public function getRutaOptimizada(): array {
        return $this->ruta_optimizada;
    }

    public function getDistanciaTotal(): float {
        return $this->distancia_total;
    }

    public function getTiempoEstimado(): float {
        return $this->tiempo_estimado;
    }

    public function getHoraSalida(): DateTime {
        return $this->hora_salida;
    }

    public function getMetricasDetalladas(): array {
        return $this->metricas_detalladas;
    }

    public function getPuntosRecoleccion(): array {
        return $this->puntos_recoleccion;
    }
}
