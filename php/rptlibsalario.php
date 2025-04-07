<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/rptlibsalario', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $query = "SELECT 
                CONCAT(a.nombre, ' ', IFNULL(a.apellidos, '')) AS nombre,
                DATE_FORMAT(DATE_SUB(NOW(),
                            INTERVAL YEAR(c.nacimiento) YEAR),
                        '%y') AS edad,
                c.sexo AS genero,
                e.nacionalidad,
                b.descripcion AS puesto,
                d.igss,
                c.documento,
                DATE_FORMAT(d.ingreso, '%d/%m/%Y') AS ingreso,
                IFNULL(DATE_FORMAT(d.baja, '%d/%m/%Y'), '') AS baja,
                d.idempresadebito
            FROM
                plnempleado a
                    INNER JOIN
                            plnpuesto b ON a.idplnpuesto = b.id
                    INNER JOIN
                            plnpersonal c ON a.idpersonal = c.id
                    INNER JOIN
                            plnlaboral d ON a.idlaboral = d.id
                    INNER JOIN
                            nacionalidad e ON c.idnacionalidad = e.id
                        WHERE
                            a.id = $d->idempleado ";
    $empleado = $db->getQuery($query)[0];

    $db->doQuery("SET lc_time_names = 'es_ES'");

    $query = "SELECT 
                NULL AS numero,
                DATE_FORMAT(a.fecha, '%b, %Y') AS periodo,
                SUM(a.sueldoordinario) AS salario,
                SUM(a.diastrabajados) AS dias,
                '-' AS hordindario,
                ROUND(a.horasmes, 0) AS hextra,
                SUM(a.sueldoordinario) AS sordinario,
                SUM(a.sueldoextra + a.bonificacion) AS sextra,
                SUM(a.otrosingresos) AS sotros,
                '-' AS sasuetos,
                SUM(a.vacaciones) AS svacaciones,
                SUM(a.sueldoordinario + a.sueldoextra + a.bonificacion + a.otrosingresos + vacaciones) AS stotal,
                SUM(a.descigss) AS digss,
                SUM(a.descisr) AS disr,
                SUM(a.descotros + a.descprestamo) AS dotros,
                SUM(a.descigss + a.descisr + a.descotros + a.descprestamo) AS dtotal,
                SUM(a.aguinaldo) AS aguinaldo,
                SUM(bonocatorce) AS bonocatorce,
                '-' AS otros,
                SUM(liquido) AS liquido,
                a.idempresa
            FROM
                plnnomina a
            WHERE
                a.idplnempleado = $d->idempleado
                    AND a.fecha >= '$d->fdelstr'
                    AND a.fecha <= '$d->falstr'
            GROUP BY MONTH(fecha)
            ORDER BY YEAR(fecha) , MONTH(fecha) ";
    $salarios = $db->getQuery($query);

    $idempresa = $salarios[0]->idempresa;

    $query = "SELECT nomempresa AS empresa, nit FROM empresa WHERE id = $idempresa";
    $empresa = $db->getQuery($query)[0];

    $cntSalario = count($salarios);

    $i = 0;
    $j = 1;
    $tsalario = array();
    $tdias = array();
    $textra = array();
    $tsextra = array();
    $tasueto = array();
    $tvacas = array();
    $tingreso = array();
    $tigss = array();
    $tisr = array();
    $totros = array();
    $tegresos = array();
    $aguinaldo = array();
    $bono14 = array();
    $liquido = array();

    while ($i < $cntSalario) {
        $salario = $salarios[$i];
        $salario->numero = $j;
        array_push($tsalario, $salario->salario);
        array_push($tdias, $salario->dias);
        array_push($textra, $salario->hextra);
        array_push($tsextra, $salario->sextra);
        array_push($tasueto, $salario->sasuetos);
        array_push($tvacas, $salario->svacaciones);
        array_push($tingreso, $salario->stotal);
        array_push($tigss, $salario->digss);
        array_push($tisr, $salario->disr);
        array_push($totros, $salario->dotros);
        array_push($tegresos, $salario->dtotal);
        array_push($aguinaldo, $salario->aguinaldo);
        array_push($bono14, $salario->bonocatorce);
        array_push($liquido, $salario->liquido);
        $j++;
        $i++;
    }

    $empleado->salario = number_format(array_sum($tsalario), 2);
    $empleado->dias = array_sum($tdias);
    $empleado->horas = '-';
    $empleado->extra = array_sum($textra);
    $empleado->sueldo = $empleado->salario; 
    $empleado->sueldoext = number_format(array_sum($tsextra), 2);
    $empleado->asueto = number_format(array_sum($tasueto), 2);
    $empleado->sotros = '-';
    $empleado->vacas = number_format(array_sum($tvacas), 2);
    $empleado->ingresos = number_format(array_sum($tingreso), 2);
    $empleado->miggs = number_format(array_sum($tigss), 2);
    $empleado->isr = number_format(array_sum($tisr), 2);
    $empleado->dotros = number_format(array_sum($totros), 2);
    $empleado->egresos = number_format(array_sum($tegresos),2);
    $empleado->aguinaldo = number_format(array_sum($aguinaldo),2);
    $empleado->bono14 = number_format(array_sum($bono14),2);
    $empleado->devoluciones = '-';
    $empleado->liquido =number_format(array_sum($liquido),2);

    print json_encode([ 'empresa' => $empresa, 'empleado' => $empleado, 'salarios' => $salarios ]);

});

$app->run();