<?php
//ini_set('memory_limit', '2048M');
ini_set('memory_limit', '3072M');
ini_set('max_execution_time', 28800);
require 'vendor/autoload.php';
require_once 'db.php';
require_once 'NumberToLetterConverter.class.php';

$app = new \Slim\Slim();
//$app->response->headers->set('Content-Type', 'application/json');
$app->response->headers->set('Content-Type', 'text/html');
$app->response->headers->set('Cache-Control', 'no-cache');

$app->get('/bancos', function(){
    $dbf = dbase_open('fox/06/banc01.DBF', 0);
    $bancos = [];
    if($dbf){
        $conteo = dbase_numrecords($dbf);
        for($i = 1; $i <= $conteo; $i++){
            $row = dbase_get_record_with_names($dbf, $i);
            $bancos[] = $row;
        }
        dbase_close($dbf);
    }
    print json_encode($bancos);
});

function chkDBCol($columna){
    $db = new dbcpm();
    $existe = (int)$db->getOneField("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'sayet' AND TABLE_NAME = 'banco' AND COLUMN_NAME = '$columna'");
    //$existe = (int)$db->getOneField("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'sayetprod' AND TABLE_NAME = 'banco' AND COLUMN_NAME = '$columna'");
    return $existe <= 0 ? false : true;
}

function padNum($num){ return (int)$num < 10 ? ("00".$num) : "0".$num; }

function isEmpty($val){ return $val == '' || is_null($val) ? true : false; }

function parseFecha($fecha){
    if(isEmpty(trim($fecha))){ return ''; }
    $anio = substr($fecha, 0, 4); $mes = substr($fecha, 4, 2); $dia = substr($fecha, 6, 2);
    return "$anio-$mes-$dia";
}

function migraCuentasContables($db, $ws, $idempresa){
    $dbf = dbase_open("$ws/cont01.DBF", 0);
    if($dbf){
        $conteo = dbase_numrecords($dbf);
        for($i = 1; $i <= $conteo; $i++){
            $row = dbase_get_record_with_names($dbf, $i);
            if((int)$row['deleted'] === 0){
                $codigofox = trim($row['CODIGO']);
                $query = "SELECT id, idempresa, codigo, nombrecta, tipocuenta FROM cuentac WHERE idempresa = $idempresa AND codigo = '$codigofox' LIMIT 1";
                $cuenta = $db->getQuery($query);
                if(count($cuenta) <= 0){
                    $query = "INSERT INTO cuentac(idempresa, codigo, nombrecta, tipocuenta) VALUES(";
                    $query.= "$idempresa, '$codigofox', '".utf8_encode(trim($row['NOMBRE']))."', ".(trim($row['TIPO']) == 'M' ? '0': '1');
                    $query.= ")";
                    echo $query."<br/>";
                    $db->doQuery($query);
                }else{
                    echo "La cuenta contable $codigofox ya existe para la empresa $idempresa...<br/>";
                }
            }
        }
        dbase_close($dbf);
    }
    echo "Migracion de cuentas contables terminada...<br/>";
}

function migraBancos($db, $ws, $idempresa){
    if(!chkDBCol('idfox')){ $db->doQuery("ALTER TABLE banco ADD COLUMN idfox VARCHAR(4) NULL AFTER id, ADD INDEX IdFoxASC (idfox ASC)"); }

    $dbf = dbase_open("$ws/banco.DBF", 0);
    if($dbf){
        $conteo = dbase_numrecords($dbf);
        for($i = 1; $i <= $conteo; $i++){
            $row = dbase_get_record_with_names($dbf, $i);
            if((int)$row['deleted'] === 0){
                preg_match_all('!\d+!', $row['NO_CUENTA'], $matches);
                $ctafox = trim(implode($matches[0]));
                $idcuentac = (int)$db->getOneField("SELECT id FROM cuentac WHERE idempresa = $idempresa AND codigo = '".trim($row['CTA_CONTAB'])."'");
                if($idcuentac >= 0){
                    $query = "SELECT id, idempresa, idcuentac, nombre, nocuenta, siglas, nomcuenta, correlativo, idmoneda, idtipoimpresion FROM banco WHERE idempresa = $idempresa AND TRIM(digits(nocuenta)) = '$ctafox' LIMIT 1";
                    $cuenta = $db->getQuery($query);
                    if(count($cuenta) <= 0){
                        $query = "INSERT INTO banco(idfox, idempresa, idcuentac, nombre, nocuenta, siglas, nomcuenta, correlativo, idmoneda, idtipoimpresion) VALUES(";
                        $query.= "'".trim($row['CODIGO'])."', $idempresa, $idcuentac, '".utf8_encode(trim($row['NOMBRE_BAN']))."', '".trim($row['NO_CUENTA'])."', '".utf8_encode(trim($row['SIGLAS']))."', '".utf8_encode(trim($row['NOMBRE_CUE']))."', 1, 2, 0";
                        $query.= ")";
                        echo $query."<br/>";
                        $db->doQuery($query);
                    }else{
                        echo "La cuenta de banco $ctafox ya existe para la empresa $idempresa...<br/>";
                        $query = "UPDATE banco SET idfox = '".trim($row['CODIGO'])."', idcuentac = $idcuentac WHERE id = ".$cuenta[0]->id;
                        echo $query."<br/>";
                        $db->doQuery($query);
                    }
                }
            }
        }
        dbase_close($dbf);
    }
    echo "Migracion de bancos terminada...<br/>";
}

