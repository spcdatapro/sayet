<?php

/**
* Clase para  realizar búsquedas
*/
class General extends Principal
{
	
	function __construct()
	{
		parent::__construct();
	}

	public function buscar_empleado($args=[])
	{
		$where = [];

		if (elemento($args, 'termino')) {
			$where["nombre[~]"] = $args['termino'];
		}

		if (elemento($args, 'proyecto')) {
			$where['idproyecto'] = $args['proyecto'];
		}

		if (elemento($args, 'actual')) {
			$where['idempresaactual'] = $args['actual'];
		}

		if (elemento($args, 'debito')) {
			$where['idempresadebito'] = $args['debito'];
		}

		if (elemento($args, 'empleado')) {
			$where['id'] = $args['empleado'];
		}

		if (elemento($args, 'estatus')) {
			if (elemento($args, 'fdel', false) || elemento($args, 'fal', false)) { 
				if (elemento($args, 'fdel')) {
					if ($args['estatus'] == 1) { # Fecha ingreso
						$where['ingreso[>=]'] = $args['fdel'];
					} elseif ($args['estatus'] == 2) { # Fecha baja
						$where['baja[>=]'] = $args['fdel'];
					}
				}

				if (elemento($args, 'fal')) {
					if ($args['estatus'] == 1) { # Fecha ingreso
						$where['ingreso[<=]'] = $args['fal'];
					} elseif ($args['estatus'] == 2) { # Fecha baja
						$where['baja[<=]'] = $args['fal'];
					}
				}
			} else {
				if ($args['estatus'] == 1) {
					$where['activo'] = 1;
				} else if ($args['estatus'] == 2) {
					$where['activo'] = 0;
				}
			}
		}

		if (count($where) > 1) {
			$condicion = ['AND' => $where];
		} else {
			$condicion = $where;
		}

		if (!elemento($args, 'sin_limite')) {
			$condicion["LIMIT"] = [elemento($args, 'inicio', 0), get_limite()];
		}

		if (isset($args['ordenar_proyecto'])) {
			$condicion["ORDER"] = "idproyecto ASC, nombre ASC";
		} else {
			$condicion["ORDER"] = "nombre ASC";
		}

		return $this->db->select(
			'plnempleado', 
			'*', 
			$condicion
		);
	}

	public function buscar_puesto(Array $args)
	{
		$condicion = [];
		
		if (elemento($args, 'termino')) {
			$condicion["descripcion[~]"] = $args['termino'];
		}

		if (!elemento($args, 'sin_limite')) {
			$condicion["LIMIT"] = [elemento($args, 'inicio', 0), get_limite()];
		}

		return $this->db->select(
			'plnpuesto', 
			'*', 
			$condicion
		);
	}

	public function buscar_periodo(Array $args)
	{
		$condicion = [];
		$where = [];

		if (elemento($args, 'fin')) {
			$where["fin"] = $args['fin'];
		}

		if (isset($args['cerrado'])) {
			$where["cerrado"] = $args['cerrado'];
		}

		if (!empty($where)) {
			$condicion['AND'] = $where;
		}

		$condicion["ORDER"] = "inicio DESC";

		if (!elemento($args, 'sin_limite')) {
			$condicion["LIMIT"] = [elemento($args, 'inicio', 0), get_limite()];
		}


		return $this->db->select(
			'plnperiodo', 
			'*', 
			$condicion
		);
	}

	public function get_archivotipo($args = [])
	{
		return $this->db->select(
			'plnarchivotipo', 
			'*'
		);
	}

	public function get_empresa($args = [])
	{
		$where = [];

		if (elemento($args, 'id')) {
			$where['id'] = $args['id'];
		}

		if (isset($args['uno'])) {
			$where['LIMIT'] = 1;
		}

		$tmp = $this->db->select(
			'empresa', 
			'*',
			$where
		);

		if (isset($args['uno'])) {
			return $tmp[0];
		} else {
			return $tmp;
		}
		
	}

