// clases/Notificador.php
class Notificador {
    private array $canales; // 'email', 'sms', 'push'

    public function __construct(array $canales = ['email']) {
        $this->canales = $canales;
    }

    public function notificarMantenimiento(Mantenimiento $mantenimiento): void {
        $mensaje = "🔧 Mantenimiento programado para el camión {$mantenimiento->getCamion()->getPlaca()} " .
                  "el {$mantenimiento->getFechaProgramada()->format('d/m/Y')}";

        foreach ($this->canales as $canal) {
            switch ($canal) {
                case 'email':
                    $this->enviarEmail($mantenimiento->getResponsable()->getEmail(), $mensaje);
                    break;
                case 'sms':
                    $this->enviarSMS($mantenimiento->getResponsable()->getTelefono(), $mensaje);
                    break;
                case 'push':
                    $this->enviarPushNotification($mantenimiento->getResponsable()->getId(), $mensaje);
                    break;
            }
        }
    }

    public function notificarAlerta(string $tipo, string $mensaje, array $destinatarios): void {
        // Lógica para alertas urgentes (bajo combustible, avería, etc.)
    }
}