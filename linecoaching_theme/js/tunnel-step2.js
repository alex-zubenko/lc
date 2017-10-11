// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
jQuery(document).ready(function($) {
    if ($('.payment_message').length > 0) {
        $('html, body').animate({
            scrollTop: $("#next").offset().top
        }, 1000);
    }
    function processingPayment(){
        $('html, body').animate({
            scrollTop: $("#next").offset().top
        }, 1000);
        $(".bt_back").fadeOut();
        console.log("clicked");
        // Gestion du type de paiement : comptant ou fractionné
/*
        var typePayment = $("input:radio[name=typePricing]:checked").val();
        $("#typePricing").val(typePayment);
        //Bug fix " Données incorrectes pour initier la transaction " QUICK and VERY DIRTY
        //Si le choix n'a pas été setté et le bouton radio n'est pas là ... en principe le dernier test suffit largement. Mais just in case
        if (''==$("#typePricing").val() &&  !($("input:radio[name=typePricing]:checked").length))
        {
            $("#typePricing").val(1);
        }
*/
        // On fait apparaître le loader puis on submit
        $("#nextStep").fadeOut(function(){
            $("#ajax-loader").fadeIn();
            $("#form_payment").submit();
            $("#nextStep").one("click", processingPayment);
        });
    }

    // Nouvelle méthode pour le jQuery Validate : regex
    $.validator.addMethod("regx", function(value, element, regexpr) {
        return regexpr.test(value);
    }, "Veuillez vérifier votre numéro de carte, merci.");

    $("#nextStep").one("click", processingPayment);

    $('#form_payment').validate({
        rules : {
            card  : {
                regx      : /^([0-9]{16,17})$/,
                required  : true
            },
            month : {
                required  : true
            },
            year  : {
                required  : true
            },
            cvv   : {
                minlength : 3,
                maxlength : 4,
                required  : true
            },
            infos : {
                minlength : 2,
                required  : true
            }
        },
        groups : {
            DOB: "month year"
        },
        messages: {
            card  : "<span class='error_message'>Veuillez vérifier votre numéro de carte</div>",
            month : "<div class='error_message'>Veuillez vérifier la date d'expiration de votre carte</div>",
            year  : "<div class='error_message'>Veuillez vérifier la date d'expiration de votre carte</div>",
            cvv   : "<span class='error_message'>Veuillez vérifier le cryptogramme de votre carte</span>",
            infos : "<span class='error_message'>Veuillez saisir le nom du porteur de carte</span>"
        },
        highlight: function(element) {
            var id_attr = "#" + $( element ).attr("id") + "1";
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
            $(id_attr).removeClass('icon-ok').addClass('icon-remove');
            $(element).addClass('input-failed');
            $(element).removeClass('input-ok');

            if($(element).attr("name") != "month" && $(element).attr("name") != "year")
            {
                var id_attr = "#" + $( element ).attr("id") + "1";
                $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
                $(id_attr).removeClass('icon-ok').addClass('icon-remove');
                $(element).addClass('input-failed');
                $(element).removeClass('input-ok');
            }

            if($(element).attr("name") == "month" || $(element).attr("name") == "year"){
                $( element ).removeClass('has-success').addClass('has-error');
            }

            // On fait disparaître le loader
            $("#ajax-loader").fadeOut(function(){
                $("#nextStep").fadeIn();
                $(".bt_back").fadeOut();
            });
        },
        unhighlight: function(element) {
            var id_attr = "#" + $( element ).attr("id") + "1";
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
            if($(element).val() != "")
            {
                $(id_attr).removeClass('icon-remove').addClass('icon-ok');
                $(element).addClass('input-ok');
                $(element).removeClass('input-failed');
            }
            else
            {
                $(id_attr).removeClass('icon-remove').removeClass('icon-ok');
                $(element).removeClass('input-ok');
                $(element).removeClass('input-failed');
            }
        },
        errorElement: 'div',
            errorClass: 'help-block',
            errorPlacement: function(error, element) {
                if (element.attr("name") == "month" || element.attr("name") == "year")
                {
                    error.insertAfter("#year");
                }
                else
                {
                    if(element.length)
                    {
                        error.insertAfter(element);
                    }
                    else
                    {
                        error.insertAfter(element);
                    }
                }

                console.log("error");

                // On fait disparaître le loader
                $("#ajax-loader").fadeOut(function(){
                    $("#nextStep").fadeIn();
                    $(".bt_back").fadeOut();
                });
            }
     });
});
