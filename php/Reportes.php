<?php
class GeneradorReportes {
    private $data_ordenada = [];
    private $primero = true;
    private $idempresa;
    private $idproyecto;
    private $sumas_general;

    public function __construct($data, $tipo, $montos, $porproyecto = false) {

        // sumadores
        $sumas_empresa = new StdClass;
        $sumas_proyecto = new StdClass;
        $this->sumas_general = new StdClass;

        // contar datos
        $cntDatos = count($data);

        // crear sumadores general
        foreach($montos as $monto) {
            $this->sumas_general->$monto = [];
        }

        // validar si hay datos, si no tirar error
        if ($cntDatos > 0) {
            for ($i = 0; $i < $cntDatos; $i++)  {
                // traer dato
                $d = $data[$i]; 

                // validar si es la primera vuelta para generar vairables o si se esta haciendo cambio de empresa y/o proyecto
                if($this->primero || $d->idempresa != $this->idempresa) {
                    // si no es primera vuelta realizar sumas y empujar datos a array padre
                    if(!$this->primero) {
                        // solo se hara si el proyecto continuara siendo el mismo
                        if($d->idproyecto == $this->idproyecto && $porproyecto) {
                            // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
                            foreach($montos as $monto) {
                                $separador_proyecto->$monto = round(array_sum($sumas_proyecto->$monto), 2);
                            }

                            // empujar a array padre, proyecto es a empresa
                            array_push($separador_empresa->proyectos, $separador_proyecto);
                        }

                        // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
                        foreach($montos as $monto) {
                            $separador_empresa->$monto = round(array_sum($sumas_empresa->$monto), 2);
                        }

                        // empujar a array padre, empresa es a global
                        array_push($this->data_ordenada, $separador_empresa);
                    }

                    // crear separador de empresa
                    $separador_empresa = new StdClass;
                    $separador_empresa->nombre = $d->empresa;
                    $separador_empresa->numero = $d->numero > 0 ? $d->numero : null;
                    $separador_empresa->abreviatura = $d->abreviatura;
                    $separador_empresa->porproyecto = $porproyecto ? true : null;

                    // crear sumadores empresa 
                    foreach($montos as $monto) {
                        $sumas_empresa->$monto = [];
                    }

                    // si se agrupa por proyecto crear array para insertar proyecto, de lo contrario array para insertar los datos directamente
                    if ($porproyecto) {
                        $separador_empresa->proyectos = array();

                        // para que genere variables de proyecto
                        $this->primero = true;
                    } else {
                        $separador_empresa->$tipo = array();

                        // terminar primera vuelta 
                        $this->primero = false;
                    }

                    // para poder hacer el reseteo de variables al cambiar de empresa
                    $this->idempresa = $d->idempresa;
                }

                if($porproyecto && ($this->primero || $d->idproyecto != $this->idproyecto)) {
                    // si no es primera vuelta realizar sumas y empujar datos a array padre
                    if(!$this->primero) {
                        // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
                        foreach($montos as $monto) {
                            $separador_proyecto->$monto = round(array_sum($sumas_proyecto->$monto), 2);
                        }

                        // empujar a array padre, proyecto es a empresa
                        array_push($separador_empresa->proyectos, $separador_proyecto);
                    }

                    // crear separador de proyecto
                    $separador_proyecto = new StdClass;
                    $separador_proyecto->nombre = $d->proyecto;
                    $separador_proyecto->$tipo = array();

                    // crear sumadores de proyecto
                    foreach($montos as $monto) {
                        $sumas_proyecto->$monto = [];
                    }

                    // terminar primera vuelta 
                    $this->primero = false;

                    // para poder hacer el reseteo de variables al cambiar de proyecto
                    $this->idproyecto = $d->idproyecto;
                }

                // empujar datos una vez generada las variables y validado estar en la mimsa empresa o proyecto
                if ($porproyecto) {
                    array_push($separador_proyecto->$tipo, $d);
                } else {
                    array_push($separador_empresa->$tipo, $d);
                }

                // empujar montos para sumas
                if ($porproyecto) {
                    // proyecto
                    foreach($montos as $monto) {
                        array_push($sumas_proyecto->$monto, $d->$monto);
                    }
                }

                // empresa
                foreach($montos as $monto) {
                    array_push($sumas_empresa->$monto, $d->$monto);
                }

                // general
                foreach($montos as $monto) {
                    array_push($this->sumas_general->$monto, $d->$monto);
                }
            }
        } else {
            $data_ordenada = 'No se recibieron datos';
        }

        // para empujar los ultimos datos
        if($porproyecto) {
            // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
            foreach($montos as $monto) {
                $separador_proyecto->$monto = round(array_sum($sumas_proyecto->$monto), 2);
            }

            // empujar a array padre, proyecto es a empresa
            array_push($separador_empresa->proyectos, $separador_proyecto);
        }

        // por cada suma que se hara generar la variable dentro de separador con el nombre de la suma
        foreach($montos as $monto) {
            $separador_empresa->$monto = round(array_sum($sumas_empresa->$monto), 2);
        }

        // empujar a array padre, empresa es a global
        array_push($this->data_ordenada, $separador_empresa);
    }

    public function getReporte() {
        return $this->data_ordenada;
    }

    public function getTotalesGenerales() {
        return $this->sumas_general;
    }
}
?>