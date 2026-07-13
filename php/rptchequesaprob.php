<?php
require 'vendor/autoload.php';
require_once 'db.php';
require 'Reportes.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/getcheques', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT a.numero, DATE_FORMAT(a.fecha, '%d/%m/%Y') AS fecha, a.monto, REPLACE(b.nocuenta, '-', '') AS nocuenta, ";
    $query.= "SUBSTR(CONCAT(TRIM(limpiaString(c.abreviatura)), ' ', TRIM(limpiaString(a.beneficiario)), ' ', TRIM(limpiaString(a.concepto))), 1, 57) AS descripcion ";
    $query.= "FROM tranban a INNER JOIN banco b ON b.id = a.idbanco INNER JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE a.tipotrans = 'C' AND a.anulado = 0 AND a.fecha = '$d->fechastr' AND UPPER(TRIM(b.nombre)) LIKE '%$d->banco%' AND b.idmoneda = $d->idmoneda ";    
    $query.= (int)$d->idempresa == 0 ? "" : "AND b.idempresa = $d->idempresa ";
    $query.= "ORDER BY b.ordensumario, a.numero";
    // print $query;
    print $db->doSelectAsJSON($query);
});

$app->get('/gettxt/:idempresa/:fechastr/:idmoneda/:nombre(/:idbanco)', function($idempresa, $fechastr, $idmoneda, $nombre, $idbanco = 0) use($app){
    $db = new dbcpm();
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/csv;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.trim($nombre).'.csv"');

    $banco = 'INDUSTRIAL';
    if((int)$idbanco > 0) {
        $query = "SELECT nombre FROM banco WHERE id = $idbanco";
        $banco = $db->getOneField($query);
    }    

    //$url = 'http://104.197.209.57:5489/api/report';
    $url = 'http://localhost:5489/api/report';
    $data = ['template' => ['shortid' => 'B1ICfUfDb'], 'data' => ['idempresa' => "$idempresa", 'fechastr' => "$fechastr", 'idmoneda' => "$idmoneda", 'banco' => $banco]];
    //print json_encode($data);

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    // Limpieza previa
    $respuesta_limpia = limpiarTextoCSV($respuesta);
    //print iconv('UTF-8','Windows-1252', preg_replace('/[^\P{C}\n]+/u', '', $respuesta));
	print iconv('UTF-8','Windows-1252', $respuesta_limpia);
});

$app->get('/gettxt_notas/:fechastr/:idbanco/:nombre', function($fechastr, $idbanco, $nombre) use($app){
    $db = new dbcpm();
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/csv;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.trim($nombre).'.csv"'); 

    $url = 'http://localhost:5489/api/report';
    $data = ['template' => ['shortid' => 'SyxKX2f4Ge'], 'data' => [ 'fechastr' => "$fechastr", 'idbanco' => $idbanco]];

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
	print iconv('UTF-8','Windows-1252', $respuesta);
});

$app->post('/getnotasd', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT 
                a.id, IFNULL(e.cuentabanco, 'n/e') AS cuenta, e.nombre, e.concepto, e.correo, a.monto
            FROM
                tranban a
                    INNER JOIN
                banco b ON a.idbanco = b.id
                    INNER JOIN
                detpagocompra c ON c.idtranban = a.id
                    INNER JOIN
                compra d ON c.idcompra = d.id
                    INNER JOIN
                proveedor e ON d.idproveedor = e.id
            WHERE
                a.fecha = '$d->fechastr' AND a.idbanco = $d->idbanco
                AND a.tipotrans = 'B'
            GROUP BY a.id";
    print $db->doSelectAsJSON($query);
});

