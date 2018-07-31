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

    public function hay_abierto()
    {
    	$condiciones = ['cerrado' => 0];

    	if ($this->periodo !== null) {
    		$condiciones['id[<>]'] = $this->periodo->id;
    	}

    	$tmp = $this->db->select(
			$this->tabla, 
			['id', 'inicio', 'fin', 'cerrado'], 
			[
				'AND' => $condiciones
			]
		);

		return count($tmp);
    }

    public function guardar($args = [])
	{
		if (isset($args['inicio'])) {
			$this->set_dato('inicio', $args['inicio']);
		}

		if (isset($args['fin'])) {
			$this->set_dato('fin', $args['fin']);
		}

		if (isset($args['cerrado'])) {
			$this->set_dato('cerrado', $args['cerrado']);
		}

		if (!empty($this->datos)) {
			if ($this->periodo === null) {
				if ($this->verificar($args['inicio'], $args['fin'])) {
					$this->set_mensaje('Este rango ya ha sido ingresado con anterioridad, por favor verifique.');
				} else {
					$lid = $this->db->insert($this->tabla, $this->datos);

					if ($lid) {
						$this->cargar_periodo($lid);

						return TRUE;
					} else {
						$this->set_mensaje('Error en la base de datos al guardar: ' . $this->db->error()[2]);
					}
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
