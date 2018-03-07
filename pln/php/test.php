<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/ayuda.php';
require 'models/Empleado.php';

$e = new Empleado();
$e->cargar_empleado(1);

if ($e->guardar($_GET)) {
	echo "<br>Bien!";
} else {
	echo "<br>" . $e->get_mensaje();
}

depurar($e->emp);