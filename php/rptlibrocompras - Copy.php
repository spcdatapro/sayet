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

	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$mesletra = $meses[$mes-1];
	
    $db = new dbcpm();
    $query = "SELECT a.fechafactura, c.siglas AS tipodocumento, a.serie, a.documento, b.nit, substr(b.nombre,1,20) AS proveedor, ";
    $query.= "IF(a.idtipocompra = 3, IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.idp) * a.tipocambio, 2), 0.00), 0.00) AS combustible, ";
    $query.= "IF(a.idtipocompra = 1, IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS bien, ";
    $query.= "IF(a.idtipocompra = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS servicio, ";
    $query.= "IF(a.idtipocompra = 1, IF(c.generaiva = 0 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS bienex, ";
    $query.= "IF(a.idtipocompra = 2, IF(c.generaiva = 0 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS servicioex, ";
    $query.= "IF(a.idtipocompra <> 5, IF(c.generaiva = 1 AND a.idtipofactura = 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS importaciones, ";
    $query.= "ROUND(a.iva * a.tipocambio, 2) AS iva, ROUND((a.totfact - (IF(a.idp IS NULL, 0.00, a.idp) + IF(a.idtipocompra = 3, 0.00, a.noafecto))) * a.tipocambio, 2) AS totfact, ";
	$query.= "ROUND(a.totfact * a.tipocambio, 2) AS totfactfull ";
    $query.= "FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor INNER JOIN tipofactura c ON c.id = a.idtipofactura ";
    $query.= "WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." AND a.idreembolso = 0 AND a.mesiva = ".$mes." AND YEAR(a.fechafactura) = ".$anio." ";
    $query.= "UNION ";
    $query.= "SELECT a.fechafactura, c.siglas AS tipodocumento, a.serie, a.documento, a.nit, substr(a.proveedor,1,20) as  proveedor, ";
    $query.= "IF(a.idtipocompra = 3, IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.idp) * a.tipocambio, 2), 0.00), 0.00) AS combustible, ";
    $query.= "IF(a.idtipocompra = 1, IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS bien, ";
    $query.= "IF(a.idtipocompra = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS servicio, ";
    $query.= "IF(a.idtipocompra = 1, IF(c.generaiva = 0 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS bienex, ";
    $query.= "IF(a.idtipocompra = 2, IF(c.generaiva = 0 AND a.idtipofactura <> 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS servicioex, ";
    $query.= "IF(a.idtipocompra <> 5, IF(c.generaiva = 1 AND a.idtipofactura = 7, ROUND((a.subtotal - a.noafecto) * a.tipocambio, 2), 0.00), 0.00) AS importaciones, ";
    $query.= "ROUND(a.iva * a.tipocambio, 2) AS iva, ROUND((a.totfact - (IF(a.idp IS NULL, 0.00, a.idp) + IF(a.idtipocompra = 3, 0.00, a.noafecto))) * a.tipocambio, 2) AS totfact, ";
	$query.= "ROUND(a.totfact * a.tipocambio, 2) AS totfactfull ";
    $query.= "FROM compra a INNER JOIN tipofactura c ON c.id = a.idtipofactura ";
    $query.= "WHERE a.idtipocompra <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." AND a.idreembolso > 0 AND a.mesiva = ".$mes." AND YEAR(a.fechafactura) = ".$anio." ";
    $query.= "ORDER BY 6, 1, 2, 3, 4";
	
	$detlbcomp = $db->getQuery($query);
	
	$libcompras = array();
	$idarray = 0;
			
	foreach ($detlbcomp as $dlbc) {
		$idarray++;
		
		array_push($libcompras,
			array(
				'nolinea' => $idarray,
				'bien' => ($dlbc->bien + $dlbc->combustible),
				'exento' => ($dlbc->bienex + $dlbc->servicioex),
				'combustible' => $dlbc->combustible,
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

$app->run();