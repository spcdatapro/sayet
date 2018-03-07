(function(){

    var rptactivosctrl = angular.module('cpm.rptactivosctrl', []);

    rptactivosctrl.controller('rptActivosCtrl', ['$scope', 'activoSrvc', 'empresaSrvc','tipoactivoSrvc', 'municipioSrvc', 'localStorageSrvc', '$location', 'jsReportSrvc', function($scope, activoSrvc, empresaSrvc, tipoactivoSrvc, municipioSrvc, localStorageSrvc, $location, jsReportSrvc){


        $scope.lasEmpresas = [];
        $scope.losActivos = [];
        $scope.losTipoActivo = [];
        $scope.params = {idempresa:'', idtipo: '', idmunicipio: ''};
        $scope.data = [];
        $scope.objEmpresa = [];
        $scope.objTipo = [];
        $scope.objMuni = [];
        $scope.municipios = [];
        $scope.empresastr = '';
        $scope.municipiostr = '';
        $scope.tipostr = '';

        empresaSrvc.lstEmpresas().then(function(d){ $scope.lasEmpresas = d; });
        tipoactivoSrvc.lstTipoActivo().then(function (d) { $scope.losTipoActivo = d; });
        municipioSrvc.lstMunicipios().then(function(d){ $scope.municipios = d; });

        $scope.setLstEmpresas = function(){ $scope.empresastr = objectPropsToList($scope.objEmpresa, 'nomempresa', ', '); };
        $scope.setLstMunis = function(){ $scope.municipiostr = objectPropsToList($scope.objMuni, 'descripcion', ', '); };
        $scope.setLstTipos = function(){ $scope.tipostr = objectPropsToList($scope.objTipo, 'descripcion', ', '); };

        var test = false;

        $scope.getRptActivos = function(){
            $scope.params.idempresa = objectPropsToList($scope.objEmpresa, 'id', ',');
            $scope.params.idtipo = objectPropsToList($scope.objTipo, 'id', ',');
            $scope.params.iddepto = objectPropsToList($scope.objMuni, 'id', ',');
            //console.log($scope.params); return;
            jsReportSrvc.getPDFReport(test ? 'Bk4-helgx' : 'ryRNMROex', $scope.params).then(function(pdf){ $scope.content = pdf; });
            //activoSrvc.rptActivos($scope.params).then(function(d){ $scope.losActivos = d; $scope.styleData(); });
        };

        function indexOfEmpresa(myArray, searchTerm) {
            var index = -1;
            for(var i = 0, len = myArray.length; i < len; i++) {
                if (myArray[i].idempresa === searchTerm) {
                    index = i;
                    break;
                }
            }
            return index;
        }

        function indexOfTipos(myArray, searchTerm, searchEmpresa) {
            var index = -1;
            for(var i = 0, len = myArray.length; i < len; i++) {
                if (myArray[i].idtipo === searchTerm && myArray[i].idempresa === searchEmpresa) {
                    index = i;
                    break;
                }
            }
            return index;
        }

        function getEmpresas(){
            var uniqueEmpresas = [];
            for(var x = 0; x < $scope.losActivos.length; x++){
                if(indexOfEmpresa(uniqueEmpresas, parseInt($scope.losActivos[x].idempresa)) < 0){
                    uniqueEmpresas.push({
                        idempresa: parseInt($scope.losActivos[x].idempresa),
                        nombre: $scope.losActivos[x].nomempresa
                    });
                }
            }
            return uniqueEmpresas;
        }

        function getTipos(){
            var uniqueTipos = [];
            for(var x = 0; x < $scope.losActivos.length; x++){
                if(indexOfTipos(uniqueTipos, parseInt($scope.losActivos[x].idtipo), parseInt($scope.losActivos[x].idempresa)) < 0){
                    uniqueTipos.push({
                        idempresa: parseInt($scope.losActivos[x].idempresa),
                        idtipo: parseInt($scope.losActivos[x].idtipo),
                        tipo: $scope.losActivos[x].tipo
                    });
                }
            }
            return uniqueTipos;
        }

        $scope.styleData = function(){
            $scope.data = [];
            var qEmpresas = getEmpresas(), qTipos = getTipos(), tmp = {};

            for(var i = 0; i < qEmpresas.length; i++){ $scope.data.push({ idempresa: qEmpresas[i].idempresa, nombre: qEmpresas[i].nombre, tipos: [] }); }

            for(i = 0; i < $scope.data.length; i++){
                for(var j = 0; j < qTipos.length; j++){
                    if(qTipos[j].idempresa === $scope.data[i].idempresa){
                        $scope.data[i].tipos.push({ idempresa: qTipos[j].idempresa, idtipo: qTipos[j].idtipo, tipo: qTipos[j].tipo, activos: [] });
                    }
                }
            }

            for(i = 0; i < $scope.data.length; i++){
                for(j = 0; j < $scope.data[i].tipos.length; j++){
                    for(var k = 0; k < $scope.losActivos.length; k++){
                        tmp = $scope.losActivos[k];
                        if(parseInt(tmp.idempresa) === $scope.data[i].tipos[j].idempresa && parseInt(tmp.idtipo) === $scope.data[i].tipos[j].idtipo){
                            $scope.data[i].tipos[j].activos.push({
                                idempresa: $scope.data[i].tipos[j].idempresa,
                                idtipo: $scope.data[i].tipos[j].idtipo,
                                idactivo: tmp.idactivo,
                                finca: tmp.finca,
                                desccorta: tmp.nombre_corto,
                                direccion: tmp.direccion_mun,
                                baseiusi: parseFloat(tmp.iusi),
                                valor: parseFloat(tmp.valor_muni),
                                mtscuad: parseFloat(tmp.metros_muni),
                                horizontal: tmp.eshorizontal,
                                esmultilotes: tmp.esmultilotes
                            });
                        }
                    }
                }
            }
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Catálogo de activos');
        };

        $scope.openActivo = function(idactivo){
            localStorageSrvc.set('idactivo', idactivo);
            $location.path('mntactivo');
        };

    }]);
}());
