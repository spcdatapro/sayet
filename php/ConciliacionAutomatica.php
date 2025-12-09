<?php

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

require_once 'libraries/loader.php';
require_once 'db.php';

define('NET_SSH2_LOGGING', SSH2::LOG_COMPLEX);

class SFTPConnInfo
{
    public $host;
    public $port;
    public $username;
    public $password;
    public $mt940_folder = '/';
    public $error = null;
    public $conn = null;

    public function __construct($host, $port, $username, $password, $mt940_folder = '/')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->mt940_folder = $mt940_folder;
    }

    public function connect()
    {
        $conected = false;
        try {
            $this->conn = new SFTP($this->host, $this->port);
            $this->conn->login($this->username, $this->password);
            $this->conn->sendKEXINITLast();
            $this->conn->setTimeout(0);
            $this->conn->setKeepAlive(5);
            $this->conn->enableDatePreservation();
            $this->conn->chdir($this->mt940_folder);
            $conected = true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }
        return $conected;
    }
}

class ConciliacionAutomatica
{
    private $source_conn;
    private $dest_conn;
    private $local_folder = __DIR__ . '\\mt940_files\\';
    private $bac_pk_folder = __DIR__ . '\\sftp_pk\\';

    public function __construct(SFTPConnInfo $source, SFTPConnInfo $dest)
    {
        $this->source_conn = $source;
        $this->dest_conn = $dest;
    }

    public function get_local_folder()
    {
        return $this->local_folder;
    }

    public function get_bac_pk_folder()
    {
        return $this->bac_pk_folder;
    }

    // private function filter_mt940($copiar = true)
    // {
    //     $archivos = $copiar ? $this->source_conn->conn->nlist('.') : $this->dest_conn->conn->nlist('.');
    //     $mt940 = [];
    //     $valid_ext = ['.pgp', '.gpg', '.txt', '.p12'];
    //     foreach ($archivos as $archivo) {
    //         $ext = substr($archivo, -4);
    //         if (in_array($ext, $valid_ext)) {
    //             $mt940[] = $archivo;
    //         }
    //     }
    //     return $mt940;
    // }

    private function filter_mt940($copiar = true, $ultFecha = null, $db = null)
    {
        date_default_timezone_set('America/Guatemala');
        // Simulación de archivos recibidos
        $archivos = $copiar ? $this->source_conn->conn->nlist('.') : $this->dest_conn->conn->nlist('.');

        $mt940 = [];
        $valid_ext = ['.pgp', '.gpg', '.txt', '.p12'];
        $fechas = [];

        foreach ($archivos as $archivo) {
            $ext = substr($archivo, -4);
            if (in_array($ext, $valid_ext)) {
                // Extraer fecha en formato ddmmyyyy después de la primera letra
                if (preg_match('/^[A-Z](\d{2})(\d{2})(\d{4})/', $archivo, $match)) {
                    $fecha = DateTime::createFromFormat('dmY', $match[1] . $match[2] . $match[3]);
                    if ($fecha) {
                        $key = $fecha->format('Y-m-d');
                        $fechas[$key][] = $archivo;
                        $mt940[] = ['archivo' => $archivo, 'fecha' => $fecha];
                    }
                }
            }
        }

        if ($copiar) {
            // Ordenar por fecha ascendente
            usort($mt940, function ($a, $b) {
                return $a['fecha'] <=> $b['fecha'];
            });

            // Validar que desde la última fecha recibida haya al menos 2 archivos por día hasta ayer
            $errores = [];
            $fecha_error = null;
            if (!empty($fechas)) {
                $inicio = $ultFecha->modify('+1 day');
                $ayer = new DateTime('yesterday');
                $intervalo = new DateInterval('P1D');
                $periodo = new DatePeriod($inicio, $intervalo, $ayer->modify('+1 day'));

                foreach ($periodo as $fecha) {
                    $key = $fecha->format('Y-m-d');
                    $cantidad = isset($fechas[$key]) ? count($fechas[$key]) : 0;
                    if ($cantidad < 2) {
                        $existe = $db->getOneField("SELECT id FROM errores_ecuenta WHERE fecha = '$key'") > 0;
                        if (!$existe) {
                            $errores[] = "Faltan archivos para el día {$key} (solo $cantidad encontrado)";
                            $fecha_error = $key;
                        }
                    }
                }
            }
        } else {
            $errores = [];
            $fecha_error = '0000-00-00';
        }

        // if (!empty($errores)) {
        //     foreach ($errores as $error) {
        //         echo $error . PHP_EOL;
        //     }
        // }

        // Retorna solo los nombres de archivo
        return (object)['archivos' => array_column($mt940, 'archivo'), 'errores' => $errores, 'fechas' => $fecha_error];
    }

