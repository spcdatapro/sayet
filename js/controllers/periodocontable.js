(function(){

    var periodocontctrl = angular.module('cpm.periodocontctrl', ['cpm.pcontsrvc']);

    periodocontctrl.controller('periodoContableCtrl', ['$scope', 'periodoContableSrvc', 'toaster', function($scope, periodoContableSrvc, toaster){

        //var now = moment();

        $scope.elPeriodo = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), abierto: 0};
        $scope.losPeriodos = [];

        function dateToStr(fecha){ return fecha !== null && fecha !== undefined ? (fecha.getFullYear() + '-' + (fecha.getMonth() + 1) + '-' + fecha.getDate()) : ''; };

        function procData(data){
            for(var i = 0; i < data.length; i++){
                data[i].del = moment(data[i].del).toDate();
                data[i].al = moment(data[i].al).toDate();
                data[i].abierto = parseInt(data[i].abierto);
            };
            return data;
        };

        $scope.getLstPeriodos = function(){
            periodoContableSrvc.lstPeriodosCont().then(function(d){
                $scope.losPeriodos = procData(d);
            });
        };

        $scope.addPeriodo = function(obj){
            obj.delstr = dateToStr(obj.del);
            obj.alstr = dateToStr(obj.al);
            obj.abierto = obj.abierto != null && obj.abierto != undefined ? obj.abierto : 0;
            if(moment(obj.del).isBefore(obj.al)){
                periodoContableSrvc.editRow(obj, 'c').then(function(){
                    $scope.getLstPeriodos();
                    $scope.elPeriodo = {del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), abierto: 0};
                });
            }else{
                toaster.pop({ type: 'error', title: 'Error en las fechas.',
                    body: 'La fecha inicial no puede ser mayor a la fecha final.', timeout: 7000 });
                $scope.elPeriodo.al = moment(obj.del).endOf('month').toDate();
            };


        };

        $scope.updPeriodo = function(data, id){
            data.id = id;
            data.delstr = dateToStr(data.del);
            data.alstr = dateToStr(data.al);
            data.abierto = data.abierto != null && data.abierto != undefined ? data.abierto : 0;
            //console.log(data);
            periodoContableSrvc.editRow(data, 'u').then(function(){
                $scope.getLstPeriodos();
            });
        };

        $scope.delPeriodo = function(id){
            periodoContableSrvc.editRow({id:id}, 'd').then(function(){
                $scope.getLstPeriodos();
            });
        };

        $scope.getLstPeriodos();
    }]);

}());
