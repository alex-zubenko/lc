// home carrrousel plugin from d6
(function($) {

$.fn.carrousel = function(wrapper_width,item_width,car_nb_visible_items,car_step){
  var car_items_wrapper = $(this).find('ul.tabs');
  var car_nb_items = $(car_items_wrapper).find('li').length;
  var car_ul_width = item_width * car_nb_items;
  $(car_items_wrapper).css({'width': car_ul_width+'px'});
  var car_next_button = $(this).find('.nextTabs');
  var car_prev_button = $(this).find('.prevTabs');
  var car_prev_count = parseInt($(car_prev_button).attr('rel'));
  var car_next_count = parseInt($(car_next_button).attr('rel'));
  car_next_button.click(function(){
    if(car_nb_items > car_nb_visible_items){
      if(car_nb_items > car_next_count){
        if ($('body').width() < 700) {
          car_step = 1;
        };
        car_next_count = car_next_count + car_step;
        car_prev_count = car_prev_count + car_step;
        $(this).attr('rel',car_next_count);
        $(car_prev_button).attr('rel',car_prev_count);
        $(car_items_wrapper).animate({
          marginLeft: (wrapper_width - item_width * car_next_count)+'px'
        });
      }
    } else {
      return false;
    }
  });
  car_prev_button.click(function(){
    if(car_nb_items > car_nb_visible_items){
      if(car_prev_count > 1){
        if ($('body').width() < 700) {
          car_step = 1;
        };
        car_next_count = car_next_count - car_step;
        car_prev_count = car_prev_count - car_step;
        $(this).attr('rel',car_prev_count);
        $(car_next_button).attr('rel',car_next_count);
        $(car_items_wrapper).animate({
          marginLeft: (wrapper_width - item_width * car_next_count)+'px'
        });
      }
    } else {
      return false;
    }
  });
};
}(jQuery));

//add popiin
function print_popin(elem, width, height, prop)
{
    var popin = jQuery("#popin_conteneur");
    elem.appendTo(popin);
    popin.css("position","absolute");

    var p_height = ((jQuery(window).height() - height ) / 2+jQuery(window).scrollTop());
    if(p_height<40)
    {
        p_height=40;
    }

    popin.css("top", p_height + "px");
    popin.css("left", ( jQuery(window).width() - width ) / 2+jQuery(window).scrollLeft() + "px");

    if(prop!=null)
    {
        for(p in prop)
        {
            popin.attr(p, prop[p]);
        }
    }

    popin.show();
}

//Save defi
function save_defi(actionDefi, idDefi) {
    //callback
    console.log('callback to do');
    /*
    var params = {
        'action' : 'save',
        'ressource' :'defis',
        'body':'<defi id="' + idDefi + '">' + actionDefi + '</defi>',
        'callback':function(msg) {
            var xml = $(msg.responseXML);
            if (isCallbackFailed(xml, 'reload')) {
                return;
            }

            if(actionDefi=='start' && $("div.widget_defi").length==0){
                $(location).attr('href', window.location.href);
            }else{
                if($("div.popin .img-continue").length>0){
                    $("div.popin .img-continue, div.popin .img-close").click(function() {
                        $(location).attr('href', window.location.href);
                    });
                }
            }


        }

    }
    buildANDsendXML(params);*/
}
(function($) {

    $(document).ready(function() {
        //started defis
        $.each($(".widget_checkradio"), function(key, value) {
          var radios = $(".checkradio_item", $(this));
          radios.click(function(){
            radios.removeClass("w_on");
            $(this).toggleClass("w_on");
            $("#defi_state_"+$(this).parent(".mcw").attr("id").replace("mcw_","")).val($(this).attr("id").replace("checkradio_item_", ""));
          });
        });
        $(".img-continue").hover(
          function(){
            $(this).attr("src", "/themes/custom/linecoaching_theme/images/prog-images/next_hover.png");
          },
          function(){
            $(this).attr("src", "/themes/custom/linecoaching_theme/images/prog-images/next.png");
          }
        );
        $(".img-close").hover(
          function(){
            $(this).attr("src", "/themes/custom/linecoaching_theme/images/prog-images/closeRollover.png");
          },
          function(){
            $(this).attr("src", "/themes/custom/linecoaching_theme/images/prog-images/close.png");
          }
        );
        $(".popinnew .closed_button, .popinnew .img-continue").click(function(){
          window.location.reload();
        });
        //new  defis
        $("#defi-carrousel").carrousel(480,80,6,6);
        $('.defi').click(function(e) {
            $('.popincarrousel').hide();
            $('.defi').removeClass('active');
            $(this).addClass('active');
            current_popin = $('#popin_' + $(this).attr('id').replace('defi_', ''));

            current_popin.show();
            var general = current_popin.find(".general");
            //alert(general.width());
            var width = general.width();
            var height = general.height();
            current_popin.hide();

            print_popin(current_popin, width, height, null);
            current_popin.show();
          });

          $('.popincarrousel .closed_button, .img-cancel').click(function() {
            $(this).parents('.popincarrousel').hide();
            current_popin.appendTo(parent);
          });
          $('.popincarrousel .img-valide').click(function() {
              var id = $('.defi.active').attr('id').replace('defi_', '');
              save_defi('start', id);
              $(".bouton").find('*').unbind();
          });

          //init cookies from d6 prog
          $.get( "prog/comportement-alimentaire/sport/index", function( data ) {
              console.log(data);
            });
      });
})(jQuery);
