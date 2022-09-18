jQuery(document).ready(function() {
  jQuery('.sp').first().addClass('active');
  jQuery('.sp').hide();
  jQuery('.active').show();

  if (jQuery('.es-send-email-screen').hasClass('active')) {
    jQuery('#button-send').addClass('es-send-email');
  }

  jQuery(document).on('click', '.es-send-email', function() {
     if(jQuery("#es-send-email-form")[0].checkValidity()) {
      jQuery('.es-send-email-screen .es-loader').show();
      var emails = [];
        jQuery(".es_email").each(function() {
          if ((jQuery.trim(jQuery(this).val()).length > 0)) {
            emails.push(jQuery(this).val());
          }
        });
        var params = {
          type: 'POST',
          url: ajaxurl,
          data: {
            action: 'send_test_email',
            emails: emails
          },
          dataType: 'json',
          success: function(data, status, xhr) {
            jQuery('.es-send-email-screen .es-loader').find('img').hide();
            jQuery('.active').fadeOut('fast').removeClass('active');
            jQuery('#button-send').hide();
            if (data.status == 'SUCCESS') {
              jQuery('.sp.es-success').addClass('active').fadeIn('slow');
            } else if(data.status == 'ERROR'){
              jQuery('.sp.es-error').find('.es-email-sending-error').html('<i class="dashicons dashicons-es dashicons-no-alt" style="color: #e66060"></i>'+data.message);
              jQuery('.sp.es-error').addClass('active').fadeIn('slow');
              jQuery('#button-send').hide();
            }
          },
          error: function(data, status, xhr) {}
        };

        jQuery.ajax(params);
     }else{
      jQuery(".es_email").addClass('error')
      jQuery("#es-send-email-form")[0].reportValidity();
     }

  });
  
  jQuery(document).on('click', '.es-receive-success-btn', function() {
     jQuery('.active').fadeOut('fast').removeClass('active');
     jQuery('.sp.es-receive-success').fadeIn('slow').addClass('active');

  });

  jQuery(document).on('click', '.es-receive-error-btn', function() {
     jQuery('.active').fadeOut('fast').removeClass('active');
     jQuery('.sp.es-receive-error').fadeIn('slow').addClass('active');
  });

});