<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para servicios básicos
$app->get('/lstservicios/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtiposervicio, b.desctiposervventa AS tiposervicio, a.idproveedor, c.nombre AS proveedor, ";
    $query.= "a.numidentificacion, a.numreferencia, a.idempresa, d.nomempresa AS empresa, a.pagacliente, a.preciomcubsug, a.mcubsug, a.espropio, a.ubicadoen, a.debaja, a.fechabaja, ";
    $query.= "a.idpadre, a.nivel, a.cobrar, e.numidentificacion AS contadorpadre, a.notas, a.asignado, a.diapre, a.diaemi ";
    $query.= "FROM serviciobasico a LEFT JOIN tiposervicioventa b ON b.id = a.idtiposervicio LEFT JOIN proveedor c ON c.id = a.idproveedor ";
    $query.= "LEFT JOIN empresa d ON d.id = a.idempresa LEFT JOIN serviciobasico e ON e.id = a.idpadre ";
    $query.= (int)$idempresa > 0 ? "WHERE d.id = $idempresa " : "";
    $query.= "ORDER BY a.nivel, e.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/getservicio/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtiposervicio, b.desctiposervventa AS tiposervicio, a.idproveedor, c.nombre AS proveedor, ";
    $query.= "a.numidentificacion, a.numreferencia, a.idempresa, d.nomempresa AS empresa, a.pagacliente, a.preciomcubsug, a.mcubsug, a.espropio, a.ubicadoen, a.debaja, a.fechabaja, ";
    $query.= "a.idpadre, a.nivel, a.cobrar, e.numidentificacion AS contadorpadre, a.notas, a.asignado, a.diapre AS fechapre, a.diaemi AS fechaemi, a.idunidad ";
    $query.= "FROM serviciobasico a LEFT JOIN tiposervicioventa b ON b.id = a.idtiposervicio LEFT JOIN proveedor c ON c.id = a.idproveedor ";
    $query.= "LEFT JOIN empresa d ON d.id = a.idempresa LEFT JOIN serviciobasico e ON e.id = a.idpadre ";
    $query.= "WHERE a.id = $idservicio";
    print $db->doSelectASJson($query);
});

$app->get('/lstservdispon/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, ";
    $query.= "CONCAT(b.desctiposervventa, ' - ', c.nombre, ' - ', a.numidentificacion, ' - ', a.numreferencia, ' - ', RTRIM(d.nomempresa), IF(a.pagacliente = 1, ' - Pagado por cliente','')) AS serviciobasico, ";
    $query.= "a.espropio, a.idpadre, a.nivel, a.cobrar, e.numidentificacion AS contadorpadre ";
    $query.= "FROM serviciobasico a LEFT JOIN tiposervicioventa b ON b.id = a.idtiposervicio LEFT JOIN proveedor c ON c.id = a.idproveedor ";
    $query.= "LEFT JOIN empresa d ON d.id = a.idempresa LEFT JOIN serviciobasico e ON e.id = a.idpadre ";
    $query.= "WHERE a.asignado = 0 AND a.debaja = 0 ";
    $query.= (int)$idempresa > 0 ? "AND d.id = $idempresa " : "";
    $query.= "ORDER BY a.nivel, e.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/lstsrvpadres', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, b.nomempresa AS empresa, a.numidentificacion, a.numreferencia, c.numidentificacion AS contadorpadre ";
    $query.= "FROM serviciobasico a INNER JOIN empresa b ON b.id = a.idempresa LEFT JOIN serviciobasico c ON a.id = c.idpadre ";
    $query.= "WHERE a.asignado = 0 AND a.debaja = 0 ";
    //$query.= "AND a.id IN (SELECT z.id FROM serviciobasico z INNER JOIN serviciobasico y ON z.id = y.idpadre GROUP BY z.id HAVING COUNT(y.idpadre) > 0) ";
    $query.= "ORDER BY b.nomempresa, a.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/histo/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT d.nomproyecto AS proyecto, c.descripcion AS tipolocal, b.nombre, b.descripcion, a.fini, IF(a.ffin IS NULL, 'A la fecha', a.ffin) AS ffin ";
    $query.= "FROM unidadservicio a LEFT JOIN unidad b ON b.id = a.idunidad LEFT JOIN tipolocal c ON c.id = b.idtipolocal LEFT JOIN proyecto d ON d.id = b.idproyecto ";
    $query.= "WHERE a.idserviciobasico = $idservicio ";
    $query.= "ORDER BY a.ffin DESC";
    print $db->doSelectASJson($query);
});

