<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para proyectos
$app->get('/lstproyecto', function(){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nomproyecto, a.registro, a.direccion, a.notas, a.metros, a.idempresa, a.metros_rentable, a.tipo_proyecto, a.subarrendado, a.notas_contrato, a.referencia, a.fechaapertura, ";
    $query.= "b.nomempresa AS empresa, c.descripcion AS tipoproyecto, a.multiempresa, a.apiurlparqueo ";
    $query.= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipo_proyecto c ON c.id = a.tipo_proyecto ";
    $query.= "ORDER BY a.nomproyecto";
    print $db->doSelectASJson($query);
});

$app->get('/lstproyectoporempresa/:idempresa', function($idempresa){
    $db = new dbcpm();
    $query = "SELECT a.id, a.nomproyecto, a.registro, a.direccion, a.notas, a.metros, a.idempresa, a.metros_rentable, a.tipo_proyecto, a.subarrendado, a.notas_contrato, a.referencia, a.fechaapertura, ";
    $query.= "b.nomempresa AS empresa, c.descripcion AS tipoproyecto, a.multiempresa, a.apiurlparqueo ";
    $query.= "FROM proyecto a INNER JOIN empresa b ON b.id = a.idempresa INNER JOIN tipo_proyecto c ON c.id = a.tipo_proyecto ";
    $query.= "WHERE a.idempresa = $idempresa OR a.multiempresa = 1 ";
    $query.= "ORDER BY a.nomproyecto";
    print $db->doSelectASJson($query);
});

$app->get('/getproyecto/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('proyecto',['id', 'nomproyecto', 'registro', 'direccion', 'notas', 'metros', 'idempresa',
                                'metros_rentable', 'tipo_proyecto', 'subarrendado', 'notas_contrato', 'referencia', 'fechaapertura', 'multiempresa', 'apiurlparqueo']
                        ,['id' => $idproyecto, 'ORDER' => 'nomproyecto']);
    print json_encode($data[0]);
});

$app->get('/getdetactivoproyecto/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT a.id, a.idproyecto, a.idactivo, b.nombre_corto, b.finca, b.folio, b.libro, b.metros_muni, ";
    $query.= "IF(c.propia = 1, c.nomempresa, CONCAT(c.nomempresa, ' (', b.nomclienteajeno,')')) AS nomempresa ";
    $query.= "FROM detalle_activo_proyecto a ";
    $query.= "LEFT JOIN activo b on a.idactivo = b.id ";
    $query.= "LEFT JOIN empresa c ON c.id = b.idempresa ";
    $query.= "WHERE a.idproyecto = ".$idproyecto." ";
    $query.= "ORDER BY c.propia DESC, c.nomempresa, b.finca, b.folio, b.libro";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->get('/getdetadocsproyecto/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('detalle_docs_proyecto',['id', 'idproyecto', 'tipo_documento', 'entidad', 'documento',
                            'vigencia_del', 'vegencia_al'],['id' => $idproyecto, 'ORDER' => 'nomproyecto']);

    print json_encode($data);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $d->fechaaperturastr = $d->fechaaperturastr == '' ? 'NULL' : "'$d->fechaaperturastr'";
    $query = "INSERT INTO proyecto(nomproyecto, direccion, notas, metros, idempresa, metros_rentable, ";
    $query.= "tipo_proyecto, subarrendado, notas_contrato, referencia, fechaapertura, multiempresa) ";
    $query.= "VALUES('".$d->nomproyecto."', '".$d->direccion."', '".$d->notas."', ".$d->metros.", ".$d->idempresa;
    $query.= ", ".$d->metros_rentable.", ".$d->tipo_proyecto.", ".$d->subarrendado.", NULL, '".$d->referencia."', $d->fechaaperturastr, $d->multiempresa)";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/u', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $d->fechaaperturastr = $d->fechaaperturastr == '' ? 'NULL' : "'$d->fechaaperturastr'";
    $query = "UPDATE proyecto SET nomproyecto = '".$d->nomproyecto."'";
    $query.= ", direccion = '".$d->direccion."', notas = '".$d->notas."', metros = ".$d->metros.", idempresa = ".$d->idempresa;
    $query.= ", metros_rentable = ".$d->metros_rentable.", tipo_proyecto = ".$d->tipo_proyecto.", subarrendado = ".$d->subarrendado;
    $query.= ", notas_contrato = '".$d->notas_contrato."', referencia= '".$d->referencia."', fechaapertura = $d->fechaaperturastr, multiempresa = $d->multiempresa ";
    $query.= "where id = ".$d->id;

    $upd = $conn->query($query);
});