    public function get_mt940()
    {
        date_default_timezone_set('America/Guatemala');
        $db = new dbcpm();
        $datos = ['exito' => false];
        // agregado para validar que vegnan todos los archivo
        $ultFecha = $db->getOneField("SELECT SUBSTRING(nombre, 2, 8) AS fecha FROM estado_cuenta ORDER BY estado_cuenta DESC LIMIT 1");
        $ultFecha = DateTime::createFromFormat('dmY', $ultFecha);
        // fin de agregado
        if ($this->source_conn->connect()) {
            // $archivosmt940 = $this->filter_mt940(true); Asi estaba antes de cambios para validacion de archivos
            $archivosmt940 = $this->filter_mt940(true, $ultFecha, $db);
            // agregado para validar que vengan todos los archivos
            $datos['errores'] = $archivosmt940->errores;
            $datos['fecha'] = $archivosmt940->fechas;
            // fin de agregado
            // todos donde dice archivosmt940->arvhivos solo estaba archivosmt940
            if (count($archivosmt940->archivos) > 0) {
                if ($this->dest_conn->connect()) {
                    $errores = [];
                    foreach ($archivosmt940->archivos as $archivo) {
                        $query = "SELECT * FROM estado_cuenta WHERE TRIM(nombre = '{$archivo}')";
                        $existe = $db->getOneField($query) > 0;
                        if (!$existe) {
                            $localFileName = $this->local_folder . $archivo;
                            try {
                                $descargado = $this->source_conn->conn->get($archivo, $localFileName);
                                if ($descargado) {
                                    $subido = $this->dest_conn->conn->put($archivo, $localFileName, SFTP::SOURCE_LOCAL_FILE);
                                    if (!$subido) {
                                        $errores[] = "No se pudo subir el archivo '{$archivo}' al destino...";
                                    } else {
                                        unlink($localFileName);
                                    }
                                } else {
                                    $errores[] = "No se pudo descargar el archivo '{$archivo}' del origen...";
                                }
                            } catch (Exception $e) {
                                $errores[] = "No se pudo descargar el archivo '{$archivo}' del origen: {$e->getMessage()}";
                            }
                            $this->source_conn->conn->disconnect();
                            $this->source_conn->connect();
                        }
                    }
                    if (count($errores) === 0) {
                        $datos['mensaje'] = 'Archivos MT940 subidos al destino con éxito...';
                        $datos['exito'] = true;
                    } else {
                        $datos['mensaje'] = implode('. ', $errores);
                    }
                } else {
                    $datos['mensaje'] = $this->dest_conn_conn->mensaje;
                }
            } else {
                $datos['mensaje'] = 'No hay archivos disponibles...';
            }
            $datos['archivos_mt940'] = $archivosmt940->archivos;
        } else {
            $datos['mensaje'] = $this->source_conn->mensaje;
        }
        return (object)$datos;
    }

    // Métodos relacionados con la desencriptación y lectura de archivos MT940

    private function get_decrypted_file($fullFilePath)
    {
        $gpgFilePath = $fullFilePath;
        $decryptedData = null;

        $fingerprint = '248A08BA6FB9EC22CF37FC5F8F8A3BA6B9753796';
        $passphrase = 'aq6Jh@q3kQ';

        $ext = 'sh';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {            
            $ext = 'bat';
        }

        $decryptCommand  = "{$this->bac_pk_folder}gpgdecrypt.{$ext}" . ' ';
        $decryptCommand .= escapeshellarg($fingerprint) . ' ';
        $decryptCommand .= escapeshellarg($passphrase) . ' ';
        $decryptCommand .= escapeshellarg($gpgFilePath) . ' ';
        $decryptCommand .= escapeshellarg($gpgFilePath) . ' ';


        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );

        $process = proc_open($decryptCommand, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            // proc_close($process);
            $return_value = proc_close($process);

            if ($return_value === 0) {
                $decryptedFileName = $gpgFilePath . '.txt';
                if (file_exists($decryptedFileName)) {
                    $decryptedData = file_get_contents($decryptedFileName);
                    unlink($decryptedFileName);
                } else {
                    throw new Exception("Decrypted file not found: " . $decryptedFileName);
                }
            } else {
                throw new Exception("Decryption failed: " . $error);
            }
        } else {
            throw new Exception("Failed to open process for decryption command.");
        }

