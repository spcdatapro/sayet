<?php
require_once 'vendor/catfan/medoo/medoo.php';
class dbcpm{

    private $dbHost = 'localhost';
    private $dbUser = 'root';
    private $dbPass = 'PoChoco2016';
    //private $dbPass = 'Solon_06';
    private $dbSchema = 'sayet';
    //private $dbSchema = 'sayetprod';

    private $dbConn;

    public $_formatoFecha = '%d/%m/%Y';
    public $_formatoFechaHora = '%d/%m/%Y %H:%i:%s';

    public function getDbHost() { return $this->dbHost; }
    public function getDbUser() { return $this->dbUser; }
    public function getDbPass() { return $this->dbPass; }
    public function getDbSchema() { return $this->dbSchema; }
    public function getConn() { return $this->dbConn; }


    function __construct() {
        $this->dbConn = new medoo([
            'database_type' => 'mysql',
            'database_name' => $this->dbSchema,
            'server' => $this->dbHost,
            'username' => $this->dbUser,
            'password' => $this->dbPass,
            'charset' => 'utf8'
        ]);
    }

    function __destruct() {
        unset($this->dbConn);
    }

    public function doSelectASJson($query){ return json_encode($this->dbConn->query($query)->fetchAll(5)); }

    public function doQuery($query) { $this->dbConn->query($query); }

    public function getQuery($query) { return $this->dbConn->query($query)->fetchAll(5); }

    public function getQueryAsArray($query) { return $this->dbConn->query($query)->fetchAll(3); }

    public function getLastId(){return $this->dbConn->query('SELECT LAST_INSERT_ID()')->fetchColumn(0);}

    public function getOneField($query){return $this->dbConn->query($query)->fetchColumn(0);}

    public function calculaISR($subtot, $tc = 1){
        $query = "SELECT id, de, a, porcentaje, importefijo, enexcedente, FLOOR(de) AS excedente FROM isr WHERE ".$subtot." >= de AND ".$subtot." <= a LIMIT 1";
        $arrisr = $this->getQuery($query);
        if(count($arrisr) > 0){ $infoisr = $arrisr[0]; } else { return 0.00; }
        //var_dump($infoisr); return 0.00;
        if((int)$infoisr->enexcedente === 0){
            $isr = round((float)$infoisr->importefijo + ($subtot * (float)$infoisr->porcentaje / 100), 2);
        }else{
            $isr = round((float)$infoisr->importefijo + (($subtot - (float)$infoisr->excedente) * (float)$infoisr->porcentaje / 100), 2);
        }
        return $isr;
    }

    public function calculaRetIVA($base, $esgubernamental, $monto, $esmaquila = false, $iva = 0){
        if($iva == 0){ $iva = $monto - $base; }
        if($base > 2500.00){
            if($esgubernamental && $base > 30000.00){
                return round($iva * 0.25, 2);
            }else{
                if(!$esmaquila){
                    return round($iva * 0.15, 2);
                }else{
                    return round($iva * 0.65, 2);
                }                
            }
        }
        return 0.00;
    }

    public function nombreMes($numero = 1, $abreviatura = false, $mayuscula = false){
        $nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $abreviaturas = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        if(!$abreviatura){
            return !$mayuscula ? $nombres[$numero - 1] : strtoupper($nombres[$numero - 1]);
        }
        return !$mayuscula ? $abreviaturas[$numero - 1] : strtoupper($abreviaturas[$numero - 1]);
    }

    public function CallJSReportAPI($method, $url, $data = false){
        $curl = curl_init();

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data) {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    public function gen_uid($l=5){
        $str = "";
        for($x = 0; $x < $l; $x++){
            $str.= substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 1);
        }
        return $str;
    }

