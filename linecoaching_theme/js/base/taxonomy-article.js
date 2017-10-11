//taxonomy flexslider on mobile ,page mag
function mobileTaxonomyCarrousel(){
    var taxMobileCarrousel = 486;

    /*todo flexislider
    if (jQuery(window).width() < taxMobileCarrousel) {
        jQuery('.tag-dossier-container').flexslider({
             selector: ".sub-taxonmy-tag-dossier span.taxonomy",
             animation : "slide"
         });
     }else{
        jQuery('.tag-dossier-container').flexslider("destroy");
     }*/
    }
jQuery(document).ready(function(jQuery) {
    //div summary h2 in articles
    jQuery("body article.taxonomy-article  h2").each(function() {
             text = jQuery(this).text();
             //subtract  250 ,related due to stickbar menu offset of 250 (main.js)
             position = jQuery(this).offset().top - 250;
             onclickText = 'window.scrollTo(0,'+position+')';
             summary =  '<span class="text"><a onclick="'+onclickText+'">' + text + '</a></span>';
             jQuery( "body article.taxonomy-article #summary .summary-text" ).append( summary );
      });
    //filter of page tags
    jQuery('.taxonomy-term-transverse .taxonomy_filters select.taxonomy-transverse-filter').on('change', function() {
         jQuery('.article-article.'+this.name).hide();
         jQuery('.article-article.'+this.value).show();
      });
    jQuery('.taxonomy-term-contenu .taxonomy_filters select.taxonomy-transverse-filter').on('change', function() {
        jQuery('.article-article.'+this.name).hide();
        jQuery('.article-article.'+this.value).show();
     });
    //slider for node diaporama
    jQuery('#diaporama-article-carousel .flexslider-diaporama').flexslider({
        animation: "slide"
      });
    //left menu theme acccordion effect level 2 on click action
    jQuery('.sidebar .theme-navigation ul.level-0 li ul.level-1 span.show-more').click(function(event) {
        next = jQuery(event.target).next();
        if(jQuery(this).hasClass('clicked')){
            next = jQuery(event.target).next();
             jQuery(this).removeClass('clicked');
             next.css("opacity", "0");
             next.css("max-height", "0");
             next.find('li').css("opacity" ,"0");
             next.find('li').css("max-height" ,"0");
        }else{
            next.css("opacity", "1");
            jQuery(this).addClass('clicked');
            next.css("max-height", "500px");
            next.find('li').css("opacity" ,"1");
            next.find('li').css("max-height" ,"none");
        }
       });
    //ckeditor video carrousel
    jQuery('.region-content .flexslider-ckeditor-video').flexslider({
            animation: "slide",
            controlNav: "thumbnails",
            slideshow:  false,
            animationLoop: false
        });
    jQuery('article.embedded-entity .flexslider-ckeditor-video.flexslider-carrousel ol.flex-control-nav li').click(function() {
  	  //stop ckeditor video carrousel
  	   jQuery('.ckeditor-video-iframe').each(function(index) {
  		   jQuery(this).attr('src', jQuery(this).attr('src'));
  	        });  
  	   });
     //on load
    mobileTaxonomyCarrousel();
    //on resize
    jQuery(window).resize(function() {
        mobileTaxonomyCarrousel();
    });
 });


