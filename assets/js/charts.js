// assets/js/charts.js

/**
 * Initializes and orchestrates responsive Chart.js components securely.
 * Reacts intelligently to the application theme states seamlessly.
 */
function initCharts() {
    if (typeof Chart === 'undefined') return;

    // Abstracting color variables linking UI aesthetics matching Tailwind palettes securely
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, sans-serif';

    // Annual Trajectory (Line) Config
    const enrollmentCtx = document.getElementById('enrollmentChart');
    if (enrollmentCtx) {
        const existingEnrollChart = Chart.getChart(enrollmentCtx);
        if (existingEnrollChart) existingEnrollChart.destroy();

        const labels = window.chartData?.enrollment?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const data = window.chartData?.enrollment?.data || [12, 19, 15, 25, 22, 30];
        
        new Chart(enrollmentCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Enrollments',
                    data: data,
                    borderColor: '#4f46e5',
                    backgroundColor: isDark ? 'rgba(79, 70, 229, 0.15)' : 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#475569',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1,
                            font: { size: 10 }
                        },
                        grid: { color: gridColor },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Pie Topology Component Config
    const deptCtx = document.getElementById('departmentChart');
    if (deptCtx) {
        const existingDeptChart = Chart.getChart(deptCtx);
        if (existingDeptChart) existingDeptChart.destroy();

        const labels = window.chartData?.departments?.labels || ['Computer Science', 'Electrical', 'Mechanical', 'Civil'];
        const data = window.chartData?.departments?.data || [45, 25, 20, 10];
        
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#4f46e5', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#ec4899', '#06b6d4', '#14b8a6'
                    ],
                    borderWidth: isDark ? 2 : 0,
                    borderColor: isDark ? '#1e293b' : 'transparent',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 6,
                            font: { size: 10, weight: '600', family: 'Inter, sans-serif' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#475569',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8
                    }
                }
            },
            plugins: [{
                id: 'centerDoughnutText',
                afterDraw(chart) {
                    if (chart.config.type !== 'doughnut') return;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    
                    let centerX = (chartArea.left + chartArea.right) / 2;
                    let centerY = (chartArea.top + chartArea.bottom) / 2;
                    const meta = chart.getDatasetMeta(0);
                    if (meta && meta.data && meta.data.length > 0 && typeof meta.data[0].x === 'number') {
                        centerX = meta.data[0].x;
                        centerY = meta.data[0].y;
                    }
                    
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    const totalVal = window.chartData?.departments?.total !== undefined 
                        ? window.chartData.departments.total 
                        : (chart.data.datasets[0]?.data?.reduce((a, b) => a + Number(b || 0), 0) || 0);
                    
                    const strVal = totalVal.toLocaleString();
                    const fontSize = strVal.length > 5 ? 16 : 22;
                    
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    
                    // Main count number - centered vertically and horizontally inside the doughnut ring
                    ctx.font = `800 ${fontSize}px Inter, sans-serif`;
                    ctx.fillStyle = isDarkMode ? '#ffffff' : '#0f172a';
                    ctx.fillText(strVal, centerX, centerY - 6);
                    
                    // Sub-label "TOTAL"
                    ctx.font = '700 9px Inter, sans-serif';
                    ctx.fillStyle = isDarkMode ? '#94a3b8' : '#64748b';
                    ctx.fillText('TOTAL', centerX, centerY + 10);
                    
                    ctx.restore();
                }
            }]
        });
    }
}

