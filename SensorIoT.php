// clases/SensorIoT.php
class SensorIoT {
    private int $camion_id;
    private float $nivel_combustible;
    private float $temperatura_motor;
    private float $presion_neumaticos;
    private DateTime $ultima_actualizacion;

    public function __construct(int $camion_id) {
        $this->camion_id = $camion_id;
        $this->actualizarDesdeAPI();
    }

    public function actualizarDesdeAPI(): void {
        // Simular datos de API IoT real
        $datos = $this->llamarAPIExterna();
        
        $this->nivel_combustible = $datos['fuel_level'];
        $this->temperatura_motor = $datos['engine_temp'];
        $this->presion_neumaticos = $datos['tire_pressure'];
        $this->ultima_actualizacion = new DateTime();
    }

    public function generarAlertas(): array {
        $alertas = [];

        if ($this->nivel_combustible < 0.1) {
            $alertas[] = '⛽ Combustible crítico (<10%)';
        }

        if ($this->temperatura_motor > 90) {
            $alertas[] = '🌡️ Sobrecalentamiento del motor';
        }

        if ($this->presion_neumaticos < 2.0) {
            $alertas[] = '💨 Presión de neumáticos baja';
        }

        return $alertas;
    }
}