$app->post('/d', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $adj = $conn->query("SELECT CONCAT('../', ubicacion) AS ubicacion FROM proyecto_adjunto WHERE headerid = ".$d->id)->fetchAll(5);
    $contAdj = count($adj);
    for($i = 0; $i < $contAdj; $i++){ unlink($adj[$i]->ubicacion); }
    $conn->query("DELETE FROM proyecto_adjunto WHERE headerid = ".$d->id);
    $conn->query("DELETE FROM detalle_activo_proyecto WHERE idproyecto = ".$d->id);
    $conn->query("DELETE FROM proyectounidad WHERE idproyecto = ".$d->id);
    $query = "DELETE FROM proyecto WHERE id = ".$d->id;
    $conn->query($query);
});

$app->get('/gettipounidad/:idtipoproyecto', function($idtipoproyecto){
    $db = new dbcpm();
    $query = "SELECT a.id FROM tipolocal a INNER JOIN tipo_proyecto b ON a.descripcion = b.descripcion WHERE b.id = ".$idtipoproyecto;
    $tipounidad = (int)$db->getOneField($query);
    print json_encode(['tipounidad' => $tipounidad]);
});

//API para unidades por proyecto
$app->get('/unidadesproy/:idproy', function($idproy){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idproyecto, a.idtipolocal, b.descripcion AS tipolocal, a.nombre, a.mcuad, a.descripcion, a.nolineastel, a.numeros, ";
    $query.= "a.conteegsa, a.observaciones, b.esrentable, a.multiunidad ";
    $query.= "FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
    $query.= "WHERE a.idproyecto = ".$idproy." ";
    //$query.= "ORDER BY b.descripcion, a.nombre";
    $query.= "ORDER BY CAST(digits(a.nombre) AS UNSIGNED), a.nombre";
    print $db->doSelectASJson($query);
});

$app->get('/unidad/:idunidad', function($idunidad){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idproyecto, a.idtipolocal, b.descripcion AS tipolocal, a.nombre, a.mcuad, a.descripcion, a.nolineastel, a.numeros, ";
    $query.= "a.conteegsa, a.observaciones, a.multiunidad ";
    $query.= "FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
    $query.= "WHERE a.id = ".$idunidad;
    print $db->doSelectASJson($query);
});

$app->get('/unidadesdisponibles/:idproy/:idcontrato', function($idproy, $idcontrato){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idproyecto, a.idtipolocal, b.descripcion AS tipolocal, a.nombre, a.mcuad, a.descripcion, a.nolineastel, a.numeros, ";
    $query.= "a.conteegsa, a.observaciones, b.esrentable, a.multiunidad ";
    $query.= "FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
    $query.= "WHERE (a.idproyecto = ".$idproy." AND ";
    $query.= "a.id NOT IN(SELECT u.id FROM unidad u, contrato c WHERE FIND_IN_SET(u.id, c.idunidad) AND c.id <> $idcontrato AND c.inactivo = 0)) OR ";
    $query.= "(a.idproyecto = $idproy AND a.multiunidad = 1) ";
    $query.= "ORDER BY CAST(digits(a.nombre) AS UNSIGNED), a.nombre";
    print $db->doSelectASJson($query);
});

