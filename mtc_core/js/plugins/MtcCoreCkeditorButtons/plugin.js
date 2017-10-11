CKEDITOR.plugins.add( 'lcckeditorbuttons', {
    requires: 'widget',
    icons: 'widgetEncadreRedac, widgetEncadreConnexe, widgetEncadrePromo, widgetMiseEnAvant, widgetbootstrap2EqualCol',

    init: function( editor ) {

        // Configurable settings
        //var allowedWidget = editor.config.widgetbootstrap_allowedWidget != undefined ? editor.config.widgetbootstrap_allowedFull :
        //    'p h2 h3 h4 h5 h6 span br ul ol li strong em img[!src,alt,width,height]';
        var allowedFull = editor.config.widgetbootstrap_allowedFull != undefined ? editor.config.widgetbootstrap_allowedFull :
            'p a div span h2 h3 h4 h5 h6 section article iframe object embed strong b i em cite pre blockquote small sub sup code ul ol li dl dt dd table thead tbody th tr td img caption mediawrapper br[href,src,target,width,height,colspan,span,alt,name,title,class,id,data-options]{text-align,float,margin}(*);'
        //var allowedText = editor.config.widgetbootstrap_allowedText != undefined ? editor.config.widgetbootstrap_allowedFull :
        //    'p span br ul ol li strong em';

        allowedWidget = allowedFull;

        editor.ui.addButton('widgetEncadreRedac', {
            label: 'Inserer box encadre redactionnelle',
            command: 'widgetEncadreRedac',
            icon: this.path + 'images/encadre-redac.jpg'
        });
        // Define the widgets
        editor.widgets.add( 'widgetEncadreRedac', {

            button:  'Inserer box encadrer redactionnelle' ,

            template:
                '<div class="encadre-redac">' +
                '   <div class="image-encadre-redac"><p><img src="http://placehold.it/300x250&text=Image" /></p></div>' +
                   '<h3>Title</h3>' +
                   '<p>Content</p>' +
                   '<div class="encadre-redac-cta"> <a class="btn btn-default" href="#"><span class="text">A Button</span></a></div>' +
                '</div>',

             editables: {
                    content: {
                        selector: '.encadre-redac',
                        allowedContent: allowedFull
                    }
                },
            allowedContent: allowedFull,

            upcast: function( element ) {
                return element.name == 'div' && element.hasClass( 'encadre-redac' );
            }

        });

        editor.widgets.add( 'widgetbootstrap2EqualCol', {

            button: 'Add 2 column box' ,

            template:
                '<div class="row two-col">' +
                    '<div class="col-md-6 col-2-1"><p><img src="http://placehold.it/400x225&text=Image" /></p><p>Texte:Deux column 50%</p></div>' +
                    '<div class="col-md-6 col-2-2 "><p><img src="http://placehold.it/400x225&text=Image" /></p><p>Text:Deux column 50%</p></div>' +
                '</div>',

            editables: {
                col1: {
                    selector: '.col-2-1',
                    allowedContent: allowedWidget
                },
                col2: {
                    selector: '.col-2-2',
                    allowedContent: allowedWidget
                }
            },

            allowedContent: allowedFull,

            upcast: function( element ) {
                return element.name == 'div' && element.hasClass( 'two-col' );
            }

        } );

        editor.ui.addButton('widgetbootstrap2EqualCol', {
            label: 'Inserer box de 50% à 50%',
            command: 'widgetbootstrap2EqualCol',
            icon: this.path + 'images/bootstrap-2-col.jpg'
        });
        editor.ui.addButton('widgetEncadreConnexe', {
            label: 'Inserer box connexe',
            command: 'widgetEncadreConnexe',
            icon: this.path + 'images/encadre-connexe.jpg'
        });
        // Define the widgets
        editor.widgets.add( 'widgetEncadreConnexe', {

            button:  'Inserer box connexe' ,

            template:
                '<div class="encadre-connexe">' +
                    '<h3>Title</h3>' +
                    '<p>Content</p>' +
                '</div>',
            editables: {
                    content: {
                        selector: '.encadre-connexe',
                        allowedContent: allowedFull
                    }
                },
            allowedContent: allowedFull,

            upcast: function( element ) {
                return element.name == 'div' && element.hasClass( 'encadre-connexe' );
            }

        } );
        // Add widget
        editor.ui.addButton('widgetEncadrePromo', {
            label: 'Inserer box promo',
            command: 'widgetEncadrePromo',
            icon: this.path + 'images/encadre-promo.jpg'
        });

        editor.widgets.add( 'widgetEncadrePromo', {

            button:  'Inserer box promo' ,

            template:
                '<div class="encadre-promo">' +
                    '<p>Content <a class="btn btn-default" href="#"><span class="text">A Button</span></a></p>' +
                '</div>',
             editables: {
                    content: {
                        selector: '.encadre-promo',
                        allowedContent: allowedFull
                    }
                },
            allowedContent: allowedFull,

            upcast: function( element ) {
                return element.name == 'div' && element.hasClass( 'encadre-promo' );
            }

        } );
        editor.ui.addButton('widgetMiseEnAvant', {
            label: 'Inserer une mise avant',
            command: 'widgetMiseEnAvant',
            icon: this.path + 'images/encadre-miseenavant.jpg'
        });
        // Define the widgets
        editor.widgets.add( 'widgetMiseEnAvant', {

            button:  'Inserer une mise avant' ,

            template:
                '<blockquote  class="n_encart">' +
                   '<p>Content</p>'+
                '</blockquote>',

             editables: {
                    content: {
                        selector: '.n_encart',
                        allowedContent: allowedFull
                    }
                },
            allowedContent: allowedFull,

            upcast: function( element ) {
                return element.name == 'blockquote' && element.hasClass( 'n_encart' );
            }

        } );

        // Append the widget's styles when in the CKEditor edit page,
        // added for better user experience.
        // Assign or append the widget's styles depending on the existing setup.
        if (typeof editor.config.contentsCss == 'object') {
            editor.config.contentsCss.push(CKEDITOR.getUrl(this.path + 'contents.css'));
        }else {
            editor.config.contentsCss = [editor.config.contentsCss, CKEDITOR.getUrl(this.path + 'contents.css')];
        }
    }
 } );
