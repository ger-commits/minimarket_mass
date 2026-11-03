<?php
// M: tabla solicitud_anulacion: solicitud de anulación: la pide el cajero y la autoriza o rechaza el administrador de la misma tienda.
require_once __DIR__ . '/Administrador.php';
class SolicitudAnulacion
{
    public function __construct(
        public int $idSolicitud,
        public string $fechaHoraSolicitud,
        public string $motivo,
        public string $estado,
        public ?string $fechaHoraResolucion,   // opcional: NULL mientras esta PENDIENTE
        public ?string $motivoRechazo,   // opcional: solo si fue RECHAZADA
        public int $idVenta,
        public int $idEmpleadoSolicita,   // cajero que la pide
        public ?int $idEmpleadoResuelve   // administrador que la autoriza o rechaza (NULL si PENDIENTE)
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_solicitud'],
            (string)$fila['fecha_hora_solicitud'],
            (string)$fila['motivo'],
            (string)$fila['estado'],
            $fila['fecha_hora_resolucion'] === null ? null : (string)$fila['fecha_hora_resolucion'],
            $fila['motivo_rechazo'] === null ? null : (string)$fila['motivo_rechazo'],
            (int)$fila['id_venta'],
            (int)$fila['id_empleado_solicita'],
            $fila['id_empleado_resuelve'] === null ? null : (int)$fila['id_empleado_resuelve']
        );
    }

    public function estaPendiente(): bool { return $this->estado === 'PENDIENTE'; }

    // el cajero solicita; quien resuelve debe ser un administrador de la tienda de la venta y distinto de quien solicita
    public function puedeResolverla(Administrador $admin, int $idTiendaDeLaVenta): bool
    {
        return $this->estaPendiente()
            && $admin->idEmpleado !== $this->idEmpleadoSolicita
            && $admin->puedeResolverSolicitud($idTiendaDeLaVenta);
    }

    // al aprobarse, la venta pasa a ANULADA, el stock vuelve y los pagos pasan a ANULADO (lo hace el repositorio en una transacción)
    public function aprobar(int $idAdministrador, string $fechaHora): void
    {
        $this->resolver('APROBADA', $idAdministrador, $fechaHora, null);
    }

    public function rechazar(int $idAdministrador, string $fechaHora, string $motivoRechazo): void
    {
        if (trim($motivoRechazo) === '') throw new InvalidArgumentException('El rechazo necesita un motivo.');
        $this->resolver('RECHAZADA', $idAdministrador, $fechaHora, $motivoRechazo);
    }

    private function resolver(string $estado, int $idAdministrador, string $fechaHora, ?string $motivoRechazo): void
    {
        if (!$this->estaPendiente()) throw new LogicException('La solicitud ya fue resuelta.');
        if ($idAdministrador === $this->idEmpleadoSolicita) throw new LogicException('Quien resuelve debe ser distinto de quien solicita.');
        $this->estado = $estado;
        $this->idEmpleadoResuelve = $idAdministrador;
        $this->fechaHoraResolucion = $fechaHora;
        $this->motivoRechazo = $motivoRechazo;
    }
}