	/**
	 * devuelve empresa para planilla
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_plnempresa($args = [])
	{
		$where = [];

		if (elemento($args, 'id')) {
			$where['id'] = $args['id'];
		}

		if (isset($args['uno'])) {
			$where['LIMIT'] = 1;
		}

		$tmp = $this->db->select(
			'plnempresa', 
			'*',
			$where
		);

		if (isset($args['uno'])) {
			return $tmp[0];
		} else {
			return $tmp;
		}
		
	}

	/* 
	Verifica si un empleado activo tiene registro en la tabla pln_proempleado 
	para el año que se quiere trabajar 
	*/
	public function verificar_proempleado($args = [])
	{
		if (elemento($args, 'anio')) {
			$empleados = $this->db->query("
				select id 
				from plnempleado 
				where activo = 1")
				->fetchAll();

			foreach ($empleados as $row) {
				$tmp = $this->db->count('plnprosueldo', [
					'AND' => [
						'idplnempleado' => $row['id'], 
						'anio' => $args['anio']
					]
				]);

				if ($tmp == 0) {
					$lid = $this->db->insert(
						'plnprosueldo', 
						['idplnempleado' => $row['id'], 'anio' => $args['anio']]
					);
				}
			}
		}
	}

	public function get_prosueldo($args = [])
	{
		if (elemento($args, 'anio')) {
			return $this->db->query("
				select a.*, b.nombre, b.apellidos 
				from plnprosueldo a 
				join plnempleado b on b.id = a.idplnempleado 
				where a.anio = " . $args['anio'])
				->fetchAll();
		}

		return [];
	}

	public function get_campo_impresion($campo, $tipo)
	{
		return (object)$this->db->get(
			"fimpresion", 
			'*', 
			[
				'AND' => [
					'campo' => $campo, 
					'tipo'  => $tipo
				]
			]
		);
	}

	public function get_impresion($tipo)
	{
		return $this->db->select(
			"fimpresion", 
			["*"], 
			[
				"tipo" => $tipo,
				"ORDER" => ["psy ASC", "psx ASC"]
			]
		);
	}

	public function buscar_prestamo($args = [])
	{
		$condicion = [];
		$where     = [];

		if (elemento($args, 'termino')) {
			$where['OR'] = [
				"plnprestamo.concepto[~]" => $args['termino'], 
				"b.nombre[~]" => $args['termino']
			];
		}

		if (elemento($args, 'fdel')) {
			$where['plnprestamo.iniciopago[>=]'] = $args['fdel'];
		}

		if (elemento($args, 'fal')) {
			$where['plnprestamo.iniciopago[<=]'] = $args['fal'];
		}

		if (elemento($args, 'empresa')) {
			$where['b.idempresadebito[=]'] = $args['empresa'];
		}

		if (elemento($args, 'empleado')) {
			$where['plnprestamo.idplnempleado[=]'] = $args['empleado'];
		}

		if (isset($args['finalizado'])) {
			if ($args['finalizado'] == 0) {
				if (elemento($args, 'fal')) {
					$where['OR'] = [
						'finalizado[=]' => 0,
						'AND' => [
							'liquidacion[<>]' => array(formatoFecha($args['fal'], 5), $args['fal'])
						]
					];
				} else {
					$where['finalizado[=]'] = $args['finalizado'];
				}
			} else {
				$where['finalizado[=]'] = $args['finalizado'];
			}
		}

		if (!empty($where)) {
			$condicion['AND'] = $where;
		}

		if (isset($args['orden'])) {
			switch ($args['orden']) {
				case 'empresa':
					$condicion["ORDER"] = ["c.ordenreppres ASC", "b.nombre ASC"];
					break;
				default:
					$condicion["ORDER"] = "b.nombre ASC";
					break;
			}
		} else {
			$condicion["ORDER"] = "plnprestamo.fecha DESC";
		}

		if (isset($args['sinlimite'])) {
			# Sin limite...
		} else {
			$condicion["LIMIT"] = [elemento($args, 'inicio', 0), get_limite()];
		}
		
		return $this->db->select("plnprestamo", [
				'[><]plnempleado(b)' => ['plnprestamo.idplnempleado' => 'id'],
				'[>]plnempresa(c)' => ['b.idempresaactual' => 'id']
			], 
			[
				"plnprestamo.id",
				"plnprestamo.idplnempleado",
				"plnprestamo.fecha",
				"plnprestamo.monto",
				"plnprestamo.cuotamensual",
				"plnprestamo.iniciopago",
				"plnprestamo.liquidacion",
				"plnprestamo.concepto",
				"plnprestamo.finalizado",
				"plnprestamo.saldo", 
				"b.nombre", 
				"b.apellidos",
				"b.idempresaactual",
				"plnprestamo.anulado"
			],
			$condicion
		);
	}

	public function getDatosVacas($args=[])
    {
		$condiciones = "";
		
		if (elemento($args, "id")) {
    		$condiciones .= " AND a.id = " . $args["id"];
    	} else {
			$condiciones .= " AND b.anio = " . $args['anio'];
		}

    	if (elemento($args, "idplnempleado")) {
    		$condiciones .= " AND a.idplnempleado = " . $args["idplnempleado"];
    	}

    	if (elemento($args, "empresa")) {
    		$condiciones .= " AND c.idempresaactual = " . $args["empresa"];
		}
		
		if (elemento($args, "empresa_debito")) {
    		$condiciones .= " AND c.idempresadebito = " . $args["empresa"];
    	}

    	if (elemento($args, "actual")) {
    		$condiciones .= " AND c.idempresaactual = " . $args["actual"];
    	}

    	if (isset($args["activo"])) {
    		$condiciones .= " AND c.activo = " . $args["activo"];
		}
		
		$condiciones .= " ORDER BY d.ordenreppres, c.nombre ";

    	if (isset($args["uno"])) {
    		$condiciones .= " LIMIT 1";
    	}

        $sql = <<<EOT
            SELECT 
                a.id,
                a.vacasusados,
                a.vacasdias,
                a.vacasgozar,
                a.vacasultimas,
				a.vacasingreso,
				DATE_FORMAT(a.vacasingreso, '%d/%m/%Y') as vacasingresof,
                a.vacastotal,
                a.vacasdescuento,
                a.vacasliquido,
                a.idplnempleado,
                concat(c.nombre, ' ', ifnull(c.apellidos,'')) as nombre,
				c.idempresaactual,
				c.sueldo,
                CONCAT(b.anio, '-01-01') AS inicio,
                CONCAT(b.anio, '-12-31') AS fin,
                DATE_ADD(a.vacasgozar, INTERVAL 20 DAY) AS fingoce,
                DATE_ADD(a.vacasgozar, INTERVAL 21 DAY) AS presentar,
                d.nombre as empresaactual
            FROM
                plnextradetalle a
                    INNER JOIN
                plnextra AS b ON a.idplnextra = b.id
                	INNER JOIN 
                plnempleado c on c.id = a.idplnempleado
                	INNER JOIN 
    			plnempresa d ON d.id = c.idempresaactual
            WHERE a.id > 0 
			{$condiciones} 
			
EOT;

        $tmp = $this->db
                    ->query($sql)
                    ->fetchAll(PDO::FETCH_ASSOC);

        if (count($tmp) > 0) {
        	if (isset($args["uno"])) {
        		return $tmp[0];
        	} else {
        		return $tmp;
        	}
        }

        return false;
    }
}
