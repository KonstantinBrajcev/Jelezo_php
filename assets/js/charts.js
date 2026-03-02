// assets/js/charts.js

document.addEventListener('DOMContentLoaded', function () {
  // 1. График помесячных сумм
  const monthlyCtx = document.getElementById('monthlyChart')?.getContext('2d');
  if (monthlyCtx) {
    // Рассчитываем среднее значение
    const averageValue = monthlyData.values.reduce((a, b) => a + b, 0) / monthlyData.values.length;

    // Форматируем среднее значение
    const formattedAverage = averageValue.toLocaleString('ru-RU', {
      style: 'currency',
      currency: 'BYN',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    });

    new Chart(monthlyCtx, {
      type: 'bar',
      data: {
        labels: monthlyData.labels,
        datasets: [{
          label: 'Сумма за ТО, руб.',
          data: monthlyData.values,
          backgroundColor: 'rgba(54, 162, 235, 0.5)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.raw.toLocaleString('ru-RU', {
                  style: 'currency',
                  currency: 'BYN',
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 0
                });
              }
            }
          },
          annotation: {
            annotations: {
              averageLine: {
                type: 'line',
                yMin: averageValue,
                yMax: averageValue,
                borderColor: 'rgba(255, 99, 132, 0.7)',
                borderWidth: 2,
                borderDash: [6, 6],
                label: {
                  content: `Ср: ${formattedAverage}`,
                  enabled: true,
                  position: 'center',
                  backgroundColor: 'rgba(255, 99, 132, 0.9)',
                  color: 'white',
                  font: {
                    size: 11,
                    weight: 'bold'
                  },
                  padding: 4,
                  borderRadius: 4,
                  yAdjust: -15
                }
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return value.toLocaleString('ru-RU') + ' руб.';
              }
            }
          }
        }
      }
    });
  }

  // 2. Круговая диаграмма типов оборудования
  const modelCtx = document.getElementById('modelChart')?.getContext('2d');
  if (modelCtx && modelData.labels.length > 0) {
    new Chart(modelCtx, {
      type: 'doughnut',
      data: {
        labels: modelData.labels.map(l => l || 'Не указано'),
        datasets: [{
          data: modelData.values,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  // 3. График сумм по заказчикам
  const customerCtx = document.getElementById('customerChart')?.getContext('2d');
  if (customerCtx && customerData.labels.length > 0) {
    new Chart(customerCtx, {
      type: 'bar',
      data: {
        labels: customerData.labels,
        datasets: [{
          label: 'Сумма за год, BYN.',
          data: customerData.values,
          backgroundColor: 'rgba(75, 192, 192, 0.5)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.raw.toLocaleString('ru-RU', {
                  style: 'currency',
                  currency: 'BYN',
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 0
                });
              }
            }
          }
        },
        scales: {
          x: {
            ticks: {
              callback: function (value) {
                return value.toLocaleString('ru-RU') + ' руб.';
              }
            }
          }
        }
      }
    });
  }
});