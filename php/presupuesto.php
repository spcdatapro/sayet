<?php
set_time_limit(0);
ini_set('memory_limit', '1536M');

require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function proyectosPorUsuario($idusuario = 0){
    $db = new dbcpm();
    $proyectos = '';
    if(!in_array((int)$idusuario, [0, 1])){
        $query = "SELECT IFNULL(GROUP_CONCAT(idproyecto SEPARATOR ','), '') FROM usuarioproyecto WHERE idusuario = $idusuario";
        $proyectos = $db->getOneField($query);
    }
    return $proyectos;
}

//API presupuestos
$app->post('/lstpresupuestos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    if(!isset($d->idusuario)){ $d->idusuario = 0; }
    $proyectos = proyectosPorUsuario((int)$d->idusuario);
    $query = "SELECT a.id, a.fechasolicitud, a.idproyecto, b.nomproyecto AS proyecto, a.idempresa, c.nomempresa AS empresa, a.idtipogasto, d.desctipogast AS tipogasto, a.idmoneda, e.simbolo, ";
    $query.= "a.total, a.notas, a.idusuario, f.nombre AS usuario, a.idestatuspresupuesto, g.descestatuspresup AS estatus, a.fechacreacion, a.fhenvioaprobacion, a.fhaprobacion, ";
    $query.= "a.idusuarioaprueba, h.nombre AS aprobadopor, a.tipo, a.idproveedor, a.idsubtipogasto, a.coniva, a.monto, a.tipocambio, a.excedente, TRIM(c.abreviatura) AS abreviaempre, a.origenprov, i.proveedor, ";
    $query.= "a.gastado AS gastado, IF(a.tipo = 1, 'OTS', 'OTM') AS tipostr, ";
    $query.= "IF(a.tipo = 1, (SELECT id FROM detpresupuesto WHERE idpresupuesto = a.id LIMIT 1), 0) AS idot ";
    $query.= "FROM presupuesto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "INNER JOIN tipogasto d ON d.id = a.idtipogasto INNER JOIN moneda e ON e.id = a.idmoneda INNER JOIN usuario f ON f.id = a.idusuario ";
    $query.= "INNER JOIN estatuspresupuesto g ON g.id = a.idestatuspresupuesto LEFT JOIN usuario h ON h.id = a.idusuarioaprueba ";
    $query.= "LEFT JOIN (SELECT x.idpresupuesto, GROUP_CONCAT(DISTINCT x.proveedor ORDER BY x.proveedor SEPARATOR ', ') AS proveedor FROM (SELECT z.idpresupuesto, y.nombre AS proveedor FROM detpresupuesto z INNER JOIN proveedor y ON y.id = z.idproveedor ";
    $query.= "WHERE z.origenprov = 1 UNION SELECT z.idpresupuesto, y.nombre AS proveedor FROM detpresupuesto z INNER JOIN beneficiario y ON y.id = z.idproveedor WHERE z.origenprov = 2) x GROUP BY x.idpresupuesto) i ON a.id = i.idpresupuesto ";
    $query.= "WHERE a.fechasolicitud >= '$d->fdelstr' AND a.fechasolicitud <= '$d->falstr' ";
    $query.= trim($proyectos) != '' ? "AND a.idproyecto IN ($proyectos) " : '';
    //$query.= $d->idestatuspresup != '' ? "AND (a.idestatuspresupuesto IN($d->idestatuspresup) OR (SELECT COUNT(idestatuspresupuesto) FROM detpresupuesto WHERE idpresupuesto = a.id AND idestatuspresupuesto IN($d->idestatuspresup)) > 0) " : '';
    $query.= $d->idestatuspresup != '' ? "AND (IF(a.tipo = 1, a.idestatuspresupuesto IN($d->idestatuspresup), (SELECT COUNT(idestatuspresupuesto) FROM detpresupuesto WHERE idpresupuesto = a.id AND idestatuspresupuesto IN($d->idestatuspresup)) > 0)) " : '';
    $query.= "ORDER BY a.id DESC";
    //print $query;
    print $db->doSelectASJson($query);
});

