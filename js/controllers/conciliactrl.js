(function () {

    var conciliactrl = angular.module('cpm.conciliactrl', ['cpm.tranbacsrvc']);

    conciliactrl.controller('conciliaCtrl', ['$scope', 'tranBancSrvc', 'authSrvc', 'bancoSrvc', 'empresaSrvc', 'DTOptionsBuilder', '$interval', 'toaster', 'tipoMovTranBanSrvc', '$uibModal', 'jsReportSrvc', '$window',
        function ($scope, tranBancSrvc, authSrvc, bancoSrvc, empresaSrvc, DTOptionsBuilder, $interval, toaster, tipoMovTranBanSrvc, $uibModal, jsReportSrvc, $window) {

            $scope.laEmpresa = {};
            $scope.lasEmpresas = [];
            $scope.losBancos = [];
            $scope.elBanco = {};
            $scope.lasTran = [];
            $scope.afecha = moment().toDate();
            $scope.fechaconcilia = moment().toDate();
            $scope.qver = 0;
            $scope.progress = 0;
            $scope.todas = [];
            $scope.trans = [];
            // automatica
            $scope.bancos = [];
            $scope.selTodos = 0;
            $scope.tipotrans = [];
            $scope.params = { tipos: [], ver: '1' }
            var idusuario = undefined;
            // para paginar
            $scope.currentPage = 1; // Página actual
            $scope.itemsPerPage = 20; // Número de elementos por página
            $scope.lookFor = ''; // Busqueda

            // para pginas de resultados
            $scope.$watch('trans.length', function () {
                $scope.totalPages = Math.ceil($scope.trans.length / $scope.itemsPerPage);
            });

            $scope.$watch('lookFor', function () {
                // Calcula el número total de páginas después del filtro
                $scope.totalPages = Math.ceil($scope.filteredEmpleados().length / $scope.itemsPerPage);
                // Reinicia la página actual a la primera después del filtro
                $scope.currentPage = 1;
            });

            $scope.setPage = function (page) {
                if (page >= 1 && page <= $scope.totalPages) {
                    $scope.currentPage = page;
                }
            };

            $scope.paginatedEmpleados = function () {
                var filtered = $scope.filteredEmpleados();
                var start = ($scope.currentPage - 1) * $scope.itemsPerPage;
                return filtered.slice(start, start + $scope.itemsPerPage);
            };

            $scope.filteredEmpleados = function () {
                return $scope.trans.filter(function (e) {
                    return !$scope.lookFor || Object.keys(e).some(function (key) {
                        return String(e[key]).toLowerCase().includes($scope.lookFor.toLowerCase());
                    });
                });
            };

            $scope.totalPages = Math.ceil($scope.trans.length / $scope.itemsPerPage);
            // fin de paginas

            bancoSrvc.lstBancosActivos(4).then(d => {
                // solo mostrar los dos bancos que estan utilizando mt940
                let idbancos = ['3', '33'];
                $scope.bancos = d.filter(banco => idbancos.includes(banco.id));
            })

            tipoMovTranBanSrvc.lstTiposMovTB().then(function (d) { $scope.tipotrans = d; });

            authSrvc.getSession().then(function (usrLogged) {
                idusuario = usrLogged.uid;
                // traer empresas permitidas por el usuario
                empresaSrvc.lstEmpresas().then(function (d) {
                    empresaSrvc.getEmpresaUsuario(usrLogged.uid).then(function (autorizado) {
                        let idempresas = [];
                        autorizado.forEach(aut => {
                            idempresas.push(aut.id);
                        });
                        $scope.lasEmpresas = idempresas.length > 0 ? d.filter(empresa => idempresas.includes(empresa.id)) : d;
                    });
                });
                if (parseInt(usrLogged.workingon) > 0) {
                    empresaSrvc.getEmpresa(parseInt(usrLogged.workingon)).then(function (d) {
                        $scope.laEmpresa = d[0];
                        $scope.getLstBancos();
                    });
                }
            });

            $scope.getLstBancos = function () {
                bancoSrvc.lstBancosActivos(parseInt($scope.laEmpresa.id)).then(function (r) {
                    $scope.losBancos = r;
                    $scope.lasTran = [];
                });
            };

            $scope.getLstTran = function () {
                if ($scope.elBanco !== null && $scope.elBanco !== undefined) {
                    $scope.qver = $scope.qver != null && $scope.qver != undefined ? $scope.qver : 0;
                    tranBancSrvc.lstAConciliar($scope.elBanco.id, (moment($scope.afecha).isValid ? moment($scope.afecha).format('YYYY-MM-DD') : '0'), $scope.qver).then(function (d) {
                        $scope.lasTran = d;
                        for (var i = 0; i < $scope.lasTran.length; i++) {
                            $scope.lasTran[i].fecha = moment($scope.lasTran[i].fecha).toDate();
                            $scope.lasTran[i].numero = parseInt($scope.lasTran[i].numero);
                            $scope.lasTran[i].monto = parseFloat($scope.lasTran[i].monto);
                            $scope.lasTran[i].operado = parseInt($scope.lasTran[i].operado) === 1;
                        }
                    });
                }
            };

            $scope.updOperado = (data, id, index) => {
                // console.log(data, index); 
                // console.log($scope.lasTran[index]); return;
                data.operado = data.operado ? 0 : 1;
                //console.log(data);
                tranBancSrvc.editRow({ id: data.id, operado: data.operado, foperado: moment($scope.fechaconcilia).format('YYYY-MM-DD') }, 'o').then(() => {
                    $scope.lasTran.splice(index, 1);
                });
            };

            // automatico

            $scope.buscarDocumentos = () => {
                $scope.cargando = true;
                $interval(() => {
                    if ($scope.progress < 99) {
                        $scope.progress += 1;
                    } else {
                        return;
                    }
                }, 500)

                tranBancSrvc.conciliacionAutomatica().then(d => {
                    $scope.progress = d.porcentaje;
                    $scope.cargando = false;
                    toaster.pop({ type: d.tipo, title: 'Conciliación automática', body: d.mensaje, timeout: 10000 })

                    if (d.caso === 2) {
                        automaticaDos();
                    } else if (d.caso === 3) {
                        $scope.todas = d.trans;
                        $scope.trans = d.trans;
                    }
                });
            }

            $scope.aceptarAutomatico = () => {
                let aconciliar = [];

                $scope.trans.forEach(tran => {
                    console.log(tran);
                    if (tran.conciliar == 1 && tran.tipo_transaccion == 'C') {
                        aconciliar.push(tran);
                    }
                });

                console.log(aconciliar);

                tranBancSrvc.editRow(aconciliar, 'ca').then(d => {
                    toaster.pop({ type: 'success', title: 'Conciliación automática', body: 'Documentos conciliados exitosamente', timeout: 10000 });
                    $scope.buscarDocumentos();
                });
            }

            $scope.cancelarAutomatico = () => {
                $scope.trans = [];
                toaster.pop({ type: 'info', title: 'Conciliación automática', body: 'Conciliación automática cancelada', timeout: 10000 });
            }

            $scope.imprimir = (params) => {
                params.delstr = params.del ? moment(params.del).format('YYYY-MM-DD') : 0;
                params.alstr = params.al ? moment(params.al).format('YYYY-MM-DD') : 0;
                params.idusuario = idusuario;
                jsReportSrvc.getPDFReport('Hysnac8leg', params).then(function (pdf) { $window.open(pdf); });
            }

            $scope.$watch('selTodos', function (newVal, oldVal) {
                if (newVal != oldVal) {
                    $scope.trans.forEach(f => f.conciliar = +newVal);
                }
            });

            $scope.$watch('params.idbanco', function (newVal) {
                if (newVal > 0) {
                    idbanco = newVal.toString();
                    if ($scope.params.tipos.length > 0) {
                        $scope.trans = $scope.todas.filter(tran => tran.idbanco === idbanco && $scope.params.tipos.includes(tran.idtipotrans));
                    } else {
                        $scope.trans = $scope.todas.filter(tran => tran.idbanco.toString() === idbanco);
                    }
                } else {
                    if ($scope.params.tipos.length > 0) {
                        $scope.trans = $scope.todas.filter(tran => $scope.params.tipos.includes(tran.idtipotrans));
                    } else {
                        $scope.trans = $scope.todas;
                    }
                }
                $scope.currentPage = 1;
            })

            $scope.$watch('params.tipos', function (newVal) {
                if (newVal.length > 0) {
                    if ($scope.params.idbanco > 0) {
                        idbanco = $scope.params.idbanco.toString();
                        $scope.trans = $scope.todas.filter(tran => tran.idbanco === idbanco && $scope.params.tipos.includes(tran.idtipotrans));
                    } else {
                        $scope.trans = $scope.todas.filter(tran => $scope.params.tipos.includes(tran.idtipotrans));
                    }
                } else {
                    if ($scope.params.idbanco > 0) {
                        idbanco = $scope.params.idbanco.toString();
                        $scope.trans = $scope.todas.filter(tran => tran.idbanco.toString() === idbanco);
                    } else {
                        $scope.trans = $scope.todas;
                    }
                }
                $scope.currentPage = 1;
            })

            $scope.getLstTranAutomatica = (ver, del, al) => {
                if ($scope.todas.length > 0) {
                    delstr = del ? moment(del).format('YYYY-MM-DD') : 0;
                    alstr = al ? moment(al).format('YYYY-MM-DD') : 0;
                    tranBancSrvc.datosAutomatica(ver, delstr, alstr).then(d => {
                        $scope.todas = d;
                        $scope.tras = d;
                        $scope.params = { tipos: [], ver: ver, del: del, al: al }
                        if (ver == '1') {
                            tipoMovTranBanSrvc.lstTiposMovTB().then(function (d) { $scope.tipotrans = d; });
                        } else {
                            $scope.tipotrans = [{ id: '1', abreviadesc: '(C) Créditos', abreviatura: 'C' }, { id: '2', abreviadesc: '(D) Débitos', abreviatura: 'D' }];
                        }
                    })
                    $scope.currentPage = 1;
                }
            }

                $scope.verPosiblesDoc = (idbanco, numero, monto, tipo) => {
                    $uibModal.open({
                        animation: true,
                        templateUrl: 'modalPosibles.html',
                        controller: 'ModalPosiblesCtrl',
                        resolve: {
                            documento: () => ({ idbanco, numero, monto, tipo })
                        }
                    }).result.then(function (data) {
                        $scope.cargando = true;
                        console.log(data);
                        empServicios.nuevoEmpleado(data).then(d => {
                            toaster.pop({ type: d.tipo, title: 'Nuevo empleado', body: d.mensaje, timeout: 10000 });
                            $scope.cargando = false;
                            $scope.buscar(1);
                            $scope.getEmpleado(d.id);
                        })
                    })
                }

            }]);

    //------------------------------------------------------------------------------------------------------------------------------------------------//
    conciliactrl.controller('ModalPosiblesCtrl', ['$scope', '$uibModalInstance', 'documento', 'tranBancSrvc', function ($scope, $uibModalInstance, documento, tranBancSrvc) {
        console.log(documento);

        tranBancSrvc.lstPosiblesDoc(documento.idbanco, documento.numero, documento.monto, documento.tipo).then(d => {
            $scope.documentos = d;
            console.log(d);
        });

        $scope.ok = () => { $uibModalInstance.dismiss('cancel') }

        $scope.cancel = () => { $uibModalInstance.dismiss('cancel') }

    }])

}());
