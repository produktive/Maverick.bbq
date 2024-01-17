/* ChartJS
* -------
* Data and config for chartjs
*/
'use strict';

var lineChart, origChartData;
Chart.defaults.global.animation.duration = 500;

function getChartData(cookid) {
	
  let timePeriod = $(".duration-list > li[style*='display: none;']").index();
  
  $.ajax({
			url: "getdata.php",
			data: {'reqType': 'chart', 'cookid': cookid || $("#chartjs").attr("src").split("cookid=")[1]},
			type: "POST",
			async: true,
			dataType: "json",
			success:function(chartData) {
				if (chartData.nocooks == "true") {
					$("#messageDivText").html("Welcome! <a href='./smokers'>Add a smoker</a> and start a cook.");
					$("#messageDivText").removeClass("d-none");
					$("#lottieplayer").hide();
					$("#messageDiv").removeClass("d-none");
				} else if (typeof chartData[0].bbq[0] == "undefined") {
					// If chart has no data, determine if connecting or cook is botched and has no data
					$("#chartDiv").addClass("d-none");
					// This means it's connecting
					if (!chartData[0].end) {
						$("#messageDivText").html("Connecting, please wait...");
						$("#lottieplayer").show();
					} else {
						// No data/bad data
						$("#lottieplayer").hide();
						$("#messageDivText").html("No cook data!");
						$("#counterDiv").addClass("d-none");
						$("#navDivider").addClass("d-none");
						$(".mdc-layout-grid__inner").first().prepend($(".stats:eq(0)"), $(".stats:eq(1)"), $(".stats:eq(2)"), $(".stats:eq(3)"));
						$(".stats").removeClass("d-none");
						$("#toggleCook").html('<a class="mdc-drawer-link" href="#" onClick="document.getElementById(\'startCookDialog\').MDCDialog.open();"><i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">play_arrow</i>Start New Cook</a>');
						$("#toggleCook").css('background-color', '#00B076');
						if (typeof window.refreshInterval != "undefined") {
							clearInterval(refreshInterval);
						}
					}
					$("#messageDiv").removeClass("d-none");
				} else {
					// Data is flowing, show info
					$("#messageDiv").addClass("d-none");
					$("#chartDiv").removeClass("d-none");
					// only show food/bbq div if active cook (no end time)
					if (!chartData[0].end) {
						$("#lastTime").html("Updated " + (chartData[0].lasttime ? chartData[0].lasttime + " ago" : "now"));
						if (chartData[0].food[0].y > 0) {
							$("#foodDiv").css('background-color', chartData[0].foodColor || '#008789').removeClass("d-none");
						}
						if (chartData[0].bbq[0].y > 0) {
							$("#bbqDiv").css('background-color', chartData[0].bbqColor || '#291A5B').removeClass("d-none");
						}
						$("#toggleCook").html('<a class="mdc-drawer-link" href="#" onClick="document.getElementById(\'stopCookDialog\').MDCDialog.open();"><i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">cancel</i>Stop Cook</a>');
						$("#toggleCook").css('background-color', '#ff420f');
						$("#toggleCook").css('border', 'none');
					// cook has ended, data looks normal. show graph, stats, and hide food+bbq div
					} else {
						$("#foodDiv").addClass("d-none");
						$("#bbqDiv").addClass("d-none");
						$("#lastTime").html("Cook time: " + chartData[0].duration);
						$("#toggleCook").html('<a class="mdc-drawer-link" href="#" onClick="document.getElementById(\'startCookDialog\').MDCDialog.open();"><i class="material-icons mdc-list-item__start-detail mdc-drawer-item-icon" aria-hidden="true">play_arrow</i>Start New Cook</a>');
						$("#toggleCook").css('background-color', '#00B076');
						$("#counterDiv").addClass("d-none");
						$("#navDivider").addClass("d-none");
						$.ajax({
							url: "getdata.php",
							data: {'reqType': 'stats'},
							type: "POST",
							async: true,
							dataType: "json",
							success: function(data) {
								$(".stats:eq(0) h4").html(data['cooks'] || '0');
								$(".stats:eq(1) h4").html(data['time'] || '0');
								$(".stats:eq(2) h4").html(data['readings'] || '0');
								$(".stats:eq(3) h4").html(data['average'] || '0');
							}
						});
						
						$(".mdc-layout-grid__inner").first().prepend($(".stats:eq(0)"), $(".stats:eq(1)"), $(".stats:eq(2)"), $(".stats:eq(3)"));
						$(".stats").removeClass("d-none");
						if (typeof window.counterInterval != "undefined") {
							clearInterval(counterInterval);
						}
						if (typeof window.refreshInterval != "undefined") {
							clearInterval(refreshInterval);
						}
					}
					
					origChartData = [chartData[0].food, chartData[0].bbq];
					let thedate = new Date(chartData[0].bbq[0].x);
					let newdate, foodData, bbqData;
					switch (timePeriod) {
						case 0:
							newdate = thedate.getMinutes() - 15;
							thedate.setMinutes(newdate);
							break;
						case 1:
							newdate = thedate.getHours() - 1;
							thedate.setHours(newdate);
							break;
						case 2:
							newdate = thedate.getHours() - 3;
							thedate.setHours(newdate);
							break;
						default:
							foodData = chartData[0].food;
							bbqData = chartData[0].bbq;
					}
				
					if (newdate) {
						foodData = chartData[0].food.filter(row => new Date(row.x) >= thedate);
						bbqData = chartData[0].bbq.filter(row => new Date(row.x) >= thedate);
					}

					$("#cookTitle").html(chartData[0].date);
					$("#cookNote").html(chartData[0].note);
					$("#foodTemp").html(chartData[0].food[0].y + '°' + chartData[0].tempType);
					$("#bbqTemp").html(chartData[0].bbq[0].y + '°' + chartData[0].tempType);
				    if ($("#bbqChart").length) {
				      var lineChartCanvas = $("#bbqChart").get(0).getContext("2d");
					  
					  const dataArray = [];
					  if (chartData[0].food[0].y > 0) {
						  var s1 = {
						    label: 'Food',
							//backgroundColor: '#5b145b',
							borderColor: chartData[0].foodColor || '#008789', //#ee7759
							fill: false,
							data: foodData
						  };
						  dataArray.push(s1);
					  }
			  
					  if (chartData[0].bbq[0].y > 0) {
						  var s2 = {
						    label: 'BBQ',
							//backgroundColor: '#008789',
							borderColor: chartData[0].bbqColor || '#291A5B', //#291A5B
							fill: false,
							  data: bbqData
						  };
						  dataArray.push(s2);
					  }
			  
				    lineChart = new Chart(lineChartCanvas, {
				        type: 'line',
						data: { datasets: dataArray },
						  options: {
							  /*legend: {
								  labels: {
									  fontSize: 14
								  }
							  },*/
						      scales: {
						        xAxes: [{
								  ticks: {
									  autoSkipPadding: 20
								  },
						          type: 'time',
								  time: {
									  tooltipFormat: 'MMMM d, yyyy h:mm:ss a'
									  //unit: 'hour'
								  }
						        }],
								yAxes: [{
									ticks: {
										callback: function(value, index, values) {
											return value + '°' + chartData[0].tempType
										}
									}
								}]
						      },
						      elements: {
						        point: {
									radius: 0
						        },
								line: {
									tension: 0.1
								}
						      }
						    }
				      });
				    }
			
				    	// Food Chart
				    	if ($("#food-chart").length) {
				        var foodChartCanvas = $("#food-chart").get(0).getContext("2d");
				        var gradient1 = foodChartCanvas.createLinearGradient(0, 0, 0, 30);
				        gradient1.addColorStop(0, '#55d1e8');
				        gradient1.addColorStop(1, 'rgba(255, 255, 255, 0)');
				        var foodChart = new Chart(foodChartCanvas, {
				          type: 'line',
				          data: {
				            datasets: [{
				                data: chartData[0].food.slice(0, 9),
				                fill: false,
								backgroundColor: gradient1,
				                borderColor: [
				                  '#ffffff'
				                ],
				                borderWidth: 1,
				                pointBorderColor: "#ffffff",
				                pointBorderWidth: 5,
				                pointRadius: [1, 0, 0, 0, 0, 0, 0, 0, 1],
				                label: "Food"
				              }
				            ]
				          },
				          options: {
				            responsive: true,
				            maintainAspectRatio: true,
				            layout: {
				              padding: {
				                left: 0,
				                right: 10,
				                top: 0,
				                bottom: 0
				              }
				            },
				            plugins: {
				              filler: {
				                propagate: false
				              }
				            },
				            scales: {
				              xAxes: [{
				                ticks: {
				                  display: false,
				                  fontColor: "#6c7293"
				                },
					          	type: 'time',
							  	time: {
								  tooltipFormat: 'MMMM d, yyyy h:mm:ss a'
								},
				                gridLines: {
				                display: false,
				                drawBorder: false,
				                  color: "rgba(101, 103, 119, 0.21)"
				                }
				              }],
				              yAxes: [{
				                ticks: {
				                  display: false,
				                  fontColor: "#6c7293",
				                },
				                gridLines: {
				                  display: false,
				                  drawBorder: false,
				                  color: "rgba(101, 103, 119, 0.21)"
				                }
				              }]
				            },
				            legend: {
				              display: false
				            },
				            tooltips: {
				              enabled: true
				            },
				            elements: {
				                line: {
				                    tension: 0.1
				                }
				            }
				          }
				        });
				    }
			
				    	// BBQ Chart
				    	if ($("#bbq-chart").length) {
				      var bbqChartCanvas = $("#bbq-chart").get(0).getContext("2d");
			          var gradient2 = bbqChartCanvas.createLinearGradient(0, 0, 0, 30);
			          gradient2.addColorStop(0, '#1bbd88');
			          gradient2.addColorStop(1, 'rgba(255, 255, 255, 0)');
				      var bbqChart = new Chart(bbqChartCanvas, {
				        type: 'line',
				        data: {
				          datasets: [{
				              data: chartData[0].bbq.slice(0, 9),
				              fill: false,
				              borderColor: [
				                '#ffffff'
				              ],
				              borderWidth: 1,
				              pointBorderColor: "#ffffff",
				              pointBorderWidth: 5,
				              pointRadius: [1, 0, 0, 0, 0, 0, 0, 0, 1],
				              label: "BBQ"
				            }
				          ]
				        },
				        options: {
				          responsive: true,
				          maintainAspectRatio: true,
				          layout: {
				            padding: {
				              left: 0,
				              right: 10,
				              top: 0,
				              bottom: 0
				            }
				          },
				          plugins: {
				            filler: {
				              propagate: false
				            }
				          },
				          scales: {
				            xAxes: [{
				              ticks: {
				                display: false,
				                fontColor: "#6c7293"
				              },
					          type: 'time',
							  time: {
								  tooltipFormat: 'MMMM d, yyyy h:mm:ss a'
								  //unit: 'hour'
							  },
				              gridLines: {
				              display: false,
				              drawBorder: false,
				                color: "rgba(101, 103, 119, 0.21)"
				              }
				            }],
				            yAxes: [{
				              ticks: {
				                display: false,
				                fontColor: "#6c7293",
				              },
				              gridLines: {
				                display: false,
				                drawBorder: false,
				                color: "rgba(101, 103, 119, 0.21)"
				              }
				            }]
				          },
				          legend: {
				            display: false
				          },
				          tooltips: {
				            enabled: true
				          },
				          elements: {
				              line: {
				                  tension: 0.1
				              }
				          }
				        }
				      });
				    }
					}
				
				// Show notifications for logged in user
					if (chartData.length == 2 && chartData[1].length > 0) {
						let unreadAlerts = chartData[1].filter((item) => item.read == 0).length;
						if (unreadAlerts > 0) {
							$(".count").html(unreadAlerts);
							$(".count-indicator").removeClass("d-none");
							$("#markAsRead").css('pointer-events', 'auto').css('opacity', '1').removeClass("d-none");
						}
						$("#notifications").html("");
						for (let i = 0; i < chartData[1].length && i < 5; i++) {
							$("#notifications").append(`
							  <li class="mdc-list-item notification`+(chartData[1][i].read == 1 ? ' notification-read' : '')+`" role="menuitem">
								<div class="item-thumbnail item-thumbnail-icon `+ chartData[1][i].type +`">
								  <i class="mdi mdi-`+(chartData[1][i].type.includes('Hi') ? 'fire' : 'fridge')+`"></i>
								</div>
								<div class="item-content d-flex align-items-start flex-column justify-content-center">
								  <h6 class="item-subject font-weight-normal">`+chartData[1][i].message+`</h6>
								  <small class="text-muted">`+chartData[1][i].time+` ago </small>
								</div>
							  </li>
							`);
						}
						if (chartData[1].length > 5) {
							$("#notifications").append(`
								<a href="./notifications" style="color:inherit">
								<li id="readAllNotifications" class="mdc-list-item" role="menuitem">
									<div class="item-thumbnail item-thumbnail-icon" style="background-color: rgba(122, 0, 255, 0.1)">
									  <i class="mdi mdi-email-outline" style="color: #7a00ff"></i>
									</div>
									<div class="item-content d-flex align-items-start flex-column justify-content-center" >
									  <h6 class="item-subject font-weight-normal">Read all notifications</h6>
									  <small class="text-muted"></small>
									</div>
								</li>
								</a>
							`);
						}
					} else {
						$("notifications").append(`
		                  <li class="mdc-list-item" role="menuitem">
		                    <div class="item-thumbnail item-thumbnail-icon">
		                      <i class="mdi mdi-email-outline"></i>
		                    </div>
		                    <div class="item-content d-flex align-items-start flex-column justify-content-center">
		                      <h6 class="item-subject font-weight-normal">No notifications!</h6>
		                      <small class="text-muted"></small>
		                    </div>
		                  </li>
							`);
					}
			}
		});
	
}

