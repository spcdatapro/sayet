<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/lstactivo', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $id = isset($d->id) ? trim($d->id) : '';
    $idempresa = isset($d->idempresa) ? trim($d->idempresa) : '';
    $idtipo = isset($d->idtipo) ? trim($d->idtipo) : '';
    $iddepto = isset($d->iddepto) ? trim($d->iddepto) : '';
    $ffl = isset($d->ffl) ? trim($d->ffl) : '';
    $estatus = isset($d->estatus) ? $d->estatus : '';

    $where = ($id !== '' || $idempresa !== '' || $idtipo !== '' || $iddepto !== '' || $ffl !== '' || $estatus !== '') ? "WHERE " : "";
    $fltr = "";
    if($where != ''){
        $f = [];

        if($idempresa !== ''){ $f[] = "a.idempresa IN($idempresa)"; }
        if($idtipo !== ''){ $f[] = "d.id IN($idtipo)"; }
        if($iddepto !== ''){ $f[] = "c.id IN($iddepto)"; }
        if($ffl !== ''){ $f[] = "(a.finca LIKE '%$ffl%' OR a.folio LIKE '%$ffl%' OR a.libro LIKE '%$ffl%')"; }
        if($id !== ''){ $f[] = "a.id IN($id)"; }
        if($estatus == 1){ $f[] = "a.debaja = 0"; }
        elseif($estatus == 2){ $f[] = "a.debaja = 1"; }

        $fltr = implode(' AND ', $f);
    }

    $query = "SELECT a.id, a.idempresa, a.departamento, a.finca, a.folio, a.libro, a.horizontal, a.direccion_cat, ";
    $query.= "a.direccion_mun, a.iusi, a.por_iusi, a.valor_registro, a.metros_registro, a.valor_dicabi, ";
    $query.= "a.metros_dicabi, a.valor_muni, a.metros_muni, a.solvencia_muni, actualizadopor, ";
    $query.= "a.actualiza_info, a.observaciones, a.tipo_activo, a.nombre_corto, a.nombre_largo, ";
    $query.= "IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')) AS nomempresa, ";
    $query.= "CONCAT(c.nomdepto,' - ',c.nombre) AS nombre_depto, d.descripcion as nombre_tipo_activo, ";
    $query.= "IF(a.horizontal = 1, 'SI', 'NO') AS eshorizontal, a.zona, a.fhcreacion, a.creadopor, a.nomclienteajeno, ";
    $query.= "CONCAT(IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')), ' - ', a.finca, '-', a.folio, '-', a.libro) AS ffl, ";
    $query.= "a.multilotes, IF(a.multilotes = 1, 'SI', 'NO') AS esmultilotes, a.direcciondos, a.fechacompra, e.bitacora ";
    $query.= "FROM activo a ";
    $query.= "LEFT JOIN empresa b ON a.idempresa=b.id ";
    $query.= "LEFT JOIN municipio c ON a.departamento = c.id ";
    $query.= "LEFT JOIN tipo_activo d ON a.tipo_activo = d.id LEFT JOIN bitacoraactivo e ON a.id = e.idactivo ";
    $query.= $where.$fltr;
    $query.= "ORDER BY b.propia DESC, b.nomempresa, c.nomdepto, c.nombre, CAST(digits(a.finca) AS UNSIGNED), CAST(digits(a.folio) AS UNSIGNED), CAST(digits(a.libro) AS UNSIGNED)";
    print $db->doSelectASJson($query);
});

$app->post('/actinact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.id, a.idempresa, a.departamento, a.finca, a.folio, a.libro, a.horizontal, a.direccion_cat, ";
    $query.= "a.direccion_mun, a.iusi, a.por_iusi, a.valor_registro, a.metros_registro, a.valor_dicabi, ";
    $query.= "a.metros_dicabi, a.valor_muni, a.metros_muni, a.solvencia_muni, actualizadopor, ";
    $query.= "a.actualiza_info, a.observaciones, a.tipo_activo, a.nombre_corto, a.nombre_largo, ";
    $query.= "IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')) AS nomempresa, ";
    $query.= "CONCAT(c.nomdepto,' - ',c.nombre) AS nombre_depto, d.descripcion as nombre_tipo_activo, ";
    $query.= "IF(a.horizontal = 1, 'SI', 'NO') AS eshorizontal, a.zona, a.fhcreacion, a.creadopor, a.nomclienteajeno, ";
    $query.= "CONCAT(IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')), ' - ', a.finca, '-', a.folio, '-', a.libro) AS ffl, ";
    $query.= "a.multilotes, IF(a.multilotes = 1, 'SI', 'NO') AS esmultilotes, a.direcciondos, a.fechacompra, DATE_FORMAT(a.fechabaja, '%d/%m/%Y') AS fechabaja ";
    $query.= "FROM activo a ";
    $query.= "LEFT JOIN empresa b ON a.idempresa=b.id ";
    $query.= "LEFT JOIN municipio c ON a.departamento = c.id ";
    $query.= "LEFT JOIN tipo_activo d ON a.tipo_activo = d.id ";
    $query.= "WHERE a.id = $d->id ";
    $query.= "ORDER BY b.propia DESC, b.nomempresa, c.nomdepto, c.nombre, CAST(digits(a.finca) AS UNSIGNED), CAST(digits(a.folio) AS UNSIGNED), CAST(digits(a.libro) AS UNSIGNED)";
    print $db->doSelectASJson($query);
});


$app->run();