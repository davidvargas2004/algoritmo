<?php

require_once 'conxion.php';
// importante establecer el salario minimo legal mensual vigente colombia 2025

if(!isset($salario_minimo)){
    $salario_minimo = 1423500.00;
}



//Menajes para la insercion de empledos

$mensaje_insercion = '';
$error_insercion = false;

//Mensajes para otras acciones sobre empleados aumento,eliminacion
$mensaje_accion='';
$error_accion = false;

//logica para insertar nuevo empleado 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_empleado'])){
    $documento = trim($_POST['documento']);
    $nombre = trim($_POST['nombre']);
    $sexo = $_POST['sexo'];
    $domicilio = trim($_POST['domicilio']);
    $fechaingreso = $_POST['fechaingreso'];
    $fechanacimiento = $_POST['fechanacimiento'];
    $sueldoBasico = filter_var($_POST['sueldobasico'], FILTER_VALIDATE_FLOAT);
    $estado_civil = $_POST['estado_civil'];
    $tipo_sangre = $_POST['tipo_sangre'];
    $usuiario_red_social = trim($_POST['usuario_red_social'] ?: NULL);


    //validar los campos

    if (empty($documento) || empty($nombre) || empty($sexo) || empty($fechaingreso) || empty($fechanacimiento)  || $sueldobasico === false || $sueldobasico < 0){
        $mensaje_insercion = "Por favor, complete todos los campos obligatorios (*) y asegurese de que el sueldo sea valido.";
        $error_insercion = true;
    } elseif (!ctype_digit($documento)){
        $mensaje_insercion = "El campo 'Documento' solo debe contener numeros.";
        $error_insercion = true;
    }---
    
}
