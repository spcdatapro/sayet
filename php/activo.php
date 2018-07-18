<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para activos
$app->get('/lstactivo', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idempresa, a.departamento, a.finca, a.folio, a.libro, a.horizontal, a.direccion_cat, ";
    $query.= "a.direccion_mun, a.iusi, a.por_iusi, a.valor_registro, a.metros_registro, a.valor_dicabi, ";
    $query.= "a.metros_dicabi, a.valor_muni, a.metros_muni, a.solvencia_muni, actualizadopor, ";
    $query.= "a.actualiza_info, a.observaciones, a.tipo_activo, a.nombre_corto, a.nombre_largo, ";
    $query.= "IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')) AS nomempresa, ";
    $query.= "concat(c.nomdepto,' - ',c.nombre) as nombre_depto, d.descripcion as nombre_tipo_activo, ";
    $query.= "if(a.horizontal = 1, 'SI', 'NO') as eshorizontal, a.zona, a.fhcreacion, a.creadopor, a.nomclienteajeno, ";
    $query.= "CONCAT(IF(b.propia = 1, b.nomempresa, CONCAT(b.nomempresa, ' (', a.nomclienteajeno,')')), ' - ', a.finca, '-', a.folio, '-', a.libro) AS ffl, ";
    $query.= "a.multilotes, IF(a.multilotes = 1, 'SI', 'NO') AS esmultilotes, a.direcciondos, a.fechacompra, a.debaja, a.fechabaja ";
    $query.= "FROM activo a ";
    $query.= "LEFT JOIN empresa b ON a.idempresa=b.id ";
    $query.= "LEFT JOIN municipio c ON a.departamento = c.id ";
    $query.= "LEFT JOIN tipo_activo d ON a.tipo_activo = d.id ";
    $query.= "ORDER BY b.propia DESC, b.nomempresa, c.nomdepto, c.nombre, a.finca, a.folio, a.libro";
    print $db->doSelectASJson($query);
});

