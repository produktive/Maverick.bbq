(function($) {
    'use strict'; 
    $(function() {

    //Revenue Chart
    if ($("#revenue-chart").length) {
        var revenueChartCanvas = $("#revenue-chart").get(0).getContext("2d");

        var revenueChart = new Chart(revenueChartCanvas, {
            type: 'bar',
            data: {
            labels: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
            datasets: [{
                data: [105, 195, 290, 320, 400, 100, 290],
                backgroundColor: ["rgba(255, 86, 48, 0.3)", "rgba(255, 86, 48, 0.3)", "rgba(255, 86, 48, 0.3)", "rgb(255, 86, 48)", "rgba(255, 86, 48, 0.3)", "rgba(255, 86, 48, 0.3)", "rgba(255, 86, 48, 0.3)"],
                }
            ]
            },
            options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                gridLines: {
                    drawBorder: false,
                    zeroLineColor: "rgba(0, 0, 0, 0.09)",
                    color: "rgba(0, 0, 0, 0.09)"
                },
                ticks: {
                    fontColor: '#bababa',
                    min:0,
                    stepSize: 100,
                }
                }],
                xAxes: [{
                ticks: {
                    fontColor: '#bababa',
                    beginAtZero: true
                },
                gridLines: {
                    display: false,
                    drawBorder: false
                },
                barPercentage: 0.4
                }]
            },
            legend: {
                display: false
            }
            }
        });
    }

    //Sales Chart
    if ($("#chart-sales").length) {
        var salesChartCanvas = $("#chart-sales").get(0).getContext("2d");
        var gradient1 = salesChartCanvas.createLinearGradient(0, 0, 0, 230);
        gradient1.addColorStop(0, '#55d1e8');
        gradient1.addColorStop(1, 'rgba(255, 255, 255, 0)');

        var gradient2 = salesChartCanvas.createLinearGradient(0, 0, 0, 160);
        gradient2.addColorStop(0, '#1bbd88');
        gradient2.addColorStop(1, 'rgba(255, 255, 255, 0)');

        var salesChart = new Chart(salesChartCanvas, {
          type: 'line',
          data: {
            labels: ["2am", "4am", "6am", "8am", "10am", "12am"],
            datasets: [{
                data: [80, 115, 115, 150, 130, 160],
                backgroundColor: gradient1,
                borderColor: [
                  '#08bdde'
                ],
                borderWidth: 2,
                pointBorderColor: "#08bdde",
                pointBorderWidth: 4,
                pointRadius: 1,
                fill: 'origin',
              },
              {
                data: [250, 310, 270, 330, 270, 380],
                backgroundColor: gradient2,
                borderColor: [
                  '#00b67a'
                ],
                borderWidth: 2,
                pointBorderColor: "#00b67a",
                pointBorderWidth: 4,
                pointRadius: 1,
                fill: 'origin',
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
              filler: {
                propagate: false
              }
            },
            scales: {
              xAxes: [{
                ticks: {
                  fontColor: "#bababa"
                },
                gridLines: {
                  display: false,
                  drawBorder: false
                }
              }],
              yAxes: [{
                ticks: {
                  fontColor: "#bababa",
                  stepSize: 100,
                  min: 0,
                  max: 500
                },
                gridLines: {
                  drawBorder: false,
                  color: "rgba(101, 103, 119, 0.21)",
                  zeroLineColor: "rgba(101, 103, 119, 0.21)"
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
                    tension: 0
                }
            },
            legendCallback : function(chart) {
              var text = [];
              text.push('<div>');
              text.push('<div class="d-flex align-items-center">');
              text.push('<span class="bullet-rounded" style="border-color: ' + chart.data.datasets[1].borderColor[0] +' "></span>');
              text.push('<p class="tx-12 text-muted mb-0 ml-2">Gross volume</p>');
              text.push('</div>');
              text.push('<div class="d-flex align-items-center">');
              text.push('<span class="bullet-rounded" style="border-color: ' + chart.data.datasets[0].borderColor[0] +' "></span>');
              text.push('<p class="tx-12 text-muted mb-0 ml-2">New Customers</p>');
              text.push('</div>');
              text.push('</div>');
              return text.join('');
            },
          }
        });
      document.getElementById('sales-legend').innerHTML = salesChart.generateLegend();
    }
    });
})(jQuery);