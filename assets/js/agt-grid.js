jQuery(function ($) {
  console.log('agt-grid.js loaded, AGTGrid:', AGTGrid);

  $(document).on('click', '.agt-load-more', function (e) {
    console.log('Load More clicked', this);
    var $btn = $(this),
      $grid = $btn.closest('.agt-grid'),
      id = $grid.data('id'),
      page = $btn.data('page') || 2;

    console.log('→ AJAX call params:', {
      action: 'agt_load_more',
      nonce: AGTGrid.nonce,
      id: id,
      page: page,
    });

    $.post(
      AGTGrid.ajax_url,
      {
        action: 'agt_load_more',
        nonce: AGTGrid.nonce,
        id: id,
        page: page,
      },
      function (res) {
        if (res.success) {
          $grid.append(res.data.html);
          if (res.data.has_more) {
            $btn.data('page', res.data.next_page);
          } else {
            $btn.remove();
          }
        }
      }
    );
  });
});
