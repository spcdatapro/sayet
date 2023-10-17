<?php
set_time_limit(0);
ini_set('memory_limit', '1536M');

require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

function proyectosPorUsuario($idusuario = 0)
{
    $db = new dbcpm();
    $proyectos = '';
    if (!in_array((int)$idusuario, [0, 1])) {
        $query = "SELECT IFNULL(GROUP_CONCAT(idproyecto SEPARATOR ','), '') FROM usuarioproyecto WHERE idusuario = $idusuario";
        $proyectos = $db->getOneField($query);
    }
    return $proyectos;
}

//API presupuestos
$app->post('/lstpresupuestos', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    if (!isset($d->idusuario)) {
        $d->idusuario = 0;
    }
    if (!isset($d->tipo)) {
        $d->tipo = 0;
    }
    $proyectos = proyectosPorUsuario((int)$d->idusuario);
    $query = "SELECT a.id, a.fechasolicitud, a.idproyecto, b.nomproyecto AS proyecto, a.idempresa, c.nomempresa AS empresa, a.idtipogasto, d.desctipogast AS tipogasto, a.idmoneda, e.simbolo, ";
    $query .= "IF(a.tipocambio > 1, a.total / a.tipocambio, a.total) AS total, a.notas, a.idusuario, f.nombre AS usuario, a.idestatuspresupuesto, g.descestatuspresup AS estatus, a.fechacreacion, a.fhenvioaprobacion, a.fhaprobacion, ";
    $query .= "a.idusuarioaprueba, h.nombre AS aprobadopor, a.tipo, a.idproveedor, a.idsubtipogasto, a.coniva, a.monto, a.tipocambio, a.excedente, TRIM(c.abreviatura) AS abreviaempre, a.origenprov, i.proveedor, ";
    $query .= "a.gastado AS gastado, IF(a.tipo = 1, 'OTS', 'OTM') AS tipostr, ";
    $query .= "IF(a.tipo = 1, (SELECT id FROM detpresupuesto WHERE idpresupuesto = a.id LIMIT 1), 0) AS idot, a.tipodocumento, 0 AS correlativo, ";
	$query .= "(SELECT GROUP_CONCAT(notas SEPARATOR '; ') FROM detpresupuesto WHERE idpresupuesto = a.id) AS notasdetalle ";
    $query .= "FROM presupuesto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = a.idempresa ";
    $query .= "INNER JOIN tipogasto d ON d.id = a.idtipogasto INNER JOIN moneda e ON e.id = a.idmoneda INNER JOIN usuario f ON f.id = a.idusuario ";
    $query .= "INNER JOIN estatuspresupuesto g ON g.id = a.idestatuspresupuesto LEFT JOIN usuario h ON h.id = a.idusuarioaprueba ";
    $query .= "LEFT JOIN (SELECT x.idpresupuesto, GROUP_CONCAT(DISTINCT x.proveedor ORDER BY x.proveedor SEPARATOR ', ') AS proveedor FROM (SELECT z.idpresupuesto, y.nombre AS proveedor FROM detpresupuesto z INNER JOIN proveedor y ON y.id = z.idproveedor ";
    $query .= "WHERE z.origenprov = 1 UNION SELECT z.idpresupuesto, y.nombre AS proveedor FROM detpresupuesto z INNER JOIN beneficiario y ON y.id = z.idproveedor WHERE z.origenprov = 2) x GROUP BY x.idpresupuesto) i ON a.id = i.idpresupuesto ";
    $query .= "WHERE a.fechasolicitud >= '$d->fdelstr' AND a.fechasolicitud <= '$d->falstr' ";
    $query .= (int)$d->tipo > 0 ? "AND a.tipo = $d->tipo " : '';
    $query .= trim($proyectos) != '' ? "AND a.idproyecto IN ($proyectos) " : '';
    $query .= $d->idestatuspresup != '' ? "AND a.idestatuspresupuesto IN($d->idestatuspresup) " : '';
    $query .= 'ORDER BY a.id DESC';
    print $db->doSelectASJson($query);
});

$app->get('/lstpresupuestospend(/:idusr)', function ($idusr = 0) {
    $db = new dbcpm();
    $limiteot = 0.00;

    if ((int)$idusr > 0) {
        $limiteot = (float)$db->getOneField("SELECT limiteot FROM usuario WHERE id = $idusr");
    }

    $query = "
        SELECT b.fechasolicitud AS fechaOrd, a.idpresupuesto AS id, a.correlativo, a.id AS idot, CONCAT(a.idpresupuesto, '-', a.correlativo) AS numero, DATE_FORMAT(b.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, 
        c.abreviatura AS empresa, d.referencia AS proyecto, e.nombre AS proveedor, f.simbolo AS moneda, a.monto, a.tipocambio, CONCAT(g.descripcion, ' - ', h.desctipogast) AS gasto, a.notas, 0 AS aprobada, 0 AS denegada, i.iniciales AS usuario,
        b.tipo, (SELECT COUNT(id) FROM ot_adjunto WHERE idot = a.id) AS adjuntos
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
    $query .= $limiteot > 0 ? "AND (a.monto * a.tipocambio) <= $limiteot " : '';
    $query .= "
        UNION
        SELECT b.fechasolicitud AS fechaOrd, a.idpresupuesto AS id, a.correlativo, a.id AS idot, CONCAT(a.idpresupuesto, '-', a.correlativo) AS numero, DATE_FORMAT(b.fechasolicitud, '%d/%m/%Y') AS fechasolicitud, 
        c.abreviatura AS empresa, d.referencia AS proyecto, e.nombre AS proveedor, f.simbolo AS moneda, a.monto, a.tipocambio, CONCAT(g.descripcion, ' - ', h.desctipogast) AS gasto, a.notas, 0 AS aprobada, 0 AS denegada, i.iniciales AS usuario,
        b.tipo, (SELECT COUNT(id) FROM ot_adjunto WHERE idot = a.id) AS adjuntos
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
    $query .= $limiteot > 0 ? "AND (a.monto * a.tipocambio) <= $limiteot " : '';
    $query .= "ORDER BY 1, 2, 3";
    print $db->doSelectASJson(trim($query));
});

//Configuración de montos de OTs que los usuarios pueden autorizar
$app->get('/usraprob(/:id)', function ($id = 0) {
    $db = new dbcpm();

    $query = "SELECT id, nombre, limiteot FROM usuario WHERE id IN(SELECT idusuario FROM permiso WHERE accesar = 1 " . ((int)$id == 0 ? '' : "AND idusuario = $id") . " AND iditemmenu = (SELECT id FROM itemmenu WHERE TRIM(url) = 'tranaprobpresup')) ORDER BY nombre";
    print $db->doSelectASJson($query);
});

$app->post('/usrmonto', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE usuario SET limiteot = $d->limiteot WHERE id = $d->id";
    $db->doQuery($query);
});
//Fin de Configuración de montos de OTs que los usuarios pueden autorizar

$app->post('/lstpresaprob', function () {
    $d = json_decode(file_get_contents('php://input'));    
    $db = new dbcpm();
    if ($d) {
        $query = "SELECT a.id, a.idestatuspresupuesto, a.fechasolicitud, a.idproyecto, b.nomproyecto AS proyecto, a.idempresa, TRIM(c.abreviatura) AS empresa, a.idtipogasto, d.desctipogast AS tipogasto, ";
        $query .= "a.idmoneda, e.simbolo AS moneda, a.total, a.notas AS descripcion, a.tipo, a.idproveedor, a.idsubtipogasto, a.coniva, a.monto, a.tipocambio, a.excedente ";
        $query .= "FROM presupuesto a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN empresa c ON c.id = a.idempresa INNER JOIN tipogasto d ON d.id = a.idtipogasto INNER JOIN moneda e ON e.id = a.idmoneda ";
        $query .= "WHERE (a.idestatuspresupuesto = 3 OR (SELECT COUNT(idestatuspresupuesto) FROM detpresupuesto WHERE idpresupuesto = a.id AND idestatuspresupuesto = 3) > 0) ";
        $query .= "AND a.fechasolicitud >= '$d->fdelstr' AND a.fechasolicitud <= '$d->falstr' ";
        $query .= "ORDER BY a.id DESC, b.nomproyecto";
        $presupuestos = $db->getQuery($query);
        $cntPresup = count($presupuestos);
        if ($cntPresup > 0) {
            for ($i = 0; $i < $cntPresup; $i++) {
                $presupuesto = $presupuestos[$i];
                $query = "SELECT a.id AS idot, a.idpresupuesto, a.correlativo AS id, a.idproveedor, b.nombre AS proyecto, a.idsubtipogasto, c.descripcion AS tipogasto, a.coniva, a.monto AS total, ";
                $query .= "a.tipocambio, a.excedente, a.origenprov ";
                $query .= "FROM detpresupuesto a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto ";
                $query .= "WHERE a.origenprov = 1 AND a.idestatuspresupuesto = 3 AND a.idpresupuesto = $presupuesto->id ";
                $query .= "UNION ";
                $query .= "SELECT a.id AS idot, a.idpresupuesto, a.correlativo AS id, a.idproveedor, b.nombre AS proyecto, a.idsubtipogasto, c.descripcion AS tipogasto, a.coniva, a.monto AS total, ";
                $query .= "a.tipocambio, a.excedente, a.origenprov ";
                $query .= "FROM detpresupuesto a INNER JOIN beneficiario b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto ";
                $query .= "WHERE a.origenprov = 2 AND a.idestatuspresupuesto = 3 AND a.idpresupuesto = $presupuesto->id ";
                $query .= "ORDER BY 3";
                $presupuesto->children = $db->getQuery($query);
            }
        } else {
            $presupuestos = [];
        }
    } else {
        $presupuestos = [];
    }
    print json_encode($presupuestos);
});

