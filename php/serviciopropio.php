<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para servicios propio
$app->get('/lstservicios/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtiposervicio, b.desctiposervventa AS tiposervicio, ";
    $query.= "a.numidentificacion, a.numreferencia, a.idempresa, d.nomempresa AS empresa, a.preciomcubsug, a.mcubsug ";
    $query.= "FROM serviciobasico a INNER JOIN tiposervicioventa b ON b.id = a.idtiposervicio ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa ";
    $query.= "WHERE a.espropio = 1 ";
    $query.= (int)$idempresa > 0 ? "AND d.id = $idempresa " : "";
    $query.= "ORDER BY b.desctiposervventa, d.nomempresa";
    //echo $query;
    print $db->doSelectASJson($query);
});

$app->get('/getservicio/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idtiposervicio, b.desctiposervventa AS tiposervicio, ";
    $query.= "a.numidentificacion, a.numreferencia, a.idempresa, d.nomempresa AS empresa, a.preciomcubsug, a.mcubsug ";
    $query.= "FROM serviciobasico a INNER JOIN tiposervicioventa b ON b.id = a.idtiposervicio ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa ";
    $query.= "WHERE a.id = $idservicio";
    print $db->doSelectASJson($query);
});

$app->get('/lstservdispon/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, CONCAT(b.desctiposervventa, ' - ', a.numidentificacion, ' - ', a.numreferencia, ' - ', RTRIM(d.nomempresa)) AS serviciopropio, ";
    $query.= "b.desctiposervventa AS tipo, a.numidentificacion AS contador, RTRIM(d.nomempresa) AS empresa ";
    $query.= "FROM serviciobasico a INNER JOIN tiposervicioventa b ON b.id = a.idtiposervicio ";
    $query.= "INNER JOIN empresa d ON d.id = a.idempresa ";
    $query.= "WHERE a.asignado = 0 AND a.debaja = 0 ";
    $query.= (int)$idempresa > 0 ? "AND d.id = $idempresa " : "";
    $query.= "ORDER BY a.numidentificacion, b.desctiposervventa, d.nomempresa";
    print $db->doSelectASJson($query);
});

$app->get('/histo/:idservicio', function($idservicio){
    $db = new dbcpm();
    $query = "SELECT d.nomproyecto AS proyecto, c.descripcion AS tipolocal, b.nombre, b.descripcion, a.fini, IF(a.ffin IS NULL, 'A la fecha', a.ffin) AS ffin ";
    $query.= "FROM unidadservicio a INNER JOIN unidad b ON b.id = a.idunidad INNER JOIN tipolocal c ON c.id = b.idtipolocal INNER JOIN proyecto d ON d.id = b.idproyecto ";
    $query.= "WHERE a.idserviciobasico = $idservicio ";
    $query.= "ORDER BY a.ffin DESC";
    print $db->doSelectASJson($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO serviciobasico(idtiposervicio, idproveedor, numidentificacion, numreferencia, idempresa, ";
    $query.= "preciomcubsug, mcubsug, espropio) VALUES(";
    $query.= "$d->idtiposervicio, 0, '$d->numidentificacion', '$d->numreferencia', $d->idempresa, ";
    $query.= "$d->preciomcubsug, $d->mcubsug, 1";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE serviciobasico SET ";
    $query.= "idtiposervicio = $d->idtiposervicio, numidentificacion = '$d->numidentificacion', ";
    $query.= "numreferencia = '$d->numreferencia', idempresa = $d->idempresa, preciomcubsug = $d->preciomcubsug, mcubsug = $d->mcubsug ";
    $query.= "WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM serviciobasico WHERE id = $d->id");
});

//API de lectura de servicios

$app->get('/lectura/:idusuario/:mes/:anio(/:idproyecto)', function($idusuario, $mes, $anio, $idproyecto = 0){
    $db = new dbcpm();

    $query = "INSERT INTO lecturaservicio(idserviciobasico, idusuario, idproyecto, idunidad, mes, anio) ";
    $query.= "SELECT a.id, $idusuario, e.id, c.id, $mes, $anio FROM serviciobasico a INNER JOIN unidadservicio b ON a.id = b.idserviciobasico INNER JOIN unidad c ON c.id = b.idunidad ";
    $query.= "INNER JOIN usuarioproyecto d ON d.idproyecto = c.idproyecto INNER JOIN proyecto e ON e.id = d.idproyecto ";
    $query.= "WHERE d.idusuario = $idusuario AND ISNULL(b.ffin) AND a.id NOT IN(";
    $query.= "SELECT idserviciobasico FROM lecturaservicio WHERE mes = $mes AND anio = $anio AND idserviciobasico IN(";
    $query.= "SELECT a.id FROM serviciobasico a	INNER JOIN unidadservicio b ON a.id = b.idserviciobasico INNER JOIN unidad c ON c.id = b.idunidad ";
    $query.= "INNER JOIN usuarioproyecto d ON d.idproyecto = c.idproyecto INNER JOIN proyecto e ON e.id = d.idproyecto ";
    $query.= "WHERE a.debaja = 0 AND d.idusuario = $idusuario AND ISNULL(b.ffin) ORDER BY e.nomproyecto, c.nombre, a.numidentificacion)) ";
    $query.= "ORDER BY e.nomproyecto, c.nombre, a.numidentificacion";
    $db->doQuery($query);

    $query = "SELECT a.id, a.idproyecto, b.nomproyecto AS proyecto, a.idunidad, c.nombre AS unidad, a.idserviciobasico, d.numidentificacion AS servicio, a.mes, a.anio, a.lectura, a.fechaingreso, a.estatus, a.fechacorte ";
    $query.= "FROM lecturaservicio a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN unidad c ON c.id = a.idunidad INNER JOIN serviciobasico d ON d.id = a.idserviciobasico ";
    $query.= "WHERE a.mes = $mes AND a.anio = $anio AND d.debaja = 0 AND a.idserviciobasico IN(";
    $query.= "SELECT a.id FROM serviciobasico a INNER JOIN unidadservicio b ON a.id = b.idserviciobasico INNER JOIN unidad c ON c.id = b.idunidad INNER JOIN usuarioproyecto d ON d.idproyecto = c.idproyecto ";
    $query.= "WHERE d.idusuario = $idusuario AND a.debaja = 0) ";
    $query.= (int)$idproyecto > 0 ? "AND a.idproyecto = $idproyecto " : '';
    $query.= "ORDER BY b.nomproyecto, CAST(digits(c.nombre) AS UNSIGNED), c.nombre";
    print $db->doSelectASJson($query);

});

$app->get('/proyusr/:idusuario', function($idusuario){
    $db = new dbcpm();

    $query = "SELECT a.idproyecto, b.nomproyecto AS proyecto ";
    $query.= "FROM usuarioproyecto a INNER JOIN proyecto b ON b.id = a.idproyecto ";
    $query.= "WHERE a.idusuario = $idusuario ";
    $query.= "ORDER BY b.nomproyecto";
    print $db->doSelectASJson($query);
});

$app->post('/ul', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fechacortestr = $d->fechacortestr == '' ? "NULL" : "'$d->fechacortestr'";
    $d->lectura = $d->lectura == '' ? "NULL" : $d->lectura;
    $query = "UPDATE lecturaservicio SET lectura = $d->lectura, fechaingreso = NOW(), fechacorte = $d->fechacortestr WHERE id = $d->id";
    $db->doQuery($query);
});

