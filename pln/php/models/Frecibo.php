<?php

/**
* Formato Recibo
*/
class Frecibo extends Principal
{
	protected $tabla = 'plnformatorecibo';
	
	function __construct($id = '')
	{
		parent::__construct();
	}

	public function get_campo_formato($campo)
	{
		return (object)$this->db->get(
			$this->tabla, 
			['*'], 
			['campo' => $campo]
		);
	}
}