function initFacultyCharts() {
    if (typeof Chart === 'undefined') return;

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, sans-serif';

    // 1. Faculty Assessments & Course Activity Bar Chart
    const assessCtx = document.getElementById('facultyAssessChart');
    if (assessCtx) {
        const existingAssessChart = Chart.getChart(assessCtx);
        if (existingAssessChart) existingAssessChart.destroy();

        const labels = window.facultyChartData?.assessments?.labels?.length ? window.facultyChartData.assessments.labels : ['No Assigned Courses'];
        const data = window.facultyChartData?.assessments?.data?.length ? window.facultyChartData.assessments.data : [0];

        // Create linear gradient for bar background
        const ctx2d = assessCtx.getContext('2d');
        const barGradient = ctx2d.createLinearGradient(0, 0, 0, 300);
        if (isDark) {
            barGradient.addColorStop(0, 'rgba(129, 140, 248, 0.95)');
            barGradient.addColorStop(1, 'rgba(79, 70, 229, 0.25)');
        } else {
            barGradient.addColorStop(0, 'rgba(99, 102, 241, 0.95)');
            barGradient.addColorStop(1, 'rgba(199, 210, 254, 0.35)');
        }

        const barHoverGradient = ctx2d.createLinearGradient(0, 0, 0, 300);
        barHoverGradient.addColorStop(0, '#4338ca');
        barHoverGradient.addColorStop(1, '#6366f1');

        new Chart(assessCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Assignments & Coursework',
                    data: data,
                    backgroundColor: barGradient,
                    hoverBackgroundColor: barHoverGradient,
                    borderColor: isDark ? '#818cf8' : '#6366f1',
                    borderWidth: { top: 2, right: 0, bottom: 0, left: 0 },
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 48
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#0f172a' : '#ffffff',
                        titleColor: isDark ? '#f8fafc' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#334155',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return ` Coursework Count: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 1,
                            precision: 0,
                            font: { size: 11, weight: '500' },
                            color: textColor
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, weight: '600' },
                            color: textColor
                        },
                        border: { display: false }
                    }
                }
            }
        });
    }

    // 2. Faculty Course Resources Doughnut
    const matCtx = document.getElementById('facultyMatChart');
    if (matCtx) {
        const existingMatChart = Chart.getChart(matCtx);
        if (existingMatChart) existingMatChart.destroy();

        const labels = window.facultyChartData?.materials?.labels?.length ? window.facultyChartData.materials.labels : ['No Resources'];
        const data = window.facultyChartData?.materials?.data?.length ? window.facultyChartData.materials.data : [0];
        
        const palette = [
            '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4', '#f43f5e', '#14b8a6'
        ];

        new Chart(matCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: palette.slice(0, Math.max(labels.length, 1)),
                    borderWidth: isDark ? 2 : 1,
                    borderColor: isDark ? '#1e293b' : '#ffffff',
                    hoverOffset: 6,
                    borderRadius: 4,
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            font: { size: 11, weight: '600', family: 'Inter, sans-serif' },
                            color: textColor
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#0f172a' : '#ffffff',
                        titleColor: isDark ? '#f8fafc' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#334155',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + Number(b || 0), 0);
                                const val = Number(context.parsed || 0);
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerFacultyDoughnutText',
                afterDraw(chart) {
                    if (chart.config.type !== 'doughnut') return;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;

                    let centerX = (chartArea.left + chartArea.right) / 2;
                    let centerY = (chartArea.top + chartArea.bottom) / 2;
                    const meta = chart.getDatasetMeta(0);
                    if (meta && meta.data && meta.data.length > 0 && typeof meta.data[0].x === 'number') {
                        centerX = meta.data[0].x;
                        centerY = meta.data[0].y;
                    }

                    const isDarkMode = document.documentElement.classList.contains('dark');
                    const totalVal = chart.data.datasets[0]?.data?.reduce((a, b) => a + Number(b || 0), 0) || 0;
                    const strVal = totalVal.toLocaleString();
                    const fontSize = strVal.length > 5 ? 18 : 24;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    // Main count number
                    ctx.font = `800 ${fontSize}px Inter, sans-serif`;
                    ctx.fillStyle = isDarkMode ? '#f8fafc' : '#0f172a';
                    ctx.fillText(strVal, centerX, centerY - 6);

                    // Sub-label
                    ctx.font = '700 10px Inter, sans-serif';
                    ctx.fillStyle = isDarkMode ? '#94a3b8' : '#64748b';
                    ctx.fillText('MATERIALS', centerX, centerY + 12);

                    ctx.restore();
                }
            }]
        });
    }
}

