<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$db = new dbcpm();

$app->post('/generar', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $query = "SELECT z.tipo, z.cuenta, LPAD(@row := @row + 1, 20, ' ') AS contador, z.nombre, z.monto, 
    CONCAT('PLANILLA DEL ', LPAD(DAY('$d->fdelstr'), 2, ' '), ' DE ', 
    (SELECT nombre FROM mes WHERE id = MONTH('$d->fdelstr')), ' AL ', LPAD(DAY('$d->falstr'), 2, ' '), ' DE ', 
    (SELECT nombre FROM mes WHERE id = MONTH('$d->falstr')), ' DEL ', YEAR('$d->falstr')) AS concepto 
    FROM (SELECT 1 AS tipo, LPAD(TRIM(e.cuentabanco), 10, ' ') AS cuenta, RPAD(CONCAT(d.primernombre, ' ', 
    IFNULL(d.segundonombre, ''), ' ', IFNULL(d.tercernombre, ''), ' ', IFNULL(d.primerapellido, ''), ' ', IFNULL(d.segundoapellido, ''), ' ', IFNULL(d.apellidocasada, '')), 100, ' ') AS nombre, 
    LPAD(a.liquido, 25,' ') AS monto FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado 
    INNER JOIN plnpersonal d ON b.idpersonal = d.id INNER JOIN plnlaboral e ON b.idlaboral = e.id 
    LEFT JOIN plnpuesto c ON c.id = b.idplnpuesto WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' 
    AND a.fecha <= '$d->falstr' AND a.liquido <> 0 AND e.cuentabanco IS NOT NULL AND e.metodo = 'nota debito' 
    ORDER BY c.descripcion, b.nombre, b.apellidos, a.fecha) z, (SELECT @row:= 0) r";
    print $db->doSelectASJson($query);
});

$app->get('/gettxt/:idempresa/:fdelstr/:falstr/:nombre', function($idempresa, $fdelstr, $falstr, $nombre) use($app, $db){
    $app->response->headers->clear();
    $app->response->headers->set('Content-Type', 'text/plain;charset=windows-1252');
    $app->response->headers->set('Content-Disposition', 'attachment;filename="'.$nombre.'.txt"');

    $url = 'http://localhost:5489/api/report';
    //$data = ['template' => ['shortid' => 'B1BikIhjG'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr"]];
    $data = ['template' => ['shortid' => 'BJty9IhoM'], 'data' => ['idempresa' => "$idempresa", 'fdelstr' => "$fdelstr", 'falstr' => "$falstr"]];

    $respuesta = $db->CallJSReportAPI('POST', $url, json_encode($data));
    print iconv('UTF-8','Windows-1252', $respuesta);
});

function getCuentaConfig($idempresa, $idconfig){
    $db = new dbcpm();
    return (int)$db->getOneField("SELECT idcuentac FROM detcontempresa WHERE idempresa = $idempresa AND idtipoconfig = $idconfig");
}

function insertaDetalleContable($origen, $iddocto, $idcuentac, $debe, $haber, $conceptomayor, $activada, $anulado){
    $db = new dbcpm();
    $query = "INSERT INTO detallecontable(";
    $query.= "origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado";
    $query.= ") VALUES(";
    $query.= "$origen, $iddocto, $idcuentac, $debe, $haber, '$conceptomayor', $activada, $anulado";
    $query.= ")";
    $db->doQuery($query);
}

