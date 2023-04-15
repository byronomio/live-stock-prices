jQuery(function($) {
    const updateInterval = parseInt(lspData.updateInterval, 10);
    const chartDays = parseInt(lspData.chartDays, 10);
  
    function updateScrollingStocks() {
      const container = $('#lsp-scrolling-container');
    
      if (container.length) {
        const tickers = lspData.tickers;
    
        $.ajax({
          url: `https://cloud.iexapis.com/stable/stock/market/batch?symbols=${tickers.join(',')}&types=quote&token=${lspData.apiKey}`,
          dataType: 'json',
          success: function (data) {
            const tickersHtml = tickers.map(ticker => {
              if (data.hasOwnProperty(ticker)) {
                const tickerData = data[ticker].quote;                
                const price = tickerData.latestPrice.toFixed(2);
                const change = tickerData.change.toFixed(2);
                const percentChange = (tickerData.changePercent * 100).toFixed(2);
                const changeClass = tickerData.change >= 0 ? 'lsp-stock-up' : 'lsp-stock-down';
                const changeSign = tickerData.change >= 0 ? '+' : '';
    
                return `
                  <span class="lsp-stock">
                    <span class="lsp-ticker">${ticker}</span>
                    <span class="lsp-price" data-ticker="${ticker}">$${price}</span>
                    <span class="lsp-change ${changeClass}">${changeSign}${change} (${changeSign}${percentChange}%)</span>
                  </span>`;
              }
              return '';
            }).join('');
    
            container.html(tickersHtml);
            updatePrices();
          },
        });
      }
    }
    
    

    function updateStockData() {
      const container = $('#lsp-container');
      container.empty();
  
      lspData.tickers.forEach((ticker, index) => {
        const stockContainer = $('<div class="lsp-stock"></div>');
        container.append(stockContainer);
  
        $.ajax({
          url: `https://cloud.iexapis.com/stable/stock/${ticker}/quote?token=${lspData.apiKey}`,
          dataType: 'json',
        })
          .done((data) => {
            const stockInfo = $('<div class="lsp-stock-info"></div>');
            stockInfo.append(`<span class="lsp-stock-symbol">${data.symbol}</span>`);
            stockInfo.append(`<span class="lsp-stock-price">$${data.latestPrice.toFixed(2)}</span>`);
            const changePercent = ((data.changePercent || 0) * 100).toFixed(2);
            const changeClass = data.changePercent > 0 ? 'lsp-stock-up' : data.changePercent < 0 ? 'lsp-stock-down' : 'lsp-stock-unchanged';
            stockInfo.append(`<span class="lsp-stock-change ${changeClass}">${changePercent > 0 ? '+' : ''}${changePercent}%</span>`);
            stockContainer.append(stockInfo);
            stockContainer.append(`<span class="lsp-stock-name">${data.companyName}</span>`);
  
            if (lspData.showCharts === 'yes') {
              const chartId = `lsp-chart-${index}`;
              stockContainer.append(`<canvas id="${chartId}" class="lsp-chart"></canvas>`);
  
              $.ajax({
                url: `https://cloud.iexapis.com/stable/stock/${ticker}/chart/${chartDays}d?token=${lspData.apiKey}`,
                dataType: 'json',
              })
                .done((chartData) => {
                  const ctx = document.getElementById(chartId).getContext('2d');
                  const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                      labels: chartData.map((entry) => entry.date),
                      datasets: [{
                        label: data.symbol,
                        data: chartData.map((entry) => entry.close),
                        borderColor: '#2196f                      3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        pointBackgroundColor: 'rgba(33, 150, 243, 1)',
                        pointBorderColor: 'rgba(33, 150, 243, 1)',
                        borderWidth: 2,
                      }],
                    },
                    options: {
                      responsive: true,
                      maintainAspectRatio: false,
                      legend: {
                        display: false,
                      },
                      scales: {
                        xAxes: [{
                          display: true,
                          gridLines: {
                            display: false,
                          },
                        }],
                        yAxes: [{
                          display: true,
                          gridLines: {
                            display: false,
                          },
                        }],
                      },
                      tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                          label: function(tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': $' + tooltipItem.yLabel.toFixed(2);
                          },
                        },
                      },
                    },
                  });
                })
                .fail((jqXHR, textStatus) => {
                  console.error('Error fetching chart data:', textStatus);
                });
            }
          })
          .fail((jqXHR, textStatus) => {
            console.error('Error fetching stock data:', textStatus);
          });
      });
    }
  
    updateStockData();
    setInterval(updateStockData, updateInterval);
    updateScrollingStocks();
  });
  