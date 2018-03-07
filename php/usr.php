<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

$app->post('/auth', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('usuario',['id', 'nombre', 'usuario', 'correoe'], [
        'AND' => [
            'usuario' => $d->usr,
            'contrasenia' => $d->pwd
        ]
    ]);

    if(count($data) > 0){
        $db->initSession($data[0]);
        $data[0]['logged'] = true;
    }
    else
        $data[0]['logged'] = false;

    print json_encode($data);
});

$app->get('/getsess', function(){
    $db = new dbcpm();
    print json_encode($db->getSession());
});

$app->get('/setempresess/:idempre', function($idempre){
    $db = new dbcpm();
    print json_encode($db->setEmpreSess($idempre));
});

$app->get('/getultempre/:idusuario', function($idusuario){
    $db = new dbcpm();
    print json_encode(['ultempre' => (int)$db->getOneField("SELECT idultimaempresa FROM usuario WHERE id = $idusuario")]);
});

$app->get('/setultempre/:idusuario/:idempresa', function($idusuario, $idempresa){
    $db = new dbcpm();
    $db->doQuery("UPDATE usuario SET idultimaempresa = $idempresa WHERE id = $idusuario");
});

$app->get('/logout', function(){
    $db = new dbcpm();
    print json_encode($db->finishSession());
});

$app->get('/menu/:idusr', function($idusr){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = 'SELECT DISTINCT a.id AS IdModulo, a.descmodulo ';
    $query.= 'FROM modulo a INNER JOIN menu b ON a.id = b.idmodulo INNER JOIN itemmenu c ON b.id = c.idmenu ';
    $query.= 'INNER JOIN permiso d ON c.id = d.iditemmenu ';
    $query.= 'WHERE d.idusuario = '.$idusr.' AND d.accesar = 1 ';
    $query.= 'ORDER BY a.descmodulo';
    $modulos = $conn->query($query)->fetchAll(5);

    $cntModulos = count($modulos) - 1;
    $menu = [];

    for($i = 0; $i <= $cntModulos; $i++) {
        $menu[] = ['idmodulo' => $modulos[$i]->IdModulo, 'descmodulo' => $modulos[$i]->descmodulo, 'menus' => []];
    }

    $cntMods = count($menu) - 1;

    for($i = 0; $i <= $cntMods; $i++){
        $query = 'SELECT DISTINCT b.id AS IdMenu, b.descmenu ';
        $query.= 'FROM modulo a INNER JOIN menu b ON a.id = b.idmodulo INNER JOIN itemmenu c ON b.id = c.idmenu ';
        $query.= 'INNER JOIN permiso d ON c.id = d.iditemmenu ';
        $query.= 'WHERE b.idmodulo = '.$menu[$i]['idmodulo'].' AND d.idusuario = '.$idusr.' AND d.accesar = 1 ';
        $query.= 'ORDER BY b.descmenu';
        $losMenus = $conn->query($query)->fetchAll(5);

        $cntMnu = count($losMenus) - 1;
        for($j = 0; $j <= $cntMnu; $j++){
            $menu[$i]['menus'][] = ['idmenu' => $losMenus[$j]->IdMenu, 'descmenu' => $losMenus[$j]->descmenu, 'items' => []];
        }

        $cnt = count($menu[$i]['menus']) - 1;
        for($j = 0; $j <= $cnt; $j++){
            $query = 'SELECT c.id AS IdItemMenu, c.descitemmenu, c.url, d.id AS IdPermiso, d.idusuario AS IdUsuario, ';
            $query.= 'd.crear, d.modificar, d.eliminar ';
            $query.= 'FROM modulo a INNER JOIN menu b ON a.id = b.idmodulo INNER JOIN itemmenu c ON b.id = c.idmenu ';
            $query.= 'INNER JOIN permiso d ON c.id = d.iditemmenu ';
            $query.= 'WHERE c.idmenu = '.$menu[$i]['menus'][$j]['idmenu'].' AND d.idusuario = '.$idusr.' AND d.accesar = 1 ';
            $query.= 'ORDER BY c.descitemmenu';
            $losItems = $conn->query($query)->fetchAll(5);

            $cntIt = count($losItems) - 1;
            for($k = 0; $k <= $cntIt; $k++){
                $menu[$i]['menus'][$j]['items'][] = ['iditemmenu' => $losItems[$k]->IdItemMenu,
                    'descitemmenu' => $losItems[$k]->descitemmenu,
                    'url' => $losItems[$k]->url];
            }
        }
    }
    //echo '<pre>'; print_r($menu); echo '</pre>';
    print json_encode($menu);
});

