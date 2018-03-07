<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para activos
$app->post('/lstactivo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $where = $d->id != '' || $d->idempresa != '' || $d->idtipo != '' || $d->iddepto != '' || $d->ffl != '' ? "WHERE " : "";
    $fltr = "";
    if($where != ''){
        $f[1] = $d->idempresa != '' ? "a.idempresa IN($d->idempresa)" : "";
        $f[2] = $d->idtipo != '' ? "d.id IN($d->idtipo)" : "";
        $f[3] = $d->iddepto != '' ? "c.id IN($d->iddepto)" : "";
        $f[4] = $d->ffl != '' ? "(a.finca LIKE '%$d->ffl%' OR a.folio LIKE '%$d->ffl%' OR a.libro LIKE '%$d->ffl%')" : "";
        $f[5] = $d->id != '' ? "a.id IN($d->id)" : "";
        for($x = 1; $x <= 5; $x++){
            if($fltr != "" && $f[$x] != ""){ $fltr .= " AND "; }
            $fltr.= $f[$x];
        }
    }

    $query = "SELECT a.id, a.idempresa, a.departamento, a.finca, a.folio, a.libro, a.horizontal, a.direccion_cat, ";
    $query.= "a.direccion_mun, a.iusi, a.por_iusi, a.valor_registro, a.metros_registro, a.valor_dicabi, ";
    $query.= "a.metros_dicabi, a.valor_muni, a.metros_muni, a.solvencia_muni, actualizadopor, ";
    $query.= "a.actualiza_info, a.observaciones, a.tipo_activo, a.nombre_corto, a.nombre_largo, ";
    $query.= "IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')) AS nomempresa, ";
    $query.= "CONCAT(c.nomdepto,' - ',c.nombre) AS nombre_depto, d.descripcion as nombre_tipo_activo, ";
    $query.= "IF(a.horizontal = 1, 'SI', 'NO') AS eshorizontal, a.zona, a.fhcreacion, a.creadopor, a.nomclienteajeno, ";
    $query.= "CONCAT(IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')), ' - ', a.finca, '-', a.folio, '-', a.libro) AS ffl, ";
    $query.= "a.multilotes, IF(a.multilotes = 1, 'SI', 'NO') AS esmultilotes, a.direcciondos, a.fechacompra ";
    $query.= "FROM activo a ";
    $query.= "LEFT JOIN empresa b ON a.idempresa=b.id ";
    $query.= "LEFT JOIN municipio c ON a.departamento = c.id ";
    $query.= "LEFT JOIN tipo_activo d ON a.tipo_activo = d.id ";
    $query.= $where.$fltr;
    $query.= "ORDER BY b.propia DESC, b.nomempresa, c.nomdepto, c.nombre, CAST(digits(a.finca) AS UNSIGNED), CAST(digits(a.folio) AS UNSIGNED), CAST(digits(a.libro) AS UNSIGNED)";
    print $db->doSelectASJson($query);
});

$app->run();