$app->get('/lstpresupuestospend(/:idusr)', function($idusr = 0){
    $db = new dbcpm();
    $limiteot = 0.00;

    if((int)$idusr > 0){
        $limiteot = (float)$db->getOneField("SELECT limiteot FROM usuario WHERE id = $idusr");
    }

    $query = "
        SELECT b.fechasolicitud AS fechaOrd, a.idpresupuesto AS id, a.correlativo, a.id AS idot, CONCAT(a.idpresupuesto, '-', a.correlativo) AS numero, DATE_FORMAT(b.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, 
        c.abreviatura AS empresa, d.referencia AS proyecto, e.nombre AS proveedor, f.simbolo AS moneda, a.monto, a.tipocambio, CONCAT(g.descripcion, ' - ', h.desctipogast) AS gasto, a.notas, 0 AS aprobada, 0 AS denegada, i.iniciales AS usuario,
        b.tipo
        FROM detpresupuesto a
        INNER JOIN presupuesto b ON b.id = a.idpresupuesto
        INNER JOIN empresa c ON c.id = b.idempresa
        INNER JOIN proyecto d ON d.id = b.idproyecto
        INNER JOIN proveedor e ON e.id = a.idproveedor
        INNER JOIN moneda f ON f.id = a.idmoneda
        INNER JOIN subtipogasto g ON g.id = a.idsubtipogasto
        INNER JOIN tipogasto h ON h.id = g.idtipogasto
        INNER JOIN usuario i ON i.id = b.idusuario
        WHERE a.idestatuspresupuesto = 2 AND a.origenprov = 1 ";
    $query.= $limiteot > 0 ? "AND (a.monto * a.tipocambio) <= $limiteot " : '';
    $query.= "
        UNION
        SELECT b.fechasolicitud AS fechaOrd, a.idpresupuesto AS id, a.correlativo, a.id AS idot, CONCAT(a.idpresupuesto, '-', a.correlativo) AS numero, DATE_FORMAT(b.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, 
        c.abreviatura AS empresa, d.referencia AS proyecto, e.nombre AS proveedor, f.simbolo AS moneda, a.monto, a.tipocambio, CONCAT(g.descripcion, ' - ', h.desctipogast) AS gasto, a.notas, 0 AS aprobada, 0 AS denegada, i.iniciales AS usuario,
        b.tipo
        FROM detpresupuesto a
        INNER JOIN presupuesto b ON b.id = a.idpresupuesto
        INNER JOIN empresa c ON c.id = b.idempresa
        INNER JOIN proyecto d ON d.id = b.idproyecto
        INNER JOIN beneficiario e ON e.id = a.idproveedor
        INNER JOIN moneda f ON f.id = a.idmoneda
        INNER JOIN subtipogasto g ON g.id = a.idsubtipogasto
        INNER JOIN tipogasto h ON h.id = g.idtipogasto
        INNER JOIN usuario i ON i.id = b.idusuario
        WHERE a.idestatuspresupuesto = 2 AND a.origenprov = 2 ";
    $query.= $limiteot > 0 ? "AND (a.monto * a.tipocambio) <= $limiteot " : '';
    $query.= "ORDER BY 1, 2, 3";
    print $db->doSelectASJson(trim($query));
});

//Configuración de montos de OTs que los usuarios pueden autorizar
$app->get('/usraprob(/:id)', function($id = 0){
    $db = new dbcpm();

    $query = "SELECT id, nombre, limiteot FROM usuario WHERE id IN(SELECT idusuario FROM permiso WHERE accesar = 1 ".((int)$id == 0 ? '' : "AND idusuario = $id")." AND iditemmenu = (SELECT id FROM itemmenu WHERE TRIM(url) = 'tranaprobpresup')) ORDER BY nombre";
    print $db->doSelectASJson($query);
});

$app->post('/usrmonto', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE usuario SET limiteot = $d->limiteot WHERE id = $d->id";
    $db->doQuery($query);
});
//Fin de Configuración de montos de OTs que los usuarios pueden autorizar

$app->post('/lstpresaprob', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.id, a.idestatuspresupuesto, a.fechasolicitud, a.idproyecto, b.nomproyecto AS proyecto, a.idempresa, TRIM(c.abreviatura) AS empresa, a.idtipogasto, d.desctipogast AS tipogasto, ";
    $query.= "a.idmoneda, e.simbolo AS moneda, a.total, a.notas AS descripcion, a.tipo, a.idproveedor, a.idsubtipogasto, a.coniva, a.monto, a.tipocambio, a.excedente ";
    $query.= "FROM presupuesto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = a.idempresa INNER JOIN tipogasto d ON d.id = a.idtipogasto INNER JOIN moneda e ON e.id = a.idmoneda ";
    $query.= "WHERE (a.idestatuspresupuesto = 3 OR (SELECT COUNT(idestatuspresupuesto) FROM detpresupuesto WHERE idpresupuesto = a.id AND idestatuspresupuesto = 3) > 0) ";
    $query.= "AND a.fechasolicitud >= '$d->fdelstr' AND a.fechasolicitud <= '$d->falstr' ";
    $query.= "ORDER BY a.id DESC, b.nomproyecto";
    $presupuestos = $db->getQuery($query);
    $cntPresup = count($presupuestos);
    if($cntPresup > 0){
        for($i = 0; $i < $cntPresup; $i++){
            $presupuesto = $presupuestos[$i];
            $query = "SELECT a.id AS idot, a.idpresupuesto, a.correlativo AS id, a.idproveedor, b.nombre AS proyecto, a.idsubtipogasto, c.descripcion AS tipogasto, a.coniva, a.monto AS total, ";
            $query.= "a.tipocambio, a.excedente, a.origenprov ";
            $query.= "FROM detpresupuesto a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto ";
            $query.= "WHERE a.origenprov = 1 AND a.idestatuspresupuesto = 3 AND a.idpresupuesto = $presupuesto->id ";
            $query.= "UNION ";
            $query.= "SELECT a.id AS idot, a.idpresupuesto, a.correlativo AS id, a.idproveedor, b.nombre AS proyecto, a.idsubtipogasto, c.descripcion AS tipogasto, a.coniva, a.monto AS total, ";
            $query.= "a.tipocambio, a.excedente, a.origenprov ";
            $query.= "FROM detpresupuesto a INNER JOIN beneficiario b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto ";
            $query.= "WHERE a.origenprov = 2 AND a.idestatuspresupuesto = 3 AND a.idpresupuesto = $presupuesto->id ";
            $query.= "ORDER BY 3";
            $presupuesto->children = $db->getQuery($query);
        }
    }else{ $presupuestos = []; }

    print json_encode($presupuestos);
});

$app->get('/getpresupuesto/:idpresupuesto', function($idpresupuesto){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fechasolicitud, a.idproyecto, b.nomproyecto AS proyecto, a.idempresa, c.nomempresa AS empresa, a.idtipogasto, d.desctipogast AS tipogasto, a.idmoneda, e.simbolo, ";
    $query.= "a.total, a.notas, a.idusuario, f.nombre AS usuario, a.idestatuspresupuesto, g.descestatuspresup AS estatus, a.fechacreacion, a.fhenvioaprobacion, a.fhaprobacion, ";
    $query.= "a.idusuarioaprueba, h.nombre AS aprobadopor, a.tipo, a.idproveedor, a.idsubtipogasto, a.coniva, a.monto, a.tipocambio, a.excedente, TRIM(c.abreviatura) AS abreviaempre, a.origenprov ";
    $query.= "FROM presupuesto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "INNER JOIN tipogasto d ON d.id = a.idtipogasto INNER JOIN moneda e ON e.id = a.idmoneda INNER JOIN usuario f ON f.id = a.idusuario ";
    $query.= "INNER JOIN estatuspresupuesto g ON g.id = a.idestatuspresupuesto LEFT JOIN usuario h ON h.id = a.idusuarioaprueba ";
    $query.= "WHERE a.id = $idpresupuesto";
    print $db->doSelectASJson($query);
});

