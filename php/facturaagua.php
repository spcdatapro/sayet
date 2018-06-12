<?php
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/pendientes', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $d->retisr = $db->getOneField("SELECT retisr FROM empresa WHERE id = $d->idempresa");

    $query = "SELECT a.id, a.idserviciobasico, a.idproyecto, a.idunidad, a.mes, a.anio, a.lectura, b.numidentificacion, b.preciomcubsug, b.mcubsug, LecturaAnterior(a.idserviciobasico, a.mes, a.anio) AS ultimalecturafact, ";
    $query.= "(a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) as consumo, ";
    $query.= "IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, ((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug), 0.00) AS consumoafacturar, ";

    $query.= "ROUND(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug) / 1.12, 0.00 ), 2) AS montosiniva, ";
    $query.= "ROUND(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00 ), 2) AS montoconiva, ";
    $query.= "0.00 AS iva, ";

    $query.= "0.00 AS retisr, RetISR(d.id, b.idtiposervicio) as retenerisr, 0.00 AS retiva, RetIVA(d.id, b.idtiposervicio) AS reteneriva, c.idtipocliente, d.nombre, d.nombrecorto, ";
    $query.= "FacturarA(d.id, b.idtiposervicio) AS facturara, NitFacturarA(d.id, b.idtiposervicio) AS nit, DirFacturarA(d.id, b.idtiposervicio) AS direccion, ";
    $query.= "f.desctiposervventa AS tipo, 0.00 AS totapagar, 0 AS numfact, '' AS seriefact, ";
    $query.= "IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, 0, 1) AS facturar, c.id AS idcontrato, ";
    $query.= "d.id AS idcliente, UPPER(f.desctiposervventa) AS tipo, UPPER(g.nomproyecto) AS proyecto, h.nombre AS unidad, ";
    //$query.= "(SELECT nombre FROM mes WHERE id = MONTH('$d->fvencestr')) AS nommes, ";
	$query.= "(SELECT nombre FROM mes WHERE id = a.mes) AS nommes, ";
	$query.= "b.idtiposervicio, ";
    $query.= "DATE_FORMAT(FechaLecturaAnterior(a.idserviciobasico, a.mes, a.anio), '%d/%m/%Y') AS fechaanterior, DATE_FORMAT(a.fechacorte, '%d/%m/%Y') AS fechaactual ";
    $query.= "FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico INNER JOIN contrato c ON c.id = (SELECT b.id FROM contrato b WHERE FIND_IN_SET(a.idunidad, b.idunidad) LIMIT 1) ";
    $query.= "INNER JOIN cliente d ON d.id = c.idcliente INNER JOIN tiposervicioventa f ON f.id = b.idtiposervicio ";
    $query.= "INNER JOIN proyecto g ON g.id = a.idproyecto INNER JOIN unidad h ON h.id = a.idunidad ";
    $query.= "WHERE a.estatus = 2 AND b.pagacliente = 0 AND ";
    $query.= "a.mes <= MONTH('$d->fvencestr') AND a.anio <= YEAR('$d->fvencestr') AND b.idempresa = $d->idempresa AND ";
    $query.= "(c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->fvencestr'))";
    //$query.= "ORDER BY d.nombre, 21, a.anio, a.mes"; //Columna 21 es "facturar a"
    $query.= "ORDER BY g.nomproyecto, CAST(digits(h.nombre) AS UNSIGNED), h.nombre";
    $factagua = $db->getQuery($query);

    $empresa = $db->getQuery("SELECT congface, seriefact, correlafact FROM empresa WHERE id = $d->idempresa")[0];
    $empresa->correlafact = (int)$empresa->correlafact;

    $cntFA = count($factagua);
    for($i = 0; $i < $cntFA; $i++){
        $fagua = $factagua[$i];
        $fagua->iva = (float)$fagua->montoconiva - (float)$fagua->montosiniva;
        $fagua->retisr = (int)$fagua->retenerisr > 0 ? $db->calculaISR((float)$fagua->montosiniva) : 0.00;
        $fagua->retiva = (int)$fagua->reteneriva > 0 ? $db->calculaRetIVA((float)$fagua->montosiniva, ((int)$fagua->idtipocliente == 1 ? true : false), $fagua->montoconiva, ((int)$fagua->idtipocliente == 2 ? true : false), $fagua->iva) : 0.00;
        $fagua->totapagar = (float)$fagua->montoconiva - ($fagua->retisr + $fagua->retiva);

        if((int)$empresa->congface == 0){
            $fagua->seriefact = $empresa->seriefact;
            $fagua->numfact = $empresa->correlafact;
            $empresa->correlafact++;
        }

    }

    print json_encode($factagua);

});

