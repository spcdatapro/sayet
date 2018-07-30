<?php
/**
 * 
 */
class Periodo extends Principal
{
	public $periodo = null;
	protected $tabla = null;
	
	function __construct($id = null)
	{
		parent::__construct();

		$this->tabla = 'plnperiodo';

		if ($id !== null) {
			$this->cargar_periodo($id);
		}
	}

	public function cargar_periodo($id)
	{
		$this->periodo = (object)$this->db->get(
			$this->tabla, 
			['id', 'inicio', 'fin', 'cerrado'], 
			['id' => $id]
		);
    }

    /**
     * Verifica si un rango dado existe
     * @param  [string] $inicio [String de fecha Ej. 2018-07-01]
     * @param  [string] $fin    [String de fecha Ej. 2018-07-15]
     * @return [bool]
     */
    public function verificar($inicio, $fin)
    {
    	$tmp = (object)$this->db->get(
			$this->tabla, 
			['id', 'inicio', 'fin', 'cerrado'], 
			[
				'AND' => [
					'inicio' => $inicio,
					'fin'    => $fin
				]
			]
		);

		if (isset($tmp->scalar)) {
			return FALSE;
		} else {
			return TRUE;
		}
    }

    public function guardar($args = [])
	{
		if (is_array($args) && !empty($args)) {
			if (elemento($args, 'descripcion')) {
				$this->set_dato('descripcion', $args['descripcion']);
			}
		}

		if (!empty($this->datos)) {
			if ($this->periodo === null) {
				$lid = $this->db->insert($this->tabla, $this->datos);

				if ($lid) {
					$this->cargar_periodo($lid);

					return TRUE;
				} else {
					$this->set_mensaje('Error en la base de datos al guardar: ' . $this->db->error()[2]);
				}
			} else {
				if ($this->db->update($this->tabla, $this->datos, ["id" => $this->periodo->id])) {
					$this->cargar_periodo($this->periodo->id);

					return TRUE;
				} else {
					if ($this->db->error()[0] == 0) {
						$this->set_mensaje('Nada que actualizar.');
					} else {
						$this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
					}
				}
			}
		} else {
			$this->set_mensaje('No hay datos que guardar o actualizar.');
		}

		return FALSE;
	}
}
