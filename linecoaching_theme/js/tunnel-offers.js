if (typeof mtc == "undefined") { mtc = { }; }
if (typeof $   == "undefined") { $ =jQuery }
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
mtc.wsoffer = {
    AUTO_SCROLL_SELECTION : false,
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    onSelection : function() {
        var keyDuration = $('#mtcOfferSelectedDuration').val();
        var keyTalk     = $('#mtcOfferSelectedTalk').val();
        var keyOffer    = $('#mtcOfferSelectedOffer').val();
        if (keyDuration.length > 0) {
            var selector  = '.mtc-data-'+keyDuration+'-'+keyTalk;
            var dataOffer = $(selector);
            $('.mtc-data-sel').removeClass('mtc-data-sel');
            $('#mtcOfferSelectedOffer').val(dataOffer.find('.id').html());
            var sp = dataOffer.find('.pricing-single').html();
            var mp = dataOffer.find('.pricing-multiple').html();
            if (mp==null || mp.length == 0) {
                $('.mtc-payment-multiple').hide();
                if ($('.mtc-payment-multiple').hasClass('mtc-selected')) {
                    $("#mtcOfferSelectedPricing").val('');
                    $('.mtc-payment-multiple').removeClass('mtc-selected');
                }
            }
            else {
                $('.mtc-payment-multiple').show();
                $('.mtc-payment-multiple > span').html(mp);
            }
            if (sp==null || sp.length == 0) {
                $('.mtc-payment-single').hide();
                if ($('.mtc-payment-single').hasClass('mtc-selected')) {
                    $("#mtcOfferSelectedPricing").val('');
                    $('.mtc-payment-single').removeClass('mtc-selected');
                }
            }
            else {
                $('.mtc-payment-single').show();
                $('.mtc-payment-single > span').html(sp);
            }
            var p = dataOffer.find('.mtc-selected');
            if (p.length >0) {
                $("#mtcOfferSelectedPricing").val(p.attr('name'));
            }
            $('.mtc-offer-desc').html(dataOffer.find('.description').html());
            console.log('offer sel : '+$('#mtcOfferSelectedOffer').val());
            if ($('#mtcOfferSelectedOffer').val().length > 0) {
                $('.mtc-offer-total').html('Total : '+dataOffer.find('.total').html()).css({ 'visibility' : 'visible'});
            }
            else {
                $('.mtc-offer-total').css({ 'visibility' : 'hidden'});
            }
        }
        else {
            $('#mtcOfferSelectedOffer').val('');
            $('.mtc-offer-total').css({ 'visibility' : 'hidden'});
        }
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    onTalkSelection : function() {
        var n = $(this);
        var isSel = n.hasClass('mtc-selected');
        if (!n.hasClass('mtc-disabled')) {
            var isSel = n.hasClass('mtc-selected');
            n.parent().find('.mtc-selected').removeClass('mtc-selected');
            if (!isSel) {
                n.addClass('mtc-selected');
                $('#mtcOfferSelectedTalk').val(n.attr('data-talk'));
            }
            else {
                $('#mtcOfferSelectedTalk').val(0);
            }
            mtc.wsoffer.onSelection();
        }
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    onDurationSelection : function() {
        var n = $(this);
        var isSel = n.hasClass('mtc-selected');
        if (!isSel) {
            n.parent().find('.mtc-selected').removeClass('mtc-selected');
            $('.mtc-box-group > div').removeClass('mtc-disabled');
            var exclude = n.attr('data-exclude').split(',');
            for(var i=0, lim = exclude.length; i < lim; i++) {
                console.log('exclude '+exclude[i]);
                $('.mtc-talk-'+exclude[i]).addClass('mtc-disabled');
                if ($('.mtc-talk-'+exclude[i]).hasClass('mtc-selected')) {
                    $('.mtc-talk-'+exclude[i]).removeClass('mtc-selected');
                    console.log('reset talk');
                    $('#mtcOfferSelectedTalk').val(0);
                }
            }
            if (!isSel) {
                n.addClass('mtc-selected');
                $('#mtcOfferSelectedDuration').val(n.attr('data-duration'));
            }
            else {
                $('#mtcOfferSelectedDuration').val(0);
            }
            mtc.wsoffer.onSelection();
        }
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    editCode : function() {
        var n = $('.mtc-offer-code-data');
        console.log(n.hasClass('mtc-offer-code-edit'));
        if (!n.hasClass('mtc-offer-code-edit')) {
            n.addClass('mtc-offer-code-edit');
            n.slideDown();
            $('.mtc-offer-load').css({ 'display' : 'none' });
        }
        else {
            n.removeClass('mtc-offer-code-edit');
            n.slideUp();
        }
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    sendCode : function(abort) {
        var cancel = abort==1;
        if (cancel) {
            $('#discountCode').val('');
            mtc.wsoffer.bindCancelCode();
        }
        else {
            mtc.wsoffer.bindSendCode();
        }
        var code  = $('#discountCode').val();
        $(cancel ? '.mtc-offer-cancel-code' : '.mtc-offer-code').fadeOut(function() {
            $('.mtc-offer-load').fadeIn(function() {
                setTimeout(function() {
                $.ajax({
                    type    : 'POST',
                    dataType: 'json',
                    url     : $('#mtc-offer-container').attr('data-url') + code,
                    success : function(json) {
                        $(cancel ? '.mtc-offer-cancel-code' : '.mtc-offer-code-send').removeClass('oned');
                        if (json.done) $('#mtc-offer-container').html(json.data);
                        $(".mtc-offer-code-error").fadeOut(function() {
                            $(this).html(json.error);
                            $(this).fadeIn();
                            setTimeout(function() {
                                $('.mtc-offer-code-error').fadeOut();
                            }, 2500);
                        });
                        $('.mtc-offer-load').fadeOut(function() {
                            if (cancel || json.error.length > 0) {
                                if (json.error.length > 0) {
                                    $('.mtc-offer-cancel-code').fadeOut();
                                    $('#discountCode').val('');
                                    $('.mtc-offer-code').fadeIn();
                                    console.log('cancel');
                                }
                                else {
                                    $('.mtc-offer-code').fadeIn();
                                    //~ mtc.wsoffer.modeBlockInitSelection();
                                    mtc.wsoffer.modeBlockBindSelection();
                                }
                            }
                            else $('.mtc-offer-cancel-code').fadeIn();
                            if (json.done) {
                                $('html, body').animate({
                                    scrollTop: $("#mtc-offer-container").offset().top
                                }, 500);
                                mtc.wsoffer.bindDuration(true, json.durationSel, json.taskSel);
                                //~ mtc.wsoffer.modeBlockInitSelection();
                                if (!cancel) {
                                    mtc.wsoffer.modeBlockBindSelection();
                                }
                            }
                         });
                         if (cancel) $('.mtc-offer-code-data').removeClass('mtc-offer-code-edit');
                    },
                    error: function(json) {
                        if (cancel) {
                            $('.mtc-offer-cancel-code').fadeOut();
                        }
                        else {
                            $('.mtc-offer-cancel-code').fadeIn();
                        }
                        $('.mtc-offer-load').fadeOut();
                        console.log(json);
                    }
                });
                }, 1500);
            });
        });
        console.log('loading code '+code);
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    bindCodes : function() {
        mtc.wsoffer.bindSendCode();
        $('.mtc-offer-code').on('click', mtc.wsoffer.editCode);
        mtc.wsoffer.bindCancelCode();
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    bindSendCode : function() {
        $('.mtc-offer-code-send').on('click', function(event) {
            if (!$('.mtc-offer-code-send').hasClass('oned')) {
                $('.mtc-offer-code-send').addClass('oned');
                mtc.wsoffer.sendCode(this);
            }
        });
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    bindCancelCode : function() {
        $('.mtc-offer-cancel-code').on('click', function(event) {
            if (!$('.mtc-offer-cancel-code').hasClass('oned')) {
                $('.mtc-offer-cancel-code').addClass('oned');
                mtc.wsoffer.sendCode(1);
            }
        });
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    bindDuration : function(init, dus, tas) {
        $('.mtc-offer-panel-item').on('click', mtc.wsoffer.onDurationSelection);
        if (init) {
            mtc.wsoffer.initSelection(dus, tas);
        }
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    bindTalk : function() {
        $('.mtc-offer-block .mtc-box-group > div').on('click', mtc.wsoffer.onTalkSelection);
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    bindPayment : function() {
        $('.mtc-payment-group > div').on('click', function() {
            var n = $(this);
            var isSel = n.hasClass('mtc-selected');
            console.log('isSel');
            console.log(isSel);
            if (!isSel) {
                n.parent().find('.mtc-selected').removeClass('mtc-selected');
                if (!isSel) {
                    n.addClass('mtc-selected');
                };
            }
            $("#mtcOfferSelectedPricing").val(n.attr('name'));
        });
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    initSelection : function(dus, tas) {
        if (!dus) {
            var dus = $('.mtc-offer-data').attr('data-dus');
        }
        if (dus != null && dus.length>0) {
            $('.mtc-offer-panel-item .mtc-selected').removeClass('mtc-selected');
            $('#mtcOfferSelectedDuration').val(dus);
            $('.mtc-offer-panel-item').each(function() {
                if ($(this).attr('data-duration')==dus) $(this).click();
            });
        }
        if (!tas) {
            var tas = $('.mtc-offer-data').attr('data-tas');
        }
        $('.mtc-box-group .mtc-selected').removeClass('mtc-selected');
        if (tas !=null && tas.length>0) {
            $('#mtcOfferSelectedTalk').val(tas);
            $('.mtc-talk-'+tas).click();
        }
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    modeBlockInitSelection : function() {
        var jqn = $('.mtc-offer-mode-block.mtc-selected');
        jqn.removeClass('mtc-selected').click();
        setTimeout(function(){
            mtc.wsoffer.AUTO_SCROLL_SELECTION = true;
        }, 500);
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    modeBlockBindSelection : function() {
        $('.mtc-offer-mode-block').on('click', function() {
            console.log('-> modeBlockBindSelection()');
            var isSel = $(this).hasClass('mtc-selected');
            var hasSel = $('.mtc-offer-mode-block.mtc-selected').length>0;
            console.log($('.mtc-offer-mode-block.mtc-selected'));
            if (isSel) {
                console.log('isSel');
                console.log('remove class SEL');
                $(this).removeClass('mtc-selected');
                $('.mtc-payment-multiple').slideUp();
                $('.mtc-payment-single').slideUp();
            }
            else {
                console.log('NOT SEL');
                $('.mtc-offer-mode-block.mtc-selected').removeClass('mtc-selected');
                $(this).addClass('mtc-selected');
                $('#mtcOfferSelectedOffer').val($(this).attr('data-id'));
                $('#mtcOfferSelectedTalk').val($(this).attr('data-talk'));
                $('#mtcOfferSelectedDuration').val($(this).attr('data-duration'));
                var sp = $(this).find('.pricing-single').html();
                var mp = $(this).find('.pricing-multiple').html();
                console.log('isMultipleDefault');
                var isMultipleDefault = $(this).find('.pricing-multiple').hasClass('pricing-default');
                console.log(isMultipleDefault);
                $('.mtc-payment-multiple').parent().find('.mtc-selected').removeClass('mtc-selected');
                if (isMultipleDefault) {
                    $('.mtc-payment-multiple').after($('.mtc-payment-single'));
                    $('.mtc-payment-multiple').addClass('mtc-selected');
                    $("#mtcOfferSelectedPricing").val(2);
                }
                else {
                    $('.mtc-payment-single').after($('.mtc-payment-multiple'));
                    $('.mtc-payment-single').addClass('mtc-selected');
                    $("#mtcOfferSelectedPricing").val(1);
                }
                if (mp==null || mp.length == 0) {
                    $('.mtc-payment-multiple').hide();
                    if ($('.mtc-payment-multiple').hasClass('mtc-selected')) {
                        $("#mtcOfferSelectedPricing").val('1');
                        $('.mtc-payment-multiple').removeClass('mtc-selected');
                    }
                }
                else {
                    if (hasSel) {
                        $('.mtc-payment-multiple').show();
                    }
                    else {
                        $('.mtc-payment-multiple').slideDown();
                    }
                    $('.mtc-payment-multiple > span').html(mp);
                }
                if (sp==null || sp.length == 0) {
                    $('.mtc-payment-single').hide();
                    if ($('.mtc-payment-single').hasClass('mtc-selected')) {
                        $("#mtcOfferSelectedPricing").val('2');
                        $('.mtc-payment-single').removeClass('mtc-selected');
                    }
                }
                else {
                    if (hasSel) {
                        $('.mtc-payment-single').show();
                    }
                    else {
                        $('.mtc-payment-single').slideDown();
                    }
                    $('.mtc-payment-single > span').html(sp);
                }
                if (mtc.wsoffer.AUTO_SCROLL_SELECTION) {
                    $('html, body').animate({
                        scrollTop: $(".mtc-offer-code-ctn").offset().top
                    }, 500);
                }
            }
            console.log('<- modeBlockBindSelection()');
        });
    },
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    modeBlockBindPayment: function() {
        $('.mtc-payment-group > div').on('click', function() {
            console.log('-> modeBlockBindPayment:()');
            var n = $(this);
            var isSel = n.hasClass('mtc-selected');
            if (!isSel) {
                n.parent().find('.mtc-selected').removeClass('mtc-selected');
                if (!isSel) {
                    n.addClass('mtc-selected');
                };
            }
            $("#mtcOfferSelectedPricing").val(n.attr('name'));
            console.log('<- modeBlockBindPayment:()');
        });
    }
}