$app->get('/perfil/:idusr', function($idusr){
    $db = new dbcpm();
    $conn = $db->getConn();
    $data = $conn->select('usuario', ['id', 'nombre', 'usuario', 'contrasenia', 'correoe'], ['id' => $idusr]);
    print json_encode($data);
});

$app->get('/lstperfiles', function(){
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "SELECT id, nombre, usuario, correoe FROM usuario ORDER BY nombre";
    $perfiles = $conn->query($query)->fetchAll(5);
    print json_encode($perfiles);
});

$app->get('/permisos/:idusr', function($idusr){
    $db = new dbcpm();
    $conn = $db->getConn();

    $query = 'INSERT INTO permiso(idusuario, iditemmenu) SELECT '.$idusr.', id FROM itemmenu ';
    $query.= 'WHERE id NOT IN (SELECT iditemmenu FROM permiso WHERE idusuario = '.$idusr.')';
    $ins = $conn->query($query);

    $query = 'SELECT a.id AS idpermiso, d.descmodulo, c.descmenu, b.descitemmenu, a.accesar, a.crear, a.modificar, a.eliminar ';
    $query.= 'FROM permiso a INNER JOIN itemmenu b ON b.id = a.iditemmenu INNER JOIN menu c ON c.id = b.idmenu ';
    $query.= 'INNER JOIN modulo d ON d.id = c.idmodulo ';
    $query.= 'WHERE a.idusuario = '.$idusr.' ';
    $query.= 'ORDER BY d.descmodulo, c.descmenu, b.descitemmenu';
    $permisos = $conn->query($query)->fetchAll(5);
    print json_encode($permisos);
});

$app->post('/setperm', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $campo = ['a' => "accesar", 'c' => "crear", 'm' => "modificar", 'e' => "eliminar"];
    $query = 'UPDATE permiso SET '.$campo[$d->tipo].' = '.$d->valor.' WHERE id = '.$d->idpermiso;
    $act = $conn->query($query);
});

$app->post('/c', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "INSERT INTO usuario(nombre, usuario, contrasenia, correoe) VALUES(";
    $query.= "'".$d->nombre."', '".$d->usuario."', '".$d->contrasenia."', '".$d->correoe."')";
    $ins = $conn->query($query);
    $query = "SELECT LAST_INSERT_ID()";
    $lastid = $conn->query($query)->fetchColumn(0);
    print json_encode(['lastid' => $lastid]);
});

$app->post('/updperf', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $conn = $db->getConn();
    $query = "UPDATE usuario SET nombre = '".$d->nombre."', usuario = '".$d->usuario."', ";
    $query.= "contrasenia = '".$d->contrasenia."', correoe = '".$d->correoe."' WHERE id = ".$d->id;
    $act = $conn->query($query);
});

$app->post('/getPermisosRoute', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "SELECT a.id, a.idusuario, a.iditemmenu, b.url, a.accesar, a.crear, a.modificar, a.eliminar ";
    $query.= "FROM permiso a INNER JOIN itemmenu b ON b.id = a.iditemmenu ";
    $query.= "WHERE a.idusuario = ".$d->idusuario." AND url = '".$d->ruta."' LIMIT 1";
    print $db->doSelectASJson($query);
});

$app->get('/getpermiso/:idusr/:ruta/:permiso', function($idusr, $ruta, $permiso){
    $db = new dbcpm();
    $p = 'accesar';
    switch($permiso){
        case 'a': $p = 'accesar'; break;
        case 'c': $p = 'crear'; break;
        case 'm': $p = 'modificar'; break;
        case 'e': $p = 'eliminar'; break;
    }
    $query = "SELECT a.$p FROM permiso a INNER JOIN itemmenu b ON b.id = a.iditemmenu WHERE a.idusuario = $idusr AND b.url = '$ruta' LIMIT 1";
    print json_encode(['permiso' => (int)$db->getOneField($query)]);
});

$app->run();