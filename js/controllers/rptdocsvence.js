(function(){

    var rptdocsvencectrl = angular.module('cpm.rptdocsvencectrl', []);

    rptdocsvencectrl.controller('rptDocsVenceCtrl', ['$scope', 'proyectoSrvc', function($scope, proyectoSrvc){


        $scope.params = {fal:moment().endOf('month').toDate(), fvencestr: ''};
        $scope.data = [];
        $scope.proyectos = [];

        $scope.getRptDocsVence = function(){
            $scope.params.fvencestr = moment($scope.params.fal).format('YYYY-MM-DD');
            proyectoSrvc.rptDocsVence($scope.params).then(function(d){
                $scope.proyectos = d;
                $scope.styleData();
            });

        };

        function indexOfProyecto(myArray, searchTerm) {
            var index = -1;
            for(var i = 0, len = myArray.length; i < len; i++) {
                if (myArray[i].idproyecto === searchTerm) {
                    index = i;
                    break;
                }
            }
            return index;
        };

        function getProyectos(){
            var uniqueProyectos = [];
            for(var x = 0; x < $scope.proyectos.length; x++){
                if(indexOfProyecto(uniqueProyectos, parseInt($scope.proyectos[x].idproyecto)) < 0){
                    uniqueProyectos.push({
                        idproyecto: parseInt($scope.proyectos[x].idproyecto),
                        proyecto: $scope.proyectos[x].nomproyecto
                    });
                };
            };
            return uniqueProyectos;
        };

        $scope.styleData = function(){
            $scope.data = [];
            var qProyectos = getProyectos(), tmp = {};

            for(var i = 0; i < qProyectos.length; i++){ $scope.data.push({ idproyecto: qProyectos[i].idproyecto, nombre: qProyectos[i].proyecto, docs: [] }); };

            for(var i = 0; i < $scope.data.length; i++){
                for(var j = 0; j < $scope.proyectos.length; j++){
                    tmp = $scope.proyectos[j];
                    if(parseInt(tmp.idproyecto) === $scope.data[i].idproyecto){
                        $scope.data[i].docs.push({
                            nombre: tmp.nomadjunto,
                            ubicacion: tmp.ubicacion,
                            tipo: tmp.tipodoc,
                            numero: tmp.numero,
                            fvence: moment(tmp.fvence).toDate()
                        });
                    };
                };
            };
        };

        $scope.printVersion = function(){
            PrintElem('#toPrint', 'Vencimiento de documentos');
        };

    }]);
}());
