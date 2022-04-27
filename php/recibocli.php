<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

//header('Content-Type: application/json');

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para recibos de clientes
//Inicio modificacion
$app->post('/lstreciboscli', function(){ 
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->tipo)){ $d->tipo = 1; }

    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, b.nombre AS cliente, 
	IFNULL(h.abreviatura, c.tipotrans) AS tipotrans, IFNULL(g.numero, c.numero) AS notranban, IFNULL(i.nombre, e.nombre) AS nombre, 
    IFNULL(j.simbolo, f.simbolo) AS simbolo, IFNULL(g.monto, c.monto) AS monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea, a.concepto, a.nit, 
    IF(a.anulado = 0, IFNULL(IF(a.serie = 'A', k.seriea, k.serieb), a.id), IF(a.serie = 'A', CONCAT(k.seriea, ' (ANULADO)'), CONCAT(k.serieb, ' (ANULADO)'))) 
    AS correlativo
    FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion 
    LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda LEFT JOIN detpagorecli g ON g.idreccli = a.id LEFT JOIN pagosreccli h ON g.tipotrans = h.id
    LEFT JOIN bancopais i ON g.idbanco = i.id LEFT JOIN moneda j ON g.idmoneda = j.id LEFT JOIN serierecli k ON k.idrecibocli = a.id
    WHERE a.idempresa = $d->idempresa AND a.tipo = $d->tipo ";
    $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : "" ;
    $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : "" ;
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "" ;
    $query.= (int)$d->recibostr != 0 ? "AND a.numero = $d->recibostr " : "" ;
    $query.= $d->clientestr != '' ? "AND b.nombre LIKE '%$d->clientestr%' " : "" ;
    $query.= $d->ban_numerostr != '' ? "AND c.numero = '$d->ban_numerostr' " : "" ;
    $query.= $d->ban_cuentastr != '' ? "AND e.nombre LIKE '%$d->ban_cuentastr%' " : "" ;
    $query.= $d->correstr != '' ? "AND a.id = $d->correstr " : "" ;
    $query.= $d->nomcortstr != '' ? "AND b.nombrecorto LIKE '%$d->nomcortstr%' " : "";
    $query.= " UNION ALL ";
    $query.= "SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, 'Facturas contado (Clientes varios)' AS cliente, 
    IFNULL(h.abreviatura, c.tipotrans) AS tipotrans, IFNULL(g.numero, c.numero) AS notranban, IFNULL(i.nombre, e.nombre) AS nombre, 
    IFNULL(j.simbolo, f.simbolo) AS simbolo, IFNULL(g.monto, c.monto) AS monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea, a.concepto, a.nit, 
    IF(a.anulado = 0, IFNULL(IF(a.serie = 'A', k.seriea, k.serieb), a.id), IF(a.serie = 'A', CONCAT(k.seriea, ' (ANULADO)'), CONCAT(k.serieb, ' (ANULADO)'))) 
    AS correlativo
    FROM recibocli a LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion 
    LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda LEFT JOIN detpagorecli g ON g.idreccli = a.id  LEFT JOIN pagosreccli h ON g.tipotrans = h.id 
    LEFT JOIN bancopais i ON g.idbanco = i.id LEFT JOIN moneda j ON g.idmoneda = j.id LEFT JOIN serierecli k ON k.idrecibocli = a.id
    WHERE a.idempresa = $d->idempresa AND a.tipo = $d->tipo AND (a.idcliente = 0 OR a.idcliente IS NULL) AND (a.nit = 0 OR a.nit IS NULL OR a.nit = 'CF')";
    $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : "" ;
    $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : "" ;
    $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "" ;
    $query.= (int)$d->recibostr != 0 ? "AND a.numero = $d->recibostr " : "" ;
    $query.= $d->ban_numerostr != '' ? "AND c.numero = '$d->ban_numerostr' " : "" ;
    $query.= $d->ban_cuentastr != '' ? "AND e.nombre LIKE '%$d->ban_cuentastr%' " : "" ;
    $query.= $d->correstr != '' ? "AND a.id = $d->correstr " : "" ;
    $query.= $d->clientestr != '' ? "AND a.idcliente != 0 " : "" ;
    $query.= $d->nomcortstr != '' ? "AND a.nit != 0 " : "";
    $query.= "UNION ALL ";
    $query.= "SELECT DISTINCT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, b.nombre AS cliente, 
    IFNULL(h.abreviatura, c.tipotrans) AS tipotrans, IFNULL(g.numero, c.numero) AS notranban, IFNULL(i.nombre, e.nombre) AS nombre, 
    IFNULL(j.simbolo, f.simbolo) AS simbolo, IFNULL(g.monto, c.monto) AS monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea, a.concepto, a.nit, 
    IF(a.anulado = 0, IFNULL(IF(a.serie = 'A', k.seriea, k.serieb), a.id), IF(a.serie = 'A', CONCAT(k.seriea, ' (ANULADO)'), CONCAT(k.serieb, ' (ANULADO)'))) 
    AS correlativo
    FROM recibocli a 
    INNER JOIN factura b ON a.nit = b.nit
    LEFT JOIN tranban c ON c.id = a.idtranban LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion 
    LEFT JOIN banco e ON e.id = c.idbanco LEFT JOIN moneda f ON f.id = e.idmoneda LEFT JOIN detpagorecli g ON g.idreccli = a.id LEFT JOIN pagosreccli h ON g.tipotrans = h.id 
    LEFT JOIN bancopais i ON g.idbanco = i.id LEFT JOIN moneda j ON g.idmoneda = j.id LEFT JOIN serierecli k ON k.idrecibocli = a.id
    WHERE a.idempresa = $d->idempresa AND a.tipo = $d->tipo AND (a.idcliente = 0 OR a.idcliente IS NULL) AND a.nit != 'CF'";
        $query.= $d->fdelstr != '' ? "AND a.fecha >= '$d->fdelstr' " : "" ;
        $query.= $d->falstr != '' ? "AND a.fecha <= '$d->falstr' " : "" ;
        $query.= $d->serie != '' ? "AND a.serie = '$d->serie' " : "" ;
        $query.= (int)$d->recibostr != 0 ? "AND a.numero = $d->recibostr " : "" ;
        $query.= $d->clientestr != '' ? "AND a.idcliente != 0 " : "" ;
        $query.= $d->nomcortstr != '' ? "AND a.idcliente != 0 " : "";
        $query.= $d->ban_numerostr != '' ? "AND c.numero = '$d->ban_numerostr' " : "" ;
        $query.= $d->ban_cuentastr != '' ? "AND a.idcliente != 0 " : "" ;
        $query.= $d->correstr != '' ? "AND a.id = $d->correstr " : "" ;
    $query.="ORDER BY 1 DESC";
    print $db->doSelectASJson($query);
});
//Fin modificacion
$app->get('/getrecibocli/:idrecibo', function($idrecibo){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, b.nombre AS cliente, 
            c.tipotrans, c.numero AS notranban, e.nombre, 
            f.simbolo, c.monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea, a.concepto, a.nit, 
            IF(a.anulado = 0, IFNULL(IF(a.serie = 'A', g.seriea, g.serieb), a.id), 
            IF(a.serie = 'A', CONCAT(g.seriea, ' (ANULADO)'), CONCAT(g.serieb, ' (ANULADO)'))) AS correlativo
            FROM recibocli a 
            INNER JOIN cliente b ON b.id = a.idcliente 
            LEFT JOIN tranban c ON c.id = a.idtranban 
            LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion 
            LEFT JOIN banco e ON e.id = c.idbanco 
            LEFT JOIN moneda f ON f.id = e.idmoneda 
            LEFT JOIN serierecli g ON a.id = g.idrecibocli
            WHERE a.id = $idrecibo
            UNION ALL 
            SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, 
            'Facturas contado (Clientes varios)' AS cliente, c.tipotrans, c.numero AS notranban, e.nombre, 
            f.simbolo, c.monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea, a.concepto, a.nit, 
            IF(a.anulado = 0, IFNULL(IF(a.serie = 'A', g.seriea, g.serieb), a.id), 'ANULADO') AS correlativo
            FROM recibocli a 
            LEFT JOIN tranban c ON c.id = a.idtranban 
            LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion 
            LEFT JOIN banco e ON e.id = c.idbanco 
            LEFT JOIN moneda f ON f.id = e.idmoneda
            LEFT JOIN serierecli g ON a.id = g.idrecibocli 
            WHERE a.id = $idrecibo AND (a.nit = 0 or a.nit IS NULL) AND (a.idcliente = 0 or a.idcliente IS NULL OR a.nit = 'CF')
            UNION ALL
            SELECT a.id, a.fecha, a.fechacrea, a.idcliente, a.espropio, a.idtranban, a.anulado, a.idrazonanulacion, a.fechaanula, b.nombre AS cliente, 
            c.tipotrans, c.numero AS notranban, e.nombre, 
            f.simbolo, c.monto, a.idempresa, d.razon, a.serie, a.numero, a.usuariocrea, a.concepto, a.nit, 
            IF(a.anulado = 0, IFNULL(IF(a.serie = 'A', g.seriea, g.serieb), a.id), 'ANULADO') AS correlativo
            FROM recibocli a 
            INNER JOIN factura b ON a.nit = b.nit 
            LEFT JOIN tranban c ON c.id = a.idtranban 
            LEFT JOIN razonanulacion d ON d.id = a.idrazonanulacion 
            LEFT JOIN banco e ON e.id = c.idbanco 
            LEFT JOIN moneda f ON f.id = e.idmoneda 
            LEFT JOIN serierecli g ON a.id = g.idrecibocli
            WHERE a.id = $idrecibo AND a.idcliente = 0 AND a.nit != 'CF' ";
    print $db->doSelectASJson($query);
});