function updTotPresupuesto($idpresupuesto){
    $db = new dbcpm();
    $query = "UPDATE presupuesto SET total = (SELECT IF(ISNULL(SUM(monto * tipocambio)), 0.00, ROUND(SUM(monto * tipocambio), 2)) FROM detpresupuesto WHERE idpresupuesto = $idpresupuesto) WHERE id = $idpresupuesto";
    $db->doQuery($query);
}

function creaDetallePresupuesto($d){
    $db = new dbcpm();
    $correlativo = (int)$db->getOneField("SELECT IF(ISNULL(MAX(correlativo)), 1, MAX(correlativo) + 1) AS correlativo FROM detpresupuesto WHERE idpresupuesto = $d->idpresupuesto");
    $excedente = round((float)$db->getOneField("SELECT excedente FROM confpresupuestos WHERE id = 1"), 2);
    $query = "INSERT INTO detpresupuesto(";
    $query.= "idpresupuesto, correlativo, idproveedor, idsubtipogasto, coniva, monto, tipocambio, excedente, notas, origenprov, idmoneda";
    $query.= ") VALUES(";
    $query.= "$d->idpresupuesto, $correlativo, $d->idproveedor, $d->idsubtipogasto, $d->coniva, $d->monto, $d->tipocambio, $excedente, '$d->notas', $d->origenprov, $d->idmoneda";
    $query.= ")";
    $db->doQuery($query);
    updTotPresupuesto($d->idpresupuesto);
}

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $excedente = round((float)$db->getOneField("SELECT excedente FROM confpresupuestos WHERE id = 1"), 2);
    $query = "INSERT INTO presupuesto(";
    $query.= "fechasolicitud, idproyecto, idempresa, idtipogasto, idmoneda, total, notas, fechacreacion, idusuario, idestatuspresupuesto, ";
    $query.= "tipo, idproveedor, idsubtipogasto, coniva, monto, tipocambio, excedente, origenprov";
    $query.= ") VALUES(";
    $query.= "'$d->fechasolicitudstr', $d->idproyecto, $d->idempresa, $d->idtipogasto, $d->idmoneda, 0.00, '$d->notas', NOW(), $d->idusuario, 1, ";
    $query.= "$d->tipo, $d->idproveedor, $d->idsubtipogasto, $d->coniva, $d->monto, $d->tipocambio, $excedente, $d->origenprov";
    $query.= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();

    if((int)$d->tipo == 1){
        $d->idpresupuesto = $lastid;
        creaDetallePresupuesto($d);
    }

    print json_encode(['lastid' => $lastid]);
});

function actualizaDetallePresupuesto($d){
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET ";
    $query.= "idproveedor = $d->idproveedor, idsubtipogasto = $d->idsubtipogasto, coniva = $d->coniva, monto = $d->monto, tipocambio = $d->tipocambio, notas = '$d->notas', origenprov = $d->origenprov, idmoneda = $d->idmoneda ";
    $query.= "WHERE idpresupuesto = ".$d->id;
    $db->doQuery($query);
    updTotPresupuesto($d->idpresupuesto);
}

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE presupuesto SET ";
    $query.= "fechasolicitud = '$d->fechasolicitudstr', idproyecto = $d->idproyecto, idempresa = $d->idempresa, idtipogasto = $d->idtipogasto, ";
    $query.= "idmoneda = $d->idmoneda, notas = '$d->notas', fechamodificacion = NOW(), lastuser = $d->idusuario, ";
    $query.= "idproveedor = $d->idproveedor, idsubtipogasto = $d->idsubtipogasto, coniva = $d->coniva, monto = $d->monto, tipocambio = $d->tipocambio, origenprov = $d->origenprov ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);

    if((int)$d->tipo == 1){
        $d->idpresupuesto = $d->id;
        actualizaDetallePresupuesto($d);
    }
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detpresupuesto WHERE idpresupuesto = $d->id");
    $db->doQuery("DELETE FROM presupuesto WHERE id = $d->id");
});

$app->post('/ep', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->esot)){ $d->esot = 0; }
    $db = new dbcpm();
    $idot = $d->id;
    if($d->esot == 0){
        $query = "UPDATE presupuesto SET fhenvioaprobacion = NOW(), idestatuspresupuesto = 2, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->doQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
    }
    $query = "UPDATE detpresupuesto SET fhenvioaprobacion = NOW(), idestatuspresupuesto = 2, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $idot";
    $db->doQuery($query);
});

$app->post('/ap', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 3, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->idot";
    $db->getQuery($query);
    if((int)$d->tipo == 1){
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 3, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
    }
});

$app->post('/np', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 4, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->idot";
    $db->getQuery($query);
    if((int)$d->tipo == 1){
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 4, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
    }
});

$app->post('/tp', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->esot)){ $d->esot = 0; }
    $db = new dbcpm();
    $idot = $d->id;
    if($d->esot == 0){
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 5, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
    }
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 5, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $idot";
    $db->getQuery($query);
});

