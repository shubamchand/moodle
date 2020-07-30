require(['jquery', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax'], 
	function($, ModalFactory, ModalEvents, Fragment, Ajax) {


	var contextid = customrestrict.contextid;

	var users = customrestrict.users;

	var cmid, type;

	$('.ra-popup').on('click', function() {
		var target = $(this);
		var cmid = target.data('cmid');
		var type = target.data('type');

		ModalFactory.create({
			type: ModalFactory.types.SAVE_CANCEL,
			title: 'Lock / Unlock user course access',
			body: getBody(cmid, type),
			large: true
		}).then(function(modal) {

			modal.show();

			modal.getRoot().on(ModalEvents.hidden, function() {
				modal.destroy();
			});

			modal.getRoot().on( ModalEvents.save, function(e) { 				
				e.preventDefault();
				submitForm(modal);
			} );

			modal.getRoot().delegate('form', 'submit', function(e) {
				e.preventDefault();	
				submitFormData(modal) 			
			});
			// formsubmit(modal);
		})
	})

	function getBody(cmid, type) {
		var args = { cmid: cmid, type: type };
		var params = { formdata: JSON.stringify(args) };
		return Fragment.loadFragment('theme_ausinet', 'get_restrictuser_form', contextid, params);
	}

	function submitForm(modal) {
		modal.getRoot().find('form').submit();
	}

	function handleSuccessResponse(modal) {
		modal.hide();
		window.location.reload();
	}

	function handleFailedResponse() {

	}

	function submitFormData(modal) {
		
		var formData = modal.getRoot().find('form').serialize();
		Ajax.call([{
			methodname: 'theme_ausinet_restrict_users',
			args: {contextid: contextid, formdata: formData},
			done: function(respnse) { 
				modal.hide();
				window.location.reload();
			},
			fail: handleFailedResponse()
		}]);
	}

})