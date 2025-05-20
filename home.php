<?php
// Archivo: index.php

// 1. Incluir el archivo de conexión a la base de datos
require_once 'conexion.php'; // Asegúrate de que este archivo establece $pdo correctamente.

// ** IMPORTANTE: Salario Mínimo Legal Mensual Vigente Colombia 2025 **
// Este valor debería idealmente venir de conexion.php o un archivo de configuración.
if (!isset($salario_minimo)) {
    $salario_minimo = 1423500.00; // Valor para 2025 en Colombia.
}


// Mensajes para la inserción de empleados
$mensaje_insercion = '';
$error_insercion = false;

// Mensajes para otras acciones sobre empleados (aumento, eliminación)
$mensaje_accion = '';
$error_accion = false;

// --- Lógica para Insertar Nuevo Empleado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_empleado'])) {
    $documento = trim($_POST['documento']);
    $nombre = trim($_POST['nombre']);
    $sexo = $_POST['sexo'];
    $domicilio = trim($_POST['domicilio']);
    $fechaingreso = $_POST['fechaingreso'];
    $fechanacimiento = $_POST['fechanacimiento'];
    $sueldobasico = filter_var($_POST['sueldobasico'], FILTER_VALIDATE_FLOAT);
    $estado_civil = $_POST['estado_civil'];
    $tipo_sangre = $_POST['tipo_sangre'];
    $usuario_red_social = trim($_POST['usuario_red_social']) ?: NULL;

    // Validaciones
    if (empty($documento) || empty($nombre) || empty($sexo) || empty($fechaingreso) || empty($fechanacimiento) || $sueldobasico === false || $sueldobasico < 0) {
        $mensaje_insercion = "Por favor, complete todos los campos obligatorios (*) y asegúrese de que el sueldo sea válido.";
        $error_insercion = true;
    } elseif (!ctype_digit($documento)) {
        $mensaje_insercion = "El campo 'Documento' solo debe contener números.";
        $error_insercion = true;
    } elseif (preg_match('/\d/', $nombre)) { // VALIDACIÓN AGREGADA POR USUARIO: No permitir números en el nombre
        $mensaje_insercion = "El campo 'Nombre Completo' no debe contener números.";
        $error_insercion = true;
    } elseif (strtotime($fechanacimiento) >= strtotime($fechaingreso)) {
        $mensaje_insercion = "La fecha de nacimiento no puede ser posterior o igual a la fecha de ingreso.";
        $error_insercion = true;
    } else {
        try {
            $sql_insert = "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social)
                           VALUES (:documento, :nombre, :sexo, :domicilio, :fechaingreso, :fechanacimiento, :sueldobasico, :estado_civil, :tipo_sangre, :usuario_red_social)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->bindParam(':documento', $documento);
            $stmt_insert->bindParam(':nombre', $nombre);
            $stmt_insert->bindParam(':sexo', $sexo);
            $stmt_insert->bindParam(':domicilio', $domicilio);
            $stmt_insert->bindParam(':fechaingreso', $fechaingreso);
            $stmt_insert->bindParam(':fechanacimiento', $fechanacimiento);
            $stmt_insert->bindParam(':sueldobasico', $sueldobasico);
            $stmt_insert->bindParam(':estado_civil', $estado_civil);
            $stmt_insert->bindParam(':tipo_sangre', $tipo_sangre);
            $stmt_insert->bindParam(':usuario_red_social', $usuario_red_social);

            if ($stmt_insert->execute()) {
                $mensaje_insercion = "Empleado agregado exitosamente.";
                $error_insercion = false;
            } else {
                $mensaje_insercion = "Error al agregar el empleado.";
                $error_insercion = true;
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                 $mensaje_insercion = "Error: Ya existe un empleado con ese número de documento.";
            } else {
                 $mensaje_insercion = "Error de base de datos al agregar empleado: " . htmlspecialchars($e->getMessage());
            }
            $error_insercion = true;
        }
    }
}