$app->post('/aprobados_old', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
    $promedido = [];
    $totales = ['monto_factura', 'iva', 'monto_cheque'];

    // estampa
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $query = "SELECT 
                a.id,
                YEAR(a.fechafactura) AS idempresa,
                YEAR(a.fechafactura) AS empresa,
                NULL AS abreviatura,
                NULL AS numero,
                a.mesiva AS mes,
                a.conceptomayor AS concepto,
                DATE_FORMAT(fechafactura, '%d/%m/%Y') AS fecha,
                a.serie,
                a.documento AS factura,
                IFNULL(c.numban, c.numero) AS tran,
                a.subtotal AS monto_factura,
                a.iva,
                c.monto AS monto_cheque,
                DATE_FORMAT(c.fecha, '%d/%m/%Y') AS fecha_elaborado,
                IFNULL(d.iniciales, 'N.E') AS elaborado_por,
                IF(c.revisado > 0, TRUE, FALSE) AS revisado,
                IFNULL(e.iniciales, '') AS revisado_por,
                IF(c.autorizado > 0, TRUE, FALSE) AS autorizado,
                IFNULL(f.iniciales, '') AS autorizado_por,
                IFNULL(DATE_FORMAT(c.fecha_autorizado, '%d/%m/%Y'), '') AS fecha_autorizado,
                g.nomempresa,
                h.nombre AS proveedor,
                i.nomproyecto AS proyecto,
                c.tipotrans
            FROM
                compra a
                    INNER JOIN
                detpagocompra b ON b.idcompra = a.id
                    INNER JOIN
                tranban c ON b.idtranban = c.id
                    LEFT JOIN
                usuario d ON c.idusuario = d.id
                    LEFT JOIN
                usuario e ON c.revisado = e.id
                    LEFT JOIN
                usuario f ON c.autorizado = f.id
                    INNER JOIN
                empresa g ON a.idempresa = g.id
                    INNER JOIN
                proveedor h ON a.idproveedor = h.id
                    INNER JOIN 
                proyecto i ON a.idproyecto = i.id
            WHERE
                a.idproveedor = $d->idproveedor ";
    $query.= isset($d->anio_inicial) && isset($d->anio_final) ? " AND YEAR(a.fechafactura) BETWEEN $d->anio_inicial AND $d->anio_final " : ""; 
    $query.="       AND (a.idreembolso = 0
                    OR a.idreembolso IS NULL)
                    AND a.idempresa = $d->idempresa
                    AND a.idproyecto = $d->idproyecto
                    AND (a.ordentrabajo IS NULL OR a.ordentrabajo = 0)
            ORDER BY a.fechafactura ASC";
            echo $query; return;
    $data = $db->getQuery($query);

    if (count($data) > 0) {
        // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
        $reporte = new GeneradorReportes($data, 'transacciones', $totales);
        $transacciones = $reporte->getReporte();
        $montos_generales = $reporte->getTotalesGenerales();
        $success = true;
    } else {
        $transacciones = 'No se recibieron datos';
        $success = false;
    }


    $letra->proveedor = $data[0]->proveedor;
    $letra->empresa = $data[0]->nomempresa;
    $letra->proyecto = $data[0]->proyecto;

    foreach($data as $compra) {
        $compra->mes = $meses[$compra->mes - 1];
        array_push($promedido, $compra->monto_cheque);
    }

    print json_encode(['encabezado' => $letra, 'data' => $transacciones]);
});

