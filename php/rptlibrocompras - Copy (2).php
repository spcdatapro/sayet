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

$app->run();