$app->post('/rp', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->esot)){ $d->esot = 0; }
    $db = new dbcpm();
    $idot = $d->id;
    if($d->esot == 0){
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 3, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
    }
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 3, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $idot";
    $db->getQuery($query);
});

$app->post('/anulapres', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->esot)){ $d->esot = 0; }
    $db = new dbcpm();
    $idot = $d->id;
    if($d->esot == 0){
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 6, fhanulacion = NOW(), idusuarioanula = $d->idusuarioanula, idrazonanula = $d->idrazonanula WHERE id = $d->id";
        $db->getQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
    }
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 6, fhanulacion = NOW(), idusuarioanula = $d->idusuarioanula, idrazonanula = $d->idrazonanula WHERE id = $idot";
    $db->getQuery($query);
});

//API detalle de presupuestos (OTs)
$app->get('/lstot/:idpresupuesto', function($idpresupuesto){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.monto, f.simbolo AS moneda, d.total, a.tipocambio, a.excedente, a.notas, a.origenprov, ";
    $query.= "a.idmoneda, a.idestatuspresupuesto ";
    $query.= "FROM detpresupuesto a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query.= "INNER JOIN moneda e ON e.id = d.idmoneda LEFT JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "WHERE a.origenprov = 1 AND a.idpresupuesto = $idpresupuesto ";
    $query.= "UNION ";
    $query.= "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.monto, f.simbolo AS moneda, d.total, a.tipocambio, a.excedente, a.notas, a.origenprov, ";
    $query.= "a.idmoneda, a.idestatuspresupuesto ";
    $query.= "FROM detpresupuesto a INNER JOIN beneficiario b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query.= "INNER JOIN moneda e ON e.id = d.idmoneda LEFT JOIN moneda f ON f.id = a.idmoneda ";
    $query.= "WHERE a.origenprov = 2 AND a.idpresupuesto = $idpresupuesto ";
    $query.= "ORDER BY 3";
    print $db->doSelectASJson($query);
});

$app->get('/getot/:idot', function($idot){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.monto, i.simbolo AS moneda, d.total, a.tipocambio, a.excedente, ";
    $query.= "f.nomproyecto AS proyecto, g.desctipogast AS tipogasto, d.fechasolicitud, h.abreviatura AS empresa, a.notas, a.origenprov, a.idmoneda, a.idestatuspresupuesto ";
    $query.= "FROM detpresupuesto a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query.= "INNER JOIN moneda e ON e.id = d.idmoneda INNER JOIN proyecto f ON f.id = d.idproyecto INNER JOIN tipogasto g ON g.id = d.idtipogasto INNER JOIN empresa h ON h.id = d.idempresa ";
    $query.= "LEFT JOIN moneda i ON i.id = a.idmoneda ";
    $query.= "WHERE a.origenprov = 1 AND a.id = $idot ";
    $query.= "UNION ";
    $query.= "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.monto, i.simbolo AS moneda, d.total, a.tipocambio, a.excedente, ";
    $query.= "f.nomproyecto AS proyecto, g.desctipogast AS tipogasto, d.fechasolicitud, h.abreviatura AS empresa, a.notas, a.origenprov, a.idmoneda, a.idestatuspresupuesto ";
    $query.= "FROM detpresupuesto a INNER JOIN beneficiario b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query.= "INNER JOIN moneda e ON e.id = d.idmoneda INNER JOIN proyecto f ON f.id = d.idproyecto INNER JOIN tipogasto g ON g.id = d.idtipogasto INNER JOIN empresa h ON h.id = d.idempresa ";
    $query.= "LEFT JOIN moneda i ON i.id = a.idmoneda ";
    $query.= "WHERE a.origenprov = 2 AND a.id = $idot";
    print $db->doSelectASJson($query);
});

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $correlativo = (int)$db->getOneField("SELECT IF(ISNULL(MAX(correlativo)), 1, MAX(correlativo) + 1) AS correlativo FROM detpresupuesto WHERE idpresupuesto = $d->idpresupuesto");
    $excedente = round((float)$db->getOneField("SELECT excedente FROM confpresupuestos WHERE id = 1"), 2);
    $query = "INSERT INTO detpresupuesto(";
    $query.= "idpresupuesto, correlativo, idproveedor, idsubtipogasto, coniva, monto, tipocambio, excedente, notas, origenprov, idmoneda";
    $query.= ") VALUES(";
    $query.= "$d->idpresupuesto, $correlativo, $d->idproveedor, $d->idsubtipogasto, $d->coniva, $d->monto, $d->tipocambio, $excedente, '$d->notas', $d->origenprov, $d->idmoneda";
    $query.= ")";
    $db->doQuery($query);
    updTotPresupuesto($d->idpresupuesto);
    print json_encode(['lastid' => $db->getLastId()]);
});


$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET ";
    $query.= "idproveedor = $d->idproveedor, idsubtipogasto = $d->idsubtipogasto, coniva = $d->coniva, monto = $d->monto, tipocambio = $d->tipocambio, notas = '$d->notas', origenprov = $d->origenprov, ";
    $query.= "idmoneda = $d->idmoneda ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
    updTotPresupuesto($d->idpresupuesto);
});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $idpresupuesto = (int)$db->getOneField("SELECT idpresupuesto FROM detpresupuesto WHERE id = $d->id");
    $db->doQuery("DELETE FROM detpresupuesto WHERE id = $d->id");
    updTotPresupuesto($idpresupuesto);
});