$app->post('/aprobados', function () {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
    $promedido = [];
    $totales = ['monto_factura', 'iva', 'monto_cheque'];
    $idproyecto = isset($d->idproyecto) ? implode(',', $d->idproyecto) : 0;

    // estampa
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->anio = "Del ".$d->fecha_inicialstr." al ".$d->fecha_finalstr;
    // $letra->agrupar = $d->ver == 1 ? 'Año' : ($d->ver == 2 ? 'Rango de años' : 'Todos los años');

    $query = "SELECT 
                a.id,
                h.id AS idempresa,
                h.nombre AS empresa,
                g.nomempresa AS abreviatura,
                i.nomproyecto AS numero,
                YEAR(a.fechafactura) AS idproyecto,
                YEAR(a.fechafactura) AS proyecto,
                a.mesiva AS mes,
                a.conceptomayor AS concepto,
                DATE_FORMAT(fechafactura, '%d/%m/%Y') AS fecha,
                a.serie,
                a.documento AS factura,
                IF(c.numban = 0 OR c.numban IS NULL, c.numero, c.numban) AS tran,
                a.subtotal AS monto_factura,
                a.iva,
                b.monto AS monto_cheque,
                DATE_FORMAT(c.fecha, '%d/%m/%Y') AS fecha_elaborado,
                IFNULL(d.iniciales, 'N.E') AS elaborado_por,
                IF(c.revisado > 0, TRUE, FALSE) AS revisado,
                IFNULL(e.iniciales, '') AS revisado_por,
                IFNULL(DATE_FORMAT(c.fecha_revisado, '%d/%m/%Y'),
                        '') AS fecha_revisado,
                IF(c.autorizado > 0, TRUE, FALSE) AS autorizado,
                IFNULL(f.iniciales, '') AS autorizado_por,
                IFNULL(DATE_FORMAT(c.fecha_autorizado, '%d/%m/%Y'),
                        '') AS fecha_autorizado,
                g.nomempresa,
                h.nombre AS proveedor,
                i.nomproyecto,
                c.tipotrans
            FROM
                compra a
                    INNER JOIN
                detpagocompra b ON b.idcompra = a.id
                    INNER JOIN
                tranban c ON b.idtranban = c.id
                    LEFT JOIN
                usuario d ON c.idusuario = d.id
                    LEFT JOIN
                usuario e ON c.revisado = e.id
                    LEFT JOIN
                usuario f ON c.autorizado = f.id
                    INNER JOIN
                empresa g ON a.idempresa = g.id
                    INNER JOIN
                proveedor h ON a.idproveedor = h.id
                    INNER JOIN
                proyecto i ON a.idproyecto = i.id
                    LEFT JOIN 
                detcontprov j ON h.id = j.idproveedor
                    LEFT JOIN
                cuentac k ON j.idcuentac = k.id
            WHERE
                a.fechafactura BETWEEN '$d->fecha_inicialstr' AND '$d->fecha_finalstr'
                    AND (a.idreembolso = 0
                    OR a.idreembolso IS NULL) 
                    AND h.hoja_control = 1 
                    ";
    $query.= isset($d->idempresa) ? "AND a.idempresa = $d->idempresa " : "";
    $query.= isset($d->idproveedor) ? "AND a.idproveedor = $d->idproveedor " : ""; 
    $query.= $idproyecto > 0 ? "AND a.idproyecto IN($idproyecto) " : "";
    $query.= isset($d->idcuenta) ? "AND j.idcuentac = $d->idcuenta " : "";
    $query.="       AND (a.ordentrabajo IS NULL
                    OR a.ordentrabajo = 0)
            GROUP BY a.id
            ORDER BY g.nomempresa , i.nomproyecto , h.nombre , a.fechafactura ASC";
            // echo $query; return;
    $data = $db->getQuery($query);

    if (count($data) > 0) {
        $correlativos = [];
        $nextCorrelativo = 1;

        foreach ($data as $c) {
            // clave compuesta: empresa + proveedor + proyecto
            $key = $c->nomempresa . '|' . $c->proveedor . '|' . $c->nomproyecto;
        
            // asignar correlativo único por combinación
            if (!isset($correlativos[$key])) {
                $correlativos[$key] = $nextCorrelativo++;
            }
            $c->idempresa = $correlativos[$key];
        
            // contar cuántos documentos hay con la misma combinación
            $cnt = 0;
            foreach ($data as $x) {
                if ($x->nomempresa === $c->nomempresa &&
                    $x->proveedor === $c->proveedor &&
                    $x->nomproyecto === $c->nomproyecto) {
                    $cnt++;
                }
            }
        
            // meses del periodo (usa el campo mes de cada registro)
            $months = max(1, (int)$c->mes);
        
            if ($cnt === 0) {
                $c->cuantos = 'Sin movimientos';
                continue;
            }
        
            // frecuencia media: meses / cantidad de documentos
            $freq = $months / $cnt;
        
            if ($freq <= 1.25) {
                $c->cuantos = 'Mensual';
            } elseif ($freq <= 2.25) {
                $c->cuantos = 'Bimensual';
            } elseif ($freq <= 3.25) {
                $c->cuantos = 'Trimestral';
            } elseif ($freq <= 6.5) {
                $c->cuantos = 'Semestral';
            } elseif ($cnt < $months) {
                $c->cuantos = 'Ocasional';
            } else {
                $c->cuantos = 'Varios movimientos';
            }
        }

        // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
        $reporte = new GeneradorReportes($data, 'transacciones', $totales, true);
        $transacciones = $reporte->getReporte();
        $montos_generales = $reporte->getTotalesGenerales();
        $success = true;
    } else {
        $transacciones = 'No se recibieron datos';
        $success = false;
    }


    $letra->proveedor = $data[0]->proveedor;
    $letra->empresa = $data[0]->nomempresa;
    $letra->proyecto = $data[0]->nomproyecto;

    foreach($data as $compra) {
        $compra->mes = $meses[$compra->mes - 1];
        array_push($promedido, $compra->monto_cheque);
    }

    print json_encode(['encabezado' => $letra, 'data' => $transacciones]);
});

