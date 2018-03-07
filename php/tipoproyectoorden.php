<?php
require 'vendor/autoload.php';
require_once 'db.php';

header('Content-Type: application/json');

$app = new \Slim\Slim();

//API para orden de tipos de proyectos
$app->get('/lista', function(){
    $db = new dbcpm();

    print $db->doSelectASJson("SELECT 
            a.*, 
            b.descripcion AS tipolocal, 
            c.descripcion AS tipoproyecto 
        FROM tipo_proyecto_orden a  
        JOIN tipolocal b ON b.id = a.idtipolocal 
        JOIN tipo_proyecto c on c.id = a.idtipo_proyecto
        ORDER BY c.descripcion, a.orden");
});

$app->post('/guardar', function(){
    $d  = json_decode(file_get_contents('php://input'));

    $t = new dbcpm();
    $db = $t->getConn();

    $datos = [
        'idtipolocal'     => $d->idtipolocal, 
        'idtipo_proyecto' => $d->idtipo_proyecto, 
        'orden'           => $d->orden
    ];

    $res = ['exito' => 0, 'update' => 0];

    if (isset($d->id)) {
        $res['id'] = $d->id;

        if ($db->update("tipo_proyecto_orden", $datos, ["id" => $d->id])) {
            $res['exito']  = 1;
            $res['update'] = 1;

            $mensaje      = "Se actualizó con éxito.";
        } else {
            if ($db->error()[0] == 0) {
                $mensaje = 'Nada que actualizar.';
            } else {
                $mensaje = 'Error al actualizar: ' . $db->error()[2] . " Es posible que esté intentando agregar datos duplicados.";
            }
        }
    } else {
        $lid = $db->insert("tipo_proyecto_orden", $datos);

        if ($lid) {
            $res['id']    = $lid;
            $res['exito'] = 1;
            $mensaje      = "Se agregó con éxito.";
        } else {
            $mensaje = 'Error al guardar: ' . $db->error()[2] . " Es posible que esté intentando agregar datos duplicados.";
        }
    }

    $res['mensaje'] = $mensaje;

    print json_encode($res);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $res = ['exito' => 0];

    if (isset($d->id)) {
        $db->doQuery("DELETE FROM tipo_proyecto_orden WHERE id = {$d->id}");
        $res['exito']   = 1;
        $res['mensaje'] = "Se eliminó con éxito.";
    } else {
        $res['mensaje'] = "Hacen falta datos obligatorios.";
    }

    print json_encode($res);
});

$app->run();