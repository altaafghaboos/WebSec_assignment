// jQuery filtering for the services page. Requires internet for CDN during local demo.
if (window.jQuery) {
  jQuery(function ($) {
    $('.filter').on('click', function () {
      var filter = $(this).data('filter');
      $('.filter').removeClass('active'); $(this).addClass('active');
      $('.service-card').each(function () {
        $(this).toggle(filter === 'all' || $(this).data('status') === filter);
      });
      $('.empty').prop('hidden', $('.service-card:visible').length !== 0);
    });
  });
}
