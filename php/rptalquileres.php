<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/alquileres', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $obyAlfa = (int)$d->porlocal == 0;

    $query = "SELECT DISTINCT b.idempresa, c.nomempresa ";
    $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE ((b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
    $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')) ";
	
	if (isset($d->empresa) && count($d->empresa)>0) {
		$query.= " and b.idempresa in (" . implode(",", $d->empresa) . ") ";
	}
	
    $query.= "ORDER BY c.nomempresa";
    $alquileres = $db->getQuery($query);

    $cntAlqui = count($alquileres);
    for($i = 0; $i < $cntAlqui; $i++){
        $alquiler = $alquileres[$i];
        $query = "SELECT DISTINCT b.idproyecto, c.nomproyecto ";
        $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN proyecto c ON c.id = b.idproyecto ";
        $query.= "INNER JOIN (SELECT y.id, z.nombre AS unidad FROM unidad z, contrato y WHERE FIND_IN_SET(z.id, y.idunidad)) d ON b.id = d.id ";
        $query.= "WHERE ((b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
        $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')) AND ";
        $query.= "b.idempresa = $alquiler->idempresa ";
		
		if (isset($d->proyecto) && count($d->proyecto)>0) {
			$query.= " and b.idproyecto in (" . implode(",", $d->proyecto) . ") ";
		}
	
        $query.= "ORDER BY ".($obyAlfa ? "c.nomproyecto" : "CAST(digits(d.unidad) AS unsigned), d.unidad");
        $alquiler->proyectos = $db->getQuery($query);
        $cntProy = count($alquiler->proyectos);
        for($j = 0; $j < $cntProy; $j++){
            $proyecto = $alquiler->proyectos[$j];
            $query = "SELECT DISTINCT b.idcliente, c.nombre, c.nombrecorto ";
            $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN cliente c ON c.id = b.idcliente ";
            $query.= "INNER JOIN (SELECT y.id, z.nombre AS unidad FROM unidad z, contrato y WHERE FIND_IN_SET(z.id, y.idunidad)) d ON b.id = d.id ";
            $query.= "WHERE ((b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
            $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')) AND ";
            $query.= "b.idempresa = $alquiler->idempresa AND b.idproyecto = $proyecto->idproyecto ";
            $query.= "ORDER BY ".($obyAlfa ? "c.nombre" : "CAST(digits(d.unidad) AS unsigned), d.unidad");
            $proyecto->clientes = $db->getQuery($query);
            $cntCli = count($proyecto->clientes);
            for($k = 0; $k < $cntCli; $k++){
                $cliente = $proyecto->clientes[$k];
                $query = "SELECT b.id AS idcontrato, UnidadesPorContrato(b.id) AS unidades, (a.monto - a.descuento) AS monto, b.fechainicia, b.fechavence, z.idtipoventa, y.desctiposervventa AS servicio, a.fechacobro, x.simbolo ";
                $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN detfactcontrato z ON z.id = a.iddetcont INNER JOIN tiposervicioventa y ON y.id = z.idtipoventa ";
                $query.= "INNER JOIN moneda x ON x.id = z.idmoneda ";
                $query.= "WHERE ((b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
                $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')) AND ";
                $query.= "b.idempresa = $alquiler->idempresa AND b.idproyecto = $proyecto->idproyecto ";
				
				if (isset($d->tipo) && count($d->tipo)>0) {
                    $query.= " AND z.idtipoventa in (" . implode(",", $d->tipo) . ") ";
                }
				
                $query.= "AND b.idcliente = $cliente->idcliente AND a.fechacobro = (";
                $query.= "SELECT MIN(c.fechacobro) FROM cargo c	INNER JOIN contrato d ON d.id = c.idcontrato INNER JOIN detfactcontrato e ON e.id = c.iddetcont	";
                $query.= "WHERE ((d.inactivo = 0 AND c.fechacobro >= '$d->fdelstr' AND c.fechacobro <= '$d->falstr') OR ";
                $query.= "(d.inactivo = 1 AND c.fechacobro >= '$d->fdelstr' AND c.fechacobro <= '$d->falstr' AND d.fechainactivo > '$d->falstr')) AND ";
                $query.= "d.idempresa = $alquiler->idempresa AND d.idproyecto = $proyecto->idproyecto AND ";
                $query.= "d.idcliente = $cliente->idcliente AND d.id = b.id AND e.idtipoventa = z.idtipoventa) ";
                $query.= "ORDER BY CAST(digits(UnidadesPorContrato(b.id)) AS UNSIGNED), 2, y.desctiposervventa";
                $cliente->contratos = $db->getQuery($query);
            }
        }
    }

    print json_encode($alquileres);
});

$app->post('/sinproy', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT DISTINCT b.idempresa, c.nomempresa ";
    $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE ((b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
    $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')) ";
	
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
        $query.= "INNER JOIN (SELECT y.id, z.nombre AS unidad FROM unidad z, contrato y WHERE FIND_IN_SET(z.id, y.idunidad)) d ON b.id = d.id ";
        $query.= "WHERE ((b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
        $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')) AND ";
        $query.= "b.idempresa = $alquiler->idempresa ";
        $query.= "ORDER BY c.nombre";
        $alquiler->clientes = $db->getQuery($query);
        $cntCli = count($alquiler->clientes);
        for($k = 0; $k < $cntCli; $k++){
            $cliente = $alquiler->clientes[$k];
            $query = "SELECT b.id AS idcontrato, UnidadesPorContrato(b.id) AS unidades, (a.monto - a.descuento) AS monto, b.fechainicia, b.fechavence, z.idtipoventa, y.desctiposervventa AS servicio, a.fechacobro, x.simbolo ";
            $query.= "FROM cargo a INNER JOIN contrato b ON b.id = a.idcontrato INNER JOIN detfactcontrato z ON z.id = a.iddetcont INNER JOIN tiposervicioventa y ON y.id = z.idtipoventa ";
            $query.= "INNER JOIN moneda x ON x.id = z.idmoneda ";
            $query.= "WHERE ((b.inactivo = 0 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr') OR ";
            $query.= "(b.inactivo = 1 AND a.fechacobro >= '$d->fdelstr' AND a.fechacobro <= '$d->falstr' AND b.fechainactivo > '$d->falstr')) AND ";
            $query.= "b.idempresa = $alquiler->idempresa ";
			
			if (isset($d->tipo) && count($d->tipo)>0) {
				$query.= " AND z.idtipoventa in (" . implode(",", $d->tipo) . ") ";
			}
			
            $query.= "AND b.idcliente = $cliente->idcliente AND a.fechacobro = (";
            $query.= "SELECT MIN(c.fechacobro) FROM cargo c	INNER JOIN contrato d ON d.id = c.idcontrato INNER JOIN detfactcontrato e ON e.id = c.iddetcont	";
            $query.= "WHERE ((d.inactivo = 0 AND c.fechacobro >= '$d->fdelstr' AND c.fechacobro <= '$d->falstr') OR ";
            $query.= "(d.inactivo = 1 AND c.fechacobro >= '$d->fdelstr' AND c.fechacobro <= '$d->falstr' AND d.fechainactivo > '$d->falstr')) AND ";
            $query.= "d.idempresa = $alquiler->idempresa AND ";
            $query.= "d.idcliente = $cliente->idcliente AND d.id = b.id AND e.idtipoventa = z.idtipoventa) ";
            $query.= "ORDER BY CAST(digits(UnidadesPorContrato(b.id)) AS UNSIGNED), 2, y.desctiposervventa";
            $cliente->contratos = $db->getQuery($query);
        }
    }

    print json_encode($alquileres);
});

$app->run();