$app->get('/avanceot/:idot', function($idot){
    $db = new dbcpm();
    $query = "SELECT 1 AS origen, a.id, a.fecha, b.siglas AS banco, a.tipotrans, a.numero, c.simbolo AS moneda, a.monto, a.concepto, a.tipocambio, getIsrTranBan(a.id) AS isr, ";
    $query.= "(SELECT GROUP_CONCAT(CONCAT(serie, '-', documento) SEPARATOR ', ') FROM doctotranban WHERE idtranban = a.id GROUP BY idtranban) AS factura ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
    $query.= "WHERE a.anulado = 0 AND a.iddetpresup = $idot ";
    $query.= "UNION ALL ";
    $query.= "SELECT 2 AS origen, a.id, a.fechafactura AS fecha, '' AS banco, '' AS tipotrans, CONCAT(a.serie, '-',a.documento) AS numero, b.simbolo AS moneda, a.totfact AS monto, a.conceptomayor AS concepto, a.tipocambio, a.isr, NULL AS factura ";
    $query.= "FROM compra a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query.= "WHERE a.ordentrabajo = $idot ";
    $query.= "ORDER BY 3 DESC, 4, 5, 6";
    print $db->doSelectASJson($query);
});

$app->post('/masexcede', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if((int)$d->esot === 0){
        $oldExcedente = $db->getOneField("SELECT excedente FROM presupuesto WHERE id = $d->id");
        $query = "UPDATE presupuesto SET excedente = $d->monto WHERE id = $d->id";
        $db->doQuery($query);
        $query = "UPDATE detpresupuesto SET excedente = $d->monto WHERE idpresupuesto = $d->id";
        $db->doQuery($query);
        $cambio = "Cambio de porcentaje de excedente a presupuesto No. $d->id. De $oldExcedente a $d->monto.";
        $tabla = 'presupuesto';
    } else{
        $oldData = $db->getQuery("SELECT idpresupuesto, correlativo, excedente FROM detpresupuesto WHERE id = $d->id")[0];
        $query = "UPDATE detpresupuesto SET excedente = $d->monto WHERE id = $d->id";
        $db->doQuery($query);
        $cambio = "Cambio de porcentaje de excedente a presupuesto No. $oldData->idpresupuesto-$oldData->correlativo. De $oldData->excedente a $d->monto.";
        $tabla = 'detpresupuesto';
    }

    $query = "INSERT INTO auditoria(idusuario, tabla, cambio, fecha, tipo) VALUES($d->idusuarioaumentaexcedente, '$tabla', '$cambio', NOW(), 'U')";
    $db->doQuery($query);
});

//API notas de OT
$app->get('/lstnotas/:iddetpresup', function($iddetpresup){
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresupuesto, a.fechahora, a.nota, a.usuario, b.nombre, a.fhcreacion ";
    $query.= "FROM notapresupuesto a INNER JOIN usuario b ON b.id = a.usuario ";
    $query.= "WHERE a.iddetpresupuesto = $iddetpresup ";
    $query.= "ORDER BY a.fechahora DESC, b.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getnota/:idnota', function($idnota){
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresupuesto, a.fechahora, a.nota, a.usuario, b.nombre, a.fhcreacion ";
    $query.= "FROM notapresupuesto a INNER JOIN usuario b ON b.id = a.usuario ";
    $query.= "WHERE a.id = $idnota";
    print $db->doSelectASJson($query);
});

$app->post('/cnp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO notapresupuesto(";
    $query.= "iddetpresupuesto, fechahora, nota, usuario, fhcreacion";
    $query.= ") VALUES(";
    $query.= "$d->iddetpresupuesto, NOW(), '$d->nota', $d->idusuario, NOW()";
    $query.= ")";
    $db->doQuery($query);
});


$app->post('/unp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE notapresupuesto SET ";
    $query.= "fechahora = NOW(), nota = '$d->nota', usuario = $d->idusuario ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/dnp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM notapresupuesto WHERE id = $d->id");
});

//API detalle de pago de OT
$app->get('/lstdetpago/:iddetpresup', function($iddetpresup){
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresup, a.nopago, a.porcentaje, a.monto, a.notas, a.pagado, a.origen, a.idorigen, a.isr, a.quitarisr FROM detpagopresup a WHERE a.iddetpresup = $iddetpresup ORDER BY a.nopago";
    print $db->doSelectASJson($query);
});

$app->get('/getdetpago/:iddetpago', function($iddetpago){
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresup, a.nopago, a.porcentaje, a.monto, a.notas, a.pagado, a.origen, a.idorigen, a.isr, a.quitarisr FROM detpagopresup a WHERE a.id = $iddetpago";
    print $db->doSelectASJson($query);
});

