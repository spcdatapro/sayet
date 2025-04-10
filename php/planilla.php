<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/empresas', function() use($db)
{
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT DISTINCT a.idempresa, b.nomempresa AS empresa, b.ndplanilla, NULL as idbanco ";
    $query.= "FROM plnnomina a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN plnempleado c ON c.id = a.idplnempleado ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND c.mediopago = $d->mediopago ORDER BY b.ordensumario";
    $empresas = $db->getQuery($query);
    $cntEmpresas = count($empresas);
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $empresas[$i];

        $existe = $db->getQuery("SELECT a.id AS idtranban, b.id AS idbanco, a.numero FROM tranban a INNER JOIN banco b ON a.idbanco = b.id 
        WHERE a.fechaplanilla = '$d->falstr' AND esplanilla = 1 AND b.idempresa = $empresa->idempresa AND a.tipotrans = 'B' AND a.liquidado = 0");

        $query = "SELECT a.id, b.id AS idcuentac, CONCAT('(', b.codigo, ') ', b.nombrecta) AS nombrecta, ";
        $query.= "a.nombre, a.nocuenta, a.siglas, a.nomcuenta, a.idmoneda, CONCAT(c.nommoneda,' (',c.simbolo,')') AS descmoneda, ";
        $query.= "CONCAT(a.nombre, ' (', c.simbolo,')') AS bancomoneda, a.correlativo, c.tipocambio, CONCAT(a.nombre, ' (', c.simbolo,') (Sigue el No. ', a.correlativo,')') AS bancomonedacorrela, ";
        $query.= "a.idtipoimpresion, d.descripcion AS tipoimpresion, d.formato, c.eslocal AS monedalocal, a.debaja ";
        $query.= "FROM banco a INNER JOIN cuentac b ON b.id = a.idcuentac ";
        $query.= "INNER JOIN moneda c ON c.id = a.idmoneda ";
        $query.= "LEFT JOIN tipoimpresioncheque d ON d.id = a.idtipoimpresion ";
        $query.= "WHERE a.idempresa = ".$empresa->idempresa." ORDER BY a.nombre";
        $empresa->bancos = $db->getQuery($query);
        $empresa->existe = isset($existe[0]) ? $existe[0] : 0;
    }
    print json_encode($empresas);
});

$app->post('/generado', function() use($db)
{
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT COUNT(*) FROM tranban WHERE tipotrans = '$d->tipo' AND esplanilla = 1 AND fechaplanilla = '$d->falstr' AND anulado = 0";
    $generado = (int)$db->getOneField($query) > 0;
    print json_encode(['generado' => ($generado ? 1: 0)]);
});

$app->post('/anular_bitacora', function () {
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    $idempleado = $d->idplnempleado;

    if ($d->revertir) {
        $query = "SELECT idempresaactual, idempresadebito, sueldo, bonificacionley, reingreso, baja, porcentajeigss, descuentoisr, idproyecto, idcuenta FROM plnbitacora WHERE id = $d->id";
        $datos = $db->getQuery($query)[0];

        $idlaboral = $db->getOneField("SELECT idlaboral FROM plnempleado WHERE id = $idempleado");

        $datos->reingreso = $datos->reingreso !== null ? $datos->reingreso : 0;
        $datos->baja = isset($datos->baja) ? $datos->baja : 'null';

        $str = "UPDATE plnlaboral SET ";
        $cambios = [
            'idempresaactual' => $datos->idempresaactual,
            'idempresadebito' => $datos->idempresadebito,
            'sueldo' => $datos->sueldo,
            'bonificacionley' => $datos->bonificacionley,
            'reingreso' => $datos->reingreso,
            'baja' => $datos->baja,
            'porcentajeigss' => $datos->porcentajeigss,
            'descuentoisr' => $datos->descuentoisr,
            'idproyecto' => $datos->idproyecto,
            'idcuenta' => $datos->idcuenta
        ];
        
        foreach ($cambios as $cambio => $valor) {
            if ($valor > 0 || $cambio == 'baja') {
                $str .= "$cambio = '$valor', ";
            }
        }
        
        $str = rtrim($str, ", ");
        $str .= " WHERE id = $idlaboral";

        $db->doQuery($str);

        if ($d->idplnmovimiento == 3) {
            $finiquito = $db->getQuery("SELECT id, idprestamos, fecha FROM plnfiniquito WHERE idplnempleado = $idempleado AND pendiente = 1")[0];
            $idprestamos = isset($finiquito->idprestamos) ? explode(',', $finiquito->idprestamos) : null;

            foreach ($idprestamos as $id) {
                $monto = $db->getOneField("SELECT monto FROM plnpresabono WHERE idplnprestamo = $id AND fecha = '$finiquito->fecha'");
                $db->doQuery("UPDATE plnprestamo SET saldo = $monto, finalizado = 0, liquidacion = null WHERE id = $id");
                // ver prestamos 
                $db->doQuery("DELETE FROM plnpresabono WHERE idplnprestamo = $id AND fecha = '$finiquito->fecha'");
                $db->doQuery("DELETE FROM plnarchivo WHERE DATE_FORMAT(fecha, '%Y-%m-%d') = '$finiquito->fecha' AND idplnarchivotipo = 3 AND idplnempleado = $idempleado");
            }

            $db->doQuery("DELETE FROM plnfiniquito WHERE id = $finiquito->id");
        }
        $db->doQuery("UPDATE plnbitacora SET mostrar = 0 WHERE id = $d->id");
        $db->doQuery("UPDATE plnempleado SET baja = $datos->baja WHERE id = $idempleado");
    } else {
        $db->doQuery("UPDATE plnbitacora SET mostrar = 0 WHERE id = $d->id");
    }

    $exito = $db->getOneField("SELECT mostrar FROM plnbitacora WHERE id = $d->id");

    if ($exito == 0) {
        $mensaje = "Bitacora eliminada con exito"; 
        $tipo = "success";
        $empleado = $idempleado;
    } else {
        $mensaje = "Error al eliminar bitacora, favor revisar.";
        $tipo = "error";
        $empleado = $idempleado;
    }

    print json_encode(["mensaje" => $mensaje, "tipo" => $tipo, "empleado" => $idempleado]);
});

$app->get('/finiquitos', function () {
    $db = new dbcpm();

    $query = "SELECT 
                a.id,
                a.fecha AS fecha,
                b.id AS idempleado,
                CONCAT(b.nombre, ' ', IFNULL(b.apellidos, 0)) AS empleado,
                a.finiquito,
                a.vacaciones,
                a.aguinaldo,
                a.bono,
                a.ordinario,
                a.extra,
                a.otrosbono,
                a.prestamos,
                a.anticipos,
                a.otrosdesc,
                a.pendiente,
                a.idempresa,
                a.idproyecto,
                c.nombre AS empresa,
                IFNULL(d.nomproyecto, 'N/E, NO GENERARÁ DETALLE EN REP. ING. EGRE.') AS proyecto,
                a.concepto
            FROM
                plnfiniquito a
                    INNER JOIN
                plnempleado b ON a.idplnempleado = b.id
                    INNER JOIN
                plnempresa c ON a.idempresa =  c.id
                    LEFT JOIN 
                proyecto d ON a.idproyecto = d.id
            WHERE
                pendiente = 1";
    $pendientes = $db->getQuery($query);

    print json_encode($pendientes);
});

$app->run();