$app->post('/proyeccion', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $datos = new stdclass();

    $d->retisr = $db->getOneField("SELECT retisr FROM empresa WHERE id = $d->idempresa");

    $query = "SELECT a.id, a.idserviciobasico, a.idproyecto, a.idunidad, a.mes, a.anio, a.lectura, b.numidentificacion, b.preciomcubsug, b.mcubsug, LecturaAnterior(a.idserviciobasico, a.mes, a.anio) AS ultimalecturafact, ";
    $query.= "(a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) as consumo, ";
    $query.= "IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, ((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug), 0.00) AS consumoafacturar, ";
    $query.= "ROUND(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug) / 1.12, 0.00 ), 2) AS montosiniva, ";
    $query.= "ROUND(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, (((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) * b.preciomcubsug), 0.00 ), 2) AS montoconiva, ";
    $query.= "0.00 as retisr, c.retiva, c.idtipocliente, d.nombre, d.nombrecorto, FacturarA(d.id, b.idtiposervicio) AS facturara, f.desctiposervventa AS tipo, 0.00 AS totapagar, 0 AS numfact, '' AS seriefact, ";
    $query.= "IF(((a.lectura - LecturaAnterior(a.idserviciobasico, a.mes, a.anio)) - b.mcubsug) > 0, 0, 1) AS facturar, c.id AS idcontrato, ";
    $query.= "d.id AS idcliente, UPPER(f.desctiposervventa) AS tipo, UPPER(g.nomproyecto) AS proyecto, h.nombre AS unidad, ";
    $query.= "(SELECT nombre FROM mes WHERE id = MONTH('$d->fvencestr')) AS nommes, b.idtiposervicio, ";
    $query.= "DATE_FORMAT(FechaLecturaAnterior(a.idserviciobasico, a.mes, a.anio), '%d/%m/%Y') AS fechaanterior, DATE_FORMAT(a.fechacorte, '%d/%m/%Y') AS fechaactual ";
    $query.= "FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico INNER JOIN contrato c ON c.id = (SELECT b.id FROM contrato b WHERE FIND_IN_SET(a.idunidad, b.idunidad) LIMIT 1) ";
    $query.= "INNER JOIN cliente d ON d.id = c.idcliente INNER JOIN tiposervicioventa f ON f.id = b.idtiposervicio ";
    $query.= "INNER JOIN proyecto g ON g.id = a.idproyecto INNER JOIN unidad h ON h.id = a.idunidad ";
    $query.= "WHERE a.estatus = 2 AND b.pagacliente = 0 AND ";
    $query.= "a.mes <= MONTH('$d->fvencestr') AND a.anio <= YEAR('$d->fvencestr') AND b.idempresa = $d->idempresa AND ";
    $query.= "(c.inactivo = 0 OR (c.inactivo = 1 AND c.fechainactivo > '$d->fvencestr'))";
    //$query.= "ORDER BY d.nombre, 21, a.anio, a.mes"; //Columna 21 es "facturar a"
    $query.= "ORDER BY g.nomproyecto, CAST(digits(h.nombre) AS UNSIGNED), h.nombre";
    $factagua = $db->getQuery($query);

    $empresa = $db->getQuery("SELECT congface, seriefact, correlafact FROM empresa WHERE id = $d->idempresa")[0];
    $empresa->correlafact = (int)$empresa->correlafact;

    $cntFA = count($factagua);
    $totconsumo = 0.00;
    $totmonto = 0.00;
    for($i = 0; $i < $cntFA; $i++){
        $fagua = $factagua[$i];
        $fagua->retisr = round((int)$d->retisr > 0 ? $db->calculaISR((float)$fagua->montosiniva) : 0.00, 2);
        $fagua->ivaaretener = round((int)$fagua->retiva > 0 ? $db->calculaRetIVA((float)$fagua->montosiniva, ((int)$fagua->idtipocliente == 1 ? true : false), $fagua->montoconiva, ((int)$fagua->idtipocliente == 2 ? true : false)) : 0.00, 2);
        $fagua->totapagar = round((float)$fagua->montoconiva - ($fagua->retisr + $fagua->ivaaretener), 2);

        if((int)$empresa->congface == 0){
            $fagua->seriefact = $empresa->seriefact;
            $fagua->numfact = $empresa->correlafact;
            $empresa->correlafact++;
        }

        $totconsumo += (float)$fagua->consumo;
        $totmonto += (float)$fagua->montoconiva;

        $fagua->ultimalecturafact = number_format((float)$fagua->ultimalecturafact, 2);
        $fagua->lectura = number_format((float)$fagua->lectura, 2);
        $fagua->consumo = number_format((float)$fagua->consumo, 2);
        $fagua->preciomcubsug = number_format((float)$fagua->preciomcubsug, 2);
        $fagua->mcubsug = number_format((float)$fagua->mcubsug, 2);
        $fagua->montosiniva = (float)$fagua->montosiniva != 0 ? number_format((float)$fagua->montosiniva, 2) : '';
        $fagua->montoconiva = (float)$fagua->montoconiva != 0 ? number_format((float)$fagua->montoconiva, 2) : '';
    }

    $datos->contadores = $factagua;
    $datos->totales = ['totconsumo' => number_format(round($totconsumo, 2), 2), 'totmonto' => number_format(round($totmonto, 2), 2)];

    print json_encode($datos);

});