// --- Lógica para Aumentar Sueldo (Modificada) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aumentar_sueldo_submit'])) { // Cambiado nombre del botón submit
    $percentage_aumento = filter_input(INPUT_POST, 'percentage_aumento', FILTER_VALIDATE_FLOAT);
    $target_aumento = $_POST['target_aumento'] ?? 'specific_employee'; // Default a específico
    $documento_aumento_especifico = trim($_POST['documento_aumento_especifico'] ?? '');

    if ($percentage_aumento === false || $percentage_aumento <= 0) {
        $mensaje_accion = "El porcentaje de aumento no es válido. Debe ser un número positivo.";
        $error_accion = true;
    } elseif ($target_aumento === 'specific_employee' && (empty($documento_aumento_especifico) || !ctype_digit($documento_aumento_especifico))) {
        $mensaje_accion = "Para 'Empleado Específico', debe ingresar un número de documento válido.";
        $error_accion = true;
    } else {
        $factor = 1 + ($percentage_aumento / 100);
        $sql_base_update = "UPDATE empleado SET sueldobasico = ROUND(sueldobasico * :factor, 2)";
        $params_update = [':factor' => $factor];
        $where_clause_update = "";

        $fetch_old_salary = false;

        switch ($target_aumento) {
            case 'female':
                $where_clause_update = " WHERE sexo = 'F'";
                break;
            case 'male':
                $where_clause_update = " WHERE sexo = 'M'";
                break;
            case 'min_wage_earners':
                $where_clause_update = " WHERE sueldobasico <= :salario_minimo_val";
                $params_update[':salario_minimo_val'] = $salario_minimo;
                break;
            case 'specific_employee':
                $where_clause_update = " WHERE documento = :documento_especifico";
                $params_update[':documento_especifico'] = $documento_aumento_especifico;
                $fetch_old_salary = true;
                break;
            case 'all':
            default:
                // No WHERE clause for all
                break;
        }
        
        $sql_final_update = $sql_base_update . $where_clause_update;

        try {
            $pdo->beginTransaction();

            $nombre_empleado_especifico = null;
            $sueldo_anterior_especifico = null;

            if ($fetch_old_salary && !empty($documento_aumento_especifico)) {
                $stmt_fetch_old = $pdo->prepare("SELECT nombre, sueldobasico FROM empleado WHERE documento = :documento FOR UPDATE");
                $stmt_fetch_old->bindParam(':documento', $documento_aumento_especifico);
                $stmt_fetch_old->execute();
                $empleado_data = $stmt_fetch_old->fetch(PDO::FETCH_ASSOC);

                if ($empleado_data) {
                    $nombre_empleado_especifico = $empleado_data['nombre'];
                    $sueldo_anterior_especifico = (float)$empleado_data['sueldobasico'];
                } else {
                    throw new Exception("No se encontró el empleado específico con documento: " . htmlspecialchars($documento_aumento_especifico));
                }
            }

            $stmt_update = $pdo->prepare($sql_final_update);
            if ($stmt_update->execute($params_update)) {
                $affected_rows = $stmt_update->rowCount();
                if ($affected_rows > 0) {
                    if ($fetch_old_salary && $nombre_empleado_especifico && $sueldo_anterior_especifico !== null) {
                        $sueldo_nuevo_especifico = $sueldo_anterior_especifico * $factor;
                        $mensaje_accion = "Aumento del {$percentage_aumento}% aplicado a " . htmlspecialchars($nombre_empleado_especifico) . 
                                          " (Doc: " . htmlspecialchars($documento_aumento_especifico) . ").<br>" .
                                          "Sueldo Anterior: $" . number_format($sueldo_anterior_especifico, 2, ',', '.') . "<br>" .
                                          "Sueldo Nuevo: $" . number_format(round($sueldo_nuevo_especifico,2), 2, ',', '.');
                    } else {
                        $mensaje_accion = "Aumento del {$percentage_aumento}% aplicado a {$affected_rows} empleado(s) exitosamente.";
                    }
                    $error_accion = false;
                } else {
                    $mensaje_accion = "No se aplicó el aumento a ningún empleado (ninguno cumplía el criterio, el documento no existe o el sueldo no cambió).";
                    // $error_accion = true; // No necesariamente un error si 0 filas afectadas es esperado
                }
                $pdo->commit();
            } else {
                $pdo->rollBack();
                $mensaje_accion = "Error al ejecutar la actualización de sueldos.";
                $error_accion = true;
            }
        } catch (Exception $e) { // Captura PDOException y Exception general
            if($pdo->inTransaction()){
                $pdo->rollBack();
            }
            $mensaje_accion = "Error de base de datos: " . htmlspecialchars($e->getMessage());
            $error_accion = true;
        }
    }
}


