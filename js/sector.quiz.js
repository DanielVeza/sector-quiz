(function ($) {
  Drupal.behaviors.sector_media_gallery = {
    attach: function (context) {
      $('.js-feedback-wrapper').hide();
      // TODO - clean this up after next round of sytlez.
      $('.js-quiz-answers li.quiz__answer').on('click', function() {
        $(this).next('.js-feedback-wrapper').show("fast");
        $('.js-quiz-answers li.quiz__answer').hide("fast");
        if(!$(this).parents('.form-type-radio').hasClass('is-correct')) {
          $('.js-quiz-answers .form-type-radio').removeClass('is-incorrect');
          $('.js-quiz-answers .form-type-radio').next('.note--default').hide();
          $(this).parents('.form-type-radio').addClass('is-incorrect');
          $(this).parents('.form-type-radio').next('.note--default').show();
          $('.js-quiz-answers .form-type-radio.is-correct').next('.note--default').show();
          $('.js-quiz-answers .form-type-radio.is-correct').addClass('is-checked');
        }
        else {
          $('.js-quiz-answers .form-type-radio').removeClass('is-incorrect');
          $('.js-quiz-answers .form-type-radio').next('.note--default').hide();
          $(this).parents('.form-type-radio').next('.note--default').show();
        }
      });
      $('.js-quiz-answers .js-quiz-back-button').on('click', function() {
        $('.js-feedback-wrapper').hide("fast");
        $('.js-quiz-answers li.quiz__answer').show("fast");
      });
    }
  }
})(jQuery);
