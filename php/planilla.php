<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/empresas', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT DISTINCT a.idempresa, b.nomempresa AS empresa, b.ndplanilla, NULL as idbanco ";
    $query.= "FROM plnnomina a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN plnempleado c ON c.id = a.idplnempleado ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND c.mediopago = $d->mediopago ORDER BY b.ordensumario";
    $empresas = $db->getQuery($query);
    $cntEmpresas = count($empresas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $empresas[$i];
        $query = "SELECT a.id, b.id AS idcuentac, CONCAT('(', b.codigo, ') ', b.nombrecta) AS nombrecta, ";
        $query.= "a.nombre, a.nocuenta, a.siglas, a.nomcuenta, a.idmoneda, CONCAT(c.nommoneda,' (',c.simbolo,')') AS descmoneda, ";
        $query.= "CONCAT(a.nombre, ' (', c.simbolo,')') AS bancomoneda, a.correlativo, c.tipocambio, CONCAT(a.nombre, ' (', c.simbolo,') (Sigue el No. ', a.correlativo,')') AS bancomonedacorrela, ";
        $query.= "a.idtipoimpresion, d.descripcion AS tipoimpresion, d.formato, c.eslocal AS monedalocal, a.debaja ";
        $query.= "FROM banco a INNER JOIN cuentac b ON b.id = a.idcuentac ";
        $query.= "INNER JOIN moneda c ON c.id = a.idmoneda ";
        $query.= "LEFT JOIN tipoimpresioncheque d ON d.id = a.idtipoimpresion ";
        $query.= "WHERE a.idempresa = ".$empresa->idempresa." ORDER BY a.nombre";
        $empresa->bancos = $db->getQuery($query);
    }
    print json_encode($empresas);
});

$app->post('/generado', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT COUNT(*) FROM tranban WHERE tipotrans = '$d->tipo' AND esplanilla = 1 AND fechaplanilla = '$d->falstr' AND anulado = 0";
    $generado = (int)$db->getOneField($query) > 0;
    print json_encode(['generado' => ($generado ? 1: 0)]);
});

$app->post('/anular_bitacora', function () {
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    $antes = json_decode($d->antes);
    $antes = get_object_vars($antes);
    unset($antes['id']);
    $antes["ultimo"] = " WHERE id = $d->idplnempleado"; 
    $str = "UPDATE plnempleado SET";
    // print_r($antes); return;
    foreach ($antes as $a => $valor) {
        if ($a == 'ultimo') {
            $str.= $valor;
        } else if ($a == 'idunidad') {
            $str .= isset($valor) ? " $a = '$valor'" : $a = " $a = null";
        } else {
            $str .= isset($valor) ? " $a = '$valor'," : $a = " $a = null,";
        }
    }
    $fecha = new DateTime($d->fecha);
    $fecha = $fecha->format('Y-m-d');
    $db->doQuery("$str");
    $db->doQuery("UPDATE plnbitacora SET mostrar = 0 WHERE id = $d->id");
    if ($d->idplnmovimiento == 3) {
        $query = "SELECT GROUP_CONCAT(a.id) AS abonos, a.idplnprestamo AS id, SUM(a.monto) AS monto FROM plnpresabono a INNER JOIN plnprestamo b ON a.idplnprestamo = b.id 
        WHERE a.fecha = '$fecha' AND b.idplnempleado = $d->idplnempleado GROUP BY a.idplnprestamo";
        $prestamos = $db->getQuery($query);
        
        if (count($prestamos) > 0) {
            foreach ($prestamos AS $p) {
                $db->doQuery("DELETE FROM plnpresabono WHERE id IN($p->abonos)"); 
                $db->doQuery("UPDATE plnprestamo SET saldo = $p->monto, finalizado = 0, liquidacion = null WHERE id = $p->id");
            }
        }

        $db->doQuery("DELETE FROM plnarchivo WHERE DATE_FORMAT(fecha, '%Y-%m-%d') = '$fecha' AND idplnarchivotipo = 3 AND idplnempleado = $d->idplnempleado");
    }
});

$app->run();