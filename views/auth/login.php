<!-- V: ingreso del empleado con su DNI y su contraseña -->
<h1>Minimarket Mass</h1>
<form method="post" action="index.php?r=login">
  <label>DNI <input name="dni" maxlength="8" pattern="[0-9]{8}" required></label>
  <label>Contraseña <input type="password" name="contrasena" autocomplete="current-password" required></label>
  <button>Ingresar</button>
  <?php if (!empty($error)): ?><p><?= htmlspecialchars($error) ?></p><?php endif; ?>
</form>
