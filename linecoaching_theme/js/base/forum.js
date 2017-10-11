(function ($, Drupal) {

    'use strict';

  Drupal.behaviors.linecoachingForumTabs = {
    attach: function (context, settings) {
          $(document).ready(function() {
                //click on pagination of forum members
            $('ul#forum-nav li.nav-item').once('forum-tabs').on('click', function(event) {
                if (!$(this).attr('data-ref')) {
                    $('.forum .action-links').hide();
                }
                else {
                    $('.forum .action-links').show();
                }
                if($('span.nav-link',$(this)).hasClass('forum-front')) {
                    document.location.href = $('span.nav-link',$(this)).attr('data-ref');
                }
                $('ul#forum-nav .nav-item .nav-link').removeClass('active');
                    var $this = this;
                    $('.region-content article.forum-container').animate({
                        'opacity': 0
                    }, 800, function() {
                        $('.nav-link', $($this)).addClass('active');
                var url = jQuery($this).find("span").attr('data-href');
                $.get( url ).done(function( data ) {
                $('.region-content article.forum-container').html(data.content);
                 Drupal.attachBehaviors(context);

                $('.region-content article.forum-container').animate({
                                'opacity': 1
                            }, 800, function() {} );
                });

                });
            event.preventDefault();
            return false;
           });
         });
    }
  };


  Drupal.behaviors.linecoachingForumComment = {
      attach: function(context, settings) {
          if($('.forum-comment-actions').length) {
              $('.comment-add a', $('.forum-comment-actions')).on('click', function(event) {
                  $('.forum-comment-form').removeClass('hidden');
              });
          }
          if($('.forum-comments-list').length) {
              var list_wrapper = $('.forum-comments-list section');
            if ($('nav.pager').length < 2) {
              $('nav.pager').clone().prependTo($(list_wrapper));
            }
          }
      }
  };
  
  Drupal.behaviors.linecoachingForumTabsComment = {
    attach: function(context, settings) {
        var list_wrapper = $('article.forum-container > .forum-subject');
        if ($('nav.pager').length < 2) {
          $('nav.pager').clone().prependTo($(list_wrapper));
        }
    }
  };
  
  Drupal.behaviors.linecoachingForumMembers = {
          attach: function(context, settings) {
            // forum memmber pagination
              if ($('.all-members nav.pager ul li.pager__item').parents('ul').length) {
                  $('.all-members nav.pager ul li.pager__item').first().find('a').attr('href','/');
              }
              $('.all-members nav.pager ul li.pager__item a').on('click', function(event) {
                  //check if search value exists
                  var searchVal = $('form#search-member-form  #search-member-forum').val();
                  var  url  = $('#forum-nav .forum-membre').attr('data-href') + $(this).attr('href');
                  if(searchVal){
                      var url = $('#forum-nav .forum-membre').attr('data-href') +'/' + searchVal + $(this).attr('href');
                  }
                  $.get( url ).done(function( data ) {
                      $('.region-content article.forum-container').html(data.content);
                      $('form#search-member-form  #search-member-forum').val(searchVal);
                       Drupal.attachBehaviors(context);
                  });
                  event.preventDefault();
              });
             //search member submit form
              $(".all-members form#search-member-form").submit(function(event) {
                  //prevent Default functionality
                  var searchVal = $('form#search-member-form  #search-member-forum').val();
                  var url = $('#forum-nav .forum-membre').attr('data-href')+'/' + searchVal;
                  console.log(url);
                  $.get( url ).done(function( data ) {
                      $('.region-content article.forum-container').html(data.content);
                      $('form#search-member-form  #search-member-forum').val(searchVal);
                      Drupal.attachBehaviors(context);
                  })
                  return false;
              });
          }
      };

  Drupal.behaviors.linecoachingForumSujetPager = {
          attach: function(context, settings) {
            //forum memmber pagination
              $('.forum-subject nav.pager ul li.pager__item a').once('paginationForum').on('click', function(event) {
                  //check if search value exists
                  var searchVal = $('a#search_url').attr('data-href');
                  var  url  = $('a#search_url').attr('data-href') + '/' + $(this).attr('href');
                  $.get( url ).done(function( data ) {
                      $('.region-content article.forum-container').html(data.content);
                       Drupal.attachBehaviors(context);
                  });
                  event.preventDefault();
              });
          }
      };
  Drupal.behaviors.linecoachingForumMarkAsRead = {
          attach: function(context, settings) {
            //forum mark as read
              $('.action-links a#mark-as-read').on('click', function(event) {
                  var  url  =  $(this).attr('data-href');
                  $.get( url ).done(function( data ) {
                      location.reload();
                  });
                  event.preventDefault();
              });
          }
      };

  Drupal.behaviors.massageQoute = {
    attach: function(context, settings) {

      var textButton = Drupal.t('Quote');
      var quote_count = $('.forum-comment-action-wrapper ul.forum .comment-quote');
      if (!quote_count.length) {
        var quote = $('.forum-comment-action-wrapper ul.forum').append("<li class='comment-quote'><a href=''>" + textButton + "</a></li>");
        var quote_button = $('.forum-comment-action-wrapper ul.forum .comment-quote');
        quote_button.click(function (e) {
          e.preventDefault();
          var text = $(this).parents('.c_text-rem').find("div.forum-comment-text").html();
          var userName = $(this).parents('.author-comments').find('a[typeof="schema:Person"]').text().trim();
          var submitted = $(this).parents('.author-comments').find('span.pull-right').text().trim();
          $('html, body').animate({
            scrollTop: $(".form-item-comment-body-0-value").offset().top - 104
          }, 500);
          var editor = CKEDITOR.instances['edit-comment-body-0-value'];
          editor.setData('<blockquote><sup>' + userName + ' ' + submitted + '</sup>' + text + '</blockquote><p>&nbsp;</p>');
        });
      }
    }
  };

    Drupal.behaviors.linecoachingUnfollovChecker = {
        attach: function(context, settings) {
            var checker = $('.forum .unfollow-check');
            checker.once('unfollowCheck').change(function (e) {
                var hidden  = $("input[type=hidden][name=unfollow_topics]");
                var values = hidden.val();
                if(this.checked) {
                    var formPost = $(this).closest('tr').find('td[data-node-id]').attr('data-node-id');
                    if(values) {
                        hidden.val(values + ',' + formPost);
                    }
                    else {
                        hidden.val(formPost);
                    }
                }
                else {
                    var deletePost = $(this).closest('tr').find('td[data-node-id]').attr('data-node-id');
                    var value_arr = values.split(',');
                    value_arr = jQuery.grep(value_arr, function(value) {
                        return value != deletePost;
                    });
                    var new_values = value_arr.join(',');
                    hidden.val(new_values);
                }
            });
        }
    };

})(jQuery, Drupal);


