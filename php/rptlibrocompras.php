<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptlibcomp', function(){
	
	$d = json_decode(file_get_contents('php://input'));
	
	//var_dump($d);
	//echo $d;
	
	$idempresa = $d->idempresa;
	$mes = $d->mes;
	$anio = $d->anio;
	$del = $d->del;
	$al = $d->del;
	$wrdate = '';
	$orden = $d->orden;
	$orderby = '';
	
	if($del != ''){
		$wrdate .= " and a.fechafactura >= '".$del."' ";
	}
	if($al != ''){
		$wrdate .= " and a.fechafactura <= '".$al."' ";
	}
	
	if($orden == '1'){
		$orderby = "ORDER BY 1, 2, 3, 4, 6";
	}else{
		$orderby = "ORDER BY 6, 1, 2, 3, 4";
	}
	
	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$mesletra = $meses[$mes-1];
	
    $db = new dbcpm();
    $query = "SELECT a.fechafactura, c.siglas AS tipodocumento, a.serie, a.documento, b.nit, substr(b.nombre,1,20) AS proveedor, ";
    $query.= "IF(a.idtipocompra = 3, IF(a.idtipofactura <> 7, round(a.subtotal * a.tipocambio,2), 0.00), 0.00) + ";
    $query.= "IF(a.idtipocompra IN(1, 4), IF(a.idtipofactura <> 7, round(a.subtotal * a.tipocambio,2), 0.00), 0.00) AS bien, ";
    $query.= "IF(a.idtipocompra = 2, IF(a.idtipofactura <> 7, round(a.subtotal * a.tipocambio,2), 0.00), 0.00) AS servicio, ";
    $query.= "IF(a.idtipocompra = 1, IF(c.generaiva = 0 AND a.idtipofactura <> 7, ROUND(a.subtotal * a.tipocambio, 2), 0.00), 0.00) + ";
    $query.= "IF(a.idtipocompra IN(2, 3), IF(c.generaiva = 0 AND a.idtipofactura NOT IN(3, 7), ROUND(a.subtotal * a.tipocambio, 2), 0.00), 0.00)+a.idp+a.noafecto AS exento, ";
    $query.= "IF(a.idtipocompra <> 5, IF(c.generaiva = 1 AND a.idtipofactura = 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS importaciones, ";
    $query.= "ROUND(a.iva * a.tipocambio, 2) AS iva, ROUND((a.totfact + IF(a.idtipocompra = 3, 0.00, a.noafecto)) * a.tipocambio, 2) AS totfact, ";
	$query.= "ROUND(a.totfact * a.tipocambio, 2) AS totfactfull ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipofactura c ON c.id = a.idtipofactura ";
	$query.= "WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." AND a.idreembolso = 0 AND a.mesiva = ".$mes." AND YEAR(a.fechafactura) = ".$anio." ";
	$query.= (int)$d->creditofiscal == 1 ? " AND a.iva <> 0 AND b.pequeniocont = 0 " : "";
    $query.= $wrdate;
	$query.= "UNION ";
    $query.= "SELECT a.fechafactura, c.siglas AS tipodocumento, a.serie, a.documento, a.nit, substr(a.proveedor,1,20) as  proveedor, ";
    $query.= "IF(a.idtipocompra = 3, IF(c.generaiva = 1 AND a.idtipofactura <> 7, round(a.subtotal * a.tipocambio,2), 0.00), 0.00) + ";
    $query.= "IF(a.idtipocompra IN(1, 4), IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND(a.subtotal * a.tipocambio, 2), 0.00), 0.00) AS bien, ";
    $query.= "IF(a.idtipocompra = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND(a.subtotal * a.tipocambio, 2), 0.00), 0.00) AS servicio, ";
    $query.= "IF(a.idtipocompra = 1, IF(c.generaiva = 0 AND a.idtipofactura <> 7, ROUND(a.subtotal * a.tipocambio, 2), 0.00), 0.00) + ";
    $query.= "IF(a.idtipocompra IN(2, 3), IF(c.generaiva = 0 AND a.idtipofactura NOT IN(3, 7), ROUND(a.subtotal * a.tipocambio, 2), 0.00), 0.00)+a.idp+a.noafecto AS exento, ";
    $query.= "IF(a.idtipocompra <> 5, IF(c.generaiva = 1 AND a.idtipofactura = 7, ROUND(a.subtotal * a.tipocambio, 2), 0.00), 0.00) AS importaciones, ";
    $query.= "ROUND(a.iva * a.tipocambio, 2) AS iva, ROUND((a.totfact - (IF(a.idp IS NULL, 0.00, a.idp) + IF(a.idtipocompra = 3, 0.00, a.noafecto))) * a.tipocambio, 2) AS totfact, ";
	$query.= "ROUND(a.totfact * a.tipocambio, 2) AS totfactfull ";
    $query.= "FROM compra a INNER JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN proveedor b ON b.id = a.idproveedor ";
	$query.= "WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." AND a.idreembolso > 0 AND a.mesiva = ".$mes." AND YEAR(a.fechafactura) = ".$anio." ";
	$query.= (int)$d->creditofiscal == 1 ? " AND a.iva <> 0 AND (b.pequeniocont = 0 OR b.pequeniocont IS NULL) " : "";
	$query.= $wrdate;
	$query.= $orderby;
    //$query.= "ORDER BY 6, 1, 2, 3, 4";
	
	//echo $query;
	
	$detlbcomp = $db->getQuery($query);
	
	$libcompras = array();
	$idarray = 0;
			
	foreach ($detlbcomp as $dlbc) {
		$idarray++;
		
		array_push($libcompras,
			array(
				'nolinea' => $idarray,
				'bien' => ($dlbc->bien),
				'exento' => ($dlbc->exento),
				'documento' => $dlbc->documento,
				'fechafactura' => $dlbc->fechafactura,
				'importaciones' => $dlbc->importaciones,
				'iva' => $dlbc->iva,
				'nit' => $dlbc->nit,
				'proveedor' => $dlbc->proveedor,
				'serie' => $dlbc->serie,
				'servicio' => $dlbc->servicio,
				'tipodocumento' => $dlbc->tipodocumento,
				'totfact' => $dlbc->totfact,
				'totfactex' => $dlbc->totfactfull
			)
		);	
	}
	
	$empresa = $db->getQuery("SELECT nomempresa, abreviatura,direccion,nit,'$mesletra' as mesrep, '$anio' as aniorep FROM empresa WHERE id = $idempresa")[0];
    //print json_encode(['empresa' => $empresa, 'datos'=> $db->getQuery($query)]);
	
	$libro = new stdclass();
	$libro->empresa = $empresa;
	$libro->lbcompra = $libcompras;

	
	print json_encode($libro);
    //print $db->doSelectASJson($query);
});

