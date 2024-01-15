<?php
session_start();
if (!$_SESSION['auth']) {
	http_response_code(403);
	include 'pages/samples/403.html';
	exit;
}

define('ROOT_PATH', dirname(__DIR__) . '/');
require ROOT_PATH.'bbq/header.php';
require ROOT_PATH.'bbq/db.php';

if (!empty($_POST)) {
	// user has pressed the save settings button, update settings
	$alerts = filter_input(INPUT_POST, 'alertsEnabled') == 'on' ? 'on' : 'off';
	$bbqLow = filter_input(INPUT_POST, 'bbqLow', FILTER_VALIDATE_INT, ["options" => ["default" => 0, "min_range" => 0, "max_range" => 500]]);
	$bbqHigh = filter_input(INPUT_POST, 'bbqHigh', FILTER_VALIDATE_INT, ["options" => ["default" => 0, "min_range" => 0, "max_range" => 500]]);
	$foodLow = filter_input(INPUT_POST, 'foodLow', FILTER_VALIDATE_INT, ["options" => ["default" => 0, "min_range" => 0, "max_range" => 500]]);
	$foodHigh = filter_input(INPUT_POST, 'foodHigh', FILTER_VALIDATE_INT, ["options" => ["default" => 0, "min_range" => 0, "max_range" => 500]]);
	$alertLimit = filter_input(INPUT_POST, 'alertLimit', FILTER_VALIDATE_INT, ["options" => ["default" => 5, "min_range" => 1, "max_range" => 15]]);
	$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL, ["options" => ["default" => ""]]);
	$emailEnabled = filter_input(INPUT_POST, 'emailEnabled') == 'on' ? 'on' : 'off';
	$push = filter_input(INPUT_POST, 'pushEnabled') == 'on' ? 'on' : 'off';
	$pushDevice = filter_input(INPUT_POST, 'pushDevice') ?: 'all';
	$update = Database::update("UPDATE settings SET alerts='{$alerts}', pitLow={$bbqLow}, pitHigh={$bbqHigh}, foodLow={$foodLow}, foodHigh={$foodHigh}, emailTo='{$email}', emailEnabled='{$emailEnabled}', push='{$push}', alertLimit={$alertLimit}, pushDevice='{$pushDevice}'", $pdo);
}

// normal page load, make sure pushover user info is still valid
$pushData = Database::selectSingle("SELECT pushToken, pushUser, pushSub FROM settings", $pdo);
if ($pushData['pushToken'] && $pushData['pushUser'] && $pushData['pushSub']) {
	
	curl_setopt_array($ch = curl_init(), array(
		CURLOPT_URL => "https://api.pushover.net/1/users/validate.json",
		CURLOPT_POSTFIELDS => array(
			"token" => $pushData['pushToken'],
			"user" => $pushData['pushUser'],
		),
		CURLOPT_SAFE_UPLOAD => true,
		CURLOPT_RETURNTRANSFER => true,
	));
	$pushValid = json_decode(curl_exec($ch));
	curl_close($ch);
	
	if ($pushValid->status !== 1) {
		$update = Database::update("UPDATE settings SET pushUser=''", $pdo);
	}
	
}

