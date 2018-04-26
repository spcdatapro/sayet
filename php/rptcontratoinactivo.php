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

    $query = "SELECT e.nomempresa AS empresa, d.nombre AS cliente, d.nombrecorto AS abreviacliente, b.nomproyecto AS ubicacion, UnidadesPorContrato(a.id) AS unidades, a.nocontrato, a.abogado, DATE_FORMAT(a.fechainicia, '%d/%m/%Y') AS inicia,
    DATE_FORMAT(a.fechavence, '%d/%m/%Y') AS vence, DATE_FORMAT(a.fechainactivo, '%d/%m/%Y') AS inactivodesde,
    (SELECT CONCAT(MONTH(MAX(fechacobro)), '/', YEAR(MAX(fechacobro))) FROM cargo WHERE facturado = 1 AND anulado = 0 AND idcontrato = $d->idcontrato) AS ultimocobro,
    c.simbolo AS monedadep, FORMAT(a.deposito, 2) AS deposito, a.reciboprov AS recibo,
    IFNULL((SELECT CONCAT(x.simbolo, ' ', FORMAT((SUM(z.total) - SUM(IFNULL(y.monto, 0.00))), 2)) FROM factura z LEFT JOIN detcobroventa y ON z.id = y.idfactura LEFT JOIN moneda x ON x.id = z.idmoneda WHERE z.idcontrato = $d->idcontrato AND z.pagada = 0), 0.00) AS saldo,
    a.observaciones
    FROM contrato a
    LEFT JOIN proyecto b ON b.id = a.idproyecto
    LEFT JOIN moneda c ON c.id = a.idmonedadep
    LEFT JOIN cliente d ON d.id = a.idcliente
    LEFT JOIN empresa e ON e.id = a.idempresa
    WHERE a.id = $d->idcontrato";
    $info->contrato = $db->getQuery($query)[0];

    print json_encode($info);
});

$app->run();