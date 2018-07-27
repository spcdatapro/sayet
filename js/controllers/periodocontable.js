(function(){

    var periodocontctrl = angular.module('cpm.periodocontctrl', ['cpm.pcontsrvc']);

    periodocontctrl.controller('periodoContableCtrl', ['$scope', 'periodoContableSrvc', 'toaster', '$confirm', function($scope, periodoContableSrvc, toaster, $confirm){

        $scope.elPeriodo = {
            del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), abierto: 0
        };
        $scope.losPeriodos = [];

        function procData(data){
            for(var i = 0; i < data.length; i++){
                data[i].del = moment(data[i].del).toDate();
                data[i].al = moment(data[i].al).toDate();
                data[i].abierto = parseInt(data[i].abierto);
            }
            return data;
        }

        $scope.resetPeriodo = function(){
            $scope.elPeriodo = { id: 0, del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), abierto: 0 };
        };

        $scope.getLstPeriodos = function(){
            periodoContableSrvc.lstPeriodosCont().then(function(d){
                $scope.losPeriodos = procData(d);
            });
        };

        $scope.getPeriodo = function(idperiodo){
            periodoContableSrvc.getPeriodoCont(+idperiodo).then(function(d){
                $scope.elPeriodo = procData(d)[0];
            });
        };

        function setData(obj){
            obj.delstr = moment(obj.del).format('YYYY-MM-DD');
            obj.alstr = moment(obj.al).format('YYYY-MM-DD');
            obj.abierto = obj.abierto != null && obj.abierto !== undefined ? obj.abierto : 0;
            return obj;
        }

        $scope.addPeriodo = function(obj){
            obj = setData(obj);
            if(moment(obj.del).isBefore(obj.al)){
                periodoContableSrvc.editRow(obj, 'c').then(function(){
                    $scope.getLstPeriodos();
                    $scope.resetPeriodo();
                });
            }else{
                toaster.pop({ type: 'error', title: 'Error en las fechas.', body: 'La fecha inicial no puede ser mayor a la fecha final.', timeout: 7000 });
                $scope.elPeriodo.al = moment(obj.del).endOf('month').toDate();
            }
        };

        $scope.updPeriodo = function(data){
            data = setData(data);
            periodoContableSrvc.editRow(data, 'u').then(function(){
                $scope.getLstPeriodos();
                $scope.resetPeriodo();
            });
        };

        $scope.delPeriodo = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar el período del ' + obj.delstr + ' al ' + obj.alstr + '?', title: 'Eliminar período contable', ok: 'Sí', cancel: 'No'}).then(function() {
                periodoContableSrvc.editRow({ id:obj.id }, 'd').then(function(){
                    $scope.getLstPeriodos();
                    $scope.resetPeriodo();
                });
            });
        };

        $scope.getLstPeriodos();
    }]);

}());
