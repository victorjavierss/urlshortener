(function($){
   $(function(){

      const BASE = 'http://test.local/onsitewize/service/';
      var shortrow = $.templates("#filterTag");

      $('.short-url').on('click', function(e){
         e.preventDefault();
         $.post('../service/', {url:$('#url-to-short').val()}, function (response) {
            if (response.sucess) {
               var shortnenUrl = BASE + response.short;
               $('.short-url-response a').attr('href', shortnenUrl);
               $('.short-url-response a').html(shortnenUrl);
            }
         }, 'json').error(function(){

         });
      });


      $('.masthead-nav a').on('click', function(e){
         e.preventDefault();

         $('.masthead-nav li.active').removeClass('active');
         $(this.parentNode).addClass('active');


      });




   }); // end of document ready
})(jQuery); // end of jQuery name space