$app->post('/lstpresupuestosm', function () {
    $db = new dbcpm();
    $query = "SELECT id FROM presupuesto WHERE tipo = 2 AND idestatuspresupuesto IN(1, 2, 3) ORDER BY id DESC";
    print $db->doSelectASJson($query);
});

$app->get('/getpresupuesto/:idpresupuesto', function ($idpresupuesto) {
    $db = new dbcpm();
    $ids = array();
    $query = "SELECT 
                a.id,
                a.tipo,
                DATE_FORMAT(a.fechacreacion, '%d/%m/%Y') AS fecha,
                b.id AS idproyecto,
                b.nomproyecto,
                c.id AS idempresa,
                c.nomempresa,
                e.id AS idtipogasto,
                e.desctipogast,
                f.id AS idmoneda,
                f.simbolo,
                a.total,
                a.notas,
                a.origenprov,
                IFNULL(g.id, h.id) AS idproveedor,
                IFNULL(g.nombre, h.nombre) AS proveedor,
                i.id AS idsubtipogasto,
                i.descripcion AS subtipogasto,
                a.coniva,
                a.total,
                a.tipocambio,
                a.idestatuspresupuesto,
                a.idusuario,
                a.idusuarioaprueba
            FROM
                presupuesto a
                    INNER JOIN
                proyecto b ON a.idproyecto = b.id
                    INNER JOIN
                empresa c ON a.idempresa = c.id
                    INNER JOIN
                tipogasto e ON a.idtipogasto = e.id
                    INNER JOIN
                moneda f ON a.idmoneda = f.id
                    LEFT JOIN
                proveedor g ON a.idproveedor = g.id
                    AND a.origenprov = 1
                    LEFT JOIN
                beneficiario h ON a.idproveedor = h.id AND origenprov = 2
                    LEFT JOIN
                subtipogasto i ON a.idsubtipogasto = i.id
            WHERE
                a.id = $idpresupuesto";
    $orden = $db->getQuery($query)[0];

    $query = "SELECT 
                a.id,
                a.correlativo,
                a.origenprov,
                IFNULL(b.id, c.id) AS idproveedor,
                IFNULL(b.nombre, c.nombre) AS proveedor,
                d.id AS idsubtipogasto,
                d.descripcion AS subtipogasto,
                a.coniva,
                e.id AS idmoneda,
                e.simbolo AS moneda,
                a.monto,
                a.tipocambio,
                a.notas
            FROM
                detpresupuesto a
                    LEFT JOIN
                proveedor b ON a.idproveedor = b.id
                    AND a.origenprov = 1
                    LEFT JOIN
                beneficiario c ON a.idproveedor = c.id
                    AND a.origenprov = 2
                    INNER JOIN
                subtipogasto d ON a.idsubtipogasto = d.id
                    INNER JOIN
                moneda e ON a.idmoneda = e.id
            WHERE
                a.idpresupuesto = $idpresupuesto
                AND a.idestatuspresupuesto IN(1, 2, 3, 5)";
    $orden->ots = $db->getQuery($query);

    $cntsOts = count($orden->ots);

    if ($cntsOts > 0) {

        for ($i = 0; $i < $cntsOts; $i++) {
            $ot = $orden->ots[$i];
            array_push($ids, $ot->id);
        }

        getTotales($orden, $ids, $db);

    } else {
        $orden->gastado = 0.00;
        $orden->avance = 0.00;
        $orden->diferencia = 0.00;
        $orden->monto = 0.00;
    }

    print json_encode([$orden]);
});

function getTotales($orden, $ids, $db) {
    // variables generales para OTM
    $gastos_ot = array();
    $montos_ot = array();

    // convertir arrahy de ids en string 
    $ids_str = implode(',', $ids);

    // contar cuantas ots tiene otm si no es multiple usar 1
    $cntsOts = count($orden->ots);

    // traer tipo cambio proveedor, primero de compra, transaccion y por ultimo de orden
    $query = "SELECT tipocambio FROM tranban WHERE iddetpresup IN($ids_str) AND tipocambio > 1";
    $tiposcambio = $db->getQuery($query);

    $cntsTipos = count($tiposcambio);
    if ($cntsTipos > 0) {
        $sumtipos = array();

        for ($i = 0; $i < $cntsTipos; $i++) {
            $tc = $tiposcambio[$i];
            array_push($sumtipos, $tc->tipocambio);
        }

        $tipocambioprov = array_sum($sumtipos) / $cntsTipos;
    } else {
        $tipocambioprov = 7.8;
    }

    // traer monto, moneda, idordentrabajo y tipocambio de compra
    $query = "SELECT id, totfact, idmoneda, tipocambio, isr, ordentrabajo AS ot FROM compra WHERE ordentrabajo IN($ids_str) AND idreembolso = 0 
    AND id NOT IN(SELECT idcompra FROM detnotacompra) AND idtipofactura < 8
    UNION ALL SELECT b.id, b.totfact, b.idmoneda, b.tipocambio, b.isr, a.ordentrabajo AS ot FROM reembolso a 
    INNER JOIN compra b ON b.idreembolso = a.id WHERE a.ordentrabajo IN($ids_str)";
    $tcompras = $db->getQuery($query);

    $cntCompras = count($tcompras);

    // traer monto y tipocambio de transaccion bancaria
    $query = "SELECT a.monto * IF(a.tipotrans = 'R', -1, 1) AS monto, a.tipocambio, b.idmoneda, a.iddetpresup AS ot FROM tranban a INNER JOIN banco b ON a.idbanco = b.id 
    WHERE a.iddetpresup IN($ids_str) AND (SELECT COUNT(b.id) FROM doctotranban b WHERE b.idtranban = a.id AND b.idtipodoc = 2) = 0 
    AND a.liquidado = 0 AND a.iddocliquida = 0 AND a.anulado = 0 
    UNION ALL 
    SELECT b.monto, a.tipocambio, c.idmoneda, a.iddetpresup AS ot FROM tranban a INNER JOIN dettranreem b ON b.idtranban = a.id 
    INNER JOIN banco c ON a.idbanco = c.id WHERE a.iddetpresup IN($ids_str)";
    $trans = $db->getQuery($query);

    $cntTranas = count($trans);

    for ($i = 0; $i < $cntsOts; $i++) {
        // crear array total de OT[$i]
        $sisr = array();
        $stran = array();
        $tc_pro = array();

        $ot = $orden->ots[$i];

        // loop de compras
        for ($j = 0; $j < $cntCompras; $j++) {
            $compra = $tcompras[$j];
            $tc = $compra->tipocambio > 1 ? $compra->tipocambio : $tipocambioprov;

            // validar si la orden de la compra y la orden son iguales
            if ($ot->id == $compra->ot) {
                if ($ot->idmoneda != $compra->idmoneda) {
                    if ($ot->idmoneda == 1) {
                        $montoisr = $compra->isr * $tc;
                    } else {
                        $montoisr = $compra->isr / $tc;
                    }
                } else {
                    $montoisr = $compra->isr;
                }
                array_push($sisr, $montoisr);
            }
        }
        // sumas compras
        $tisr = array_sum($sisr);

        // loop transacciones bancarias
        for ($j = 0; $j < $cntTranas; $j++) {
            $tran = $trans[$j];
            $tc = $tran->tipocambio > 1 ? $tran->tipocambio : $tipocambioprov;

            if ($ot->id == $tran->ot) {
                if ($ot->idmoneda !== $tran->idmoneda) {
                    if ($ot->idmoneda == 1) {
                        $monto = $tran->monto * $tc;
                    } else {
                        $monto = $tran->monto / $tc;
                    }
                } else {
                    $monto = $tran->monto;
                }
                array_push($stran, $monto);
                if ($tran->tipocambio > 1) {
                    array_push($tc_pro, $tran->tipocambio);
                }
            }
        }
        // sumas transacciones bancarias
        $ttran = array_sum($stran);

        $gastado = $ttran + $tisr;

        $conteo_promedio = count($tc_pro) > 0 ? count($tc_pro) : 1; 
        $sum_promedio = array_sum($tc_pro) > 0 ? array_sum($tc_pro) : 1;
        $tc_prom = $sum_promedio / $conteo_promedio;

        $tc = $ot->tipocambio > 1 ? $ot->tipocambio : $tipocambioprov;
        $tc_gasto = $tc_prom > 1 ? round($tc_prom, 5) : $ot->tipocambio;

        // efectos de cada OTS en la OTM
        if ($ot->idmoneda == $orden->idmoneda) {
            $monto = $ot->monto;
            $gasto = $gastado;
        } else {
            if ($orden->idmoneda == 1) {
                $monto = $ot->monto * $tc;
                $gasto = $gastado * $tc_gasto;
            } else {
                $monto = $ot->monto / $tc;
                $gasto = $gastado / $tc_gasto;
            }
        }

        array_push($montos_ot, $monto);
        array_push($gastos_ot, $gasto);
    }

    $montog = array_sum($montos_ot);
    $gastado = array_sum($gastos_ot);

    // calculos otm
    $diferencia = $montog - $gastado;
    $avance = ($gastado * 100) / $montog;

    // insertar valores
    $orden->gastado = number_format($gastado, 2, '.', ',');
    $orden->avance = number_format($avance, 2, '.', ',');
    $orden->diferencia = number_format($diferencia, 2, '.', ',');
    $orden->monto = number_format($montog, 2, '.', ',');
}