$app->post('/comparativo', function () {
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));

    $totales = ['monto_factura', 'iva', 'monto_cheque'];
    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
    $idproyecto = isset($d->idproyecto) ? implode(',', $d->idproyecto) : 0;

    // estampa
    $letra = new stdClass();
    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');
    $letra->rango = "Comparacion de ". $meses[$d->mes_comparar - 1] ." con ". $meses[$d->mes - 1] ." del ". $d->anio;

    $query = "SELECT 
                a.id,
                e.id AS idempresa,
                e.nombre AS empresa,
                d.nomempresa AS abreviatura,
                f.nomproyecto AS numero,
                MONTH(a.fechafactura) AS idproyecto,
                MONTH(a.fechafactura) AS proyecto,
                MONTH(a.fechafactura) AS mes,
                a.conceptomayor AS concepto,
                DATE_FORMAT(fechafactura, '%d/%m/%Y') AS fecha,
                a.serie,
                a.documento AS factura,
                IF(c.numban = 0 OR c.numban IS NULL,
                    c.numero,
                    c.numban) AS tran,
                a.subtotal AS monto_factura,
                a.iva,
                b.monto AS monto_cheque,
                d.nomempresa,
                e.nombre AS proveedor,
                f.nomproyecto,
                c.tipotrans
            FROM
                compra a
                    INNER JOIN
                detpagocompra b ON b.idcompra = a.id
                    INNER JOIN
                tranban c ON b.idtranban = c.id
                    INNER JOIN
                empresa d ON a.idempresa = d.id
                    INNER JOIN
                proveedor e ON a.idproveedor = e.id
                    INNER JOIN
                proyecto f ON a.idproyecto = f.id
                    LEFT JOIN 
                detcontprov j ON e.id = j.idproveedor
            WHERE
                YEAR(a.fechafactura) = $d->anio
                    AND (a.idreembolso = 0
                    OR a.idreembolso IS NULL)
                    AND a.idempresa = $d->idempresa
                    AND MONTH(a.fechafactura) IN ($d->mes , $d->mes_comparar) ";
    $query.= $idproyecto > 0 ? "AND a.idproyecto IN($idproyecto) " : "";  
    $query.= isset($d->idcuenta) ? "AND j.idcuentac = $d->idcuenta " : "";  
    $query.= "AND (a.ordentrabajo IS NULL
                    OR a.ordentrabajo = 0)
                    AND e.hoja_control = 1
            ORDER BY e.id , MONTH(a.fechafactura)";
    $data = $db->getQuery($query);

    if (count($data) > 0) {
        $correlativos = [];
        $nextCorrelativo = 1;
        $facturasPorMes = [];
        $facturasPorMesMonto = [];
        $metaPorKey = [];
        $cuantosPorKey = [];
        $diferentePorKey = [];
        $diferenciaPorKey = [];
        $mesesComparados = array_values(array_unique([(int)$d->mes_comparar, (int)$d->mes]));
        $mesesPeriodo = count(array_unique($mesesComparados));

        foreach ($data as $c) {
            $key = $c->nomempresa . '|' . $c->proveedor . '|' . $c->nomproyecto;

            if (!isset($correlativos[$key])) {
                $correlativos[$key] = $nextCorrelativo++;
                $metaPorKey[$key] = [
                    'idempresa' => $correlativos[$key],
                    'empresa' => $c->empresa,
                    'abreviatura' => $c->abreviatura,
                    'numero' => $c->numero,
                    'concepto' => $c->concepto,
                    'nomempresa' => $c->nomempresa,
                    'proveedor' => $c->proveedor,
                    'nomproyecto' => $c->nomproyecto,
                    'tipotrans' => $c->tipotrans
                ];
            }

            $c->idempresa = $correlativos[$key];
            $c->idproyecto = (int)$c->mes;
            $c->proyecto = $meses[$c->idproyecto - 1];

            if (!isset($facturasPorMes[$key])) {
                $facturasPorMes[$key] = [];
            }
            if (!isset($facturasPorMes[$key][$c->idproyecto])) {
                $facturasPorMes[$key][$c->idproyecto] = 0;
            }
            $facturasPorMes[$key][$c->idproyecto]++;

            if (!isset($facturasPorMesMonto[$key])) {
                $facturasPorMesMonto[$key] = [];
            }
            if (!isset($facturasPorMesMonto[$key][$c->idproyecto])) {
                $facturasPorMesMonto[$key][$c->idproyecto] = 0;
            }
            $facturasPorMesMonto[$key][$c->idproyecto] += $c->monto_cheque;
        }

        foreach ($metaPorKey as $key => $meta) {
            $mesA = (int)$d->mes;
            $mesB = (int)$d->mes_comparar;
            $cntMesA = $facturasPorMes[$key][$mesA] ?? 0;
            $cntMesB = $facturasPorMes[$key][$mesB] ?? 0;
            $cntTotal = $cntMesA + $cntMesB;

            if ($cntTotal === 0) {
                $cuantosPorKey[$key] = 'Sin movimientos';
            } else {
                $freq = max(1, $mesesPeriodo) / $cntTotal;
                if ($freq <= 1.25) {
                    $cuantosPorKey[$key] = 'Mensual';
                } elseif ($freq <= 2.25) {
                    $cuantosPorKey[$key] = 'Bimensual';
                } elseif ($freq <= 3.25) {
                    $cuantosPorKey[$key] = 'Trimestral';
                } elseif ($freq <= 6.5) {
                    $cuantosPorKey[$key] = 'Semestral';
                } elseif ($cntTotal < $mesesPeriodo) {
                    $cuantosPorKey[$key] = 'Ocasional';
                } else {
                    $cuantosPorKey[$key] = 'Varios movimientos';
                }
            }

            $diferentePorKey[$key] = ($cntMesA !== $cntMesB) ? 1 : 0;
            $diferenciaPorKey[$key] = (($facturasPorMesMonto[$key][$mesA] ?? 0) - ($facturasPorMesMonto[$key][$mesB] ?? 0));

            foreach ($mesesComparados as $mesComparado) {
                if (($facturasPorMes[$key][$mesComparado] ?? 0) > 0) {
                    continue;
                }

                $vacio = new stdClass();
                $vacio->id = 0;
                $vacio->idempresa = $meta['idempresa'];
                $vacio->empresa = $meta['empresa'];
                $vacio->abreviatura = $meta['abreviatura'];
                $vacio->numero = $meta['numero'];
                $vacio->idproyecto = (int)$mesComparado;
                $vacio->proyecto = $meses[$mesComparado - 1];
                $vacio->mes = (int)$mesComparado;
                $vacio->concepto = 'Sin transaccion';
                $vacio->fecha = '';
                $vacio->serie = '';
                $vacio->factura = '';
                $vacio->tran = '';
                $vacio->monto_factura = 0;
                $vacio->iva = 0;
                $vacio->monto_cheque = 0;
                $vacio->nomempresa = $meta['nomempresa'];
                $vacio->proveedor = $meta['proveedor'];
                $vacio->nomproyecto = $meta['nomproyecto'];
                $vacio->tipotrans = $meta['tipotrans'];
                $vacio->cuantos = $cuantosPorKey[$key];
                $vacio->diferente = $diferentePorKey[$key];
                $vacio->diferencia = $diferenciaPorKey[$key];

                $data[] = $vacio;
            }
        }

        foreach ($data as $c) {
            $key = $c->nomempresa . '|' . $c->proveedor . '|' . $c->nomproyecto;
            $c->cuantos = $cuantosPorKey[$key] ?? 'Sin movimientos';
            $c->diferente = $diferentePorKey[$key] ?? 0;
            $c->diferencia = $diferenciaPorKey[$key] ?? 0;
            $c->idproyecto = (int)$c->mes;
            $c->proyecto = $meses[$c->idproyecto - 1];
        }

        usort($data, function ($a, $b) use ($mesesComparados) {
            if ($a->idempresa !== $b->idempresa) {
                return $a->idempresa <=> $b->idempresa;
            }

            $ordenMesA = array_search((int)$a->mes, $mesesComparados);
            $ordenMesB = array_search((int)$b->mes, $mesesComparados);

            $ordenMesA = $ordenMesA === false ? 99 : $ordenMesA;
            $ordenMesB = $ordenMesB === false ? 99 : $ordenMesB;

            if ($ordenMesA !== $ordenMesB) {
                return $ordenMesA <=> $ordenMesB;
            }

            return ((int)$a->id) <=> ((int)$b->id);
        });

        // funcion contructora para reporteria espera: datos de la bd, nombre de los datos, nombre en array de los montos que se quire total, si se agrupa por proyecto (opcional)
        $reporte = new GeneradorReportes($data, 'transacciones', $totales, true);
        $transacciones = $reporte->getReporte();
        $montos_generales = $reporte->getTotalesGenerales();
        $success = true;
    } else {
        $transacciones = 'No se recibieron datos';
        $success = false;
    }

    print json_encode(['data' => $transacciones, 'encabezado' => $letra]);
});

function limpiarTextoCSV($texto) {
    // Mapa de sustitución de caracteres problemáticos
    $map = [
        'А' => 'A', // cirílica → latina
        'В' => 'B',
        'С' => 'C',
        'Е' => 'E',
        'Н' => 'H',
        'К' => 'K',
        'М' => 'M',
        'О' => 'O',
        'Р' => 'P',
        'Т' => 'T',
        'Х' => 'X',
        'Ь' => '',  // eliminar si aparece
    ];

    // Sustituir caracteres según el mapa
    $texto = strtr($texto, $map);

    // Eliminar caracteres de control invisibles
    $texto = preg_replace('/[^\P{C}\n]+/u', '', $texto);

    // Normalizar a UTF-8 limpio
    return $texto;
}


$app->run();