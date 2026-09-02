<?php

/**
* 
*/
class Puesto extends Principal
{
	public $pst;
	protected $tabla;
	
	function __construct($id = '')
	{
		parent::__construct();

		$this->tabla = 'plnpuesto';

		if (!empty($id)) {
			$this->cargar_puesto($id);
		}
	}

	public function cargar_puesto($id)
	{
		$this->pst = (object)$this->db->get(
			$this->tabla, 
			['id', 'descripcion'], 
			['id' => $id]
		);
    }

    public function guardar($args = '')
	{
		if (is_array($args) && !empty($args)) {
			if (elemento($args, 'descripcion')) {
				$this->set_dato('descripcion', $args['descripcion']);
			}
		}

		if (!empty($this->datos)) {
			if ($this->pst) {
				if ($this->db->update($this->tabla, $this->datos, ["id" => $this->pst->id])) {
					$this->cargar_puesto($this->pst->id);

					return TRUE;
				} else {
					if ($this->db->error()[0] == 0) {
						$this->set_mensaje('Nada que actualizar.');
					} else {
						$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
					}
				}
			} else {
				$lid = $this->db->insert($this->tabla, $this->datos);

				if ($lid) {
					$this->cargar_puesto($lid);

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

	public function eliminar($id) : object {
		$datos = $this->db->has('plnempleado', ['idplnpuesto' => $id]);

		if ($datos) {
			$respuesta = new StdClass;
			$respuesta->tipo = 'warning';
			$respuesta->mensaje = 'No se puede eliminar el puesto, ya que tiene empleados asociados.';
			return $respuesta;
		}

		$elm = $this->db->delete('plnpuesto', ["id [=]" => $id]);

		if ($elm) {
			$respuesta = new StdClass;
			$respuesta->tipo = 'success';
			$respuesta->mensaje = 'Puesto eliminado con exito.';
		} else {
			$respuesta = new StdClass;
			$respuesta->tipo = 'error';
			$respuesta->mensaje = 'Error al eliminar puesto, favor comunicarse con IT.';
		}
		return $respuesta;
	}
}

?>