function getCorrelativoInterno($d, $db, $lastid)
{
    if($d->serie == 'A')
    {
        $seriea = $db->getOneField("SELECT seriea AS seriea FROM serierecli WHERE seriea != 0 ORDER BY id DESC LIMIT 1 ");
        if($seriea == NULL)
        {
            $seriea = 1;
        } else 
        {
            $seriea++; 
        }
        $insertcorre = "INSERT INTO serierecli(seriea, idrecibocli) VALUES ($seriea, $lastid) ";
        $db->doQuery($insertcorre);
    } elseif ($d->serie == 'B')
    {
        $serieb = $db->getOneField("SELECT serieb AS serieb FROM serierecli WHERE serieb != 0 ORDER BY id DESC LIMIT 1 ");
        if($serieb == NULL)
        {
            $serieb = 1;
        } else 
        {
            $serieb++;
        }
        $insertcorre = "INSERT INTO serierecli(serieb, idrecibocli) VALUES ($serieb, $lastid) ";
        $db->doQuery($insertcorre);
    }
}

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    if(!isset($d->tipo)){ $d->tipo = 1; }
    if(!isset($d->concepto)){ $d->conceto = ''; }
    if((int)$d->tipo > 1){
        $d->serie = (int)$d->tipo == 2 ? 'I' : 'D';
        $d->numero = (int)$db->getOneField("SELECT IFNULL(MAX(numero), 0) + 1 FROM recibocli WHERE tipo = $d->tipo");
    } else 
    {
        $d->numero = 0;
    }

    $query = "INSERT INTO recibocli(idempresa, fecha, fechacrea, idcliente, espropio, idtranban, serie, numero, usuariocrea, tipo, concepto, nit) VALUES(";
    $query.= "$d->idempresa,'$d->fechastr', NOW(), $d->idcliente, $d->espropio, $d->idtranban, '$d->serie', $d->numero, '$d->usuariocrea', $d->tipo, '$d->concepto', '$d->nit'";
    $query.= ")";
    $db->doQuery($query);

    $lastid = $db->getLastId();
    print json_encode(['lastid' => $db->getLastId()]);
    if((int)$lastid > 0){
        //Correlativo
        getCorrelativoInterno($d, $db, $lastid);
    }
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    if(!isset($d->tipo)){ $d->tipo = 1; }
    if(!isset($d->concepto)){ $d->conceto = ''; }
    $db = new dbcpm();
    $query = "UPDATE recibocli SET ";
    $query.= "fecha = '$d->fechastr', idcliente = $d->idcliente, espropio = $d->espropio, idtranban = $d->idtranban, ";
    $query.= "serie = '$d->serie', concepto = '$d->concepto', ";
    $query.= "usuariocrea = '$d->usuariocrea', nit = $d->nit ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    //Rony 2017-11-21 Mantiene los registro de facturas aplicadas al recibo en un array
    $datos = [];
    $query ="SELECT * FROM detcobroventa WHERE idrecibocli = $d->id";
    $datos = $db->getQuery($query);

    //Rony 2017-11-21 Poner como NO pagada las facturas aplicdas en recibo eliminado
    $registros = count($datos);
    for($i = 0; $i < $registros; $i++){
        $registro = $datos[$i];

        $query = "UPDATE factura SET pagada = 0, fechapago = NULL WHERE id = $registro->idfactura";
        $db->doQuery($query);
    }

    // Elimina registros del recibo, detalle contable y facturas aplicadas
    $db->doQuery("DELETE FROM detallecontable WHERE origen = 8 AND idorigen = $d->id");
    $db->doQuery("DELETE FROM detcobroventa WHERE idrecibocli = $d->id");
    $db->doQuery("DELETE FROM recibocli WHERE id = ".$d->id);

});

