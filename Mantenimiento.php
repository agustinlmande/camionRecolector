// clases/Mantenimiento.php
class Mantenimiento {
    private int $id;
    private int $camion_id;
    private DateTime $fecha_programada;
    private string $tipo; // 'preventivo', 'correctivo', 'predictivo'
    private string $estado; // 'pendiente', 'en_proceso', 'completado'
    private string $descripcion;
    private float $costo_estimado;

    public function __construct(
        int $camion_id,
        string $tipo,
        DateTime $fecha_programada,
        string $descripcion = '',
        float $costo_estimado = 0.0
    ) {
        $this->id = rand(10000, 99999);
        $this->camion_id = $camion_id;
        $this->setTipo($tipo);
        $this->fecha_programada = $fecha_programada;
        $this->descripcion = $descripcion;
        $this->costo_estimado = $costo_estimado;
        $this->estado = 'pendiente';
    }

    // Método predictivo basado en kilometraje y antigüedad
    public static function predecirMantenimiento(Camion $camion, float $kilometraje): ?Mantenimiento {
        $hoy = new DateTime();
        $antiguedad = $hoy->diff($camion->getFechaCompra())->y;
        
        // Lógica predictiva
        if ($kilometraje > 100000 || $antiguedad > 5) {
            return new Mantenimiento(
                $camion->getId(),
                'predictivo',
                $hoy->modify('+30 days'),
                'Mantenimiento predictivo por kilometraje/alto',
                $antiguedad > 5 ? 1500.00 : 800.00
            );
        }
        
        return null;
    }
}