function updTotPresupuesto($idpresupuesto)
{
    $db = new dbcpm();
    $query = "UPDATE presupuesto SET total = (SELECT IF(ISNULL(SUM(monto * tipocambio)), 0.00, ROUND(SUM(monto * tipocambio), 2)) FROM detpresupuesto WHERE idpresupuesto = $idpresupuesto) WHERE id = $idpresupuesto";
    $db->doQuery($query);
}

function creaDetallePresupuesto($d)
{
    $db = new dbcpm();
    $correlativo = (int)$db->getOneField("SELECT IF(ISNULL(MAX(correlativo)), 1, MAX(correlativo) + 1) AS correlativo FROM detpresupuesto WHERE idpresupuesto = $d->idpresupuesto");
    $excedente = round((float)$db->getOneField("SELECT excedente FROM confpresupuestos WHERE id = 1"), 2);
    $query = "INSERT INTO detpresupuesto(";
    $query .= "idpresupuesto, correlativo, idproveedor, idsubtipogasto, coniva, escontado, monto, tipocambio, excedente, notas, origenprov, idmoneda, tipodocumento";
    $query .= ") VALUES(";
    $query .= "$d->idpresupuesto, $correlativo, $d->idproveedor, $d->idsubtipogasto, $d->coniva, 0, $d->monto, $d->tipocambio, $excedente, '$d->notas', $d->origenprov, $d->idmoneda, $d->tipodocumento";
    $query .= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    updTotPresupuesto($d->idpresupuesto);
    $obj = new stdClass();
    $obj->origen = 2;
    $obj->idpresupuesto = $lastid;
    $obj->evento = 'C';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
}

$app->post('/c', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if (!isset($d->tipodocumento)) {
        $d->tipodocumento = 0;
    }
    $excedente = round((float)$db->getOneField("SELECT excedente FROM confpresupuestos WHERE id = 1"), 2);
    $query = "INSERT INTO presupuesto(";
    $query .= "fechasolicitud, idproyecto, idempresa, idtipogasto, idmoneda, total, notas, fechacreacion, idusuario, idestatuspresupuesto, ";
    $query .= "tipo, idproveedor, idsubtipogasto, coniva, escontado, monto, tipocambio, excedente, origenprov, tipodocumento";
    $query .= ") VALUES(";
    $query .= "'$d->fechasolicitudstr', $d->idproyecto, $d->idempresa, $d->idtipogasto, $d->idmoneda, 0.00, '$d->notas', NOW(), $d->idusuario, 1, ";
    $query .= "$d->tipo, $d->idproveedor, $d->idsubtipogasto, $d->coniva, 0, $d->monto, $d->tipocambio, $excedente, $d->origenprov, $d->tipodocumento";
    $query .= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();

    if ((int)$d->tipo == 1) {
        $d->idpresupuesto = $lastid;
        creaDetallePresupuesto($d);
    }

    $obj = new stdClass();
    $obj->origen = 1;
    $obj->idpresupuesto = $lastid;
    $obj->evento = 'C';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);

    print json_encode(['lastid' => $lastid]);
});

function actualizaDetallePresupuesto($d)
{
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET ";
    $query .= "idproveedor = $d->idproveedor, idsubtipogasto = $d->idsubtipogasto, coniva = $d->coniva, escontado = 0, monto = $d->total, tipocambio = $d->tipocambio, notas = '$d->notas', origenprov = $d->origenprov, ";
    $query .= "idmoneda = $d->idmoneda, tipodocumento = $d->tipodocumento ";
    $query .= "WHERE idpresupuesto = " . $d->id;
    $db->doQuery($query);
    updTotPresupuesto($d->idpresupuesto);
    $idot = $db->getOneField("SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1");
    $obj = new stdClass();
    $obj->origen = 2;
    $obj->idpresupuesto = $idot;
    $obj->evento = 'U';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
}

$app->post('/u', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    if (!isset($d->tipodocumento)) {
        $d->tipodocumento = 0;
    }
    $query = "UPDATE presupuesto SET ";
    $query .= "fechasolicitud = '$d->fechasolicitudstr', idproyecto = $d->idproyecto, idempresa = $d->idempresa, idtipogasto = $d->idtipogasto, ";
    $query .= "idmoneda = $d->idmoneda, notas = '$d->notas', fechamodificacion = NOW(), lastuser = $d->idusuario, ";
    $query .= "idproveedor = $d->idproveedor, idsubtipogasto = $d->idsubtipogasto, coniva = $d->coniva, escontado = 0, monto = '$d->monto', tipocambio = $d->tipocambio, origenprov = $d->origenprov, ";
    $query .= "tipodocumento = $d->tipodocumento ";
    $query .= "WHERE id = " . $d->id;
    // echo $query; return;
    $db->doQuery($query);

    $obj = new stdClass();
    $obj->origen = 1;
    $obj->idpresupuesto = $d->id;
    $obj->evento = 'U';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);

    if ((int)$d->tipo == 1) {
        $d->idpresupuesto = $d->id;
        actualizaDetallePresupuesto($d);
    }
});

$app->post('/d', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM bitacorapresupuesto WHERE origen = 1 AND idpresupuesto = $d->id");
    $db->doQuery("DELETE FROM detpresupuesto WHERE idpresupuesto = $d->id");
    $db->doQuery("DELETE FROM presupuesto WHERE id = $d->id");
});

$app->post('/ep', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->esot)) {
        $d->esot = 0;
    }
    $db = new dbcpm();
    $idot = $d->id;
    $obj = new stdClass();
    if ($d->esot == 0) {
        $query = "UPDATE presupuesto SET fhenvioaprobacion = NOW(), idestatuspresupuesto = 2, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->doQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
        $obj->origen = 1;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'U';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    }
    $query = "UPDATE detpresupuesto SET fhenvioaprobacion = NOW(), idestatuspresupuesto = 2, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $idot";
    $db->doQuery($query);
    $obj->origen = 2;
    $obj->idpresupuesto = $idot;
    $obj->evento = 'U';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
});

$app->post('/ap', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 3, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->idot";
    $db->getQuery($query);
    $obj = new stdClass();
    $obj->origen = 2;
    $obj->idpresupuesto = $d->idot;
    $obj->evento = 'A';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
    if ((int)$d->tipo == 1) {
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 3, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $obj->origen = 1;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'A';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    }
});

$app->post('/np', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 4, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->idot";
    $db->getQuery($query);
    $obj = new stdClass();
    $obj->origen = 2;
    $obj->idpresupuesto = $d->idot;
    $obj->evento = 'N';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
    if ((int)$d->tipo == 1) {
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 4, fhaprobacion = NOW(), idusuarioaprueba = $d->idusuario, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $obj->origen = 1;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'N';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    }
});

$app->post('/tp', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->esot)) {
        $d->esot = 0;
    }
    $db = new dbcpm();
    $idot = $d->id;
    $obj = new stdClass();
    if ($d->esot == 0) {
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 5, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
        $obj->origen = 1;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'T';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    } elseif ($d-> esot == 1) {
        $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 5, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $idot";
        $db->getQuery($query);
        $obj->origen = 2;
        $obj->idpresupuesto = $idot;
        $obj->evento = 'T';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    } else {
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 5, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 5, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE idpresupuesto = $d->id AND idestatuspresupuesto !=6 ";
        $db->getQuery($query);
        $obj->origen = 1;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'T';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    }
});

$app->post('/rp', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->esot)) {
        $d->esot = 0;
    }
    $db = new dbcpm();
    $idot = $d->id;
    $obj = new stdClass();
    if ($d->esot == 0) {
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 3, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
        $obj->origen = 1;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'R';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    } elseif ($d->esot == 1) {
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 3, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $idot";
    $db->getQuery($query);
    $obj->origen = 2;
    $obj->idpresupuesto = $idot;
    $obj->evento = 'R';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
    } else {
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 1, fechamodificacion = NOW(), lastuser = $d->idusuario WHERE id = $d->id";
        $db->getQuery($query);
        $obj->origen = 2;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'R';
        $obj->idusuario = $d->idusuario;
        insertaBitacoraPresupuesto($db, $obj);
    }
});

$app->post('/anulapres', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->esot)) {
        $d->esot = 0;
    }
    $db = new dbcpm();
    $idot = $d->id;
    $obj = new stdClass();
    if ($d->esot == 0) {
        $query = "UPDATE presupuesto SET idestatuspresupuesto = 6, fhanulacion = NOW(), idusuarioanula = $d->idusuarioanula, idrazonanula = $d->idrazonanula WHERE id = $d->id";
        $db->getQuery($query);
        $query = "SELECT id FROM detpresupuesto WHERE idpresupuesto = $d->id LIMIT 1";
        $idot = $db->getOneField($query);
        $obj->origen = 1;
        $obj->idpresupuesto = $d->id;
        $obj->evento = 'V';
        $obj->idusuario = $d->idusuarioanula;
        insertaBitacoraPresupuesto($db, $obj);
    }
    $query = "UPDATE detpresupuesto SET idestatuspresupuesto = 6, fhanulacion = NOW(), idusuarioanula = $d->idusuarioanula, idrazonanula = $d->idrazonanula WHERE id = $idot";
    $db->getQuery($query);
    $obj->origen = 2;
    $obj->idpresupuesto = $idot;
    $obj->evento = 'V';
    $obj->idusuario = $d->idusuarioanula;
    insertaBitacoraPresupuesto($db, $obj);
});

