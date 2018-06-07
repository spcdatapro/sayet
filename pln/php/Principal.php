<?php

require_once dirname(dirname(__DIR__)) . '/php/db.php';

/**
* Clase principal que se heredarà, incluye funciones bàsica que se usaràn 
* en todas las subclases
*/
class Principal extends dbcpm
{
	protected $mensaje = '';
	public $db;
	public $datos = [];

	function __construct()
	{
		parent::__construct();
		$this->db = $this->getConn();

		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	}

	/**
	 * Se usarà para retornar mensajes de errores o de éxito 
	 * @param string $texto texto a agregar
	 */
	public function set_mensaje($texto)
	{
		$this->mensaje .= $texto;
	}

	/**
	 * Texto que se va agregando en el transcurso del código
	 * @return string 
	 */
	public function get_mensaje()
	{
		return $this->mensaje;
	}

	public function set_dato($campo, $valor)
	{
		$this->datos[$campo] = $valor;
	}
}