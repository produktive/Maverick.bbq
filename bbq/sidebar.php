<?php
$cookID = Database::selectSingle("SELECT cookid FROM activecook ORDER BY cookid DESC LIMIT 1", $pdo);

exec("pgrep maverick", $pids);
if (empty($pids) || $cookID == -1) {
	$activeCook = false;
	$endCook = Database::update("UPDATE activecook SET cookid=-1", $pdo);
	$cookID = Database::selectSingle("SELECT id, end FROM cooks ORDER BY id DESC LIMIT 1", $pdo);
	$endTime = Database::selectSingle("SELECT time FROM readings WHERE cookid={$cookID['id']} ORDER BY DESC LIMIT 1", $pdo);
	if (!$cookID['end']) {
		$endCook = Database::update("UPDATE cooks SET end={$endTime} WHERE id={$cookID['id']}", $pdo);
	}
	$cookID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: $cookID['id'];
} else {
	$activeCook = true;
	$startTime = Database::selectSingle("SELECT start FROM cooks WHERE id={$cookID}", $pdo);
	$cookStarted = Database::selectSingle("SELECT time FROM readings WHERE id={$cookID} LIMIT 1", $pdo);
	$runTime = gmdate("H:i:s", time() - strtotime($startTime));
}
?>
<aside class="mdc-drawer mdc-drawer--dismissible mdc-drawer--open">
  <div class="mdc-drawer__header">
    <a href="./" class="brand-logo align-items-center" style="display:flex !important; justify-content: center">
      <img src="assets/images/logo.png" alt="logo">
	  <span>Maverick.bbq</span>
    </a>
  </div>
  <div class="mdc-drawer__content">
    <div class="mdc-list-group">
      <nav class="mdc-list mdc-drawer-menu">
        <div class="mdc-list-item mdc-drawer-item">
          <a class="mdc-drawer-link" href="./">
            <i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">home</i>
            Dashboard
          </a>
        </div>
        <div class="mdc-list-item mdc-drawer-item">
          <a class="mdc-drawer-link" href="./cooks">
            <i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">restaurant</i>
            Cooks
          </a>
        </div>
		<?php if ($_SESSION['auth']): ?>
        <div class="mdc-list-item mdc-drawer-item">
          <a class="mdc-drawer-link" href="./alerts">
            <i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">notifications</i>
            Alerts
          </a>
        </div>
        <div class="mdc-list-item mdc-drawer-item">
          <a class="mdc-drawer-link" href="./smokers">
            <i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">outdoor_grill</i>
            Smokers
          </a>
        </div>
        <div class="mdc-list-item mdc-drawer-item">
          <a class="mdc-drawer-link" href="./settings">
            <i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">settings</i>
            Settings
          </a>
        </div>
		<?php if (!$activeCook): ?>
		<div id="toggleCook" class="mdc-list-item mdc-drawer-item" style="background: #00B076">
          <a class="mdc-drawer-link" href="#" onClick="document.getElementById('startCookDialog').MDCDialog.open();">
            <i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">play_arrow</i>
            Start New Cook
          </a>
		<?php else: ?>
		<div id="toggleCook" class="mdc-list-item mdc-drawer-item" style="background: #ff420f">
          <a class="mdc-drawer-link" href="#" onClick="document.getElementById('stopCookDialog').MDCDialog.open();">
            <i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">cancel</i>
            Stop Cook
          </a>
		<?php endif; ?>
        </div>
	    <!-- Start cook popup dialog -->
	    <div id="startCookDialog" class="mdc-dialog" data-mdc-auto-init="MDCDialog">
	      <div class="mdc-dialog__container">
					<form id="startCookForm">
						<input type="hidden" name="p1" value="clicked">
	        	<div class="mdc-dialog__surface" role="alertdialog" aria-modal="true" aria-labelledby="Confirm" aria-describedby="Start New Cook">
          		<div class="mdc-dialog__content" id="startcook-dialog-content">
  		  				<h5 id="my-dialog-title">Start New Cook</h5>
        				<div class="mdc-select mdc-select--required" style="width:100%" data-mdc-auto-init="MDCSelect">
          				<input type="hidden" name="smoker">
          				<i class="mdc-select__dropdown-icon"></i>
          				<div class="mdc-select__selected-text"></div>
          				<div class="mdc-select__menu mdc-menu-surface">
            				<ul class="mdc-list">
								<?php
								$smokers = Database::select("SELECT * FROM smokers WHERE archived=0 ORDER BY id DESC", $pdo);
							  	foreach ($smokers as $row): ?>
									<li class="mdc-list-item<?= ($smokers[0]['id'] == $row['id'] ? ' mdc-list-item--selected" aria-selected="true' : '') ?>" data-value="<?= $row['id'] ?>">
										<?= htmlspecialchars($row['desc']) ?>
									</li>
								<?php endforeach; ?>
            				</ul>
          				</div>
          				<span class="mdc-floating-label">Choose Smoker</span>
          				<div class="mdc-line-ripple"></div>
        				</div>
						<div id="startCookTextArea" style="max-width:100%;width:560px;height:12em;font-size:1rem;font-family:inherit"></div>
          		</div>
	          	<div class="mdc-dialog__actions">
	            	<button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="cancel">
	              	<div class="mdc-button__ripple"></div>
	              	<span class="mdc-button__label">Cancel</span>
	            	</button>
	            	<button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="start">
	              	<div class="mdc-button__ripple"></div>
	              	<span class="mdc-button__label">Start</span>
	            	</button>
	          	</div>
	        	</div>
					</form>
	      </div>
	      <div class="mdc-dialog__scrim"></div>
	    </div>
	    <!-- end dialog -->
		<!-- Start complete cook confirmation dialog -->
		<div id="stopCookDialog" class="mdc-dialog" data-mdc-auto-init="MDCDialog">
		  <div class="mdc-dialog__container">
		    <div class="mdc-dialog__surface"
		      role="alertdialog"
		      aria-modal="true"
		      aria-labelledby="stop-dialog-title"
		      aria-describedby="Stop Cook">
		      <div class="mdc-dialog__content" id="deletecook-dialog-content">
		       	 Are you sure you want to stop recording the current cook session?
		      </div>
		      <div class="mdc-dialog__actions">
		        <button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="cancel">
		          <div class="mdc-button__ripple"></div>
		          <span class="mdc-button__label">Cancel</span>
		        </button>
		        <button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="stopcook">
		          <div class="mdc-button__ripple"></div>
		          <span class="mdc-button__label">End Session</span>
		        </button>
		      </div>
		    </div>
		  </div>
		  <div class="mdc-dialog__scrim"></div>
		</div>
		<!-- End complete cook confirmation dialog -->
		<?php endif; ?>
      </nav>
    </div>

	<?php if ($_SESSION['auth']): ?>
	<div class="profile-actions">
	  <a href="?action=logout" style="font-size:.8em !important">Logout</a>
	</div>
	<?php else: ?>
  	<div class="mt-5 mdc-card premium-card" style="background: #fff">
        <div class="d-flex align-items-center">
          <div>
            <p class="mb-4 mdc-typography--button" style="color: #000">Login</p>
          </div>
        </div>
  	  <form method="POST">
            <div class="mdc-layout-grid__cell mb-3 stretch-card mdc-layout-grid__cell--span-12">
              <div class="mdc-text-field mdc-text-field--outlined search-text-field">
                <input class="mdc-text-field__input" id="text-field-hero-pass" name="password" type="password">
                <div class="mdc-notched-outline">
                  <div class="mdc-notched-outline__leading"></div>
                  <div class="mdc-notched-outline__notch">
                    <label for="text-field-hero-pass" class="mdc-floating-label">Password</label>
                  </div>
                  <div class="mdc-notched-outline__trailing"></div>
                </div>
              </div>
            </div>
  		  <button class="mdc-button mdc-button--raised filled-button--dark" style="width:100%">
  		    Login
  		  </button>
        </form>
      </div>
  	<?php endif; ?>
  </div>
    </aside>