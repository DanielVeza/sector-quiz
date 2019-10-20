(function ($) {
  Drupal.behaviors.sector_media_gallery = {
    attach: function (context) {
      // TODO - clean this up after next round of sytlez.
      $('.js-quiz-answers input[name="quiz-answer"]').on('change', function() {
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
        console.log('change');
      });
    }
  }
})(jQuery);
