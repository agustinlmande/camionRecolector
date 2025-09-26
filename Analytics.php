// clases/Analytics.php
class Analytics {
    private GestorCamiones $gestor;
    private array $metricas;

    public function __construct(GestorCamiones $gestor) {
        $this->gestor = $gestor;
        $this->calcularMetricas();
    }

    public function calcularMetricas(): void {
        $camiones = $this->gestor->obtenerCamiones();
        $recorridos = $this->gestor->obtenerTodosRecorridos();

        $this->metricas = [
            'total_camiones' => count($camiones),
            'camiones_activos' => count(array_filter($camiones, fn($c) => $c->getEstado() === 'activo')),
            'total_recorridos_hoy' => $this->contarRecorridosHoy($recorridos),
            'residuos_recolectados_mes' => $this->calcularResiduosMes($recorridos),
            'eficiencia_promedio' => $this->calcularEficiencia($camiones, $recorridos),
            'rutas_mas_productivas' => $this->identificarRutasProductivas($recorridos),
            'mantenimientos_pendientes' => $this->contarMantenimientosPendientes()
        ];
    }

    public function generarReportePDF(): string {
        // Generar PDF con gráficas y estadísticas
        return $this->crearPDF();
    }

    public function getAlertas(): array {
        $alertas = [];
        
        // Alerta por bajo rendimiento
        if ($this->metricas['eficiencia_promedio'] < 0.7) {
            $alertas[] = [
                'tipo' => 'advertencia',
                'mensaje' => 'Eficiencia por debajo del 70%',
                'gravedad' => 'media'
            ];
        }

        // Alerta por mantenimiento urgente
        if ($this->metricas['mantenimientos_pendientes'] > 3) {
            $alertas[] = [
                'tipo' => 'urgente',
                'mensaje' => '3+ mantenimientos pendientes',
                'gravedad' => 'alta'
            ];
        }

        return $alertas;
    }
}