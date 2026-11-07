<?php
// M: cancelar turnos de trabajo desde el panel del administrador.
require_once __DIR__ . '/../models/Sesion.php';
require_once __DIR__ . '/../models/Administrador.php';
require_once __DIR__ . '/../models/TurnoTrabajoRepository.php';

class TurnoController
{
    public function cancelar(): void
    {
        $admin = Sesion::empleado();
        if (!$admin instanceof Administrador) {
            header('Location: index.php?r=administrador');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(Sesion::token(), $_POST['csrf'] ?? '')) {
            $this->volver('La solicitud no es válida. Vuelve a intentarlo.', 'error');
        }
        try {
            (new TurnoTrabajoRepository())->cancelar($admin, (int)($_POST['id_turno'] ?? 0));
            $this->volver('Turno cancelado.');
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->volver($e->getMessage(), 'error');
        } catch (Throwable $e) {
            error_log('Cancelar turno: ' . $e->getMessage());
            $this->volver('No se pudo cancelar el turno.', 'error');
        }
    }

    private function volver(string $texto, string $tipo = 'ok'): void
    {
        $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo];
        header('Location: index.php?r=admin');
        exit;
    }
}