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
			['*'], 
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
			['*'], 
			$condicion
		);
	}

	public function buscar_periodo(Array $args)
	{
		$condicion = [];
		$where = [];
		
		if (elemento($args, 'inicio')) {
			$where["inicio"] = $args['inicio'];
		}

		if (elemento($args, 'fin')) {
			$where["fin"] = $args['fin'];
		}

		if (isset($args['cerrado'])) {
			$where["cerrado"] = $args['cerrado'];
		}

		if (!empty($where)) {
			$condicion['AND'] = $where;
		}

		if (!elemento($args, 'sin_limite')) {
			$condicion["LIMIT"] = [elemento($args, 'inicio', 0), get_limite()];
		}

		return $this->db->select(
			'plnperiodo', 
			['*'], 
			$condicion
		);
	}

	public function get_archivotipo($args = [])
	{
		return $this->db->select(
			'plnarchivotipo', 
			['*']
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
			['*'],
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
			['*'],
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
			"plnimpresion", 
			['*'], 
			[
				'AND' => [
					'campo' => $campo, 
					'tipo'  => $tipo
				]
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
				$where['OR'] = [
					'finalizado[=]' => 0,
					'AND' => [
						'liquidacion[<>]' => array(formatoFecha($args['fal'], 5), $args['fal'])
					]
				];
			} else {
				$where['finalizado[=]'] = $args['finalizado'];
			}
		}

		if (!empty($where)) {
			$condicion['AND'] = $where;
		}

		if (isset($args['orden'])) {
			if ($args['orden'] === 'empleado') {
				$condicion["ORDER"] = "b.nombre ASC";
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
				'[><]plnempleado(b)' => ['plnprestamo.idplnempleado' => 'id']
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
				"b.idempresaactual"
			],
			$condicion
		);
	}
}
