<?php
define('ROOT_PATH', dirname(__DIR__) . '/');
require ROOT_PATH.'bbq/header.php'; 
require ROOT_PATH.'bbq/db.php';

if (filter_input(INPUT_POST, 'deleteCook', FILTER_VALIDATE_INT) && $_SESSION['auth']) {
	$cookid=$_POST["deleteCook"];
	$query=Database::delete("DELETE FROM cooks WHERE id=".$cookid, $pdo);
	$query=Database::delete("DELETE FROM readings WHERE cookid=".$cookid, $pdo);
	$query=Database::delete("DELETE FROM alerts WHERE cookid=".$cookid, $pdo);
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Maverick.bbq: Cook History</title>
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
		.ellipsis {
				min-width: 20em;
		    position: relative;
		}
		.ellipsis:before {
		    content: '';
		    display: inline-block;
		}
		.ellipsis span {
		    position: absolute;
				width: 100%;
		    white-space: nowrap;
		    overflow: hidden;
		    text-overflow: ellipsis;
		}
		.mdc-button__icon {
			padding: 4px;
		}
		[id^="cookRow"] {
			cursor: pointer;
		}
		#cancel-search {
			cursor: pointer;
			display: none;
			pointer-events: auto;
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
                    <h6 class="card-title card-padding pb-0">Cooks</h6>
										
										
										<!-- Search bar -->
										<div class="mdc-top-app-bar" style="background-color: #fff; z-index: 0">
											<div class="mdc-top-app-bar__row">
							          <div class="mdc-top-app-bar__section">
													<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-leading-icon mdc-text-field--with-trailing-icon search-text-field d-md-flex" style="width: 100%">
							        			<i class="material-icons mdc-text-field__icon">search</i>
														<i id="cancel-search" class="material-icons mdc-text-field__icon">cancel</i>
							        			<input class="mdc-text-field__input" id="text-field-hero-search">
							        			<div class="mdc-notched-outline mdc-notched-outline--upgraded">
							          			<div class="mdc-notched-outline__leading"></div>
							          			<div class="mdc-notched-outline__notch">
							            			<label for="text-field-hero-search" class="mdc-floating-label" style="">Search...</label>
							          			</div>
							         				<div class="mdc-notched-outline__trailing"></div>
							        			</div>
							      			</div>
												</div>
											</div>
										</div>
										<!-- End search bar -->

                    <div class="table-responsive">
                      <table class="table table-hoverable w-auto">
                        <thead>
                          <tr>
                            <th class="text-left" width="1%" style="white-space:nowrap">Date</th>
                            <th class="text-left">Description</th>
														<th class="text-left" width="1%" style="white-space:nowrap"></th>
                          </tr>
                        </thead>
                        <tbody>
						<?php
						  $results = Database::select("SELECT * FROM cooks ORDER BY id DESC", $pdo);
						  foreach ($results as $row) {
							$t = strtotime($row['start']);
						?>
						<tr id="cookRow<?=$row['id']?>">
							<td class="text-left date-column"><?=date('m',$t)."/".date('d',$t)."/".date('y',$t)." ".date('h',$t).":".date('ia',$t)?></td>
							<td class="text-left ellipsis"><span><?=strip_tags(str_replace('<br>', ' ', $row['note']))?></span></td>
							<td>
							<?php if ($_SESSION['auth']) : ?>
		            	<button class="mdc-button mdc-button--raised icon-button filled-button--secondary" id="deleteCook<?=$row['id']?>" value="<?=$row['id']?>" data-time="<?= $row['start']?>">
		               	<i class="material-icons mdc-button__icon">delete</i>
		              </button>
							<?php endif; ?>
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
		      aria-labelledby="stopcook-dialog-title"
		      aria-describedby="Stop Cook">
		      <div class="mdc-dialog__content" id="deletecook-dialog-content">
		        Delete cook session?
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
    <!-- End plugin js for this page-->
    <!-- inject:js -->
    <script src="assets/js/material.js"></script>
    <script src="assets/js/misc.js"></script>
	<script src="assets/js/messages.js"></script>
	<?=$_SESSION['auth'] ? '<script src="assets/js/quill.min.js"></script>' : ''?>
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
	$(function() {
			 
		$('[id^=deleteCook]').css("visibility", "hidden");

		$('[id^=deleteCook]').click(function() {
			dd = document.getElementById('deleteDialog');
			dd.dataset.id = this.value;
			dd.dataset.time = this.dataset.time;
			$("#deletecook-dialog-content", dd).html("Delete cook session " + this.parentNode.parentNode.childNodes[1].innerHTML + "?");
			dd.MDCDialog.open();
		});

		$('tr[id^=cookRow]').hover(function() {
			$('#deleteCook'+$(this).attr('id').match(/\d+/)).css("visibility", "visible");
		},
		function() {
			$('#deleteCook'+$(this).attr('id').match(/\d+/)).css("visibility", "hidden");
		});
		
	  $("tr[id^=cookRow]").click(function(e) {
			if (e.target.tagName == 'BUTTON' || e.target.tagName == 'I') return
	     window.location = "./?id=" + this.id.substring(7);
	  });
		 
 		$("#deleteDialog").on("MDCDialog:closing", function(event) {
 			if (event.detail.action == "delete") {
				$.ajax({
					url:'cooks.php',
					type:'POST',
					data: 'deleteCook='+this.dataset.id,
				});
				$('#cookRow'+this.dataset.id).remove();
 			}
 		});
		
		$("#text-field-hero-search").on("input", function() {	
		  filter = $(this).val().toUpperCase();
		  items = $("[id^=cookRow]");
			
			if (this.value == "") {
				items.show();
				$("#cancel-search").hide();
				return;
			}
			
			items.hide();
			$("#cancel-search").show();
		  items.filter(function(i) {
		  	text = $(this).children().eq(0).text().toUpperCase() + $(this).children().eq(1).text().toUpperCase();
				return text.indexOf(filter) > -1;
			})
			.show();
		});
		
		$("#cancel-search").click(function() {
			$(this).hide();
			$("#text-field-hero-search").val("").trigger("input").focus();
		});
		
		<?php if ($activeCook): ?>
		window.refreshInterval = setInterval(getMessages, 12000);
		<?php endif; ?>
		
	});
	</script>
    <!-- End custom js for this page-->
  </body>
</html>