// --- Lógica para Eliminar Empleado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_empleado'])) {
    $documento_eliminar = trim($_POST['documento_eliminar']);
    if (empty($documento_eliminar)) {
        $mensaje_accion = "Por favor, ingrese el documento del empleado a eliminar.";
        $error_accion = true;
    } elseif (!ctype_digit($documento_eliminar)) {
        $mensaje_accion = "El documento para eliminar solo debe contener números.";
        $error_accion = true;
    } else {
        try {
            $sql_delete_empleado = "DELETE FROM empleado WHERE documento = :documento";
            $stmt_delete = $pdo->prepare($sql_delete_empleado);
            $stmt_delete->bindParam(':documento', $documento_eliminar);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->rowCount() > 0) {
                    $mensaje_accion = "Empleado con documento " . htmlspecialchars($documento_eliminar) . " eliminado exitosamente.";
                    $error_accion = false;
                } else {
                    $mensaje_accion = "No se encontró ningún empleado con el documento " . htmlspecialchars($documento_eliminar) . " para eliminar.";
                    $error_accion = true;
                }
            } else {
                $mensaje_accion = "Error al eliminar el empleado con documento " . htmlspecialchars($documento_eliminar) . ".";
                $error_accion = true;
            }
        } catch (\PDOException $e) {
            $mensaje_accion = "Error de base de datos al eliminar empleado: " . htmlspecialchars($e->getMessage());
            $error_accion = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión y Reportes de Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #007bff; color: white; }
        .card-header.actions-header { background-color: #6c757d; }
        .card-header.report-header-special { background-color: #198754; }
        .card-header.report-header-list { background-color: #fd7e14; } /* Nuevo color para el listado */
        .container { padding-top: 20px; padding-bottom: 40px; }
        .report-value { font-weight: bold; color: #28a745; }
        .table-responsive-md { margin-top: 1rem; }
        .social-media-button { position: fixed; bottom: 20px; right: 20px; background-color: #007bff; color: white; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 28px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); cursor: pointer; z-index: 1000; transition: background-color 0.3s ease, transform 0.3s ease; }
        .social-media-button:hover { background-color: #0056b3; transform: scale(1.1); }
        .social-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: none; justify-content: center; align-items: center; z-index: 1050; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .social-modal-overlay.active { display: flex; opacity: 1; }
        .social-modal-content { background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); text-align: center; width: 90%; max-width: 400px; position: relative; transform: translateY(-50px) scale(0.9); opacity: 0; transition: transform 0.3s ease-out, opacity 0.3s ease-out; }
        .social-modal-overlay.active .social-modal-content { transform: translateY(0) scale(1); opacity: 1; }
        .social-modal-content h3 { color: #333; margin-bottom: 25px; font-weight: 600; }
        .social-links a { display: inline-block; margin: 0 12px; font-size: 38px; color: #007bff; transition: transform 0.2s ease-in-out, color 0.2s ease; }
        .social-links a:hover { transform: scale(1.2); color: #0056b3; }
        .social-modal-close-btn { position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; color: #888; cursor: pointer; transition: color 0.2s ease; }
        .social-modal-close-btn:hover { color: #333; }
        .table th { white-space: nowrap; } /* Evita que los encabezados de tabla se rompan */
    </style>
</head>
<body>

    <div class="container">
        <header class="text-center mb-5">
            <h1 class="display-5"><i class="bi bi-people-fill"></i> Gestión y Reportes de Empleados</h1>
        </header>

        <div class="card mb-4 shadow-sm">
            <div class="card-header"> <h2 class="h5 mb-0"><i class="bi bi-person-plus-fill"></i> Agregar Nuevo Empleado</h2> </div>
            <div class="card-body">
                <?php if (!empty($mensaje_insercion)): ?> <div class="alert <?php echo $error_insercion ? 'alert-danger' : 'alert-success'; ?>" role="alert"> <?php echo htmlspecialchars($mensaje_insercion); ?> </div> <?php endif; ?>
                <form method="POST" action=""> 
                    <div class="row g-3"> 
                        <div class="col-md-6"> 
                            <label for="documento" class="form-label">Documento (*):</label> 
                            <input type="text" class="form-control" id="documento" name="documento" required pattern="[0-9]*" inputmode="numeric" title="El documento solo debe contener números." oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div> 
                        <div class="col-md-6"> 
                            <label for="nombre" class="form-label">Nombre Completo (*):</label> 
                            <input type="text" class="form-control" id="nombre" name="nombre" required pattern="[A-Za-zñÑáéíóúÁÉÍÓÚüÜ\s']+" title="El nombre solo debe contener letras, espacios y apóstrofes." oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚüÜ\s']/g, '')">
                        </div> 
                        <div class="col-md-6"> <label for="sexo" class="form-label">Sexo (*):</label> <select class="form-select" id="sexo" name="sexo" required> <option value="">Seleccione...</option> <option value="f">Femenino</option> <option value="m">Masculino</option> <option value="o">Otro</option> </select> </div> 
                        <div class="col-md-6"> <label for="domicilio" class="form-label">Domicilio:</label> <input type="text" class="form-control" id="domicilio" name="domicilio"> </div> 
                        <div class="col-md-6"> <label for="fechanacimiento" class="form-label">Fecha de Nacimiento (*):</label> <input type="date" class="form-control" id="fechanacimiento" name="fechanacimiento" required> </div> 
                        <div class="col-md-6"> <label for="fechaingreso" class="form-label">Fecha de Ingreso (*):</label> <input type="date" class="form-control" id="fechaingreso" name="fechaingreso" required> </div> 
                        <div class="col-md-6"> <label for="sueldobasico" class="form-label">Sueldo Básico (*):</label> <input type="number" class="form-control" id="sueldobasico" name="sueldobasico" step="0.01" min="0" required> </div> 
                        <div class="col-md-6"> <label for="estado_civil" class="form-label">Estado Civil:</label> <select class="form-select" id="estado_civil" name="estado_civil"> <option value="">Seleccione...</option> <option value="Soltero/a">Soltero/a</option> <option value="Casado/a">Casado/a</option> <option value="Divorciado/a">Divorciado/a</option> <option value="Viudo/a">Viudo/a</option> <option value="Unión Libre">Unión Libre</option> </select> </div> 
                        <div class="col-md-6"> <label for="tipo_sangre" class="form-label">Tipo de Sangre:</label> <select class="form-select" id="tipo_sangre" name="tipo_sangre"> <option value="">Seleccione...</option> <option value="O+">O+</option> <option value="O-">O-</option> <option value="A+">A+</option> <option value="A-">A-</option> <option value="B+">B+</option> <option value="B-">B-</option> <option value="AB+">AB+</option> <option value="AB-">AB-</option> </select> </div> 
                        <div class="col-md-6"> <label for="usuario_red_social" class="form-label">Usuario Red Social (Ej: @usuario):</label> <input type="text" class="form-control" id="usuario_red_social" name="usuario_red_social"> </div> 
                    </div> 
                    <button type="submit" name="agregar_empleado" class="btn btn-primary mt-3"><i class="bi bi-check-circle-fill"></i> Guardar Empleado</button> 
                </form>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header actions-header"> <h2 class="h5 mb-0"><i class="bi bi-pencil-square"></i> Acciones sobre Empleados</h2> </div>
            <div class="card-body">
                <?php if (!empty($mensaje_accion)): ?> 
                    <div class="alert <?php echo $error_accion ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert"> 
                        <?php echo $mensaje_accion; // Permite HTML como <br> para el mensaje detallado ?> 
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div> 
                <?php endif; ?>
                
                <h3 class="h6 mt-2"><i class="bi bi-graph-up"></i> Aplicar Aumento de Sueldo</h3>
                <form method="POST" action="" class="mb-4 p-3 border rounded bg-light">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="percentage_aumento" class="form-label">Porcentaje Aumento (%):</label>
                            <input type="number" class="form-control form-control-sm" id="percentage_aumento" name="percentage_aumento" value="10.0" step="0.1" min="0.1" max="100" required>
                        </div>
                        <div class="col-md-4">
                            <label for="target_aumento" class="form-label">Aplicar A:</label>
                            <select class="form-select form-select-sm" id="target_aumento" name="target_aumento">
                                <option value="specific_employee">Empleado Específico</option>
                                <option value="all">Todos los Empleados</option>
                                <option value="female">Solo Mujeres</option>
                                <option value="male">Solo Hombres</option>
                                <option value="min_wage_earners">Sueldo Mínimo o Menos</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="documentoAumentoField">
                            <label for="documento_aumento_especifico" class="form-label">Documento Específico:</label>
                            <input type="text" class="form-control form-control-sm" id="documento_aumento_especifico" name="documento_aumento_especifico" placeholder="Solo si es específico" pattern="[0-9]*" inputmode="numeric" title="Solo números">
                        </div>
                        <div class="col-12">
                             <button type="submit" name="aumentar_sueldo_submit" class="btn btn-info btn-sm w-100 mt-2"><i class="bi bi-percent"></i> Aplicar Aumento</button>
                        </div>
                    </div>
                </form>

                <h3 class="h6 mt-4"><i class="bi bi-person-x-fill"></i> Eliminar Empleado</h3>
                <form method="POST" action="" class="p-3 border rounded bg-light" onsubmit="return confirm('ADVERTENCIA:\n¿Está TOTALMENTE seguro de que desea eliminar a este empleado?\nEsta acción NO SE PUEDE DESHACER.');"> 
                    <div class="row g-2 align-items-end"> 
                        <div class="col-sm-8"> 
                            <label for="documento_eliminar" class="form-label">Documento del Empleado:</label> 
                            <input type="text" class="form-control form-control-sm" id="documento_eliminar" name="documento_eliminar" placeholder="Ingrese documento" required pattern="[0-9]*" inputmode="numeric" title="Solo números"> 
                        </div> 
                        <div class="col-sm-4"> <button type="submit" name="eliminar_empleado" class="btn btn-danger btn-sm w-100"><i class="bi bi-trash-fill"></i> Eliminar</button> </div> 
                    </div> 
                </form>
            </div>
        </div>

        <hr class="my-5">
        <h2 class="text-center mb-4 display-6">Reportes Generales</h2>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4"> 
            <div class="col"> 
                <div class="card h-100 shadow-sm"> 
                    <div class="card-header"> 
                        <h3 class="h6 mb-0"><i class="bi bi-cake2-fill"></i> 1. Cumpleaños en Mayo</h3> 
                        </div> 
                        <div class="card-body"> 
                            <p class="card-text">Regalos para Mayo:</p> 
                            <?php
            $sql1 = "SELECT sexo, COUNT(*) AS total_cumpleaneros_mayo FROM empleado WHERE MONTH(fechanacimiento) = 5 GROUP BY sexo;";
            try { $stmt1 = $pdo->query($sql1); 
                $rosas = 0; $corbatas = 0; 
                $otros_regalos_mayo = 0; 
                $found_may_birthdays = false;
            while ($row1 = $stmt1->fetch()) { $found_may_birthdays = true; if (strtolower($row1['sexo']) === 'f') { $rosas = $row1['total_cumpleaneros_mayo']; 
            } 
            elseif (strtolower($row1['sexo']) === 'm') { 
                $corbatas = $row1['total_cumpleaneros_mayo'];
             } else { $otros_regalos_mayo += $row1['total_cumpleaneros_mayo']; } }
            if ($found_may_birthdays) { echo "<ul class='list-group list-group-flush'>"; echo "<li class='list-group-item d-flex justify-content-between align-items-center'>Ramos de rosas (F): <span class='badge bg-primary rounded-pill'>" . $rosas . "</span></li>"; echo "<li class='list-group-item d-flex justify-content-between align-items-center'>Corbatas (M): <span class='badge bg-primary rounded-pill'>" . $corbatas . "</span></li>"; if ($otros_regalos_mayo > 0) { echo "<li class='list-group-item d-flex justify-content-between align-items-center'>Regalos (Otro): <span class='badge bg-secondary rounded-pill'>" . $otros_regalos_mayo . "</span></li>"; } echo "</ul>"; } else { echo "<p class='text-muted'>No hay cumpleaños en Mayo.</p>"; }
            } catch (\PDOException $e) { echo "<div class='alert alert-danger' role='alert'>Error: " . htmlspecialchars($e->getMessage()) . "</div>"; } ?> </div> </div> </div>
            <div class="col"> <div class="card h-100 shadow-sm"> <div class="card-header"> <h3 class="h6 mb-0"><i class="bi bi-gender-ambiguous"></i> 2. Empleados por Sexo</h3> </div> <div class="card-body"> <?php
            $sql2 = "SELECT sexo, COUNT(*) AS total_empleados FROM empleado GROUP BY sexo;";
            try { $stmt2 = $pdo->query($sql2); if ($stmt2->rowCount() > 0) { echo "<ul class='list-group list-group-flush'>";
            while ($row2 = $stmt2->fetch()) { $sexo_display = 'Desconocido'; if (strtolower($row2['sexo']) === 'f') $sexo_display = 'Femenino'; if (strtolower($row2['sexo']) === 'm') $sexo_display = 'Masculino'; if (strtolower($row2['sexo']) === 'o') $sexo_display = 'Otro'; echo "<li class='list-group-item d-flex justify-content-between align-items-center'>Sexo " . htmlspecialchars($sexo_display) . ": <span class='badge bg-info rounded-pill'>" . $row2['total_empleados'] . "</span></li>"; } echo "</ul>"; } else { echo "<p class='text-muted'>No hay datos.</p>"; }
            } catch (\PDOException $e) { echo "<div class='alert alert-danger' role='alert'>Error: " . htmlspecialchars($e->getMessage()) . "</div>"; } ?> </div> </div> </div>
            <div class="col"> <div class="card h-100 shadow-sm"> <div class="card-header"> <h3 class="h6 mb-0"><i class="bi bi-graph-up-arrow"></i> 3. Sueldos +10% (General)</h3> </div> <div class="card-body"> <?php
            $sql3 = "SELECT nombre, sueldobasico, sueldobasico * 1.10 AS sueldo_con_aumento FROM empleado ORDER BY nombre ASC;"; 
            // Modificado para mostrar ambos
            try { $stmt3 = $pdo->query($sql3); if ($stmt3->rowCount() > 0) { echo "<div class='table-responsive-md' style='max-height: 200px; overflow-y: auto;'>"; echo "<table class='table table-striped table-bordered table-hover table-sm'>"; echo "<thead class='table-dark'><tr><th>Nombre</th><th>Sueldo Actual</th><th>Sueldo +10%</th></tr></thead>"; echo "<tbody>";
            while ($row3 = $stmt3->fetch()) { echo "<tr>"; echo "<td>" . htmlspecialchars($row3['nombre']) . "</td>"; echo "<td>$" . number_format($row3['sueldobasico'], 2, ',', '.') . "</td>"; echo "<td>$" . number_format($row3['sueldo_con_aumento'], 2, ',', '.') . "</td>"; echo "</tr>"; } echo "</tbody></table></div>"; } else { echo "<p class='text-muted'>No hay empleados.</p>"; }
            } catch (\PDOException $e) { echo "<div class='alert alert-danger' role='alert'>Error: " . htmlspecialchars($e->getMessage()) . "</div>"; } ?> </div> </div> </div>
            <div class="col"> <div class="card h-100 shadow-sm"> <div class="card-header"> <h3 class="h6 mb-0"><i class="bi bi-cash-stack"></i> 4. Total Nómina por Sexo</h3> </div> <div class="card-body"> <?php
            $sql4 = "SELECT sexo, SUM(sueldobasico) AS total_nomina_por_sexo FROM empleado GROUP BY sexo;";
            try { $stmt4 = $pdo->query($sql4); if ($stmt4->rowCount() > 0) { echo "<ul class='list-group list-group-flush'>";
            while ($row4 = $stmt4->fetch()) { $sexo_display = 'Desconocido'; if (strtolower($row4['sexo']) === 'f') $sexo_display = 'Femenino'; if (strtolower($row4['sexo']) === 'm') $sexo_display = 'Masculino'; if (strtolower($row4['sexo']) === 'o') $sexo_display = 'Otro'; echo "<li class='list-group-item d-flex justify-content-between align-items-center'>Nómina " . htmlspecialchars($sexo_display) . ": <span class='badge bg-success rounded-pill'>$" . number_format($row4['total_nomina_por_sexo'], 2, ',', '.') . "</span></li>"; } echo "</ul>"; } else { echo "<p class='text-muted'>No hay datos.</p>"; }
            } catch (\PDOException $e) { echo "<div class='alert alert-danger' role='alert'>Error: " . htmlspecialchars($e->getMessage()) . "</div>"; } ?> </div> </div> </div>
            <div class="col"> <div class="card h-100 shadow-sm"> <div class="card-header"> <h3 class="h6 mb-0"><i class="bi bi-person-check-fill"></i> 5. Empleados Sobre Mínimo</h3> </div> <div class="card-body"> <p class="card-text">*(Mínimo: <span class="report-value">$<?php echo number_format($salario_minimo, 2, ',', '.'); ?></span>)*</p> <?php
            $sql5 = "SELECT COUNT(*) AS total_empleados_sobre_minimo FROM empleado WHERE sueldobasico > :salario_minimo";
            try { $stmt5 = $pdo->prepare($sql5); $stmt5->bindParam(':salario_minimo', $salario_minimo, PDO::PARAM_STR); $stmt5->execute(); $resultado5 = $stmt5->fetch();
            if ($resultado5) { echo "<p class='fs-5'>Cantidad: <strong class='report-value'>" . $resultado5['total_empleados_sobre_minimo'] . "</strong> empleados.</p>"; } else { echo "<p class='text-muted'>No se pudo obtener conteo.</p>"; }
            } catch (\PDOException $e) { echo "<div class='alert alert-danger' role='alert'>Error: " . htmlspecialchars($e->getMessage()) . "</div>"; } ?> </div> </div> </div>
            <div class="col"> <div class="card h-100 shadow-sm"> <div class="card-header report-header-special"> <h3 class="h6 mb-0"><i class="bi bi-trophy-fill"></i> 6. Empleado con Mayor Sueldo</h3> </div> <div class="card-body"> <?php
            $sql6 = "SELECT documento, nombre, sueldobasico FROM empleado ORDER BY sueldobasico DESC LIMIT 1;";
            try { $stmt6 = $pdo->query($sql6); $empleado_mayor_sueldo = $stmt6->fetch();
            if ($empleado_mayor_sueldo) { echo "<p><strong>Nombre:</strong> " . htmlspecialchars($empleado_mayor_sueldo['nombre']) . "</p>"; echo "<p><strong>Documento:</strong> " . htmlspecialchars($empleado_mayor_sueldo['documento']) . "</p>"; echo "<p><strong>Sueldo Básico:</strong> <span class='report-value'>$" . number_format($empleado_mayor_sueldo['sueldobasico'], 2, ',', '.') . "</span></p>"; } else { echo "<p class='text-muted'>No hay empleados registrados.</p>"; }
            } catch (\PDOException $e) { echo "<div class='alert alert-danger' role='alert'>Error: " . htmlspecialchars($e->getMessage()) . "</div>"; } ?> </div> </div> </div>
        </div> 

        <div class="card mt-4 mb-4 shadow-sm">
            <div class="card-header report-header-list"> <h3 class="h5 mb-0"><i class="bi bi-list-ul"></i> 7. Listado Completo de Empleados</h3>
            </div>
            <div class="card-body">
                <?php
                $sql_all_employees = "SELECT documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social FROM empleado ORDER BY nombre ASC;";
                try {
                    $stmt_all_employees = $pdo->query($sql_all_employees);
                    if ($stmt_all_employees->rowCount() > 0) {
                        echo "<div class='table-responsive'>";
                        echo "<table class='table table-striped table-bordered table-hover table-sm align-middle'>";
                        echo "<thead class='table-dark'><tr>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Sexo</th>
                                <th>Domicilio</th>
                                <th>Fecha Ingreso</th>
                                <th>Fecha Nacimiento</th>
                                <th>Sueldo Básico</th>
                                <th>Estado Civil</th>
                                <th>Tipo Sangre</th>
                                <th>Usuario Red Social</th>
                              </tr></thead>";
                        echo "<tbody>";
                        while ($employee = $stmt_all_employees->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($employee['documento']) . "</td>";
                            echo "<td>" . htmlspecialchars($employee['nombre']) . "</td>";
                            $sexo_display_table = 'N/A';
                            if (strtolower($employee['sexo']) === 'f') $sexo_display_table = 'Femenino';
                            if (strtolower($employee['sexo']) === 'm') $sexo_display_table = 'Masculino';
                            if (strtolower($employee['sexo']) === 'o') $sexo_display_table = 'Otro';
                            echo "<td>" . htmlspecialchars($sexo_display_table) . "</td>";
                            echo "<td>" . htmlspecialchars($employee['domicilio'] ?: 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars(date("d/m/Y", strtotime($employee['fechaingreso']))) . "</td>";
                            echo "<td>" . htmlspecialchars(date("d/m/Y", strtotime($employee['fechanacimiento']))) . "</td>";
                            echo "<td class='text-end'>$" . number_format($employee['sueldobasico'], 2, ',', '.') . "</td>";
                            echo "<td>" . htmlspecialchars($employee['estado_civil'] ?: 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($employee['tipo_sangre'] ?: 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($employee['usuario_red_social'] ?: 'N/A') . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table></div>";
                    } else {
                        echo "<p class='text-muted'>No hay empleados registrados para mostrar.</p>";
                    }
                } catch (\PDOException $e) {
                    echo "<div class='alert alert-danger' role='alert'>Error al cargar el listado de empleados: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
                ?>
            </div>
        </div>
        </div> 

    <footer class="text-center mt-5 py-3 bg-light">
        <p class="mb-0">© <?php echo date("Y"); ?> Mi Empresa - Reportes de Empleados</p>
    </footer>

    <button id="socialMediaBtn" class="social-media-button" title="Nuestras Redes Sociales"> <i class="bi bi-share-fill"></i> </button>
    <div id="socialMediaModalOverlay" class="social-modal-overlay"> <div class="social-modal-content"> <button id="closeSocialModalBtn" class="social-modal-close-btn" title="Cerrar">×</button> <h3>¡Síguenos!</h3> <div class="social-links"> <a href="https://facebook.com/tuempresa" target="_blank" title="Facebook"><i class="bi bi-facebook"></i></a> <a href="https://instagram.com/tuempresa" target="_blank" title="Instagram"><i class="bi bi-instagram"></i></a> <a href="https://twitter.com/tuempresa" target="_blank" title="Twitter/X"><i class="bi bi-twitter-x"></i></a> <a href="https://linkedin.com/company/tuempresa" target="_blank" title="LinkedIn"><i class="bi bi-linkedin"></i></a> <a href="https://youtube.com/c/tuempresa" target="_blank" title="YouTube"><i class="bi bi-youtube"></i></a> <a href="https://wa.me/573001234567" target="_blank" title="WhatsApp"><i class="bi bi-whatsapp"></i></a> </div> </div> </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Lógica para el modal de redes sociales ---
        const socialMediaBtn = document.getElementById('socialMediaBtn');
        const socialModalOverlay = document.getElementById('socialMediaModalOverlay');
        const closeSocialModalBtn = document.getElementById('closeSocialModalBtn');
        if (socialMediaBtn && socialModalOverlay && closeSocialModalBtn) {
            socialMediaBtn.addEventListener('click', function() { socialModalOverlay.classList.add('active'); });
            closeSocialModalBtn.addEventListener('click', function() { socialModalOverlay.classList.remove('active'); });
            socialModalOverlay.addEventListener('click', function(event) { if (event.target === socialModalOverlay) { socialModalOverlay.classList.remove('active'); } });
            document.addEventListener('keydown', function(event) { if (event.key === 'Escape' && socialModalOverlay.classList.contains('active')) { socialModalOverlay.classList.remove('active'); } });
        } else { console.warn('Elementos del modal de redes no encontrados.'); }

        // --- Lógica para el formulario de aumento de sueldo ---
        const targetAumentoSelect = document.getElementById('target_aumento');
        const documentoAumentoField = document.getElementById('documentoAumentoField');
        const documentoAumentoInput = document.getElementById('documento_aumento_especifico');

        if (targetAumentoSelect && documentoAumentoField && documentoAumentoInput) {
            // Función para actualizar visibilidad y requerido
            function toggleDocumentoAumentoField() {
                if (targetAumentoSelect.value === 'specific_employee') {
                    documentoAumentoField.style.display = 'block';
                    documentoAumentoInput.required = true;
                } else {
                    documentoAumentoField.style.display = 'none';
                    documentoAumentoInput.required = false;
                    documentoAumentoInput.value = ''; // Limpiar el campo si no es necesario
                }
            }
            // Ejecutar al cargar la página para el estado inicial
            toggleDocumentoAumentoField(); 
            // Ejecutar al cambiar la selección
            targetAumentoSelect.addEventListener('change', toggleDocumentoAumentoField);
        }
    });
    </script>
</body>
</html>