$app->get('/getactivo/:idactivo', function($idactivo){
    $db = new dbcpm();
    $query = "SELECT id, idempresa, nomclienteajeno, departamento, finca, folio, libro, horizontal, direccion_cat, direccion_mun, direcciondos, iusi, por_iusi, valor_registro, metros_registro, ";
    $query.= "valor_dicabi, metros_dicabi, valor_muni, metros_muni, solvencia_muni, actualizadopor, actualiza_info, observaciones, tipo_activo, nombre_corto, nombre_largo, zona, ";
    $query.= "creadopor, fhcreacion, multilotes, fechacompra, debaja, fechabaja ";
    $query.= "FROM activo ";
    $query.= "WHERE id = $idactivo";
    $activo = $db->getQuery($query);
    print json_encode(count($activo) > 0 ? $activo[0]: []);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fechacomprastr = $d->fechacomprastr == '' ? 'NULL' : "'$d->fechacomprastr'";
    $query = "INSERT INTO activo(idempresa,departamento, finca, folio, libro, horizontal, direccion_mun,";
    $query.= "iusi, por_iusi, valor_registro, metros_registro, valor_dicabi, metros_dicabi, valor_muni, metros_muni,";
    $query.= "observaciones, tipo_activo, nombre_corto, nombre_largo, zona, fhcreacion, creadopor, nomclienteajeno, ";
    $query.= "multilotes, direcciondos, fechacompra";
    $query.= ") ";
    $query.= "VALUES(".$d->idempresa.",".$d->departamento.", '".$d->finca."', '".$d->folio."', '".$d->libro."', ".$d->horizontal;
    $query.= ", '".$d->direccion_mun."', ".$d->iusi.", ".$d->por_iusi.", ".$d->valor_registro.", ";
    $query.= $d->metros_registro.", ".$d->valor_dicabi.", ".$d->metros_dicabi.", ".$d->valor_muni.", ".$d->metros_muni;
    $query.= ", '".$d->observaciones."', ".$d->tipo_activo.", '".$d->nombre_corto."', '".$d->nombre_largo."', ".$d->zona.", NOW(), '".$d->usuario."', ";
    $query.= "'".$d->nomclienteajeno."', ".$d->multilotes.", '".$d->direcciondos."', $d->fechacomprastr)";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    print json_encode(['lastid' => $lastid]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fechacomprastr = $d->fechacomprastr == '' ? 'NULL' : "'$d->fechacomprastr'";
    $d->fechabajastr = $d->fechabajastr == '' ? 'NULL' : "'$d->fechabajastr'";
    $query = "UPDATE activo SET idempresa = ".$d->idempresa.", departamento = ".$d->departamento.", ";
    $query.= " finca = '".$d->finca."', folio = '".$d->folio."', libro = '".$d->libro."', horizontal = ".$d->horizontal;
    $query.= ", direccion_mun ='".$d->direccion_mun."', iusi = ".$d->iusi.", por_iusi = ".$d->por_iusi.", valor_registro = ".$d->valor_registro;
    $query.= ", metros_registro = ".$d->metros_registro.", valor_dicabi = ".$d->valor_dicabi.", metros_dicabi = ".$d->metros_dicabi;
    $query.= ", valor_muni = ".$d->valor_muni.", metros_muni = ".$d->metros_muni;
    $query.= ", observaciones = '".$d->observaciones."', tipo_activo = ".$d->tipo_activo.", nombre_corto = '".$d->nombre_corto;
    $query.= "', nombre_largo = '".$d->nombre_largo."', zona = ".$d->zona.", actualiza_info = NOW(), actualizadopor = '".$d->usuario."', ";
    $query.= "nomclienteajeno = '".$d->nomclienteajeno."', multilotes = ".$d->multilotes.", direcciondos = '".$d->direcciondos."', fechacompra = $d->fechacomprastr, ";
    $query.= "debaja = $d->debaja, fechabaja = $d->fechabajastr ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
    print json_encode(['lastid' => $d->id]);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM activo WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/rptactivos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();

    $where = $d->idempresa != '' || $d->idtipo != '' || $d->idmunicipio != '' ? "WHERE " : "";

    $f[1] = $d->idempresa != '' ? "b.id IN(".$d->idempresa.")" : "";
    $f[2] = $d->idtipo != '' ? "d.id IN(".$d->idtipo.")" : "";
    $f[3] = $d->idmunicipio != '' ? "c.id IN(".$d->idmunicipio.")" : "";

    $complemento = "";
    for($x = 1; $x <= 3; $x++){
        if($complemento != "" && $f[$x] != ""){
            $complemento .= " AND ";
        }
        $complemento.= $f[$x];
    }

    $query = "SELECT a.idempresa, b.nomempresa, d.id AS idtipo, d.descripcion AS tipo, CONCAT(a.finca, '-',a.folio, '-', a.libro) AS finca, ";
    $query.= "a.nombre_corto, CONCAT(a.direccion_mun, ' ', IF(ISNULL(a.direcciondos), '', a.direcciondos), ', zona ', a.zona) AS direccion_mun, ";
    $query.= "a.iusi, a.valor_muni, a.metros_muni, ";
    $query.= "IF(a.horizontal = 1, 'SI', 'NO') AS eshorizontal, IF(a.multilotes = 1, 'SI', 'NO') AS esmultilotes, a.id AS idactivo ";
    $query.= "FROM activo a LEFT JOIN empresa b on a.idempresa=b.id LEFT JOIN municipio c on a.departamento = c.id LEFT JOIN tipo_activo d on a.tipo_activo = d.id ";
    $query.= $where.$complemento." ";
    $query.= "ORDER BY b.nomempresa, d.descripcion, a.finca, a.folio, a.libro";
    //echo $query."<br/>";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/rptpagosiusi', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $where = (int)$d->idempresa > 0 || (int)$d->depto > 0 ? "WHERE " : "";
    $f[1] = (int)$d->idempresa > 0 ? "a.idempresa = ".$d->idempresa : "";
    $f[2] = (int)$d->depto > 0 ? "b.id = ".$d->depto : "";
    $complemento = "";
    for($x = 1; $x <= 2; $x++){
        if($complemento != "" && $f[$x] != ""){
            $complemento .= " AND ";
        }
        $complemento.= $f[$x];
    }
    $query = "SELECT a.id, b.id AS iddepto, CONCAT(b.nomdepto, ' - ', b.nombre) AS departamento, c.nomempresa AS empresa, ";
    $query.= "CONCAT(a.finca, '-', a.folio, '-', a.libro) AS finca, ";
    $query.= "IF(a.horizontal = 0, 'No', 'Sí') AS eshorizontal, a.iusi, (a.iusi * (a.por_iusi / 1000)) AS apagar, a.por_iusi, IF(a.multilotes = 1, 'SI', 'NO') AS esmultilotes ";
    $query.= "FROM activo a LEFT JOIN municipio b ON b.id = a.departamento LEFT JOIN empresa c ON c.id = a.idempresa ";
    $query.= $where.$complemento." ";
    $query.= "ORDER BY b.nomdepto, b.nombre, c.nomempresa, a.finca, a.folio, a.libro";
    print $db->doSelectASJson($query);
});

$app->get('/lstproyact/:idactivo', function($idactivo){
    $db = new dbcpm();
    $query = "SELECT a.id AS idactivo, c.id AS idproyecto, c.idempresa, d.nomempresa AS empresa, c.nomproyecto AS proyecto, c.registro, c.referencia ";
    $query.= "FROM activo a INNER JOIN detalle_activo_proyecto b ON a.id = b.idactivo INNER JOIN proyecto c ON c.id = b.idproyecto ";
    $query.= "INNER JOIN empresa d ON d.id = c.idempresa ";
    $query.= "WHERE a.id = ".$idactivo." ";
    $query.= "ORDER BY d.nomempresa, c.nomproyecto";
    print $db->doSelectASJson($query);
});

//API para bitacoras de activos
$app->get('/lstbitacora/:idactivo', function($idactivo){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, idactivo, fhbitacora, usuario, bitacora FROM bitacoraactivo WHERE idactivo = ".$idactivo." ";
    $query.= "ORDER BY fhbitacora DESC";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->post('/cb', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO bitacoraactivo(idactivo, fhbitacora, usuario, bitacora) ";
    $query.= "VALUES(".$d->idactivo.", NOW(), '".$d->usuario."', '".$d->bitacora."')";
    $ins = $conn->query($query);
    $lastid = $conn->query("SELECT LAST_INSERT_ID()")->fetchColumn(0);
    print json_encode(['lastid' => $lastid]);
});

/*
$app->post('/ub', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE bitacoraactivo SET idempresa = ".$d->idempresa.", departamento = ".$d->departamento.", ";
    $query.= " finca = '".$d->finca."', folio = '".$d->folio."', libro = '".$d->libro."', horizontal = ".$d->horizontal;
    $query.= ", direccion_mun ='".$d->direccion_mun."', iusi = ".$d->iusi.", por_iusi = ".$d->por_iusi.", valor_registro = ".$d->valor_registro;
    $query.= ", metros_registro = ".$d->metros_registro.", valor_dicabi = ".$d->valor_dicabi.", metros_dicabi = ".$d->metros_dicabi;
    $query.= ", valor_muni = ".$d->valor_muni.", metros_muni = ".$d->metros_muni;
    $query.= ", observaciones = '".$d->observaciones."', tipo_activo = ".$d->tipo_activo.", nombre_corto = '".$d->nombre_corto;
    $query.= "', nombre_largo = '".$d->nombre_largo."', zona = ".$d->zona.", actualiza_info = NOW(), actualizadopor = '".$d->usuario."' ";
    $query.= "WHERE id = ".$d->id;
    $upd = $conn->query($query);
    print json_encode(['lastid' => $d->id]);
});
*/


$app->post('/db', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "DELETE FROM bitacoraactivo WHERE id = ".$d->id;
    $del = $conn->query($query);
});

$app->run();