//API detalle de presupuestos (OTs)
$app->get('/lstot/:idpresupuesto', function ($idpresupuesto) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.escontado, a.monto AS monto, f.simbolo AS moneda, d.total, a.tipocambio, a.excedente, a.notas, a.origenprov, ";
    $query .= "a.idmoneda, a.idestatuspresupuesto ";
    $query .= "FROM detpresupuesto a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query .= "INNER JOIN moneda e ON e.id = d.idmoneda LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN ampliapresupuesto j ON j.iddetpresupuesto = a.id ";
    $query .= "WHERE a.origenprov = 1 AND a.idpresupuesto = $idpresupuesto ";
    $query .= "UNION ";
    $query .= "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.escontado, a.monto AS monto, f.simbolo AS moneda, d.total, a.tipocambio, a.excedente, a.notas, a.origenprov, ";
    $query .= "a.idmoneda, a.idestatuspresupuesto ";
    $query .= "FROM detpresupuesto a INNER JOIN beneficiario b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query .= "INNER JOIN moneda e ON e.id = d.idmoneda LEFT JOIN moneda f ON f.id = a.idmoneda LEFT JOIN ampliapresupuesto j ON j.iddetpresupuesto = a.id ";
    $query .= "WHERE a.origenprov = 2 AND a.idpresupuesto = $idpresupuesto ";
    $query .= "ORDER BY 3";
    print $db->doSelectASJson($query);
});

$app->get('/getot/:idot', function ($idot) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, a.origenprov, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.escontado, a.monto, i.simbolo AS moneda, d.total, a.tipocambio, a.excedente, ";
    $query .= "f.nomproyecto AS proyecto, g.desctipogast AS tipogasto, d.fechasolicitud, h.abreviatura AS empresa, a.notas, a.origenprov, a.idmoneda, a.idestatuspresupuesto, a.tipodocumento ";
    $query .= "FROM detpresupuesto a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query .= "INNER JOIN moneda e ON e.id = d.idmoneda INNER JOIN proyecto f ON f.id = d.idproyecto INNER JOIN tipogasto g ON g.id = d.idtipogasto INNER JOIN empresa h ON h.id = d.idempresa ";
    $query .= "LEFT JOIN moneda i ON i.id = a.idmoneda ";
    $query .= "WHERE a.origenprov = 1 AND a.id = $idot ";
    $query .= "UNION ";
    $query .= "SELECT a.id, a.idpresupuesto, a.correlativo, a.idproveedor, a.origenprov, b.nombre AS proveedor, a.idsubtipogasto, c.descripcion AS subtipogasto, a.coniva, a.escontado, a.monto, i.simbolo AS moneda, d.total, a.tipocambio, a.excedente, ";
    $query .= "f.nomproyecto AS proyecto, g.desctipogast AS tipogasto, d.fechasolicitud, h.abreviatura AS empresa, a.notas, a.origenprov, a.idmoneda, a.idestatuspresupuesto, a.tipodocumento ";
    $query .= "FROM detpresupuesto a INNER JOIN beneficiario b ON b.id = a.idproveedor INNER JOIN subtipogasto c ON c.id = a.idsubtipogasto INNER JOIN presupuesto d ON d.id = a.idpresupuesto ";
    $query .= "INNER JOIN moneda e ON e.id = d.idmoneda INNER JOIN proyecto f ON f.id = d.idproyecto INNER JOIN tipogasto g ON g.id = d.idtipogasto INNER JOIN empresa h ON h.id = d.idempresa ";
    $query .= "LEFT JOIN moneda i ON i.id = a.idmoneda ";
    $query .= "WHERE a.origenprov = 2 AND a.id = $idot ";
    print $db->doSelectASJson($query);
});

$app->post('/cd', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->idusuario)) {
        $d->idusuario = 0;
    }
    if (!isset($d->tipodocumento)) {
        $d->tipodocumento = 0;
    }
    $db = new dbcpm();
    $correlativo = (int)$db->getOneField("SELECT IF(ISNULL(MAX(correlativo)), 1, MAX(correlativo) + 1) AS correlativo FROM detpresupuesto WHERE idpresupuesto = $d->idpresupuesto");
    $excedente = round((float)$db->getOneField("SELECT excedente FROM confpresupuestos WHERE id = 1"), 2);
    $query = "INSERT INTO detpresupuesto(";
    $query .= "idpresupuesto, correlativo, idproveedor, idsubtipogasto, coniva, escontado, monto, tipocambio, excedente, notas, origenprov, idmoneda, tipodocumento";
    $query .= ") VALUES(";
    $query .= "$d->idpresupuesto, $correlativo, $d->idproveedor, $d->idsubtipogasto, $d->coniva, $d->escontado, $d->monto, $d->tipocambio, $excedente, '$d->notas', $d->origenprov, $d->idmoneda, $d->tipodocumento";
    $query .= ")";
    $db->doQuery($query);
    $lastid = $db->getLastId();
    updTotPresupuesto($d->idpresupuesto);
    $obj = new stdClass();
    $obj->origen = 2;
    $obj->idpresupuesto = $lastid;
    $obj->evento = 'C';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
    print json_encode(['lastid' => $lastid]);
});


$app->post('/ud', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->idusuario)) {
        $d->idusuario = 0;
    }
    if (!isset($d->tipodocumento)) {
        $d->tipodocumento = 0;
    }
    $db = new dbcpm();
    $query = "UPDATE detpresupuesto SET ";
    $query .= "idproveedor = $d->idproveedor, idsubtipogasto = $d->idsubtipogasto, coniva = $d->coniva, escontado = $d->escontado, monto = $d->monto, tipocambio = $d->tipocambio, notas = '$d->notas', origenprov = $d->origenprov, ";
    $query .= "idmoneda = $d->idmoneda, tipodocumento = $d->tipodocumento ";
    $query .= "WHERE id = $d->id";
    $db->doQuery($query);
    updTotPresupuesto($d->idpresupuesto);
    $obj = new stdClass();
    $obj->origen = 2;
    $obj->idpresupuesto = $d->id;
    $obj->evento = 'U';
    $obj->idusuario = $d->idusuario;
    insertaBitacoraPresupuesto($db, $obj);
});

$app->post('/attachto', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT idpresupuesto FROM detpresupuesto WHERE id = $d->id";
    $idpresupuestoOriginal = $db->getOneField($query);

    $query = "SELECT IFNULL(MAX(correlativo), 0) + 1 FROM detpresupuesto WHERE idpresupuesto = $d->idpresupuesto";
    $correlativo = $db->getOneField($query);

    $query = "UPDATE detpresupuesto SET idpresupuesto = $d->idpresupuesto, correlativo = $correlativo WHERE id = $d->id";
    $db->doQuery($query);
    updTotPresupuesto($idpresupuestoOriginal);
    updTotPresupuesto($d->idpresupuesto);
    print json_encode(['numero' => ($d->idpresupuesto.'-'.$correlativo)]);
});

$app->get('/lstotm', function() {
    $db = new dbcpm();
    $query = "SELECT id AS idpresupuesto FROM presupuesto a WHERE a.tipo = 2 AND a.idestatuspresupuesto IN(1, 2, 3) ORDER BY a.fechasolicitud DESC";
    print $db->doSelectASJson($query);
});

$app->post('/dd', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $idpresupuesto = (int)$db->getOneField("SELECT idpresupuesto FROM detpresupuesto WHERE id = $d->id");
    $db->doQuery("DELETE FROM detpresupuesto WHERE id = $d->id");
    updTotPresupuesto($idpresupuesto);
});

$app->get('/avanceot/:idot', function ($idot) {
    $db = new dbcpm();
    $query = "SELECT 1 AS origen, a.id, a.fecha, b.siglas AS banco, a.tipotrans, a.numero, c.simbolo AS moneda, a.monto, a.concepto, a.tipocambio, getIsrTranBan(a.id) AS isr, ";
    $query .= "(SELECT GROUP_CONCAT(CONCAT(serie, '-', documento) SEPARATOR ', ') FROM doctotranban WHERE idtranban = a.id GROUP BY idtranban) AS factura ";
    $query .= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
    $query .= "WHERE a.anulado = 0 AND a.iddetpresup = $idot ";
    $query .= "UNION ALL ";
    $query .= "SELECT 2 AS origen, a.id, a.fechafactura AS fecha, '' AS banco, '' AS tipotrans, CONCAT(a.serie, '-',a.documento) AS numero, b.simbolo AS moneda, a.totfact AS monto, a.conceptomayor AS concepto, a.tipocambio, a.isr, NULL AS factura ";
    $query .= "FROM compra a INNER JOIN moneda b ON b.id = a.idmoneda ";
    $query .= "WHERE a.ordentrabajo = $idot ";
    $query .= "ORDER BY 3 DESC, 4, 5, 6";
    print $db->doSelectASJson($query);
});

