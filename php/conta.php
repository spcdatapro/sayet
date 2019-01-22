<?php

class contabilidad{

    private $_fdel = '';
    private $_fal = '';
    private $_idempresa = '';
    private $_vercierre = '';
    private $_datosEnCrudo = '';
    private $_decimales = 2;
    private $_formatoFechaConta = '%d/%m/%Y';
    private $_formatoFechaHoraConta = '%d/%m/%Y %H:%i:%s';

    function __construct($fdel = null, $fal = null, $idempresa = '1', $vercierre = 1, $decimales = 2, $formatoFecha = '%d/%m/%Y', $formatoFechaHora = '%d/%m/%Y %H:%i:%s'){

        if(!$fdel){
            $fdel = date('Y').'-'.date('m').'-01';
        }

        if(!$fal){
            $fal = date('Y').'-'.date('m').'-'.date('t', strtotime($fdel));
        }

        $this->_fdel = $fdel;
        $this->_fal = $fal;
        $this->_idempresa = $idempresa;
        $this->_vercierre = $vercierre;
        $this->_decimales = $decimales;
        $this->_formatoFechaConta = $formatoFecha;
        $this->_formatoFechaHoraConta = $formatoFechaHora;
        $this->datosEnCrudo();
    }

    private function detalleContable($origen, $idorigen = 0){
        $query = "SELECT z.origen, z.idorigen, y.id AS idcuentac, y.codigo, y.nombrecta, z.debe, z.haber ";
        $query.= "FROM detallecontable z INNER JOIN cuentac y ON y.id = z.idcuenta ";
        $query.= "WHERE z.origen = ".($origen != 5 ? ($origen." AND z.activada = 1") : 2)." ";
        $query.= $idorigen > 0 ? "AND z.idorigen = $idorigen " : '';
        $query.= $origen != 1 ? "AND z.anulado = 0 " : "";
        return $query;
    }