$app->post('/enviofact', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE lecturaservicio SET estatus = 2, fechaenvio = NOW(), usrenvio = $d->idusuario WHERE mes = $d->mes AND anio = $d->anio AND id IN($d->idservicio)";
    $db->doQuery($query);

    $query = "UPDATE serviciobasico a INNER JOIN lecturaservicio b ON a.id = b.idserviciobasico ";
    $query.= "SET a.ultimalectura = b.lectura ";
    $query.= "WHERE b.mes = $d->mes AND anio = $d->anio";
    $db->doQuery($query);
});

$app->post('/rptagua', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();

    $mesAnterior = (int)$d->mes > 1 ? ((int)$d->mes - 1) : 12;
    $anio = (int)$d->mes > 1 ? (int)$d->anio : ((int)$d->anio - 1);

    $query = "SELECT DISTINCT a.id, b.nomproyecto AS proyecto, f.nombre, f.nombrecorto, c.nombre AS unidad, d.numidentificacion AS servicio, DATE_FORMAT(g.fechacorte, '%d/%m/%Y') AS fechaanterior, DATE_FORMAT(a.fechacorte, '%d/%m/%Y') AS fechaactual,
IF(g.lectura IS NOT NULL, g.lectura, 0.00) AS anterior, a.lectura AS actual,
(a.lectura - IF(g.lectura IS NOT NULL, g.lectura, 0.00)) AS consumo
FROM lecturaservicio a
INNER JOIN proyecto b ON b.id = a.idproyecto
INNER JOIN unidad c ON c.id = a.idunidad
INNER JOIN serviciobasico d ON d.id = a.idserviciobasico
INNER JOIN (
	SELECT DISTINCT b.idcliente, a.id AS idunidad
	FROM unidad a, contrato b
	WHERE FIND_IN_SET(a.id, b.idunidad) AND b.inactivo = 0 AND TRIM(b.abogado) NOT LIKE '%iusi%'
) e ON e.idunidad = a.idunidad
INNER JOIN cliente f ON f.id = e.idcliente
LEFT JOIN (
	SELECT a.idserviciobasico, a.fechacorte, a.lectura
	FROM lecturaservicio a
	INNER JOIN proyecto b ON b.id = a.idproyecto
	INNER JOIN unidad c ON c.id = a.idunidad
	INNER JOIN serviciobasico d ON d.id = a.idserviciobasico
	WHERE a.mes = $mesAnterior AND a.anio = $anio AND a.idserviciobasico IN
	(
		SELECT DISTINCT a.id
		FROM serviciobasico a
		INNER JOIN unidadservicio b ON a.id = b.idserviciobasico
		INNER JOIN unidad c ON c.id = b.idunidad
		INNER JOIN usuarioproyecto d ON d.idproyecto = c.idproyecto
		WHERE d.idusuario = $d->idusuario
	)
	ORDER BY b.nomproyecto, CAST(digits(c.nombre) AS UNSIGNED), c.nombre
) g ON a.idserviciobasico = g.idserviciobasico
WHERE a.mes = $d->mes AND a.anio = $d->anio AND a.idserviciobasico IN
(
	SELECT DISTINCT a.id
    FROM serviciobasico a
    INNER JOIN unidadservicio b ON a.id = b.idserviciobasico
    INNER JOIN unidad c ON c.id = b.idunidad
    INNER JOIN usuarioproyecto d ON d.idproyecto = c.idproyecto
    WHERE d.idusuario = $d->idusuario
)
ORDER BY b.nomproyecto, CAST(digits(c.nombre) AS UNSIGNED), c.nombre";

    print $db->doSelectASJson($query);
});

$app->run();