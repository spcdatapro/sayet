<?php

/**
* 
*/
class Vacaciones extends Empleado
{
    public function get_vacaciones($args=[])
	{
		$where = ["idplnempleado" => $this->emp->id];

		if (isset($args["anio"])) {
			$where["anio"] = $args["anio"];
        }
        
        if (elemento($args, 'fdel')) {
			$where['fechainicio[>=]'] = $args['fdel'];
		}

		if (isset($args["anulado"])) {
			$where["anulado"] = $args["anulado"];
		}

		if (isset($args["pagadas"])) {
			$where["pagadas"] = $args["pagadas"];
        }

		if (count($where) > 1) {
			$condicion = ['AND' => $where];
		} else {
			$condicion = $where;
        }
        
        if (isset($args["reciente"])) {
            $condicion["ORDER"] = "fechainicio DESC";
        }

		return $this->db->select(
			'plnvacaciones', 
			['*'], 
			$condicion
		);
    }
    
    public function getDiasPagados($args=[])
    {
        $tmp = $this->get_vacaciones([
            "anio" => $args["anio"],
            "pagadas" => 1,
            "anulado" => 0
        ]);

        if (count($tmp) > 0) {
            return $tmp[0];
        } else {
            return false;
        }
    }

    public function setDiasVacaciones($args=[])
    {        
        if (empty($this->emp->reingreso)) {
            $ingreso = new DateTime($this->emp->ingreso);
        } else {
            $ingreso = new DateTime($this->emp->reingreso);
        }

        $diasAnio = 365;
        $dusados = ["pagadas" => 0, "anulado" => 0];

        if ($ingreso->format('Y') == $args["anio"]) {
            $finAnio  = new DateTime($args["anio"]."-12-31");
            $interval = $ingreso->diff($finAnio);
            $dias     = ($interval->format('%a')+1);

            if ($dias > $diasAnio) {
                $diasLaborados = $diasAnio;
            } else {
                $diasLaborados = $dias;
            }

            $vacasdias = (($diasLaborados*15)/$diasAnio);
            
            $dusados["anio"] = $args["anio"];
        } else {
            $vacasdias = 15;

            if (elemento($args, "vacasultimas")) {
                $dusados["fdel"] = $args["vacasultimas"];
            } else {
                $dusados["anio"] = $args["anio"];
            }
        }

        $datosExtra = [
            "vacassueldo" => $this->emp->sueldo,
            "vacasingreso" => $ingreso->format('Y-m-d'),
            "vacasdias" => $vacasdias
        ];

        if ($args["accion"] == 1) {
            $datosExtra["vacasultimas"] = elemento($args, "vacasultimas");
            $datosExtra["vacasgozar"] = elemento($args, "vacasgozar");
        }

        $this->guardar_extra([
            "anio" => $args["anio"],
            "datos" => $datosExtra
        ]);
    }

    public function getDatosVacas($anio)
    {
        $tmp = new General();
        
        return $tmp->getDatosVacas([
            "uno" => true,
            "anio" => $anio,
            "idplnempleado" => $this->emp->id
        ]);
    }

    public function getImpresionVacas($args = [])
    {
        $vac = $this->getDatosVacas($args["anio"]);

        if ($vac) {
            $emp = $this->get_empresa_debito();

            $fecha = date('d/m/Y');
            $inicio = formatoFecha($vac["inicio"], 1);
            $fin = formatoFecha($vac["fin"], 1);
            $vacasgozar = "01/12/" . $args["anio"];
            $fingoce = "15/12/" . $args["anio"];
            $presentar = "16/12/" . $args["anio"];
            
            return [
                "uno" => "Guatemala {$fecha}",
                "dos" => "Señores {$emp->nomempresa}\nPresente",
                "tres" => "Hago constar que de conformidad con el artículo No. 130 del código de trabajo, desde esta fecha he principiado a gozar de mi período de vacaciones por el término de 15 días.",
                "cuatro" => "Vacaciones que me corresponden por el úlitmo año de trabajo en su establecimiento comercial comprendido del {$inicio} al {$fin}.",
                "cinco" => "A gozar del {$vacasgozar} al {$fingoce}.",
                "seis" => "debiendo presentarme a mis labores el día {$presentar}.",
                "siete" => "Así mismo hago constar que el importe del sueldo correspondiente a dichas vacaciones me ha sido cubierto por anticipado; y que firmo la presente de conformidad con el artículo No. 137 del código de trabajo.",
                "ocho" => "Atentamente,",
                "nueve" => str_repeat("_", 45),
                "diez" => "{$this->emp->nombre} {$this->emp->apellidos}",
                "once" => "Autorizado por:",
                "doce" => str_repeat("_", 45)
            ];
        } else {
            return false;
        }
    }
}