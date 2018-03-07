<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/contratotoprint/:idcontrato/:del/:al/:idtiposervicio', function($idcontrato, $del, $al, $idtiposervicio){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idcliente, a.nocontrato, a.fechainicia, a.fechavence, ";
    $query.= "a.idempresa, c.nomempresa AS empresa, a.idproyecto, d.nomproyecto AS proyecto, ";
    $query.= "i.unidades, a.reciboprov, j.descperiodicidad AS periodicidad ";
    $query.= "FROM contrato a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN empresa c ON c.id = a.idempresa LEFT JOIN proyecto d ON d.id = a.idproyecto ";
    $query.= "LEFT JOIN tipocliente f ON f.id = a.idtipocliente LEFT JOIN moneda h ON h.id = a.idmonedadep LEFT JOIN (";
    $query.= "SELECT c.idcontrato, GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS unidades FROM (SELECT b.id AS idcontrato, a.nombre ";
    $query.= "FROM unidad a, contrato b WHERE FIND_IN_SET(a.id, b.idunidad)) c GROUP BY c.idcontrato";
    $query.= ") i ON a.id = i.idcontrato LEFT JOIN periodicidad j ON j.id = a.idperiodicidad ";
    $query.= "WHERE a.id = ".$idcontrato;
    $contrato = $db->getQuery($query)[0];

    $query = "SELECT a.nombre, a.nombrecorto, GROUP_CONCAT(DISTINCT b.facturara ORDER BY b.facturara SEPARATOR ' / ') AS facturara, ";
    $query.= "GROUP_CONCAT(DISTINCT b.nit ORDER BY b.facturara SEPARATOR ' / ') AS nit ";
    $query.= "FROM cliente a LEFT JOIN (SELECT idcliente, facturara, nit FROM detclientefact WHERE idcliente = ".$contrato->idcliente." AND ISNULL(fal) ORDER BY facturara) b ON a.id = b.idcliente ";
    $query.= "WHERE a.id = ".$contrato->idcliente;
    if((int)$idtiposervicio > 0){
        $query = "SELECT a.nombre, a.nombrecorto, FacturarA($contrato->idcliente, $idtiposervicio) AS facturara, NitFacturarA($contrato->idcliente, $idtiposervicio) AS nit FROM cliente a WHERE a.id = $contrato->idcliente";
    }
    $contrato->datacliente = $db->getQuery($query)[0];
    $contrato->servicios = [];

    $query = "SELECT DISTINCT a.idtipoventa, b.desctiposervventa AS tiposervicio ";
    $query.= "FROM detfactcontrato a INNER JOIN tiposervicioventa b ON b.id = a.idtipoventa ";
    $query.= "WHERE a.idcontrato = $idcontrato ".((int)$idtiposervicio > 0 ? "AND b.id = $idtiposervicio " : "");
    $query.= "ORDER BY b.desctiposervventa";
    $servcont = $db->getQuery($query);

    foreach($servcont as $k => $sv){
        //$contrato->servicios[] = ['descripcion' => $sv->tiposervicio];

        $query = "SELECT a.id, a.idcontrato, a.iddetcont, c.id AS idtiposervicio, c.desctiposervventa AS tiposervicio, a.fechacobro, d.simbolo, a.monto, (a.descuento * -1) AS descuento, ";
        $query.= "(a.monto - a.descuento) AS subtotal, ROUND((a.monto - a.descuento) * 0.12, 2) AS iva, (a.monto - a.descuento) + ROUND((a.monto - a.descuento) * 0.12, 2) AS grantotal ";
        $query.= "FROM cargo a INNER JOIN detfactcontrato b ON b.id = a.iddetcont INNER JOIN tiposervicioventa c ON c.id = b.idtipoventa INNER JOIN moneda d ON d.id = b.idmoneda ";
        $query.= "WHERE a.idcontrato = $idcontrato AND c.id = $sv->idtipoventa ";
        $query.= $del != "0" ? "AND a.fechacobro >= '$del' " : "" ;
        $query.= $al != "0" ? "AND a.fechacobro <= '$al' " : "" ;
        $query.= "ORDER BY a.fechacobro";
        $detsc = $db->getQuery($query);
        $contrato->servicios[] = ['descripcion' => $sv->tiposervicio, 'detalle' => $detsc];

    }

    print json_encode($contrato);
});


$app->run();