$app->get('/lstpagos/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.idpresupuesto, a.id, b.idproyecto, c.nomproyecto AS proyecto, b.fhaprobacion, a.idproveedor, e.nombre AS proveedor, b.idmoneda, d.simbolo AS moneda, a.monto, ";
    $query.= "b.fechasolicitud, f.nomempresa AS empresa, g.desctipogast AS tipogasto, h.descripcion AS subtipogasto, IF(a.coniva = 1, 'I.V.A. incluido', 'I.V.A. NO incluido') AS coniva, a.correlativo, ";
    $query.= "CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, b.idempresa, i.nopago, i.porcentaje, i.monto AS valor, i.notas, i.id AS iddetpagopresup, i.pagado, ";
    $query.= "IF(i.pagado = 0, NULL, 'Pagado') AS estatuspagado, b.total, a.tipocambio, a.origenprov, i.isr, i.quitarisr ";
    $query.= "FROM detpresupuesto a INNER JOIN presupuesto b ON b.id = a.idpresupuesto INNER JOIN proyecto c ON c.id = b.idproyecto INNER JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "INNER JOIN proveedor e ON e.id = a.idproveedor INNER JOIN empresa f ON f.id = b.idempresa INNER JOIN tipogasto g ON g.id = b.idtipogasto ";
    $query.= "INNER JOIN subtipogasto h ON h.id = a.idsubtipogasto LEFT JOIN detpagopresup i ON a.id = i.iddetpresup ";
    $query.= "WHERE a.origenprov = 1 AND a.idestatuspresupuesto IN(3, 5) ";
    $query.= (int)$idempresa > 0 ? "AND f.id = $idempresa " : "";
    $query.= "UNION ";
    $query.= "SELECT a.idpresupuesto, a.id, b.idproyecto, c.nomproyecto AS proyecto, b.fhaprobacion, a.idproveedor, e.nombre AS proveedor, b.idmoneda, d.simbolo AS moneda, a.monto, ";
    $query.= "b.fechasolicitud, f.nomempresa AS empresa, g.desctipogast AS tipogasto, h.descripcion AS subtipogasto, IF(a.coniva = 1, 'I.V.A. incluido', 'I.V.A. NO incluido') AS coniva, a.correlativo, ";
    $query.= "CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, b.idempresa, i.nopago, i.porcentaje, i.monto AS valor, i.notas, i.id AS iddetpagopresup, i.pagado, ";
    $query.= "IF(i.pagado = 0, NULL, 'Pagado') AS estatuspagado, b.total, a.tipocambio, a.origenprov, i.isr, i.quitarisr ";
    $query.= "FROM detpresupuesto a INNER JOIN presupuesto b ON b.id = a.idpresupuesto INNER JOIN proyecto c ON c.id = b.idproyecto INNER JOIN moneda d ON d.id = b.idmoneda ";
    $query.= "INNER JOIN beneficiario e ON e.id = a.idproveedor INNER JOIN empresa f ON f.id = b.idempresa INNER JOIN tipogasto g ON g.id = b.idtipogasto ";
    $query.= "INNER JOIN subtipogasto h ON h.id = a.idsubtipogasto LEFT JOIN detpagopresup i ON a.id = i.iddetpresup ";
    $query.= "WHERE a.origenprov = 2 AND a.idestatuspresupuesto IN(3, 5) ";
    $query.= (int)$idempresa > 0 ? "AND f.id = $idempresa " : "";
    $query.= "ORDER BY 1, 2, 5, 19";
    print $db->doSelectASJson($query);
});

$app->get('/pagospend', function(){
    $db = new dbcpm();

    $query = "SELECT f.ordensumario, a.idpresupuesto, a.id, b.idproyecto, c.nomproyecto AS proyecto, b.fhaprobacion, a.idproveedor, e.nombre AS proveedor, b.idmoneda, d.simbolo AS moneda, a.monto, ";
    $query.= "b.fechasolicitud, f.nomempresa AS empresa, g.desctipogast AS tipogasto, h.descripcion AS subtipogasto, IF(a.coniva = 1, 'I.V.A. incluido', 'I.V.A. NO incluido') AS coniva, a.correlativo, ";
    $query.= "CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, b.idempresa, i.nopago, i.porcentaje, i.monto AS valor, i.notas, i.id AS iddetpagopresup, i.pagado, ";
    $query.= "IF(i.pagado = 0, NULL, 'Pagado') AS estatuspagado, b.total, a.tipocambio, a.origenprov, i.isr, i.quitarisr, 1 AS generar, NULL AS idbanco, f.abreviatura AS abreviaempresa ";
    $query.= "FROM detpresupuesto a INNER JOIN presupuesto b ON b.id = a.idpresupuesto INNER JOIN proyecto c ON c.id = b.idproyecto INNER JOIN moneda d ON d.id = a.idmoneda ";
    $query.= "INNER JOIN proveedor e ON e.id = a.idproveedor INNER JOIN empresa f ON f.id = b.idempresa INNER JOIN tipogasto g ON g.id = b.idtipogasto ";
    $query.= "INNER JOIN subtipogasto h ON h.id = a.idsubtipogasto LEFT JOIN detpagopresup i ON a.id = i.iddetpresup ";
    $query.= "WHERE a.origenprov = 1 AND a.idestatuspresupuesto IN(3) AND i.pagado = 0 ";
    $query.= "UNION ";
    $query.= "SELECT f.ordensumario, a.idpresupuesto, a.id, b.idproyecto, c.nomproyecto AS proyecto, b.fhaprobacion, a.idproveedor, e.nombre AS proveedor, b.idmoneda, d.simbolo AS moneda, a.monto, ";
    $query.= "b.fechasolicitud, f.nomempresa AS empresa, g.desctipogast AS tipogasto, h.descripcion AS subtipogasto, IF(a.coniva = 1, 'I.V.A. incluido', 'I.V.A. NO incluido') AS coniva, a.correlativo, ";
    $query.= "CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, b.idempresa, i.nopago, i.porcentaje, i.monto AS valor, i.notas, i.id AS iddetpagopresup, i.pagado, ";
    $query.= "IF(i.pagado = 0, NULL, 'Pagado') AS estatuspagado, b.total, a.tipocambio, a.origenprov, i.isr, i.quitarisr, 1 AS generar, NULL AS idbanco, f.abreviatura AS abreviaempresa ";
    $query.= "FROM detpresupuesto a INNER JOIN presupuesto b ON b.id = a.idpresupuesto INNER JOIN proyecto c ON c.id = b.idproyecto INNER JOIN moneda d ON d.id = a.idmoneda ";
    $query.= "INNER JOIN beneficiario e ON e.id = a.idproveedor INNER JOIN empresa f ON f.id = b.idempresa INNER JOIN tipogasto g ON g.id = b.idtipogasto ";
    $query.= "INNER JOIN subtipogasto h ON h.id = a.idsubtipogasto LEFT JOIN detpagopresup i ON a.id = i.iddetpresup ";
    $query.= "WHERE a.origenprov = 2 AND a.idestatuspresupuesto IN(3) AND i.pagado = 0 ";
    $query.= "ORDER BY 1, 5, 8, 18, 20";

    $queryEmpresas = "SELECT DISTINCT z.idempresa, z.empresa FROM ($query) z ORDER BY z.ordensumario";

    print json_encode(['empresas' => $db->getQuery($queryEmpresas), 'pagos' => $db->getQuery($query)]);
});