function genDetContDoc($db, $d, $idtranban, $concepto, $idctabanco, $mediopago, $idempleado = 0){

    $mediopago = (int)$mediopago === 3 ? 'nota debito' : 'cheque';

    $query = "SELECT b.idempresadebito AS deptodeb, a.fecha, SUM(a.anticipo) AS anticipo, SUM(a.sueldoordinario) AS suel_ord, SUM(a.sueldoextra) AS suel_ext, SUM(a.bonificacion) AS bonifica, ";
    $query.= "SUM(a.viaticos) AS viaticos, SUM(a.otrosingresos) AS otros_ingr, SUM(a.vacaciones) AS vacaciones, SUM(a.aguinaldo) AS aguinaldo, SUM(a.bonocatorce) AS bono_14, SUM(a.indemnizacion) AS indemniza, ";
    $query.= "SUM(a.descigss) AS desc_igss, SUM(a.descisr) AS desc_isr, SUM(a.descanticipo) AS desc_anti, SUM(a.descprestamo) AS desc_prest, SUM(a.descotros) AS otros_desc, SUM(a.liquido) AS liquido, SUM(a.descembargo) AS desc_embargos, ";
    $query.= "ROUND(SUM((a.sueldoordinario + a.sueldoextra + a.vacaciones) * c.patronaligss), 2) AS cuotapatronaligss ";
    $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado INNER JOIN plnlaboral d ON b.idlaboral = d.id INNER JOIN plnempresa c ON c.id = d.idempresaactual ";
    $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' ";
    $query.= $mediopago == 'nota debito' ? "AND d.cuentabanco IS NOT NULL AND d.cuentabanco > 0 " : '';
    $query.= "AND d.metodo = '$mediopago' AND a.idempresa = $d->idempresa ";
    $query.= $idempleado == 0 ? '' : "AND a.idplnempleado = $idempleado ";
    // print $query;
    $sumas = $db->getQuery($query);
    if(count($sumas) > 0){
        $suma = $sumas[0];
        $origen = 1;

        if((float)$suma->anticipo != 0.00){
            $ctaAnticipo = getCuentaConfig($d->idempresa, 15);
            if($ctaAnticipo > 0){
                insertaDetalleContable($origen, $idtranban, $ctaAnticipo, $suma->anticipo, 0.00, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->suel_ord != 0.00){
            $ctaSueldosOrdinarios = getCuentaConfig($d->idempresa, 16);
            if($ctaSueldosOrdinarios > 0){
                insertaDetalleContable($origen, $idtranban, $ctaSueldosOrdinarios, $suma->suel_ord, 0.00, "PLANILLA $concepto", 1, 0);
            }

        }

        if((float)$suma->suel_ext != 0.00){
            $ctaSueldosExtraordinarios = getCuentaConfig($d->idempresa, 17);
            if($ctaSueldosExtraordinarios > 0){
                insertaDetalleContable($origen, $idtranban, $ctaSueldosExtraordinarios, $suma->suel_ext, 0.00, "PLANILLA $concepto", 1, 0);
            }

        }

        if((float)$suma->bonifica != 0.00 || (float)$suma->viaticos != 0.00 || (float)$suma->otros_ingr != 0.00){
            $otrosIngresos = (float)$suma->bonifica + (float)$suma->viaticos + (float)$suma->otros_ingr;
            $ctaOtrosIngresos = getCuentaConfig($d->idempresa, 18);
            if($ctaOtrosIngresos > 0){
                insertaDetalleContable($origen, $idtranban, $ctaOtrosIngresos, $otrosIngresos, 0.00, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->vacaciones != 0.00){
            $ctaVacaciones = getCuentaConfig($d->idempresa, 19);
            if($ctaVacaciones > 0){
                insertaDetalleContable($origen, $idtranban, $ctaVacaciones, $suma->vacaciones, 0.00, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->aguinaldo != 0.00){
            $ctaAguinaldo = getCuentaConfig($d->idempresa, 20);
            if($ctaAguinaldo > 0){
                insertaDetalleContable($origen, $idtranban, $ctaAguinaldo, $suma->aguinaldo, 0.00, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->bono_14 != 0.00){
            $ctaBono14 = getCuentaConfig($d->idempresa, 21);
            if($ctaBono14 > 0){
                insertaDetalleContable($origen, $idtranban, $ctaBono14, $suma->bono_14, 0.00, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->indemniza != 0.00){
            $ctaIndemnizacion = getCuentaConfig($d->idempresa, 22);
            if($ctaIndemnizacion > 0){
                insertaDetalleContable($origen, $idtranban, $ctaIndemnizacion, $suma->indemniza, 0.00, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->suel_ord != 0.00 || (float)$suma->suel_ext != 0.00 || (float)$suma->vacaciones != 0.00){
            $ctaCuotaPatronal = getCuentaConfig($d->idempresa, 23);
            if($ctaCuotaPatronal > 0){
                insertaDetalleContable($origen, $idtranban, $ctaCuotaPatronal, $suma->cuotapatronaligss, 0.00, "PLANILLA $concepto", 1, 0);
            }
        }

        //Deducidos
        if((float)$suma->desc_igss != 0.00){
            $ctaIgssCuotaLaboral = getCuentaConfig($d->idempresa, 24);
            if($ctaIgssCuotaLaboral > 0){
                insertaDetalleContable($origen, $idtranban, $ctaIgssCuotaLaboral, 0.00, $suma->desc_igss, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->desc_isr != 0.00){
            $ctaRetencionISR = getCuentaConfig($d->idempresa, 25);
            if($ctaRetencionISR > 0){
                insertaDetalleContable($origen, $idtranban, $ctaRetencionISR, 0.00, $suma->desc_isr, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->desc_anti != 0.00){
            $ctaAnticipos = getCuentaConfig($d->idempresa, 15);
            if($ctaAnticipos > 0){
                insertaDetalleContable($origen, $idtranban, $ctaAnticipos, 0.00, $suma->desc_anti, "PLANILLA $concepto", 1, 0);
            }
        }

        if((float)$suma->suel_ord != 0.00 || (float)$suma->suel_ext != 0.00 || (float)$suma->vacaciones != 0.00){
            $ctaCuotaPatronal = getCuentaConfig($d->idempresa, 26);
            if($ctaCuotaPatronal > 0){
                insertaDetalleContable($origen, $idtranban, $ctaCuotaPatronal, 0.00, $suma->cuotapatronaligss, "PLANILLA $concepto", 1, 0);
            }
        }

        // Embargos
        if ((float)$suma->desc_embargos > 0 ) {
            $ctaEmbargos = getCuentaConfig($d->idempresa, 32);
            $query = "SELECT SUM(a.descembargo) AS desc_embargos FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado 
            INNER JOIN plnlaboral d ON b.idlaboral = d.id INNER JOIN plnempresa c ON c.id = d.idempresaactual 
            WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.descembargo > 0 ";
            $query.= $mediopago == 'nota debito' ? "AND d.cuentabanco IS NOT NULL AND d.cuentabanco > 0 " : '';
            $query.= "AND d.metodo = '$mediopago' AND a.idempresa = $d->idempresa ";
            $query.= $idempleado == 0 ? '' : "AND a.idplnempleado = $idempleado ";
            $query.= "GROUP BY a.descembargo";
            $embargos =  $db->getQuery($query);

            if ($ctaEmbargos > 0) {
                if (count($embargos) > 0) {
                    foreach ($embargos as $emb) {
                        insertaDetalleContable($origen, $idtranban, $ctaEmbargos, 0.00, $emb->desc_embargos, "PLANILLA $concepto", 1, 0);
                    }
                }
            }
        }

        //Préstamos
        if((float)$suma->desc_prest != 0.00){
            $query = "SELECT c.idempresadebito AS deptodeb, a.fecha, a.descprestamo AS desc_prest, c.idcuenta AS cuentapersonal, CONCAT(IFNULL(TRIM(b.nombre), ''), ' ', IFNULL(TRIM(b.apellidos), '')) AS nombre ";
            $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado INNER JOIN plnlaboral c ON b.idlaboral = c.id ";
            $query.= "WHERE a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.liquido > 0 ";
            $query.= $mediopago == 'nota debito' ? "AND c.cuentabanco IS NOT NULL AND c.cuentabanco > 0 " : '';
            $query.= "AND (a.descprestamo <> 0) AND c.metodo = '$mediopago' AND ";
            $query.= "a.idempresa = $d->idempresa ";
            $query.= $idempleado == 0 ? '' : "AND a.idplnempleado = $idempleado ";
            $query.= "ORDER BY b.nombre, b.apellidos";
            // print $query; return;
            $prestamos = $db->getQuery($query);
            $cntPrestamos = count($prestamos);
            for($i = 0; $i < $cntPrestamos; $i++){
                $prestamo = $prestamos[$i];
                // $query = "SELECT id FROM cuentac WHERE idempresa = $d->idempresa AND TRIM(codigo) = '".trim($prestamo->cuentapersonal)."'";
                // $idctaempleado = (int)$db->getOneField($query);
                if($prestamo->cuentapersonal > 0){
                    insertaDetalleContable($origen, $idtranban, $prestamo->cuentapersonal, 0.00, $prestamo->desc_prest, "PLANILLA $concepto", 1, 0);
                }
            }
        }

        if($idctabanco > 0 && (float)$suma->liquido != 0.00){
            insertaDetalleContable($origen, $idtranban, $idctabanco, 0.00, $suma->liquido, "PLANILLA $concepto", 1, 0);
        }
    }
}

function generand($d, $db, $total, $generales){
    $query = "SELECT COUNT(*) FROM tranban WHERE idbanco = $d->idbanco AND tipotrans = 'B' AND esplanilla = 1 AND fechaplanilla = '$d->falstr' AND liquidado = 0";
    $existe = (int)$db->getOneField($query) > 0;
    if(!$existe){
        $query = "INSERT INTO tranban(";
        $query.= "idbanco, tipotrans, numero, esplanilla, fechaplanilla, fecha, monto, beneficiario, concepto, tipocambio, idempresa";
        $query.= ") VALUES (";
        $query.= "$d->idbanco, 'B', $d->notadebito, 1, '$d->falstr', DATE(NOW()), $total, 'PLANILLA EMPLEADOS', 'PLANILLA $generales->concepto', 1.00, $d->idempresa";
        $query.= ")";
        $db->doQuery($query);
        $lastId = (int)$db->getLastId();

        if($lastId > 0){
            $query = "SELECT idcuentac FROM banco WHERE id = $d->idbanco";
            $idctabco = (int)$db->getOneField($query);
            genDetContDoc($db, $d, $lastId, $generales->concepto, $idctabco, 3);
            $query = "UPDATE empresa SET ndplanilla = $d->notadebito + 1 WHERE id = $d->idempresa";
            $db->doQuery($query);
        }

    }
};

$app->post('/generand', function() use($db){
    $d = json_decode(file_get_contents('php://input'));

    $query = "SELECT a.nomempresa AS empresa, DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s') AS hoy, '$d->notadebito' AS notadebito, ";
    $query.= "CONCAT('DEL ', LPAD(DAY('$d->fdelstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->fdelstr')), ' AL ', ";
    $query.= "LPAD(DAY('$d->falstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->falstr')), ' DEL ', YEAR('$d->falstr')) AS concepto, ";
    $query.= "CONCAT(a.abreviatura, 'PLA', DATE_FORMAT('$d->fdelstr', '%Y%m%d'), DATE_FORMAT('$d->falstr', '%Y%m%d')) AS archivo ";
    $query.= "FROM empresa a WHERE a.id = $d->idempresa";
    //print $query;
    $generales = $db->getQuery($query)[0];

    $query = "SELECT z.tipo, z.cuenta, @row := @row + 1 AS contador, z.nombre, z.monto, z.cuentacontable ";
    $query.= "FROM (";
    $query.= "SELECT 3 AS tipo, TRIM(e.cuentabanco) AS cuenta, TRIM(CONCAT(f.primernombre, ' ', IFNULL(f.segundonombre, ''), IFNULL(f.tercernombre, ''), ' ', IFNULL(f.primerapellido, ''), ' ', IFNULL(f.segundoapellido, ''), ' ', IFNULL(f.apellidocasada, ''))) AS nombre, a.liquido AS monto, e.idcuenta AS cuentacontable ";
    $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado INNER JOIN plnlaboral e ON b.idlaboral = e.id LEFT JOIN plnpuesto c ON c.id = b.idplnpuesto LEFT JOIN plnempresa d ON d.id = b.idempresaactual INNER JOIN plnpersonal f ON b.idpersonal = f.id ";
    $query.= "WHERE a.idempresa = $d->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.liquido <> 0 AND e.cuentabanco IS NOT NULL ";
    $query.= "AND e.metodo = 'nota debito' ";
    $query.= "ORDER BY b.nombre, b.apellidos";
    $query.= ") z, (SELECT @row:= 0) r";
    // print $query;
    $empleados = $db->getQuery($query);
    $cntEmpleados = count($empleados);

    if($cntEmpleados > 0){
        $qSuma = "SELECT SUM(y.monto) FROM ($query) y";
        $suma = $db->getOneField($qSuma);
        generand($d, $db, $suma, $generales);
        $empleados[] = ['tipo' => 'TOTAL:', 'cuenta' => '', 'contador' => '', 'nombre' => '', 'monto' => number_format($suma, 2)];

        for($i = 0; $i < $cntEmpleados; $i++){
            $empleado = $empleados[$i];
            $empleado->monto = number_format((float)$empleado->monto, 2);
        }
    }

    print json_encode(['generales' => $generales, 'empleados' => $empleados]);

});

function generachq($d, $db, $empresa, $empleado){
    $query = "SELECT COUNT(*) FROM tranban ";
    $query.= "WHERE idbanco = $empresa->idbanco AND tipotrans = 'C' AND esplanilla = 1 AND fechaplanilla = '$d->falstr' AND idempleado = $empleado->idempleado ";
    $query.= "AND anulado = 0 AND beneficiario NOT LIKE '%anula%' AND concepto NOT LIKE '%anula%'";
    $existe = (int)$db->getOneField($query) > 0;
    if(!$existe){
        $query = "INSERT INTO tranban(";
        $query.= "idbanco, tipotrans, numero, esplanilla, fechaplanilla, fecha, monto, beneficiario, concepto, tipocambio, idempresa, idempleado";
        $query.= ") VALUES (";
        $query.= "$empresa->idbanco, 'C', $empresa->correlativo, 1, '$d->falstr', DATE(NOW()), $empleado->monto, '$empleado->nombre', 'PLANILLA $empleado->concepto', 1.00, $empresa->idempresa, $empleado->idempleado";
        $query.= ")";
        // print $query;
        $db->doQuery($query);
        $lastId = (int)$db->getLastId();
        if($lastId > 0){
            $query = "SELECT idcuentac FROM banco WHERE id = $empresa->idbanco";
            $idctabco = (int)$db->getOneField($query);
            $d->idempresa = $empresa->idempresa;
            genDetContDoc($db, $d, $lastId, $empleado->concepto, $idctabco, 1, (int)$empleado->idempleado);
            $query = "UPDATE banco SET correlativo = $empresa->correlativo + 1 WHERE id = $empresa->idbanco";
            $db->doQuery($query);
        }
        return $empresa->correlativo;
    }
    return 0;
};

$app->post('/generachq', function() use($db){
    $d = json_decode(file_get_contents('php://input'));
    $cntEmpresas = count($d->empresas);
    $generados = [];
    for($i = 0; $i < $cntEmpresas; $i++){
        $empresa = $d->empresas[$i];
        if ($empresa->idbanco > 0) {
            $query = "SELECT z.tipo, z.cuenta, @row := @row + 1 AS contador, z.nombre, z.monto, z.cuentacontable, z.idempleado, z.concepto ";
            $query.= "FROM (";
            $query.= "SELECT 3 AS tipo, TRIM(e.cuentabanco) AS cuenta, TRIM(CONCAT(f.primernombre, ' ', IFNULL(f.segundonombre, ''), IFNULL(f.tercernombre, ''), ' ', IFNULL(f.primerapellido, ''), ' ', IFNULL(f.segundoapellido, ''), ' ', IFNULL(f.apellidocasada, ''))) AS nombre, a.liquido AS monto, e.idcuenta AS cuentacontable, b.id AS idempleado, ";
            $query.= "CONCAT('DEL ', LPAD(DAY('$d->fdelstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->fdelstr')), ' AL ', ";
            $query.= "LPAD(DAY('$d->falstr'), 2, ' '), ' DE ', (SELECT nombre FROM mes WHERE id = MONTH('$d->falstr')), ' DEL ', YEAR('$d->falstr')) AS concepto ";
            $query.= "FROM plnnomina a INNER JOIN plnempleado b ON b.id = a.idplnempleado INNER JOIN plnlaboral e ON b.idlaboral = e.id LEFT JOIN plnpuesto c ON c.id = b.idplnpuesto LEFT JOIN plnempresa d ON d.id = e.idempresaactual ";
            $query.= "INNER JOIN plnpersonal f ON b.idpersonal = f.id "; 
            $query.= "WHERE a.idempresa = $empresa->idempresa AND a.fecha >= '$d->fdelstr' AND a.fecha <= '$d->falstr' AND a.liquido <> 0 ";
            $query.= "AND e.metodo = 'cheque' ";
            $query.= "ORDER BY d.ordenreppres, b.nombre, b.apellidos";
            $query.= ") z, (SELECT @row:= 0) r";
            $empleados = $db->getQuery($query);
            $cntEmpleados = count($empleados);
            
            $empresa->correlativo = 0;
            $cntBancos = count($empresa->bancos);
            $banco = '';
            for($j = 0; $j < $cntBancos; $j++){
                if((int)$empresa->idbanco === (int)$empresa->bancos[$j]->id){
                    $empresa->correlativo = (int)$empresa->bancos[$j]->correlativo;
                    $banco = $empresa->bancos[$j]->bancomoneda;
                }
            }
        
            $numeros = [];
            for($j = 0; $j < $cntEmpleados; $j++){
                $empleado = $empleados[$j];
                $numero = generachq($d, $db, $empresa, $empleado);
                if($numero > 0){
                    $numeros[] = ['numero' => $numero, 'beneficiario' => $empleado->nombre, 'monto' => number_format((float)$empleado->monto, 2)];
                    $empresa->correlativo++;
                }
            }
            if(count($numeros) > 0){
                $generados[] = ['empresa' => $empresa->empresa, 'banco' => $banco, 'cheques' => $numeros];
            }
        }
    }
    print json_encode(['generados' => $generados]);
});

$app->post('/tran_finiquito', function() {
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));
    $errores = '';

    $d->total = round($d->total, 2);

    $query = "INSERT INTO tranban(idbanco, tipotrans, numero, esplanilla, fechaplanilla, fecha, monto, beneficiario, concepto, tipocambio, idempresa, idempleado, idusuario) VALUES ($d->idbanco, 
    '$d->tipo', $d->numero, 1, '$d->fecha', '$d->fechatran', $d->total, '$d->empleado', '$d->concepto', 1.00, $d->idempresa, $d->idempleado, $d->idusuario)";
    $db->doQuery($query);

    $lastid = $db->getLastId();

    if ($lastid > 0) {
        $db->doQuery("UPDATE plnfiniquito SET pendiente = 0, idtranban = $lastid WHERE id = $d->id");
        $db->doQuery("UPDATE banco SET correlativo = $d->numero+1 WHERE id = $d->idbanco");


        $cnt_inde = getCuentaConfig($d->idempresa, 22); 
        if ((int)$d->finiquito > 0) {
            if ($cnt_inde > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_inde, $d->finiquito, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de indeminzacion, ';
            }
        }

        $cnt_vacas = getCuentaConfig($d->idempresa, 19);
        if((int)$d->vacaciones > 0){
            if ($cnt_vacas > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_vacas, $d->vacaciones, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de vacaciones, ';
            }
        }

        $cnt_bono14 = getCuentaConfig($d->idempresa, 21); 
        if ((int)$d->bono > 0) {
            if ($cnt_bono14 > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_bono14, $d->bono, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de bono 14, ';
            }
        }

        $cnt_aguinaldo = getCuentaConfig($d->idempresa, 20); 
        if ((int)$d->aguinaldo > 0) {
            if($cnt_aguinaldo > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_aguinaldo, $d->aguinaldo, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de aguinaldo, ';
            }
        }

        $cnt_ordinarios = getCuentaConfig($d->idempresa, 16); 
        if ((int)$d->ordinario > 0) {
            if($cnt_ordinarios > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_ordinarios, $d->ordinario, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de salarios ordinarios, ';
            }
        }

        $cnt_extra = getCuentaConfig($d->idempresa, 17); 
        if ((int)$d->extra > 0) {
            if($cnt_extra > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_extra, $d->extra, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de salarios extraordinarios, ';
            }
        }

        $cnt_bono = isset($d->idcuenta_bono) ? $d->idcuenta_bono : null; 
        if ((int)$d->otrosbono > 0) {
            if($cnt_bono > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_bono, $d->otrosbono, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de aguinaldo, ';
            }
        }

        $cnt_prestamo = $db->getOneField("SELECT id FROM cuentac WHERE nombrecta LIKE '%$d->empleado%' AND idempresa = $d->idempresa"); 
        if ((int)$d->prestamos > 0) {
            if($cnt_prestamo > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_prestamo, 0, $d->prestamos, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de prestamos, ';
            }
        }

        $cnt_desc = isset($d->idcuenta_desc) ? $d->idcuenta_desc : null;
        if ((int)$d->otrosdesc > 0) {
            if($cnt_desc > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_desc, 0, $d->otrosdesc, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de otros descuentos, ';
            }
        }

        $cnt_anticipo = isset($d->idcuenta_anti) ? $d->idcuenta_anti : null;
        if ((int)$d->anticipos > 0) {
            if($cnt_anticipo > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_anticipo, 0, $d->anticipos, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de anticipos, ';
            }
        }

        $cnt_banco = $db->getOneField("SELECT idcuentac FROM banco WHERE id = $d->idbanco");
        if ((int)$d->total > 0) {
            if($cnt_banco > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_banco, 0, $d->total, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de banco. ';
            }
        }

        if (strlen($errores) > 5) {
            print json_encode(['tipo' => 'warning', 'mensaje' => 'No se pudo generar completamente la partida contable, favor arreglar. Errores: '.$errores]);
        } else {
            print json_encode(['tipo' => 'success', 'mensaje' => 'Transacción generada con éxito.']);
        }
    } else {
        print json_encode(['tipo' => 'error', 'mensaje' => 'No se recibieron suficientes datos, favor volver a intentar.']);
    }
});

$app->post('/tran_premio', function() {
    $db = new dbcpm();
    $d = json_decode(file_get_contents('php://input'));
    $errores = '';

    $d->total = round($d->total, 2);

    $query = "INSERT INTO tranban(idbanco, tipotrans, numero, esplanilla, fechaplanilla, fecha, monto, beneficiario, concepto, tipocambio, idempresa, idempleado, idusuario) VALUES ($d->idbanco, 
    '$d->tipo', $d->numero, 1, '$d->fecha', '$d->fechatran', $d->total, '$d->empleado', '$d->concepto', 1.00, $d->idempresa, $d->idempleado, $d->idusuario)";
    $db->doQuery($query);

    $lastid = $db->getLastId();

    if ($lastid > 0) {
        $db->doQuery("UPDATE detpremioemp SET pagado = 1 WHERE id = $d->id");
        $db->doQuery("UPDATE banco SET correlativo = $d->numero+1 WHERE id = $d->idbanco");


        $cnt_gratificaciones = getCuentaConfig($d->idempresa, 31); 
        if ((int)$d->total > 0) {
            if ($cnt_gratificaciones > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_gratificaciones, $d->total, 0, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de gratificacion, ';
            }
        }

        $cnt_banco = $db->getOneField("SELECT idcuentac FROM banco WHERE id = $d->idbanco");
        if ((int)$d->total > 0) {
            if($cnt_banco > 0) {
                $db->doQuery("INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado, idproyecto) 
                VALUES (1, $lastid, $cnt_banco, 0, $d->total, '$d->concepto', 1, 0, $d->idproyecto)");
            } else {
                $errores .= 'sin cuenta de banco. ';
            }
        }

        if (strlen($errores) > 5) {
            print json_encode(['tipo' => 'warning', 'mensaje' => 'No se pudo generar completamente la partida contable, favor arreglar. Errores: '.$errores]);
        } else {
            print json_encode(['tipo' => 'success', 'mensaje' => 'Transacción generada con éxito.']);
        }
    } else {
        print json_encode(['tipo' => 'error', 'mensaje' => 'No se recibieron suficientes datos, favor volver a intentar.']);
    }
});

$app->run();