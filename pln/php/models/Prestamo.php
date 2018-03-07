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
			if (elemento($args, 'empleado')) {
				$this->set_dato('idplnempleado', $args['empleado']);
			}

			if (elemento($args, 'monto', FALSE)) {
				$this->set_dato('monto', $args['monto']);
			}

			if (elemento($args, 'cuotamensual', FALSE)) {
				$this->set_dato('cuotamensual', $args['cuotamensual']);
			}

			if (elemento($args, 'iniciopago', FALSE)) {
				$this->set_dato('iniciopago', fecha_angularjs($args['iniciopago']));
			}

			if (elemento($args, 'liquidacion', FALSE)) {
				$this->set_dato('liquidacion', fecha_angularjs($args['liquidacion']));
				$this->set_dato('finalizado', 1);
			}

			if (elemento($args, 'concepto', FALSE)) {
				$this->set_dato('concepto', $args['concepto']);
			}
		}

		if (!empty($this->datos)) {
			if ($this->pre) {
				if ($this->pre->finalizado) {
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
}

?>