// Chart time window drop down options
$(".duration-list > li").on("click", function() {
	let button = $("#durationDiv > button");
	button.html($("h6", $(this)).text() + ' <i class="material-icons">arrow_drop_down</i>');
	$(this).hide().siblings().show();
	
	let thedate = new Date(lineChart.data.datasets[1].data[0].x.replace(' ', 'T'));
	let newdate;

	switch ($(this).index()) {
	
		case 0:
			// last 15 min
			newdate = thedate.getMinutes() - 15;
			thedate.setMinutes(newdate);
			break;
	
		case 1:
			// last hour
			newdate = thedate.getHours() - 1;
			thedate.setHours(newdate);
			break;
	
		case 2:
			// last 3 hours
			newdate = thedate.getHours() - 3;
			thedate.setHours(newdate);
	}
	
	lineChart.data.datasets[0].data = origChartData[0];
	lineChart.data.datasets[1].data = origChartData[1];
	
	if ($(this).index() < 3) {
		lineChart.data.datasets.forEach((e) => {
			e.data = e.data.filter(row => new Date(row.x.replace(' ', 'T')) >= thedate);
		});
	}
	
	lineChart.update();

});

$("#refreshChart").click(function() {
	getChartData(null, $(".duration-list > li[style*='display: none;']").index());
});

$("#editCook").click(function() {
	document.getElementById('editCookDialog').MDCDialog.open();
});