$app->post('/masexcede', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if ((int)$d->esot === 0) {
        $oldExcedente = $db->getOneField("SELECT excedente FROM presupuesto WHERE id = $d->id");
        $query = "UPDATE presupuesto SET excedente = $d->monto WHERE id = $d->id";
        $db->doQuery($query);
        $query = "UPDATE detpresupuesto SET excedente = $d->monto WHERE idpresupuesto = $d->id";
        $db->doQuery($query);
        $cambio = "Cambio de porcentaje de excedente a presupuesto No. $d->id. De $oldExcedente a $d->monto.";
        $tabla = 'presupuesto';
    } else {
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
$app->get('/lstnotas/:iddetpresup', function ($iddetpresup) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresupuesto, a.fechahora, a.nota, a.usuario, b.nombre, a.fhcreacion ";
    $query .= "FROM notapresupuesto a INNER JOIN usuario b ON b.id = a.usuario ";
    $query .= "WHERE a.iddetpresupuesto = $iddetpresup ";
    $query .= "ORDER BY a.fechahora DESC, b.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/getnota/:idnota', function ($idnota) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresupuesto, a.fechahora, a.nota, a.usuario, b.nombre, a.fhcreacion ";
    $query .= "FROM notapresupuesto a INNER JOIN usuario b ON b.id = a.usuario ";
    $query .= "WHERE a.id = $idnota";
    print $db->doSelectASJson($query);
});

$app->post('/cnp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO notapresupuesto(";
    $query .= "iddetpresupuesto, fechahora, nota, usuario, fhcreacion";
    $query .= ") VALUES(";
    $query .= "$d->iddetpresupuesto, NOW(), '$d->nota', $d->idusuario, NOW()";
    $query .= ")";
    $db->doQuery($query);
});


$app->post('/unp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE notapresupuesto SET ";
    $query .= "fechahora = NOW(), nota = '$d->nota', usuario = $d->idusuario ";
    $query .= "WHERE id = " . $d->id;
    $db->doQuery($query);
});

$app->post('/dnp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM notapresupuesto WHERE id = $d->id");
});

//API detalle de pago de OT
$app->get('/lstdetpago/:iddetpresup', function ($iddetpresup) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresup, a.nopago, a.porcentaje, a.monto, a.notas, a.pagado, a.origen, a.idorigen, a.isr, a.quitarisr, a.idmoneda, b.simbolo AS moneda ";
    $query .= "FROM detpagopresup a LEFT JOIN moneda b ON b.id = a.idmoneda ";
    $query .= "WHERE a.iddetpresup = $iddetpresup ";
    $query .= "ORDER BY a.nopago";
    print $db->doSelectASJson($query);
});

$app->get('/getdetpago/:iddetpago', function ($iddetpago) {
    $db = new dbcpm();
    $query = "SELECT a.id, a.iddetpresup, a.nopago, a.porcentaje, a.monto, a.notas, a.pagado, a.origen, a.idorigen, a.isr, a.quitarisr, a.idmoneda ";
    $query .= "FROM detpagopresup a WHERE a.id = $iddetpago";
    print $db->doSelectASJson($query);
});

$app->get('/lstpagos/:idempresa', function ($idempresa) {
    $db = new dbcpm();
    $query = "SELECT 
                a.id,
                b.id AS idpresupuesto,
                CONCAT(b.id, '-', a.correlativo) AS ot,
                IFNULL(c.nombre, f.nombre) AS proveedor,
                IFNULL(c.id, f.id) AS idproveedor,
                a.origenprov,
                d.nomempresa AS empresa,
                a.monto,
                e.simbolo AS moneda,
                ROUND(a.tipocambio, 2) AS tipocambio,
                a.notas,
                b.fechacreacion
            FROM
                detpresupuesto a
                    INNER JOIN
                presupuesto b ON a.idpresupuesto = b.id
                    LEFT JOIN
                proveedor c ON a.idproveedor = c.id
                    AND a.origenprov = 1
                    INNER JOIN
                empresa d ON b.idempresa = d.id
                    INNER JOIN
                moneda e ON a.idmoneda = e.id
                    LEFT JOIN
                beneficiario f ON a.idproveedor = c.id
                    AND a.origenprov = 2
            WHERE
                a.idestatuspresupuesto = 3
                    AND b.idempresa = $idempresa
            ORDER BY fechacreacion ";
    // print $query;
    print $db->doSelectASJson($query);
});

$app->get('/pagospend', function () {
    $db = new dbcpm();

    $query = "SELECT f.ordensumario, a.idpresupuesto, a.id, b.idproyecto, c.nomproyecto AS proyecto, b.fhaprobacion, a.idproveedor, e.nombre AS proveedor, b.idmoneda, d.simbolo AS moneda, a.monto, ";
    $query .= "b.fechasolicitud, f.nomempresa AS empresa, g.desctipogast AS tipogasto, h.descripcion AS subtipogasto, IF(a.coniva = 1, 'I.V.A. incluido', 'I.V.A. NO incluido') AS coniva, a.correlativo, ";
    $query .= "CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, b.idempresa, i.nopago, i.porcentaje, i.idmoneda AS idmonedapago, j.simbolo AS monedapago, i.monto AS valor, i.notas, i.id AS iddetpagopresup, i.pagado, ";
    $query .= "IF(i.pagado = 0, NULL, 'Pagado') AS estatuspagado, b.total, a.tipocambio, a.origenprov, i.isr, i.quitarisr, 0 AS generar, NULL AS idbanco, f.abreviatura AS abreviaempresa ";
    $query .= "FROM detpresupuesto a INNER JOIN presupuesto b ON b.id = a.idpresupuesto INNER JOIN proyecto c ON c.id = b.idproyecto INNER JOIN moneda d ON d.id = a.idmoneda ";
    $query .= "INNER JOIN proveedor e ON e.id = a.idproveedor INNER JOIN empresa f ON f.id = b.idempresa INNER JOIN tipogasto g ON g.id = b.idtipogasto ";
    $query .= "INNER JOIN subtipogasto h ON h.id = a.idsubtipogasto LEFT JOIN detpagopresup i ON a.id = i.iddetpresup LEFT JOIN moneda j ON j.id = i.idmoneda ";
    $query .= "WHERE a.origenprov = 1 AND a.idestatuspresupuesto IN(3) AND i.pagado = 0 ";
    $query .= "UNION ";
    $query .= "SELECT f.ordensumario, a.idpresupuesto, a.id, b.idproyecto, c.nomproyecto AS proyecto, b.fhaprobacion, a.idproveedor, e.nombre AS proveedor, b.idmoneda, d.simbolo AS moneda, a.monto, ";
    $query .= "b.fechasolicitud, f.nomempresa AS empresa, g.desctipogast AS tipogasto, h.descripcion AS subtipogasto, IF(a.coniva = 1, 'I.V.A. incluido', 'I.V.A. NO incluido') AS coniva, a.correlativo, ";
    $query .= "CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, b.idempresa, i.nopago, i.porcentaje, i.idmoneda AS idmonedapago, j.simbolo AS monedapago, i.monto AS valor, i.notas, i.id AS iddetpagopresup, i.pagado, ";
    $query .= "IF(i.pagado = 0, NULL, 'Pagado') AS estatuspagado, b.total, a.tipocambio, a.origenprov, i.isr, i.quitarisr, 0 AS generar, NULL AS idbanco, f.abreviatura AS abreviaempresa ";
    $query .= "FROM detpresupuesto a INNER JOIN presupuesto b ON b.id = a.idpresupuesto INNER JOIN proyecto c ON c.id = b.idproyecto INNER JOIN moneda d ON d.id = a.idmoneda ";
    $query .= "INNER JOIN beneficiario e ON e.id = a.idproveedor INNER JOIN empresa f ON f.id = b.idempresa INNER JOIN tipogasto g ON g.id = b.idtipogasto ";
    $query .= "INNER JOIN subtipogasto h ON h.id = a.idsubtipogasto LEFT JOIN detpagopresup i ON a.id = i.iddetpresup LEFT JOIN moneda j ON j.id = i.idmoneda ";
    $query .= "WHERE a.origenprov = 2 AND a.idestatuspresupuesto IN(3) AND i.pagado = 0 ";
    $query .= "ORDER BY 1, 5, 8, 18, 20";

    $queryEmpresas = "SELECT DISTINCT z.idempresa, z.empresa FROM ($query) z ORDER BY z.ordensumario";

    print json_encode(['empresas' => $db->getQuery($queryEmpresas), 'pagos' => $db->getQuery($query)]);
});

