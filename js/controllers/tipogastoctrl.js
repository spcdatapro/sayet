(function(){

    var tipogastoctrl = angular.module('cpm.tipogastoctrl', ['cpm.tipogastosrvc']);

    tipogastoctrl.controller('tipogastoCtrl', ['$scope', 'tipogastoSrvc',  '$confirm', function($scope, tipogastoSrvc, $confirm){

        $scope.elTipogasto = {};
        $scope.lstTipogastos = [];
        $scope.subtiposgasto = {};
        $scope.lstsubtiposgasto = [];


        $scope.grpBtnTipoGasto = {i: false, p:false, e: false, u: false, c: false, d: false, a: true};
        $scope.grpBtnSubTipoGasto = {i: false, p:false, e: false, u: false, c: false, d: false, a: true};
        $scope.sl = {tipogasto: true, subtipogasto: true};

        $scope.confGrpBtn = function(grp, i, u, d, a, e, c, p){
            var instruccion = "$scope." + grp + ".i = i; $scope." + grp + ".u = u; $scope." + grp + ".d = d; $scope." + grp + ".a = a; $scope." + grp + ".e = e; $scope." + grp + ".c = c; $scope." + grp + ".p = p;";
            eval(instruccion);
        };

        $scope.resetTipogasto = function(){
            $scope.elTipogasto = {};
        };

        $scope.getLstTipogasto = function(){
            tipogastoSrvc.lstTipogastos().then(function(d){
                $scope.lstTipogastos = d;
            });
        };

        $scope.getTipogasto = function(idtipogasto){
            $scope.subtiposgasto = {};
            $scope.lstsubtiposgasto = [];
            tipogastoSrvc.getTipogasto(idtipogasto).then(function(d){
                $scope.elTipogasto = d[0];
                $scope.getLstSubTipogasto();
                $scope.confGrpBtn('grpBtnTipoGasto', false, false, true, true, true, false, false);
                $scope.sl.tipogasto = true;
                goTop();
            });
        };

        $scope.cancelEditTipogasto = function(){
            if($scope.elTipogasto.id > 0){
                $scope.getTipogasto($scope.elTipogasto.id);
            }else{
                $scope.resetTipogasto();
            }
            $scope.confGrpBtn('grpBtnTipoGasto', false, false, false, true, false, false, false);
            $scope.sl.tipogasto = true;
        };

        $scope.startEditTipogasto = function(){
            $scope.sl.tipogasto = false;
            $scope.confGrpBtn('grpBtnTipoGasto', false, true, true, false, false, true, false);
            goTop();
        };

        $scope.imprimirTipogasto = function(){ console.log('Función pendiente...') };

        $scope.nuevoTipogasto = function(){
            $scope.sl.tipogasto = false;
            $scope.resetTipogasto();
            $scope.confGrpBtn('grpBtnTipoGasto', true, false, false, false, false, true, false);
        };

        $scope.addTipogasto = function(obj){
            tipogastoSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstTipogasto();
                $scope.getTipogasto(d.lastid);
            });
        };

        $scope.updTipogasto = function(obj){
            tipogastoSrvc.editRow(obj, 'u').then(function(){
                $scope.getLstTipogasto();
                $scope.getTipogasto(obj.id);
            });
        };

        $scope.delTipogasto = function(obj){
            $confirm({ text: "¿Esta seguro(a) de eliminar este tipo de gasto?", title: 'Eliminar tipo de gasto', ok: 'Sí', cancel: 'No'}).then(function() {
                tipogastoSrvc.editRow({id:obj.id}, 'd').then(function(){ $scope.getLstTipogasto(); $scope.resetTipogasto(); });
            });
        };

        //subtipo de gasto
        $scope.resetSubTipogasto = function(){
            $scope.subtiposgasto = { idtipogasto: $scope.elTipogasto.id, idcuentac: 0 };
        };

        $scope.getLstSubTipogasto = function(){
            tipogastoSrvc.lstSubTipoGastoByTipoGasto($scope.elTipogasto.id).then(function(d){
                $scope.lstsubtiposgasto = d;
            });
        };

        $scope.getSubTipogasto = function(idsubtipogasto){
            tipogastoSrvc.getSubTipoGasto(idsubtipogasto).then(function(d){
                $scope.subtiposgasto = d[0];
                $scope.confGrpBtn('grpBtnSubTipoGasto', false, false, true, true, true, false, false);
                $scope.sl.subtipogasto = true;
                goTop();
            });
        };

        $scope.cancelEditSubTipogasto = function(){
            if($scope.subtiposgasto.id > 0){
                $scope.getSubTipogasto($scope.subtiposgasto.id);
            }else{
                $scope.resetSubTipogasto();
            }
            $scope.confGrpBtn('grpBtnSubTipoGasto', false, false, false, true, false, false, false);
            $scope.sl.subtipogasto = true;
        };

        $scope.startEditSubTipogasto = function(){
            $scope.sl.subtipogasto = false;
            $scope.confGrpBtn('grpBtnSubTipoGasto', false, true, true, false, false, true, false);
            goTop();
        };

        $scope.imprimirSubTipogasto = function(){ console.log('Función pendiente...') };

        $scope.nuevoSubTipogasto = function(){
            $scope.sl.subtipogasto = false;
            $scope.resetSubTipogasto();
            $scope.confGrpBtn('grpBtnSubTipoGasto', true, false, false, false, false, true, false);
        };

        function prepSubTipoGasto(obj){
            obj.idtipogasto = $scope.elTipogasto.id;
            obj.idcuentac = 0;
            return obj;
        }

        $scope.addSubTipogasto = function(obj){
            obj = prepSubTipoGasto(obj);
            tipogastoSrvc.editRow(obj, 'cd').then(function(d){
                $scope.getLstSubTipogasto();
                $scope.getSubTipogasto(d.lastid);
            });
        };

        $scope.updSubTipogasto = function(obj){
            obj = prepSubTipoGasto(obj);
            tipogastoSrvc.editRow(obj, 'ud').then(function(){
                $scope.getLstSubTipogasto();
                $scope.getSubTipogasto(obj.id);
            });
        };

        $scope.delSubTipogasto = function(obj){
            $confirm({ text: "¿Esta seguro(a) de eliminar este sub-tipo de gasto?", title: 'Eliminar sub-tipo de gasto', ok: 'Sí', cancel: 'No'}).then(function() {
                tipogastoSrvc.editRow({id:obj.id}, 'dd').then(function(){ $scope.getLstSubTipogasto(); $scope.resetSubTipogasto(); });
            });
        };



        $scope.getLstTipogasto();

    }]);

}());