$app->get('/gastact/:idempresa/:mes/:anio', function($idempresa, $mes, $anio){
	$db = new dbcpm();
	$query = "SELECT SUM(a.debe) AS totdebe ";
	$query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta INNER JOIN compra c ON c.id = a.idorigen ";
	$query.= "WHERE a.origen = 2 AND a.anulado = 0 AND c.idempresa = $idempresa AND c.mesiva = $mes AND YEAR(c.fechaingreso) = $anio AND (b.codigo LIKE '12101%' OR b.codigo LIKE '12102%')";
	print json_encode(['gastosactivo' => $db->getOneField($query)]);
});

$app->get('/detgastact/:idempresa/:mes/:anio', function($idempresa, $mes, $anio){
	$db = new dbcpm();

	$query = "SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, a.nomempresa AS empresa, (SELECT UPPER(nombre) FROM mes WHERE id = $mes) AS mes, $anio AS anio, '' AS totactivos ";
	$query.= "FROM empresa a WHERE id = $idempresa";
	$generales = $db->getQuery($query)[0];

	$cuentas = ['12101', '12102'];
	$activos = [];
	$totActivos = 0.00;
	for($i = 0; $i < 2; $i++){
		$cuenta = $cuentas[$i];
		$query = "SELECT codigo, nombrecta AS cuenta FROM cuentac WHERE idempresa = $idempresa AND codigo = '$cuenta'";
		$ctaActivo = $db->getQuery($query)[0];
		$query = "SELECT c.id, DATE_FORMAT(c.fechaingreso, '%d/%m/%Y') AS fechaingreso, c.serie, c.documento, IFNULL(TRIM(d.nombre), TRIM(c.proveedor)) AS proveedor, b.codigo, b.nombrecta AS cuenta, FORMAT(a.debe, 2) AS debe, a.conceptomayor ";
		$query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta INNER JOIN compra c ON c.id = a.idorigen LEFT JOIN proveedor d ON d.id = c.idproveedor ";
		$query.= "WHERE a.origen = 2 AND a.anulado = 0 AND c.idempresa = $idempresa AND c.mesiva = $mes AND YEAR(c.fechaingreso) = $anio AND b.codigo LIKE '$cuenta%' ";
		$query.= "ORDER BY c.fechaingreso";
		$compras = $db->getQuery($query);
		if(count($compras) > 0){
			$query = "SELECT SUM(a.debe) ";
			$query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta INNER JOIN compra c ON c.id = a.idorigen LEFT JOIN proveedor d ON d.id = c.idproveedor ";
			$query.= "WHERE a.origen = 2 AND a.anulado = 0 AND c.idempresa = $idempresa AND c.mesiva = $mes AND YEAR(c.fechaingreso) = $anio AND b.codigo LIKE '$cuenta%' ";
			$query.= "ORDER BY c.fechaingreso";
			$suma = $db->getOneField($query);
			$totActivos += (float)$suma;
			$compras[] = [
				'id' => '', 'fechaingreso' => '', 'serie' => '', 'documento' => '', 'proveedor' => '', 'codigo' => '', 'cuenta' => 'Total:', 'debe' => number_format((float)$suma, 2), 'conceptomayor' => ''
			];
			$activos[] = [
				'codigo' => $ctaActivo->codigo,
				'cuenta' => $ctaActivo->cuenta,
				'compras' => $compras
			];
		}
	}

	$generales->totactivos = number_format($totActivos, 2);

	print json_encode(['generales' => $generales, 'activos' => $activos]);

});

$app->run();