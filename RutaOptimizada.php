// clases/RutaOptimizada.php
class RutaOptimizada {
    private array $puntos_recoleccion;
    private array $ruta_optimizada;
    private float $distancia_total;
    private float $tiempo_estimado;
    private DateTime $hora_salida;

    public function __construct(array $puntos_recoleccion, DateTime $hora_salida) {
        $this->puntos_recoleccion = $puntos_recoleccion;
        $this->hora_salida = $hora_salida;
        $this->optimizarRuta();
    }

    private function optimizarRuta(): void {
        // Algoritmo de optimización (TSP simplificado)
        $puntos = $this->puntos_recoleccion;
        $ruta_optima = [];
        $punto_actual = array_shift($puntos); // Punto de partida
        
        while (!empty($puntos)) {
            $mas_cercano = $this->encontrarPuntoMasCercano($punto_actual, $puntos);
            $ruta_optima[] = $mas_cercano;
            $punto_actual = $mas_cercano;
        }
        
        $this->ruta_optimizada = $ruta_optima;
        $this->calcularMetricas();
    }

    public function generarInstrucciones(): array {
        $instrucciones = [];
        foreach ($this->ruta_optimizada as $index => $punto) {
            $instrucciones[] = [
                'orden' => $index + 1,
                'direccion' => $punto['direccion'],
                'estimado_residuos' => $punto['estimado_kg'],
                'instruccion' => $this->generarInstruccion($index)
            ];
        }
        return $instrucciones;
    }
}