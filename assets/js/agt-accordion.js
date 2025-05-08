jQuery(function ($) {
  $('.agt-accordion .agt-body').hide();

  $('.agt-accordion .agt-item.open .agt-body').show().end().find('.agt-item.open .agt-icon').text('-');

  $('.agt-accordion').on('click', '.agt-header', function () {
    var $item = $(this).closest('.agt-item');

    if (!window.AGT_multiOpen) {
      $item.siblings('.open').removeClass('open').find('.agt-body').slideUp(window.AGT_animSpeed).end().find('.agt-icon').text('+');
    }

    $item.toggleClass('open');
    $item.find('.agt-body').slideToggle(window.AGT_animSpeed);
    $item.find('.agt-icon').text($item.hasClass('open') ? '−' : '+');
  });
});
