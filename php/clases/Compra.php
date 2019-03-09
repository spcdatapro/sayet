<?php

require_once BASEPATH . '/php/clases/Base.php';

/**
 * 
 */
class Compra extends Base
{
	private $compra = null;
	private $tabla = "compra";
	
	function __construct($args = [])
	{
		parent::__construct();
	}

    /**
     * @return mixed
     */
    public function getCompra()
    {
        return $this->compra;
    }

    /**
     * @param mixed $compra
     *
     * @return self
     */
    public function setCompra($compra)
    {
        $this->compra = (object)$this->db->get(
			$this->tabla, 
			['*'], 
			['id' => $compra]
		);

        return $this;
    }

    public function guardarCompra($args = [])
    {
        if (!isset($args['idproveedor']) || empty($args['idproveedor'])) {
            $args['idproveedor'] = $this->getProveedorID($args);
        }

        if ($this->compra === null) {
            $lid = $this->db->insert($this->tabla, $args);

            if ($lid) {
                $this->setCompra($lid);
                return true;
            } else {
                $this->set_mensaje('Error en la base de datos al guardar: ' . $this->db->error()[2]);
            }
        } else {
            if ($args['nit'] != $this->compra->nit) {
                $args['idproveedor'] = $this->getProveedorID($args);
            }

            if ($this->db->update($this->tabla, $args, ["id" => $this->compra->id])) {
                $this->setCompra($this->compra->id);
                return true;
            } else {
                if ($this->db->error()[0] == 0) {
                    $this->set_mensaje('Nada que actualizar.');
                } else {
                    $this->set_mensaje('Error en la base de datos al actualizar: ' . $this->db->error()[2]);
                }
            }
        }

        return false;
    }

    /**
     * Recibe como parámetro en el arreglo el NIT y nombre del proveedor
     * De no encontrar hace un insert para generar el registro
     */
    public function getProveedorID($args = [])
    {
        $proveedor = $this->db->get(
            'proveedor', 
            ['*'], 
            ['nit' => $args['nit']]
        );

        if (is_array($proveedor)) {
            return $proveedor['id'];
        } else {
            $this->db->insert('proveedor', [
                'nit' => $args['nit'],
                'nombre' => $args['proveedor']
            ]);

            $this->getProveedorID($args);
        }
    }
}