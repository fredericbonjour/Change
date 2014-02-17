(function () {

	"use strict";

	function rbsOrderOrderEditorShippingModes ( REST, $filter, i18n, NotificationCenter, ErrorFormatter, Events)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Order/Order/shippingModes.twig',
			scope : {
				'addressDocuments' : "=",
				'shippingModes' : "=",
				'lines' : "=",
				'orderId' : "@"
			},

			link : function (scope, element, attrs)
			{
				scope.showShippingAddressUI = false;
				scope.shippingAddress = {};
				scope.editedShippingMode = {};
				scope.shippingDetails = {};
				scope.addressDefined = {};

				scope.getLinesNumbers = function(shippingMode) {
					var matchingLines, lineNumbers= [];
					angular.forEach(shippingMode.lineKeys, function (lineKey) {
						matchingLines = $filter('filter')(scope.lines, {key: lineKey});
						angular.forEach(matchingLines, function (line) {
							lineNumbers.push(line.index + 1);
						});
					});
					return lineNumbers.join(', ');
				};

				scope.refreshShippingModes = function()
				{
					if (!angular.isArray(scope.shippingModes)){
						scope.shippingModes = [];
					}

					var shippingModes = scope.shippingModes;
					angular.forEach(shippingModes, function (shippingMode) {
						shippingMode.lineKeys = [];
						//set the status to 'unavailable' because we can be clear with shipment status
						if (scope.shippingDetails[shippingMode.id]){
							scope.shippingDetails[shippingMode.id].status = 'unavailable';
						}
					});

					angular.forEach(scope.lines, function (line) {
						var shippingModeId = line.options.shippingMode;
						if (shippingModeId){
							var matchingShippingModes = $filter('filter')(shippingModes, {id: shippingModeId});
							if (matchingShippingModes.length){
								angular.forEach(matchingShippingModes, function (shippingMode) {
									shippingMode.lineKeys.push(line.key);
								});
							}
							else{
								shippingModes.push({id: shippingModeId, lineKeys: [line.key]});
							}
						}

						angular.forEach(shippingModes, function (shippingMode) {
							if (!shippingMode.hasOwnProperty('title')) {
								var detail = scope.shippingDetails[shippingMode.id];
								shippingMode.title = detail ? detail.label : '[' + shippingMode.id + ']';
							}
						});
					});
				};

				scope.editShippingAddress = function (shippingId) {
					angular.forEach(scope.shippingModes, function (shipping){
						if(shipping.id == shippingId){
							if(!angular.isObject(shipping.address))
							{
								shipping.address = {};
							}
							scope.editedShippingMode = shipping;
						}
					});
					scope.showShippingAddressUI = true;
				};

				// This watches for modifications in the address doc in order to fill the address form
				scope.$watch('shippingModes', function (shippingModes, old) {
					if (angular.isObject(shippingModes) && !angular.isObject(old)){

						angular.forEach(scope.shippingModes, function (shipping){
							if (shipping.id){
								scope.addressDefined[shipping.id] = angular.isObject(shipping.address);
							}
						});
					}
				}, true);

				// This refreshes shippingModesObject to be synchronized with parent scope in order editor
				scope.$watchCollection('shippingModes', function (shippingModes, old) {
					scope.shippingModes = shippingModes;
					if (angular.isObject(scope.editedShippingMode) && scope.editedShippingMode.id){
						scope.editShippingAddress(scope.editedShippingMode.id);
					}
				});

				// This refreshes shippingModesObject to be synchronized with order editor
				scope.$on('shippingModesUpdated', function (event) {
					scope.refreshShippingModes();
				});

				scope.$on(Events.EditorPostSave, function (){
					if(angular.isObject(scope.shippingModes)){
						angular.forEach(scope.shippingModes, function (shipping){
							if (shipping.id){
								scope.addressDefined[shipping.id] = angular.isObject(shipping.address);
							}
						});
					}
				});

				scope.$watch('orderId', function(orderId, old) {
					var shippingDetails = {};
					if (orderId > 0) {
						REST.collection('Rbs_Shipping_Mode').then(function(response) {
							angular.forEach(response.resources, function (shippingDoc) {
								REST.call(REST.getBaseUrl('rbs/order/orderRemainder'), {
									orderId: scope.orderId,
									shippingModeId: shippingDoc.id
								}).then(function (data){
									shippingDoc.status = data.status;
									shippingDetails[shippingDoc.id] = shippingDoc;
								}, function (error){
									NotificationCenter.error(i18n.trans('m.rbs.order.adminjs.shipment_invalid_request_remainder | ucf'),
										ErrorFormatter.format(error));
									console.error(error);
								});
							});
						});
					}
					scope.shippingDetails = shippingDetails;
				});
			}
		};
	}

	rbsOrderOrderEditorShippingModes.$inject = [ 'RbsChange.REST', '$filter', 'RbsChange.i18n',
		'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter', 'RbsChange.Events' ];
	angular.module('RbsChange').directive('rbsOrderShippingModes', rbsOrderOrderEditorShippingModes);
})();