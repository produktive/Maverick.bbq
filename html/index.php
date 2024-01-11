<?php
define('ROOT_PATH', dirname(__DIR__) . '/');
require ROOT_PATH.'bbq/header.php';
require ROOT_PATH.'bbq/db.php';

$times = Database::select("SELECT start, end FROM cooks", $pdo);
if ($times) {
	$totalTime = 0;
	foreach ($times as $row) {
		if ($row['end'] == "" && $activeCook == true) {
			$end = time();
		} else {
			$end = strtotime($row['end']);
		}
		$totalTime += $end - strtotime($row['start']);
	}
	$stats['time'] = secondstoHumanReadable($totalTime, 3);
	$stats['cooks'] = count($times);
	$stats['readings'] = Database::selectSingle("SELECT count(time) FROM readings", $pdo);
	$stats['average'] = secondstoHumanReadable($totalTime/$stats['cooks'], 2);
	$chartColors = Database::selectSingle("SELECT pitLineColor, foodLineColor FROM settings", $pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Maverick.bbq: Dashboard</title>
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
			  <!-- stats begin -->
              <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-3-desktop mdc-layout-grid__cell--span-4-tablet stats<?= ($activeCook ? ' d-none' : '') ?>">
                <div class="mdc-card info-card info-card--success">
                  <div class="card-inner">
                    <h5 class="card-title">Cooks</h5>
                    <h4 class="font-weight-light pb-2"><?= $stats['cooks'] ?: '0' ?></h4>
                    <div class="card-icon-wrapper">
                      <i class="material-icons">restaurant</i>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-3-desktop mdc-layout-grid__cell--span-4-tablet stats<?= ($activeCook ? ' d-none' : '') ?>">
                <div class="mdc-card info-card info-card--danger">
                  <div class="card-inner">
                    <h5 class="card-title">Total Cook Time</h5>
                    <h4 class="font-weight-light pb-2"><?= $stats['time'] ?: '0' ?></h4>
                    <div class="card-icon-wrapper">
                      <i class="material-icons">schedule</i>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-3-desktop mdc-layout-grid__cell--span-4-tablet stats<?= ($activeCook ? ' d-none' : '') ?>">
                <div class="mdc-card info-card info-card--primary">
                  <div class="card-inner">
                    <h5 class="card-title">Temperature Readings</h5>
                    <h4 class="font-weight-light pb-2"><?= number_format($stats['readings']) ?: '0' ?></h4>
                    <div class="card-icon-wrapper">
                      <i class="material-icons">trending_up</i>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-3-desktop mdc-layout-grid__cell--span-4-tablet stats<?= ($activeCook ? ' d-none' : '') ?>">
                <div class="mdc-card info-card info-card--info">
                  <div class="card-inner">
                    <h5 class="card-title">Average Cook Time</h5>
                    <h4 class="font-weight-light pb-2"><?= $stats['average'] ?: '0' ?></h4>
                    <div class="card-icon-wrapper">
                      <i class="material-icons">watch</i>
                    </div>
                  </div>
                </div>
              </div>
			  <!-- stats end -->
			  <!-- live temperature data begins -->
			  <div id="foodDiv" class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-6-desktop mdc-layout-grid__cell--span-4-tablet<?= ($activeCook && $cookStarted ? '' : ' d-none') ?>">
                <div class="mdc-card text-white" style="background-color: <?=$chartColors['foodLineColor'] ?: '#008789'?>"> <!-- #008789 -->
                  <div class="d-flex justify-content-between">
                    <h3 class="font-weight-normal">Food</h3>
                    <i class="material-icons options-icon text-white">more_vert</i>
                  </div>
                  <div class="mdc-layout-grid__inner align-items-center">
                    <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-4-desktop mdc-layout-grid__cell--span-3-tablet mdc-layout-grid__cell--span-2-phone">
                      <div>
                        <h1 id="foodTemp" class="mdc-typography--headline1"></h1>
                      </div>
                    </div>
                    <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-8-desktop mdc-layout-grid__cell--span-5-tablet mdc-layout-grid__cell--span-2-phone">
                      <canvas id="food-chart" height="80"></canvas>
                    </div>
                  </div>
                </div>
              </div>
              <div id="bbqDiv" class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-6-desktop mdc-layout-grid__cell--span-4-tablet<?= ($activeCook && $cookStarted ? '' : ' d-none') ?>">
                <div class="mdc-card text-white" style="background-color: <?=$chartColors['pitLineColor'] ?: '#291A5B'?>"><!-- #291A5B -->
                    <div class="d-flex justify-content-between">
                      <h3 class="font-weight-normal">BBQ</h3>
                      <i class="material-icons options-icon text-white">more_vert</i>
                    </div>
                    <div class="mdc-layout-grid__inner align-items-center">
                      <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-4-desktop mdc-layout-grid__cell--span-3-tablet mdc-layout-grid__cell--span-2-phone">
                        <div>
                          <h1 id="bbqTemp" class="mdc-typography--headline1"></h1>
                        </div>
                      </div>
                      <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-8-desktop mdc-layout-grid__cell--span-5-tablet mdc-layout-grid__cell--span-2-phone">
                        <canvas id="bbq-chart" height="80"></canvas>
                      </div>
                    </div>
                </div>
              </div>
			  <!-- live temperature data ends -->
			  <!-- cook graph begins -->
              <div id="chartDiv" class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-12<?= ($activeCook && $cookStarted ? '' : ' d-none') ?>">
                <div class="mdc-card">
                  <div class="d-flex justify-content-between">
                    <h4 id="cookTitle" class="card-title mb-0"><!-- Cook Time & Date --></h4>
                    <div>
                        <i id="refreshChart" class="material-icons refresh-icon" id>refresh</i>
                        <i class="material-icons options-icon ml-2">more_vert</i>
                    </div>
                  </div>
                  <div class="d-block d-sm-flex justify-content-between align-items-center">
					  <p id="lastTime" class="mdc-typography--caption mdc-theme--dark"><!-- Last update --></p>
                      <div id="durationDiv" class="menu-button-container">
                        <button class="mdc-button mdc-menu-button mdc-button--raised button-box-shadow tx-12 text-dark bg-white font-weight-light">
                            Entire duration
                          <i class="material-icons">arrow_drop_down</i>
                        </button>
                        <div class="mdc-menu mdc-menu-surface" tabindex="-1">
                          <ul class="mdc-list duration-list" role="menu" aria-hidden="true" aria-orientation="vertical">
                            <li class="mdc-list-item" role="menuitem">
                              <h6 class="item-subject font-weight-normal">Last 15 minutes</h6>
                            </li>
                            <li class="mdc-list-item" role="menuitem">
                              <h6 class="item-subject font-weight-normal">Last hour</h6>
                            </li>
                            <li class="mdc-list-item" role="menuitem">
                              <h6 class="item-subject font-weight-normal">Last 3 hours</h6>
                            </li>
                            <li class="mdc-list-item" role="menuitem" style="display:none">
                              <h6 class="item-subject font-weight-normal">Entire duration</h6>
                            </li>
                          </ul>
                        </div>
                      </div>
                  </div>
                  <div class="mdc-layout-grid__inner mt-2">
                    <div class="mdc-layout-grid__cell mdc-layout-grid__cell--span-12">
                      <canvas id="bbqChart" class="bbqChart"></canvas>
                    </div>
                  </div>
				  <p id="cookNote" class="mdc-typography--body1" style="padding-top:2em"><!-- Cook Description --></p>
                </div> 
              </div>
			  <!-- cook graph ends -->
			  <!-- message/error div begins -->
              <div id="messageDiv" class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-12 d-none">
                <div class="mdc-card">
                  <div class="mdc-layout-grid__inner mt-2">
                    <div class="mdc-layout-grid__cell mdc-layout-grid__cell--span-12">
						<center><script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script><dotlottie-player id="lottieplayer" src="https://lottie.host/7a33abef-8ec4-4b1f-b3e1-01cba89ee181/fQIWZDXvNi.lottie" background speed="1" style="max-width: 600px; width: 100%" direction="1" mode="normal" loop autoplay></dotlottie-player></center>
						<h2 id="messageDivText" class="mdc-typography--headline2 text-center"></h2>
                    </div>
                  </div>
                </div> 
              </div>
			  <!-- message/error div ends -->
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
	<script src="assets/vendors/chartjs/Chart.min.js"></script>
  <script src="assets/vendors/chartjs/chartjs-adapter-date-fns.bundle.min.js"></script>
  <!-- End plugin js for this page-->
  <!-- inject:js -->
  <script src="assets/js/material.js"></script>
  <script src="assets/js/misc.js"></script>
  <?=$_SESSION['auth'] ? '<script src="assets/js/quill.min.js"></script>' : ''?>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <script src="assets/js/dashboard.js"></script>
  <?php
	  if (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)) {
	  	$cookID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
	  }
  ?>
  <script src="assets/js/chartjs.js?cookid=<?= $cookID ?>" id="chartjs"></script>
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
			getChartData();
			<?php if ($activeCook): ?>
			window.refreshInterval = setInterval(getChartData, 12000);
			<?php endif; ?>
		});
	</script>
  <!-- End custom js for this page-->
</body>
</html>
