
/* dot nav */
jQuery(window).bind('scroll',function(e){
  redrawDotNav();
  hideShowBilanGratuit();
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

function redrawDotNav(){

      var topNavHeight = 50;
      var numDivs = jQuery('section.hp-nav').length;
      jQuery('#dotNav li a').removeClass('active').parent('li').removeClass('active');
      jQuery('section.hp-nav').each(function(i,item){
      var ele = jQuery(item), nextTop;
          if (typeof ele.next().offset() != "undefined") {
            nextTop = ele.next().offset().top;
          } else {
            nextTop = jQuery(document).height();
          }
          if (ele.offset() !== null) {
            thisTop = ele.offset().top - ((nextTop - ele.offset().top) / numDivs);
          }else {
            thisTop = 0;
          }
          var docTop = jQuery(document).scrollTop()+topNavHeight;
          if(docTop >= thisTop && (docTop < nextTop)){
              jQuery('#dotNav li').removeClass('active');
              jQuery('#dotNav li').eq(i).addClass('active');
          }
    });
}

/* get clicks working */
jQuery('#dotNav li').click(function(){

    var id = jQuery(this).find('a').attr("href"),
      posi,
      ele,
      padding = jQuery('.navbar-fixed-top').height();

    ele = jQuery(id);
    posi = (jQuery(ele).offset()||0).top - padding;

    jQuery('html, body').animate({scrollTop:posi}, 'slow');

    return false;
});

/* end dot nav */
//homepage  animate on scroll
AOS.init();

/**
 * Temoignage next
 */
jQuery("section.testimonial.hp-nav .owl-next").click(function(){
    jQuery('section.testimonial.hp-nav .owl-carousel').trigger('owl.next');
   });

//hide cta on scroll
function hideShowBilanGratuit(){
    //hide cta bilan gratuit
    if(jQuery(".region-showcase .bilan-gratuit-hp").visible()){
        jQuery('header#main-header .hp-bilan-gratuit-cta').hide();
    }
    if(!jQuery(".region-showcase .bilan-gratuit-hp").visible()){
        jQuery('header#main-header .hp-bilan-gratuit-cta').show();
     }
}

(function ($) {

  $(window).load(function(){
    if ($('[data-spy]').length) {
      var scrollspyOffset = $('[data-spy]').data('offset-top');
      if ($(window).scrollTop() < scrollspyOffset) {
        $('[data-spy] li').eq(0).addClass('active');
      }
    }
});

  $(window).scroll(function(){
    if ($('[data-spy]').length) {
      var scrollspyOffset = $('[data-spy]').data('offset-top');
      if ($(window).scrollTop() < scrollspyOffset || !$('[data-spy] li.active').length) {
        $('[data-spy] li').eq(0).addClass('active');
      }
    }
  });

})(jQuery);