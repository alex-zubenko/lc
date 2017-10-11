// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
jQuery(document).ready(function($) {
    var fr = [
        'France', 'Guyane_Francaise', 'Guadeloupe', 'La_Reunion', 'La_Martinique', 'La_Nouvelle-Caledonie',
        'La_Polynesie_Francaise', 'Wallis-et-Futuna', 'Mayotte_(ile)', 'Saint-Pierre-et-Miquelon'
    ]; 

    function reinitForm()
    {
        $("#ajax-loader").fadeOut(function(){
            $("#next").fadeIn();
            $("#next").one("click", processingSave);
        });
    }

    function processingSave()
    {
        // On fait apparaître le loader puis on submit
        $("#next").fadeOut(function(){
            $("#ajax-loader").fadeIn();
            if($("#subscribe_form").valid())
            {
                $("#subscribe_form").submit();
            }
            else
            {
                reinitForm();
            }
        });
    }
    $('#confirmationPwd').on('paste', function (event) {
        console.log('paste is disabled');
        event.preventDefault();
    });
    $("#next").one("click", processingSave);
    
    // Nouvelle méthode pour le jQuery Validate : regex
    $.validator.addMethod("regx", function(value, element, regexpr) {
        return regexpr.test(value);
    }, "");
    
    // Nouvelle méthode pour le jQuery Validate : chkCp
    $.validator.addMethod("chkCp", function(value, element) {
        var country = $("#country").val();
         
        // FR/DOM/TOM
        if(fr.indexOf(country) != -1)
        {
            // Regex FR
            return /^((0[1-9])|([1-8][0-9])|(9[0-7]))[0-9]{3}$/.test(value);
        }
        else
        {
            // Si on a Monaco
            if(country === "Monaco")
            {
                // Regex MONACO
                return /(98)([0-9]{3})$/.test(value);
            }

            // Si on a autre pays
            return true;
        }
    }, "");

    // Nouvelle méthode pour le jQuery Validate : chkPwd
    $.validator.addMethod("chkPwd", function(value, element) {
        //console.log(value);
        var hasNum      = /[\d]+/.test(value);
        var hasLow      = /[a-z]+/.test(value);
        var hasUpp      = /[A-Z]+/.test(value);
        
        var replacedSpe = value.replace(/[a-z0-9\s]*/i, '');
        var hasSpe      = /^[^a-z0-9\s]+$/i.test(replacedSpe);
        
        var test = hasNum && hasLow && hasUpp && value.length > 7;
               
        return test;
    }, "");
    
    // Nouvelle méthode pour le jQuery Validate : chkPhone
    $.validator.addMethod("chkPhone", function(value, element, selector) {
        //console.log(value);
        if(selector.is(':checked'))
        {
            return value.length > 0;
        }
        else
        {        
            // Longueur numéro FR en 10 chiffres
            return value.length == 10;
        }
    });

    function modifyContentText(selector, txt) 
    {
        selector.text(txt);
    }

    // Si FR/DOM/TOM n'est pas sélectionné, on modifie le label Code postal pour qu'il devienne facultatif
    $("#country").change(function(){
        if(fr.indexOf($(this).val()) == -1)
        {
            modifyContentText($("#zip_code_label"), "Code postal");
        }
        else
        {
            modifyContentText($("#zip_code_label"), "Code postal *"); 
        }
    });
    
    $('#subscribe_form').validate({
        rules: {
            username: {
                remote: '/ws/nickname/available',
                regx: /^[a-zA-Z0-9_-]{5,12}$/,
                required: true
            },
            pwd: {
                chkPwd: true,
                required: true,
                minlength: 8,
                maxlength: 50
            },
            confirmationPwd: { 
                required: true,
                equalTo: "#pwd"
            },
            country: {
                required: true
            },
            address: {
                regx: /[^\s]{2,}/i,
                required: true
            },
            address_more: {
                required: false  
            },
            zip_code: {
                required: {
                    depends: function(){
                        var country = $("#country").val();
                        return (fr.indexOf(country) != -1) ? true : false;
                    }
                },
                chkCp: true
            },
            city: {
                required: true
            },
            phone: {
                number: {
                    depends: function(){
                        return !$("#fa_num_etranger").is(":checked");
                    }
                },
                chkPhone: $("#fa_num_etranger")
            }
        },
        messages: {
            username: {
                required: "<div class='error_message'>Votre pseudo doit contenir entre 5 caractères et 12 caractères.</div>",
                regx:     "<div class='error_message'>Votre pseudo doit contenir entre 5 caractères et 12 caractères.</div>",
                remote:   "<div class='error_message'>Ce pseudo est déjà utilisé.</div>"
            },
            pwd:             "<div  class='error_message'>Votre mot de passe doit contenir au minimum 8 caractères.</div>",
            confirmationPwd: "<div  class='error_message'>Veuillez vérifier la confirmation de votre mot de passe.</div>",
            country:         "<span class='error_message'>Veuillez choisir votre pays.</span>",
            address:         "<span class='error_message'>Veuillez vérifier votre adresse.</span>",
            zip_code:        "<span class='error_message'>Veuillez vérifier votre code postal.</span>",
            city:            "<span class='error_message'>Veuillez vérifier votre ville.</span>",
            phone: {
                number: "<span class='error_message'>Veuillez saisir un numéro de téléphone à 10 chiffres sans espace ni caractères spéciaux.</span>",
                chkPhone: function(){
                    if($("#fa_num_etranger").is(":checked")){
                        if($("#inputTel").val().length < 1){
                            return "<span class='error_message'>Veuillez indiquer un numéro de téléphone.</span>";
                        } else {
                            //return "<span class='error_message'>Veuillez indiquer un numéro de téléphone.</span>";
                        }
                    } else {
                        if($("#inputTel").val().length < 10){
                            return "<span class='error_message'>Veuillez saisir un numéro de téléphone à 10 chiffres sans espace ni caractères spéciaux.</span>";
                        }
                    }
                }
            }
        },
        highlight: function(element) {
            var id_attr = "#" + $( element ).attr("id") + "1";
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
            $(id_attr).removeClass('icon-ok').addClass('icon-remove');
            $(element).addClass('input-failed');
            $(element).removeClass('input-ok');
            
            // On fait disparaître le loader
            $("#ajax-loader").fadeOut(function(){
                $("#next").fadeIn();
            });
        },
        unhighlight: function(element) {
            var id_attr = "#" + $( element ).attr("id") + "1";
            if($(element).attr("id") != "inputAdresse2")
            {
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
            }
        },
        errorElement: 'div',
            errorClass: 'help-block',
            errorPlacement: function(error, element) {
                if(element.length) 
                {
                    error.insertAfter(element);
                } 
                else 
                {
                    error.insertAfter(element);
                }
                //console.log("error");
                
                // On fait disparaître le loader
                $("#ajax-loader").fadeOut(function(){
                    $("#next").fadeIn();
                });
            } 
     });
});
