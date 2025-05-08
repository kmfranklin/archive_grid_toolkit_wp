jQuery(document).ready(function ($) {
  $('.js-agt-media-upload').on('click', function (e) {
    e.preventDefault();
    var button = $(this);
    var frame = wp.media({ title: 'Select Media', multiple: false });
    frame.on('select', function () {
      var url = frame.state().get('selection').first().toJSON().url;
      button.prev('input').val(url);
    });
    frame.open();
  });
});
