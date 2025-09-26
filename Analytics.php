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

     // Cuenta recorridos realizados hoy
    private function contarRecorridosHoy(array $recorridos): int {
        $hoy = date('Y-m-d');
        return count(array_filter($recorridos, fn($r) => isset($r['fecha']) && $r['fecha'] === $hoy));
    }

    // Suma residuos recolectados en el mes actual
    private function calcularResiduosMes(array $recorridos): float {
        $mes = date('Y-m');
        $total = 0;
        foreach ($recorridos as $r) {
            if (isset($r['fecha']) && strpos($r['fecha'], $mes) === 0 && isset($r['residuos_kg'])) {
                $total += $r['residuos_kg'];
            }
        }
        return $total;
    }

    // Calcula eficiencia promedio de los camiones
    private function calcularEficiencia(array $camiones, array $recorridos): float {
        if (count($camiones) === 0) return 0.0;
        $eficiencias = [];
        foreach ($camiones as $c) {
            $id = method_exists($c, 'getId') ? $c->getId() : (isset($c['id']) ? $c['id'] : null);
            $recorridosCamion = array_filter($recorridos, fn($r) => isset($r['camion_id']) && $r['camion_id'] == $id);
            $totalRecorridos = count($recorridosCamion);
            $eficiencia = $totalRecorridos > 0 ? array_sum(array_map(fn($r) => $r['eficiencia'] ?? 1, $recorridosCamion)) / $totalRecorridos : 1;
            $eficiencias[] = $eficiencia;
        }
        return count($eficiencias) ? round(array_sum($eficiencias) / count($eficiencias), 2) : 1.0;
    }

    // Identifica las rutas más productivas
    private function identificarRutasProductivas(array $recorridos): array {
        $rutas = [];
        foreach ($recorridos as $r) {
            if (isset($r['ruta_id'], $r['residuos_kg'])) {
                if (!isset($rutas[$r['ruta_id']])) $rutas[$r['ruta_id']] = 0;
                $rutas[$r['ruta_id']] += $r['residuos_kg'];
            }
        }
        arsort($rutas);
        return array_slice($rutas, 0, 3, true); // Top 3 rutas
    }

    // Cuenta mantenimientos pendientes
    private function contarMantenimientosPendientes(): int {
        if (!method_exists($this->gestor, 'obtenerMantenimientos')) return 0;
        $mantenimientos = $this->gestor->obtenerMantenimientos();
        return count(array_filter($mantenimientos, fn($m) => isset($m['estado']) ? $m['estado'] === 'pendiente' : (method_exists($m, 'getEstado') && $m->getEstado() === 'pendiente')));
    }

    // Simulación de creación de PDF
    private function crearPDF(): string {
        // Aquí iría la lógica real usando una librería como FPDF o TCPDF
        return 'reporte_analytics.pdf';
    }
}