$app->post('/genfact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $params = $d->params;
    $pendientes = $d->pendientes;
    $db = new dbcpm();
    $n2l = new NumberToLetterConverter();

    $empresa = $db->getQuery("SELECT congface, seriefact, correlafact FROM empresa WHERE id = $params->idempresa")[0];
    $empresa->correlafact = (int)$empresa->correlafact;

    foreach($pendientes as $p){

        if((float)$p->consumoafacturar > 0){
            if((int)$empresa->congface == 0){
                $p->seriefact = "'$empresa->seriefact'";
                $p->numfact = "'$empresa->correlafact'";
                $p->tipofact = "7";
                $empresa->correlafact++;
            }
            else{
                $p->seriefact = "NULL";
                $p->numfact = "NULL";
                $p->tipofact = "1";
            }

            $descripcion = $p->tipo.' DE '.$p->proyecto.' '.$p->unidad.', Contador: '.$p->numidentificacion.', Consumo(m3): '.$p->consumoafacturar.' Mes de '.$p->nommes.' '.$p->anio;
            $query = "INSERT INTO factura(";
            $query.= "idempresa, idtipofactura, idcontrato, idcliente, serie, numero, ";
            $query.= "fechaingreso, mesiva, fecha, idtipoventa, conceptomayor, iva, ";
            $query.= "total, noafecto, subtotal, totalletras, idmoneda, tipocambio, ";
            $query.= "retisr, retiva, totdescuento, nit, nombre, direccion, montocargoiva, montocargoflat";
            $query.= ") VALUES (";
            $query.= "$params->idempresa, $p->tipofact, $p->idcontrato, $p->idcliente, $p->seriefact, $p->numfact, ";
            $query.= "NOW(), MONTH('$params->ffacturastr'), '$params->ffacturastr', 2, '$descripcion', $p->iva, ";
            $query.= "$p->totapagar, 0.00, $p->montoconiva, '".$n2l->to_word($p->totapagar, 'GTQ')."', 1, $params->tc, ";
            $query.= "$p->retisr, $p->retiva, 0.00, '$p->nit', '$p->facturara', '$p->direccion', $p->montoconiva, $p->montoconiva";
            $query.= ")";
            //echo $query.'<br/>';

            $db->doQuery($query);
            $lastid = $db->getLastId();

            if((int)$lastid > 0){
                //Inserta detalle de factura
                $query = "INSERT INTO detfact(";
                $query.= "idfactura, cantidad, descripcion, preciounitario, preciotot, idtiposervicio, mes, anio, descuento, montoconiva, montoflatconiva";
                $query.= ") VALUES(";
                $query.= "$lastid, 1, '$descripcion', $p->montoconiva, $p->montoconiva, $p->idtiposervicio, $p->mes, $p->anio, 0.00, $p->montoconiva, $p->montoconiva";
                $query.= ")";
                $db->doQuery($query);
                $query = "UPDATE lecturaservicio SET estatus = 3, facturado = 1, idfactura = $lastid WHERE id = $p->id";
                $db->doQuery($query);

                $query = "UPDATE serviciobasico SET ultimalecturafact = $p->lectura WHERE id = $p->idserviciobasico";
                $db->doQuery($query);

                if((int)$lastid > 0){
                    $url = 'http://localhost/sayet/php/genpartidasventa.php/genpost';
                    $data = ['ids' => $lastid, 'idcontrato' => 1];
                    $db->CallJSReportAPI('POST', $url, json_encode($data));
                }
            }
        }else{
            $query = "UPDATE lecturaservicio SET estatus = 3, facturado = 1, idfactura = 0 WHERE id = $p->id";
            $db->doQuery($query);

            $query = "UPDATE serviciobasico SET ultimalecturafact = $p->lectura WHERE id = $p->idserviciobasico";
            $db->doQuery($query);
        }
    }

    if((int)$empresa->congface == 0){
        $query = "UPDATE empresa SET correlafact = $empresa->correlafact WHERE id = $params->idempresa";
        $db->doQuery($query);
    }

    print json_encode('Generación de facturas completada...');

});