$app->post('/anula', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE recibocli SET anulado = 1, fechaanula = NOW()  WHERE id = $d->id";
    $db->doQuery($query);

    //Rony 2017-11-21 Poner como NO pagada las facturas aplicdas en recibo eliminado
    $datos = [];
    $query ="SELECT * FROM detcobroventa WHERE idrecibocli = $d->id";
    $datos = $db->getQuery($query);

    $registros = count($datos);
    for($i = 0; $i < $registros; $i++){
        $registro = $datos[$i];

        $query = "UPDATE factura SET pagada = 0, fechapago = NULL WHERE id = $registro->idfactura";
        $db->doQuery($query);
    }

        $query = "DELETE FROM detpagorecli WHERE idreccli = $d->id ";
        $db->doQuery($query);

        $query = "DELETE FROM detcobroventa WHERE idrecibocli = $d->id";
        $db->doQuery($query);

    // $query = "UPDATE detallecontable SET activada = 0, anulado = 1 WHERE origen = 8 AND idorigen = $d->id";
    // $db->doQuery($query);
});

$app->get('/lsttranban/:idempresa(/:tipo)', function($idempresa, $tipo = 1){
    $db = new dbcpm();
    $query = "SELECT a.id, a.fecha, b.nombre, a.tipotrans, a.numero, c.simbolo, a.monto ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN moneda c ON c.id = b.idmoneda ";
    $query.= "WHERE a.tipotrans IN ".((int)$tipo == 3 ? "('C', 'B')" : "('D', 'R')" )." AND b.idempresa = $idempresa ";
    $query.= "ORDER BY a.fecha, b.nombre, a.tipotrans, a.numero";
    //echo $query;
    print $db->doSelectASJson($query);
});

