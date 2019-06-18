(function(){

    var socketiosrvc = angular.module('cpm.socketiosrvc', []);

    socketiosrvc.factory('socketIOSrvc', ['$rootScope', ($rootScope) => {
		const urlSocketServer = 'http://localhost:3520';
		
		const socket = io.connect(urlSocketServer);
		return {
			on: (eventName, callback) => {
				socket.on(eventName, function () {  
					var args = arguments;
					$rootScope.$apply(function () {
					callback.apply(socket, args);
					});
				});
			},
			emit: (eventName, data, callback) => {
				socket.emit(eventName, data, function () {
					var args = arguments;
					$rootScope.$apply(function () {
					if (callback) {
						callback.apply(socket, args);
					}
					});
				});
			}
		};

    }]);

}());
