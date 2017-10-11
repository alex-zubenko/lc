(function($) {

    $(document).ready(function() {
        $(".titreApplication span.spanTitreApp").click(function() {
            $(".iconeApplication").toggle();
            $(this).toggleClass("up");
        });
        $(".titreOutils .spanTitreOutils").click(function() {
            $(".iconeOutils").toggle();
            $(this).toggleClass("up");
        });

    });

})(jQuery);
