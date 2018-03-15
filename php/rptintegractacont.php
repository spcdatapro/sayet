<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/integra', function()use($db){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT a.nomempresa AS empresa, DATE_FORMAT('$d->fdelstr', '%d/%m/%Y') AS del, DATE_FORMAT('$d->falstr', '%d/%m/%Y') AS al, b.codigo, b.nombrecta ";
    $query.= "FROM empresa a INNER JOIN cuentac b ON a.id = b.idempresa WHERE b.id = $d->idcuenta";
    //print $query;
    $generales = $db->getQuery($query)[0];

    $query = "SELECT a.id, c.idtranban, a.idbanco, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, CONCAT(d.siglas, ' ',a.tipotrans, a.numero) AS transaccion, b.debe, b.haber, ";
    $query.= "GROUP_CONCAT(c.idcompra SEPARATOR ', ') AS compras, ";
    $query.= "COUNT(c.idcompra) AS conteofacturas ";
    $query.= "FROM tranban a INNER JOIN detallecontable b ON a.id = b.idorigen LEFT JOIN detpagocompra c ON a.id = c.idtranban LEFT JOIN banco d ON d.id = a.idbanco ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND b.origen = 1 AND b.idcuenta = $d->idcuenta AND (c.esrecprov = 0 OR c.esrecprov IS NULL) ";
    $query.= "GROUP BY a.id ";
    $query.= "ORDER BY a.fecha ";
    $cheques = $db->getQuery($query);
    $cntCheques = count($cheques);
    $descudres = [];
    for($i = 0; $i < $cntCheques; $i++){
        $cheque = $cheques[$i];
        if((int)$cheque->conteofacturas > 0){
            $query = "SELECT GROUP_CONCAT(CONCAT(a.serie, '-', a.documento) SEPARATOR ', ') AS facturas, SUM(b.debe) AS totdebe, SUM(b.haber) AS tothaber, 0 AS reembolso ";
            $query.= "FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen ";
            $query.= "WHERE a.id IN($cheque->compras) AND b.origen = 2 AND b.idcuenta = $d->idcuenta";
        }else{
            $query = "SELECT GROUP_CONCAT(CONCAT(a.serie, '', a.documento) SEPARATOR ', ') AS facturas, SUM(c.debe) AS totdebe, SUM(c.haber) AS tothaber, b.id AS reembolso ";
            $query.= "FROM compra a INNER JOIN reembolso b ON b.id = a.idreembolso INNER JOIN detallecontable c ON a.id = c.idorigen ";
            $query.= "WHERE b.idtranban = $cheque->id AND b.esrecprov = 0 AND c.origen = 2 AND c.idcuenta = $d->idcuenta";
        }
        //print $query;
        $documentos = $db->getQuery($query);
        $cntDocumentos = count($documentos);
        if($cntDocumentos > 0){
            $documento = $documentos[0];
            if(((float)$cheque->debe != (float)$documento->tothaber) || ((float)$cheque->haber != (float)$documento->totdebe)){
                $descuadres[] = [
                    'idtranban' => $cheque->id,
                    'fecha' => $cheque->fecha,
                    'transaccion' => $cheque->transaccion,
                    'debet' => number_format((float)$cheque->debe, 2),
                    'habert' => number_format((float)$cheque->haber, 2),
                    'idcompras' => !is_null($cheque->compras) ? $cheque->compras : '',
                    'compras' => !is_null($documento->facturas) ? $documento->facturas : '',
                    'debec' => number_format((float)$documento->totdebe, 2),
                    'haberc' => number_format((float)$documento->tothaber, 2),
                    'reembolso' => ((int)$documento->reembolso == 0 ? '' : $documento->reembolso)
                ];
            }
        }else{
            $descuadres[] = [
                'idtranban' => $cheque->id,
                'fecha' => $cheque->fecha,
                'transaccion' => $cheque->transaccion,
                'debet' => number_format((float)$cheque->debe, 2),
                'habert' => number_format((float)$cheque->haber, 2),
                'idcompras' => '',
                'compras' => 'N/A',
                'debec' => number_format((float)0, 2),
                'haberc' => number_format((float)0, 2),
                'reembolso' => ''
            ];
        }
    }

    print json_encode(['generales' => $generales, 'documentos' => $descuadres]);
});

$app->run();