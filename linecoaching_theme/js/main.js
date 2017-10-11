jQuery(document).ready(function() {
    jQuery(".mobile-main-menu").find("a").click(function () {


        if(jQuery(this).hasClass("highlighted") || !jQuery(this).hasClass("has-submenu")) {
            jQuery(".openMenu").click();
            jQuery("#main-header").find("button.navbar-toggle").removeClass("openMenu");
            jQuery(".region-primary-menu").css("display","none");
        }

    })

    /* Fonctionnalité permettant d'empêcher le scroll de la page en mode tablette */
     var navbar_toggle=1;
       jQuery(".navbar-toggle").click(function () {

               if(navbar_toggle>0) jQuery("body").css("overflow","hidden");
               else jQuery("body").css("overflow","auto");
               navbar_toggle*=-1;
         })

         jQuery(".showcase-wrap").click(function () {
             if(jQuery(".openMenu").length) {
                 //alert(jQuery("#main-header").find("button.navbar-toggle").hasClass("openMenu"));
                 jQuery(".openMenu").click();
                 jQuery("#main-header").find("button.navbar-toggle").removeClass("openMenu");
            }

             if(jQuery(".region-primary-menu").css("display")=="block") jQuery(".region-primary-menu").css("display","none");
             jQuery("body").css("overflow","auto");
         })

         jQuery("#wrapper").click(function () {
             if(jQuery(".openMenu").length) {
                 jQuery(".openMenu").click();
                 jQuery("#main-header").find("button.navbar-toggle").removeClass("openMenu");
            }

             if(jQuery(".region-primary-menu").css("display")=="block") jQuery(".region-primary-menu").css("display","none");
             jQuery("body").css("overflow","auto");
         })

     jQuery(".keywords").flexslider({
             animation:"slider",
             animationLoop: false,
             slideShow: false,
             initDelay: 100000000,


             controlNav: false
     })

    //stickyNavBar menu on scroll
     jQuery('#main-header').stickyNavbar({startAt:250});

     //visible floating block:form bilan gratuit
     var maxWidthMobile = 768;
     var win       =  jQuery(window);
     var formBilan =  jQuery(".sidebar .col-md-3 form#bilan-gratuit-form");
     var options   = {
         bottoming: true,
         offset_top: 120,
         parent: "#wrapper"
     };
     if (win.width() > maxWidthMobile) {
         formBilan.stick_in_parent(options);
     }
     win.resize(function () {
         if (win.width() > maxWidthMobile) {
             formBilan.stick_in_parent(options);
         } else {
             formBilan.trigger("sticky_kit:detach");
         }
     });


});

// >
// @mbe : manage login throught ws
(function ($) {
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function mtcLoginDisplay(context, disableAnimate) {
        var $dialog  = $('#MtcLoginModal').attr('align', 'center').find(".modal-dialog"),
        offset       = ($(window).height() - $dialog.height()) / 2,
        bottomMargin = parseInt($dialog.css('marginBottom'), 10);
        // Make sure you don't hide the top part of the modal w/ a negative margin if it's longer than the screen height, and keep the margin equal to the bottom margin of the modal
        if(offset < bottomMargin) offset = bottomMargin;
        if (arguments.length==1 || !disableAnimate) {
            $dialog.animate({ 'marginTop': offset }, 600);
        }
        else {
            $dialog.css("margin-top", offset);
        }
    }
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function mtcLoginHide() {
        $('body').removeClass('modal-open');
        $('#MtcLoginModal .modal-dialog').attr('class', 'modal-dialog fadeIn  animated');
        $('#MtcLoginModal .modal-body > div').attr('align', 'center').html(
            '<img src="/themes/custom/linecoaching_theme/images/ajax-loader.gif" border="0"/>'
        );
    }
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function mtcLoginLaunch() {
        $.ajax({
            type : 'GET',
            url  : '/user/ws/login',
            data : { date : $('.mtc-login').attr("data-date") },
            success: function(json) {
                $('#MtcLoginModal .modal-body > div').fadeOut(function() {
                    $(this).html(json.data).removeAttr('align').fadeIn(function() {
                        $('#MtcLoginModal #id_login').focus();
                    });
                });
            },
            error: function(json) {
                console.error(json);
            }
        });
    }
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $(document).ready(function() {
        $('#MtcLoginModal').appendTo("body");
        $(document).on('hide.bs.modal' , '#MtcLoginModal', mtcLoginHide );
        $(document).on('shown.bs.modal', '#MtcLoginModal', mtcLoginDisplay);
        $(document).on('show.bs.modal' , '#MtcLoginModal', mtcLoginLaunch);
        $(window).on("resize", function () {
            $('#MtcLoginModal:visible').each(function() { mtcLoginDisplay(null, true) });
        });
        if (window.SHOW_MTC_LOGIN === true) {
            $('#MtcLoginModal').modal('show');
        }
    });
}(jQuery));
// <