$app->post('/genpagos', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->tipo)) {
        $d->tipo = 'C';
    }
    $db = new dbcpm();

    $cntPagos = count($d->pagos);
    $obj = new stdClass();
    $chqGenerados = '';
    for ($i = 0; $i < $cntPagos; $i++) {
        $pago = $d->pagos[$i];

        $fldCorrela = 'correlativo';
        $getCorrela = "SELECT correlativo FROM banco WHERE id = $pago->idbanco";

        if (strtoupper(trim($d->tipo)) === 'B') {
            $fldCorrela = 'correlativond';
            $getCorrela = "SELECT CONCAT('9999', correlativond) FROM banco WHERE id = $pago->idbanco";
        }

        $query = "SELECT a.idmoneda, b.eslocal FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda WHERE a.id = $pago->idbanco";
        $datosMonedaBanco = $db->getQuery($query)[0];

        $query = "SELECT a.idmoneda, b.tipocambio, c.eslocal FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN moneda c ON c.id = a.idmoneda WHERE a.id = $pago->idpago";
        $datosMonedaOt = $db->getQuery($query)[0];

        $seMultiplica = true;
        $noConvertir = true;

        if ((int)$datosMonedaOt->idmoneda !== (int)$datosMonedaBanco->idmoneda) {
            $noConvertir = false;
            if ((int)$datosMonedaOt->eslocal == 1 && (int)$datosMonedaBanco->eslocal == 0) {
                $seMultiplica = false;
            }
        }

        $query = "SELECT a.monto, a.notas, a.isr, a.quitarisr, b.idproveedor, c.chequesa AS beneficiario, b.origenprov, b.id AS iddetpresup ";
        $query .= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN proveedor c ON c.id = b.idproveedor WHERE a.id = $pago->idpago AND b.origenprov = 1 UNION ";
        $query .= "SELECT a.monto, a.notas, a.isr, a.quitarisr, b.idproveedor, c.nombre AS beneficiario, b.origenprov, b.id AS iddetpresup ";
        $query .= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN beneficiario c ON c.id = b.idproveedor WHERE a.id = $pago->idpago AND b.origenprov = 2";
        $detspago = $db->getQuery($query);
        if (count($detspago) > 0) {
            $detpago = $detspago[0];

            $isrAQuitar = 0.00;
            if ((int)$detpago->quitarisr == 1 && (float)$detpago->isr > 0) {
                $isrAQuitar = $db->calculaISR($db->calculaMontoBase($detpago->isr));
            }

            $monto = 0.00;
            if ($noConvertir) {
                $monto = $detpago->monto - ((int)$datosMonedaOt->eslocal == 1 ? $isrAQuitar : round($isrAQuitar / (float)$datosMonedaOt->tipocambio, 2));
            } else {
                if ($seMultiplica) {
                    $monto = round((float)$detpago->monto * (float)$datosMonedaOt->tipocambio, 2) - $isrAQuitar;
                } else {
                    $monto = round(((float)$detpago->monto / (float)$datosMonedaOt->tipocambio) - ($isrAQuitar / (float)$datosMonedaOt->tipocambio), 2);
                }
            }

            $query = "INSERT INTO tranban(";
            $query .= "idbanco, tipotrans, fecha, monto, beneficiario, concepto, numero, origenbene, idbeneficiario, tipocambio, iddetpresup, iddetpagopresup, anticipo, correlativond";
            $query .= ") VALUES(";
            $query .= "$pago->idbanco, '$d->tipo', '$d->fecha', $monto, '$detpago->beneficiario', ";
            $query .= "'$detpago->notas', ($getCorrela), $detpago->origenprov, $detpago->idproveedor, $datosMonedaOt->tipocambio, $detpago->iddetpresup, $pago->idpago, 1,";
            $query .= strtoupper(trim($d->tipo)) === 'B' ? "(SELECT correlativond FROM banco WHERE id = $pago->idbanco)" : '0';
            $query .= ")";
            $db->doQuery($query);
            $lastid = $db->getLastId();
            if ((int)$lastid > 0) {
                $db->doQuery("UPDATE banco SET $fldCorrela = $fldCorrela + 1 WHERE id = $pago->idbanco");

                $obj->tipocambio = $datosMonedaOt->tipocambio;
                $obj->idbanco = $pago->idbanco;
                $obj->origenbene = $detpago->origenprov;
                $obj->monto = $monto;
                $obj->concepto = $detpago->notas;
                //$url = 'http://localhost/sytdev/php/tranbanc.php/doinsdetcont'; //Desarrollo
                $url = 'http://localhost/sayet/php/tranbanc.php/doinsdetcont'; //Producción
                $obj->anticipo = 1;
                $data = ['obj' => $obj, 'lastid' => $lastid];
                $db->CallJSReportAPI('POST', $url, json_encode($data));

                $query = "UPDATE detpagopresup SET pagado = 1, origen = 1, idorigen = $lastid WHERE id = $pago->idpago";
                $db->doQuery($query);
                if ($chqGenerados !== '') {
                    $chqGenerados .= ', ';
                }
                $chqGenerados .= $db->getOneField("SELECT numero FROM tranban WHERE id = $lastid");
            }
        }
    }
    print json_encode(['segeneraron' => $chqGenerados !== '', 'cheques' => $chqGenerados]);
});

$app->post('/pagosgenerados', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if (!isset($d->fechastr)) {
        $d->fechastr = date('Y-m-d');
    }

    $query = "SELECT a.iddetpresup AS idot FROM tranban a WHERE a.tipotrans IN('C', 'B') AND a.iddetpresup > 0 AND a.fecha = '$d->fechastr' ORDER BY a.id";
    print $db->doSelectASJson($query);
});

$app->get('/notificaciones', function () {
    $db = new dbcpm();
    $query = "SELECT c.id AS idpresupuesto, b.id AS iddetpresupuesto, b.correlativo, a.nopago, b.idproveedor, d.nombre AS proveedor, c.idempresa, f.abreviatura AS empresa, e.simbolo, a.monto, ";
    $query .= "CONCAT('OT: ', c.id, '-', b.correlativo, ', Pago #', a.nopago, ', ', d.nombre, ', ', f.abreviatura, ', ', e.simbolo, ' ', a.monto) AS notificacion, b.origenprov, a.isr, a.quitarisr ";
    $query .= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN presupuesto c ON c.id = b.idpresupuesto INNER JOIN proveedor d ON d.id = b.idproveedor ";
    $query .= "INNER JOIN moneda e ON e.id = b.idmoneda INNER JOIN empresa f ON f.id = c.idempresa ";
    $query .= "WHERE a.notificado = 0 AND a.pagado = 0 AND b.origenprov = 1 AND b.idestatuspresupuesto = 3 ";
    $query .= "UNION ";
    $query .= "SELECT c.id AS idpresupuesto, b.id AS iddetpresupuesto, b.correlativo, a.nopago, b.idproveedor, d.nombre AS proveedor, c.idempresa, f.abreviatura AS empresa, e.simbolo, a.monto, ";
    $query .= "CONCAT('OT: ', c.id, '-', b.correlativo, ', Pago #', a.nopago, ', ', d.nombre, ', ', f.abreviatura, ', ', e.simbolo, ' ', a.monto) AS notificacion, b.origenprov, a.isr, a.quitarisr ";
    $query .= "FROM detpagopresup a INNER JOIN detpresupuesto b ON b.id = a.iddetpresup INNER JOIN presupuesto c ON c.id = b.idpresupuesto INNER JOIN beneficiario d ON d.id = b.idproveedor ";
    $query .= "INNER JOIN moneda e ON e.id = b.idmoneda INNER JOIN empresa f ON f.id = c.idempresa ";
    $query .= "WHERE a.notificado = 0 AND a.pagado = 0 AND b.origenprov = 2 AND b.idestatuspresupuesto = 3 ";
    $query .= "ORDER BY 1, 3, 4, 5, 6";
    print $db->doSelectASJson($query);
});

$app->post('/cdp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->notas = $d->notas == '' ? "NULL" : "'$d->notas'";

    if (!isset($d->isr)) {
        $d->isr = 0.00;
    }
    if (!isset($d->quitarisr)) {
        $d->quitarisr = 0;
    }
    if (!isset($d->idmoneda)) {
        $d->idmoneda = 1;
    }

    $nopago = $db->getOneField("SELECT IF(MAX(nopago) IS NULL, 1, MAX(nopago) + 1) FROM detpagopresup WHERE iddetpresup = $d->iddetpresup");
    $query = "INSERT INTO detpagopresup(";
    $query .= "iddetpresup, nopago, porcentaje, monto, notas, isr, quitarisr, idmoneda";
    $query .= ") VALUES(";
    $query .= "$d->iddetpresup, $nopago, $d->porcentaje, $d->monto, $d->notas, $d->isr, $d->quitarisr, $d->idmoneda";
    $query .= ")";
    $db->doQuery($query);
});

$app->post('/udp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if (!isset($d->isr)) {
        $d->isr = 0.00;
    }
    if (!isset($d->quitarisr)) {
        $d->quitarisr = 0;
    }
    if (!isset($d->idmoneda)) {
        $d->idmoneda = 1;
    }

    $d->notas = $d->notas == '' ? "NULL" : "'$d->notas'";
    $query = "UPDATE detpagopresup SET ";
    $query .= "porcentaje = $d->porcentaje, monto = $d->monto, notas = $d->notas, isr = $d->isr, quitarisr = $d->quitarisr, idmoneda = $d->idmoneda ";
    $query .= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/ddp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM detpagopresup WHERE id = $d->id";
    $db->doQuery($query);
});

$app->get('/setnotificado/:idusr', function ($idusr) {
    $db = new dbcpm();
    $query = "UPDATE detpagopresup SET notificado = 1, fhnotificado = NOW(), idusrnotifica = $idusr WHERE notificado = 0 AND pagado = 0";
    $db->doQuery($query);
});

$app->post('/calcisr', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.idproveedor, a.origenprov, a.coniva, a.tipocambio, b.retensionisr AS retieneisr ";
    $query .= "FROM detpresupuesto a LEFT JOIN proveedor b ON b.id = a.idproveedor ";
    $query .= "WHERE a.id = $d->idot";
    $otq = $db->getQuery($query);
    $isr = 0.00;
    if (count($otq) > 0) {
        $ot = $otq[0];
        if ((int)$ot->origenprov == 1) {
            if ((int)$ot->retieneisr == 1) {
                $subtotal = (int)$ot->coniva == 1 ? round((float)$d->monto / 1.12, 2) : (float)$d->monto;
                $isr = $db->calculaISR($subtotal);
            }
        }
    }

    print json_encode(['isr' => $isr]);
});

