(function ($, Drupal) {

  Drupal.behaviors.massageWasSenddh = {
    attach: function(context, settings) {
      $(document).once('modalClick').on("mousedown", ".ui-dialog-buttonset button:last-child", function(e){
        e.preventDefault();
        $('.ui-dialog-titlebar-close').click();
      });
    }
  };

})(jQuery, Drupal);