        return $decryptedData;
    }

    private function save_to_db($nombreArchivo, $dataArchivo)
    {
        $db = new dbcpm();
        $grabado = false;
        foreach ($dataArchivo as $data) {
            $nombreArchivo = trim($nombreArchivo);
            $data->number = trim($data->number);
            $query = "SELECT estado_cuenta FROM estado_cuenta WHERE TRIM(nombre) = '{$nombreArchivo}' AND TRIM(numero) = '{$data->number}'";
            $existe = (int)$db->getOneField($query) > 0;
            if (!$existe) {
                $query = 'INSERT INTO estado_cuenta(nombre, cuenta, numero, moneda, saldo_inicial, saldo_final) VALUES(';
                $query .= "'{$nombreArchivo}',  '{$data->account}', '{$data->number}', '{$data->currency}', {$data->startPrice}, {$data->endPrice}";
                $query .= ')';
                $db->doQuery($query);
                $lastid = (int)$db->getLastId();
                if ($lastid > 0) {
                    $cntTransacciones = count($data->transactions);
                    // foreach ($data->transactions as $transaccion) {
                    for ($i = 0; $i < $cntTransacciones; $i++) {
                        $transaccion = $data->transactions[$i];
                        $insDet = 'INSERT INTO d_estado_cuenta(estado_cuenta, es_cancelacion, tipo_transaccion, descripcion, fecha, monto, referencia, codigo_transaccion, fecha_contable) VALUES(';
                        $insDet .= $lastid . ", " . ($transaccion->cancellation ? 1 : 0) . ", '" . trim($transaccion->debitcredit) . "', '" . trim($transaccion->description) . "', '" . date('Y-m-d', $transaccion->entryTimestamp) . "', ";
                        $insDet .=  $transaccion->price . ", '" . trim($transaccion->referenceAccountOwner) . "', '" . trim($transaccion->transactionCode) . "', '" . date('Y-m-d', $transaccion->valueTimestamp) . "'";
                        $insDet .= ')';
                        $db->doQuery($insDet);
                    }
                }
                $grabado = true;
            } else {
                $grabado = true;
                // $this->existia = true;
            }
        }
        return $grabado;
    }

    public function read_mt940()
    {
        set_time_limit(0);
        $datos = ['exito' => false];
        if ($this->dest_conn->connect()) {
            $archivos = $this->filter_mt940(false);
            // antes solo archivos
            if (count($archivos->archivos) > 0) {
                $parser = new \Kingsquare\Parser\Banking\Mt940();
                $errores = [];
                foreach ($archivos->archivos as $archivo) {
                    $localFileName = $this->local_folder . $archivo;
                    $descargado = null;
                    try {
                        $descargado = $this->dest_conn->conn->get($archivo, $localFileName);
                    } catch (Throwable $e) {
                        $datos['sftp_errors'] = $this->dest_conn->conn->getErrors();
                        $datos['sftp_sftperrors'] = $this->dest_conn->conn->getSFTPErrors();
                        $datos['throwable'] = $e->getMessage();
                        $datos['sftp_logs'] = $this->dest_conn->conn->getLog();
                    }
                    if ($descargado) {
                        try {
                            $parsedStatements = $parser->parse($this->get_decrypted_file($localFileName));                            
                            $datos['json'] = [];
                            foreach ($parsedStatements as $statement) {
                                $datos['json'][] = $statement->jsonSerialize();
                            }
                            // Aquí grabo a BD
                            $grabado = $this->save_to_db($archivo, json_decode(json_encode($datos['json'])));
                            if ($grabado) {
                                $movido = $this->dest_conn->conn->put('./procesado/' . $archivo, $localFileName, SFTP::SOURCE_LOCAL_FILE);
                                if ($movido) {
                                    $this->dest_conn->conn->delete($this->dest_conn->mt940_folder . '/' . $archivo);
                                }
                            }
                        } catch (Exception $e) {
                            $errores[] = $e->getMessage();
                        }
                        unlink($localFileName);
                    } else {
                        $errores[] = "No se pudo descargar el archivo '{$archivo}' para leerlo.";
                    }
                }

                if (count($errores) === 0) {
                    // if (!$this->existia) {
                        unset($datos['json']);
                        $datos['exito'] = true;
                        $datos['mensaje'] = 'Lista de archivos MT940.';
                    // } else {
                        // unset($datos['json']);
                        // $datos['exito'] = true;
                        // $datos['mensaje'] = 'No existen archivos nuevos, favor intentar mas tarde.';
                        // $archivos = [];
                    // }
                } else {
                    $datos['mensaje'] = implode('. ', $errores);
                }
                $datos['archivos_mt940'] = $archivos->archivos;
            } else {
                $datos['mensaje'] = 'No hay archivos MT940 disponibles.';
            }
        } else {
            $datos['mensaje'] = 'No se pudo conectar al servidor de archivos MT940. ' . $this->dest_conn->mensaje;
        }
        return $datos;
    }
}
