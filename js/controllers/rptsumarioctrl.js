(function () {

    var rptsumarioctrl = angular.module('cpm.rptsumarioctrl', []);

    rptsumarioctrl.controller('rptSumarioCtrl', ['$scope', 'jsReportSrvc', 'monedaSrvc', 'bancoSrvc', '$sce', '$http', '$window', '$q', '$filter', 'Upload', function ($scope, jsReportSrvc, monedaSrvc, bancoSrvc, $sce, $http, $window, $q, $filter, Upload) {

        $scope.params = { fecha: moment().toDate(), idmoneda: '1', solomov: 1, tipo: '1' };
        $scope.content = '';
        // variable para mostrar que esta cargando
        $scope.estaGenerando = false;
        // para mostrar reporte en pantalla
        $scope.content = `${window.location.origin}/sayet/blank.html`;

        var test = false;
        $scope.getRptSumario = function () {
            $scope.estaGenerando = true;
            $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');
            jsReportSrvc.getPDFReport(test ? 'H13Q-o81-' : 'By_inM6jp', $scope.params).then(function (pdf) { 
                $scope.content = pdf; 
                $scope.estaGenerando = false;
            });
        };

        $scope.resetParams = function () { $scope.params = { fecha: moment().toDate(), idmoneda: '1', solomov: 1, tipo: '1' }; };

        $scope.getSumarioGeneral = function () {
            $scope.estaGenerando = true;
            $scope.params.fechastr = moment($scope.params.fecha).format('YYYY-MM-DD');
            $scope.params.fdelstr = moment($scope.params.fecha).format('YYYY-MM-DD');
            $scope.params.falstr = moment($scope.params.fecha).format('YYYY-MM-DD');
            $scope.params.idmoneda = $scope.params.idmoneda != null && $scope.params.idmoneda != undefined ? $scope.params.idmoneda : '1';
            $scope.params.solomov = $scope.params.solomov != null && $scope.params.solomov != undefined ? $scope.params.solomov : 0;
            $scope.params.idbanco = 0;
            $scope.params.resumen = 1;

            bancoSrvc.getCuentasSumario(+$scope.params.idmoneda, $scope.params.fdelstr, $scope.params.falstr, $scope.params.tipo).then(function (d) {
                var url = window.location.origin + ':5489/api/report', props = {}, file, formData = new FormData();

                props = { 'template': { 'shortid': 'By_inM6jp' }, 'data': $scope.params };
                $http.post(url, props, { responseType: 'arraybuffer' }).then(function (response) {
                    file = new Blob([response.data], { type: 'application/pdf' });
                    formData.append('sumario', file);

                    var promises = d.map(function (cuenta) {
                        props = { 'template': { 'shortid': 'SJB5nj-QW' }, 'data': { fdelstr: $scope.params.fdelstr, falstr: $scope.params.falstr, resumen: 1, idbanco: +cuenta.id } };
                        return $http.post(url, props, { responseType: 'arraybuffer' });
                    });

                    $q.all(promises).then(function (respuestas) {
                        for (var i = 0; i < d.length; i++) {
                            file = new Blob([respuestas[i].data], { type: 'application/pdf' });
                            formData.append('ResumenBco' + $filter('padNumber')(+d[i].id, 2), file);
                        }
                        $scope.estaGenerando = false;
                        $.ajax({
                            url: "php/rptsumariogroup.php",
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function () { $scope.estaGenerando = false; },
                            error: function () { console.log("Se produjo un error al generar el sumario y su detalle..."); }
                        }).done(function () {
                            var urlpdf = window.location.origin + '/sayet/php/pdfgenerator/SumarioDetalle.pdf';
                            $window.open(urlpdf);
                        });
                    });
                });
            });
        };

    }]);
}());
