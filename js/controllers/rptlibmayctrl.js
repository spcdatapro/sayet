(function(){

    var rptlibmayctrl = angular.module('cpm.rptlibmayctrl', []);

    rptlibmayctrl.controller('rptLibroMayorCtrl', ['$scope', 'rptLibroMayorSrvc', 'empresaSrvc', 'authSrvc', 'jsReportSrvc', '$sce', 'cuentacSrvc', function($scope, rptLibroMayorSrvc, empresaSrvc, authSrvc, jsReportSrvc, $sce, cuentacSrvc){

        $scope.params = {
            del: moment().startOf('month').toDate(), al: moment().endOf('month').toDate(), idempresa: 0, codigo: undefined, constproc: 0, filtro: '1', codigoal: undefined,
            cuentasSel: undefined, vercierre: 1, nofolio: undefined, noheader: 0
        };
        $scope.libromayor = [];
        $scope.content = '';
        $scope.cuentas = [];
        $scope.empresa = {};

        authSrvc.getSession().then(function(usrLogged){
            if(parseInt(usrLogged.workingon) > 0){
                $scope.params.idempresa = parseInt(usrLogged.workingon);
                cuentacSrvc.lstCuentasC($scope.params.idempresa).then(function(d){ $scope.cuentas = d; });
                empresaSrvc.getEmpresa($scope.params.idempresa).then(function(d){ $scope.empresa = d[0]; });
            }
        });

        function setCodigos(ctas){
            var lista = '';
            ctas.forEach(function(cta){
                if(lista !== ''){ lista += ','; }
                lista += "'" + cta.trim() + "'";
            });
            return lista;
        }

        $scope.resetParams = function(){
            $scope.params.cuentasSel = undefined;
            $scope.params.codigo = undefined;
            $scope.params.codigoal = undefined;
        };

        var test = false;
        $scope.getLibroMayor = function(){
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.vercierre = $scope.params.vercierre != null && $scope.params.vercierre !== undefined ? $scope.params.vercierre : 0;
            $scope.params.nofolio = $scope.params.nofolio != null && $scope.params.nofolio !== undefined ? $scope.params.nofolio : '';
            $scope.params.noheader = $scope.params.noheader != null && $scope.params.noheader !== undefined ? $scope.params.noheader : 0;

            if(+$scope.params.filtro === 1){
                $scope.params.codigo = $scope.params.cuentasSel ? setCodigos($scope.params.cuentasSel) : '';
                $scope.params.codigoal = '';
            }else{
                $scope.params.codigo = $scope.params.codigo != null && $scope.params.codigo !== undefined ? ("'" + $scope.params.codigo.trim() + "'") : '';
                $scope.params.codigoal = $scope.params.codigoal != null && $scope.params.codigoal !== undefined ? ("'" + $scope.params.codigoal.trim() + "'") : '';
            }

            //console.log($scope.params); return;

            if(+$scope.params.constproc === 0){
                jsReportSrvc.getPDFReport(test ? '' : 'ryzIcT87Z', $scope.params).then(function(pdf){ $scope.content = pdf; });
            }else{
                jsReportSrvc.getPDFReport(test ? '' : 'rJsjLgzyG', $scope.params).then(function(pdf){ $scope.content = pdf; });
            }
        };

        $scope.getLibroMayorXLSX = function(){
            //$scope.params.constproc = 0;
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.vercierre = $scope.params.vercierre != null && $scope.params.vercierre !== undefined ? $scope.params.vercierre : 0;
            $scope.params.nofolio = $scope.params.nofolio != null && $scope.params.nofolio !== undefined ? $scope.params.nofolio : '';
            $scope.params.noheader = $scope.params.noheader != null && $scope.params.noheader !== undefined ? $scope.params.noheader : 0;

            if(+$scope.params.filtro === 1){
                $scope.params.codigo = $scope.params.cuentasSel ? setCodigos($scope.params.cuentasSel) : '';
                $scope.params.codigoal = '';
            }else{
                $scope.params.codigo = $scope.params.codigo != null && $scope.params.codigo !== undefined ? ("'" + $scope.params.codigo.trim() + "'") : '';
                $scope.params.codigoal = $scope.params.codigoal != null && $scope.params.codigoal !== undefined ? ("'" + $scope.params.codigoal.trim() + "'") : '';
            }

            if(+$scope.params.constproc === 0){
                jsReportSrvc.getReport(test ? 'S1OS4Lhdf' : 'HJwukPndf', $scope.params).then(function(result){
                    //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                    var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.del).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY');
                    saveAs(file, 'DMG_' + nombre + '.xlsx');
                });
            }else{
                jsReportSrvc.getReport(test ? 'rkv1lRrFM' : 'SkHHrArKG', $scope.params).then(function(result){
                    //var file = new Blob([result.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                    var file = new Blob([result.data], {type: 'application/vnd.ms-excel'});
                    var nombre = $scope.empresa.abreviatura + '_' + moment($scope.params.del).format('DDMMYYYY') + '_' + moment($scope.params.al).format('DDMMYYYY');
                    saveAs(file, 'INT_' + nombre + '.xlsx');
                });
            }


        };

        $scope.getLibroMayorXLSX2 = function(btn){
            $('#btnDMGExcel').button('loading')
            $scope.params.fdelstr = moment($scope.params.del).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.al).format('YYYY-MM-DD');
            $scope.params.vercierre = $scope.params.vercierre != null && $scope.params.vercierre !== undefined ? $scope.params.vercierre : 0;
            $scope.params.nofolio = $scope.params.nofolio != null && $scope.params.nofolio !== undefined ? $scope.params.nofolio : '';
            $scope.params.noheader = $scope.params.noheader != null && $scope.params.noheader !== undefined ? $scope.params.noheader : 0;

            if(+$scope.params.filtro === 1){
                $scope.params.codigo = $scope.params.cuentasSel ? setCodigos($scope.params.cuentasSel) : '';
                $scope.params.codigoal = '';
            }else{
                $scope.params.codigo = $scope.params.codigo != null && $scope.params.codigo !== undefined ? ("'" + $scope.params.codigo.trim() + "'") : '';
                $scope.params.codigoal = $scope.params.codigoal != null && $scope.params.codigoal !== undefined ? ("'" + $scope.params.codigoal.trim() + "'") : '';
            }

            var url = '/sayet/php/rptlibmay.php/rptlibmay'

            $.post(url, $scope.params, function(data){
                var tab_text='<table>'
                tab_text = tab_text+"<tr><td>Código</td><td colspan='2'>Cuenta</td><td>Anterior</td><td>Debe</td><td>Haber</td><td>Saldo</td></tr>"
                tab_text = tab_text+"<tr><td>Fecha</td><td>Referencia</td><td colspan='5'>Transacción</td></tr>";
                
                data.datos.forEach(function(e){
                    if (e.dlm.length) {
                        tab_text = tab_text+'<tr><td>'+e.codigo+'</td>'
                        tab_text = tab_text+'<td colspan="2">'+e.nombrecta+'</td>'
                        tab_text = tab_text+'<td>'+e.anterior+'</td>'
                        tab_text = tab_text+'<td>'+e.debe+'</td>'
                        tab_text = tab_text+'<td>'+e.haber+'</td>'
                        tab_text = tab_text+'<td>'+e.actual+'</td></tr>'

                        e.dlm.forEach(function(d){
                            tab_text = tab_text+'<tr><td>'+d.fecha+'</td>'
                            tab_text = tab_text+'<td colspan="2">'+d.referencia+'</td>'
                            tab_text = tab_text+'<td>'+(d.transaccion?d.transaccion:'')+'</td>'
                            tab_text = tab_text+'<td>'+d.debe+'</td>'
                            tab_text = tab_text+'<td>'+d.haber+'</td>'
                            tab_text = tab_text+'<td></td></tr>'
                        })
                    }
                })

                tab_text = tab_text+'</table>'

                var a = document.createElement('a')
                document.body.appendChild(a)
                a.href = 'data:application/vnd.oasis.opendocument.spreadsheet,' + encodeURIComponent(tab_text)
                a.download = 'DMG.xls'
                a.click()

                $('#btnDMGExcel').button('reset')
            })
        }

    }]);

}());