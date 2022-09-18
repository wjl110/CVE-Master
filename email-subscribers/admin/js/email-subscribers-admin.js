(function ($) {

	$(document).ready(
		function () {
			$(document).on('change', '.es_visible', function () {
				if ($('.es_visible:checked').length >= 1) {
					$('.es_required').prop('disabled', false);
					$('.es_name_label').removeAttr('disabled');
				} else {
					$('.es_required').prop('disabled', true);
					$('.es_name_label').attr('disabled', 'disabled');
				}
			});
			$('.es_visible').change();

			$('#es-settings-tabs').tabs().addClass('ui-tabs-vertical ui-helper-clearfix');

			var defaultHeight = $('div#es-settings-tabs div#menu-tab-content div#tabs-general').height() + 30;
			$('div#es-settings-tabs div#menu-tab-listing ul').height(defaultHeight);

			// Set Tab Height
			$('.ui-tabs-anchor').click(function () {
				var tab = $(this).attr('href');
				$('#email_tabs_form').attr('action', tab);
				var tabHight = $('div#es-settings-tabs div#menu-tab-content div' + tab).height() + 30;
				$('div#es-settings-tabs div#menu-tab-listing ul').height(tabHight);
			});

			if (jQuery('.statusesselect').length) {
				var statusselect = jQuery('.statusesselect')[0].outerHTML;
			}

			if (jQuery('.groupsselect').length) {
				var groupselect = jQuery('.groupsselect')[0].outerHTML;
			}

			jQuery(".es-audience-view .bulkactions #bulk-action-selector-top").after(statusselect);
			jQuery(".es-audience-view .bulkactions #bulk-action-selector-top").after(groupselect);

			//jQuery(".es-audience-view .bulkactions #bulk-action-selector-bottom").after(statusselect);
			// jQuery(".es-audience-view .bulkactions #bulk-action-selector-bottom").after(groupselect);

			jQuery("#bulk-action-selector-top").change(function () {
				if (jQuery('option:selected', this).attr('value') == 'bulk_list_update' || jQuery('option:selected', this).attr('value') == 'bulk_list_add') {
					jQuery('.groupsselect').eq(1).show();
					jQuery('.statusesselect').eq(1).hide();
				} else if (jQuery('option:selected', this).attr('value') == 'bulk_status_update') {
					jQuery('.statusesselect').eq(1).show();
					jQuery('.groupsselect').eq(1).hide();
				} else {
					jQuery('.statusesselect').hide();
					jQuery('.groupsselect').hide();
				}
			});

			jQuery('.es-audience-view .tablenav.bottom #bulk-action-selector-bottom').hide();
			jQuery('.es-audience-view .tablenav.bottom #doaction2').hide();
			jQuery(document).on('change', "#base_template_id", function () {
				var img = jQuery('option:selected', this).data('img')
				jQuery('.es-templ-img').html(img);
			});

			//send test emails
			$(document).on('click', '#es-send-test', function (e) {
				e.preventDefault();
				var test_email = $('#es-test-email').val();
				var params = {};
				params.es_test_email = test_email;
				params.action = 'es_send_test_email';
				if (test_email) {
					$('#es-send-test').next('#spinner-image').show();
					jQuery.ajax({
						method: 'POST',
						url: ajaxurl,
						data: params,
						dataType: 'json',
						success: function (response) {
							if (response && typeof response.status !== 'undefined' && response.status == "SUCCESS") {
								$('#es-send-test').parent().find('.helper').html('<span style="color:green">' + response.message + '</span>');
							} else {
								$('#es-send-test').parent().find('.helper').html('<span style="color:#e66060">' + response.message + '</span>');
							}

							$('#es-send-test').next('#spinner-image').hide();
						},

						error: function (err) {
							$('#es-send-test').next('#spinner-image').hide();
						}
					});
				} else {
					confirm('Add test email ');
				}

			});

			//klawoo form submit
			jQuery("form[name=klawoo_subscribe]").submit(function (e) {
				e.preventDefault();
				var form = e.target;
				jQuery(form).find('#klawoo_response').html('');
				jQuery(form).find('#klawoo_response').show();

				params = jQuery(form).serializeArray();
				params.push({name: 'action', value: 'es_klawoo_subscribe'});

				jQuery.ajax({
					method: 'POST',
					type: 'text',
					url: ajaxurl,
					async: false,
					data: params,
					success: function (response) {
						if (response != '') {
							var parser = new DOMParser()
							var el = parser.parseFromString(response, "text/xml");
							var msg = el.childNodes[0].firstChild.nextElementSibling.innerHTML;
							if (jQuery(form).hasClass('es-onboarding')) {
								location.reload();
							} else {
								jQuery(form).find('#klawoo_response').html();

								jQuery('.es-emm-optin #name').val('');
								jQuery('.es-emm-optin #email').val('');
								jQuery('.es-emm-optin #es-gdpr-agree').attr('checked', false);
								setTimeout(function () {
									jQuery(form).find('#klawoo_response').hide('slow');
								}, 2000);
							}


						} else {
							jQuery('#klawoo_response').html('error!');
						}
					}
				});

			});


			// Select List ID for Export
			var _href = $('#ig_es_export_link_select_list').attr("href");
			$('#ig_es_export_list_dropdown').change(function () {
				var selected_list_id = $(this).val();

				$('#ig_es_export_link_select_list').attr("href", _href + '&list_id=' + selected_list_id);

				// Update total count in lists
				var params = {
					action: 'count_contacts_by_list',
					list_id: selected_list_id
				};

				$.ajax({
					method: 'POST',
					url: ajaxurl,
					async: false,
					data: params,
					success: function (response) {
						if (response != '') {
							response = JSON.parse(response);
							$('#ig_es_export_select_list .ig_es_total_contacts').text(response.total);
						}
					}
				});

			});

			// Broadcast Setttings
			// Get count by list
			$('#ig_es_campaign_submit_button').attr("disabled", true);
			$('#ig_es_broadcast_list_ids').change(function(){
				var selected_list_id = $(this).val();

				// Update total count in lists
				var params = {
					action: 'count_contacts_by_list',
					list_id: selected_list_id,
					status: 'subscribed'
				};

				$.ajax({
					method: 'POST',
					url: ajaxurl,
					async: false,
					data: params,
					success: function (response) {
						if (response !== '') {
							response = JSON.parse(response);
							if(response.hasOwnProperty('total')) {
								var total = response.total;
								$('#ig_es_total_contacts').text(response.total);
								if(total == 0 ) {
									$('#ig_es_campaign_submit_button').attr("disabled", true);
								} else {
									$('#ig_es_campaign_submit_button').attr("disabled", false);
								}
							}
						}
					}
				});
			});

			//post notification category select
			jQuery(document).on('change', '.es-note-category-parent', function(){
				var val = jQuery('.es-note-category-parent:checked').val();
				if('{a}All{a}' === val){
					jQuery('input[name="es_note_cat[]"]').not('.es_custom_post_type').closest('tr').hide();
				}else{
					jQuery('input[name="es_note_cat[]"]').not('.es_custom_post_type').closest('tr').show();
				}

			});
			jQuery('.es-note-category-parent').trigger('change');


			//es mailer settings
			jQuery( document ).on( 'change', '.es_mailer' , function(e) {
				var val =  jQuery('.es_mailer:checked').val();
				jQuery('[name*="ig_es_mailer_settings"], .es_sub_headline').not('.es_mailer').hide();
				jQuery(document).find('.'+val).show();
			});
			jQuery('.es_mailer').trigger('change');
		});





})(jQuery);

function checkDelete() {
	return confirm('Are you sure?');
}
