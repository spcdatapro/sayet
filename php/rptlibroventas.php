<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptlibventas', function(){
	
	$d = json_decode(file_get_contents('php://input'));
	
	$idempresa = $d->idempresa;
	$mes = $d->mes;
	$anio = $d->anio;

	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$mesletra = $meses[$mes-1];
	
    $db = new dbcpm();
    $query = "SELECT a.fecha AS fechafactura, c.siglas AS tipodocumento, a.serie, a.numero AS documento, ";
	$query.= "IF(a.anulada = 0, TRIM(a.nit), '0') AS nit, ";
    $query.= "substr(IF(a.anulada = 0, TRIM(a.nombre), 'ANULADA'),1,35) AS cliente, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa IN(1, 2, 4), IF(c.generaiva = 0 AND a.idtipofactura <> 6, ROUND((a.subtotal - a.noafecto), 2), 0.00), 0.00), 0.00) AS exento, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 4, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.total - a.noafecto - a.iva), 2), 0.00), 0.00), 0.00) AS activo, ";
    $query.= "IF(a.anulada = 0, IF(a.idtipoventa = 1, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.total - a.noafecto - a.iva), 2), 0.00), 0.00), 0.00) AS bien, ";    
	//$query.= "IF(a.anulada = 0, IF(a.idtipoventa = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND((a.subtotal + IF(a.idfox IS NULL, 0, a.retiva) - IF(a.idfox IS NULL, a.iva, 0)), 2), 0.00), 0.00), 0.00) AS servicio, ";    	
	$query.= "IF(a.anulada = 0, IF(a.idtipoventa = 2, IF(c.generaiva = 1 AND a.idtipofactura <> 6, ROUND(a.subtotal - a.iva, 2), 0.00), 0.00), 0.00) AS servicio, ";
	//$query.= "IF(a.anulada = 0, ROUND(a.iva, 2), 0.00) AS iva, IF(a.anulada = 0, ROUND(((a.subtotal + IF(a.idfox IS NULL, 0, a.iva + a.retiva))), 2), 0.00) AS totfact ";	
	$query.= "IF(a.anulada = 0, ROUND(a.iva, 2), 0.00) AS iva, IF(a.anulada = 0, ROUND(a.subtotal, 2), 0.00) AS totfact ";
    $query.= "FROM factura a LEFT JOIN contrato b ON b.id = a.idcontrato LEFT JOIN tipofactura c ON c.id = a.idtipofactura LEFT JOIN cliente d ON d.id = a.idcliente ";
    $query.= "WHERE a.idtipoventa <> 5 AND c.id <> 5 AND a.idempresa = ".$idempresa." AND a.mesiva = ".$mes." AND YEAR(a.fecha) = ".$anio." ";
    $query.= (int)$d->alfa > 0 ? "ORDER BY 6, 1, 3, 4" : "ORDER BY 1, 3, 4";
	
	$detlbventa = $db->getQuery($query);
	
	$libventas = array();
	$idarray = 0;
			
	foreach ($detlbventa as $dlbv) {
		$idarray++;
		
		array_push($libventas,
			array(
				'nolinea' => $idarray,
				'bien' => $dlbv->bien,
				'documento' => $dlbv->documento,
				'fechafactura' => $dlbv->fechafactura,
				'iva' => $dlbv->iva,
				'nit' => $dlbv->nit,
				'cliente' => $dlbv->cliente,
				'serie' => $dlbv->serie,
				'servicio' => $dlbv->servicio,
				'exento' => $dlbv->exento,
				'tipodocumento' => $dlbv->tipodocumento,
				'totfact' => $dlbv->totfact
			)
		);	
	}
	
	$empresa = $db->getQuery("SELECT nomempresa, abreviatura,direccion,nit,'$mesletra' as mesrep, '$anio' as aniorep FROM empresa WHERE id = $idempresa")[0];
    //print json_encode(['empresa' => $empresa, 'datos'=> $db->getQuery($query)]);
	
	$libro = new stdclass();
	$libro->empresa = $empresa;
	$libro->lbventa = $libventas;

	
	print json_encode($libro);
    //print $db->doSelectASJson($query);
});

$app->run();