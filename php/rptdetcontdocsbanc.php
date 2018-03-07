<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/detcontdocsbanc', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $documentos = new stdClass();

    //Datos del banco
    $query = "SELECT a.nombre, b.simbolo, a.nocuenta, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, c.nomempresa AS empresa ";
    $query.= "FROM banco a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN empresa c ON c.id = a.idempresa ";
    $query.= "WHERE a.id = $d->idbanco";
    $documentos->banco = $db->getQuery($query)[0];

    //Documentos
    $query = "SELECT a.id, a.idbanco, a.tipotrans, a.numero AS documento, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.beneficiario, FORMAT(a.monto, 2) AS monto, a.concepto ";
    $query.= "FROM tranban a ";
    $query.= "WHERE a.idbanco = $d->idbanco AND a.fecha >= '$d->fdelstr' AND fecha <= '$d->falstr' ";
    $query.= $d->abreviatura != '' ? "AND a.tipotrans = '$d->abreviatura' " : "";
    $query.= "ORDER BY a.fecha, a.tipotrans, a.numero";
    $documentos->docs = $db->getQuery($query);
    $cntDocs = count($documentos->docs);

    for($i = 0; $i < $cntDocs; $i++){
        $doc = $documentos->docs[$i];
        //Detalle contable
        $query = "SELECT b.codigo, b.nombrecta AS cuenta, IF(a.debe <> 0, FORMAT(a.debe, 2), '') AS debe, IF(a.haber <> 0, FORMAT(a.haber, 2), '') AS haber ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.origen = 1 AND a.idorigen = $doc->id ";
        $query.= "ORDER BY a.debe DESC, b.codigo, b.nombrecta";
        $doc->detcont = $db->getQuery($query);
        if(count($doc->detcont) > 0){
            //Suma del detalle contable
            $query = "SELECT FORMAT(SUM(a.debe), 2) AS debe, FORMAT(SUM(a.haber), 2) AS haber ";
            $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
            $query.= "WHERE a.origen = 1 AND a.idorigen = $doc->id";
            $suma = $db->getQuery($query)[0];
            $doc->detcont[] = ['codigo' => '', 'cuenta' => 'Totales de partida:', 'debe' => $suma->debe, 'haber' => $suma->haber];
        }

        //Documentos de soporte
        $query = "SELECT a.id, c.nit, CONCAT(d.siglas, a.serie, a.documento) AS documento, c.nombre AS proveedor, IF(a.idtipocompra = 1, FORMAT(a.subtotal, 2), '') AS bien, IF(a.idtipocompra = 2, FORMAT(a.subtotal, 2), '') AS servicio, ";
        $query.= "IF(a.idtipocompra NOT IN(1, 2), FORMAT(a.subtotal, 2), '') AS otros, FORMAT(a.iva, 2) AS iva, FORMAT(a.totfact, 2) AS totfact, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fecha ";
        $query.= "FROM compra a INNER JOIN detpagocompra b ON a.id = b.idcompra INNER JOIN proveedor c ON c.id = a.idproveedor INNER JOIN tipofactura d ON d.id = a.idtipofactura ";
        $query.= "WHERE b.idtranban = $doc->id ";
        $query.= "UNION ";
        $query.= "SELECT a.id, c.nit, CONCAT(d.siglas, a.serie, a.documento) AS documento, c.nombre AS proveedor, IF(a.idtipocompra = 1, FORMAT(a.subtotal, 2), '') AS bien, IF(a.idtipocompra = 2, FORMAT(a.subtotal, 2), '') AS servicio, ";
        $query.= "IF(a.idtipocompra NOT IN(1, 2), FORMAT(a.subtotal, 2), '') AS otros, FORMAT(a.iva, 2) AS iva, FORMAT(a.totfact, 2) AS totfact, DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fecha ";
        $query.= "FROM compra a INNER JOIN doctotranban b ON a.id = b.iddocto INNER JOIN proveedor c ON c.id = a.idproveedor INNER JOIN tipofactura d ON d.id = a.idtipofactura ";
        $query.= "WHERE b.idtipodoc = 1 AND b.idtranban = $doc->id ";
        $query.= "ORDER BY 2, 3, 4";
        $doc->docsop = $db->getQuery($query);
        $cntDocsSop = count($doc->docsop);
        if($cntDocsSop > 0){
            //Suma de los documentos de soporte
            $query = "SELECT IF(SUM(bien) <> 0, FORMAT(SUM(bien), 2), '') AS bien, IF(SUM(servicio) <> 0, FORMAT(SUM(servicio), 2), '') AS servicio, IF(SUM(otros) <> 0, FORMAT(SUM(otros), 2), '') AS otros, ";
            $query.= "IF(SUM(iva) <> 0, FORMAT(SUM(iva), 2), '') AS iva, IF(SUM(totfact) <> 0, FORMAT(SUM(totfact), 2), '') AS totfact ";
            $query.= "FROM(";
            $query.= "SELECT IF(a.idtipocompra = 1, a.subtotal, 0.00) AS bien,IF(a.idtipocompra = 2, a.subtotal, 0.00) AS servicio, IF(a.idtipocompra NOT IN(1, 2), a.subtotal, 0.00) AS otros, a.iva, a.totfact ";
            $query.= "FROM compra a INNER JOIN detpagocompra b ON a.id = b.idcompra INNER JOIN proveedor c ON c.id = a.idproveedor INNER JOIN tipofactura d ON d.id = a.idtipofactura ";
            $query.= "WHERE b.idtranban = $doc->id ";
            $query.= "UNION ";
            $query.= "SELECT IF(a.idtipocompra = 1, a.subtotal, 0.00) AS bien,IF(a.idtipocompra = 2, a.subtotal, 0.00) AS servicio, IF(a.idtipocompra NOT IN(1, 2), a.subtotal, 0.00) AS otros, a.iva, a.totfact ";
            $query.= "FROM compra a INNER JOIN doctotranban b ON a.id = b.iddocto INNER JOIN proveedor c ON c.id = a.idproveedor INNER JOIN tipofactura d ON d.id = a.idtipofactura ";
            $query.= "WHERE b.idtranban = $doc->id";
            $query.= ") e";
            $suma = $db->getQuery($query)[0];
            $doc->docsop[] = ['id' => '','nit' => '', 'documento' => '', 'proveedor' => 'Totales de Facts.:', 'bien' => $suma->bien, 'servicio' => $suma->servicio, 'otros' => $suma->otros, 'iva' => $suma->iva, 'totfact' => $suma->totfact];

            //Detalle contable de documentos de soporte
            for($j = 0; $j < $cntDocsSop; $j++){
                $dsop = $doc->docsop[$j];
                //var_dump($dsop);
                $query = "SELECT b.codigo, b.nombrecta AS cuenta, IF(a.debe <> 0, FORMAT(a.debe, 2), '') AS debe, IF(a.haber <> 0, FORMAT(a.haber, 2), '') AS haber ";
                $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
                $query.= "WHERE a.origen = 2 AND a.idorigen = $dsop->id ";
                $query.= "ORDER BY a.debe DESC, b.codigo, b.nombrecta";
                //print $query;
                $dsop->detcontdsop = $db->getQuery($query);
                if(count($dsop->detcontdsop) > 0){
                    //Suma del detalle contable
                    $query = "SELECT FORMAT(SUM(a.debe), 2) AS debe, FORMAT(SUM(a.haber), 2) AS haber ";
                    $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
                    $query.= "WHERE a.origen = 2 AND a.idorigen = $dsop->id";
                    $suma = $db->getQuery($query)[0];
                    $dsop->detcontdsop[] = ['codigo' => '', 'cuenta' => 'Totales de partida:', 'debe' => $suma->debe, 'haber' => $suma->haber];
                }
            }
        }
    }

    print json_encode($documentos);
});

$app->run();