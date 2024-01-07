'use strict';

function getMessages() {
    $.ajax({
  			url: "getdata.php",
  			data: {'reqType': 'alerts'},
  			type: "POST",
  			async: true,
  			dataType: "json",
			success:function(messageData) {
				if (messageData == null) {
					// no alerts to show
				} else {
					let unreadAlerts = messageData.filter((item) => item.read == 0).length;
					if (unreadAlerts > 0) {
						$(".count").html(unreadAlerts);
						$(".count-indicator").removeClass("d-none");
						$("#markAsRead").css('pointer-events', 'auto').css('opacity', '1').removeClass("d-none");
					}
					$("#notifications").html("");
					for (let i = 0; i < messageData.length && i < 5; i++) {
						$("#notifications").append(`
						  <li class="mdc-list-item notification`+(messageData[i].read == 1 ? ' notification-read' : '')+`" role="menuitem">
							<div class="item-thumbnail item-thumbnail-icon `+messageData[i].type+`">
							  <i class="mdi mdi-`+(messageData[i].type.includes('Hi') ? 'fire' : 'fridge')+`"></i>
							</div>
							<div class="item-content d-flex align-items-start flex-column justify-content-center">
							  <h6 class="item-subject font-weight-normal">`+messageData[i].message+`</h6>
							  <small class="text-muted">`+messageData[i].time+` ago </small>
							</div>
						  </li>
						`);
					}
					if (messageData.length > 5) {
						$("#notifications").append(`
							<a href="./notifications">
							<li id="readAllNotifications" class="mdc-list-item" role="menuitem">
								<div class="item-thumbnail item-thumbnail-icon" style="background-color: rgba(122, 0, 255, 0.1)">
								  <i class="mdi mdi-email-outline" style="color: #7a00ff"></i>
								</div>
								<div class="item-content d-flex align-items-start flex-column justify-content-center">
								  <h6 class="item-subject font-weight-normal">Read all notifications</h6>
								  <small class="text-muted"></small>
								</div>
							</li>
							</a>
						`);
					}
				}
			}
		});
}