$app->get('/docspend/:idempresa/:idcliente/:nit(/:tipo)', function($idempresa, $idcliente, $nit, $tipo = 1){
    $db = new dbcpm();
    $query = "SELECT a.id, c.siglas, a.serie, a.numero, a.fecha, b.simbolo, a.total, IF(ISNULL(d.cobrado), 0.00, d.cobrado) AS cobrado, ";
    $query.= "(a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo, ";

    $query.= "CONCAT(c.siglas, ' - ', a.serie, ' ', a.numero, ' - ', DATE_FORMAT(a.fecha, '%d/%m/%Y'), ' - Total: ', b.simbolo, ' ', TRUNCATE(a.total, 2),  ' - Abonado: ', ";
    $query.= "IF(ISNULL(d.cobrado), 0.00, TRUNCATE(d.cobrado, 2)),  ' - Saldo: ',TRUNCATE((a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)), 2)) AS cadena ";

    $query.= "FROM factura a INNER JOIN moneda b ON b.id = a.idmoneda INNER JOIN tipofactura c ON c.id = a.idtipofactura ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.anulada = 0 AND a.idempresa = $idempresa ";
    $query.= (int)$tipo < 3 ? "AND IF(a.pagada = 0, (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) > 0, (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) < 0)" : "AND (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) < 0 ";
    //$query.= "AND a.pagada = 0 AND (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) > 0 ";
    $query.= (int)$idcliente > 0 ? "AND a.idcliente = $idcliente " : "AND a.nit = '$nit' ";
    $query.= "ORDER BY a.fecha";
    print $db->doSelectASJson($query);
});

