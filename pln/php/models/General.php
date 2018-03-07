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
		$condicion = [];

		if (elemento($args, 'termino')) {
			$condicion["nombre[~]"] = $args['termino'];
		}

		$condicion["LIMIT"] = [elemento($args, 'inicio', 0), get_limite()];

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

	public function get_archivotipo($args = [])
	{
		return $this->db->select(
			'plnarchivotipo', 
			['*']
		);
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
}