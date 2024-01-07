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

if (filter_input(INPUT_POST, 'addSmoker')) {
	$smoker = htmlspecialchars(trim(filter_input(INPUT_POST, 'addSmoker')));
	$results = Database::update("INSERT INTO smokers VALUES (null, '{$smoker}', 0)", $pdo);
} elseif (filter_input(INPUT_POST, 'deleteSmoker', FILTER_VALIDATE_INT)) {
	$smokerid = filter_input(INPUT_POST, 'deleteSmoker', FILTER_VALIDATE_INT);
	$results = Database::select("SELECT smoker FROM cooks WHERE smoker={$smokerid}", $pdo);
	if ($results) {
		die(json_encode($results));
	} else {
		$results = Database::delete("DELETE FROM smokers WHERE id={$smokerid}", $pdo);
		die($results);
	}
} elseif (filter_input(INPUT_POST, 'archiveSmoker', FILTER_VALIDATE_INT)) {
	$smokerid = filter_input(INPUT_POST, 'archiveSmoker', FILTER_VALIDATE_INT);
	$results = Database::update("UPDATE smokers SET archived=1 WHERE id={$smokerid}", $pdo);
	die($results);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Maverick.bbq: Smokers</title>
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
		.mdc-button__icon {
			padding: 4px;
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
                <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-12">
                  <div class="mdc-card p-0">
                    <h6 class="card-title card-padding pb-0">Smokers</h6>
					
					<form action="smokers.php" method="post" class="d-flex align-items-center m-3" style="gap:1em">
					    <div class="mdc-text-field mdc-text-field--outlined" style="width:20em;max-width:100%">
						  	<input class="mdc-text-field__input" id="addSmoker" name="addSmoker" value="">
						  	<div class="mdc-notched-outline">
						    	<div class="mdc-notched-outline__leading"></div>
						    	<div class="mdc-notched-outline__notch">
							  		<label for="email" class="mdc-floating-label">Smoker Name</label>
						    	</div>
						    	<div class="mdc-notched-outline__trailing"></div>
						  	</div>
					    </div>
					
						  	<button type="submit" id="addSmokerButton" class="mdc-button mdc-button--raised" style="min-width: fit-content">
								Add Smoker
						  	</button>
			  		</form>
					
                    <div class="table-responsive">
                      <table class="table table-hoverable w-auto">
                        <thead>
                          <tr>
                            <th class="text-left" width="1%" style="white-space:nowrap">ID</th>
                            <th class="text-left">Description</th>
							<th class="text-left" width="1%" style="white-space:nowrap"></th>
                          </tr>
                        </thead>
                        <tbody>
						<?php
						  $results = Database::select("SELECT id, desc FROM smokers WHERE archived=0 ORDER BY id ASC", $pdo);
						  foreach ($results as $row) {
						?>
						<tr id="smokerRow<?=$row['id']?>">
							<td class="text-left date-column"><?=$row['id']?></td>
							<td class="text-left ellipsis"><span><?=htmlspecialchars($row['desc'])?></span></td>
							<td>
				            	<button class="mdc-button mdc-button--raised icon-button filled-button--secondary" id="deleteSmoker<?=$row['id']?>" value="<?=$row['id']?>">
				               	<i class="material-icons mdc-button__icon">delete</i>
				                </button>
							</td>
						</tr>
						<?php } ?>
                        </tbody>
                      </table>
					</div>
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
	<!-- Start delete cook confirmation dialog -->
	<div id="deleteDialog" class="mdc-dialog" data-mdc-auto-init="MDCDialog">
	  <div class="mdc-dialog__container">
	    <div class="mdc-dialog__surface"
	      role="alertdialog"
	      aria-modal="true"
	      aria-labelledby="deletesmoker-dialog-title"
	      aria-describedby="Delete Smoker">
	      <div class="mdc-dialog__content" id="deletecook-dialog-content">
	        Delete smoker?
	      </div>
	      <div class="mdc-dialog__actions">
	        <button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="cancel">
	          <div class="mdc-button__ripple"></div>
	          <span class="mdc-button__label">Cancel</span>
	        </button>
	        <button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="delete">
	          <div class="mdc-button__ripple"></div>
	          <span class="mdc-button__label">Delete</span>
	        </button>
	      </div>
	    </div>
	  </div>
	  <div class="mdc-dialog__scrim"></div>
	</div>
	<!-- End delete cook confirmation dialog -->
	
	<!-- Start archive cook confirmation dialog -->
	<div id="archiveDialog" class="mdc-dialog" data-mdc-auto-init="MDCDialog">
	  <div class="mdc-dialog__container">
	    <div class="mdc-dialog__surface"
	      role="alertdialog"
	      aria-modal="true"
	      aria-labelledby="archivesmoker-dialog-title"
	      aria-describedby="Delete Smoker">
	      <div class="mdc-dialog__content" id="archivecook-dialog-content">
	        Archive smoker?
	      </div>
	      <div class="mdc-dialog__actions">
	        <button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="cancel">
	          <div class="mdc-button__ripple"></div>
	          <span class="mdc-button__label">Cancel</span>
	        </button>
	        <button type="button" class="mdc-button mdc-dialog__button" data-mdc-dialog-action="archive">
	          <div class="mdc-button__ripple"></div>
	          <span class="mdc-button__label">Archive</span>
	        </button>
	      </div>
	    </div>
	  </div>
	  <div class="mdc-dialog__scrim"></div>
	</div>
	<!-- End archive cook confirmation dialog -->
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
			$('[id^=deleteSmoker]').css("visibility", "hidden");
			
			$('[id^=deleteSmoker]').click(function() {
				dd = document.getElementById('deleteDialog');
				ad = document.getElementById('archiveDialog');
				dd.dataset.id = this.value;
				ad.dataset.id = this.value;
				$("#deletecook-dialog-content", dd).html("Delete smoker " + this.parentNode.parentNode.childNodes[3].innerText + "?");
				dd.MDCDialog.open();
			});
			
			<?php if ($activeCook): ?>
			window.refreshInterval = setInterval(getMessages, 12000);
			<?php endif; ?>
			
			$('tr[id^=smokerRow]').hover(function() {
				$('#deleteSmoker'+$(this).attr('id').match(/\d+/)).css("visibility", "visible");
			},
			function() {
				$('#deleteSmoker'+$(this).attr('id').match(/\d+/)).css("visibility", "hidden");
			});
		
	 		$("#deleteDialog").on("MDCDialog:closing", function(event) {
				var id = this.dataset.id;
	 			if (event.detail.action == "delete") {
					$.ajax({
						url: 'smokers.php',
						type: 'POST',
						data: 'deleteSmoker='+id,
						success: function(data) {
							if (data == 'success') {
								$('#smokerRow'+id).remove();
							} else {
								data = JSON.parse(data);
								ad = document.getElementById('archiveDialog');
								$("#archivecook-dialog-content", ad).html("Can't delete smoker " + $("#smokerRow" + id + " td").eq(1).text() + ", there are " + data.length + " associated cooks. Would you like to archive it? You won't be able to use it for future cook sessions.");
								ad.MDCDialog.open();
							}
						}
					});
					
	 			}
	 		});
			
	 		$("#archiveDialog").on("MDCDialog:closing", function(event) {
				let id = this.dataset.id;
	 			if (event.detail.action == "archive") {
					$.ajax({
						url: 'smokers.php',
						type: 'POST',
						data: 'archiveSmoker='+id,
						success: function(data) {
							$('#smokerRow'+id).remove();
						}
					});
					
	 			}
	 		});
		});
	</script>
    <!-- End custom js for this page-->
  </body>
</html>