//API para detalle de recibos de clientes
$app->get('/lstdetreccli/:idrecibo', function($idrecibo){
    $db = new dbcpm();
    $query = "SELECT 0 AS oby, a.id, a.idfactura, a.idrecibocli, d.siglas, b.serie, b.numero, b.fecha, c.simbolo, b.total, a.monto, a.interes ";
    $query.= "FROM detcobroventa a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN tipofactura d ON d.id = b.idtipofactura ";
    $query.= "WHERE a.idrecibocli = $idrecibo ";
    $query.= "UNION ";
    $query.= "SELECT 1 AS oby, '' AS id, '' AS idfactura, '' AS idrecibocli, '' AS siglas, '' AS serie, 'Total' AS numero, '' AS fecha, '' AS simbolo, '' AS total, SUM(a.monto), '' AS interes ";
    $query.= "FROM detcobroventa a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN tipofactura d ON d.id = b.idtipofactura ";
    $query.= "WHERE a.idrecibocli = $idrecibo ";
    $query.= "ORDER BY 1, fecha";
    print $db->doSelectASJson($query);
});

$app->get('/getdetreccli/:iddetrec', function($iddetrec){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idfactura, a.idrecibocli, d.siglas, b.serie, b.numero, b.fecha, c.simbolo, b.total, a.monto, a.interes ";
    $query.= "FROM detcobroventa a INNER JOIN factura b ON b.id = a.idfactura INNER JOIN moneda c ON c.id = b.idmoneda INNER JOIN tipofactura d ON d.id = b.idtipofactura ";
    $query.= "WHERE a.id = $iddetrec";
    print $db->doSelectASJson($query);
});

function setFacturaPagada($db, $d){
    $query = "SELECT (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo FROM factura a ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.id = $d->idfactura LIMIT 1";
    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if(!$haypendiente){
        $query = "UPDATE factura SET pagada = 1, fechapago = (SELECT fecha FROM recibocli WHERE id = $d->idrecibocli) WHERE id = $d->idfactura";
        $db->doQuery($query);
    }    
}

$app->post('/cd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO detcobroventa(idfactura, idrecibocli, monto, interes, esrecprov) VALUES($d->idfactura, $d->idrecibocli, $d->monto, $d->interes, 1)";
    $db->doQuery($query);

    //Poner como pagada la factura si su saldo es 0.00
    setFacturaPagada($db, $d);
    print json_encode(['lastid' => $db->getLastId()]);
});
//esto es el modelo para actualizar el campo de abono
$app->post('/ud', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE detcobroventa SET monto = $d->monto, interes = $d->interes WHERE id = $d->id");

    //Rony 2017-11-16 Editar monto abono
    //Obtiene saldo de factura
    $query = "SELECT (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo FROM factura a ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.id = $d->idfactura LIMIT 1";
    
    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if($haypendiente){
        //Poner como NO pagada la factura
        $query = "UPDATE factura SET pagada = 0, fechapago = NULL WHERE id = $d->idfactura";
        $db->doQuery($query);
    } else {
        //Poner como pagada la factura si su saldo es 0.00
        setFacturaPagada($db, $d);
    }
    //Rony 2017-11-16 Editar monto abono

});

$app->post('/dd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detcobroventa WHERE id = $d->id");

    //Poner como NO pagada la factura
    $query = "SELECT (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo FROM factura a ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.id = $d->idfactura LIMIT 1";
    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if($haypendiente){
        $query = "UPDATE factura SET pagada = 0, fechapago = NULL WHERE id = $d->idfactura";
        $db->doQuery($query);
    }
});

$app->post('/prntrecint', function() {
    $d = json_decode(file_get_contents('php://input'));
    $n2l = new NumberToLetterConverter();
    $db = new dbcpm();
    $query = "SELECT a.serie, a.numero, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, b.nombre AS cliente, b.nombrecorto AS abreviacliente, ";
    $query .= "a.concepto, a.usuariocrea AS hechopor, (SELECT FORMAT(IFNULL(SUM(monto), 0.00), 2) FROM detcobroventa WHERE idrecibocli = a.id) AS monto, ";
    $query .= "(SELECT IFNULL(SUM(monto), 0.00) FROM detcobroventa WHERE idrecibocli = a.id) AS total, NULL as montoletras ";
    $query .= "FROM recibocli a INNER JOIN cliente b ON b.id = a.idcliente ";
    $query .= "WHERE a.id = $d->id";

    $recibo = $db->getQuery($query);

    if (count($recibo) > 0) {
        $recibo[0]->montoletras = $n2l->to_word($recibo[0]->total, 'GTQ');

        $query = "SELECT b.codigo, b.nombrecta AS cuenta, FORMAT(a.debe, 2) AS debe, FORMAT(a.haber, 2) AS haber, a.conceptomayor AS concepto ";
        $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
        $query.= "WHERE a.origen = 12 AND a.idorigen = $d->id ";
        $query.= "ORDER BY a.debe DESC, a.haber, b.codigo";
        $detcont = $db->getQuery($query);
        
        if(count($detcont) > 0) {
            $query = "SELECT '' AS codigo, 'TOTAL' AS cuenta, FORMAT(SUM(a.debe), 2) AS debe, FORMAT(SUM(a.haber), 2) AS haber, '' AS concepto ";
            $query.= "FROM detallecontable a INNER JOIN cuentac b ON b.id = a.idcuenta ";
            $query.= "WHERE a.origen = 12 AND a.idorigen = $d->id";
            $detcont[] = $db->getQuery($query)[0];
            $recibo[0]->detcont = $detcont;
        }

        print json_encode(['recibo' => $recibo[0]]);
    } else {
        print json_encode(['recibo' => null]);
    }
});