$app->post('/genpagos', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $cntPagos = count($d->pagos);
    $obj = new stdClass();
    $chqGenerados = '';
    for($i = 0; $i < $cntPagos; $i++){
        $pago = $d->pagos[$i];

        $getCorrela = "SELECT correlativo FROM banco WHERE id = $pago->idbanco";

        $query = "SELECT a.idmoneda, b.eslocal FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda WHERE a.id = $pago->idbanco";
        $datosMonedaBanco = $db->getQuery($query)[0];

        $query = "SELECT b.idmoneda, b.tipocambio, c.eslocal FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN moneda c ON c.id = b.idmoneda WHERE a.id = $pago->idpago";
        $datosMonedaOt = $db->getQuery($query)[0];

        $seMultiplica = true;
        $noConvertir = true;

        if((int)$datosMonedaOt->idmoneda !== (int)$datosMonedaBanco->idmoneda){
            $noConvertir = false;
            if((int)$datosMonedaOt->eslocal == 1 && (int)$datosMonedaBanco->eslocal == 0){
                $seMultiplica = false;
            }
        }        

        $query = "SELECT a.monto, a.notas, a.isr, a.quitarisr, b.idproveedor, c.chequesa AS beneficiario, b.origenprov, b.id AS iddetpresup ";
        $query.= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN proveedor c ON c.id = b.idproveedor WHERE a.id = $pago->idpago AND b.origenprov = 1 UNION ";
        $query.= "SELECT a.monto, a.notas, a.isr, a.quitarisr, b.idproveedor, c.nombre AS beneficiario, b.origenprov, b.id AS iddetpresup ";
        $query.= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN beneficiario c ON c.id = b.idproveedor WHERE a.id = $pago->idpago AND b.origenprov = 2";
        $detspago = $db->getQuery($query);
        if(count($detspago) > 0){
            $detpago = $detspago[0];

            $isrAQuitar = 0.00;
            if((int)$detpago->quitarisr == 1 && (float)$detpago->isr > 0) {
                $isrAQuitar = $db->calculaISR($db->calculaMontoBase($detpago->isr));
            }

            $monto = 0.00;
            if($noConvertir){
                $monto = $detpago->monto - ((int)$datosMonedaOt->eslocal == 1 ? $isrAQuitar : round($isrAQuitar / (float)$datosMonedaOt->tipocambio, 2));
            }else{
                if($seMultiplica){
                    $monto = round((float)$detpago->monto * (float)$datosMonedaOt->tipocambio, 2) - $isrAQuitar;
                }else{
                    $monto = round(((float)$detpago->monto / (float)$datosMonedaOt->tipocambio) - ($isrAQuitar / (float)$datosMonedaOt->tipocambio), 2);
                }
            }
            $query = "INSERT INTO tranban(";
            $query.= "idbanco, tipotrans, fecha, monto, beneficiario, concepto, numero, origenbene, idbeneficiario, tipocambio, iddetpresup, iddetpagopresup, anticipo";
            $query.= ") VALUES(";
            $query.= "$pago->idbanco, 'C', '$d->fecha', $monto, '$detpago->beneficiario', ";
            $query.= "'$detpago->notas', ($getCorrela), $detpago->origenprov, $detpago->idproveedor, $datosMonedaOt->tipocambio, $detpago->iddetpresup, $pago->idpago, 1";
            $query.= ")";
            $db->doQuery($query);
            $lastid = $db->getLastId();
            if((int)$lastid > 0) {
                $db->doQuery("UPDATE banco SET correlativo = correlativo + 1 WHERE id = $pago->idbanco");

                $obj->tipocambio = $datosMonedaOt->tipocambio;
                $obj->idbanco = $pago->idbanco;
                $obj->origenbene = $detpago->origenprov;
                $obj->monto = $monto;
                $obj->concepto = $detpago->notas;
                $url = 'http://localhost/sayet/php/tranbanc.php/doinsdetcont';
                //$url = 'http://localhost/sytdev/php/tranbanc.php/doinsdetcont';
                $data = ['obj' => $obj, 'lastid' => $lastid];
                $db->CallJSReportAPI('POST', $url, json_encode($data));
                
                $query = "UPDATE detpagopresup SET pagado = 1, origen = 1, idorigen = $lastid WHERE id = $pago->idpago";
                $db->doQuery($query);
                if($chqGenerados !== ''){ $chqGenerados .= ', '; }
                $chqGenerados.= $db->getOneField("SELECT numero FROM tranban WHERE id = $lastid");
            }
        }
    }
    print json_encode(['segeneraron' => $chqGenerados !== '', 'cheques' => $chqGenerados]);
});

