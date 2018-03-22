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

    //Transacciones bancarias
    $query = "SELECT a.id, c.idtranban, a.idbanco, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, CONCAT(d.siglas, ' ',a.tipotrans, a.numero) AS transaccion, b.debe, b.haber, ";
    $query.= "GROUP_CONCAT(c.idcompra SEPARATOR ', ') AS compras, ";
    $query.= "COUNT(c.idcompra) AS conteofacturas, a.beneficiario ";
    $query.= "FROM tranban a INNER JOIN detallecontable b ON a.id = b.idorigen LEFT JOIN detpagocompra c ON a.id = c.idtranban LEFT JOIN banco d ON d.id = a.idbanco ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND b.origen = 1 AND b.idcuenta = $d->idcuenta AND (c.esrecprov = 0 OR c.esrecprov IS NULL) ";
    $query.= "GROUP BY a.id ";
    $query.= "ORDER BY a.fecha ";
    $cheques = $db->getQuery($query);
    $cntCheques = count($cheques);
    $descuadres = [];
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
                    'beneficiario' => $cheque->beneficiario,
                    'debet' => (float)$cheque->debe,
                    'habert' => (float)$cheque->haber,
                    'idcompras' => !is_null($cheque->compras) ? $cheque->compras : '',
                    'compras' => !is_null($documento->facturas) ? $documento->facturas : '',
                    'debec' => (float)$documento->totdebe,
                    'haberc' => (float)$documento->tothaber,
                    'reembolso' => ((int)$documento->reembolso == 0 ? '' : $documento->reembolso)
                ];
            }
        }else{
            $descuadres[] = [
                'idtranban' => $cheque->id,
                'fecha' => $cheque->fecha,
                'transaccion' => $cheque->transaccion,
                'beneficiario' => $cheque->beneficiario,
                'debet' => (float)$cheque->debe,
                'habert' => (float)$cheque->haber,
                'idcompras' => '',
                'compras' => '',
                'debec' => 0.0,
                'haberc' => 0.0,
                'reembolso' => ''
            ];
        }
    }

    //Compras
    $query = "SELECT a.id AS idcompra, CONCAT(a.serie, '-', a.documento) AS facturas, b.debe AS debec, b.haber AS haberc, 0 AS reembolso, GROUP_CONCAT(c.idtranban SEPARATOR ', ') AS transacciones, COUNT(c.idtranban) AS conteotransacciones ";
    $query.= "FROM compra a INNER JOIN detallecontable b ON a.id = b.idorigen LEFT JOIN detpagocompra c ON a.id = c.idcompra ";
    $query.= "WHERE a.idreembolso = 0 AND a.fechaingreso >= '$d->fdelstr' AND a.fechaingreso <= '$d->falstr' AND b.origen = 2 AND b.idcuenta = $d->idcuenta ";
    $query.= "GROUP BY a.id ";
    $query.= "HAVING conteotransacciones <> 1 ";
    $query.= "ORDER BY a.fechaingreso";
    $compras = $db->getQuery($query);
    $cntCompras = count($compras);
    for($i = 0; $i < $cntCompras; $i++){
        $compra = $compras[$i];
        if((int)$compra->conteotransacciones > 0){
            $query = "SELECT GROUP_CONCAT(DISTINCT a.id SEPARATOR ', ') AS idtranban, GROUP_CONCAT(CONCAT(c.siglas, ' ', a.tipotrans, a.numero) SEPARATOR ', ') AS transaccion, ";
            $query.= "GROUP_CONCAT(DISTINCT a.beneficiario SEPARATOR ', ') AS beneficiario, SUM(b.debe) AS totdebe, SUM(b.haber) AS tothaber, ";
            $query.= "GROUP_CONCAT(DISTINCT DATE_FORMAT(a.fecha, '%d/%m/%Y') SEPARATOR ', ') AS fecha ";
            $query.= "FROM tranban a INNER JOIN detallecontable b ON a.id = b.idorigen INNER JOIN banco c ON c.id = a.idbanco ";
            $query.= "WHERE a.id IN($compra->transacciones) AND b.origen = 1 AND b.idcuenta = $d->idcuenta ";
            $documentos = $db->getQuery($query);
            $cntDocumentos = count($documentos);
            if($cntDocumentos > 0){
                $documento = $documentos[0];
                if(((float)$compra->debec != (float)$documento->tothaber) || ((float)$compra->haberc != (float)$documento->totdebe)){
                    $descuadres[] = [
                        'idtranban' => $documento->idtranban,
                        'fecha' => $documento->fecha,
                        'transaccion' => $documento->transaccion,
                        'beneficiario' => $documento->beneficiario,
                        'debet' => (float)$documento->totdebe,
                        'habert' => (float)$documento->tothaber,
                        'idcompras' => $compra->idcompra,
                        'compras' =>  $compra->facturas,
                        'debec' => (float)$compra->debec,
                        'haberc' => (float)$compra->haberc,
                        'reembolso' => ''
                    ];
                }
            }else{
                $descuadres[] = [
                    'idtranban' => '',
                    'fecha' => '',
                    'transaccion' => '',
                    'beneficiario' => '',
                    'debet' => 0.0,
                    'habert' => 0.0,
                    'idcompras' => $compra->idcompra,
                    'compras' => $compra->facturas,
                    'debec' => (float)$compra->debec,
                    'haberc' => (float)$compra->haberc,
                    'reembolso' => ''
                ];
            }
        }else{
            $descuadres[] = [
                'idtranban' => '',
                'fecha' => '',
                'transaccion' => '',
                'beneficiario' => '',
                'debet' => 0.0,
                'habert' => 0.0,
                'idcompras' => $compra->idcompra,
                'compras' => $compra->facturas,
                'debec' => (float)$compra->debec,
                'haberc' => (float)$compra->haberc,
                'reembolso' => ''
            ];
        }
    }

    $sumas = ['debet' => 0.0, 'habert' => 0.0, 'debec' => 0.0, 'haberc' => 0.0];
    $cntDescuadres = count($descuadres);
    for($i = 0; $i < $cntDescuadres; $i++){
        $descuadre = $descuadres[$i];
        $sumas['debet'] += $descuadre['debet'];
        $descuadres[$i]['debet'] = number_format($descuadre['debet'], 2);
        $sumas['habert'] += $descuadre['habert'];
        $descuadres[$i]['habert'] = number_format($descuadre['habert'], 2);
        $sumas['debec'] += $descuadre['debec'];
        $descuadres[$i]['debec'] = number_format($descuadre['debec'], 2);
        $sumas['haberc'] += $descuadre['haberc'];
        $descuadres[$i]['haberc'] = number_format($descuadre['haberc'], 2);
    }

    $descuadres[] = [
        'idtranban' => '',
        'fecha' => '',
        'transaccion' => '',
        'beneficiario' => 'Total transacciones:',
        'debet' => number_format($sumas['debet'], 2),
        'habert' => number_format($sumas['habert'], 2),
        'idcompras' => '',
        'compras' => 'Total facturas:',
        'debec' => number_format($sumas['debec'], 2),
        'haberc' => number_format($sumas['haberc'], 2),
        'reembolso' => ''
    ];

    print json_encode(['generales' => $generales, 'documentos' => $descuadres]);
});

$app->run();