$app->post('/cup', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "INSERT INTO unidad(idproyecto, idtipolocal, nombre, mcuad, descripcion, observaciones, multiunidad) VALUES(";
    $query.= $d->idproyecto.", ".$d->idtipolocal.", '".$d->nombre."', ".$d->mcuad.", '".$d->descripcion."', '".$d->observaciones."', $d->multiunidad";
    $query.= ")";
    $db->doQuery($query);
    print json_encode(['lastid' => $db->getLastId()]);
});

$app->post('/uup', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "UPDATE unidad SET idtipolocal = ".$d->idtipolocal.", nombre = '".$d->nombre."', mcuad = ".$d->mcuad.", ";
    $query.= "descripcion = '".$d->descripcion."', observaciones = '".$d->observaciones."', multiunidad = $d->multiunidad ";
    $query.= "WHERE id = ".$d->id;
    $db->doQuery($query);
});

$app->post('/dup', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM unidad WHERE id = ".$d->id);
});

$app->get('/servuni/:idunidad', function($idunidad){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idunidad, a.idserviciobasico, c.desctiposervventa AS tiposervicio, b.numidentificacion, b.numreferencia, e.nomempresa AS empresa, ";
    $query.= "b.preciomcubsug, ";
    $query.= "IF((SELECT cantbase FROM detunidadservicio WHERE idunidadservicio = a.id ORDER BY fechacambio DESC LIMIT 1) IS NULL, 0.00, ";
    $query.= "(SELECT cantbase FROM detunidadservicio WHERE idunidadservicio = a.id ORDER BY fechacambio DESC LIMIT 1)) AS mcubsug ";
    $query.= "FROM unidadservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico INNER JOIN tiposervicioventa c ON c.id = b.idtiposervicio ";
    $query.= "INNER JOIN empresa e ON e.id = b.idempresa ";
    $query.= "WHERE b.asignado = 1 AND b.espropio = 1 AND a.idunidad = ".$idunidad." AND ISNULL(a.ffin) ";
    $query.= "ORDER BY c.desctiposervventa, b.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/servunibasico/:idunidad', function($idunidad){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idunidad, a.idserviciobasico, c.desctiposervventa AS tiposervicio, d.nombre AS proveedor, b.numidentificacion, b.numreferencia, e.nomempresa AS empresa, ";
    $query.= "IF(b.pagacliente = 0, 'No', 'Sí') AS pagacliente, ";
    $query.= "IF((SELECT cantbase FROM detunidadservicio WHERE idunidadservicio = a.id ORDER BY fechacambio DESC LIMIT 1) IS NULL, 0.00, ";
    $query.= "(SELECT cantbase FROM detunidadservicio WHERE idunidadservicio = a.id ORDER BY fechacambio DESC LIMIT 1)) AS mcubsug ";
    $query.= "FROM unidadservicio a INNER JOIN serviciobasico b ON b.id = a.idserviciobasico INNER JOIN tiposervicioventa c ON c.id = b.idtiposervicio ";
    $query.= "INNER JOIN proveedor d ON d.id = b.idproveedor INNER JOIN empresa e ON e.id = b.idempresa ";
    $query.= "WHERE b.asignado = 1 AND b.espropio = 0 AND a.idunidad = ".$idunidad." AND ISNULL(a.ffin) ";
    $query.= "ORDER BY c.desctiposervventa, b.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->post('/csu', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO unidadservicio(idunidad, idserviciobasico, fini) VALUES(".$d->idunidad.", ".$d->idserviciobasico.", NOW())");
    $db->doQuery("UPDATE serviciobasico SET asignado = 1 WHERE id = ".$d->idserviciobasico);
});

$app->post('/dsu', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("UPDATE unidadservicio SET ffin = NOW() WHERE id = ".$d->id);
    $db->doQuery("UPDATE serviciobasico SET asignado = 0 WHERE id = ".$d->idserviciobasico);
});

