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
				$this->set_dato('finalizado', 1);
			} else {
				$this->set_dato('liquidacion', 'NULL');
				$this->set_dato('finalizado', '0');
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

	public function get_saldo($args = [])
	{
		if ($this->pre->finalizado == 1) {
			return 0;
		} else {
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

			$saldo = round($this->pre->monto - $abonos, 2);

			if ($saldo != $this->pre->saldo) {
				$this->guardar(['saldo' => $saldo]);
			}

			return $saldo;
		}
	}
}
