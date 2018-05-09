<?php

/**
* 
*/
class Prestamo extends Principal
{
	public $pre;
	protected $tabla;
	
	function __construct($id = '')
	{
		parent::__construct();

		$this->tabla = 'plnprestamo';

		if (!empty($id)) {
			$this->cargar_prestamo($id);
		}
	}

	public function cargar_prestamo($id)
	{
		$this->pre = (object)$this->db->get(
			$this->tabla, 
			['*'], 
			['id' => $id]
		);
    }

    public function get_empleado()
    {
    	return (object)$this->db->get(
			'plnempleado', 
			['id', 'nombre', 'apellidos'], 
			['id' => $this->pre->idplnempleado]
		);
    }

    public function guardar($args = [])
	{
		if (is_array($args) && !empty($args)) {
			if (elemento($args, 'idplnempleado')) {
				$this->set_dato('idplnempleado', $args['idplnempleado']);
			}

			if (elemento($args, 'monto', FALSE)) {
				$this->set_dato('monto', $args['monto']);
			}

			if (elemento($args, 'cuotamensual', FALSE)) {
				$this->set_dato('cuotamensual', $args['cuotamensual']);
			}

			if (elemento($args, 'iniciopago', FALSE)) {
				$this->set_dato('iniciopago', $args['iniciopago']);
			}

			if (elemento($args, 'liquidacion', FALSE)) {
				$this->set_dato('liquidacion', $args['liquidacion']);
				$this->set_dato('finalizado', TRUE);
			} else {
				$this->set_dato('liquidacion', NULL);
				$this->set_dato('finalizado', FALSE);
			}

			if (elemento($args, 'concepto', FALSE)) {
				$this->set_dato('concepto', $args['concepto']);
			}

			if (elemento($args, 'saldo', FALSE)) {
				$this->set_dato('saldo', $args['saldo']);
			}
		}

		if (!empty($this->datos)) {
			if ($this->pre) {
				if ($this->pre->finalizado == 0) {
					if ($this->db->update($this->tabla, $this->datos, ["id" => $this->pre->id])) {
						$this->cargar_prestamo($this->pre->id);

						return TRUE;
					} else {
						if ($this->db->error()[0] == 0) {
							$this->set_mensaje('Nada que actualizar.');
						} else {
							$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
						}
					}
				} else {
					$this->set_mensaje("Préstamo finalizado, no puedo continuar.");
				}
			} else {
				$this->set_dato('saldo', elemento($args, 'monto', 0));

				$lid = $this->db->insert($this->tabla, $this->datos);

				if ($lid) {
					$this->cargar_prestamo($lid);

					return TRUE;
				} else {
					$this->set_mensaje('Error en la base de datos al guardar: ' . $this->db->error()[2]);
				}
			}
		} else {
			$this->set_mensaje('No hay datos que guardar o actualizar.');
		}

		return FALSE;
	}

	public function guardar_omision($args = [])
	{
		if (elemento($args, 'fecha', FALSE)) {
			$datos = [
				'fecha' => $args['fecha'], 
				'idusuario' => $_SESSION['uid'], 
				'idplnprestamo' => $this->pre->id
			];

			$lid = $this->db->insert('plnpresnodesc', $datos);

			if ($lid) {
				return TRUE;
			} else {
				$this->set_mensaje('Error en la base de datos al guardar: ' . $this->db->error()[2]);
			}
		} else {
			$this->set_mensaje('Por favor ingrese una fecha.');
		}

		return FALSE;
	}

	public function get_omisiones()
	{
		return $this->db->select("plnpresnodesc", [
				'[><]usuario(b)' => ['plnpresnodesc.idusuario' => 'id']
			], 
			[
				"plnpresnodesc.id",
				"plnpresnodesc.fecha",
				"plnpresnodesc.registro",
				"b.nombre"
			],
			[
				'idplnprestamo' => $this->pre->id
			]
		);
	}

	public function guardar_abono($args = [])
	{
		if ($this->get_saldo() >= $args['monto']) {
			$datos = [
				'fecha' => $args['fecha'],
				'monto' => $args['monto'],
				'concepto' => $args['concepto'],
				'idusuario' => $_SESSION['uid'],
				'idplnprestamo' => $this->pre->id
			];

			$lid = $this->db->insert('plnpresabono', $datos);

			if ($lid) {
				return TRUE;
			} else {
				$this->set_mensaje('Error al guardar: ' . $this->db->error()[2]);
			}
		} else {
			$this->set_mensaje('El saldo es inferior al monto ingresado. Por favor verifique e intente nuevamente.');
		}

		return FALSE;
	}

	public function get_abonos()
	{
		return $this->db->select("plnpresabono", [
				'[><]usuario(b)' => ['plnpresabono.idusuario' => 'id']
			], 
			[
				"plnpresabono.id",
				"plnpresabono.fecha",
				"plnpresabono.monto",
				"plnpresabono.concepto",
				"plnpresabono.registro",
				"b.nombre"
			],
			[
				'idplnprestamo' => $this->pre->id
			]
		);
	}

	public function get_saldo($args = [])
	{
		if ($this->pre->finalizado == 1) {
			return 0;
		} else {
			$abonos = $this->get_total_descuentos();
			$saldo  = ($this->pre->monto - $abonos);

			if ($saldo != $this->pre->saldo) {
				$this->guardar(['saldo' => $saldo]);
			}

			return $saldo;
		}
	}

	public function get_total_descuentos()
	{
		return ($this->get_descuentos_planilla() + $this->get_otro_abonos());
	}

	public function get_descuentos_planilla()
	{
		$abonos = 0;

		$tmp = $this->db->select(
			'plnpresnom', 
			['monto'],
			['idplnprestamo' => $this->pre->id]
		);

		if ($tmp) {
			foreach ($tmp as $row) {
				$abonos += $row['monto'];
			}
		}

		return $abonos;
	}

	public function get_otro_abonos()
	{
		$abonos = 0;

		$tmpdir = $this->db->select(
			'plnpresabono', 
			['monto'],
			['idplnprestamo' => $this->pre->id]
		);

		if ($tmpdir) {
			foreach ($tmpdir as $row) {
				$abonos += $row['monto'];
			}
		}

		return $abonos;
	}

	public function get_saldo_anterior($args=[])
	{
		$abonos = 0;

		$tmp = $this->db->select("plnpresnom", [
				'[><]plnnomina(b)' => ['plnpresnom.idplnnomina' => 'id']
			], 
			[
				"plnpresnom.monto"
			],
			[
				'plnpresnom.idplnprestamo' => $this->pre->id,
				'b.fecha[<]' => $args['fecha']
			]
		);

		if ($tmp) {
			foreach ($tmp as $row) {
				$abonos += $row['monto'];
			}
		}

		$tmpdir = $this->db->select(
			'plnpresabono', 
			['monto'],
			[
				'idplnprestamo' => $this->pre->id,
				'fecha[<]' => $args['fecha']
			]
		);

		if ($tmpdir) {
			foreach ($tmpdir as $row) {
				$abonos += $row['monto'];
			}
		}

		return ($this->pre->monto - $abonos);
	}
}