$app->post('/rptagua', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $tiempo = $db->getQuery("SELECT MONTH('$d->fvencestr') AS mes, YEAR('$d->fvencestr') AS anio")[0];

    $mesAnterior = (int)$tiempo->mes > 1 ? ((int)$tiempo->mes - 1) : 12;
    $anioAnterior = (int)$tiempo->mes > 1 ? (int)$tiempo->anio : ((int)$tiempo->anio - 1);

    $qEnding = "FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico LEFT JOIN contrato c ON c.id = (";
    $qEnding.= "SELECT b.id FROM contrato b WHERE IF(b.inactivo = 1 AND MONTH(b.fechainactivo) = MONTH('$d->fvencestr') AND YEAR(b.fechainactivo) = YEAR('$d->fvencestr'), FIND_IN_SET(a.idunidad, b.idunidadbck), FIND_IN_SET(a.idunidad, b.idunidad)) LIMIT 1";
    $qEnding.= ") ";
    $qEnding.= "LEFT JOIN cliente d ON d.id = c.idcliente LEFT JOIN detclientefact e ON d.id = e.idcliente LEFT JOIN tiposervicioventa f ON f.id = b.idtiposervicio LEFT JOIN proyecto g ON g.id = a.idproyecto ";
    $qEnding.= "LEFT JOIN unidad h ON h.id = a.idunidad LEFT JOIN empresa i ON i.id = b.idempresa ";
    $qEnding.= "WHERE a.estatus IN(2, 3) AND b.pagacliente = 0 AND (c.inactivo = 0 OR c.inactivo IS NULL) AND e.fal IS NULL AND a.mes = MONTH('$d->fvencestr') AND a.anio = YEAR('$d->fvencestr') ";
    $qEnding.= $d->idempresa != '' ? "AND b.idempresa IN($d->idempresa) " : "";
	$qEnding.= $d->idproyecto != '' ? "AND a.idproyecto IN ($d->idproyecto) " : "";

    $query = "SELECT DISTINCT b.idempresa, i.abreviatura AS empresa ".$qEnding."ORDER BY i.abreviatura";
    $contadores = $db->getQuery($query);
    $cntCont = count($contadores);
    for($i = 0; $i < $cntCont; $i++){
        $contador = $contadores[$i];

        $query = "SELECT DISTINCT a.idproyecto, g.nomproyecto AS proyecto ".$qEnding." AND b.idempresa = $contador->idempresa ORDER BY g.nomproyecto";
        $contador->proyectos = $db->getQuery($query);
        $cntProy = count($contador->proyectos);
        for($j = 0; $j < $cntProy; $j++){
            $proyecto = $contador->proyectos[$j];
            $query = "SELECT a.id, FORMAT(a.lectura, 2) AS lectura, b.numidentificacion, FORMAT(b.preciomcubsug, 2) AS preciomcubsug, ";
            $query.= "FORMAT((SELECT IFNULL((SELECT cantbase FROM detunidadservicio WHERE idunidad = a.idunidad AND idserviciobasico = a.idserviciobasico AND DATE(fechacambio) <= '$d->fvencestr' ORDER BY fechacambio DESC LIMIT 1) , 0.00)), 2) AS mcubsug, ";
            $query.= "FORMAT((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr'))), 2) as consumo, ";
            $query.= "FORMAT(ROUND(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr'))) - (SELECT IFNULL((SELECT cantbase FROM detunidadservicio WHERE idunidad = a.idunidad AND idserviciobasico = a.idserviciobasico AND DATE(fechacambio) <= '$d->fvencestr' ORDER BY fechacambio DESC LIMIT 1) , 0.00))) > 0, ((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr'))) - (SELECT IFNULL((SELECT cantbase FROM detunidadservicio WHERE idunidad = a.idunidad AND idserviciobasico = a.idserviciobasico AND DATE(fechacambio) <= '$d->fvencestr' ORDER BY fechacambio DESC LIMIT 1) , 0.00))) * b.preciomcubsug, 0.00 ), 2), 2) AS montosiniva, ";
            $query.= "IFNULL(d.nombre, 'VACANTE') AS nombre, h.nombre AS unidad, DATE_FORMAT(a.fechacorte, '%d/%m/%Y') AS fechafinal, DATE_FORMAT(FechaLecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr')), '%d/%m/%Y') AS fechainicial, ";
            $query.= "FORMAT(LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr')), 2) AS lecturainicial ";
            $query.= "FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico ";
            $query.= "LEFT JOIN contrato c ON c.id = (";
            $query.= "SELECT b.id FROM contrato b ";
            $query.= "WHERE IF(b.inactivo = 1 AND MONTH(b.fechainactivo) = MONTH('$d->fvencestr') AND YEAR(b.fechainactivo) = YEAR('$d->fvencestr'), FIND_IN_SET(a.idunidad, b.idunidadbck), FIND_IN_SET(a.idunidad, b.idunidad)) LIMIT 1";
            $query.= ") ";
            $query.= "LEFT JOIN cliente d ON d.id = c.idcliente LEFT JOIN tiposervicioventa f ON f.id = b.idtiposervicio LEFT JOIN proyecto g ON g.id = a.idproyecto LEFT JOIN unidad h ON h.id = a.idunidad LEFT JOIN empresa i ON i.id = b.idempresa ";
            $query.= "WHERE a.estatus IN(2, 3) AND b.pagacliente = 0 AND (c.inactivo = 0 OR (c.inactivo = 1 AND MONTH(c.fechainactivo) = MONTH('$d->fvencestr') AND YEAR(c.fechainactivo) = YEAR('$d->fvencestr')) OR c.inactivo IS NULL) ";
            $query.= "AND a.mes = MONTH('$d->fvencestr') AND a.anio = YEAR('$d->fvencestr') AND b.idempresa = $contador->idempresa AND a.idproyecto = $proyecto->idproyecto ";
            $query.= "ORDER BY CAST(digits(h.nombre) AS UNSIGNED), h.nombre, b.numidentificacion";
            //print $query;
            $proyecto->consumos = $db->getQuery($query);
            $cntConsumos = count($proyecto->consumos);
            if($cntConsumos > 0){
                $query = "SELECT ";
                $query.= "FORMAT(SUM((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr')))), 2) as consumo, ";
                $query.= "FORMAT(SUM(ROUND(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr'))) - (SELECT IFNULL((SELECT cantbase FROM detunidadservicio WHERE idunidad = a.idunidad AND idserviciobasico = a.idserviciobasico AND DATE(fechacambio) <= '$d->fvencestr' ORDER BY fechacambio DESC LIMIT 1) , 0.00))) > 0, ((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr'))) - (SELECT IFNULL((SELECT cantbase FROM detunidadservicio WHERE idunidad = a.idunidad AND idserviciobasico = a.idserviciobasico AND DATE(fechacambio) <= '$d->fvencestr' ORDER BY fechacambio DESC LIMIT 1) , 0.00))) * b.preciomcubsug, 0.00 ), 2)), 2) AS montosiniva ";
                $query.= "FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico ";
                $query.= "LEFT JOIN contrato c ON c.id = (";
                $query.= "SELECT b.id FROM contrato b ";
                $query.= "WHERE IF(b.inactivo = 1 AND MONTH(b.fechainactivo) = MONTH('$d->fvencestr') AND YEAR(b.fechainactivo) = YEAR('$d->fvencestr'), FIND_IN_SET(a.idunidad, b.idunidadbck), FIND_IN_SET(a.idunidad, b.idunidad)) LIMIT 1";
                $query.= ") ";
                $query.= "LEFT JOIN cliente d ON d.id = c.idcliente LEFT JOIN tiposervicioventa f ON f.id = b.idtiposervicio LEFT JOIN proyecto g ON g.id = a.idproyecto LEFT JOIN unidad h ON h.id = a.idunidad LEFT JOIN empresa i ON i.id = b.idempresa ";
                $query.= "WHERE a.estatus IN(2, 3) AND b.pagacliente = 0 AND (c.inactivo = 0 OR (c.inactivo = 1 AND MONTH(c.fechainactivo) = MONTH('$d->fvencestr') AND YEAR(c.fechainactivo) = YEAR('$d->fvencestr')) OR c.inactivo IS NULL) ";
                $query.= "AND a.mes = MONTH('$d->fvencestr') AND a.anio = YEAR('$d->fvencestr') AND b.idempresa = $contador->idempresa AND a.idproyecto = $proyecto->idproyecto ";
                $sumas = $db->getQuery($query)[0];
                $proyecto->consumos[] =[
                    'id' => '', 'lectura' => 'Total:', 'numidentificacion' => '', 'preciomcubsug' => '', 'mcubsug' => '',
                    'consumo' => $sumas->consumo, 'montosiniva' => $sumas->montosiniva,
                    'nombre' => '', 'unidad' => '', 'fechafinal' => '', 'fechainicial' => '', 'lecturainicial' => ''
                ];
            }
        }

        $query = "SELECT ";
        $query.= "FORMAT(SUM((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr')))), 2) as consumo, ";
        $query.= "FORMAT(SUM(ROUND(IF(((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr'))) - (SELECT IFNULL((SELECT cantbase FROM detunidadservicio WHERE idunidad = a.idunidad AND idserviciobasico = a.idserviciobasico AND DATE(fechacambio) <= '$d->fvencestr' ORDER BY fechacambio DESC LIMIT 1) , 0.00))) > 0, ((a.lectura - LecturaAnterior(a.idserviciobasico, MONTH('$d->fvencestr'), YEAR('$d->fvencestr'))) - (SELECT IFNULL((SELECT cantbase FROM detunidadservicio WHERE idunidad = a.idunidad AND idserviciobasico = a.idserviciobasico AND DATE(fechacambio) <= '$d->fvencestr' ORDER BY fechacambio DESC LIMIT 1) , 0.00))) * b.preciomcubsug, 0.00 ), 2)), 2) AS montosiniva ";
        $query.= "FROM lecturaservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico ";
        $query.= "LEFT JOIN contrato c ON c.id = (";
        $query.= "SELECT b.id FROM contrato b ";
        $query.= "WHERE IF(b.inactivo = 1 AND MONTH(b.fechainactivo) = MONTH('$d->fvencestr') AND YEAR(b.fechainactivo) = YEAR('$d->fvencestr'), FIND_IN_SET(a.idunidad, b.idunidadbck), FIND_IN_SET(a.idunidad, b.idunidad)) LIMIT 1";
        $query.= ") ";
        $query.= "LEFT JOIN cliente d ON d.id = c.idcliente LEFT JOIN tiposervicioventa f ON f.id = b.idtiposervicio LEFT JOIN proyecto g ON g.id = a.idproyecto LEFT JOIN unidad h ON h.id = a.idunidad LEFT JOIN empresa i ON i.id = b.idempresa ";
        $query.= "WHERE a.estatus IN(2, 3) AND b.pagacliente = 0 AND (c.inactivo = 0 OR (c.inactivo = 1 AND MONTH(c.fechainactivo) = MONTH('$d->fvencestr') AND YEAR(c.fechainactivo) = YEAR('$d->fvencestr')) OR c.inactivo IS NULL) ";
        $query.= "AND a.mes = MONTH('$d->fvencestr') AND a.anio = YEAR('$d->fvencestr') AND b.idempresa = $contador->idempresa ";
        $query.= $d->idproyecto != '' ? "AND a.idproyecto IN ($d->idproyecto) " : "";
        $contador->sumatoria = $db->getQuery($query)[0];
    }

    print json_encode($contadores);

});


$app->run();