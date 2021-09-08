<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/alquileres', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->verinactivos)) { $d->verinactivos = 0; }
    if(!isset($d->solofacturados)) { $d->solofacturados = 0; }

    $obyAlfa = (int)$d->porlocal == 0;

    /*
    $query = "SELECT DISTINCT b.idempresa, c.nomempresa ";
    $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE a.anulado = 0 AND (";
    $query.= "(b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
    $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')";
    $query.= ") ";
    */
    $query = "SELECT DISTINCT b.idempresa, c.nomempresa, 0.00 AS montosindescuento, 0.00 AS descuento, 0.00 AS monto, ";
    $query.= "0.00 AS montosindescuentodol, 0.00 AS descuentodol, 0.00 AS montodol ";
    $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE a.anulado = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' ";
    $query.= (int)$d->verinactivos == 0 ? "AND (b.inactivo = 0 OR (b.inactivo = 1 AND b.fechainactivo > '$d->falstr')) " : '';
    $query.= (int)$d->solofacturados == 0 ? '' : "AND a.facturado = 1 ";

	
	if (isset($d->empresa) && count($d->empresa)>0) {
		$query.= " and b.idempresa in (" . implode(",", $d->empresa) . ") ";
	}

    $query.= "ORDER BY c.nomempresa";
    $alquileres = $db->getQuery($query);

    $cntAlqui = count($alquileres);
    for($i = 0; $i < $cntAlqui; $i++){
        $alquiler = $alquileres[$i];
        $query = "SELECT DISTINCT b.idproyecto, c.nomproyecto, 0.00 AS montosindescuento, 0.00 AS descuento, 0.00 AS monto, ";
        $query.= "0.00 AS montosindescuentodol, 0.00 AS descuentodol, 0.00 AS montodol ";
        $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN proyecto c ON c.id = b.idproyecto ";
        $query.= "INNER JOIN (SELECT y.id, z.nombre AS unidad FROM unidad z, contrato y WHERE IF(y.inactivo = 0, FIND_IN_SET(z.id, y.idunidad), FIND_IN_SET(z.id, y.idunidadbck))) d ON b.id = d.id ";
        $query.= "WHERE a.anulado = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.idempresa = $alquiler->idempresa ";
        $query.= (int)$d->categoria != NULL ? "AND b.catclie = $d->categoria " : '';
        $query.= (int)$d->verinactivos == 0 ? "AND (b.inactivo = 0 OR (b.inactivo = 1 AND b.fechainactivo > '$d->falstr')) " : '';
        $query.= (int)$d->solofacturados == 0 ? '' : "AND a.facturado = 1 ";
		
		if (isset($d->proyecto) && count($d->proyecto)>0) {
			$query.= " and b.idproyecto in (" . implode(",", $d->proyecto) . ") ";
		}
	
        $query.= "ORDER BY ".($obyAlfa ? "c.nomproyecto" : "CAST(digits(d.unidad) AS unsigned), d.unidad");
        $alquiler->proyectos = $db->getQuery($query);
        $cntProy = count($alquiler->proyectos);
        for($j = 0; $j < $cntProy; $j++){
            $proyecto = $alquiler->proyectos[$j];
            $query = "SELECT DISTINCT b.idcliente, c.nombre, c.nombrecorto, 0.00 AS montosindescuento, 0.00 AS descuento, 0.00 AS monto, ";
            $query.= "0.00 AS montosindescuentodol, 0.00 AS descuentodol, 0.00 AS montodol, e.nombre AS catclie ";
            $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN cliente c ON c.id = b.idcliente ";
            $query.= "INNER JOIN (SELECT y.id, z.nombre AS unidad FROM unidad z, contrato y WHERE IF(y.inactivo = 0, FIND_IN_SET(z.id, y.idunidad), FIND_IN_SET(z.id, y.idunidadbck))) d ON b.id = d.id ";
            $query.= "LEFT JOIN catclie e ON b.catclie = e.id ";
            $query.= "WHERE a.anulado = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND ";
            $query.= "b.idempresa = $alquiler->idempresa AND b.idproyecto = $proyecto->idproyecto ";
            $query.= (int)$d->verinactivos == 0 ? "AND (b.inactivo = 0 OR (b.inactivo = 1 AND b.fechainactivo > '$d->falstr')) " : '';
            $query.= (int)$d->solofacturados == 0 ? '' : "AND a.facturado = 1 ";
            $query.= "ORDER BY ".($obyAlfa ? "c.nombre" : "CAST(digits(d.unidad) AS unsigned), d.unidad");
            $proyecto->clientes = $db->getQuery($query);
            $cntCli = count($proyecto->clientes);
            for($k = 0; $k < $cntCli; $k++){
                $cliente = $proyecto->clientes[$k];
                $query = "SELECT b.id AS idcontrato, UnidadesPorContrato(b.id) AS unidades, (a.monto - a.descuento) AS monto, b.fechainicia, b.fechavence, z.idtipoventa, y.desctiposervventa AS servicio, a.fechacobro, x.simbolo, ";
                $query.= "a.monto AS montosindescuento, a.descuento, x.eslocal AS eslocal, e.nombre AS catclie ";
                $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN detfactcontrato z ON z.id = a.iddetcont INNER JOIN tiposervicioventa y ON y.id = z.idtipoventa ";
                $query.= "INNER JOIN moneda x ON x.id = z.idmoneda LEFT JOIN catclie e ON b.catclie = e.id ";
                $query.= "WHERE a.anulado = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND ";
                $query.= "b.idempresa = $alquiler->idempresa AND b.idproyecto = $proyecto->idproyecto ";
                $query.= (int)$d->verinactivos == 0 ? "AND (b.inactivo = 0 OR (b.inactivo = 1 AND b.fechainactivo > '$d->falstr')) " : '';
                $query.= (int)$d->solofacturados == 0 ? '' : "AND a.facturado = 1 ";
				
				if (isset($d->tipo) && count($d->tipo)>0) {
                    $query.= " AND z.idtipoventa in (" . implode(",", $d->tipo) . ") ";
                }
				
                $query.= "AND b.idcliente = $cliente->idcliente AND a.fechacobro = (";
                $query.= "SELECT MIN(c.fechacobro) FROM cargo c	INNER JOIN contrato d ON d.id = c.idcontrato INNER JOIN detfactcontrato e ON e.id = c.iddetcont	";
                $query.= "WHERE c.anulado = 0 AND c.fechacobro >= '$d->fdelstr' AND c.fechacobro <= '$d->falstr' AND ";
                $query.= "d.idempresa = $alquiler->idempresa AND d.idproyecto = $proyecto->idproyecto AND ";
                $query.= "d.idcliente = $cliente->idcliente AND d.id = b.id AND e.idtipoventa = z.idtipoventa ";
                $query.= (int)$d->verinactivos == 0 ? "AND (d.inactivo = 0 OR (d.inactivo = 1 AND d.fechainactivo > '$d->falstr')) " : '';
                $query.= (int)$d->solofacturados == 0 ? '' : "AND c.facturado = 1 ";
                $query.= ") ";
                $query.= "ORDER BY CAST(digits(UnidadesPorContrato(b.id)) AS UNSIGNED), 2, y.desctiposervventa";
                $cliente->contratos = $db->getQuery($query);
                if (count($cliente->contratos) > 0) {
                    foreach($cliente->contratos as $contrato) {
                        if ((int)$contrato->eslocal === 1) {
                            $cliente->montosindescuento += (float)$contrato->montosindescuento;                        
                            $cliente->descuento += (float)$contrato->descuento;
                            $cliente->monto += (float)$contrato->monto;
                        } else {
                            $cliente->montosindescuentodol += (float)$contrato->montosindescuento;                        
                            $cliente->descuentodol += (float)$contrato->descuento;
                            $cliente->montodol += (float)$contrato->monto;
                        }                        
                    }
                }
            }
            if ($cntCli > 0) {
                foreach($proyecto->clientes as $cli) {
                    $proyecto->montosindescuento += (float)$cli->montosindescuento;
                    $proyecto->descuento += (float)$cli->descuento;
                    $proyecto->monto += (float)$cli->monto;
                    $proyecto->montosindescuentodol += (float)$cli->montosindescuentodol;
                    $proyecto->descuentodol += (float)$cli->descuentodol;
                    $proyecto->montodol += (float)$cli->montodol;
                }
            }
        }
        if ($cntProy > 0) {
            foreach($alquiler->proyectos as $proy) {
                $alquiler->montosindescuento += (float)$proy->montosindescuento;
                $alquiler->descuento += (float)$proy->descuento;
                $alquiler->monto += (float)$proy->monto;
                $alquiler->montosindescuentodol += (float)$proy->montosindescuentodol;
                $alquiler->descuentodol += (float)$proy->descuentodol;
                $alquiler->montodol += (float)$proy->montodol;
            }
        }
        $alquiler->montosindescuento = number_format((float)$alquiler->montosindescuento, 2);
        $alquiler->descuento = number_format((float)$alquiler->descuento, 2);
        $alquiler->monto = number_format((float)$alquiler->monto, 2);
        $alquiler->montosindescuentodol = number_format((float)$alquiler->montosindescuentodol, 2);
        $alquiler->descuentodol = number_format((float)$alquiler->descuentodol, 2);
        $alquiler->montodol = number_format((float)$alquiler->montodol, 2);
    }   

    print json_encode($alquileres);
});

