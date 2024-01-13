(function ($) {
  'use strict';
  $(function () {
    var sidebar = $('.mdc-drawer-menu');
    var body = $('body');

    if($('.mdc-drawer').length) {
      var drawer = mdc.drawer.MDCDrawer.attachTo(document.querySelector('.mdc-drawer'));
      // toggler icon click function
      document.querySelector('.sidebar-toggler').addEventListener('click', function () {
        drawer.open = !drawer.open;
      });
    }

    // Initially collapsed drawer in below desktop
    if(window.matchMedia('(max-width: 991px)').matches) {
      if(document.querySelector('.mdc-drawer.mdc-drawer--dismissible').classList.contains('mdc-drawer--open')) {
        document.querySelector('.mdc-drawer.mdc-drawer--dismissible').classList.remove('mdc-drawer--open'); 
      }
    }

    //Add active class to nav-link based on url dynamically
    //Active class can be hard coded directly in html file also as required
    var current = location.pathname.split("/").slice(-1)[0].replace(/^\/|\/$/g, '');
    $('.mdc-drawer-item .mdc-drawer-link', sidebar).each(function () {
      var $this = $(this);
      if (current === "") {
        //for root url
        if ($this.attr('href') == "./") {
          $(this).addClass('active');
          if ($(this).parents('.mdc-expansion-panel').length) {
            $(this).closest('.mdc-expansion-panel').addClass('expanded');
          }
        }
      } else {
        //for other url
        if ($this.attr('href').indexOf(current) !== -1) {
          $(this).addClass('active');
          if ($(this).parents('.mdc-expansion-panel').length) {
            $(this).closest('.mdc-expansion-panel').addClass('expanded'); 
            $(this).closest('.mdc-expansion-panel').show();
          }
        }
      }
    });

    // Toggle Sidebar items
    $('[data-toggle="expansionPanel"]').on('click', function () {
      // close other items
      $('.mdc-expansion-panel').not($('#' + $(this).attr("data-target"))).hide(300);
      $('.mdc-expansion-panel').not($('#' + $(this).attr("data-target"))).prev('[data-toggle="expansionPanel"]').removeClass("expanded");
      // Open toggle menu
      $('#' + $(this).attr("data-target")).slideToggle(300, function() {
        $('#' + $(this).attr("data-target")).toggleClass('expanded');
      });
    });


    // Add expanded class to mdc-drawer-link after expanded
    $('.mdc-drawer-item .mdc-expansion-panel').each(function () {
      $(this).prev('[data-toggle="expansionPanel"]').on('click', function () {
        $(this).toggleClass('expanded');
      })
    });

    //Applying perfect scrollbar to sidebar
    if (!body.hasClass("rtl")) {
      if ($('.mdc-drawer .mdc-drawer__content').length) {
        const chatsScroll = new PerfectScrollbar('.mdc-drawer .mdc-drawer__content');
      }
    }
		
		$("#startCookDialog").on("MDCDialog:closing", function(event) {
			if (event.detail.action == "start") {
				let toggleCook = $("#toggleCook");
				toggleCook.css('pointer-events', 'none');
				toggleCook.css('opacity', '0.6');
				let data = $("#startCookForm").serializeArray();
				data.push({name: "note", value: quill.root.innerHTML});
				$.ajax({
								url: 'togglecook.php',
								type: 'POST',
								data: $.param(data),
								success: function(data) {
									window.location = document.URL.replace(/#$/, '');
								},
								error: function (request, status, error) {
									toggleCook.css('pointer-events', 'auto');
									toggleCook.css('opacity', '1');
									alert(request.responseText);
								}
							});
			} else {
				quill.deleteText(0, quill.getLength());
			}
		});
		
		$("#stopCookDialog").on("MDCDialog:closing", function(event) {
			if (event.detail.action == "stopcook") {
				let toggleCook = $("#toggleCook");
				toggleCook.css('pointer-events', 'none');
				toggleCook.css('opacity', '0.6');
				$.ajax({
					url: 'togglecook.php',
					type: 'POST',
					data: 'p1=clicked',
					success: function(data) {
						window.location = document.URL.replace(/#$/, '');
					},
					error: function (request, status, error) {
						toggleCook.css('pointer-events', 'auto');
						toggleCook.css('opacity', '1');
						alert(request.responseText);
					}
				});
			}
		});
		
		$("#markAsRead").click(function() {
			$.ajax({
				url: 'getdata.php',
				type: 'POST',
				data: 'markread=true',
				success: function(data) {
					$(".count-indicator").addClass("d-none");
					$("#markAsRead").css('pointer-events', 'none');
					$("#markAsRead").css('opacity', '0.6');
					$(".notification").addClass("notification-read");
				},
				error: function (request, status, error) {
					alert(request.responseText);
				}
			});
		});
		
		$("#readAllNotifications").click(function(e) {
			location.href = "./notifications";
		});
		
		$("#editCookDialog").on("MDCDialog:closing", function(event) {
			if (event.detail.action == "save") {
				let data = $("#editCookForm").serializeArray();
				data.push({name: "note", value: quillEdit.root.innerHTML});
				$.ajax({
					url: 'togglecook.php',
					type: 'POST',
					data: $.param(data),
					success: function(data) {
						$("#cookNote").html(quillEdit.root.innerHTML);
					},
					error: function (request, status, error) {
						toggleCook.css('pointer-events', 'auto');
						toggleCook.css('opacity', '1');
						alert(request.responseText);
					}
				});
			} else {
				quillEdit.setContents(delta, 'silent');
			}
		});

  });
})(jQuery);