    public function crearTablasReportesConta($cual = ''){
        $crud = '';
        $tblname = '';
        switch($cual){
            case 'dm' :
                $tblname = 'rdm'.$this->gen_uid();
                $crud = "
                    CREATE TABLE $tblname (
                      id int(10) unsigned NOT NULL AUTO_INCREMENT,
                      idcuentac int(11) NOT NULL DEFAULT '0',
                      codigo varchar(10) NOT NULL,
                      nombrecta varchar(75) NOT NULL,
                      tipocuenta bit(1) NOT NULL DEFAULT b'0',
                      anterior decimal(20,2) NOT NULL DEFAULT '0.00',
                      debe decimal(20,2) NOT NULL DEFAULT '0.00',
                      haber decimal(20,2) NOT NULL DEFAULT '0.00',
                      actual decimal(20,2) NOT NULL DEFAULT '0.00',
                      PRIMARY KEY (id),
                      KEY CodigoASC (codigo) USING BTREE,
                      KEY TipoCuentaASC (tipocuenta) USING BTREE
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
                break;
            case 'bs' :
                $tblname = 'rbs'.$this->gen_uid();
                $crud = "
                    CREATE TABLE $tblname (
                      id int(10) unsigned NOT NULL AUTO_INCREMENT,
                      idcuentac int(11) NOT NULL DEFAULT '0',
                      codigo varchar(10) NOT NULL,
                      nombrecta varchar(75) NOT NULL,
                      tipocuenta bit(1) NOT NULL DEFAULT b'0',
                      anterior decimal(20,2) NOT NULL DEFAULT '0.00',
                      debe decimal(20,2) NOT NULL DEFAULT '0.00',
                      haber decimal(20,2) NOT NULL DEFAULT '0.00',
                      actual decimal(20,2) NOT NULL DEFAULT '0.00',
                      PRIMARY KEY (id),
                      KEY CodigoASC (codigo) USING BTREE,
                      KEY TipoCuentaASC (tipocuenta) USING BTREE
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
                break;
            case 'bg' :
                $tblname = 'rbg'.$this->gen_uid();
                $crud = "
                    CREATE TABLE $tblname (
                      id int(10) unsigned NOT NULL AUTO_INCREMENT,
                      idcuenta int(10) unsigned NOT NULL,
                      codigo varchar(10) NOT NULL,
                      nombrecta varchar(75) NOT NULL,
                      tipocuenta bit(1) NOT NULL DEFAULT b'0',
                      actpascap int(10) unsigned NOT NULL DEFAULT '0',
                      parasuma bit(1) NOT NULL DEFAULT b'0',
                      estotal bit(1) NOT NULL DEFAULT b'0',
                      saldo decimal(20,2) NOT NULL DEFAULT '0.00',
                      PRIMARY KEY (id)
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
                break;
            case 'er' :
                $tblname = 'rer'.$this->gen_uid();
                $crud = "
                    CREATE TABLE $tblname (
                      id int(10) unsigned NOT NULL AUTO_INCREMENT,
                      idcuenta int(10) unsigned NOT NULL,
                      codigo varchar(10) NOT NULL,
                      nombrecta varchar(75) NOT NULL,
                      tipocuenta bit(1) NOT NULL DEFAULT b'0',
                      ingresos bit(1) NOT NULL DEFAULT b'0',
                      parasuma bit(1) NOT NULL DEFAULT b'0',
                      estotal bit(1) NOT NULL DEFAULT b'0',
                      saldo decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_ene decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_feb decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_mar decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_abr decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_may decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_jun decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_jul decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_ago decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_sep decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_oct decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_nov decimal(20,2) NOT NULL DEFAULT '0.00',
                      s_dic decimal(20,2) NOT NULL DEFAULT '0.00',
                      PRIMARY KEY (id)
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
                break;
            case 'cp':
                $tblname = 'rcp'.$this->gen_uid();
                $crud = "
                    CREATE TABLE $tblname (
                      id int(10) unsigned NOT NULL AUTO_INCREMENT,
                      idcuentac int(11) NOT NULL DEFAULT '0',
                      codigo varchar(10) NOT NULL,
                      nombrecta varchar(75) NOT NULL,
                      tipocuenta bit(1) NOT NULL DEFAULT b'0',
                      anterior decimal(20,2) NOT NULL DEFAULT '0.00',
                      debe decimal(20,2) NOT NULL DEFAULT '0.00',
                      haber decimal(20,2) NOT NULL DEFAULT '0.00',
                      actual decimal(20,2) NOT NULL DEFAULT '0.00',
                      PRIMARY KEY (id),
                      KEY CodigoASC (codigo) USING BTREE,
                      KEY TipoCuentaASC (tipocuenta) USING BTREE
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
                break;
        }
        $this->doQuery($crud);
        return $tblname;
    }

    public function eliminarTablasRepConta($tblname = ''){
        if($tblname !== ''){
            $crud = "DROP TABLE IF EXISTS $tblname";
            $this->doQuery($crud);
        }
    }

    public function initSession($userdata){
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['uid'] = $userdata['id'];
        $_SESSION['nombre'] = $userdata['nombre'];
        $_SESSION['usuario'] = $userdata['usuario'];
        $_SESSION['correoe'] = $userdata['correoe'];
        $_SESSION['workingon'] = 0;
        $_SESSION['logged'] = true;
    }

    public function getSession(){
        try{
            session_start();
            $sess = array();
            $sess['uid'] = $_SESSION['uid'];
            $sess['nombre'] = $_SESSION['nombre'];
            $sess['usuario'] = $_SESSION['usuario'];
            $sess['correoe'] = $_SESSION['correoe'];
            $sess['workingon'] = $_SESSION['workingon'];
            $sess['logged'] = $_SESSION['logged'];
            return $sess;
        }catch(Exception $e){
            return ['Error' => $e->getMessage()];
        }
    }

    public function setEmpreSess($qIdEmpresa){
        try{
            session_start();
            $_SESSION['workingon'] = (int) $qIdEmpresa;
            return ['workingon' => $_SESSION['workingon']];
        }catch(Exception $e){
            return ['Error' => $e->getMessage()];
        }
    }

    public function finishSession(){
        if (!isset($_SESSION)) {
            session_start();
        }
        if(isset($_SESSION['uid'])){
            unset($_SESSION['uid']);
            unset($_SESSION['nombre']);
            unset($_SESSION['usuario']);
            unset($_SESSION['correoe']);
            unset($_SESSION['workingon']);
            unset($_SESSION['logged']);
            $info='info';
            if(isSet($_COOKIE[$info])){
                $cookie_time = 86400;
                setcookie ($info, '', time() - $cookie_time);
            }
            $msg="Logged Out Successfully...";
        }
        else{
            $msg = "Not logged in...";
        }
        return $resultado[] = $msg;
    }
}