$app->get('/histocb/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idunidadservicio, a.idproyecto, b.nomproyecto AS proyecto, a.idunidad, c.nombre AS unidad, a.usrcambio, CONCAT(d.nombre, ' (', d.usuario, ')') AS usuario, a.fechacambio, a.cantbase ";
    $query.= "FROM detunidadservicio a LEFT JOIN proyecto b ON b.id = a.idproyecto LEFT JOIN unidad c ON c.id = a.idunidad LEFT JOIN usuario d ON d.id = a.usrcambio ";
    $query.= "WHERE a.idserviciobasico = $idservicio ";
    $query.= "ORDER BY a.fechacambio DESC";
    print $db->doSelectASJson($query);
});

function getNivel($db, $idpadre){ return (int)$db->getOneField("SELECT nivel + 1 FROM serviciobasico WHERE id = $idpadre"); }

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $nivel = (int)$d->idpadre > 0 ? getNivel($db, (int)$d->idpadre) : 0;
	$d->idpadre = (int)$d->idpadre > 0 ? $d->idpadre : 0;
    $notas = $d->notas = '' ? 'NULL' : "'$d->notas'";
    $query = "INSERT INTO serviciobasico(idtiposervicio, idproveedor, numidentificacion, numreferencia, idempresa, ";
    $query.= "pagacliente, preciomcubsug, mcubsug, espropio, ubicadoen, idpadre, nivel, cobrar, notas, diapre, diaemi, idunidad) VALUES(";
    $query.= "$d->idtiposervicio, $d->idproveedor, '$d->numidentificacion', '$d->numreferencia', $d->idempresa, ";
    $query.= "$d->pagacliente, $d->preciomcubsug, $d->mcubsug, $d->espropio, '$d->ubicadoen', $d->idpadre, $nivel, $d->cobrar, $notas, ";
    $query.= "$d->fechapre, $d->fechaemi, $d->idunidad) ";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fechabajastr = $d->fechabajastr == '' ? "NULL" : "'$d->fechabajastr'";
    $nivel = (int)$d->idpadre > 0 ? getNivel($db, (int)$d->idpadre) : 0;
    $notas = $d->notas = '' ? 'NULL' : "'$d->notas'";
    $query = "UPDATE serviciobasico SET ";
    $query.= "idtiposervicio = $d->idtiposervicio, idproveedor = $d->idproveedor, numidentificacion = '$d->numidentificacion', ";
    $query.= "numreferencia = '$d->numreferencia', idempresa = $d->idempresa, pagacliente = $d->pagacliente, ";
    $query.= "preciomcubsug = $d->preciomcubsug, mcubsug = $d->mcubsug, espropio = $d->espropio, ubicadoen = '$d->ubicadoen', ";
    $query.= "debaja = $d->debaja, fechabaja = $d->fechabajastr, idpadre = $d->idpadre, nivel = $nivel, cobrar = $d->cobrar, notas = $notas, ";
    $query.= "diapre = $d->fechapre, diaemi = $d->fechaemi, idunidad = $d->idunidad ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);

    if((int)$d->debaja == 1){
        $db->doQuery("UPDATE unidadservicio SET ffin = NOW() WHERE idserviciobasico = $d->id");
        $db->doQuery("UPDATE serviciobasico SET asignado = 0 WHERE id = $d->id");
    }
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $noAsignado = (int)$db->getOneField("SELECT asignado FROM serviciobasico WHERE id = $d->id") == 0;
    if($noAsignado){
        $db->doQuery("DELETE FROM unidadservicio WHERE idserviciobasico = $d->id");
        $db->doQuery("DELETE FROM serviciobasico WHERE id = $d->id");
    }    
});

$app->get('/asignacion/:idservicio', function($idservicio){
    $db = new dbcpm();
    $asignado = new stdClass();
    $asignado->idproyecto = 0;
    $asignado->idunidad = 0;

    $query = "SELECT b.idproyecto, a.idunidad, a.idserviciobasico 
            FROM unidadservicio a
            INNER JOIN unidad b ON b.id = a.idunidad
            INNER JOIN proyecto c ON c.id = b.idproyecto
            WHERE a.ffin IS NULL AND a.idserviciobasico = $idservicio
            LIMIT 1";
    $asignacion = $db->getQuery($query);
    if(count($asignacion) > 0) {
        $asignado->idproyecto = (int)$asignacion[0]->idproyecto;
        $asignado->idunidad = (int)$asignacion[0]->idunidad;
    }

    print json_encode(['asignacion' => $asignado]);
});