$app->post('/ucb', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $cambio = 1;
    $query = "SELECT IF((SELECT cantbase FROM detunidadservicio WHERE idserviciobasico = $d->idserviciobasico ORDER BY fechacambio DESC LIMIT 1) IS NULL, 0.00, (SELECT cantbase FROM detunidadservicio WHERE idserviciobasico = $d->idserviciobasico ORDER BY fechacambio DESC LIMIT 1))";
    $ultcambio = round((float)$db->getOneField($query), 2);
    $mcs = round((float)$d->mcubsug, 2);
    if($mcs != $ultcambio){
        $query = "INSERT INTO detunidadservicio(idunidadservicio, idproyecto, idunidad, fechacambio, usrcambio, cantbase, idserviciobasico) VALUES(";
        $query.= "$d->id, $d->idproyecto, $d->idunidad, NOW(), $d->idusuario, $mcs, $d->idserviciobasico";
        $query.= ")";
        //print $query;
        $db->doQuery($query);
        $cambio = 1;
    }

    $query = "UPDATE serviciobasico SET preciomcubsug = $d->preciomcubsug, mcubsug = $mcs WHERE id = $d->idserviciobasico";
    //print $query;
    $db->doQuery($query);

    print json_encode(['cambio' => $cambio]);
});

//API para servicios a nivel de proyecto
$app->get('/lstsrvproy/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idproyecto, b.nomproyecto AS proyecto, b.referencia AS refproyecto, d.nomempresa AS empresaproyecto, ";
    $query.= "a.idserviciobasico, c.numidentificacion, e.nomempresa AS empresaservicio ";
    $query.= "FROM proyectoservicio a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN serviciobasico c ON c.id = a.idserviciobasico INNER JOIN empresa d ON d.id = b.idempresa ";
    $query.= "INNER JOIN empresa e ON e.id = c.idempresa ";
    $query.= "WHERE a.idproyecto = $idproyecto ";
    $query.= "ORDER BY e.nomempresa, c.numidentificacion";
    print $db->doSelectASJson($query);
});

$app->get('/getsrvproy/:idsrvproy', function($idsrvproy){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idproyecto, b.nomproyecto AS proyecto, b.referencia AS refproyecto, d.nomempresa AS empresaproyecto, ";
    $query.= "a.idserviciobasico, c.numidentificacion, e.nomempresa AS empresaservicio ";
    $query.= "FROM proyectoservicio a INNER JOIN proyecto b ON b.id = a.idproyecto INNER JOIN serviciobasico c ON c.id = a.idserviciobasico INNER JOIN empresa d ON d.id = b.idempresa ";
    $query.= "INNER JOIN empresa e ON e.id = c.idempresa ";
    $query.= "WHERE a.id = $idsrvproy";
    print $db->doSelectASJson($query);
});

$app->post('/csp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO proyectoservicio(idproyecto, idserviciobasico) VALUES($d->idproyecto, $d->idserviciobasico)");
    $db->doQuery("UPDATE serviciobasico SET asignado = 1 WHERE id = $d->idserviciobasico");
});

$app->post('/usp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $idsrv = (int)$db->getOneField("SELECT idserviciobasico FROM proyectoservicio WHERE id = $d->id");
    $db->doQuery("UPDATE proyectoservicio SET idserviciobasico = $d->idserviciobasico WHERE id = $d->id");
    if($idsrv > 0 && (int)$d->idserviciobasico != $idsrv){
        $db->doQuery("UPDATE serviciobasico SET asignado = 0 WHERE id = $idsrv");
        $db->doQuery("UPDATE serviciobasico SET asignado = 1 WHERE id = $d->idserviciobasico");
    }
});

$app->post('/dsp', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM proyectoservicio WHERE id = $d->id");
    $db->doQuery("UPDATE serviciobasico SET asignado = 0 WHERE id = $d->idserviciobasico");
});


