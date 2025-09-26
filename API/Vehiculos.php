<?php
// Vehiculos.php

abstract class Vehiculo {
    protected $id;
    protected $placa;
    protected $capacidad;

    public function __construct($id, $placa, $capacidad) {
        $this->id = $id ?? uniqid();
        $this->placa = $placa;
        $this->capacidad = $capacidad;
    }

    public function getId() {
        return $this->id;
    }

    abstract public function toArray();

    // Método genérico para crear un Vehículo desde array
    public static function fromArray($data) {
        if ($data['tipo'] === 'Camion') {
            return new Camion(
                $data['id'] ?? null,
                $data['placa'],
                $data['capacidad'],
                $data['conductor']
            );
        }
        throw new Exception("Tipo de vehículo no soportado");
    }
}

class Camion extends Vehiculo {
    private $conductor;

    public function __construct($id, $placa, $capacidad, $conductor) {
        parent::__construct($id, $placa, $capacidad);
        $this->conductor = $conductor;
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'placa' => $this->placa,
            'capacidad' => $this->capacidad,
            'conductor' => $this->conductor,
            'tipo' => 'Camion'
        ];
    }
}

class GestorVehiculos {
    private $file = __DIR__ . "/data.json";
    private $vehiculos = [];

    public function __construct() {
        $this->cargarDatos();
    }

    private function cargarDatos() {
        if (file_exists($this->file)) {
            $json = file_get_contents($this->file);
            $data = json_decode($json, true) ?? [];
            foreach ($data as $v) {
                $this->vehiculos[$v['id']] = Vehiculo::fromArray($v);
            }
        }
    }

    private function guardarDatos() {
        $data = array_map(fn($v) => $v->toArray(), $this->vehiculos);
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function obtenerVehiculos() {
        return array_map(fn($v) => $v->toArray(), $this->vehiculos);
    }

    public function buscarVehiculoPorId($id) {
        return $this->vehiculos[$id] ?? null;
    }

    public function agregarVehiculo(Vehiculo $vehiculo) {
        $this->vehiculos[$vehiculo->getId()] = $vehiculo;
        $this->guardarDatos();
    }

    public function actualizarVehiculo($id, $data) {
        if (!isset($this->vehiculos[$id])) {
            throw new Exception("Vehículo no encontrado");
        }
        $vehiculo = Vehiculo::fromArray(array_merge($data, ['id' => $id]));
        $this->vehiculos[$id] = $vehiculo;
        $this->guardarDatos();
    }

    public function eliminarVehiculo($id) {
        unset($this->vehiculos[$id]);
        $this->guardarDatos();
    }
}