$app->post('/lecturainicial', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();    

    $query = "INSERT INTO lecturaservicio(";
    $query.= "idserviciobasico, idusuario, idproyecto, idunidad, mes, anio, fechacorte, lectura, fechaingreso, estatus, fechaenvio, usrenvio, ";
    $query.= "facturado, idfactura, descuento, conceptoadicional";
    $query.= ") VALUES(";
    $query.= "$d->idservicio, $d->idusuario, $d->idproyecto, $d->idunidad, $d->mes, $d->anio, '$d->fechacortestr', $d->lectura, NOW(), 3, NOW(), $d->idusuario,";
    $query.= "1, 0, 0, 'INSERCIÓN DE LECTURA INICIAL DEL CONTADOR.'";
    $query.= ")";
    $db->doQuery($query);
});

$app->get('/getcontadores/:idempresa', function ($idempresa) {
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();  

    if ($idempresa == 0) { $idempresa = null; };

    $query = "SELECT 
                a.id,
                b.nombre AS proveedor,
                c.desctiposervventa AS tipo,
                a.numidentificacion AS identificacion,
                a.numreferencia AS referencia,
                a.idunidad,
                d.idproyecto
            FROM
                serviciobasico a
                    INNER JOIN
                proveedor b ON a.idproveedor = b.id
                    INNER JOIN
                tiposervicioventa c ON a.idtiposervicio = c.id
					INNER JOIN
				unidad d ON a.idunidad = d.id
					INNER JOIN
				proyecto e ON d.idproyecto = e.id
            WHERE
                a.espropio = 0 ";
    $query.= $idempresa > 0 ? "AND a.idempresa = $idempresa " : '';
    $query.= "AND pagacliente = 0";
    print $db->doSelectASJson($query);
});