//API para usuarios por proyecto
$app->get('/usrproy/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $query = "SELECT a.id, a.idusuario, a.idproyecto, CONCAT(b.nombre, ' (', b.usuario,')') AS usuario ";
    $query.= "FROM usuarioproyecto a INNER JOIN usuario b ON b.id = a.idusuario ";
    $query.= "WHERE a.idproyecto = $idproyecto ";
    $query.= "ORDER BY b.nombre, b.usuario";
    print $db->doSelectASJson($query);
});

$app->get('/usrdisp/:idproyecto', function($idproyecto){
    $db = new dbcpm();
    $query = "SELECT id, CONCAT(nombre, ' (', usuario,')') AS usuario FROM usuario WHERE id NOT IN(SELECT idusuario FROM usuarioproyecto WHERE idproyecto = $idproyecto) ORDER BY nombre, usuario";
    print $db->doSelectASJson($query);
});

$app->post('/cpu', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("INSERT INTO usuarioproyecto(idusuario, idproyecto) VALUES($d->idusuario, $d->idproyecto)");
});

$app->post('/dpu', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $db->doQuery("DELETE FROM usuarioproyecto WHERE id = $d->id");
});

//API para reportes de proyectos
$app->post('/rptlstproy', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $fempresa = (int)$d->idempresa > 0 ? "WHERE b.id = ".$d->idempresa." " : "";
    $query = "SELECT b.id AS idempresa, b.nomempresa, a.id AS idproyecto, a.nomproyecto, c.id AS idtipoproyecto, c.descripcion AS tipoproyecto, a.direccion, ";
    $query.= "CONCAT(e.finca, '-', e.folio, '-', e.libro) AS ffl, f.nomempresa AS empresaactivo, e.metros_muni AS mcuad, a.metros_rentable AS mcuadrentables, ";
    $query.= "IF( ISNULL(g.cantunidades), 0, g.cantunidades) AS cantunidades, h.canttipo ";
    $query.= "FROM proyecto a LEFT JOIN empresa b ON b.id = a.idempresa LEFT JOIN tipo_proyecto c ON c.id = a.tipo_proyecto ";
    $query.= "LEFT JOIN detalle_activo_proyecto d ON a.id = d.idproyecto LEFT JOIN activo e ON e.id = d.idactivo LEFT JOIN empresa f ON f.id = e.idempresa ";
    $query.= "LEFT JOIN (SELECT idproyecto, COUNT(idunidad) AS cantunidades FROM proyectounidad GROUP BY idproyecto) g ON a.id = g.idproyecto ";
    $query.= "LEFT JOIN (";
    $query.= "SELECT idproyecto, GROUP_CONCAT(canttipo ORDER BY canttipo SEPARATOR ',') AS canttipo FROM (";
    $query.= "SELECT a.idproyecto, CONCAT(b.descripcion, ': ', COUNT(a.idtipolocal)) AS canttipo FROM unidad a INNER JOIN tipolocal b ON b.id = a.idtipolocal ";
    $query.= "GROUP BY a.idproyecto, a.idtipolocal) AS cantidades GROUP BY idproyecto";
    $query.= ") h ON a.id = h.idproyecto ";
    $query.= $fempresa;
    $query.= "ORDER BY b.propia DESC, b.nomempresa, a.nomproyecto, f.propia DESC, f.nomempresa, e.finca, e.folio, e.libro";
    print $db->doSelectASJson($query);
});

$app->post('/rptdocsvence', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT b.id AS idproyecto, b.nomproyecto, a.idadjunto, a.nomadjunto, a.ubicacion, c.descripcion AS tipodoc, ";
    $query.= "a.numero, a.fvence ";
    $query.= "FROM proyecto_adjunto a INNER JOIN proyecto b ON b.id = a.headerid INNER JOIN tipodocproy c ON c.id = a.idtipodocproy ";
    $query.= "WHERE a.fvence <= '".$d->fvencestr."' ";
    $query.= "ORDER BY b.nomproyecto, a.fvence, c.descripcion";
    $data = $conn->query($query)->fetchAll(5);
    print json_encode($data);
});

$app->run();