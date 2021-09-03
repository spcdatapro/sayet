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

function getSaldoCliente($data){
    $db = new dbcpm();

    $url = 'http://localhost/sayet/php/rptecuentacli.php/rptecuentacli';
    $data = ["clistr" => $data['idcliente'], "detalle" => 0, "falstr" => $data['falstr'], "idempresa" => $data['idempresa'], "idcontrato" => $data['idcontrato']];
    $saldoCliente = json_decode($db->CallJSReportAPI('POST', $url, json_encode($data)));
    if(is_array($saldoCliente)){
        if(count($saldoCliente) > 0){
            $saldo = $saldoCliente[0];
            if(isset($saldo->saldo)){
                return number_format($saldo->saldo, 2);
            } else {
                return '0.00';
            }
        } else {
            return '0.00';
        }
    }
    return '0.00';
}

$app->post('/contrato', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $info = new stdClass();

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS fecha";
    $info->generales = $db->getQuery($query)[0];

    $query = "SELECT e.nomempresa AS empresa, d.nombre AS cliente, d.nombrecorto AS abreviacliente, b.nomproyecto AS ubicacion, UnidadesPorContrato(a.id) AS unidades, a.nocontrato, a.abogado, DATE_FORMAT(a.fechainicia, '%d/%m/%Y') AS inicia,
    DATE_FORMAT(a.fechavence, '%d/%m/%Y') AS vence, DATE_FORMAT(a.fechainactivo, '%d/%m/%Y') AS inactivodesde,
    (SELECT CONCAT(MONTH(MAX(fechacobro)), '/', YEAR(MAX(fechacobro))) FROM cargo WHERE facturado = 1 AND anulado = 0 AND idcontrato = $d->idcontrato) AS ultimocobro,
    c.simbolo AS monedadep, FORMAT(a.deposito, 2) AS deposito, a.reciboprov AS recibo, 0.00 AS saldo, a.observaciones, a.idcliente, a.idempresa, DATE_FORMAT(NOW(), '%Y-%m-%d') AS falstr,
    (SELECT GROUP_CONCAT(DISTINCT y.desctiposervventa ORDER BY y.desctiposervventa SEPARATOR ', ') FROM detfactcontrato z INNER JOIN tiposervicioventa y ON y.id = z.idtipoventa WHERE idcontrato = $d->idcontrato) AS servicios,
    0.00 saldocontrato, a.id AS idcontrato
    FROM contrato a
    LEFT JOIN proyecto b ON b.id = a.idproyecto
    LEFT JOIN moneda c ON c.id = a.idmonedadep
    LEFT JOIN cliente d ON d.id = a.idcliente
    LEFT JOIN empresa e ON e.id = a.idempresa
    WHERE a.id = $d->idcontrato";
    $info->contrato = $db->getQuery($query)[0];

    $saldoCliente = getSaldoCliente([
        'idcliente' => $info->contrato->idcliente,
        'falstr' => $info->contrato->falstr,
        'idempresa' => $info->contrato->idempresa,
        'idcontrato' => 0
    ]);

    $saldoClienteContrato = getSaldoCliente([
        'idcliente' => $info->contrato->idcliente,
        'falstr' => $info->contrato->falstr,
        'idempresa' => $info->contrato->idempresa,
        'idcontrato' => $info->contrato->idcontrato
    ]);    

    $info->contrato->saldo = $saldoCliente;
    $info->contrato->saldocontrato = $saldoClienteContrato;

    $query = "SELECT b.desctiposervventa AS servicio, FORMAT(a.montoflatconiva, 2) AS monto 
    FROM detfact a
    INNER JOIN tiposervicioventa b ON b.id = a.idtiposervicio
    WHERE idfactura = (SELECT z.idfactura FROM cargo z WHERE z.facturado = 1 AND z.anulado = 0 AND z.idcontrato = $d->idcontrato ORDER BY z.fechacobro DESC LIMIT 1)
    ORDER BY 1";

    $info->contrato->ultimoscobros = $db->getQuery($query);

    print json_encode($info);
});

$app->post('/lista', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->idempresa)) { $d->idempresa = ''; }
    if(!isset($d->idcliente)) { $d->idcliente = ''; }
    if(!isset($d->idcategoria)) { $d->idcategoria = ''; }
    if(!isset($d->idcliente)) { $d->idcliente = ''; }
    if(!isset($d->idproyecto)) { $d->idproyecto = ''; }

    $query = "SELECT b.nomempresa AS empresa, c.nomproyecto AS proyecto, UnidadesPorContrato(a.id) as unidad, d.nombre AS cliente, a.nocontrato AS NoContrato, 
            DATE_FORMAT(a.fechainactivo, '%d/%m%/%Y') AS fechainactivo, a.catclie
            FROM contrato a
            INNER JOIN empresa b ON a.idempresa = b.id
            INNER JOIN proyecto c ON a.idproyecto = c.id
            INNER JOIN cliente d ON a.idcliente = d.id
            WHERE a.inactivo = 1 AND a.fechainactivo >= '$d->fdelstr' AND a.fechainactivo <= '$d->falstr' ";
    $query.= $d->idcategoria != '' ? "AND a.catclie = $d->idcategoria " : '';
    $query.= $d->idempresa != '' ? "AND a.idempresa = $d->idempresa " : '';
    $query.= $d->idproyecto != '' ? "AND a.idproyecto = $d->idproyecto " : '';
    $query.= $d->idcliente != '' ? "AND a.idcliente = $d->idcliente " : ''; 
    $query.= (int)$d->usufructo === 0 ? "AND a.usufructo IS NULL " : '';
    $query.= "ORDER BY b.ordensumario, c.nomproyecto, d.nombre, a.fechainactivo";
    $reporte = $db->getQuery($query);

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, IFNULL(DATE_FORMAT('$d->fdelstr', '%d/%m/%Y'), '') AS fdel, ";
    $query.= "IFNULL(DATE_FORMAT('$d->falstr', '%d/%m/%Y'), '') AS fal";
    $general = $db->getQuery($query)[0];
    print json_encode(['general' => $general, 'reporte' => $reporte]);
});

$app->run();