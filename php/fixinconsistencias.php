<?php
set_time_limit(0);
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->get('/empdetcont', function() use($db){
    $query = "
        SELECT b.origen, e.id AS idempresadocumento, e.abreviatura AS empresadocumento, a.id AS iddocumento, CONCAT(c.siglas, ' - ', a.tipotrans) AS serie, a.numero AS documento, a.fecha, a.monto, a.concepto, 
        b.id AS iddetallecontable, f.id AS idempresacuentacontable, f.abreviatura AS empresacuentacontable, b.idcuenta, TRIM(d.codigo) AS codigo, b.debe, b.haber 
        FROM tranban a
        INNER JOIN detallecontable b ON a.id = b.idorigen
        INNER JOIN banco c ON c.id = a.idbanco
        INNER JOIN cuentac d ON d.id = b.idcuenta
        INNER JOIN empresa e ON e.id = a.idempresa
        INNER JOIN empresa f ON f.id = d.idempresa
        WHERE b.origen = 1 AND d.idempresa <> c.idempresa
        UNION ALL
        SELECT b.origen, e.id AS idempresadocumento, e.abreviatura AS empresadocumento, a.id AS iddocumento, a.serie, a.documento, a.fechaingreso AS fecha, a.totfact AS monto, a.conceptomayor AS concepto, 
        b.id AS iddetallecontable, f.id AS idempresacuentacontable, f.abreviatura AS empresacuentacontable, b.idcuenta, TRIM(d.codigo) AS codigo, b.debe, b.haber 
        FROM compra a
        INNER JOIN detallecontable b ON a.id = b.idorigen
        INNER JOIN cuentac d ON d.id = b.idcuenta
        INNER JOIN empresa e ON e.id = a.idempresa
        INNER JOIN empresa f ON f.id = d.idempresa
        WHERE b.origen = 2 AND a.idreembolso = 0 AND d.idempresa <> a.idempresa
        UNION ALL
        SELECT b.origen, e.id AS idempresadocumento, e.abreviatura AS empresadocumento, a.id AS iddocumento, a.serie, a.numero AS documento, a.fecha, TRUNCATE(a.total, 2) AS monto, a.conceptomayor AS concepto, 
        b.id AS iddetallecontable, f.id AS idempresacuentacontable, f.abreviatura AS empresacuentacontable, b.idcuenta, TRIM(d.codigo) AS codigo, b.debe, b.haber 
        FROM factura a
        INNER JOIN detallecontable b ON a.id = b.idorigen
        INNER JOIN cuentac d ON d.id = b.idcuenta
        INNER JOIN empresa e ON e.id = a.idempresa
        INNER JOIN empresa f ON f.id = d.idempresa
        WHERE b.origen = 3 AND d.idempresa <> a.idempresa
        UNION ALL
        SELECT b.origen, e.id AS idempresadocumento, e.abreviatura AS empresadocumento, a.id AS iddocumento, NULL AS serie, NULL AS documento, a.fecha, NULL AS monto, a.concepto, 
        b.id AS iddetallecontable, f.id AS idempresacuentacontable, f.abreviatura AS empresacuentacontable, b.idcuenta, TRIM(d.codigo) AS codigo, b.debe, b.haber 
        FROM directa a
        INNER JOIN detallecontable b ON a.id = b.idorigen
        INNER JOIN cuentac d ON d.id = b.idcuenta
        INNER JOIN empresa e ON e.id = a.idempresa
        INNER JOIN empresa f ON f.id = d.idempresa
        WHERE b.origen = 4 AND d.idempresa <> a.idempresa
        UNION ALL
        SELECT b.origen, e.id AS idempresadocumento, e.abreviatura AS empresadocumento, a.id AS iddocumento, a.serie, a.documento, a.fechaingreso AS fecha, a.totfact AS monto, a.conceptomayor AS concepto, 
        b.id AS iddetallecontable, f.id AS idempresacuentacontable, f.abreviatura AS empresacuentacontable, b.idcuenta, TRIM(d.codigo) AS codigo, b.debe, b.haber 
        FROM compra a
        INNER JOIN detallecontable b ON a.id = b.idorigen
        INNER JOIN cuentac d ON d.id = b.idcuenta
        INNER JOIN empresa e ON e.id = a.idempresa
        INNER JOIN empresa f ON f.id = d.idempresa
        WHERE b.origen = 2 AND a.idreembolso > 0 AND d.idempresa <> a.idempresa
    ";

    $documentos = $db->getQuery($query);
    $cntDocumentos = count($documentos);
    $documentosArreglados = [];
    for($i = 0; $i < $cntDocumentos; $i++){
        $doc = $documentos[$i];
        $query = "SELECT id FROM cuentac WHERE idempresa = $doc->idempresadocumento AND TRIM(codigo) = '$doc->codigo'";
        $idctacorrecta = (int)$db->getOneField($query);
        if($idctacorrecta > 0){
            $query = "UPDATE detallecontable SET idcuenta = $idctacorrecta WHERE id = $doc->iddetallecontable";
            $db->doQuery($query);
            $documentosArreglados[] = [
                'empresaDocumento' => $doc->empresadocumento, 'origen' => $doc->origen, 'idDocumento' => $doc->iddocumento,
                'serie' => $doc->serie, 'documento' => $doc->documento, 'fecha' => $doc->fecha
            ];
        }
    }

    print json_encode(['documentosArreglados' => $documentosArreglados]);

});


$app->run();