$app->post('/rptservicios', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();  

    if (!isset($d->fdel)) { $d->fdel = false; };
    if (!isset($d->fal)) { $d->fal = false; };

    $mesdel = date("m", strtotime($d->fdel));
    $mesal = date("m", strtotime($d->fal));
    $aniodel = ' '.date("Y", strtotime($d->fdel));
    $anioal = ' '.date("Y", strtotime($d->fal));

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    if ($aniodel == $anioal) {
        $aniodel = '';
    }

    $letra = new stdClass();

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    $letra->del = 'De ' .$meses[$mesdel-1].$aniodel;

    $mesal != $mesdel ? $letra->al = 'a '.$meses[$mesal-1].$anioal : $letra->al = $anioal;

    $query = "SELECT 
                id, nomempresa AS empresa
            FROM
                empresa
            WHERE
                id IN (SELECT DISTINCT
                        a.idempresa
                    FROM
                        serviciobasico a
                            INNER JOIN
                        compserv b ON b.idservicio = a.id";
    $query.= $d->fdel ? " WHERE b.fechaini >= $d->fdel AND b.fechafin <= $d->fal) " : ") ";
    $query.= "ORDER BY nomempresa";
    $empresas = $db->getQuery($query);

    $cntEmpresa = count($empresas);

    for ($i = 0; $i < $cntEmpresa; $i++) {
        $empresa = $empresas[$i];

        $query = "SELECT 
                    id, nomproyecto AS proyecto
                FROM
                    proyecto
                WHERE
                    id IN (SELECT DISTINCT
                        c.idproyecto
                    FROM
                        serviciobasico a
                            INNER JOIN
                        compserv b ON b.idservicio = a.id
                            INNER JOIN
                        unidad c ON a.idunidad = c.id
                    WHERE
                        a.idempresa = $empresa->id";
        $query.= $d->fdel ? " AND b.fechaini >= $d->fdel AND b.fechafin <= $d->fal) " : ") ";
        $query.= "ORDER BY nomproyecto";
        // print $query; return;
        $empresa->proyectos = $db->getQuery($query);

        $cntProyectos = count($empresa->proyectos);

        for ($j = 0; $j < $cntProyectos; $j++) {
            $proyecto = $empresa->proyectos[$j];
            
            $query = "SELECT 
                        a.id,
                        CONCAT(IFNULL(a.numidentificacion, ''),
                                ' ',
                                IFNULL(a.numreferencia, '')) AS contador,
                        IFNULL(b.descripcion, 'N/A') AS tiposervicio
                    FROM
                        serviciobasico a
                            LEFT JOIN
                        tiposervicio b ON a.idtiposervicio = b.id
                            INNER JOIN
                        compserv c ON c.idservicio = a.id
                            INNER JOIN
                        unidad d ON a.idunidad = d.id
                    WHERE
                        d.idproyecto = $proyecto->id AND a.idempresa = $empresa->id";
            $query.= $d->fdel ? " AND c.fechaini >= $d->fdel AND c.fechafin <= $d->fal " : " ";
            $query.= "GROUP BY a.id";
            // print $query; return;
            $proyecto->servicios = $db->getQuery($query);

            $cntServicios = count($proyecto->servicios);

            for ($k = 0; $k < $cntServicios; $k++) {
                $servicio = $proyecto->servicios[$k];

                $query = "SELECT 
                            IF(c.id = 5, 'EEGSA', c.nombre) AS proveedor,
                            a.documento AS factura,
                            DATE_FORMAT(a.fechafactura, '%d/%m/%Y') AS fecha,
                            IFNULL(d.nombre, 'N/A') AS unidad,
                            b.lecturaini,
                            b.lecturafin,
                            DATE_FORMAT(b.fechaini, '%d/%m/%Y') AS fechaini,
                            DATE_FORMAT(b.fechafin, '%d/%m/%Y') AS fechafin,
                            b.lecturafin - b.lecturaini AS lectura,
                            IFNULL(ROUND(a.subtotal / (b.lecturafin - b.lecturaini),
                                    2), 0.00) AS preciouni,
                            a.totfact AS total,
                            e.simbolo AS moneda,
                            CONCAT(g.tipotrans, '-', g.numero) AS transaccion,
                            IF(c.id = 5, 'kwa', IF(c.id = 11, 'm³', '')) AS tipo
                        FROM
                            compra a
                                INNER JOIN
                            compserv b ON b.idcompra = a.id
                                INNER JOIN
                            proveedor c ON a.idproveedor = c.id
                                LEFT JOIN
                            unidad d ON a.idunidad = d.id
                                INNER JOIN
                            moneda e ON a.idmoneda = e.id
                                LEFT JOIN 
                            detpagocompra f ON f.idcompra = a.id 
                                LEFT JOIN 
                            tranban g ON f.idtranban = g.id
                        WHERE
                            b.idservicio = $servicio->id";
                $query.= $d->fdel ? " AND a.fechafactura >= $d->fdel AND a.fechafactura <= $d->fal " : " ";
                $query.= "ORDER BY fechafactura";
                // print $query; return;
                $servicio->compras = $db->getQuery($query);

                $cntCompras = count($servicio->compras);

                $promedio = 0.00;
                $total = 0.00;

                for ($l = 0; $l < $cntCompras; $l++) {
                    $compra = $servicio->compras[$l];

                    $promedio += $compra->lectura;
                    $total += $compra->total;
                }

                $promedio = $promedio / $cntCompras;
                $servicio->promedio = number_format($promedio, 2, '.', ',');
                $servicio->total = number_format($total, 2, '.', ',');
            }
        }
    }

    print json_encode(['fechas' => $letra, 'servicios' => $empresas]);
});

$app->post('/rptservicio', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();  
    date_default_timezone_set('America/Guatemala');

    if (!isset($d->anio)) { $d->anio = false; };

    $meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

    $letra = new stdClass();

    $letra->estampa = new DateTime();
    $letra->estampa = $letra->estampa->format('d-m-Y H:i');

    if ($d->anio) {
        $letra->anio = '2023';
    }
    

    $lecturas = array();
    $totales = array();

    $query = "SELECT 
                a.numidentificacion AS contador,
                a.numreferencia AS correlativo,
                UPPER(b.desctiposervventa) AS tiposervicio,
                c.nomempresa AS empresa,
                e.nomproyecto AS proyecto,
                d.descripcion AS unidad
            FROM
                serviciobasico a
                    INNER JOIN
                tiposervicioventa b ON a.idtiposervicio = b.id
                    INNER JOIN
                empresa c ON a.idempresa = c.id
                    INNER JOIN
                unidad d ON a.idunidad = d.id
                    INNER JOIN
                proyecto e ON d.idproyecto = e.id
            WHERE
                a.id = $d->idservicio";
    $encabezado = $db->getQuery($query)[0];

    $query = "SELECT 
                MONTH(c.fechafactura) AS mes,
                YEAR(c.fechafactura) AS anio,
                DATEDIFF(b.fechafin, b.fechaini) AS dias,
                DATE_FORMAT(b.fechaini, '%d/%m/%y') AS inicio,
                DATE_FORMAT(b.fechafin, '%d/%m/%y') AS fin,
                b.lecturaini,
                b.lecturafin,
                b.lecturafin - b.lecturaini AS lectura,
                ROUND((c.totfact - c.iva) / (b.lecturafin - b.lecturaini),
                        2) AS precio,
                IF(g.id = 5, 'EEGSA', g.nombre) AS proveedor,
                DATE_FORMAT(c.fechafactura, '%d/%m/%y') AS fecha,
                c.documento AS factura,
                DATE_FORMAT(c.fechapago, '%d/%m/%y') AS fechapago,
                CONCAT(e.tipotrans, '-', e.numero) AS trans,
                c.totfact AS monto,
                f.simbolo,
                IF(a.idtiposervicio = 9,
                    'kW',
                    IF(a.idtiposervicio = 4, 'm³', '')) AS terminacion
            FROM
                serviciobasico a
                    INNER JOIN
                compserv b ON b.idservicio = a.id
                    INNER JOIN
                compra c ON b.idcompra = c.id
                    LEFT JOIN
                detpagocompra d ON d.idcompra = c.id
                    LEFT JOIN
                tranban e ON d.idtranban = e.id
                    INNER JOIN
                moneda f ON c.idmoneda = f.id
                    INNER JOIN
                proveedor g ON c.idproveedor = g.id
            WHERE
                a.id = $d->idservicio ";
    $query.= $d->anio ? "AND YEAR(c.fechafactura) = $d->anio " : '';
    $query.= "ORDER BY c.fechafactura ASC";
    $compras = $db->getQuery($query);
    
    $cntCompras = count($compras);

    $encabezado->movimientos = array();

    // array para generar anios
    for ($i = 0; $i < $cntCompras; $i++) {
        $compra = $compras[$i];

        if ($i == 0) {
            $j = 0;

            $anio = new stdClass();
            $anio->anio = $compra->anio;
            $anio->compras = array();
    
            array_push($encabezado->movimientos, $anio);
        } else {
            $comprant = $compras[$i-1];
            if ($compra->anio !== $comprant->anio) {
                $anio = new stdClass();
                $anio->anio = $compra->anio;
                $anio->compras = array();
        
                array_push($encabezado->movimientos, $anio);
            } 
        }

        $compra->mesletra = $meses[$compra->mes - 1];
    }

    $cntAnios = count($encabezado->movimientos);

    for ($i = 0; $i < $cntAnios; $i++) {
        $anio = $encabezado->movimientos[$i];
        
        $montos_anio = array();
        $lecturas_anio = array(); 
        $moneda = null;
        $terminacion = null;

        for ($j = 0; $j < $cntCompras; $j++) {
            $compra = $compras[$j];

            if ($anio->anio == $compra->anio) {
                array_push($anio->compras, $compra);
                array_push($montos_anio, $compra->monto);
                array_push($lecturas_anio, $compra->lectura);
                $moneda = $compra->simbolo;
                $terminacion = $compra->terminacion;
            }
        }

        $anio->terminacion = $terminacion;
        $anio->moneda = $moneda;
        $anio->total = number_format(array_sum($montos_anio) / count($montos_anio), 2, '.', ',');
        $anio->lectura = number_format(array_sum($lecturas_anio) / count($lecturas_anio), 2, '.', ',');
    }

    $encabezado->terminacion = $compras[0]->terminacion;
    $encabezado->moneda = $compras[0]->simbolo;
    $encabezado->proveedor = $compras[0]->proveedor;

    print json_encode(['fechas' => $letra, 'servicio' => $encabezado]);
});

$app->get('/getlectura/:idservicio', function($idservicio){
    $db = new dbcpm();

    $query = "SELECT lecturafin AS lectura, fechafin AS fechaini, DATE_ADD(fechafin, INTERVAL 1 MONTH) AS fechafin FROM compserv WHERE idservicio = $idservicio ORDER BY fechafin DESC LIMIT 1";
    print $db->doSelectASJson($query);
});

$app->get('/getnombre/:idservicio', function($idservicio){
    $db = new dbcpm();

    $query = "SELECT numidentificacion AS nombre FROM serviciobasico WHERE id = $idservicio";
    print $db->doSelectASJson($query);
});

$app->run();