$app->post('/sinproy', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    if(!isset($d->verinactivos)) { $d->verinactivos = 0; }

    $query = "SELECT DISTINCT b.idempresa, c.nomempresa ";
    $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE a.anulado = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' ";
    $query.= (int)$d->verinactivos == 0 ? "AND (b.inactivo = 0 OR (b.inactivo = 1 AND b.fechainactivo > '$d->falstr')) " : '';
	
	if (isset($d->empresa) && count($d->empresa)>0) {
		$query.= " and b.idempresa in (" . implode(",", $d->empresa) . ") ";
	}
	
    $query.= "ORDER BY c.ordensumario";
    $alquileres = $db->getQuery($query);

    $cntAlqui = count($alquileres);
    for($i = 0; $i < $cntAlqui; $i++){
        $alquiler = $alquileres[$i];

        $query = "SELECT DISTINCT b.idcliente, c.nombre, c.nombrecorto ";
        $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN cliente c ON c.id = b.idcliente ";
        $query.= "INNER JOIN (SELECT y.id, z.nombre AS unidad FROM unidad z, contrato y WHERE IF(y.inactivo = 0, FIND_IN_SET(z.id, y.idunidad), FIND_IN_SET(z.id, y.idunidadbck))) d ON b.id = d.id ";
        $query.= "WHERE a.anulado = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.idempresa = $alquiler->idempresa ";
        $query.= (int)$d->verinactivos == 0 ? "AND (b.inactivo = 0 OR (b.inactivo = 1 AND b.fechainactivo > '$d->falstr')) " : '';
        $query.= "ORDER BY c.nombre";
        $alquiler->clientes = $db->getQuery($query);
        $cntCli = count($alquiler->clientes);
        for($k = 0; $k < $cntCli; $k++){
            $cliente = $alquiler->clientes[$k];
            $query = "SELECT b.id AS idcontrato, UnidadesPorContrato(b.id) AS unidades, (a.monto - a.descuento) AS monto, b.fechainicia, b.fechavence, z.idtipoventa, y.desctiposervventa AS servicio, a.fechacobro, x.simbolo ";
            $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN detfactcontrato z ON z.id = a.iddetcont INNER JOIN tiposervicioventa y ON y.id = z.idtipoventa ";
            $query.= "INNER JOIN moneda x ON x.id = z.idmoneda ";
            $query.= "WHERE a.anulado = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.idempresa = $alquiler->idempresa ";
            $query.= (int)$d->verinactivos == 0 ? "AND (b.inactivo = 0 OR (b.inactivo = 1 AND b.fechainactivo > '$d->falstr')) " : '';
			
			if (isset($d->tipo) && count($d->tipo)>0) {
				$query.= " AND z.idtipoventa in (" . implode(",", $d->tipo) . ") ";
			}
			
            $query.= "AND b.idcliente = $cliente->idcliente AND a.fechacobro = (";
            $query.= "SELECT MIN(c.fechacobro) FROM cargo c	INNER JOIN contrato d ON d.id = c.idcontrato INNER JOIN detfactcontrato e ON e.id = c.iddetcont	";
            $query.= "WHERE c.anulado = 0 AND c.fechacobro >= '$d->fdelstr' AND c.fechacobro <= '$d->falstr' AND ";
            $query.= "d.idempresa = $alquiler->idempresa AND d.idcliente = $cliente->idcliente AND d.id = b.id AND e.idtipoventa = z.idtipoventa ";
            $query.= (int)$d->verinactivos == 0 ? "AND (d.inactivo = 0 OR (d.inactivo = 1 AND d.fechainactivo > '$d->falstr')) " : '';
            $query.= ") ";
            $query.= "ORDER BY CAST(digits(UnidadesPorContrato(b.id)) AS UNSIGNED), 2, y.desctiposervventa";
            $cliente->contratos = $db->getQuery($query);
        }
    }

    print json_encode($alquileres);
});

$app->run();