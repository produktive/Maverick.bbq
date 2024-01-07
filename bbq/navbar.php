<?php if ($activeCook): ?>
	<script>var activeCook = "<?=$activeCook?>", start_dt = "<?=$startTime?>";</script>
	<script src="assets/js/timer.js"></script>
<?php endif; ?>
<header class="mdc-top-app-bar">
        <div class="mdc-top-app-bar__row">
          <div class="mdc-top-app-bar__section mdc-top-app-bar__section--align-start">
            <button class="material-icons mdc-top-app-bar__navigation-icon mdc-icon-button sidebar-toggler">menu</button>
            <span class="mdc-top-app-bar__title d-none d-sm-block">Hello!</span>
          </div>
          <div class="mdc-top-app-bar__section mdc-top-app-bar__section--align-end mdc-top-app-bar__section-right">
			<?php if ($activeCook): ?>
			<div id="counterDiv" class="menu-button-container">
			  <span id="counter" class="p-1"><?=$runTime?></span>
			  <span id="live" class="text-white font-weight-bold bg-danger py-2 px-2 ml-1 rounded">● LIVE</span>
			</div>
			<?php endif; ?>
			<?php if ($_SESSION['auth']): ?>
            <?= ($activeCook ? '<div id="navDivider" class="divider ml-2 ml-lg-4"></div>' : ''); ?>
			<?php
				$alerts = Database::select("SELECT cookid, time, type, message, read FROM alerts ORDER BY time DESC LIMIT 99", $pdo);
				if ($alerts !== false) {
					$unreadAlerts = array_filter($alerts, function($k) {
						return $k['read'] == 0;
					});
				} else {
					$unreadAlerts = 0;
				}
			?>
            <div class="menu-button-container">
              <button class="mdc-button mdc-menu-button">
                <i class="mdi mdi-bell"></i>
                <span class="count-indicator<?=count($unreadAlerts) !== 0 ? '' : ' d-none'?>" style="width:16px; height:16px; border-radius:16px;">
                  <span class="count" style="font-size:10px"><?=count($unreadAlerts)?></span>
                </span>
              </button>
              <div class="mdc-menu mdc-menu-surface" tabindex="-1">
				<h6 class="title d-flex" style="justify-content:space-between; align-items:center; gap: 3em"> <span style="white-space:nowrap"><i class="mdi mdi-bell-outline mr-2 tx-16"></i> Notifications</span>
					<button id="markAsRead" class="mdc-button mdc-button--unelevated mdc-button--dense" style="<?=filter_var($alerts[$i]['read'], FILTER_VALIDATE_INT) == 0 ? ';pointer-events:none;opacity:0.6' : ''?>">
						 Dismiss All
					</button>
				</h6>
                <ul id="notifications" class="mdc-list" role="menu" aria-hidden="true" aria-orientation="vertical">
				<?php for ($i = 0; $i < count($alerts) && $i < 5; $i++): ?>
                  <li class="mdc-list-item notification<?=$alerts[i]['read'] == 1 ? ' notification-read' : ''?>" role="menuitem">
                    <div class="item-thumbnail item-thumbnail-icon <?=$alerts[$i]['type']?>">
                      <i class="mdi mdi-<?=strpos($alerts[$i]['type'], "Hi") ? "fire" : "fridge"?>"></i>
                    </div>
                    <div class="item-content d-flex align-items-start flex-column justify-content-center">
                      <h6 class="item-subject font-weight-normal"><?=$alerts[$i]['message']?></h6>
                      <small class="text-muted"> <?=secondsToHumanReadable(time() - strtotime($alerts[$i]['time']), 1) . ' ago'?> </small>
                    </div>
                  </li> 
				  <?php endfor; ?>
				  <?php if (count($alerts) > 5): ?>
					  <a href="./notifications">
		                  <li id="readAllNotifications" class="mdc-list-item" role="menuitem">
		                    <div class="item-thumbnail item-thumbnail-icon" style="background-color: rgba(122, 0, 255, 0.1)">
		                      <i class="mdi mdi-email-outline" style="color:#7a00ff"></i>
		                    </div>
		                    <div class="item-content d-flex align-items-start flex-column justify-content-center">
		                      <h6 class="item-subject font-weight-normal">Read all notifications</h6>
		                      <small class="text-muted"></small>
		                    </div>
		                  </li>
					  </a>
				  <?php elseif ($alerts == false): ?>
	                  <li class="mdc-list-item" role="menuitem">
	                    <div class="item-thumbnail item-thumbnail-icon">
	                      <i class="mdi mdi-email-outline"></i>
	                    </div>
	                    <div class="item-content d-flex align-items-start flex-column justify-content-center">
	                      <h6 class="item-subject font-weight-normal">No notifications!</h6>
	                      <small class="text-muted"></small>
	                    </div>
	                  </li>
				  <?php endif; ?>
                </ul>
              </div>
            </div>
			<?php endif; ?>
          </div>
        </div>
      </header>