// Export functions
function toggleExportMenu(menuId) {
    const menu = document.getElementById(menuId);
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Close export menus on outside click
document.addEventListener('click', function(event) {
    const menus = document.querySelectorAll('[id^="exportMenu"]');
    menus.forEach(menu => {
        if (!menu.classList.contains('hidden') && !menu.previousElementSibling.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
});

function exportChartAsPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    if (!element || typeof html2pdf === 'undefined') return;
    
    // Temporarily hide export button
    const btns = element.querySelectorAll('button');
    btns.forEach(b => b.style.display = 'none');
    
    const opt = {
        margin:       1,
        filename:     filename + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    
    html2pdf().set(opt).from(element).save().then(() => {
        btns.forEach(b => b.style.display = '');
    });
}

function exportChartAsExcel(chartDataObj, filename) {
    if (typeof XLSX === 'undefined' || !chartDataObj) return;
    
    const labels = chartDataObj.labels || [];
    const data = chartDataObj.data || [];
    
    // Prepare data array of objects
    const exportData = labels.map((label, index) => {
        return {
            "Category": label,
            "Value": data[index] || 0
        };
    });
    
    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Chart Data");
    XLSX.writeFile(workbook, filename + '.xlsx');
}

function initStudentCharts() {
    if (typeof Chart === 'undefined') return;

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, sans-serif';

    // 1. Subject-wise Attendance Bar Chart
    const attCtx = document.getElementById('studentAttendanceChart');
    if (attCtx) {
        const existingAttChart = Chart.getChart(attCtx);
        if (existingAttChart) existingAttChart.destroy();

        const labels = window.studentChartData?.attendance?.labels?.length ? window.studentChartData.attendance.labels : ['No Enrolled Courses'];
        const data = window.studentChartData?.attendance?.data?.length ? window.studentChartData.attendance.data : [0];
        const details = window.studentChartData?.attendance?.details || [];

        const ctx2d = attCtx.getContext('2d');
        
        // Dynamic colors per bar based on >=75% safe vs warning
        const backgroundColors = data.map(val => {
            if (val >= 75) {
                const grad = ctx2d.createLinearGradient(0, 0, 0, 260);
                grad.addColorStop(0, 'rgba(16, 185, 129, 0.95)');
                grad.addColorStop(1, 'rgba(16, 185, 129, 0.25)');
                return grad;
            } else if (val >= 50) {
                const grad = ctx2d.createLinearGradient(0, 0, 0, 260);
                grad.addColorStop(0, 'rgba(245, 158, 11, 0.95)');
                grad.addColorStop(1, 'rgba(245, 158, 11, 0.25)');
                return grad;
            } else {
                const grad = ctx2d.createLinearGradient(0, 0, 0, 260);
                grad.addColorStop(0, 'rgba(244, 63, 94, 0.95)');
                grad.addColorStop(1, 'rgba(244, 63, 94, 0.25)');
                return grad;
            }
        });

        const borderColors = data.map(val => val >= 75 ? '#10b981' : (val >= 50 ? '#f59e0b' : '#f43f5e'));

        new Chart(attCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance Rate',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: { top: 2, right: 0, bottom: 0, left: 0 },
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#0f172a' : '#ffffff',
                        titleColor: isDark ? '#f8fafc' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#334155',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const idx = context.dataIndex;
                                const item = details[idx];
                                const lines = [` Attendance: ${context.parsed.y}%`];
                                if (item) {
                                    lines.push(` Present: ${item.present}/${item.total} classes`);
                                    if (item.late > 0) lines.push(` Late: ${item.late}`);
                                    lines.push(` Status: ${context.parsed.y >= 75 ? 'Safe (Eligible)' : 'Attendance Low'}`);
                                }
                                return lines;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 25,
                            callback: value => value + '%',
                            font: { size: 11, weight: '500' },
                            color: textColor
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, weight: '600' },
                            color: textColor
                        },
                        border: { display: false }
                    }
                }
            }
        });
    }

    // 2. Student Academic Performance / Scores Breakdown
    const perfCtx = document.getElementById('studentPerformanceChart');
    if (perfCtx) {
        const existingPerfChart = Chart.getChart(perfCtx);
        if (existingPerfChart) existingPerfChart.destroy();

        const labels = window.studentChartData?.performance?.labels?.length ? window.studentChartData.performance.labels : ['No Assessment Data'];
        const data = window.studentChartData?.performance?.data?.length ? window.studentChartData.performance.data : [0];

        const ctx2d = perfCtx.getContext('2d');
        const perfGradient = ctx2d.createLinearGradient(0, 0, 0, 260);
        if (isDark) {
            perfGradient.addColorStop(0, 'rgba(168, 85, 247, 0.95)');
            perfGradient.addColorStop(1, 'rgba(99, 102, 241, 0.2)');
        } else {
            perfGradient.addColorStop(0, 'rgba(147, 51, 234, 0.95)');
            perfGradient.addColorStop(1, 'rgba(199, 210, 254, 0.35)');
        }

        new Chart(perfCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score Percentage',
                    data: data,
                    backgroundColor: perfGradient,
                    hoverBackgroundColor: '#7c3aed',
                    borderColor: isDark ? '#c084fc' : '#9333ea',
                    borderWidth: { top: 2, right: 0, bottom: 0, left: 0 },
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#0f172a' : '#ffffff',
                        titleColor: isDark ? '#f8fafc' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#334155',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                return ` Score: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 25,
                            callback: value => value + '%',
                            font: { size: 11, weight: '500' },
                            color: textColor
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, weight: '600' },
                            color: textColor
                        },
                        border: { display: false }
                    }
                }
            }
        });
    }
}

// Automatically sync and re-render charts when theme changes
if (typeof MutationObserver !== 'undefined' && !window.__themeObserverAttached) {
    window.__themeObserverAttached = true;
    const observer = new MutationObserver(() => {
        if (document.getElementById('facultyAssessChart') || document.getElementById('facultyMatChart')) {
            if (typeof initFacultyCharts === 'function') initFacultyCharts();
        }
        if (document.getElementById('studentAttendanceChart') || document.getElementById('studentPerformanceChart')) {
            if (typeof initStudentCharts === 'function') initStudentCharts();
        }
        if (document.getElementById('enrollmentChart') || document.getElementById('departmentChart')) {
            if (typeof initCharts === 'function') initCharts();
        }
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}

