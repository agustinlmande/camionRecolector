// clases/repositories/CamionRepository.php
class CamionRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function guardar(Camion $camion): bool {
        $sql = "INSERT INTO camiones (placa, capacidad, conductor, estado) 
                VALUES (:placa, :capacidad, :conductor, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':placa' => $camion->getPlaca(),
            ':capacidad' => $camion->getCapacidad(),
            ':conductor' => $camion->getConductor(),
            ':estado' => $camion->getEstado()
        ]);
    }

    public function buscarPorEstado(string $estado): array {
        $sql = "SELECT * FROM camiones WHERE estado = :estado";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $estado]);
        
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'Camion');
    }

    public function obtenerEstadisticas(): array {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as total,
                    AVG(capacidad) as capacidad_promedio
                FROM camiones 
                GROUP BY estado";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}