$app->post('/prtrecibocli', function() {
    $d = json_decode(file_get_contents('php://input'));
    $n2l = new NumberToLetterConverter();
    $db = new dbcpm();
    $query =
                "SELECT 
                    a.serie,
                    IF(a.anulado = 0,
                    IFNULL(IF(a.serie = 'A', b.seriea, b.serieb),
                            a.id),
                    IF(a.serie = 'A',
                        CONCAT(b.seriea, ' (ANULADO)'),
                        CONCAT(b.serieb, ' (ANULADO)'))) AS numero,
                    FORMAT(SUM(c.monto), 2) AS montorecli,
                    e.simbolo AS monedarecli,
                    DAY(a.fecha) AS dia,
                    MONTH(a.fecha) AS mes,
                    YEAR(a.fecha) AS anio,
                    IFNULL(f.nombre, d.nombre) AS cliente,
                    NULL AS montoletras,
                    a.concepto,
                    g.nomempresa AS empresa
                FROM
                    recibocli a
                        LEFT JOIN
                    serierecli b ON b.idrecibocli = a.id
                        LEFT JOIN
                    detcobroventa c ON c.idrecibocli = a.id
                        LEFT JOIN
                    factura d ON c.idfactura = d.id
                        LEFT JOIN
                    moneda e ON d.idmoneda = e.id
                        LEFT JOIN
                    cliente f ON a.idcliente = f.id
                        INNER JOIN
                    empresa g ON a.idempresa = g.id
                WHERE
                a.id = $d->idrecibo ";
    $recibo = $db->getQuery($query);

        $recibo[0]->montoletras = $n2l->to_word($recibo[0]->montorecli, 'GTQ');

    // facturas

    $query = 
                "SELECT  
                    c.serie AS seriefact,
                    c.numero AS numfact,
                    IF(b.monto < 0,
                    CONCAT('(',
                            SUBSTRING(FORMAT(b.monto, 2), 2, 10),
                            ')'),
                    IF(ABS(c.total - b.monto) < 10,
                        FORMAT(c.total, 2),
                        FORMAT(b.monto, 2))) AS montofact,
                    e.simbolo AS monedafact
                FROM
                    recibocli a
                        INNER JOIN
                    detcobroventa b ON b.idrecibocli = a.id
                        INNER JOIN
                    factura c ON b.idfactura = c.id
                        INNER JOIN
                    moneda e ON c.idmoneda = e.id
                WHERE
                a.id = $d->idrecibo ";
    $facturas = $db->getQuery($query);

    // cheques

    $query = "SELECT 
                    IFNULL(CONCAT(e.abreviatura, '-', b.numero),
                            CONCAT('(', e.abreviatura, ') ', e.descripcion)) AS numero,
                    IFNULL(c.nombre, '') AS banco,
                    d.simbolo AS moneda,
                    FORMAT(b.monto, 2) AS monto
                FROM
                    recibocli a
                        INNER JOIN
                    detpagorecli b ON a.id = b.idreccli
                        LEFT JOIN
                    bancopais c ON c.id = b.idbanco
                        INNER JOIN
                    moneda d ON d.id = b.idmoneda
                        LEFT JOIN
                    pagosreccli e ON b.tipotrans = e.id
                WHERE
                    b.idreccli = $d->idrecibo ";
    $cheques = $db->getQuery($query);

    print json_encode(['recibo' => $recibo[0], 'facturas' => $facturas, 'cheques' => $cheques]);
});

