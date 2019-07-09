(function(){

    const tipoimpchqctrl = angular.module('cpm.tipoimpchqctrl', []);

    tipoimpchqctrl.controller('tipoImpChqCtrl', ['$scope', 'tipoImpresionChequeSrvc', '$confirm', ($scope, tipoImpresionChequeSrvc, $confirm) => {

        $scope.tipochq = {};
		$scope.lsttiposchq = [];
		$scope.lstcampos = [];

		prepareData = (d) =>{
			d.map((t) => {
				t.pagewidth = parseFloat(t.pagewidth);
				t.pageheight = parseFloat(t.pageheight);
			});
			return d;
		};

		$scope.loadTipos = () => tipoImpresionChequeSrvc.lstTiposImpresionCheque().then((d) => {
			$scope.lsttiposchq = prepareData(d);
			if($scope.tipochq.id){
				$scope.tipochq = $scope.lsttiposchq.find((tp) => +tp.id === +$scope.tipochq.id);
			}
		});

		prepareDataCampos = (d) => {
			d.map((c) => {
				c.superior = parseFloat(c.superior);
				c.izquierda = parseFloat(c.izquierda);
				c.ancho = parseFloat(c.ancho);
				c.alto = parseFloat(c.alto);
				c.tamletra = parseFloat(c.tamletra);
				c.ajustelinea = +c.ajustelinea;
				c.estilodeletra = +c.estilodeletra;
			});
			return d;
		};
		$scope.loadCampos = () => tipoImpresionChequeSrvc.lstCampos($scope.tipochq.formato).then((d) => $scope.lstcampos = prepareDataCampos(d));

		$scope.updTipo = (obj) => tipoImpresionChequeSrvc.editRow(obj, 'u').then(() => {
            toaster.pop({ type: 'success', title: 'Formatos de impresión.', body: 'Datos de formato actualizados con éxito.', timeout: 1500 });
		    $scope.loadTipos();
		});
		$scope.updCampo = (obj) => tipoImpresionChequeSrvc.editRow(obj, 'ud').then(() => {
            toaster.pop({ type: 'success', title: 'Formatos de impresión.', body: 'Datos de etiqueta actualizados con éxito.', timeout: 1500 });
		    $scope.loadCampos();
		});

        $scope.loadTipos();

    }]);

}());
