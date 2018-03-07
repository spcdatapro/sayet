<?php
require 'vendor/autoload.php';
require_once 'db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');

//API para dashboard
// --- API para favoritos
$app->get('/favusr/:idusr', function($idusr){
    $db = new dbcpm();
    $query = "SELECT a.posicion, d.descmodulo AS modulo, c.descmenu AS menu, b.descitemmenu, b.url, b.descitemmenu AS itemmenu ";
    $query.= "FROM favorito a INNER JOIN itemmenu b ON b.id = a.iditemmenu INNER JOIN menu c ON c.id = b.idmenu INNER JOIN modulo d ON d.id = c.idmodulo ";
    $query.= "WHERE a.idusuario = $idusr ";
    $query.= "ORDER BY a.posicion";
    print $db->doSelectASJson($query);
});

$app->get('/favspend/:idusr', function($idusr){
    $db = new dbcpm();
    $query = "SELECT c.id, CONCAT(a.descmodulo, ' --> ', b.descmenu, ' --> ', c.descitemmenu) AS itemmenu, c.url ";
    $query.= "FROM modulo a INNER JOIN menu b ON a.id = b.idmodulo INNER JOIN itemmenu c ON b.id = c.idmenu INNER JOIN permiso d ON c.id = d.iditemmenu ";
    $query.= "WHERE d.idusuario = $idusr AND d.accesar = 1 AND c.id NOT IN(SELECT iditemmenu FROM favorito WHERE idusuario = $idusr) ";
    $query.= "ORDER BY a.descmodulo, b.descmenu, c.descitemmenu";
    print $db->doSelectASJson($query);
});

$app->post('/cfav', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $existe = (int)$db->getOneField("SELECT id FROM favorito WHERE idusuario = $d->idusr AND posicion = $d->posicion") > 0;
    if($existe){
        $query = "UPDATE favorito SET iditemmenu = $d->iditemmenu WHERE idusuario = $d->idusr AND posicion = $d->posicion";
    }else{
        $query = "INSERT INTO favorito(idusuario, iditemmenu, posicion) VALUES($d->idusr, $d->iditemmenu, $d->posicion)";
    }
    $db->doQuery($query);
});

$app->post('/ffav', function(){
    $d = json_decode(file_get_contents('php://input'));
    $db = new dbcpm();
    $query = "DELETE FROM favorito WHERE idusuario = $d->idusr AND posicion = $d->posicion";
    $db->doQuery($query);
});


$app->get('/activosemp', function(){
    $db = new dbcpm();
    $query = "SELECT a.id AS idempresa, a.nomempresa, COUNT(b.id) AS conteo ";
    $query.= "FROM empresa a LEFT JOIN activo b ON a.id = b.idempresa ";
    $query.= "GROUP BY a.id ";
    $query.= "ORDER BY 3 DESC, a.nomempresa";
    print $db->doSelectASJson($query);
});

$app->get('/proyectosemp', function(){
    $db = new dbcpm();
    $query = "SELECT a.id AS idempresa, a.nomempresa, COUNT(b.id) AS conteo ";
    $query.= "FROM empresa a LEFT JOIN proyecto b ON a.id = b.idempresa ";
    $query.= "GROUP BY a.id ";
    $query.= "ORDER BY 3 DESC, a.nomempresa";
    print $db->doSelectASJson($query);
});




$app->run();