// สถิติการซ่อม (approved) - Chart.js
// ใช้กับหน้า statistics.php

const monthLabelsAll = window.monthLabelsAll;
const monthCountsAll = window.monthCountsAll;
const years = window.years;
let selectedYear = window.selectedYear;
const labelsWeekAll = window.labelsWeekAll;
const countsWeekAll = window.countsWeekAll;

const ctx = document.getElementById('repairChart').getContext('2d');
let chartType = 'month';
let selectedMonth = 1;
let repairChart = null;

function getChartConfig(type, displayType) {
    let labels, data, title, xTitle;
    if (type === 'week') {
        labels = labelsWeekAll[selectedYear]?.[selectedMonth] || [];
        data = countsWeekAll[selectedYear]?.[selectedMonth] || [];
        title = `จำนวนงานซ่อมที่ได้รับการอนุมัติ (ต่อสัปดาห์) เดือน ${selectedMonth}/${selectedYear}`;
        xTitle = 'สัปดาห์';
    } else {
        labels = monthLabelsAll[selectedYear] || [];
        data = monthCountsAll[selectedYear] || [];
        title = 'จำนวนงานซ่อมที่ได้รับการอนุมัติ (ต่อเดือน)';
        xTitle = 'เดือน';
    }
    return {
        type: displayType || window.chartDisplayType || 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: type === 'week' ? 'จำนวนงานซ่อมแต่ละสัปดาห์' : 'จำนวนงานซ่อมแต่ละเดือน',
                data: data,
                backgroundColor: 'rgba(26, 35, 126, 0.7)', // #1a237e navy
                borderColor: 'rgba(26, 35, 126, 1)',
                borderWidth: 2,
                borderRadius: 8,
                fill: false,
                // ถ้าใช้ datalabels plugin ให้กำหนดสีด้วย
                datalabels: {
                    color: '#222',
                    font: { weight: 'bold', size: 14 }
                }
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: '#222',
                        font: { size: 15 }
                    }
                },
                title: {
                    display: true,
                    text: title,
                    color: '#222',
                    font: { size: 20 }
                },
                tooltip: {
                    bodyColor: '#222',
                    titleColor: '#222',
                    footerColor: '#222',
                    backgroundColor: '#fff',
                    borderColor: '#bbb',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: xTitle,
                        color: '#222',
                        font: { size: 16 }
                    },
                    ticks: {
                        color: '#222',
                        font: { size: 14, weight: 'bold' }
                    },
                    grid: { color: '#bbb' }
                },
                y: {
                    title: {
                        display: true,
                        text: 'จำนวนงานซ่อม',
                        color: '#222',
                        font: { size: 16 }
                    },
                    beginAtZero: true,
                    ticks: {
                        color: '#222',
                        font: { size: 14, weight: 'bold' }
                    },
                    grid: { color: '#bbb' }
                }
            },
            layout: {
                padding: 10
            },
            backgroundColor: '#fff'
        }
    };
}

function renderChart(type, displayType) {
    if (repairChart) repairChart.destroy();
    const config = getChartConfig(type, displayType);
    repairChart = new Chart(ctx, config);
    // คำนวณยอดรวม
    let total = 0;
    if (config.data && config.data.datasets && config.data.datasets[0]) {
        total = config.data.datasets[0].data.reduce((a,b) => a + b, 0);
    }
    let label = (type === 'week') ? 'ยอดรวมงานซ่อมในเดือนนี้' : 'ยอดรวมงานซ่อมในปีนี้';
    document.getElementById('totalCount').innerHTML = `${label}: <span class="text-warning">${total}</span>`;
}

// สร้าง year/month dropdown
const yearSelect = document.getElementById('yearSelect');
let monthSelect = null;
function renderYearOptions() {
    yearSelect.innerHTML = '';
    years.forEach(y => {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        if (y == selectedYear) opt.selected = true;
        yearSelect.appendChild(opt);
    });
}
function renderMonthSelect() {
    if (!monthSelect) {
        monthSelect = document.createElement('select');
        monthSelect.id = 'monthSelect';
        monthSelect.className = 'form-select w-auto bg-light text-dark border-secondary';
        monthSelect.style.marginLeft = '0.5rem';
        yearSelect.parentNode.appendChild(monthSelect);
    }
    monthSelect.innerHTML = '';
    for (let m = 1; m <= 12; m++) {
        const opt = document.createElement('option');
        opt.value = m;
        opt.textContent = m;
        if (m == selectedMonth) opt.selected = true;
        monthSelect.appendChild(opt);
    }
}
renderYearOptions();
renderMonthSelect();

function updateDropdownVisibility() {
    yearSelect.style.display = (chartType === 'month' || chartType === 'week') ? '' : 'none';
    if (monthSelect) monthSelect.style.display = (chartType === 'week') ? '' : 'none';
}
updateDropdownVisibility();

// Initial render
renderChart(chartType);

// Handle dropdown change

document.getElementById('chartType').addEventListener('change', function(e) {
    chartType = e.target.value;
    updateDropdownVisibility();
    if (chartType === 'week') {
        renderMonthSelect();
    }
    renderChart(chartType);
});
yearSelect.addEventListener('change', function(e) {
    selectedYear = e.target.value;
    renderChart(chartType);
});
if (monthSelect) {
    monthSelect.addEventListener('change', function(e) {
        selectedMonth = e.target.value;
        renderChart(chartType);
    });
}

window.addEventListener('DOMContentLoaded', function() {
    var chartType = window.selectedChartType || 'month';
    var chartTypeSelect = document.getElementById('chartType');
    if (chartTypeSelect) chartTypeSelect.value = chartType;
    // trigger change event เพื่อให้ JS โหลดข้อมูลกราฟที่ถูกต้อง
    if (chartTypeSelect) {
        var event = new Event('change', { bubbles: true });
        chartTypeSelect.dispatchEvent(event);
    }
    // toggle chart display type (bar/line)
    var toggleBtn = document.getElementById('toggleChartType');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            window.chartDisplayType = (window.chartDisplayType === 'bar') ? 'line' : 'bar';
            toggleBtn.textContent = (window.chartDisplayType === 'bar') ? 'เปลี่ยนเป็นกราฟเส้น' : 'เปลี่ยนเป็นกราฟแท่ง';
            if (typeof window.renderRepairChart === 'function') {
                window.renderRepairChart(chartType, window.chartDisplayType);
            }
        });
    }
});

// เปลี่ยน window.renderRepairChart ให้เรียก renderChart แบบใหม่
window.renderRepairChart = renderChart;