function migraProveedores($db, $ws, $idempresa){
    $provsdb = $db->getQuery("SELECT nit FROM proveedor ORDER BY nit");
    $nitsdb = [];
    $cntProvsDB = count($provsdb);
    for($i = 0; $i < $cntProvsDB; $i++){ $nitsdb[] = trim(preg_replace('/[^A-Za-z0-9]/', '', $provsdb[$i]->nit)); }

    $dbf = dbase_open("$ws/cxpa01.DBF", 0);
    $detdbf = dbase_open("$ws/cxpa02.DBF", 0);
    if($dbf){
        $conteo = dbase_numrecords($dbf);
        for($i = 1; $i <= $conteo; $i++){
            $row = dbase_get_record_with_names($dbf, $i);
            if((int)$row['deleted'] === 0){
                $nitfox = preg_replace('/[^A-Za-z0-9]/', '', trim(str_replace(' ', '', trim($row['NIT']))));
                if(!array_search($nitfox, $nitsdb)){
                    $query = "INSERT INTO proveedor(nit, nombre, direccion, telefono, correo, concepto, chequesa, retensionisr, diascred, limitecred, pequeniocont, idmoneda, tipocambioprov) VALUES(";
                    $query.= "'".trim($row['NIT'])."', '".utf8_encode(trim($row['NOMBRE']))."', '".(utf8_encode(trim($row['DIRECCION1'])).(trim($row['DIRECCION2']) != '' ? (', '.utf8_encode(trim($row['DIRECCION2']))) : ''))."', ";

                    $telefono = (trim($row['TELEFONO']) != '' ? trim($row['TELEFONO']) : "");
                    if($telefono != '' && trim($row['FAX']) != ''){ $telefono.= ', '; }
                    $telefono .= (trim($row['FAX']) != '' ? ("FAX: ".trim($row['FAX'])) : "");
                    $telefono = $telefono != '' ? ("'$telefono'") : "NULL";

                    $query.= "$telefono, NULL, ".(trim($row['CONTACTO']) != '' ? ("'".utf8_encode(trim($row['CONTACTO']))."'") : "NULL").", ".(trim($row['FACTURA']) != '' ? ("'".utf8_encode(trim($row['FACTURA']))."'") : "NULL").", ";
                    $query.= (int)$row['RETENCION'].", ".(int)$row['DIASC'].", ".round((float)$row['LIMITEC'], 2).", 0, 1, 1.00";
                    $query.= ")";
                    echo $query."<br/>";
                    $db->doQuery($query);
                    $lastId = (int)$db->getLastId();
                    if($detdbf && $lastId > 0){
                        $cntDet = dbase_numrecords($detdbf);
                        for($j = 1; $j <= $cntDet; $j++){
                            $rdet = dbase_get_record_with_names($detdbf, $j);
                            if((int)$rdet['deleted']=== 0){
                                if(trim($rdet['NIT']) == trim($row['NIT'])){
                                    $idcuentac = (int)$db->getOneField("SELECT id FROM cuentac WHERE idempresa = $idempresa AND codigo = '".trim($rdet['CUENTA'])."'");
                                    if($idcuentac > 0){
                                        $existe = (int)$db->getOneField("SELECT COUNT(id) FROM detcontprov WHERE idproveedor = $lastId AND idcuentac = $idcuentac");
                                        if($existe <= 0){
                                            $query = "INSERT INTO detcontprov(idproveedor, idcuentac) VALUES($lastId, $idcuentac)";
                                            echo $query."<br/>";
                                            $db->doQuery($query);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }else{
                    echo "El proveedor $nitfox ya existe en la base de datos...<br/>";
                }
            }
        }
        dbase_close($dbf);
        dbase_close($detdbf);
    }
    echo "Migracion de proveedores terminada...<br/>";
}

function getDetalleContable($ws){
    $rows = [];
    return $rows;
    /*
    $detcont = dbase_open("$ws/detacomp.DBF", 0);
    $cntDet = dbase_numrecords($detcont);
    for($j = 1; $j <= $cntDet; $j++){
        $rdet = dbase_get_record_with_names($detcont, $j);
        if((int)$rdet['deleted'] === 0){
            $rows[] = $rdet;
        }
    }
    //var_dump($rows);
    dbase_close($detcont);
    return $rows;
    */
}

function migraTransaccionesBancarias($db, $ws, $idempresa, $detcont){
    if(!chkDBCol('bancofox') && !chkDBCol('tipofox') && !chkDBCol('documentofox') && !chkDBCol('idempresa')){
        $query = "ALTER TABLE tranban ADD COLUMN bancofox VARCHAR(4) NULL AFTER iddetpagopresup, ADD COLUMN tipofox VARCHAR(1) NULL AFTER bancofox, ADD COLUMN documentofox VARCHAR(10) NULL AFTER tipofox, ";
        $query.= "ADD COLUMN idempresa INTEGER UNSIGNED NULL AFTER documentofox, ADD INDEX ColsFox (bancofox ASC, tipofox ASC, documentofox ASC, idempresa ASC)";
        $db->doQuery($query);
    }

    $dbf = dbase_open("$ws/banc03.DBF", 0);
    $cntDet = count($detcont);
    if($dbf){
        $conteo = dbase_numrecords($dbf);
        for($i = 1; $i <= $conteo; $i++){
            $row = dbase_get_record_with_names($dbf, $i);
            if((int)$row['deleted'] === 0){
                $row['FECHA'] = parseFecha($row['FECHA']);
                $row['ANIO'] = (int)date_format(date_create($row['FECHA']), 'Y');
                if($row['ANIO'] >= 2016){
                    $idbanco = (int)$db->getOneField("SELECT id FROM banco WHERE TRIM(idfox) = '".trim($row['BANCO'])."' AND idempresa = $idempresa");
                    if($idbanco > 0 && trim($row['DOCUMENTO']) != ''){
                        $documento = preg_replace('/[^0-9]/', '', trim($row['DOCUMENTO']));
                        $tipodoc = trim($row['TIPO']);
                        $query = "SELECT id FROM tranban WHERE idbanco = $idbanco AND tipotrans = '$tipodoc' AND numero = $documento LIMIT 1";
                        $idtb = (int)$db->getOneField($query);
                        if($idtb <= 0){
                            $query = "INSERT INTO tranban(idbanco, tipotrans, numero, fecha, monto, beneficiario, concepto, operado, fechaoperado, tipocambio, anulado, fechaanula, bancofox, tipofox, documentofox, idempresa) VALUES(";
                            $query.= "$idbanco, '$tipodoc', $documento, '".$row['FECHA']."', ".round((float)$row['MONTO'], 2).", '".utf8_encode(trim($row['BENEFICIAR']))."', ";

                            $concepto = isEmpty(trim($row['CONCEPTO'])) ? '' : trim($row['CONCEPTO']);
                            /*
                            if(!isEmpty($concepto) && !isEmpty(trim($row['CONCEPTO02']))){ $concepto.= '. '; }
                            $concepto.= isEmpty(trim($row['CONCEPTO02'])) ? '' : trim($row['CONCEPTO02']);
                            if(!isEmpty($concepto) && !isEmpty(trim($row['CONCEPTO03']))){ $concepto.= '. '; }
                            $concepto.= isEmpty(trim($row['CONCEPTO03'])) ? '' : trim($row['CONCEPTO03']);
                            if(!isEmpty($concepto) && !isEmpty(trim($row['CONCEPTO04']))){ $concepto.= '. '; }
                            $concepto.= isEmpty(trim($row['CONCEPTO04'])) ? '' : trim($row['CONCEPTO04']);
                            if(!isEmpty($concepto) && !isEmpty(trim($row['CONCEPTO05']))){ $concepto.= '. '; }
                            $concepto.= isEmpty(trim($row['CONCEPTO05'])) ? '' : trim($row['CONCEPTO05']);
                            */
                            $concepto = isEmpty($concepto) ? "NULL" : "'".utf8_encode($concepto)."'";

                            //$query.= "$concepto, ".$row['OPERADO'].", ".((int)$row['OPERADO'] == 0 ? "NULL" : ("'".$row['FECHA']."'")).", 1.0000, ";
                            $query.= "$concepto, 1, '".$row['FECHA']."', 1.0000, ";

                            $anulado = strpos(strtoupper($concepto), 'ANULADO') ? 1 : 0;

                            $query.= "$anulado, ".($anulado == 0 ? "NULL" : ("'".$row['FECHA']."'")).", ";
                            $query.= "'".trim($row['BANCO'])."', '".trim($row['TIPO'])."', '".trim($row['DOCUMENTO'])."', $idempresa";
                            $query.= ")";
                            echo $query."<br/>";
                            $db->doQuery($query);
                            /*
                            //Este es para el detalle contable de la transacción. Dólares no lleva detalle contable de FOX
                            $lastId = $db->getLastId();
                            if($lastId > 0){
                                for($j = 0; $j < $cntDet; $j++){
                                    $rdet = $detcont[$j];
                                    if(trim($rdet['BANCO']) == trim($row['BANCO']) && trim($rdet['TIPO_B']) == trim($row['TIPO']) && trim($rdet['DOCU_B']) == trim($row['DOCUMENTO'])){
                                        $idcuentac = (int)$db->getOneField("SELECT id FROM cuentac WHERE idempresa = $idempresa AND codigo = '".trim($rdet['CUENTA'])."'");
                                        if($idcuentac > 0){
                                            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado) VALUES(";
                                            $query.= "1, $lastId, $idcuentac, ".round((float)$rdet['DEBE'], 2).", ".round((float)$rdet['HABER'], 2).", '".utf8_encode(trim($rdet['REFERENCIA']))."', 1, $anulado";
                                            $query.= ")";
                                            echo $query."<br/>";
                                            $db->doQuery($query);
                                        }
                                    }
                                }
                            }
                            */
                        }else{
                            echo $query."<br/>";
                            echo "La transacción ".trim($row['TIPO'])."-".trim($row['DOCUMENTO'])." ya existe para este banco...<br/>";
                            $query = "UPDATE tranban SET bancofox = '".trim($row['BANCO'])."', tipofox = '".trim($row['TIPO'])."', documentofox = '".trim($row['DOCUMENTO'])."' WHERE id = $idtb";
                            echo $query."<br/>";
                            $db->doQuery($query);
                        }
                    }
                }
            }
        }
        dbase_close($dbf);
    }
    echo "Migracion de transacciones bancarias terminada...<br/>";
}

function migraCompra($db, $idempresa, $row){

    $idproveedor = (int)$db->getOneField("SELECT id FROM proveedor WHERE TRIM(nit) = '".trim($row['CNIT'])."' LIMIT 1");
    $query = "SELECT COUNT(id) FROM compra WHERE idempresa = $idempresa AND idproveedor = $idproveedor AND TRIM(serie) = '".trim($row['SERIE'])."' AND documento = ".((int)$row['CNUM_DOC']);
    $existe = (int)$db->getOneField($query);
    if($existe <= 0){
        $retenerisr = (int)$db->getOneField("SELECT retensionisr FROM proveedor WHERE id = $idproveedor");
        $query = "INSERT INTO compra(";
        $query.= "idempresa, idtipofactura, idproveedor, serie, documento, fechaingreso, mesiva, fechafactura, idtipocompra, conceptomayor, fechapago, totfact, noafecto, subtotal, iva, retenerisr, isr, idmoneda, tipocambio";
        $query.= ") VALUES(";
        //$query.= "$idempresa, ".(trim($row['CFAC_OTR']) == 'F' ? "6" : "5").", $idproveedor, '".trim($row['SERIE'])."', ".((int)$row['CNUM_DOC']).", '".parseFecha($row['CFECREAL'])."', ".((int)$row['VMES']).", ";
        $query.= "$idempresa, 6, $idproveedor, '".trim($row['SERIE'])."', ".((int)$row['CNUM_DOC']).", '".parseFecha($row['CFECREAL'])."', ".((int)$row['VMES']).", ";
        $query.= "'".parseFecha($row['CFECHA'])."', ".(trim($row['CTIPOCOMP_']) == 'B' ? "1" : "2").", '".utf8_encode(trim($row['CCONCEPTO']))."', ".(isEmpty($row['FECPAGO']) ? "NULL" : ("'".parseFecha($row['FECPAGO'])."'")).", ";
        $query.= round((float)$row['CTOTAL'], 2).", ".round((float)$row['CDESCTO'], 2).", ".(trim($row['CTIPOCOMP_']) == 'B' ? round((float)$row['CVAL_EXE'], 2) : round((float)$row['CVAL_GRA'], 2)).", ";
        $query.= round((float)$row['CVAL_IVA'], 2).", $retenerisr, ".($retenerisr == 0 ? "0.00" : $db->calculaISR((trim($row['CTIPOCOMP_']) == 'B' ? round((float)$row['CVAL_EXE'], 2) : round((float)$row['CVAL_GRA'], 2)))).", 1, 1.00";
        $query.= ")";
        echo $query."<br/>";
        $db->doQuery($query);
        $lastId = $db->getLastId();
        if(!isEmpty(trim($row['BANCO'])) && !isEmpty(trim($row['TIPO'])) && !isEmpty(trim($row['DOCUMENTO']))){
            $query = "SELECT id, monto FROM tranban WHERE idempresa = $idempresa AND bancofox = '".trim($row['BANCO'])."' AND tipofox = '".trim($row['TIPO'])."' AND documentofox = '".trim($row['DOCUMENTO'])."' LIMIT 1";
            echo $query."<br/>";
            $tranban = $db->getQuery($query);
            if(count($tranban) > 0){
                $tb = $tranban[0];
                $query = "INSERT INTO detpagocompra(idcompra, idtranban, monto) VALUES($lastId, $tb->id, $tb->monto)";
                echo $query."<br/>";
                $db->doQuery($query);
                $query = "INSERT INTO doctotranban(idtranban, idtipodoc, documento, fechadoc, monto, serie, iddocto) VALUES(";
                $query.= "$tb->id, 1, ".((int)$row['CNUM_DOC']).", '".parseFecha($row['CFECHA'])."', ".round((float)$row['CTOTAL'], 2).", '".trim($row['SERIE'])."', $lastId";
                $query.= ")";
                echo $query."<br/>";
                $db->doQuery($query);
            }
        }
    }else{
        echo "La factura ".trim($row['SERIE'])."-".trim($row['CNUM_DOC'])." ya existe para el proveedor ".trim($row['CNIT']);
    }
}

function migraCompras($db, $ws, $idempresa, $detcont){
    $dbf = dbase_open("$ws/compras.DBF", 0);
    $cntRegs = dbase_numrecords($dbf);
    for($i = 1; $i <= $cntRegs; $i++){
        $row = dbase_get_record_with_names($dbf, $i);
        $row['ANIO'] = (int)date_format(date_create(parseFecha($row['CFECHA'])), 'Y');
        if((int)$row['deleted'] === 0 && $row['ANIO'] >= 2016){
            switch(trim($row['TIPODOC'])){
                case 'C': migraCompra($db, $idempresa, $row); break;
            }
        }
    }
    dbase_close($dbf);
}

function checkCompraExiste($ws, $db){
    $dbf = dbase_open("$ws/COMPRASNP.DBF", 0);
    $cntRegs = dbase_numrecords($dbf);
    $faltantes = 0;
    for($i = 1; $i <= $cntRegs; $i++){
        $row = dbase_get_record_with_names($dbf, $i);
        $row['ANIO'] = (int)date_format(date_create(parseFecha($row['CFECHA'])), 'Y');
        if((int)$row['deleted'] === 0 && $row['ANIO'] >= 2016){
            $query = "SELECT COUNT(a.id) ";
            $query.= "FROM compra a LEFT JOIN proveedor b ON b.id = a.idproveedor ";
            $query.= "WHERE (TRIM(b.nit) = '".trim($row['CNIT'])."' OR TRIM(a.nit) = '".trim($row['CNIT'])."') AND TRIM(a.serie) = '".trim($row['SERIE'])."' AND a.documento = ".(int)$row['CNUM_DOC']." AND a.idempresa = ".(int)$row['IDEMP'];
            $existe = (int)$db->getOneField($query);
            if($existe <= 0){
                echo "ALERTA: La factura '".trim($row['SERIE'])."-".(int)$row['CNUM_DOC']."' del proveedor '".trim($row['CNIT'])."' en la empresa '".(int)$row['IDEMP']."' NO existe. (".trim($row['BANCO'] != '' ? 'CON' : 'SIN')." transacción bancaria)</br>";
                $faltantes++;
                migraCompra($db, (int)$row['IDEMP'], $row);
				echo "<br/><br/>";
            }
        }
    }
    dbase_close($dbf);
    echo "$faltantes facturas migradas...<br/>";
}

function getDetalleContableDirectas($ws, $cual){
    $detcont = dbase_open("$ws/cont$cual.DBF", 0);
    $cntDet = dbase_numrecords($detcont);
    $rows = [];
    for($j = 1; $j <= $cntDet; $j++){
        $rdet = dbase_get_record_with_names($detcont, $j);
        if((int)$rdet['deleted'] === 0 && (int)date_format(date_create(parseFecha($rdet['FECHA'])), 'Y') >= 2016 ){
            $rows[] = $rdet;
        }
    }
    dbase_close($detcont);
    return $rows;
}

function migraDirectas($db, $ws, $idempresa, $enc, $det){
    $encabezado = dbase_open("$ws/cont$enc.DBF", 0);
    $detalle = getDetalleContableDirectas($ws, $det);
    $cntDirectas = dbase_numrecords($encabezado);
    $directas = [];
    for($i = 1; $i <= $cntDirectas; $i++){
        $directa = dbase_get_record_with_names($encabezado, $i);
        $directa['DETCONT'] = [];
        if((int)$directa['deleted'] === 0 && (int)date_format(date_create(parseFecha($directa['FECHA'])), 'Y') >= 2016){
            $cntDeta = count($detalle);
            for($j = 0; $j < $cntDeta; $j++){
                $deta = $detalle[$j];
                if(trim($deta['NODOC']) == trim($directa['NODOC']) && trim($deta['TIPO']) == trim($directa['TIPO']) && isEmpty(trim($deta['BANCOS'])) ){
                    $directa['DETCONT'][] = $deta;
                }
            }
            if(count($directa['DETCONT']) > 0){
                $directas[] = $directa;
            }
        }
    }

    $conteo = count($directas);
    for($i = 0; $i < $conteo; $i++){
        $d = $directas[$i];
        $query = "INSERT INTO directa(idempresa, fecha) VALUES($idempresa, '".parseFecha($d['FECHA'])."')";
        $db->doQuery($query);
        echo $query."<br/>";
        $lastId = $db->getLastId();
        if($lastId >= 0){
            $contDet = count($d['DETCONT']);
            for($j = 0; $j < $contDet; $j++){
                $dc = $d['DETCONT'][$j];
                $idcuentac = (int)$db->getOneField("SELECT id FROM cuentac WHERE idempresa = $idempresa AND codigo = '".trim($dc['CODIGO'])."'");
                if($idcuentac > 0){
                    $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado) VALUES(";
                    $query.= "4, $lastId, $idcuentac, ".round((float)$dc['DEBE'], 2).", ".round((float)$dc['HABER'], 2).", '".utf8_encode(trim($dc['QDESCRIP']))."', 1, 0";
                    $query.= ")";
                    $db->doQuery($query);
                    echo $query."<br/>";
                }
            }
        }
    }
    echo "Partidas directas de empresa $idempresa migradas...<br/>";
}

function migraVentasRecibosContable($db){
    for($i = 2; $i <= 22; $i++){
        $idempfox = trim(padNum($i));
        $idempresa = (int)$db->getOneField("SELECT id FROM empresa WHERE idfox = '$idempfox' LIMIT 1");
        if($idempresa > 0){
            $encabezado = dbase_open("fox/vr/header$idempfox.DBF", 0);
            echo "header$idempfox.DBF<br/>";
            $cntEnc = dbase_numrecords($encabezado);
            if($cntEnc > 0){
                $detalle = dbase_open("fox/vr/polizafac$idempfox.DBF", 0);
                echo "polizafac$idempfox.DBF<br/>";
                $cntDet = dbase_numrecords($detalle);
                if($cntDet > 0){
                    for($j = 1; $j <= $cntEnc; $j++){
                        $enc = dbase_get_record_with_names($encabezado, $j);
                        if((int)$enc['deleted'] === 0 && (int)date_format(date_create(parseFecha($enc['FECHA'])), 'Y') >= 2016 ){
                            $query = "INSERT INTO directa(idempresa, fecha, concepto) VALUES($idempresa, '".parseFecha($enc['FECHA'])."', '".utf8_encode(trim($enc['DETALLE1']).". ".trim($enc['DETALLE2']))."')";
                            $db->doQuery($query);
                            echo $query."<br/>";
                            $lastId = (int)$db->getLastId();
                            if($lastId >= 0){
                                for($k = 1; $k <= $cntDet; $k++){
                                    $det = dbase_get_record_with_names($detalle, $k);
                                    if((int)$det['deleted'] === 0 && trim($det['NODOC_B']) === trim($enc['NODOC']) && trim($det['FECHA_B']) === trim($enc['FECHA']) && trim($det['TIPO_B']) === trim($enc['TIPO'])){
                                        $idcuentac = (int)$db->getOneField("SELECT id FROM cuentac WHERE idempresa = $idempresa AND codigo = '".trim($det['CODIGO_B'])."'");
                                        if($idcuentac > 0){
                                            $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor, activada, anulado) VALUES(";
                                            $concepto = '';
                                            if(trim($det['QDESCRIP']) != ''){ $concepto = trim($det['QDESCRIP']); }
                                            if(trim($det['QREFER']) != ''){
                                                if($concepto != ''){ $concepto .= '. '; }
                                                $concepto .= trim($det['QREFER']);
                                            }
                                            $query.= "4, $lastId, $idcuentac, ".round((float)$det['DEBE'], 2).", ".round((float)$det['HABER'], 2).", '".utf8_encode($concepto)."', 1, 0";
                                            $query.= ")";
                                            $db->doQuery($query);
                                            echo $query."<br/>";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                dbase_close($detalle);
            }
            dbase_close($encabezado);
        }
    }
}

function migraVentas($db){
    $n2l = new NumberToLetterConverter();
    $facturas = dbase_open("fox/vr/facturas.DBF", 0);
    $cntFacts = dbase_numrecords($facturas);
    echo "Total de facturas: $cntFacts.<br/>";
    for($i = 1; $i <= $cntFacts; $i++){
        $fact = dbase_get_record_with_names($facturas, $i);
        if((int)$fact['deleted'] === 0 && (int)date_format(date_create(parseFecha($fact['FECHA'])), 'Y') >= 2016) {
            $idempresa = (int)$db->getOneField("SELECT id FROM empresa WHERE idcafcfox = '".trim($fact['EMPRESA'])."'");
            if($idempresa > 0){
                $esinsertada = trim($fact['CONTRATO']) != '' ? 0 : 1;
                $idcontrato = $esinsertada === 0 ? ((int)$db->getOneField("SELECT idcontrato FROM correscontrato WHERE idcontratofox = '".trim($fact['CONTRATO'])."'")) : 0;
                $idcliente = $idcontrato > 0 ? ((int)$db->getOneField("SELECT idcliente FROM contrato WHERE id = $idcontrato")) : 0;
                $idtipofactura = strpos(strtoupper(trim($fact['SERIE'])), 'FACE') === false ? 2 : 1 ;
                $conceptomayor = utf8_encode("(".trim($fact['TIPOCARGO']).")".(trim($fact['NOTAS']) != '' ? (" ".trim($fact['NOTAS'])) : ""));
                //$nit = trim($fact['NITCF']) != '' ? ("'".trim($fact['NITCF'])."'") : "NULL";
                $nit = trim($fact['NIT']) != '' ? ("'".trim($fact['NIT'])."'") : "NULL";
                $nombrefac = trim($fact['NOMBREFAC']) != '' ? ("'".trim($fact['NOMBREFAC'])."'") : "NULL";
                $dirfac = trim($fact['DIRECCION']) != '' ? ("'".trim($fact['DIRECCION'])."'") : "NULL";
                $serie = trim($fact['SERIE']) != '' ? ("'".trim($fact['SERIE'])."'") : "NULL";
                $subtotal = round((float)$fact['MONTO'] - ((float)$fact['IVA'] + (float)$fact['ISR'] + (float)$fact['RETIVA']), 2);
                $firma = trim($fact['FIRMA']) != '' ? ("'".trim($fact['FIRMA'])."'") : "NULL";
                $anulada = trim($fact['STATUS']) == 'A';

                echo "ITERACÓN No. $i<br/>";
                $query = "INSERT INTO factura (";
                $query.= "idfox, idempresa, idtipofactura, idcontrato, idcliente, nit, nombre, serie, numero, fechaingreso, ";
                $query.= "mesiva, fecha, idtipoventa, conceptomayor, iva, total, noafecto, subtotal, retisr, retiva, ";
                $query.= "totalletras, totdescuento, noautorizacion, firmaelectronica, respuestagface, idmoneda, tipocambio, pagada, fechapago, ";
                $query.= "anulada, idrazonanulafactura, fechaanula, esinsertada, direccion";
                $query.= ") VALUES(";
                $query.= "'".trim($fact['ID'])."', $idempresa, $idtipofactura, $idcontrato, $idcliente, $nit, $nombrefac, $serie, '".trim($fact['DOCUMENTO'])."', '".parseFecha($fact['FECHA'])."', ";
                $query.= (int)date_format(date_create(parseFecha($fact['FECHA'])), 'n').", '".parseFecha($fact['FECHA'])."', 2, '$conceptomayor', ".round((float)$fact['IVA'], 2).", ".round((float)$fact['MONTO'], 2).", ";
                $query.= "0.00, $subtotal, ".round((float)$fact['ISR'], 2).", ".round((float)$fact['RETIVA'], 2).", '".$n2l->to_word(round((float)$fact['MONTO'], 2), 'GTQ')."', ".round((float)$fact['DESCUENTO'], 2).", NULL, ";
                $query.= "$firma, NULL, 1, ".round((float)$fact['TASA'],5).", 0, NULL, ".(trim($fact['STATUS']) != 'A' ? 0 : 1).", 0, ".(trim($fact['STATUS']) != 'A' ? "NULL" : ("'".parseFecha($fact['FECHA'])."'")).", $esinsertada, ";
                $query.= "$dirfac)";

                if($anulada){
                    $idfactura = (int)$db->getOneField("SELECT id FROM factura WHERE idempresa = $idempresa AND serie = $serie AND numero = '".trim($fact['DOCUMENTO'])."'");
                    if($idfactura > 0){
                        $query = "UPDATE factura SET anulada = 1, fechaanula = '".parseFecha($fact['FECHA'])."', firmaelectronicaalt = $firma WHERE id = $idfactura";
                        echo "ANULACIÓN de factura '$serie-".trim($fact['DOCUMENTO'])."'<br/>";
                    }
                }

                echo $query."<br/>";
                $db->doQuery($query);
                $lastId = (int)$db->getLastId();
                if($lastId <= 0){ echo $query."<br/>ERROR: La factura '".trim($fact['ID'])."' no fue insertada...<br/>"; }
            }else{
                echo "ERROR: No se encontró la empresa '".trim($fact['EMPRESA'])."'.<br/>";
            }
        }
    }
    dbase_close($facturas);
}

function getDetalleRecibos(){
    $detrecs = dbase_open("fox/vr/detarecs.DBF", 0);
    $cntDetRecs = dbase_numrecords($detrecs);
    echo "Total de Detalle de recibos: $cntDetRecs.<br/>";
    $rows = [];
    for($j = 1; $j <= $cntDetRecs; $j++){
        $rdet = dbase_get_record_with_names($detrecs, $j);
        if((int)$rdet['deleted'] === 0){
            $rows[] = $rdet;
        }
    }
    dbase_close($detrecs);
    return $rows;
}

function searchDataRecibos($db, $detrecs, $cntDetRecs, $idrecibo){
    $detalle = [];
    for($i = 0; $i < $cntDetRecs; $i++){
        if(trim($detrecs[$i]['HEADERID']) == $idrecibo){
            $query = "SELECT id, idempresa, idcontrato, idcliente FROM factura WHERE idfox = '".trim($detrecs[$i]['IDFAC'])."' LIMIT 1";
            $info = $db->getQuery($query);
            if(count($info) > 0){
                if((int)$info[0]->id > 0){
                    $detalle[] = [
                        'idfactura' => (int)$info[0]->id, 'monto' => round((float)$detrecs[$i]['VALOR'], 2), 'idempresa' => (int)$info[0]->idempresa, 'idcontrato' => (int)$info[0]->idcontrato, 'idcliente' => (int)$info[0]->idcliente,
                        'idfox' => trim($detrecs[$i]['ID']), 'headeridfox' => trim($detrecs[$i]['HEADERID'])
                    ];
                }
            }
        }
    }
    return $detalle;
}

function estaPagada($db, $idfactura, $idrecibo){
    //Poner como pagada la factura si su saldo es 0.00
    $query = "SELECT (a.total - IF(ISNULL(d.cobrado), 0.00, d.cobrado)) AS saldo FROM factura a ";
    $query.= "LEFT JOIN (SELECT a.idfactura, SUM(a.monto) AS cobrado FROM detcobroventa a INNER JOIN recibocli b ON b.id = a.idrecibocli WHERE b.anulado = 0 GROUP BY a.idfactura) d ON a.id = d.idfactura ";
    $query.= "WHERE a.id = $idfactura LIMIT 1";
    $haypendiente = (float)$db->getOneField($query) > 0.00;
    if(!$haypendiente){
        $query = "UPDATE factura SET pagada = 1, fechapago = (SELECT fecha FROM recibocli WHERE id = $idrecibo) WHERE id = $idfactura";
        echo $query."<br/>";
        $db->doQuery($query);
    }
}

function migraRecibos($db){
    $recibos = dbase_open("fox/vr/headrecs.DBF", 0);
    $cntRecs = dbase_numrecords($recibos);
    echo "Total de Recibos: $cntRecs.<br/>";
    $detrecs = getDetalleRecibos();
    $cntDetRecs = count($detrecs);
    for($i = 1; $i <= $cntRecs; $i++){
        $rec = dbase_get_record_with_names($recibos, $i);
        if((int)$rec['deleted'] === 0 && (int)date_format(date_create(parseFecha($rec['FECHA'])), 'Y') >= 2016 ){
            $det = searchDataRecibos($db, $detrecs, $cntDetRecs, trim($rec['ID']));
            $cntDetalle = count($det);
            if($cntDetalle > 0){
                $stDetRec = $det[0];
                $documento = preg_replace('/[^0-9]/', '', trim($rec['NODEPOSITO']));
                $query = "SELECT a.id FROM tranban a INNER JOIN banco b ON b.id = a.idbanco WHERE b.idempresa = ".$stDetRec['idempresa']." AND a.bancofox = '".trim($rec['BANCO'])."' AND numero = $documento LIMIT 1";
                //echo $query."<br/>";
                $idtranban = (trim($rec['BANCO']) != '' && (int)$documento > 0) ? (int)$db->getOneField($query) : 0;
                $query = "INSERT INTO recibocli(idempresa, fecha, fechacrea, idcliente, espropio, idtranban, idfox) VALUES(";
                $query.= $stDetRec['idempresa'].", '".parseFecha(trim($rec['FECHA']))."', '".parseFecha(trim($rec['FECHA']))."', ".$stDetRec['idcliente'].", 1, $idtranban, '".trim($rec['ID'])."'";
                $query.= ")";
                echo $query."<br/>";
                $db->doQuery($query);
                $lastId = $db->getLastId();
                if($lastId > 0){
                    for($j = 0; $j < $cntDetalle; $j++){
                        $query = "INSERT INTO detcobroventa(idfactura, idrecibocli, monto, idfox, headeridfox) VALUES(";
                        $query.= $det[$j]['idfactura'].", $lastId, ".$det[$j]['monto'].", '".$det[$j]['idfox']."', '".$det[$j]['headeridfox']."'";
                        $query.= ")";
                        echo $query."<br/>";
                        $db->doQuery($query);
                        estaPagada($db, $det[$j]['idfactura'], $lastId);
                    }
                }else{
                    echo "ERROR: No se pudo insertar el recibo con ID ".trim($rec['ID'])."<br/>";
                }
            }
        }
    }
    dbase_close($recibos);
}

function fixCompras($db, $ws, $idempresa){
    $dbf = dbase_open("$ws/compras.DBF", 0);
    $cntRegs = dbase_numrecords($dbf);
    $compras = $db->getQuery("SELECT id, proveedor, nit, serie, documento, fechaingreso, fechafactura FROM compra WHERE idreembolso = 0 AND idproveedor = 0 AND idempresa = $idempresa ORDER BY fechafactura");
    $cntCompras = count($compras);
    for($i = 0; $i < $cntCompras; $i++){
        $compra = $compras[$i];
        for($j = 1; $j <= $cntRegs; $j++){
            $row = dbase_get_record_with_names($dbf, $j);
            $row['ANIO'] = (int)date_format(date_create(parseFecha($row['CFECHA'])), 'Y');
            if(
                (int)$row['deleted'] === 0 && $row['ANIO'] >= 2016 && trim($row['TIPODOC']) == 'C' &&
                trim($row['SERIE']) == trim($compra->serie) && (int)$row['CNUM_DOC'] == (int)$compra->documento && parseFecha($row['CFECHA']) == $compra->fechafactura
            ){
                $idproveedor = (int)$db->getOneField("SELECT id FROM proveedor WHERE TRIM(nit) = '".trim($row['CNIT'])."' LIMIT 1");
                if($idproveedor > 0){
                    $query = "UPDATE compra SET idproveedor = $idproveedor WHERE id = $compra->id";
                    echo $query."<br/>Compra $compra->id actualizada con el proveedor $idproveedor.<br/>";
                    $db->doQuery($query);
                }else{
                    $query = "INSERT INTO proveedor(nit, nombre) VALUES('".trim($row['CNIT'])."', '".trim($row['CPROVEE'])."')";
                    echo $query."<br/>Se inserto el proveedor ".trim($row['CNIT'])."<br/>";
                    $db->doQuery($query);
                    $lastId = $db->getLastId();
                    if($lastId > 0){
                        $query = "UPDATE compra SET idproveedor = $lastId WHERE id = $compra->id";
                        echo $query."<br/>Compra $compra->id actualizada con el proveedor $idproveedor.<br/>";
                        $db->doQuery($query);
                    }else{
                        echo "ERROR: No se pudo insertar el proveedor ".trim($row['CNIT']).", ".trim($row['CPROVEE'])."<br/>";
                    }
                }
            }
        }
    }
    dbase_close($dbf);
    echo "Compras de empresa No. $idempresa arregladas...<br/>";
}

function migraDetalleContableCompras($db, $ws, $idempresa, $detallecontable){
    $cntDetCont = count($detallecontable);
    for($i = 0; $i < $cntDetCont; $i++ ){
        $dc = $detallecontable[$i];
        //if((int)$dc['deleted'] == 0 && trim($dc['TIPO']) == 'C' && trim($dc['BANCO']) == '' && trim($dc['TIPO_B']) == '' && trim($dc['DOCU_B']) == ''){
        //var_dump($dc);
        //echo "<br/><--------------------------------------------------------------------------------------------------><br/>";
        if((int)$dc['deleted'] == 0 && trim($dc['TIPO']) == 'C' && trim($dc['BANCO']) == '' && (int)date_format(date_create(parseFecha($dc['FECHA'])), 'Y') >= 2016){
            $query = "SELECT a.id FROM compra a INNER JOIN proveedor b ON b.id = a.idproveedor WHERE a.idreembolso = 0 AND a.idempresa = $idempresa AND TRIM(b.nit) = '".trim($dc['NIT'])."' AND a.documento = ".((int)$dc['DOCUMENTO']);
            $idcompra = (int)$db->getOneField($query);
            if($idcompra > 0){
                $idcuentac = (int)$db->getOneField("SELECT id FROM cuentac WHERE idempresa = $idempresa AND TRIM(codigo) = '".trim($dc['CUENTA'])."'");
                if($idcuentac > 0){
                    $query = "INSERT INTO detallecontable(origen, idorigen, idcuenta, debe, haber, conceptomayor) VALUES(";
                    $query.= "2, $idcompra, $idcuentac, ".round((float)$dc['DEBE'], 2).", ".round((float)$dc['HABER'], 2).", '".utf8_encode(trim($dc['REFERENCIA']))."'";
                    $query.= ")";
                    echo $query."<br/>";
                    $db->doQuery($query);
                }else{
                    echo "ERROR: La cuenta contable ".trim($dc['CUENTA'])."no ha sido insertada o migrada...<br/>";
                }
            }else{
                echo "ERROR: La compra ".$dc['DOCUMENTO']." con NIT ".trim($dc['NIT'])." no ha sido insertada o migrada (puede que no tenga encabezado o el nit es incorrecto)...<br/>";
            }
        }
    }
}

$app->get('/migrar', function() use($app){
    $db = new dbcpm();
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body><small>".$db->getOneField("SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s')")."<br/>";
    echo "Mamoria inicial: ".round(((memory_get_usage(true) / 1024) / 1024), 2)."MB<br/>";
    $query = "SELECT id, LPAD(id, 2, '0') AS folder, nomempresa, abreviatura ";
    $query.= "FROM empresa ";
    //$query.= "WHERE id = 8 ";
    $query.= "ORDER BY id";
    $empresas = $db->getQuery($query);
    $cntEmp = count($empresas);
    $folder = 'fox/';
    /*
    for($i = 0; $i < $cntEmp; $i++){
        $empresa = $empresas[$i];
        $workSpace = $folder.$empresa->folder;
        if(file_exists($workSpace)){
            echo "WORKSPACE = ".$workSpace."<br/>";
            //migraCuentasContables($db, $workSpace, (int)$empresa->id);
            //migraBancos($db, $workSpace, (int)$empresa->id);
            //migraProveedores($db, $workSpace, (int)$empresa->id);
            //$detalleContable = getDetalleContable($workSpace);
            //echo "Cantidad registros de detalle contable = ".count($detalleContable)."<br/>";
            //migraTransaccionesBancarias($db, $workSpace, (int)$empresa->id, $detalleContable);
            //migraCompras($db, $workSpace, (int)$empresa->id, $detalleContable);
            //migraDirectas($db, $workSpace, (int)$empresa->id, '06', '07');
            //migraDirectas($db, $workSpace, (int)$empresa->id, '02', '03');
            //fixCompras($db, $workSpace, (int)$empresa->id);
            //migraDetalleContableCompras($db, $workSpace, (int)$empresa->id, $detalleContable);
        }
    }
    */
    //migraVentasRecibosContable($db);
    //migraVentas($db);
    //migraRecibos($db);
    //checkCompraExiste($folder, $db);
    echo "Mamoria final: ".round(((memory_get_usage(true) / 1024) / 1024), 2)."MB<br/>";
    echo $db->getOneField("SELECT DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i:%s')")."</small></body></html>";
});

$app->run();