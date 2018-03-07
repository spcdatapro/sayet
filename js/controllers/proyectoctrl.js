(function(){

    var proyectoctrl = angular.module('cpm.proyectoctrl', ['cpm.proyectosrvc']);

    proyectoctrl.controller('proyectoCtrl', ['$scope', 'proyectoSrvc', 'empresaSrvc','tipoProyectoSrvc','proyectoAdjuntoSrvc','tipoAdjuntoSrvc','Upload','activoSrvc','proyectoActivoSrvc', '$confirm', 'tipoDocProySrvc', 'unidadSrvc', 'tipoLocalSrvc', 'DTOptionsBuilder', '$filter', 'servicioBasicoSrvc', 'toaster', '$uibModal', 'jsReportSrvc', 'servicioPropioSrvc', 'authSrvc', function($scope, proyectoSrvc, empresaSrvc, tipoProyectoSrvc,proyectoAdjuntoSrvc,tipoAdjuntoSrvc,Upload,activoSrvc,proyectoActivoSrvc, $confirm, tipoDocProySrvc, unidadSrvc, tipoLocalSrvc, DTOptionsBuilder, $filter, servicioBasicoSrvc, toaster, $uibModal, jsReportSrvc, servicioPropioSrvc, authSrvc){

        $scope.losProyectoAdjuntos = [];
        $scope.losDetActivoProy = [];
        $scope.unidadesProy = [];
        $scope.servciosUnidad = [];
        $scope.servubas = [];

        $scope.elProyecto = {};
        $scope.lasEmpresas = [];
        $scope.losProyectos = [];
        $scope.losTipoProyecto = [];
        $scope.elProyectoAdjunto = {};
        $scope.elDetActivoProy = {};
        $scope.elDetDocProy = {};
        $scope.losDetDocProy = [];
        $scope.losTipoAdjunto = [];
        $scope.losActivos = [];
        $scope.tiposDocProy = [];
        $scope.unidades = [];
        $scope.unidadProy = {};
        $scope.tiposlocales = [];
        $scope.unidad = {};
        $scope.serviciosDisponibles = [];
        $scope.servuni = {};
        $scope.slproyecto = true;
        $scope.slunidad = true;
        $scope.slservbas = true;
        $scope.grpBtnProyecto = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.grpBtnUnidad = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.grpBtnServBas = {i: false, u: false, d: false, a: true, e: false, c: false};
        $scope.showForm = {adjunto:false, activo:false, unidad: false, servicio: false};
        $scope.params = {idproyecto:0};
        $scope.usrdata = {};

        $scope.totalItems = 25;
        $scope.currentPage = 1;
        $scope.maxSize = 25;
        $scope.bigTotalItems = 25;
        $scope.bigCurrentPage = 1;

        $scope.proyectostr = '';

        $scope.dtOptions = DTOptionsBuilder.newOptions().withPaginationType('full_numbers').withBootstrap()
            .withBootstrapOptions({ pagination: { classes: { ul: 'pagination pagination-sm' } } }).withOption('responsive', true).withOption('ordering', false);

        authSrvc.getSession().then(function(usrLogged){ $scope.usrdata = usrLogged; });

        // upload later on form submit or something similar
        $scope.submit = function() {
            if ($scope.file) {
                $scope.upload($scope.file);
            }
        };

        // upload on file select or drop
        $scope.upload = function (file) {
            Upload.upload({
                url: 'php/upload.php',
                method: 'POST',
                file: file,
                sendFieldsAs: 'form',
                fields: {
                    directorio: '../proyecto_adjunto/',
                    prefijo: 'PRY_'+$scope.elProyecto.id+'_'
                }
            }).then(function (resp) {
                //console.log(resp);
                //console.log('Success ' + resp.config.data.file.name + 'uploaded. Response: ' + resp.data);
            }, function (resp) {
                //console.log(resp);
                //console.log('Error status: ' + resp.status);
            }, function (evt) {
                //console.log(evt);
                var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
                //console.log('progress: ' + progressPercentage + '% ' + evt.config.data.file.name);
            });
        };

        $scope.resetElProyecto = function(){
            $scope.elProyecto = {
                id : 0,
                nomproyecto : '',
                registro : '',
                direccion : '',
                notas : '',
                metros : parseFloat(0.0),
                idempresa : 0,
                metros_rentable : parseFloat(0.0),
                tipo_proyecto : 0,
                subarrendado : 0,
                notas_contrato : '',
                referencia : '',
                fechaapertura: null,
                multiempresa: 0
            };
            $scope.proyectostr = '';
        };

        $scope.resetElProyecto();

        $scope.getlstEmpresas = function(){
            empresaSrvc.lstEmpresas().then(function(d){
                $scope.lasEmpresas = d;
            });
        };

        $scope.getLstActivos = function(){
            activoSrvc.lstActivo().then(function(d){
                $scope.losActivos = d;
            });
        };

        $scope.getlstTipoProyecto = function() {
            tipoProyectoSrvc.lstTipoProyecto().then(function (d) {
                $scope.losTipoProyecto = d;
            });
        };

        $scope.getlstTipoAdjunto = function() {
            tipoAdjuntoSrvc.lstTipoAdjunto().then(function (d) {
                $scope.losTipoAdjunto = d;
            });
        };

        $scope.getLstProyectoAdjuntos = function() {
            proyectoAdjuntoSrvc.getProyectoAdjunto($scope.elProyecto.id).then(function(d){
                $scope.losProyectoAdjuntos = d;
            });
        };

        $scope.getLstDetActivoProy = function() {
            proyectoSrvc.getDetalleActivoProyecto($scope.elProyecto.id).then(function(d){
                var summcuad = 0.0;
                for(var x = 0; x < d.length; x++){
                    d[x].id = parseInt(d[x].id);
                    d[x].metros_muni = parseFloat(d[x].metros_muni);
                    summcuad += d[x].metros_muni;
                }
                $scope.elProyecto.metros = summcuad;
                if($scope.elProyecto.id != null && $scope.elProyecto.id != undefined){ $scope.updProyecto($scope.elProyecto, $scope.elProyecto.id); }
                d.push({id:0, nomempresa: 'Total -->', metros_muni: summcuad});
                $scope.losDetActivoProy = d;
            });
        };

        $scope.getLstDetDocProy = function() {
            proyectoSrvc.getDetalleDocsProyecto($scope.elProyecto.id).then(function(d){
                $scope.losDetDocProy = d;
            });
        };

        $scope.getLstUnidadesProyecto = function(idproyecto){
            proyectoSrvc.lstUnidadesProyecto(idproyecto).then(function(d){
                var summrent = 0.0, summnorent = 0.0;
                for(var i = 0; i < d.length; i++){
                    d[i].id = parseInt(d[i].id);
                    d[i].idproyecto = parseInt(d[i].idproyecto);
                    d[i].mcuad = parseFloat(parseFloat(d[i].mcuad).toFixed(4));
                    d[i].esrentable = parseInt(d[i].esrentable);
                    if(d[i].esrentable == 1){
                        summrent += d[i].mcuad;
                    }else{
                        summnorent += d[i].mcuad;
                    }
                }
                $scope.elProyecto.metros_rentable = summrent;
                if($scope.elProyecto.id != null && $scope.elProyecto.id != undefined){ $scope.updProyecto($scope.elProyecto, $scope.elProyecto.id); }
                d.push({id: 0, idproyecto: $scope.elProyecto.id, nombre: 'Total de área rentable', mcuad: summrent});
                d.push({id: 0, idproyecto: $scope.elProyecto.id, nombre: 'Total de área común', mcuad: summnorent});
                $scope.unidadesProy = d;
            });
        };

        $scope.getLstProyectos = function(){
            proyectoSrvc.lstProyecto().then(function(d){
                $scope.losProyectos = d;
            });
        };

        $scope.confGrpBtn = function(grp, i, u, d, a, e, c){
            var instruccion = "$scope." + grp + ".i = i; $scope." + grp + ".u = u; $scope." + grp + ".d = d; $scope." + grp + ".a = a; $scope." + grp + ".e = e; $scope." + grp + ".c = c;";
            eval(instruccion);
        };

        $scope.btnAP = function(){
            $scope.slproyecto = false;
            $scope.resetElProyecto();
            $scope.confGrpBtn('grpBtnProyecto', true, false, false, false, false, true);
        };

        $scope.btnCP = function(){
            if($scope.elProyecto.id > 0){
                $scope.getProyecto($scope.elProyecto.id);
            }else{
                $scope.resetElProyecto();
            }
            $scope.confGrpBtn('grpBtnProyecto', false, false, false, true, false, false);
            $scope.slproyecto = true;
        };

        $scope.btnCP2 = function(){
            if($scope.elProyecto.id > 0){
                $scope.confGrpBtn('grpBtnProyecto', false, false, true, true, true, false);
                $scope.slproyecto = true;
            }
        };

        $scope.btnEP = function(){
            $scope.slproyecto = false;
            $scope.confGrpBtn('grpBtnProyecto', false, true, true, false, false, true);
            goTop();
        };

        function procDataProyecto(d){
            d.id = parseInt(d.id);
            d.metros = parseFloat(parseFloat(d.metros).toFixed(4));
            d.idempresa = parseInt(d.idempresa);
            d.metros_rentable = parseFloat(parseFloat(d.metros_rentable).toFixed(4));
            d.subarrendado = parseInt(d.subarrendado);
            d.tipo_proyecto = parseInt(d.tipo_proyecto);
            d.fechaapertura = moment(d.fechaapertura).isValid() ? moment(d.fechaapertura).toDate() : undefined;
            d.multiempresa = parseInt(d.multiempresa);
            return d;
        }

        $scope.getProyecto = function(id){
            $scope.resetUnidad();
            $scope.losDetDocProy = [];
            $scope.losProyectoAdjuntos = [];
            $scope.losDetActivoProy = [];
            $scope.unidadesProy = [];
            $scope.servciosUnidad = [];
            $scope.servubas = [];

            proyectoSrvc.getProyecto(id).then(function(d){
                $scope.elProyecto = procDataProyecto(d);

                var tmp = $scope.elProyecto;
                $scope.proyectostr = tmp.nomproyecto + ' (' + tmp.referencia + ')';

                empresaSrvc.getEmpresa(d.idempresa).then(function(resEmpresa){
                    $scope.elProyecto.objEmpresa = resEmpresa[0];
                });

                tipoProyectoSrvc.getTipoProyecto(d.tipo_proyecto).then(function(resTipoProyecto){
                    $scope.elProyecto.objTipoProyecto = resTipoProyecto[0];
                });

                tipoDocProySrvc.lstTiposDocProy().then(function(d){
                    $scope.tiposDocProy = d;
                });


                tipoLocalSrvc.lstTiposLocal().then(function(d){ $scope.tiposlocales = d; });

                $scope.getLstProyectoAdjuntos();
                $scope.getLstDetActivoProy();
                $scope.getLstDetDocProy();
                $scope.getLstUnidadesProyecto($scope.elProyecto.id);
                //$scope.resetUnidad();

                $scope.confGrpBtn('grpBtnProyecto', false, false, true, true, true, false);
                $scope.slproyecto = true;

                moveToTab('divFrmServUnid', 'divFrmDataGen');
                moveToTab('divLstProyectos', 'divFrmProyectos');

                goTop();
            });
        };

        var test = true;

        $scope.printProyecto = function(idproyecto){
            $scope.params.idproyecto = idproyecto;
            moveToTab('divFrmProyectos', 'divProyPrint');
            goTop();
            jsReportSrvc.getPDFReport(test ? 'BJGZI7Bkg' : 'SJuVsQByg', $scope.params).then(function(pdf){ $scope.content = pdf; });
        };

        $scope.addProyecto = function(obj){
            obj.idempresa = $scope.elProyecto.objEmpresa.id;
            obj.tipo_proyecto = $scope.elProyecto.objTipoProyecto.id;
            obj.subarrendado = obj.subarrendado != null && obj.subarrendado != undefined ? obj.subarrendado : 0;
            obj.fechaaperturastr = obj.fechaapertura != null && obj.fechaapertura != undefined ? moment(obj.fechaapertura).format('YYYY-MM-DD') : '';
            obj.multiempresa = obj.multiempresa != null && obj.multiempresa != undefined ? obj.multiempresa : 0;
            proyectoSrvc.editRow(obj, 'c').then(function(d){
                $scope.getLstProyectos();
                $scope.getProyecto(parseInt(d.lastid));
            });

        };

        $scope.updProyecto = function(data, id){
            try{
                data.id = id;
                data.idempresa = $scope.elProyecto.objEmpresa.id;
                data.tipo_proyecto = $scope.elProyecto.objTipoProyecto.id;
                data.subarrendado = data.subarrendado != null && data.subarrendado != undefined ? data.subarrendado : 0;
                data.fechaaperturastr = data.fechaapertura != null && data.fechaapertura != undefined ? moment(data.fechaapertura).format('YYYY-MM-DD') : '';
                data.multiempresa = data.multiempresa != null && data.multiempresa != undefined ? data.multiempresa : 0;
                proyectoSrvc.editRow(data, 'u').then(function(){
                    $scope.getLstProyectos();
                    $scope.btnCP2();
                });
            }catch(err){
                //console.log('Error: ' + err);
                //console.log($scope.elProyecto);
            }
        };

        $scope.delProyecto = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar el proyecto? (Esto eliminará también adjuntos, activos y unidades relacionados al proyecto)',
                title: 'Eliminar proyecto ' + obj.nomproyecto, ok: 'Sí', cancel: 'No'}).then(function() {
                proyectoSrvc.editRow({id:obj.id}, 'd').then(function(){ $scope.getLstProyectos(); $scope.resetElProyecto(); $scope.btnCP(); });
            });
        };

        $scope.replica = function(obj){
            unidadSrvc.editRow(obj, 'dupproy').then(function(d){
                if(parseInt(d.lastid) > 0){
                    $scope.getLstUnidadesProyecto(parseInt($scope.elProyecto.id));
                    $scope.getUnidad(parseInt(d.lastid));
                    moveToTab('divFrmProyectos', 'divFrmUnidades');
                }else{
                    toaster.pop('error', 'Error', 'No se pudo replicar el proyecto como unidad', 'timeout:2000');
                }
            });
        };

        $scope.replicarAUnidad = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalTipoLocal.html',
                controller: 'ModalTipoLocal',
                resolve:{
                    proyecto: function(){
                        return $scope.elProyecto;
                    }
                }
            });
            modalInstance.result.then(function(obj){
                //console.log(obj);
                $scope.replica({idtipolocal: obj.idtipolocal, idproyecto: $scope.elProyecto.id, mcuad:obj.mcuad});
            }, function(){ return 0; });
        };

        $scope.asignarUsuarios = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalUsuario.html',
                controller: 'ModalUsuario',
                resolve:{
                    proyecto: function(){
                        return $scope.elProyecto;
                    }
                }
            });

            modalInstance.result.then(function(obj){
                console.log(obj);
            }, function(){ return 0; });
        };

        $scope.asignarServicios = function(){
            var modalInstance = $uibModal.open({
                animation: true,
                templateUrl: 'modalServicios.html',
                controller: 'ModalServicioCtrl',
                resolve:{ proyecto: function(){ return $scope.elProyecto; }
                }
            });

            modalInstance.result.then(function(obj){
                console.log(obj);
            }, function(){ return 0; });
        };

        $scope.addProyectoAdjunto = function(obj){
            $scope.submit();
            obj.idproyecto = $scope.elProyecto.id;
            obj.tipo_adjunto = $scope.elProyectoAdjunto.objTipoAdjunto.id;
            obj.ubicacion = "proyecto_adjunto/"+'PRY_'+$scope.elProyecto.id+'_'+$filter('textCleaner')($scope.file.name);
            obj.idtipodocproy = obj.objTipoDoc.id;
            obj.numero = obj.numero != null && obj.numero != undefined ? obj.numero : '';
            obj.fvencestr = obj.fvence != null && obj.fvence != undefined ? moment(obj.fvence).format('YYYY-MM-DD') : null;
            proyectoAdjuntoSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstProyectoAdjuntos();
                $scope.elProyectoAdjunto = {};
                $scope.file = null;
            });

        };

        $scope.updProyectoAdjunto = function(data, id){
            data.id = id;
            proyectoAdjuntoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstProyectoAdjuntos();
            });
        };

        $scope.delProyectoAdjunto = function(id){
            $confirm({text: '¿Seguro(a) de eliminar este adjunto? (Esto también eliminará físicamente el documento)',
                title: 'Eliminar adjunto', ok: 'Sí', cancel: 'No'}).then(function() {
                proyectoAdjuntoSrvc.editRow({id:id}, 'd').then(function(){ $scope.getLstProyectoAdjuntos(); });
            });
        };

        $scope.addProyectoActivo = function(obj){
            obj.idproyecto = $scope.elProyecto.id;
            obj.idactivo = obj.objActivo[0].id;
            proyectoActivoSrvc.editRow(obj, 'c').then(function(){
                $scope.getLstDetActivoProy();
                $scope.updProyecto($scope.elProyecto, $scope.elProyecto.id);
            });
        };

        $scope.updProyectoActivo = function(data, id){
            data.id = id;
            proyectoActivoSrvc.editRow(data, 'u').then(function(){
                $scope.getLstDetActivoProy();
            });
        };

        $scope.delProyectoActivo = function(qActivo){
            $confirm({text: '¿Seguro(a) de eliminar este activo del proyecto?',
                title: 'Eliminar activo ' + qActivo.finca + '-' + qActivo.folio + '-' + qActivo.libro, ok: 'Sí', cancel: 'No'}).then(function() {
                proyectoActivoSrvc.editRow({id:qActivo.id}, 'd').then(function(){
                    $scope.getLstDetActivoProy();
                    $scope.updProyecto($scope.elProyecto, $scope.elProyecto.id);
                });
            });
        };

        $scope.btnAPU = function(){
            $scope.slunidad = false;
            $scope.resetUnidad();
            $scope.confGrpBtn('grpBtnUnidad', true, false, false, false, false, true);
        };

        $scope.btnCPU = function(){
            if($scope.unidad.id > 0){
                $scope.getUnidad($scope.unidad.id);
            }else{
                $scope.resetUnidad();
            }
            $scope.confGrpBtn('grpBtnUnidad', false, false, false, true, false, false);
            $scope.slunidad = true;
        };

        $scope.btnEPU = function(){
            $scope.slunidad = false;
            $scope.confGrpBtn('grpBtnUnidad', false, true, true, false, false, true);
            goTop();
        };

        $scope.resetUnidad = function(){
            $scope.unidad = {};
        };

        function procDataUnidad(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idproyecto = parseInt(d[i].idproyecto);
                d[i].idtipolocal = parseInt(d[i].idtipolocal);
                d[i].mcuad = parseFloat(parseFloat(d[i].mcuad).toFixed(2));
                d[i].multiunidad = parseInt(d[i].multiunidad);
            }
            return d;
        }

        function procDataServUni(d){
            for(var i = 0; i < d.length; i++){
                d[i].id = parseInt(d[i].id);
                d[i].idunidad = parseInt(d[i].idunidad);
                d[i].idserviciobasico = parseInt(d[i].idserviciobasico);
                d[i].pagacliente = parseInt(d[i].pagacliente);
                d[i].preciomcubsug = parseFloat(parseFloat(d[i].preciomcubsug).toFixed(2));
                d[i].mcubsug = parseFloat(parseFloat(d[i].mcubsug).toFixed(2));
            }
            return d;
        }

        $scope.getServiciosDisponibles = function(idempresa){ servicioPropioSrvc.lstServiciosDisponibles(idempresa).then(function(d){ $scope.serviciosDisponibles = d; }); };

        $scope.getUnidad = function(idunidad){
            if(parseInt(idunidad) > 0){
                $scope.servciosUnidad = [];
                $scope.servubas = [];
                proyectoSrvc.getUnidad(idunidad).then(function(d){
                    $scope.unidad = procDataUnidad(d)[0];
                    $scope.unidad.objTipoLocal = $filter('getById')($scope.tiposlocales, $scope.unidad.idtipolocal);
                    proyectoSrvc.lstServiciosUnidad(idunidad).then(function(d){ $scope.servciosUnidad = procDataServUni(d); });
                    proyectoSrvc.lstServBasicosUnidad(idunidad).then(function(d){ $scope.servubas = d; });
                    $scope.getServiciosDisponibles(0);
                    $scope.confGrpBtn('grpBtnUnidad', false, false, true, true, true, false);
                    $scope.slunidad = true;
                    $scope.showForm.unidad = true;
                    goTop();
                });
            }
        };

        $scope.addUnidadProyecto = function(obj){
            obj.idproyecto = $scope.elProyecto.id;
            obj.idtipolocal = obj.objTipoLocal.id;
            obj.observaciones = obj.observaciones != null && obj.observaciones != undefined ? obj.observaciones : '';
            obj.multiunidad = obj.multiunidad != null && obj.multiunidad != undefined ? obj.multiunidad : 0;
            proyectoSrvc.editRow(obj, 'cup').then(function(d){
                $scope.getLstUnidadesProyecto($scope.elProyecto.id);
                $scope.getUnidad(parseInt(d.lastid));
            });
        };

        $scope.updUnidad = function(obj){
            obj.idproyecto = $scope.elProyecto.id;
            obj.idtipolocal = obj.objTipoLocal.id;
            obj.observaciones = obj.observaciones != null && obj.observaciones != undefined ? obj.observaciones : '';
            obj.multiunidad = obj.multiunidad != null && obj.multiunidad != undefined ? obj.multiunidad : 0;
            proyectoSrvc.editRow(obj, 'uup').then(function(){
                $scope.getLstUnidadesProyecto($scope.elProyecto.id);
                $scope.getUnidad(obj.id);
            });
        };

        $scope.delUnidadProyecto = function(obj){
            $confirm({text: '¿Seguro(a) de eliminar la unidad ' + obj.nombre + ' del proyecto?',
                title: 'Eliminar unidad ', ok: 'Sí', cancel: 'No'}).then(function() {
                proyectoSrvc.editRow({id: obj.id}, 'dup').then(function(){
                    $scope.getLstUnidadesProyecto(obj.idproyecto);
                    $scope.resetUnidad();
                    $scope.btnCPU();
                });
            });
        };

        $scope.btnAPS = function(){
            $scope.slservbas = false;
            $scope.servuni = {};
            $scope.confGrpBtn('grpBtnServBas', true, false, false, false, false, true);
        };

        $scope.btnCPS = function(){
            $scope.servuni = {};
            $scope.confGrpBtn('grpBtnServBas', false, false, false, true, false, false);
            $scope.slservbas = true;
        };

        $scope.btnEPS = function(){
            $scope.slservbas = false;
            $scope.confGrpBtn('grpBtnServBas', false, true, true, false, false, true);
            goTop();
        };

        $scope.addServUni = function(obj){
            obj.idunidad = $scope.unidad.id;
            obj.idserviciobasico = parseInt(obj.objServBasico.id);
            proyectoSrvc.editRow(obj, 'csu').then(function(){
                proyectoSrvc.lstServiciosUnidad(obj.idunidad).then(function(d){ $scope.servciosUnidad = procDataServUni(d); });
                proyectoSrvc.lstServBasicosUnidad(obj.idunidad).then(function(d){
                    for(var i = 0; i < d.length ; i++){
                        d[i].preciomcubsug = parseFloat(parseFloat(d[i].preciomcubsug).toFixed(2));
                        d[i].mcubsug = parseFloat(parseFloat(d[i].mcubsug).toFixed(2));
                    }
                    $scope.servubas = d;
                });
                $scope.getServiciosDisponibles(0);
                $scope.btnCPS();
            });
        };

        $scope.delServUni = function(obj){
            $confirm({text: '¿Seguro(a) de quitar este servicio de esta unidad?',
                title: 'Quitar servicio de la unidad', ok: 'Sí', cancel: 'No'}).then(function() {
                proyectoSrvc.editRow(obj, 'dsu').then(function(){
                    $scope.getServiciosDisponibles(0);
                    proyectoSrvc.lstServiciosUnidad(obj.idunidad).then(function(d){
                        $scope.servciosUnidad = procDataServUni(d);
                        $scope.servuni = {}; $scope.btnCPS();
                    });
                    proyectoSrvc.lstServBasicosUnidad(obj.idunidad).then(function(d){
                        for(var i = 0; i < d.length ; i++){
                            d[i].preciomcubsug = parseFloat(parseFloat(d[i].preciomcubsug).toFixed(2));
                            d[i].mcubsug = parseFloat(parseFloat(d[i].mcubsug).toFixed(2));
                        }
                        $scope.servubas = d;
                    });
                });
            });
        };

        $scope.updCantBase = function(obj){
            obj.idproyecto = $scope.elProyecto.id;
            obj.idusuario = $scope.usrdata.uid;
            proyectoSrvc.editRow(obj, 'ucb').then(function(d){
                $scope.getServiciosDisponibles(0);
                proyectoSrvc.lstServiciosUnidad(obj.idunidad).then(function(d){
                    $scope.servciosUnidad = procDataServUni(d);
                    $scope.servuni = {}; $scope.btnCPS();
                });
                proyectoSrvc.lstServBasicosUnidad(obj.idunidad).then(function(d){
                    for(var i = 0; i < d.length ; i++){
                        d[i].preciomcubsug = parseFloat(parseFloat(d[i].preciomcubsug).toFixed(2));
                        d[i].mcubsug = parseFloat(parseFloat(d[i].mcubsug).toFixed(2));
                    }
                    $scope.servubas = d;
                });

                if(+d.cambio == 1){ toaster.pop('success', 'Cambio en precio y/o cantidad base', 'Se actualizó el precio y la cantidad base para esta unidad en este proyecto.', 'timeout:2000'); }
            });
        };

        $scope.getLstProyectos();
        $scope.getlstEmpresas();
        $scope.getlstTipoProyecto();
        $scope.getlstTipoAdjunto();
        $scope.getLstActivos();
    }]);
    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    proyectoctrl.controller('ModalTipoLocal', ['$scope', '$uibModalInstance', 'proyecto', 'tipoLocalSrvc', 'proyectoSrvc', '$filter', 'toaster', function($scope, $uibModalInstance, proyecto, tipoLocalSrvc, proyectoSrvc, $filter, toaster){
        $scope.proyecto = proyecto;
        $scope.tiposlocales = [];
        $scope.param = {objTipoUnidad: null, idtipounidad:0, mcuad:0.0};

        tipoLocalSrvc.lstTiposLocal().then(function(d){
            $scope.tiposlocales = d;
            proyectoSrvc.getTipoUnidad($scope.proyecto.tipo_proyecto).then(function(d){
                if(parseInt(d.tipounidad) > 0){
                    $scope.param.objTipoUnidad = [$filter('getById')($scope.tiposlocales, parseInt(d.tipounidad))];
                }
            });
        });

        $scope.ok = function () {
            if($scope.param.objTipoUnidad !== undefined && $scope.param.objTipoUnidad !== null && $scope.param.mcuad !== undefined && $scope.param.mcuad !== null){
                $uibModalInstance.close({idtipolocal: $scope.param.objTipoUnidad[0].id, mcuad: $scope.param.mcuad});
            }else{
                toaster.pop('error', 'Error', 'El tipo de la unidad y los metros cuadrados son requeridos.', 'timeout:3000');
            }
        };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

    }]);
    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    proyectoctrl.controller('ModalUsuario', ['$scope', '$uibModalInstance', 'proyecto', 'proyectoSrvc', '$filter', 'toaster', '$confirm', function($scope, $uibModalInstance, proyecto, proyectoSrvc, $filter, toaster, $confirm){
        $scope.proyecto = proyecto;
        $scope.usuarios = [];
        $scope.usuario = { idproyecto: +$scope.proyecto.id, idusuario: '0' };
        $scope.asignados = [];

        $scope.loadData = function(){
            proyectoSrvc.getUsuariosDisponibles(+$scope.proyecto.id).then(function(d){ $scope.usuarios = d; });
            proyectoSrvc.getProyectoUsuarios(+$scope.proyecto.id).then(function(d){ $scope.asignados = d; });
        };

        //$scope.ok = function () { $uibModalInstance.close(); };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.addUsrProy = function(obj){
            proyectoSrvc.editRow(obj, 'cpu').then(function(){
                $scope.loadData();
            });
        };

        $scope.delUsrProy = function(obj){
            $confirm({text: '¿Seguro(a) de desasignar a ' + obj.usuario + ' del proyecto ' + $scope.proyecto.nomproyecto + ' ?', title: 'Desasignar usuario', ok: 'Sí', cancel: 'No'}).then(function() {
                proyectoSrvc.editRow({id:obj.id}, 'dpu').then(function(){ $scope.loadData(); });
            });
        };

        $scope.loadData();

    }]);
    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
    proyectoctrl.controller('ModalServicioCtrl', ['$scope', '$uibModalInstance', 'proyecto', 'proyectoSrvc', '$filter', 'toaster', '$confirm', 'servicioBasicoSrvc', function($scope, $uibModalInstance, proyecto, proyectoSrvc, $filter, toaster, $confirm, servicioBasicoSrvc){
        $scope.proyecto = proyecto;
        $scope.servicios = [];
        $scope.servicio = { idproyecto: +$scope.proyecto.id, idserviciobasico: undefined };
        $scope.servproy = [];

        $scope.loadData = function(){
            proyectoSrvc.lstServBasicosProy(+$scope.proyecto.id).then(function(d){ $scope.servproy = d; });
            servicioBasicoSrvc.lstServiciosPadre().then(function(d){ $scope.servicios = d; });
        };

        //$scope.ok = function () { $uibModalInstance.close(); };

        $scope.cancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.addSrvProy = function(obj){
            proyectoSrvc.editRow(obj, 'csp').then(function(){
                $scope.loadData();
                $scope.servicio = { idproyecto: +$scope.proyecto.id, idserviciobasico: undefined };
            });
        };

        $scope.delSrvProy = function(obj){
            $confirm({text: '¿Seguro(a) de desasignar el servicio ' + obj.numidentificacion + ' del proyecto ' + $scope.proyecto.nomproyecto + ' ?',
                title: 'Desasignar servicio', ok: 'Sí', cancel: 'No'}).then(function() {
                proyectoSrvc.editRow({id:obj.id, idserviciobasico: obj.idserviciobasico}, 'dsp').then(function(){
                    $scope.loadData();
                    $scope.servicio = { idproyecto: +$scope.proyecto.id, idserviciobasico: undefined };
                });
            });
        };

        $scope.loadData();

    }]);
}());