    private function datosEnCrudo(){
        //#Transacciones bancarias -> origen = 1
        $query = "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(1, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
        $query.= "CONCAT(d.descripcion, ' ', b.numero, ' ', c.nombre) AS referencia, b.concepto, b.id, 1 AS origen, ";
        $query.= "x.idcuentac, x.codigo, x.nombrecta, IFNULL(x.debe, 0.00) AS debe, IFNULL(x.haber, 0.00) AS haber ";
        $query.= "FROM tranban b INNER JOIN banco c ON c.id = b.idbanco INNER JOIN tipomovtranban d ON d.abreviatura = b.tipotrans ";
        $query.= "LEFT JOIN(".$this->detalleContable(1).") x ON b.id = x.idorigen ";
        $query.= "WHERE b.fecha >= '$this->_fdel' AND b.fecha <= '$this->_fal' AND c.idempresa = $this->_idempresa ";

        //#Compras -> origen = 2
        $query.= "UNION ALL ";
        $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(2, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechaingreso AS fecha, ";
        $query.= "CONCAT('Compra', ' ', b.serie, '-', b.documento, ' ') AS referencia, b.conceptomayor AS concepto, b.id, 2 AS origen, ";
        $query.= "x.idcuentac, x.codigo, x.nombrecta, IFNULL(x.debe, 0.00) AS debe, IFNULL(x.haber, 0.00) AS haber ";
        $query.= "FROM compra b ";
        $query.= "LEFT JOIN(".$this->detalleContable(2).") x ON b.id = x.idorigen ";
        $query.= "WHERE b.idreembolso = 0 AND b.fechaingreso >= '$this->_fdel' AND b.fechaingreso <= '$this->_fal' AND b.idempresa = $this->_idempresa ";

        //#Ventas -> origen = 3
        $query.= "UNION ALL ";
        $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(3, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
        $query.= "CONCAT('Venta', ' ', b.serie, '-', b.numero) AS referencia, b.conceptomayor AS concepto, b.id, 3 AS origen, ";
        $query.= "x.idcuentac, x.codigo, x.nombrecta, IFNULL(x.debe, 0.00) AS debe, IFNULL(x.haber, 0.00) AS haber ";
        $query.= "FROM factura b ";
        $query.= "LEFT JOIN(".$this->detalleContable(3).") x ON b.id = x.idorigen ";
        $query.= "WHERE b.fecha >= '$this->_fdel' AND b.fecha <= '$this->_fal' AND b.anulada = 0 AND b.idempresa = $this->_idempresa ";

        //#Directas -> origen = 4
        $query.= "UNION ALL ";
        $query.= "SELECT CONCAT('P', YEAR(b.fecha), LPAD(MONTH(b.fecha), 2, '0'), LPAD(DAY(b.fecha), 2, '0'), LPAD(4, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fecha, ";
        $query.= "CONCAT('Directa No.', LPAD(b.id, 5, '0')) AS referencia, '' AS concepto, b.id, 4 AS origen, ";
        $query.= "x.idcuentac, x.codigo, x.nombrecta, IFNULL(x.debe, 0.00) AS debe, IFNULL(x.haber, 0.00) AS haber ";
        $query.= "FROM directa b ";
        $query.= "LEFT JOIN(".$this->detalleContable(4).") x ON b.id = x.idorigen ";
        $query.= "WHERE b.fecha >= '$this->_fdel' AND b.fecha <= '$this->_fal' AND b.idempresa = $this->_idempresa ";
        $query.= (int)$this->_vercierre === 0 ? "AND b.tipocierre NOT IN(1, 2, 3, 4) " : '';

        //#Reembolsos -> origen = 5
        $query.= "UNION ALL ";
        $query.= "SELECT CONCAT('P', YEAR(b.fechaingreso), LPAD(MONTH(b.fechaingreso), 2, '0'), LPAD(DAY(b.fechaingreso), 2, '0'), LPAD(5, 2, '0'), LPAD(b.id, 7, '0')) AS poliza, b.fechaingreso AS fecha, ";
        $query.= "CONCAT('Compra', ' ', b.serie, '-', b.documento, ' ') AS referencia, b.conceptomayor AS concepto, b.id, 5 AS origen, ";
        $query.= "x.idcuentac, x.codigo, x.nombrecta, IFNULL(x.debe, 0.00) AS debe, IFNULL(x.haber, 0.00) AS haber ";
        $query.= "FROM compra b ";
        $query.= "LEFT JOIN(".$this->detalleContable(5).") x ON b.id = x.idorigen ";
        $query.= "WHERE b.idreembolso > 0 AND b.fechaingreso >= '$this->_fdel' AND b.fechaingreso <= '$this->_fal' AND b.idempresa = $this->_idempresa ";

        //$query.= "ORDER BY fecha, poliza, debe DESC, nombrecta";

        $this->_datosEnCrudo = $query;
    }

    public function getCatalogoCuentas(){
        $query = "SELECT id AS idcuentac, idempresa, codigo, nombrecta, tipocuenta, 0.00 AS anterior, 0.00 AS debe, 0.00 AS haber, 0.00 AS actual ";
        $query.= "FROM cuentac WHERE idempresa = $this->_idempresa ";
        $query.= "ORDER BY codigo";

        return $query;
    }

    public function getDatosEnCrudo(){
        return $this->_datosEnCrudo;
    }

    public function getPolizas(){
        $query = "SELECT w.poliza, w.fecha, DATE_FORMAT(w.fecha, '$this->_formatoFechaConta') AS fechastr, w.referencia, w.concepto, w.id, w.origen, ";
        $query.= "SUM(w.debe) AS debe, FORMAT(SUM(w.debe), $this->_decimales) AS debestr, SUM(w.haber) AS haber, FORMAT(SUM(w.haber), $this->_decimales) AS haberstr ";
        $query.= "FROM ($this->_datosEnCrudo) w ";
        $query.= "GROUP BY w.id ";
        $query.= "ORDER BY w.fecha, w.poliza";
        return $query;
    }

    public function getDetallePoliza($origen, $idorigen){
        $query = "SELECT w.idorigen AS id, w.origen, w.codigo, w.nombrecta, w.debe, FORMAT(w.debe, $this->_decimales) AS debestr, w.haber, FORMAT(w.haber, $this->_decimales) AS haberstr ";
        $query.= "FROM (".$this->detalleContable($origen, (int)$idorigen).") w ";
        $query.= "WHERE w.origen = $origen AND w.idorigen = $idorigen ";
        $query.= "ORDER BY w.debe DESC, w.nombrecta";
        return $query;
    }

    public function getSumasDebeHaber(){
        $query = "SELECT SUM(w.debe) AS debe, FORMAT(SUM(w.debe), $this->_decimales) AS debestr, SUM(w.haber) AS haber, FORMAT(SUM(w.haber), $this->_decimales) AS haberstr ";
        $query.= "FROM ($this->_datosEnCrudo) w ";
        return $query;
    }
}