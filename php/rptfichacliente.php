<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->get('/clientetoprint/:idcliente', function($idcliente){
    $db = new dbcpm();
    $query = "SELECT LPAD(a.id, 10, '0') AS id, a.nombre, a.nombrecorto, a.direntrega, a.dirplanta, a.telpbx, a.teldirecto, a.telfax, a.telcel, a.correo, a.idordencedula, b.noorden, a.regcedula, ";
    $query.= "a.dpi, a.cargolegal, a.nomlegal, a.apellidolegal, a.nomadmon, a.mailadmon, a.nompago, a.mailcont, a.idcuentac, ";
    $query.= "a.creadopor, a.fhcreacion, a.actualizadopor, a.fhactualizacion, c.contratos, DATE_FORMAT(NOW(), '%d/%m/%Y') as hoy ";
    $query.= "FROM cliente a LEFT JOIN ordencedula b ON b.id = a.idordencedula ";
    $query.= "LEFT JOIN (SELECT idcliente, GROUP_CONCAT(CONCAT(id, '-', nocontrato) SEPARATOR ',') AS contratos FROM contrato GROUP BY idcliente) c ON a.id = c.idcliente ";
    $query.= "WHERE a.id = ".$idcliente;
    $cliente = $db->getQuery($query)[0];

    $query = "SELECT a.id, a.idcliente, a.nocontrato, a.abogado, a.inactivo, a.fechainicia, a.fechavence, a.nuevarenta, a.nuevomantenimiento, a.idmoneda, b.simbolo AS moneda, ";
    $query.= "a.idempresa, c.nomempresa AS empresa, a.deposito, a.idproyecto, d.nomproyecto AS proyecto, a.idunidad, a.retiva, a.prorrogable, a.retisr, ";
    $query.= "a.documento, a.adelantado, a.subarrendado, a.idtipocliente, f.desctipocliente AS tipocliente, a.idcuentac, a.observaciones, a.idmonedadep, ";
    $query.= "h.simbolo AS monedadep, i.unidades, j.mcrentados, k.descripcion AS incrementos ";
    $query.= "FROM contrato a LEFT JOIN moneda b ON b.id = a.idmoneda LEFT JOIN empresa c ON c.id = a.idempresa LEFT JOIN proyecto d ON d.id = a.idproyecto ";
    $query.= "LEFT JOIN tipocliente f ON f.id = a.idtipocliente LEFT JOIN moneda h ON h.id = a.idmonedadep LEFT JOIN (";
    $query.= "SELECT c.idcontrato, GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS unidades FROM (SELECT b.id AS idcontrato, a.nombre ";
    $query.= "FROM unidad a, contrato b WHERE FIND_IN_SET(a.id, b.idunidad)) c GROUP BY c.idcontrato";
    $query.= ") i ON a.id = i.idcontrato ";
    $query.= "LEFT JOIN (SELECT b.id AS idcontrato, SUM(a.mcuad) AS mcrentados FROM unidad a, contrato b WHERE FIND_IN_SET(a.id, b.idunidad) GROUP BY b.idcliente, b.id) j ON a.id = j.idcontrato ";
    $query.= "LEFT JOIN tipoipc k ON k.id = a.idtipoipc ";
    $query.= "WHERE a.idcliente = ".$idcliente;
    $cliente->contratos = $db->getQuery($query);

    foreach($cliente->contratos as $cont){
        $cont->detalle = [];
        $query = "SELECT a.noperiodo, a.fdel, a.fal, b.desctiposervventa, c.simbolo, a.monto ";
        $query.= "FROM detfactcontrato a INNER JOIN tiposervicioventa b ON b.id = a.idtipoventa INNER JOIN moneda c ON c.id = a.idmoneda ";
        $query.= "WHERE a.idcontrato = $cont->id ";
        $query.= "ORDER BY b.desctiposervventa, a.noperiodo";
        $cont->detalle = $db->getQuery($query);

        $cont->fiadores = [];
        $query = "SELECT nombre, empresa, direccion, telefono, identificacion FROM detclientefiadores WHERE idcontrato = $cont->id ORDER BY nombre";
        $fia = $db->getQuery($query);
        $cont->fiadores = count($fia) > 0 ? $fia : [];
    }

    $query = "SELECT facturara, emailfactura, direccion, nit FROM detclientefact WHERE idcliente = ".$idcliente." AND ISNULL(fal) ORDER BY fdel DESC LIMIT 1";
    $df = $db->getQuery($query);
    $cliente->datafact = count($df) > 0 ? $df[0] : [];
    print json_encode($cliente);
});
$app->post('/lista', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT d.idempresa, c.nomempresa AS nombreempresa, c.abreviatura, SUBSTRING(a.nombre, 1, 30) AS cliente, a.nombrecorto, SUBSTRING(b.facturara, 1, 45) AS facturara,
            SUBSTRING(b.direccion, 1, 50) AS direccion, b.nit, 
            IF(b.retisr = 1, 'Sí', '') AS retieneisr, IF(b.retiva = 1, 'Sí', '') AS retieneiva, IF(b.porretiva = 0, '', b.porretiva) AS porretiva, IF(b.exentoiva = 0, '', 'Sí') AS exentoiva
            FROM contrato d
            INNER JOIN cliente a ON a.id = d.idcliente
            INNER JOIN empresa c ON c.id = d.idempresa
            INNER JOIN detclientefact b ON b.idcliente = a.id
            WHERE b.fal IS NULL ";
    $query.= $d->idempresa !== '' ? "AND d.idempresa IN($d->idempresa) " : '';
    $query.= "ORDER BY c.ordensumario, a.nombre, b.facturara";
    $clientes = $db->getQuery($query);

    $query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy";
    $general = $db->getQuery($query)[0];

    print json_encode(['general' => $general, 'clientes' => $clientes]);
});

$app->run();