$app->post('/cp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO detpagorecli(idreccli, numero, idbanco, idmoneda, monto, tipotrans) VALUES($d->idrecibocli, $d->numero, $d->idbanco, $d->idmoneda, $d->monto, $d->idtipotrans)";
    $db->doQuery($query);
});

$app->post('/dp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM detpagorecli WHERE id = $d->id");
});

$app->post('/up', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE detpagorecli SET numero = $d->numero, idbanco = $d->idbanco, idmoneda = $d->idmoneda, 
            monto = $d->monto, tipotrans = $d->idtipotrans WHERE id = $d->id ";
    $db->doQuery($query);
});

$app->get('/getpagorecli/:idrecibo', function($idrecibo){
    $db = new dbcpm();
    $query = "SELECT 
                b.id,
                b.idreccli,
                IFNULL(b.numero, e.descripcion) AS numero,
                IFNULL(c.nombre, '') AS banco,
                d.simbolo AS moneda,
                FORMAT(b.monto, 2) AS monto, 
                e.abreviatura AS tipotrans
            FROM
                recibocli a
                    INNER JOIN
                detpagorecli b ON a.id = b.idreccli
                    LEFT JOIN
                bancopais c ON c.id = b.idbanco
                    INNER JOIN
                moneda d ON d.id = b.idmoneda
                    LEFT JOIN 
                pagosreccli e ON b.tipotrans = e.id
                WHERE
            b.idreccli = $idrecibo ";
    print $db->doSelectASJson($query);
});

$app->get('/getlstrecpend/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT DISTINCT
                    a.id,
                    CONCAT(a.serie,
                            '-',
                            IFNULL(IF(a.serie = 'A', d.seriea, d.serieb),
                                    a.id)) AS reccli,
                    (SELECT 
                            IFNULL(ROUND(SUM(b.monto), 2), 0.00)
                        FROM
                            detcobroventa b
                        WHERE
                            a.id = b.idrecibocli) AS montorec,
                    IFNULL(b.nombre, c.nombre) AS cliente,
                    a.concepto,
                    (SELECT 
                            GROUP_CONCAT(c.serie, '-', c.numero
                                    SEPARATOR ', ')
                        FROM
                            detcobroventa b
                                INNER JOIN
                            factura c ON b.idfactura = c.id
                        WHERE
                            a.id = b.idrecibocli) AS facturas
                FROM
                    recibocli a
                        LEFT JOIN
                    cliente b ON a.idcliente = b.id
                        LEFT JOIN
                    factura c ON a.nit = c.nit
                        LEFT JOIN
                    serierecli d ON d.idrecibocli = a.id
            WHERE
                a.idtranban = 0 AND a.fecha >= 20211101
                    AND a.tipo = 1
                    AND a.anulado = 0
                    AND a.idempresa = $idempresa ";
    print $db->doSelectASJson($query);
});

$app->get('/getlstrec/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT DISTINCT
                a.id,
                CONCAT(a.serie,
                        '-',
                        IFNULL(IF(a.serie = 'A', d.seriea, d.serieb),
                                a.id)) AS reccli,
                (SELECT 
                        IFNULL(ROUND(SUM(b.monto), 2), 0.00)
                    FROM
                        detcobroventa b
                    WHERE
                        a.id = b.idrecibocli) AS montorec,
                IFNULL(b.nombre, c.nombre) AS cliente,
                a.concepto
            FROM
                recibocli a
                    LEFT JOIN
                cliente b ON a.idcliente = b.id
                    LEFT JOIN
                factura c ON a.nit = c.nit
                    LEFT JOIN
                serierecli d ON d.idrecibocli = a.id
            WHERE
                    a.fecha >= 20210101
                    AND a.tipo = 1
                    AND a.anulado = 0
                    AND a.idempresa = $idempresa ";
    print $db->doSelectASJson($query);
});

$app->get('/getpago/:idpago', function($idpago){
    $db = new dbcpm();
    $query = "SELECT 
                id, numero, idbanco, idmoneda, ROUND(monto, 2) AS monto, tipotrans AS idtipotrans
            FROM
                detpagorecli
            WHERE
                id = $idpago ";
    print $db->doSelectASJson($query);
});

$app->run();