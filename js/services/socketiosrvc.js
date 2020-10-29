(function(){

    var socketiosrvc = angular.module('cpm.socketiosrvc', []);

    socketiosrvc.factory('socketIOSrvc', ['$rootScope', ($rootScope) => {
		const urlSocketServer = `${window.location.origin}:3520`;
		let socket;
		
		if (urlSocketServer.indexOf('localhost') < 0) {
			socket = io.connect(urlSocketServer);
		}
		
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
