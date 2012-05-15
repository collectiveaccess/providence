(function($) {
  
  $.fn.placeholder = function(opts) {
    var defaults = {
      message: '...',
      className: 'placeholder',
      clearClassName: 'show_cancel_search'
    };
    var options = $.extend({}, defaults, opts);
    
    var setPlaceholder = function($input) {
      $input.val($input.attr('placeholder') || options.message);
      $input.addClass(options.className);
    };
    
    return this.each(function() {
      var $this = $(this);

      if ($this.attr('type') == 'search') return;
      
      $this.bind('blur', function() {
        if ($this.val() == '') {
          setPlaceholder($this);
        }
      }).bind('focus', function() {
        if ($this.val() == ($this.attr('placeholder') || options.message)) {
          $this.val('');
        }
        $this.removeClass(options.className);
      }).bind('keyup', function() {
        var searchVal = $this.val();
        if (searchVal != '' && searchVal != options.message) {
          $this.parent().addClass(options.clearClassName);
        } else {
          $this.parent().removeClass(options.clearClassName);
        }
      });
      _.defer(function(){
        $this.keyup().blur();
      });
    });
    
  };
  
})(jQuery);