jQuery(window).bind('scroll',function(e){
  scrollTop();
});

jQuery(document).ready(function () {
  scrollTop();
});

function scrollTop() {
  if ( jQuery(window).scrollTop() >= 150 ) {
    jQuery('#topnav').fadeIn(1000);
  }
  else {
    jQuery('#topnav').fadeOut(1000);
  }
}

(function ($, Drupal) {

    Drupal.behaviors.massageWasSend = {
        attach: function(context, settings) {

            function setCookie(cname, cvalue, exdays) {
                var d = new Date();
                d.setTime(d.getTime() + (exdays*24*60*60*1000));
                var expires = "expires="+ d.toUTCString();
                document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
            }

            function getCookie(cname) {
                var name = cname + "=";
                var decodedCookie = decodeURIComponent(document.cookie);
                var ca = decodedCookie.split(';');
                for(var i = 0; i <ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length);
                    }
                }
                return "";
            }

            function showSentMessage() {
                if (getCookie("Drupal.visitor.new_message_sent") == '1') {
                    var dialog = BootstrapDialog.show({
                        // title: Drupal.t('Information'),
                        message: Drupal.t('Message was send'),
                        buttons: [{
                            label: Drupal.t('Ok'),
                            action: function (dialogItself) {
                                dialogItself.close();
                            }
                        }]
                    });

                    var text = dialog.getModalBody().find('.bootstrap-dialog-body').text();
                    dialog.getModalBody().hide();
                    dialog.getModalHeader().hide();
                    dialog.getModalFooter().css('border-top', 'none');
                    dialog.getModalFooter().find('button').css('margin', '0');
                    var footer = dialog.getModalFooter().find('.bootstrap-dialog-footer-buttons').prepend('<span style="float: left; line-height: 2.5;">' + text + '</span>');

                    setCookie('Drupal.visitor.new_message_sent', 0, -1);
                }
            }

            setTimeout(showSentMessage, 1500);

        }
    };

    Drupal.behaviors.bannerCookiesBottom = {
        attach: function(context, settings) {
            var cookie = $.cookie('banner_cookies_bottom');
            if (cookie != '1') {
                $("#banner-bottom-link").click(function (e) {
                    e.preventDefault();
                    $(this).closest("div.banner-bottom").hide(350);
                    $.cookie('banner_cookies_bottom', '1');
                });
            }
            else {
                $("#banner-bottom-link").closest("div.banner-bottom").hide();
            }
        }
    };

  Drupal.behaviors.messageTableFunctioanlity = {
    attach: function(context, settings) {
      $(".clickable-row td:nth-child(1n+3)").click(function() {
        var url = $(this).closest('.clickable-row').attr("data-href");
        window.location = url;
      });
      $('.all_massages').click(function (e) {
        e.preventDefault();
      });
    }
  };
  
  Drupal.behaviors.messageTableBulkOperation = {
    attach: function(context, settings) {
      // checkboxes in messages
      var checker = $('.private-messsage input[name="all_massages_val"]');
      var checker_top = $('.private-messsage input[name="all_massages"]');
      checker.once('allMessageCheck').change(function (e) {
          var hidden  = $("input[type=hidden][name=selected_message]");
        var values = hidden.val();
        if(this.checked) {
          var formPost = $(this).val();
          if(values) {
            hidden.val(values + ',' + formPost);
          }
          else {
            hidden.val(formPost);
          }
        }
        else {
          var deletePost = $(this).val();
          var value_arr = values.split(',');
          value_arr = jQuery.grep(value_arr, function (value) {
            return value != deletePost;
          });
          var new_values = value_arr.join(',');
          hidden.val(new_values);
        }
       
      });
  
      //global checkboxes button
      var check = $('.private-messsage input[name="all_massages_val"]');
      var hidden  = $("input[type=hidden][name=selected_message]");
      var checker_top = $('form input[name="all_massages"]');
      checker_top.once('allCheck').change(function (e) {
        if(this.checked) {
          var new_values = [];
          hidden.val(new_values);
          check.each(function (e) {
            var values = hidden.val();
            $(this).prop('checked', true);
            console.log($(this));
            if($(this).val()) {
              hidden.val(values + ',' + $(this).val());
            }
            else {
              hidden.val($(this).val());
            }
          });
        }
        else {
          check.each(function (e) {
            $(this).prop('checked', false);
          });
          var new_values = [];
          hidden.val(new_values);
        }
      });
      
      // autosubmit
      var select = $('select[name="operation"]');
      select.once('SelectChange').change(function (e) {
        $(this).closest("form").trigger("submit");
      });
  
    }
  };
  
  Drupal.behaviors.confirmationInfo = {
    attach: function (context, settings) {
      var button = $(".subscription-newsletter-form input[type='submit']");
      button.mousedown(function (e) {
        setTimeout(function (e) {
          $(".newsletter .info").hide();
        }, 3000);
      });
    }
  };
  
  Drupal.behaviors.subscriptionNewsletterForm = {
    attach: function(context, settings) {
      var submit = $('form[id*="subscription-newsletter-form"] input[type="submit"]');
      var input = $('form[id*="subscription-newsletter-form"] input[id*="edit-email-address-newsletter"]');
      submit.once('NewsletterForm').mousedown(function (e) {
        var elemet = $('.messages.messages--error');
        if (elemet.length > 0) {elemet.hide("slow");}
        input.val('');
      });

      $(document).click(function(event) {
        var elemet = $('form[class*="subscription-newsletter-form"] .messages.messages--error');
        if ($(event.target).closest("form[class*='subscription-newsletter-form'] .messages.messages--error").length) return;
        elemet.hide("slow");
        event.stopPropagation();
      });

      $(document).click(function(event) {
        var el = $('.newsletter-a7_manager .ui-dialog-titlebar-close');
        if ($(event.target).closest(".newsletter-a7_manager").length) return;
        el.click();
        event.stopPropagation();
      });
    }
  };
  
  Drupal.behaviors.confirmationInfo = {
    attach: function (context, settings) {
      var button = $(".subscription-newsletter-form input[type='submit']");
      button.mousedown(function (e) {
        setTimeout(function (e) {
          $(".newsletter .info").hide();
        }, 3000);
      });
    }
  };

  Drupal.behaviors.iframeVideoResize = {
      attach: function(context, settings) {
        // Resize iframe for width modal window
        $(context).ajaxSuccess(function (event, xhr, settings) {
          var popup = $('.homepage-video');
          if(popup.length > 0) {
            var prop_coof = 1.777777;
            var height_coof = 0.8;
            var popup_width = popup.width();
            var popup_height = popup.height();
            var iframe = $('.homepage-video iframe');
            var iframe_height = popup_width / prop_coof;

            if (($(window).height() * height_coof * prop_coof) > $(window).width()) {
              iframe.css('height', $(window).width() / prop_coof);
              iframe.css('width', $(window).width() / prop_coof * prop_coof);
            } else {
              iframe.css('height', $(window).height() * height_coof);
              iframe.css('width', $(window).height()  * height_coof * prop_coof);
            }
           
            setTimeout(function (e) {
              //http://help.vzaar.com/article/181-javascript-api-2-0
              var vzp = new vzPlayer("vzvd-11140242");
              vzp.play2();
            }, 2000)
            
          }
        });

        $(document).click(function(event) {
          var el = $('.homepage-video .ui-dialog-titlebar-close');
          if ($(event.target).closest(".homepage-video").length) return;
          el.click();
          event.stopPropagation();
        });
      }
  };

  Drupal.behaviors.subscriptionNewsletterForm = {
    attach: function(context, settings) {
      var submit = $('form[id*="subscription-newsletter-form"] input[type="submit"]');
      var input = $('form[id*="subscription-newsletter-form"] input[id*="edit-email-address-newsletter"]');
      submit.once('NewsletterForm').mousedown(function (e) {
        var elemet = $('.messages.messages--error');
        if (elemet.length > 0) {elemet.hide("slow");}
        input.val('');
      });
      
      $(document).click(function(event) {
        var elemet = $('form[class*="subscription-newsletter-form"] .messages.messages--error');
        if ($(event.target).closest("form[class*='subscription-newsletter-form'] .messages.messages--error").length) return;
        elemet.hide("slow");
        event.stopPropagation();
      });
      
      $(document).click(function(event) {
        var el = $('.newsletter-a7_manager .ui-dialog-titlebar-close');
        if ($(event.target).closest(".newsletter-a7_manager").length) return;
        el.click();
        event.stopPropagation();
      });
    }
  };
  
  Drupal.behaviors.homepageVideoPopup = {
    attach: function (context, settings) {
      $(document).click(function(event) {
        var el = $('.homepage-video .ui-dialog-titlebar-close');
        if ($(event.target).closest(".homepage-video").length) return;
        el.click();
        event.stopPropagation();
      });
    }
  };
  
  Drupal.behaviors.searchPageMag = {
    attach: function (context, settings) {
      var el = $('#advanced-search-field .menu-search-box input[type="checkbox"]');
      var magbox_rubric = $('#advanced-search-field .menu-search-box .filter-theme > div:nth-child(n+2)');
      var inputs = $('#advanced-search-field .menu-search-box .filter-theme input[type="checkbox"], #advanced-search-field .menu-search-box.msb_type input[type="checkbox"]');
      var magbox = $('#advanced-search-field .menu-search-box.msb_type');
      if (el.length > 0) {
        el.once('magCheck').change(function (e) {
          if ($(this).is(':checked') && $(this).val() == '267') {
            inputs.each(function (e) {
              $(this).prop('checked', true);
            });
            magbox.fadeIn();
            magbox_rubric.fadeIn();
          }
          else if(!$(this).is(':checked') && $(this).val() == '267') {
            inputs.each(function (e) {
              $(this).prop('checked', false);
            });
            magbox.fadeOut();
            magbox_rubric.fadeOut();
          }
          if($('#advanced-search-field').find('input[type=checkbox]:checked').length > 0){
            $('button.dublicate').fadeIn();
          }
          else {
            $('button.dublicate').fadeOut();
          }
        });
      }
    }
  };
  
  Drupal.behaviors.privateMessageApp = {
    attach: function (context, settings) {
      var el = $('span.modified-mode');
      var input = $('input[name="all_massages_val"]');
      var newMessage = $('a.new-message');
      var form = $('form[class*="lc-message-bulk-operation"]');
      var form_select = form.find('select[name="operation"]');
      var functional = $('.modified-functionality > span');
      el.once('MessageApp').click(function (e) {
        var self = $(this);
        self.toggleClass('expand');
        input.each(function (e) {
          if (self.hasClass('expand')) {
            $(this).css('opacity', 1);
            newMessage.fadeOut();
          }
          else {
            $(this).css('opacity', 0);
            newMessage.fadeIn();
          }
        });
      });
      functional.once('MessageFoo').click(function (e) {
        var attr = $(this).attr('data-value');
        form_select.val(attr);
        form.trigger("submit");
      });
    }
  };

})(jQuery, Drupal);
