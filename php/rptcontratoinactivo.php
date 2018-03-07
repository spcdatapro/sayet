<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/continact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT a.idcliente, b.nombre, b.nombrecorto ";
    $query.= "FROM contrato a INNER JOIN cliente b ON b.id = a.idcliente ";
    $query.= "WHERE a.inactivo = 1 ";
    $query.= (int)$d->idcliente > 0 ? "AND a.idcliente = $d->idcliente " : "";
    $query.= $d->fdelstr != '' ? "AND a.fechainactivo >= '$d->fdelstr' " : "";
    $query.= $d->falstr != '' ? "AND a.fechainactivo <= '$d->falstr' " : "";
    $query.= "ORDER BY b.nombre, a.fechainactivo";
    $data = $db->getQuery($query);

    print json_encode($data);
});

$app->post('/contrato', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $info = new stdClass();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS fecha";
    $info->generales = $db->getQuery($query)[0];

    $query = "SELECT a.id, a.idcliente, b.nombre AS cliente, b.nombrecorto AS abreviacliente, a.nocontrato, a.abogado, DATE_FORMAT(a.fechainicia, '%d/%m/%Y') AS fechainicia, DATE_FORMAT(a.fechavence, '%d/%m/%Y') AS fechavence, ";
    $query.= "a.inactivo, DATE_FORMAT(a.fechainactivo, '%d/%m/%Y') AS fechainactivo, a.idmoneda, c.simbolo AS moneda, a.idmonedadep, d.simbolo AS monedadep, a.deposito, a.idcuentac, a.reciboprov, a.idperiodicidad, e.descperiodicidad AS periodicidad, ";
    $query.= "IF(a.documento = 1, 'FACTURA', 'RECIBO') AS documento, IF(a.subarrendado = 1, 'Sí', 'No') AS subarrendado, a.idtipocliente, f.desctipocliente AS tipocliente, a.idempresa, g.nomempresa AS empresa, g.abreviatura AS abreviaempresa, ";
    $query.= "a.idproyecto, h.nomproyecto AS proyecto, UnidadesPorContrato(a.id) AS unidades, a.idtipoipc, i.descripcion AS tipoincremento, IF(a.cobro = 1, 'Sí', 'No') AS cobrovencido, DATE_FORMAT(a.plazofdel, '%d/%m/%Y') AS plazofdel, ";
    $query.= "DATE_FORMAT(a.plazofal, '%d/%m/%Y') AS plazofal, a.prescision, a.observaciones ";
    $query.= "FROM contrato a INNER JOIN cliente b ON b.id = a.idcliente INNER JOIN moneda c ON c.id = a.idmoneda INNER JOIN moneda d ON d.id = a.idmonedadep INNER JOIN periodicidad e ON e.id = a.idperiodicidad ";
    $query.= "INNER JOIN tipocliente f ON f.id = a.idtipocliente INNER JOIN empresa g ON g.id = a.idempresa INNER JOIN proyecto h ON h.id = a.idproyecto INNER JOIN tipoipc i ON i.id = a.idtipoipc ";
    $query.= "WHERE a.id = $d->idcontrato";
    $info->contrato = $db->getQuery($query)[0];

    print json_encode($info);
});

$app->run();