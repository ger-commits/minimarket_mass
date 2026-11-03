<?php
// M: rol administrador. Autoriza o rechaza las solicitudes de anulación de su tienda (solicitud_anulacion),
// programa los turnos de trabajo de su tienda (turno_trabajo) y registra los traslados de mercadería.
require_once __DIR__ . '/Empleado.php';

class Administrador extends Empleado
{
    public function rol(): string { return 'ADMINISTRADOR'; }

    // Solo puede resolver solicitudes de ventas de su propia tienda.
    public function puedeResolverSolicitud(int $idTiendaDeLaVenta): bool
    {
        return $this->idTienda === $idTiendaDeLaVenta;
    }

    // Solo puede programar turnos a empleados de su propia tienda.
    public function puedeProgramarTurno(int $idTiendaDelEmpleado): bool
    {
        return $this->idTienda === $idTiendaDelEmpleado;
    }

    // Solo registra traslados que salen de su propia tienda (origen).
    public function puedeRegistrarTraslado(int $idTiendaOrigen): bool
    {
        return $this->idTienda === $idTiendaOrigen;
    }
}