$app->get('/notificaciones', function(){
    $db = new dbcpm();
    $query = "SELECT c.id AS idpresupuesto, b.id AS iddetpresupuesto, b.correlativo, a.nopago, b.idproveedor, d.nombre AS proveedor, c.idempresa, f.abreviatura AS empresa, e.simbolo, a.monto, ";
    $query.= "CONCAT('OT: ', c.id, '-', b.correlativo, ', Pago #', a.nopago, ', ', d.nombre, ', ', f.abreviatura, ', ', e.simbolo, ' ', a.monto) AS notificacion, b.origenprov, a.isr, a.quitarisr ";
    $query.= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN presupuesto c ON c.id = b.idpresupuesto INNER JOIN proveedor d ON d.id = b.idproveedor ";
    $query.= "INNER JOIN moneda e ON e.id = b.idmoneda INNER JOIN empresa f ON f.id = c.idempresa ";
    $query.= "WHERE a.notificado = 0 AND a.pagado = 0 AND b.origenprov = 1 AND b.idestatuspresupuesto = 3 ";
    $query.= "UNION ";
    $query.= "SELECT c.id AS idpresupuesto, b.id AS iddetpresupuesto, b.correlativo, a.nopago, b.idproveedor, d.nombre AS proveedor, c.idempresa, f.abreviatura AS empresa, e.simbolo, a.monto, ";
    $query.= "CONCAT('OT: ', c.id, '-', b.correlativo, ', Pago #', a.nopago, ', ', d.nombre, ', ', f.abreviatura, ', ', e.simbolo, ' ', a.monto) AS notificacion, b.origenprov, a.isr, a.quitarisr ";
    $query.= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN presupuesto c ON c.id = b.idpresupuesto INNER JOIN beneficiario d ON d.id = b.idproveedor ";
    $query.= "INNER JOIN moneda e ON e.id = b.idmoneda INNER JOIN empresa f ON f.id = c.idempresa ";
    $query.= "WHERE a.notificado = 0 AND a.pagado = 0 AND b.origenprov = 2 AND b.idestatuspresupuesto = 3 ";
    $query.= "ORDER BY 1, 3, 4, 5, 6";
    print $db->doSelectASJson($query);
});

$app->post('/cdp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->notas = $d->notas == '' ? "NULL" : "'$d->notas'";

    if(!isset($d->isr)){ $d->isr = 0.00; }
    if(!isset($d->quitarisr)){ $d->quitarisr = 0; }

    $nopago = $db->getOneField("SELECT IF(MAX(nopago) IS NULL, 1, MAX(nopago) + 1) FROM detpagopresup WHERE iddetpresup = $d->iddetpresup");
    $query = "INSERT INTO detpagopresup(iddetpresup, nopago, porcentaje, monto, notas, isr, quitarisr) VALUES($d->iddetpresup, $nopago, $d->porcentaje, $d->monto, $d->notas, $d->isr, $d->quitarisr)";
    $db->doQuery($query);
});

$app->post('/udp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    
    if(!isset($d->isr)){ $d->isr = 0.00; }
    if(!isset($d->quitarisr)){ $d->quitarisr = 0; }

    $d->notas = $d->notas == '' ? "NULL" : "'$d->notas'";
    $query = "UPDATE detpagopresup SET porcentaje = $d->porcentaje, monto = $d->monto, notas = $d->notas, isr = $d->isr, quitarisr = $d->quitarisr WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/ddp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM detpagopresup WHERE id = $d->id";
    $db->doQuery($query);
});

$app->get('/setnotificado/:idusr', function($idusr){
    $db = new dbcpm();
    $query = "UPDATE detpagopresup SET notificado = 1, fhnotificado = NOW(), idusrnotifica = $idusr WHERE notificado = 0 AND pagado = 0";
    $db->doQuery($query);
});

$app->post('/calcisr', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.idproveedor, a.origenprov, a.coniva, a.tipocambio, b.retensionisr AS retieneisr ";
    $query.= "FROM detpresupuesto a LEFT JOIN proveedor b ON b.id = a.idproveedor ";
    $query.= "WHERE a.id = $d->idot";
    $otq = $db->getQuery($query);
    $isr = 0.00;
    if(count($otq) > 0){
        $ot = $otq[0];
        if((int)$ot->origenprov == 1){
            if((int)$ot->retieneisr == 1){
                $subtotal = (int)$ot->coniva == 1 ? round((float)$d->monto / 1.12, 2) : (float)$d->monto;
                $isr = $db->calculaISR($subtotal);
            }
        }
    }

    print json_encode(['isr' => $isr]);
});

// Ampliaciones de presupuesto
$app->get('/ampliapresup/:iddetpresup', function($iddetpresup){
    $db = new dbcpm();

    $query = "SELECT id, idpresupuesto, iddetpresupuesto, correlativoamplia, monto, notas ";
    $query.= "FROM ampliapresupuesto ";
    $query.= "WHERE iddetpresupuesto = $iddetpresup ";
    $query.= "ORDER BY correlativoamplia";

    print $db->doSelectASJson($query);
});

$app->get('/getampliapresup/:idamplia', function($idamplia){
    $db = new dbcpm();

    $query = "SELECT id, idpresupuesto, iddetpresupuesto, correlativoamplia, monto, notas ";
    $query.= "FROM ampliapresupuesto ";
    $query.= "WHERE id = $idamplia ";
    $query.= "ORDER BY correlativoamplia";

    print $db->doSelectASJson($query);
});

$app->post('/cap', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $notas = trim($d->notas) == '' ? "NULL" : "'$d->notas'";
    $correlativo = (int)$db->getOneField("SELECT IF(ISNULL(MAX(correlativoamplia)), 1, MAX(correlativoamplia) + 1) FROM ampliapresupuesto WHERE iddetpresupuesto = $d->iddetpresupuesto");
    $query = "INSERT INTO ampliapresupuesto(idpresupuesto, iddetpresupuesto, correlativoamplia, monto, notas) VALUES(";
    $query.= "$d->idpresupuesto, $d->iddetpresupuesto, $correlativo, $d->monto, $notas";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/uap', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $notas = trim($d->notas) == '' ? "NULL" : "'$d->notas'";
    $query = "UPDATE ampliapresupuesto SET monto = $d->monto, notas = $notas ";
    $query.= "WHERE id = $d->idamplia";
    $db->doQuery($query);
});

$app->post('/dap', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "DELETE FROM ampliapresupuesto WHERE id = $d->idamplia";
    $db->doQuery($query);
});

$app->run();