// Ampliaciones de presupuesto
$app->get('/ampliapresup/:iddetpresup', function ($iddetpresup) {
    $db = new dbcpm();

    $query = "SELECT id, idpresupuesto, iddetpresupuesto, correlativoamplia, monto, notas, idestatuspresupuesto 
    FROM ampliapresupuesto 
    WHERE iddetpresupuesto = $iddetpresup 
    ORDER BY correlativoamplia ";

    print $db->doSelectASJson($query);
});

$app->get('/getampliapresup/:idamplia', function ($idamplia) {
    $db = new dbcpm();

    $query = "SELECT id, idpresupuesto, iddetpresupuesto, correlativoamplia, monto, notas ";
    $query .= "FROM ampliapresupuesto ";
    $query .= "WHERE id = $idamplia ";
    $query .= "ORDER BY correlativoamplia";

    print $db->doSelectASJson($query);
});

$app->post('/cap', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $notas = trim($d->notas) == '' ? "NULL" : "'$d->notas'";
    $correlativo = (int)$db->getOneField("SELECT IF(ISNULL(MAX(correlativoamplia)), 1, MAX(correlativoamplia) + 1) FROM ampliapresupuesto WHERE iddetpresupuesto = $d->iddetpresupuesto");
    $query = "INSERT INTO ampliapresupuesto(idpresupuesto, iddetpresupuesto, correlativoamplia, monto, notas, idmoneda, tipocambio) VALUES(";
    $query .= "$d->idpresupuesto, $d->iddetpresupuesto, $correlativo, $d->monto, $notas, $d->idmoneda, $d->tipocambio";
    $query .= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/uap', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $notas = trim($d->notas) == '' ? "NULL" : "'$d->notas'";
    $query = "UPDATE ampliapresupuesto SET monto = $d->monto, notas = $notas ";
    $query .= "WHERE id = $d->idamplia";
    $db->doQuery($query);
});
$app->post('/dap', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "DELETE FROM ampliapresupuesto WHERE id = $d->idamplia";
    $db->doQuery($query);
});

//Bitacora de presupuestos

$app->get('/gbp/:origen/:idpresupuesto', function ($origen, $idpresupuesto) {
    $db = new dbcpm();
    $query = "SELECT b.descripcion AS evento, DATE_FORMAT(a.fechahora, '%d/%m/%Y %H:%i:%s') AS fechahora, c.iniciales ";
    $query .= "FROM bitacorapresupuesto a INNER JOIN eventobitapresup b ON a.evento = b.abreviatura INNER JOIN usuario c ON c.id = a.idusuario ";
    $query .= "WHERE origen = $origen AND idpresupuesto = $idpresupuesto ";
    $query .= "ORDER BY a.fechahora";
    print $db->doSelectASJson($query);
});

function insertaBitacoraPresupuesto($db, $d)
{
    $query = "INSERT INTO bitacorapresupuesto(origen, idpresupuesto, evento, idusuario) VALUES(";
    $query .= "$d->origen, $d->idpresupuesto, '$d->evento', $d->idusuario";
    $query .= ")";
    $db->doQuery($query);
}

$app->post('/ibp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    insertaBitacoraPresupuesto($db, $d);
});

$app->get('/lstotadjuntos/:idot(/:multiple)', function ($idot, $multiple) {
    $db = new dbcpm();
    $query = "SELECT id, idot, nomadjunto, ubicacion FROM ot_adjunto WHERE idot = $idot AND esmultiple = $multiple ORDER BY nomadjunto";
    print $db->doSelectASJson($query);
});

$app->post('/aaot', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO ot_adjunto(idot, nomadjunto, ubicacion, esmultiple) VALUES(";
    $query .= "$d->idot,'$d->nomadjunto', '$d->ubicacion', '$d->esmultiple'";
    $query .= ")";
    $db->doQuery($query);
});

$app->post('/daot', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $ubicacion = $db->getOneField("SELECT ubicacion FROM ot_adjunto WHERE id = $d->id");
    if (file_exists('../' . $ubicacion)) {
        unlink('../' . $ubicacion);
    }
    $query = "DELETE FROM ot_adjunto WHERE id = $d->id";
    $db->doQuery($query);
});

$app->get('/pagopencont', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.id, c.abreviatura AS empresa, d.nomproyecto as proyecto, e.nombre AS proveedor, 
            CONCAT(a.idpresupuesto, '-', a.correlativo) AS ot, f.simbolo, a.monto, c.ordensumario, c.id 
            AS idempresa, a.tipocambio, a.notas, a.idproveedor, a.origenprov
            FROM detpresupuesto a
            INNER JOIN presuuesto b ON b.id = a.idpresupuesto
            INNER JOIN empresa c ON c.id = b.idempresa
            INNER JOIN proyecto d ON d.id = b.idproyecto
            INNER JOIN proveedor e ON e.id = a.idproveedor
            INNER JOIN moneda f ON f.id = a.idmoneda
            WHERE a.idestatuspresupuesto = 3 AND a.escontado = 1 AND a.id NOT IN (
                SELECT iddetpresup 
                FROM tranban 
                WHERE tipotrans IN('C', 'B') AND iddetpresup > 0 AND anulado = 0 AND beneficiario NOT LIKE '%anula%' AND concepto NOT LIKE '%anula%') " ; 

    //print $db->doSelectASJson($query);
    $queryEmpresas = "SELECT DISTINCT z.idempresa, z.empresa FROM ($query) z ORDER BY z.ordensumario";
    //print $queryEmpresas;
    print json_encode(['empresas' => $db->getQuery($queryEmpresas), 'pagos' => $db->getQuery($query)]);
});

$app->post('/genpagoscontado', function () {
    $d = json_decode(file_get_contents('php://input'));
    if (!isset($d->tipo)) {
        $d->tipo = 'C';
    }
    $db = new dbcpm();

    $cntPagos = count($d->pagos);
    $obj = new stdClass();
    $chqGenerados = '';
    for ($i = 0; $i < $cntPagos; $i++) {
        $pago = $d->pagos[$i];

        $fldCorrela = 'correlativo';
        $getCorrela = "SELECT correlativo FROM banco WHERE id = $pago->idbanco";

        if (strtoupper(trim($d->tipo)) === 'B') {
            $fldCorrela = 'correlativond';
            $getCorrela = "SELECT CONCAT('9999', correlativond) FROM banco WHERE id = $pago->idbanco";
        }

        $query = "SELECT a.idmoneda, b.eslocal FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda WHERE a.id = $pago->idbanco";
        $datosMonedaBanco = $db->getQuery($query)[0];

        $query = "SELECT b.idmoneda, b.tipocambio, c.eslocal FROM detpresupuesto b INNER JOIN moneda c ON c.id = b.idmoneda WHERE b.id = $pago->idpago";
        $datosMonedaOt = $db->getQuery($query)[0];

        $seMultiplica = true;
        $noConvertir = true;

        if ((int)$datosMonedaOt->idmoneda !== (int)$datosMonedaBanco->idmoneda) {
            $noConvertir = false;
            if ((int)$datosMonedaOt->eslocal == 1 && (int)$datosMonedaBanco->eslocal == 0) {
                $seMultiplica = false;
            }
        }

        $query = "SELECT b.monto, b.notas, c.retensionisr, b.idproveedor, c.chequesa AS beneficiario, b.origenprov, b.id AS iddetpresup
        FROM detpresupuesto b INNER JOIN proveedor c ON c.id = b.idproveedor WHERE b.id = $pago->idpago AND b.origenprov = 1 UNION
        SELECT b.monto, b.notas, a.retensionisr, b.idproveedor, c.nombre AS beneficiario, b.origenprov, b.id AS iddetpresup
        FROM detpresupuesto b INNER JOIN beneficiario c ON c.id = b.idproveedor INNER JOIN proveedor a ON b.id = a.id WHERE b.id = $pago->idpago AND b.origenprov = 2 ";
        $detspago = $db->getQuery($query);
        if (count($detspago) > 0) {
            $detpago = $detspago[0];

            $isrAQuitar = 0.00;
            if ((int)$detpago->retensionisr == 1) {
                $isrAQuitar = $db->calculaISR($db->calculaMontoBase($detpago->monto));
            }

            $monto = 0.00;
            if ($noConvertir) {
                $monto = $detpago->monto - ((int)$datosMonedaOt->eslocal == 1 ? $isrAQuitar : round($isrAQuitar / (float)$datosMonedaOt->tipocambio, 2));
            } else {
                if ($seMultiplica) {
                    $monto = round((float)$detpago->monto * (float)$datosMonedaOt->tipocambio, 2) - $isrAQuitar;
                } else {
                    $monto = round(((float)$detpago->monto / (float)$datosMonedaOt->tipocambio) - ($isrAQuitar / (float)$datosMonedaOt->tipocambio), 2);
                }
            }

            $query = "INSERT INTO tranban(";
            $query .= "idbanco, tipotrans, fecha, monto, beneficiario, concepto, numero, origenbene, idbeneficiario, tipocambio, iddetpresup, anticipo, correlativond";
            $query .= ") VALUES(";
            $query .= "$pago->idbanco, '$d->tipo', '$d->fecha', $monto, '$detpago->beneficiario', ";
            $query .= "'$detpago->notas', ($getCorrela), $detpago->origenprov, $detpago->idproveedor, $datosMonedaOt->tipocambio, $detpago->iddetpresup, 1,";
            $query .= strtoupper(trim($d->tipo)) === 'B' ? "(SELECT correlativond FROM banco WHERE id = $pago->idbanco)" : '0';
            $query .= ")";
            $db->doQuery($query);
            $lastid = $db->getLastId();
            if ((int)$lastid > 0) {
                $db->doQuery("UPDATE banco SET $fldCorrela = $fldCorrela + 1 WHERE id = $pago->idbanco");

                $obj->tipocambio = $datosMonedaOt->tipocambio;
                $obj->idbanco = $pago->idbanco;
                $obj->origenbene = $detpago->origenprov;
                $obj->monto = $monto;
                $obj->concepto = $detpago->notas;
                //$url = 'http://localhost/sytdev/php/tranbanc.php/doinsdetcont'; //Desarrollo
                $url = 'http://localhost/sayet/php/tranbanc.php/doinsdetcont'; //Producción
                $data = ['obj' => $obj, 'lastid' => $lastid];
                $db->CallJSReportAPI('POST', $url, json_encode($data));

                //$query = "UPDATE detpagopresup SET pagado = 1, origen = 1, idorigen = $lastid WHERE id = $pago->idpago";
                //$db->doQuery($query);
                if ($chqGenerados !== '') {
                    $chqGenerados .= ', ';
                }
                $chqGenerados .= $db->getOneField("SELECT numero FROM tranban WHERE id = $lastid");
            }
        }
    }
    print json_encode(['segeneraron' => $chqGenerados !== '', 'cheques' => $chqGenerados]);
});