$settings = Database::selectSingle("SELECT alerts, pitLow, pitHigh, foodLow, foodHigh, tempType, emailTo, emailEnabled, pushSub, pushToken, pushUser, push, alertLimit, pushDevice FROM settings", $pdo);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Maverick.bbq: Alerts</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&display=swap" rel="stylesheet">
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/demo/style.css">
	<link rel="stylesheet" href="assets/css/quill.bubble.css">
    <!-- End layout styles -->
	<link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
	<link rel="manifest" href="assets/images/site.webmanifest">
	<link rel="mask-icon" href="assets/images/safari-pinned-tab.svg" color="#7a00ff">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="msapplication-config" content="assets/images/browserconfig.xml">
	<meta name="theme-color" content="#7a00ff">
	<style>
	a {
		color: #FE2D81;
		font-size: inherit;
	}
	fieldset {
		border: 1px solid transparent;
		gap: 2em;
		padding: 1em;
	}
	fieldset.food {
		border-top-color: blue;
	}
	legend {
		border-radius: 3px;
	}
	fieldset.bbq {
		border-top-color: red;
	}
	fieldset input {
		
	}
	legend {
		margin: auto;
		padding: .33em 1em;
	}
	.card-title {
		margin-bottom: 1em !important;
	}
	.mdc-text-field {
		align-self: center;
	}
	span.mdc-text-field__icon {
		bottom: 14px !important;
	}
	</style>
  </head>
  <body>
	<script src="assets/js/preloader.js"></script>
    <div class="body-wrapper">
      <!-- partial:sidebar.php -->
      <?php include ROOT_PATH.'bbq/sidebar.php'; ?>
      <!-- partial -->
      <div class="main-wrapper mdc-drawer-app-content">
        <!-- partial:navbar.php -->
        <?php include ROOT_PATH.'bbq/navbar.php'; ?>
        <!-- partial -->
        <div class="page-wrapper mdc-toolbar-fixed-adjust">
          <main class="content-wrapper">
			<div class="mdc-layout-grid">
            <div class="mdc-layout-grid__inner">
              <div class="mdc-layout-grid__cell--span-12">
                <div class="mdc-card">
					<form action="" method="post">
						<!-- card title and enable switch -->
						<div class="d-flex justify-content-between align-items-baseline">
	                  		<h6 class="card-title">Temperature Alerts</h6>
					  		<div class="mdc-switch" data-mdc-auto-init="MDCSwitch">
			                  <div class="mdc-switch__track"></div>
				                  <div class="mdc-switch__thumb-underlay">
				                    <div class="mdc-switch__thumb">
				                        <input type="checkbox" id="alertsEnabled" name="alertsEnabled" class="mdc-switch__native-control" role="switch"<?=$settings['alerts'] == 'on' ? ' checked' : ''?>>
				                    </div>
				                  </div>
		                  	</div>
				  		</div>
				
						<p class="mdc-typography--body1">Enter 0 to disable an individual alert setting.</p>

						<div class="d-flex flex-wrap" style="justify-content:space-evenly;gap:1em">
							<!-- temp preferences -->
							<div class="d-flex" style="gap:1em">
					
								<fieldset class="bbq d-flex flex-column">
									<legend>BBQ</legend>
									<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon">
									  <span class="mdc-text-field__icon">°<?=$settings['tempType'] == 'F' ? 'F' : 'C'?></span>
									  <input class="mdc-text-field__input" name="bbqHigh" type="number" value="<?=$settings['pitHigh']?>" min="0" max="500" step="1" maxlength="3"  inputMode="numeric" pattern="[0-9]*">
									  <div class="mdc-notched-outline">
										<div class="mdc-notched-outline__leading"></div>
										<div class="mdc-notched-outline__notch">
										  <label for="bbqHigh" class="mdc-floating-label">BBQ High</label>
										</div>
										<div class="mdc-notched-outline__trailing"></div>
									  </div>
									</div>
					  
									<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon">
									  <span class="mdc-text-field__icon">°<?=$settings['tempType'] == 'F' ? 'F' : 'C'?></span>
									  <input class="mdc-text-field__input" name="bbqLow" type="number" value="<?=$settings['pitLow']?>" min="0" max="500" step="1" maxlength="3"  inputMode="numeric" pattern="[0-9]*">
									  <div class="mdc-notched-outline">
										<div class="mdc-notched-outline__leading"></div>
										<div class="mdc-notched-outline__notch">
										  <label for="bbqLow" class="mdc-floating-label">BBQ Low</label>
										</div>
										<div class="mdc-notched-outline__trailing"></div>
									  </div>
									</div>
								</fieldset>

								<fieldset class="food d-flex flex-column">
									<legend>Food</legend>
								  	<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon">
									  <span class="mdc-text-field__icon">°<?=$settings['tempType'] == 'F' ? 'F' : 'C'?></span>
									  <input class="mdc-text-field__input" name="foodHigh" type="number" value="<?=$settings['foodHigh']?>" min="0" max="500" step="1" maxlength="3" inputMode="numeric" pattern="[0-9]*">
									  <div class="mdc-notched-outline">
									    <div class="mdc-notched-outline__leading"></div>
									    <div class="mdc-notched-outline__notch">
										  <label for="bbqHigh" class="mdc-floating-label">Food High</label>
									    </div>
									    <div class="mdc-notched-outline__trailing"></div>
									  </div>
								    </div>
							
								  	<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon">
									  <span class="mdc-text-field__icon">°<?=$settings['tempType'] == 'F' ? 'F' : 'C'?></span>
									  <input class="mdc-text-field__input" name="foodLow" type="number" value="<?=$settings['foodLow']?>" min="0" max="500" step="1" maxlength="3"  inputMode="numeric" pattern="[0-9]*">
									  <div class="mdc-notched-outline">
									    <div class="mdc-notched-outline__leading"></div>
									    <div class="mdc-notched-outline__notch">
										  <label for="foodLow" class="mdc-floating-label">Food Low</label>
									    </div>
									    <div class="mdc-notched-outline__trailing"></div>
									  </div>
								    </div>
								</fieldset>
						
							</div>
				
							<!-- alert preferences -->
							<div class="flex-grow-1">
								<div class="d-flex flex-column" style="gap:1em">
								
									<div class="mdc-select flex-grow-1" data-mdc-auto-init="MDCSelect">
									  <input type="hidden" id="alertLimit" name="alertLimit" value="<?=$settings['alertLimit'] ?: '1'?>">
									  <span class="mdc-select__dropdown-icon"></span>
									  <div class="mdc-select__selected-text"><?=$settings['alertLimit'] > 1 ? $settings['alertLimit'] . ' minutes' : '1 minute'?></div>
									  <div class="mdc-select__menu mdc-menu-surface">
										<ul class="mdc-list">
										  <li class="mdc-list-item" data-value="1">
											1 minute 
										  </li>
										  <li class="mdc-list-item" data-value="5">
											5 minutes
										  </li>
										  <li class="mdc-list-item" data-value="10">
											10 minutes
										  </li>
										  <li class="mdc-list-item" data-value="15">
											15 minutes
										  </li>
										</ul>
									  </div>
									  <span class="mdc-floating-label">Maximum alert frequency</span>
									  <div class="mdc-line-ripple"></div>
									</div>
									
									<!-- email input and checkbox -->
									<div class="d-flex" style="gap:1em">
										
									    <div class="mdc-text-field mdc-text-field--outlined flex-grow-1">
										  	<input class="mdc-text-field__input" id="email" name="email" value="<?=$settings['emailTo']?>">
										  	<div class="mdc-notched-outline">
										    	<div class="mdc-notched-outline__leading"></div>
										    	<div class="mdc-notched-outline__notch">
											  		<label for="email" class="mdc-floating-label">E-mail</label>
										    	</div>
										    	<div class="mdc-notched-outline__trailing"></div>
										  	</div>
									    </div>
									
										<div class="mdc-form-field">
										  	<div class="mdc-checkbox">
												<input type="checkbox" class="mdc-checkbox__native-control" id="emailEnabled" name="emailEnabled" <?=$settings['emailEnabled'] == 'on' ? 'checked ' : ''?>/>
												<div class="mdc-checkbox__background">
											  	  	<svg class="mdc-checkbox__checkmark" viewBox="0 0 24 24">
														<path class="mdc-checkbox__checkmark-path" fill="none" d="M1.73,12.91 8.1,19.28 22.79,4.59" />
											  		</svg>
											  	  	<div class="mdc-checkbox__mixedmark"></div>
												</div>
										  	</div>
										</div>
										
									</div>
									
									<!-- pushover info and checkbox -->
									<div class="d-flex flex-column" style="gap:1em">
										<div class="d-flex" style="gap:1em">
											<div class="d-flex flex-wrap" style="align-items:center;flex-grow:1;gap:1em">
												<?php if ($settings['pushToken'] == "" || $settings['pushUser'] == ""): ?>
												  <span class="mdc-typography--body1">Enable Pushover in <a href="./settings">settings</a>.</span>
											  	<?php else: ?>
												  <input type="hidden" id="pushToken" name="pushToken" value="<?=$settings['pushToken']?>">
												  <div class="mdc-select flex-grow-1" data-mdc-auto-init="MDCSelect">
													  <input type="hidden" id="pushDevice" name="pushDevice" value="<?=$settings['pushDevice']?>">
													  <span class="mdc-select__dropdown-icon"></span>
													  <div class="mdc-select__selected-text"><?=$settings['pushDevice']?></div>
													  <div class="mdc-select__menu mdc-menu-surface">
														<ul class="mdc-list">
														<li class="mdc-list-item" data-value="all">All devices</li>
														<?php
														foreach ($pushValid->devices as $i) {
														  echo "<li class=\"mdc-list-item\" data-value=\"{$i}\">{$i}</li>";
														}
														 ?>
														</ul>
													  </div>
													  <span class="mdc-floating-label">Pushover notifications</span>
													  <div class="mdc-line-ripple"></div>
												  </div>
											  	<?php endif; ?>
											</div>
										
											<div class="mdc-form-field">
											  <div class="mdc-checkbox<?=$settings['pushUser'] == '' ? ' mdc-checkbox--disabled' : ''?>">
												<input type="checkbox" class="mdc-checkbox__native-control" id="pushEnabled" name="pushEnabled"
													<?=$settings['push'] == 'on' && $settings['pushUser'] !== '' ? 'checked ' : ''?><?=$settings['pushUser'] == '' ? 'disabled ' : ''?>/>
												<div class="mdc-checkbox__background">
												  <svg class="mdc-checkbox__checkmark" viewBox="0 0 24 24">
													<path class="mdc-checkbox__checkmark-path" fill="none" d="M1.73,12.91 8.1,19.28 22.79,4.59" />
												  </svg>
												  <div class="mdc-checkbox__mixedmark"></div>
												</div>
											  </div>
											</div>
										
										</div>
									</div>
									
								</div>
							</div>
						</div>
						
						<div class="text-right" style="margin-top:1em">
						  <button type="submit" class="mdc-button mdc-button--raised">
							Save Settings
						  </button>
						</div>
					</form>
                </div>
              </div>
            </div>
          </div>
		  </main>
          <!-- partial:footer.php -->
          <?php include ROOT_PATH.'bbq/footer.php'; ?>
          <!-- partial -->
        </div>
      </div>
    </div>
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page-->
    <!-- End plugin js for this page-->
    <!-- inject:js -->
    <script src="assets/js/material.js"></script>
    <script src="assets/js/misc.js"></script>
	<script src="assets/js/messages.js"></script>
	<script src="assets/js/quill.min.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
	<script>
		<?php if ($_SESSION['auth']): ?>
		var quill = new Quill('#startCookTextArea', {
		  bounds: '#startCookTextArea',
		  modules: {
		    toolbar: [
		      ['link'],
		    ]
		  },
		  placeholder: 'Cook description',
		  theme: 'bubble'  // or 'bubble'
		});
		
		var tooltip = quill.theme.tooltip;
		var input = tooltip.root.querySelector("input[data-link]");
		input.dataset.link = 'https://example.com';
		<?php endif; ?>
		$(function () {
		
			<?php if ($activeCook): ?>
			window.refreshInterval = setInterval(getMessages, 12000);
			<?php endif; ?>
		});
	</script>
    <!-- End custom js for this page-->
  </body>
</html>