// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
jQuery(document).ready(function($) {
    try {
        if ($('.mtc-is-subscriber').length > 0) {
            $('.abonnement_form_customer_informations, .je_m_abonne').slideUp();
        }
        mtc.wsoffer.bindCodes();
        mtc.wsoffer.bindTalk();
        mtc.wsoffer.bindDuration(true);
        mtc.wsoffer.bindPayment();
        mtc.wsoffer.modeBlockBindSelection();
        mtc.wsoffer.modeBlockInitSelection();
        $('#confirmation_email').on('paste', function (event) {
            console.log('paste is disabled');
            event.preventDefault();
        });
    }
    catch(e) {
        console.log('error : '+e.message);
    }
    $('select').show();
    $('.chosen-container').remove();
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
var current_message = "";
var regex_email     = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/;
var alert_messages  = [
    "Vous avez déjà un compte. Pour continuer, connectez-vous en renseignant votre identifiant et votre mot de passe dans l'encart de connexion en haut de la page.",
    "Vous possédez déjà un abonnement en cours.",
    "Cet email est associé à un compte.",
    "Veuillez sélectionner une offre.",
    "Pour continuer, vous devez confirmer que vous êtes majeur, que vous avez lu et compris les conditions générales d'abonnement et d'utilisation.<br/>Vous devez déclarer que n'êtes pas sujet aux contre-indications décrites ci-dessus.<br/>Pour cela, vous devez cocher les cases correspondantes",
    "Cet email est associé à un abonnement en pause",
    "Cet email est associé à un abonnement fermé",
    "Veuillez sélectionner un paiement"
];
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function setCGAMode() {
    console.log('CGA');
    // mode CGA : replace #next button by #next-cga then display popup
    var nextCGA  = $('<button id="next-cga" class="align-right bt_suivant">suivant <span>›</span></button>');
    var modalCGA = $('#modalCGA');
    nextCGA.on('click', showCGA);

    $("#ajax-loader").fadeOut(function() {
        $("#next").after(nextCGA).remove();
        nextCGA.fadeIn();
    });

    $('.btn-primary', modalCGA).on('click', function(e) {
        if (!$(this).hasClass('disabled')) {
            $(this).addClass('disabled');
            $(this).fadeOut(function() {
                var loader = $('<img id="ajax-loader" src="/themes/custom/linecoaching_theme/images/ajax-loader.gif" class="align-right"/>');
                $(this).after(loader);
                loader.fadeIn();
            });
            $("#prospect_form").submit();
        }
    });
    $('.btn-default', modalCGA).on('click', function(e) {
        $('.content_layout > .col-md-6').removeClass('mtc-with-modal');
        $("#ajax-loader-page").fadeOut(function() {
            $("#next").fadeIn();
        });
    });
    $(modalCGA).on('hidden.bs.modal', function () {
        $('.content_layout > .col-md-6').removeClass('mtc-with-modal');
        $("#ajax-loader-page").fadeOut(function() {
            $("#next").fadeIn();
        });
    })
    showCGA();

    function showCGA() {
        modalCGA.modal();
        $('.content_layout > .col-md-6').addClass('mtc-with-modal');
        $('.modal-backdrop').removeClass("modal-backdrop");
    }
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function getBirthday(){
    var day   = $("#day").val();
    var month = $("#month").val();
    var year  = $("#year").val();
    return birthdate = new Date(year+"-"+month+"-"+day);
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function digit2(int) {
    return ("0" + int).slice(-2);
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function isMajor(year, month, day) {
    var d = new Date();
    d = new Date(d.getFullYear()-18, d.getMonth(), d.getDate());
    return d.getFullYear()+digit2(d.getMonth()+1)+digit2(d.getDate()) >= ''+year+month+day;
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function highlightBirthday() {
    var minor  = !isMajor($("#year").val(), $("#month").val(), $("#day").val());
    var addCss = 'input-'+(minor ? 'failed' : 'ok');
    var rmCss  = 'input-'+(minor ? 'ok' : 'failed');
    var sl     = ['#day', '#month', '#year'];
    for(var i=0, lim = sl.length; i < lim; i++) {
        $(sl[i]).addClass(addCss).removeClass(rmCss);
    }
    $(sl[0]).parent().parent().removeClass('has-'+(minor ? 'success' : 'error')).addClass('has-'+(minor ? 'error' : 'success'));
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function selectOffer(selection) {
    $(".bt_selection").show();
    selection.hide();
    // responsive
    if ($(".abo_offer_details").is(":visible")) {
        $(".abo_offer_block").addClass("abo_offer_block_mobile");
    }
    else {
        $(".abo_offer_block").removeClass("abo_offer_block_mobile");
    }
    $(".bt_selection").parent().parent().parent().parent().removeClass("selected_offer");
    selection.parent().parent().parent().parent().addClass("selected_offer");
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function collapseContent(bt){
    if (bt.children().hasClass("collapsed")) {
        if (bt.children().hasClass("collapsed")) {
            bt.children().removeClass("collapsed");
        }
        bt.prev().text("Réduire");
        bt.find(".btn").text("-");
    }
    else {
        if (bt.children().removeClass("collapsed")) {
            bt.children().addClass("collapsed");
        }
        bt.prev().text("Voir le détail");
        bt.find(".btn").text("+");
    }
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function displayAlertMessage(availability) {
    switch(parseInt(availability)){
        case -2  :
            $("#notification_message").fadeOut(function() {
                $(this).html("");
            });
            break;

        case -1  :
            $("#notification_message").fadeOut(function() {
                $(this).html(alert_messages[0]).fadeIn();
            });
            break;

        case 10  :
            $("#notification_message").fadeOut(function() {
                $(this).html(alert_messages[0]).fadeIn();
            });
            break;

        case 101 :
        case 1   :
        case 3   :
            $("#notification_message").fadeOut(function() {
                $(this).html(alert_messages[1]).fadeIn();
            });
            break;

        case 2   :
            $("#notification_message").fadeOut(function() {
                $(this).html(alert_messages[0]).fadeIn();
            });
            break;

        case 4   :
            $("#notification_message").fadeOut(function() {
                $(this).html(alert_messages[0]).fadeIn();
            });
            break;
    }

    if (regex_email.test($("#email").val())) {
        emailFieldControl(availability);
    }
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function emailFieldControl(boolean) {
    switch(boolean){
        case -2:
            $("#email").closest('.form-group').removeClass('has-success').addClass('has-error');
            $("#email").removeClass('input-failed').addClass('input-ok');
            $("#email1").removeClass('icon-remove').addClass('icon-ok');
            break;
        case -1:
        case 10:
        case  1:
        case  2:
        case  3:
        case  4:
            $("#email").closest('.form-group').removeClass('has-success').addClass('has-error');
            $("#email").removeClass('input-ok').addClass('input-failed');
            $("#email1").removeClass('icon-ok').addClass('icon-remove');
            break;
    }
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function recheckEmailBeforeContinue(data) {
    displayAlertMessage(data);
    if (data == -2) {
        setCGAMode();
    }
    else {
        $("#ajax-loader-page").fadeOut(function() {
            $("#next").fadeIn();
        });
    }
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function checkOptins() {
    var idOffer     = $('#mtcOfferSelectedOffer').val();
    var typePricing = $('#mtcOfferSelectedPricing').val()
    if ($("#prospect_form").valid()  && "on" && idOffer > 0 && typePricing > 0) {
        checkEmailAvailabality(recheckEmailBeforeContinue, { loader : true });
    }
    else {
        $("#ajax-loader").fadeOut(function() {
            $("#next").fadeIn();
        });
    }
    $("#next").one("click", processingNext);
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function processingNext() {
    $("#next").attr('name', 'used');
    $("#notification_message").slideUp(function() {
        $(this).html("");
    });
    $("#optins_message").fadeOut(function() {
        $(this).html("");
    });
    var error_message = "";
    checkOptins();

    var done = true;
    var idOffer     = $('#mtcOfferSelectedOffer').val();
    var typePricing = $('#mtcOfferSelectedPricing').val();
    $('#prospect_form input[name=idOffer]').val(idOffer);
    $('#prospect_form input[name=typePricing]').val(typePricing);
    if (!(idOffer > 0)) {
        $("#offer_message").fadeOut(function() {
            $(this).html(alert_messages[3]).fadeIn();
        });
        done = false;
    }
    else if (!(typePricing > 0)) {
        $("#offer_message").fadeOut(function() {
            $(this).html(alert_messages[7]).fadeIn();
        });
        done = false;
    }
    return done;
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function defineStep1Form() {
    $('#prospect_form').validate({
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        rules : {
            sex         : {
                required    : true
            },
            firstname   : {
                minlength       : 2,
                maxlength       : 100,
                required        : true
            },
            lastname    : {
                minlength       : 2,
                maxlength       : 100,
                required        : true
            },
            day         : {
                required        : true,
                checkBirthday   : true
            },
            month       : {
                required        : true,
                checkBirthday   : true
            },
            year        : {
                required        : true,
                checkBirthday   : true
            },
            email       : {
                // Regex RFC3696 : à vérifier
                regx            : regex_email
            },
            confirmation_email : {
                equalTo         : "#email",
                required        : true
            }
        },
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        groups      : {
            DOB         : "day month year"
        },
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        messages    : {
            sex         : addWarnMsg("Veuillez choisir votre civilité"),
            firstname   : addWarnMsg("Veuillez saisir 2 caractères minimum"),
            lastname    : addWarnMsg("Veuillez saisir 2 caractères minimum"),
            day         : {
                required      : addWarnMsg("Veuillez sélectionner votre date de naissance"),
                checkBirthday : function() {
                    return addWarnMsg("Vous devez être majeur pour vous inscrire");
                }
            },
            month       : {
                required      : addWarnMsg("Veuillez sélectionner votre date de naissance"),
                checkBirthday : function() {
                    return addWarnMsg("Vous devez être majeur pour vous inscrire");
                }
            },
            year        : {
                required      : addWarnMsg("Veuillez sélectionner votre date de naissance"),
                checkBirthday : function() {
                    return addWarnMsg("Vous devez être majeur pour vous inscrire");
                }
            },
            email             : addWarnMsg("Veuillez vérifier votre email"),
            confirmation_email: addWarnMsg("Votre adresse mail ne correspond pas à l'adresse saisie précédemment. Merci de corriger.")
        },
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        highlight : function(node) {
            if (!isDateNode(node)) {
                var idAttr = "#" + $(node).attr("id") + "1";
                $(node).closest('.form-group').removeClass('has-success').addClass('has-error');
                $(idAttr).removeClass('icon-ok').addClass('icon-remove');
                $(node).addClass('input-failed').removeClass('input-ok');
            }
            else {
                highlightBirthday();
            }
        },
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        unhighlight: function(node) {
            if (!isDateNode(node)) {
                var idAttr = "#" + $(node).attr("id") + "1";
                $(node).closest('.form-group').removeClass('has-error').addClass('has-success');
                if ($(node).val() != "") {
                    $(idAttr).removeClass('icon-remove').addClass('icon-ok');
                    $(node).addClass('input-ok');
                    $(node).removeClass('input-failed');
                }
                else {
                    $(idAttr).removeClass('icon-remove').removeClass('icon-ok');
                    $(node).removeClass('input-ok');
                    $(node).removeClass('input-failed');
                }
            }
            else {
                highlightBirthday();
            }
        },
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        errorElement  : 'div',
        errorClass    : 'help-block',
        errorPlacement: function(error, node) {
            error.insertAfter(isDateNode(node) ? '#year' : node);
        }
    });
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function isDateNode(node) {
    var name = $(node).attr("name");
    return name == "day" || name == "month" || name == "year";
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function addWarnMsg(msg) {
    return "<span class='error_message'>"+msg+"</span>";
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function checkEmailAvailabality(func, params) {
    var ajxdata = {
        type     : 'GET',
        async    : true,
        dataType : 'json',
        data     : { email: $("#email").val()},
        url      : '/ws/email/'+encodeURI($("#email").val())+'/available',
        success  : function(data) {
           if (!(!params) && params.loader) {
                $('#ajax-loader-page').fadeOut(function() {
                    //~ $('#next').fadeIn();
                });
            }
            func.call(null, data, params);
        },
        error    : function(x,e) {
            console.log(x);
        }
    };
    $.ajax(ajxdata);
    if (!(!params) && params.loader) {
        $('#next').fadeOut(function() {
            $('#ajax-loader-page').fadeIn();
        });
    }
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function initStep1() {
    $(".bt_discount").on("click", function() {
        var options = { easing: 'swing' };
        $(".abonnement_discount_code_input").slideToggle(options);
    });
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#discount_form").submit(function(e) {
        e.preventDefault();
    });
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $("#email").on( "focusout", function() {
        checkEmailAvailabality(displayAlertMessage);
    });

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $.validator.addMethod("regx", function(value, element, regexpr) {
        return regexpr.test(value);
    }, "Veuillez vérifier votre email.");

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $.validator.addMethod("checkBirthday", function(value, element, selector) {
        var inSubmit = $("#next").attr('name') == 'used';
        var major    = isMajor($("#year").val(), $("#month").val(), $("#day").val());
        if (inSubmit) {
            $("#next").attr('name', '');
        }
        // not in submit, short-cut validation if year not fill
        else if ($("#year").val().length==0) {
            major = true;
        }
        return major;
    });
    $("#next").one("click", processingNext);

    // On récupère l'offre sélectionnée
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $(".abo_offer_block").on("click", function(e) {
        e.preventDefault();
        var bt_selection = $(this).find(".bt_selection");
        // $("#prospect_offer").val((bt_selection.prev().val()));
        selectOffer(bt_selection);
        $("#offer_message").fadeOut();
    });

    $(".collapse-btn").find(".btn").addClass("collapsed");

    $(".collapse-btn").click(function() {
        collapseContent($(this));
    });

    $(".see_details").click(function() {
        collapseContent($(this).next());
    });
    defineStep1Form();
    //~ $("#prospect_form").valid();
}
    initStep1();
});