$app->post('/revap', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE ampliapresupuesto SET idestatuspresupuesto = 2 
    WHERE id = $d->idamplia ";
    $db->doQuery($query);
});

$app->get('/aprobacionamp', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.id, CONCAT(b.idpresupuesto, '-', b.correlativo) AS ot, a.correlativoamplia AS ampliacion, c.iniciales, d.nombre AS proveedor, 
            CONCAT(e.simbolo, a.monto) AS monto, a.notas
            FROM ampliapresupuesto a 
            INNER JOIN detpresupuesto b ON b.id = a.iddetpresupuesto
            INNER JOIN presupuesto f ON f.id = a.idpresupuesto
            INNER JOIN usuario c ON c.id = f.idusuario
            INNER JOIN proveedor d ON d.id = f.idproveedor
            INNER JOIN moneda e ON e.id = a.idmoneda
            WHERE a.idestatuspresupuesto = 2 AND b.origenprov = 1
            UNION 
            SELECT a.id, CONCAT(b.idpresupuesto, '-', b.correlativo) AS ot, a.correlativoamplia AS ampliacion, c.iniciales, d.nombre AS proveedor, 
            CONCAT(e.simbolo, a.monto) AS monto, a.notas
            FROM ampliapresupuesto a 
            INNER JOIN detpresupuesto b ON b.id = a.iddetpresupuesto
            INNER JOIN presupuesto f ON f.id = a.idpresupuesto
            INNER JOIN usuario c ON c.id = f.idusuario
            INNER JOIN beneficiario d ON d.id = f.idproveedor
            INNER JOIN moneda e ON e.id = a.idmoneda
            WHERE a.idestatuspresupuesto = 2 AND b.origenprov = 2;"; 

    print $db->doSelectASJson($query);
});

$app->post('/apamp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE ampliapresupuesto SET idestatuspresupuesto = 3 
    WHERE id = $d->idamplia ";
    $db->doQuery($query);
});

$app->post('/rechamp', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "UPDATE ampliapresupuesto SET idestatuspresupuesto = 4 
    WHERE id = $d->idamplia ";
    $db->doQuery($query);
});

$app->post('/prtaprobacion', function() {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $sisr = array();
    $stran = array();
    $tipocambioprov = 7.8;

    if (!isset($d->notas)) { $d->notas = null; };
    if (!isset($d->tc)) { $d->tc = 1.00; };

    $query = "SELECT 
                a.id,
                IF(b.tipo = 2, 'MULTIPLE', 'SIMPLE') AS tipo,
                DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha,
                CONCAT(a.idpresupuesto, '-', a.correlativo) AS numero,
                c.nomempresa AS empresa,
                IFNULL(d.nombre, e.nombre) AS proveedor,
                IFNULL(d.nit, e.nit) AS nit,
                f.nomproyecto AS proyecto,
                ROUND(a.monto, 2) AS monto,
                a.notas AS concepto,
                GROUP_CONCAT(g.nomadjunto
                    SEPARATOR ', ') AS adjuntos,
                DATE_FORMAT(DATE_ADD(NOW(),
                            INTERVAL IFNULL(d.diascred, 1) DAY),
                        '%d/%m/%Y') AS fpago,
                IF(b.idtipogasto = 1, i.descripcion, NULL) AS cep,
                IF(b.idtipogasto = 5, i.descripcion, NULL) AS af,
                IF(b.idtipogasto = 6, i.descripcion, NULL) AS m,
                h.simbolo AS moneda,
                a.idmoneda
            FROM
                detpresupuesto a
                    INNER JOIN
                presupuesto b ON a.idpresupuesto = b.id
                    INNER JOIN
                empresa c ON b.idempresa = c.id
                    LEFT JOIN
                proveedor d ON a.idproveedor = d.id
                    AND a.origenprov = 1
                    LEFT JOIN
                beneficiario e ON a.idproveedor = e.id
                    AND a.origenprov = 2
                    INNER JOIN 
                proyecto f ON b.idproyecto = f.id
                    LEFT JOIN
                ot_adjunto g ON g.idot = a.id
                    INNER JOIN 
                moneda h ON a.idmoneda = h.id
                    INNER JOIN
                subtipogasto i ON a.idsubtipogasto = i.id
            WHERE
                a.idpresupuesto = $d->idpresupuesto
                AND a.correlativo = $d->correlativo";
    $datos = $db->getQuery($query)[0];

    // traer monto, moneda, idordentrabajo y tipocambio de compra
    $query = "SELECT id, totfact, idmoneda, tipocambio, isr, ordentrabajo AS ot FROM compra WHERE ordentrabajo = $datos->id AND idreembolso = 0 
    AND id NOT IN(SELECT idcompra FROM detnotacompra) AND idtipofactura < 8
    UNION ALL SELECT b.id, b.totfact, b.idmoneda, b.tipocambio, b.isr, a.ordentrabajo AS ot FROM reembolso a 
    INNER JOIN compra b ON b.idreembolso = a.id WHERE a.ordentrabajo = $datos->id";
    $tcompras = $db->getQuery($query);

    $cntCompras = count($tcompras);

    // traer monto y tipocambio de transaccion bancaria
    $query = "SELECT a.monto * IF(a.tipotrans = 'R', -1, 1) AS monto, a.tipocambio, b.idmoneda, a.iddetpresup AS ot FROM tranban a INNER JOIN banco b ON a.idbanco = b.id 
    WHERE a.iddetpresup = $datos->id AND (SELECT COUNT(b.id) FROM doctotranban b WHERE b.idtranban = a.id AND b.idtipodoc = 2) = 0 
    AND a.liquidado = 0 AND a.iddocliquida = 0 AND a.anulado = 0 
    UNION ALL 
    SELECT b.monto, a.tipocambio, c.idmoneda, a.iddetpresup AS ot FROM tranban a INNER JOIN dettranreem b ON b.idtranban = a.id 
    INNER JOIN banco c ON a.idbanco = c.id WHERE a.iddetpresup = $datos->id";
    $trans = $db->getQuery($query);

    $cntTranas = count($trans);

    for ($j = 0; $j < $cntCompras; $j++) {
        $compra = $tcompras[$j];
        $tc = $compra->tipocambio > 1 ? $compra->tipocambio : $tipocambioprov;

        if ($datos->idmoneda != $compra->idmoneda) {
            if ($datos->idmoneda == 1) {
                $montoisr = $compra->isr * $tc;
            } else {
                $montoisr = $compra->isr / $tc;
            }
        } else {
            $montoisr = $compra->isr;
        }
        array_push($sisr, $montoisr);
    }
    // sumas compras
    $tisr = array_sum($sisr);

    // loop transacciones bancarias
    for ($j = 0; $j < $cntTranas; $j++) {
        $tran = $trans[$j];
        $tc = $tran->tipocambio > 1 ? $tran->tipocambio : $tipocambioprov;

        if ($datos->idmoneda !== $tran->idmoneda) {
            if ($datos->idmoneda == 1) {
                $monto = $tran->monto * $tc;
            } else {
                $monto = $tran->monto / $tc;
            }
        } else {
            $monto = $tran->monto;
        }
        array_push($stran, $monto);
    }
    // sumas transacciones bancarias
    $ttran = array_sum($stran);

    $gastado = $ttran + $tisr;

    $avance = ROUND(($gastado * 100) / $datos->monto, 2);

    $datos->anticipo = $d->monto;

    $afectar = 0.00;

    if ($datos->idmoneda == 1) {
        $afectar = $d->idmoneda == 1 ? $d->monto : $d->monto * $d->tc;
    } else {
        $afectar = $d->idmoneda == 1 ? $d->motno / $d->tc : $d->monto;
    }

    $datos->avance = round(($afectar * 100) / $datos->monto + $avance, 2); 
    
    $datos->moneda_ant = $d->idmoneda == 1 ? 'Q' : '$';

    $datos->observaciones = $d->notas;

    print json_encode([ 'datos' => $datos ]);
});

$app->run();