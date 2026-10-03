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
    
    let exportData = [];
    if (chartDataObj.details && Array.isArray(chartDataObj.details) && chartDataObj.details.length > 0) {
        exportData = chartDataObj.details.map(d => ({
            "Course Code": d.code || '',
            "Course Name": d.name || '',
            "Credits": d.credits !== undefined ? d.credits : '',
            "Total Classes": d.total !== undefined ? d.total : 0,
            "Present": d.present !== undefined ? d.present : 0,
            "Late": d.late !== undefined ? d.late : 0,
            "Absent": d.absent !== undefined ? d.absent : 0,
            "Attendance Rate": (d.pct !== undefined ? d.pct : 0) + '%',
            "Exam Standing": (d.pct !== undefined && d.pct >= 75) ? 'Eligible' : 'Attendance Shortage (<75%)'
        }));
    } else {
        const labels = chartDataObj.labels || [];
        const data = chartDataObj.data || [];
        exportData = labels.map((label, index) => {
            return {
                "Category": label,
                "Value": data[index] || 0
            };
        });
    }
    
    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Attendance Data");
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
            },
            plugins: [{
                id: 'attendanceThresholdLine',
                afterDraw(chart) {
                    const { ctx, chartArea, scales: { y } } = chart;
                    if (!chartArea || !y) return;
                    const yVal = y.getPixelForValue(75);
                    if (yVal < chartArea.top || yVal > chartArea.bottom) return;

                    ctx.save();
                    ctx.beginPath();
                    ctx.lineWidth = 1.5;
                    ctx.setLineDash([5, 4]);
                    ctx.strokeStyle = '#f59e0b';
                    ctx.moveTo(chartArea.left, yVal);
                    ctx.lineTo(chartArea.right, yVal);
                    ctx.stroke();

                    ctx.setLineDash([]);
                    ctx.font = '700 10px Inter, sans-serif';
                    ctx.fillStyle = '#f59e0b';
                    ctx.textAlign = 'right';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText('75% Target', chartArea.right - 4, yVal - 3);
                    ctx.restore();
                }
            }]
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

/**
 * Visual Analytics for views/student/my_attendance.php
 * Renders subject-wise comparison with 75% benchmark and overall status doughnut.
 */
function initMyAttendanceCharts() {
    if (typeof Chart === 'undefined') return;

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, sans-serif';

    // 1. My Attendance Subject Comparison Bar Chart
    const subCtx = document.getElementById('myAttendanceSubjectChart');
    if (subCtx) {
        const existingSubChart = Chart.getChart(subCtx);
        if (existingSubChart) existingSubChart.destroy();

        const labels = window.myAttendanceData?.subjects?.labels?.length ? window.myAttendanceData.subjects.labels : ['No Enrolled Courses'];
        const data = window.myAttendanceData?.subjects?.data?.length ? window.myAttendanceData.subjects.data : [0];
        const details = window.myAttendanceData?.subjects?.details || [];

        const ctx2d = subCtx.getContext('2d');
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

        new Chart(subCtx, {
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
                    maxBarThickness: 46
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
                            title: function(context) {
                                const idx = context[0].dataIndex;
                                const item = details[idx];
                                return item ? `${item.name} (${item.code})` : context[0].label;
                            },
                            label: function(context) {
                                const idx = context.dataIndex;
                                const item = details[idx];
                                const lines = [` Attendance Rate: ${context.parsed.y}%`];
                                if (item) {
                                    lines.push(` Attended: ${item.present}/${item.total} sessions`);
                                    if (item.late > 0) lines.push(` Late Arrivals: ${item.late}`);
                                    if (item.absent > 0) lines.push(` Absent: ${item.absent}`);
                                    lines.push(` Exam Standing: ${context.parsed.y >= 75 ? 'Eligible (Satisfactory)' : 'Attendance Low (< 75%)'}`);
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
            },
            plugins: [{
                id: 'myAttendanceThresholdLine',
                afterDraw(chart) {
                    const { ctx, chartArea, scales: { y } } = chart;
                    if (!chartArea || !y) return;
                    const yVal = y.getPixelForValue(75);
                    if (yVal < chartArea.top || yVal > chartArea.bottom) return;

                    ctx.save();
                    ctx.beginPath();
                    ctx.lineWidth = 1.5;
                    ctx.setLineDash([5, 4]);
                    ctx.strokeStyle = '#f59e0b';
                    ctx.moveTo(chartArea.left, yVal);
                    ctx.lineTo(chartArea.right, yVal);
                    ctx.stroke();

                    ctx.setLineDash([]);
                    ctx.font = '700 10px Inter, sans-serif';
                    ctx.fillStyle = '#f59e0b';
                    ctx.textAlign = 'right';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText('75% Target', chartArea.right - 4, yVal - 3);
                    ctx.restore();
                }
            }]
        });
    }

    // 2. My Attendance Status Breakdown Doughnut Chart
    const statusCtx = document.getElementById('myAttendanceStatusChart');
    if (statusCtx) {
        const existingStatusChart = Chart.getChart(statusCtx);
        if (existingStatusChart) existingStatusChart.destroy();

        const statusInfo = window.myAttendanceData?.status || {};
        const total = statusInfo.overallTotal || 0;
        const present = statusInfo.overallPresent || 0;
        const late = statusInfo.overallLate || 0;
        const absent = statusInfo.overallAbsent || 0;
        const overallPct = statusInfo.overallPct || 0;

        const hasData = total > 0;
        const chartData = hasData ? [present, late, absent] : [1];
        const chartLabels = hasData ? ['Present', 'Late', 'Absent'] : ['No Sessions Recorded'];
        const chartColors = hasData ? ['#10b981', '#f59e0b', '#f43f5e'] : [isDark ? '#334155' : '#cbd5e1'];

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: isDark ? 2 : 1,
                    borderColor: isDark ? '#1e293b' : '#ffffff',
                    hoverOffset: hasData ? 6 : 0,
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
                            padding: 14,
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
                                if (!hasData) return ' No attendance records yet';
                                const val = Number(context.parsed || 0);
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${context.label}: ${val} session${val === 1 ? '' : 's'} (${pct}%)`;
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerAttendanceDoughnutText',
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
                    const strVal = `${overallPct}%`;
                    const fontSize = 24;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    // Percentage value
                    ctx.font = `800 ${fontSize}px Inter, sans-serif`;
                    ctx.fillStyle = isDarkMode ? '#f8fafc' : '#0f172a';
                    ctx.fillText(strVal, centerX, centerY - 8);

                    // Sub-label "ATTENDANCE"
                    ctx.font = '700 9px Inter, sans-serif';
                    ctx.fillStyle = isDarkMode ? '#94a3b8' : '#64748b';
                    ctx.fillText('ATTENDANCE', centerX, centerY + 10);

                    // Status pill text
                    if (hasData) {
                        const isEligible = overallPct >= 75;
                        ctx.font = '700 8.5px Inter, sans-serif';
                        ctx.fillStyle = isEligible ? '#10b981' : (overallPct >= 50 ? '#f59e0b' : '#f43f5e');
                        ctx.fillText(isEligible ? 'ELIGIBLE' : 'DEFICIT', centerX, centerY + 22);
                    }

                    ctx.restore();
                }
            }]
        });
    }
}

/**
 * Visual Analytics for views/faculty/view_attendance.php
 * Renders student-wise attendance comparison with 75% benchmark line and eligibility distribution doughnut.
 */
function initFacultyAttendanceReportCharts() {
    if (typeof Chart === 'undefined') return;

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Inter, sans-serif';

    // 1. Student-wise Attendance Comparison Bar Chart
    const stuCtx = document.getElementById('facultyStudentAttendanceChart');
    if (stuCtx) {
        const existing = Chart.getChart(stuCtx);
        if (existing) existing.destroy();

        const reportData = window.facultyAttendanceReportData || {};
        const labels = reportData.students?.labels?.length ? reportData.students.labels : ['No Students'];
        const data = reportData.students?.data?.length ? reportData.students.data : [0];
        const details = reportData.students?.details || [];

        const ctx2d = stuCtx.getContext('2d');
        const backgroundColors = data.map(val => {
            if (val >= 75) {
                const grad = ctx2d.createLinearGradient(0, 0, 0, 260);
                grad.addColorStop(0, 'rgba(16, 185, 129, 0.95)');
                grad.addColorStop(1, 'rgba(16, 185, 129, 0.25)');
                return grad;
            } else if (val >= 60) {
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

        const borderColors = data.map(val => val >= 75 ? '#10b981' : (val >= 60 ? '#f59e0b' : '#f43f5e'));

        new Chart(stuCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance Rate',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: { top: 2, right: 0, bottom: 0, left: 0 },
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 42
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
                            title: ctx => {
                                const item = details[ctx[0]?.dataIndex];
                                return item ? `${item.name} (${item.roll})` : ctx[0]?.label;
                            },
                            label: ctx => {
                                const item = details[ctx.dataIndex];
                                const lines = [` Attendance Rate: ${ctx.parsed.y}%`];
                                if (item) {
                                    lines.push(` Attended: ${item.present}/${item.total} classes`);
                                    if (item.late > 0) lines.push(` Late: ${item.late}`);
                                    if (item.absent > 0) lines.push(` Absent: ${item.absent}`);
                                    lines.push(` Standing: ${ctx.parsed.y >= 75 ? 'Eligible for Exams' : (ctx.parsed.y >= 60 ? 'Warning (60-74%)' : 'Critical Shortage (<60%)')}`);
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
                        grid: { color: gridColor, drawBorder: false },
                        ticks: { stepSize: 25, callback: v => v + '%', font: { size: 11, weight: '500' }, color: textColor },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: '600' }, color: textColor, maxRotation: 45 },
                        border: { display: false }
                    }
                }
            },
            plugins: [{
                id: 'facultyThresholdLine',
                afterDraw(chart) {
                    const { ctx, chartArea, scales: { y } } = chart;
                    if (!chartArea || !y) return;
                    const yVal = y.getPixelForValue(75);
                    if (yVal < chartArea.top || yVal > chartArea.bottom) return;
                    ctx.save();
                    ctx.beginPath();
                    ctx.lineWidth = 1.5;
                    ctx.setLineDash([5, 4]);
                    ctx.strokeStyle = '#f59e0b';
                    ctx.moveTo(chartArea.left, yVal);
                    ctx.lineTo(chartArea.right, yVal);
                    ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.font = '700 10px Inter, sans-serif';
                    ctx.fillStyle = '#f59e0b';
                    ctx.textAlign = 'right';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText('75% Criterion', chartArea.right - 4, yVal - 3);
                    ctx.restore();
                }
            }]
        });
    }

    // 2. Class Eligibility & Standing Breakdown Doughnut Chart
    const tierCtx = document.getElementById('facultyAttendanceTierChart');
    if (tierCtx) {
        const existing = Chart.getChart(tierCtx);
        if (existing) existing.destroy();

        const reportData = window.facultyAttendanceReportData || {};
        const tiers = reportData.tiers || {};
        const safe = tiers.safe || 0;
        const warning = tiers.warning || 0;
        const critical = tiers.critical || 0;
        const total = safe + warning + critical;
        const classAvg = reportData.classAvg || 0;

        const hasData = total > 0;
        const chartData = hasData ? [safe, warning, critical] : [1];
        const chartLabels = hasData ? ['Safe (≥75%)', 'Warning (60-74%)', 'Critical (<60%)'] : ['No Student Records'];
        const chartColors = hasData ? ['#10b981', '#f59e0b', '#f43f5e'] : [isDark ? '#334155' : '#cbd5e1'];

        new Chart(tierCtx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: isDark ? 2 : 1,
                    borderColor: isDark ? '#1e293b' : '#ffffff',
                    hoverOffset: hasData ? 6 : 0,
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
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            font: { size: 10, weight: '600', family: 'Inter, sans-serif' },
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
                                if (!hasData) return ' No attendance records';
                                const val = Number(context.parsed || 0);
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${context.label}: ${val} student${val === 1 ? '' : 's'} (${pct}%)`;
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerFacultyTierDoughnutText',
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

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = '800 22px Inter, sans-serif';
                    ctx.fillStyle = isDark ? '#f8fafc' : '#0f172a';
                    ctx.fillText(`${classAvg}%`, centerX, centerY - 8);

                    ctx.font = '700 9px Inter, sans-serif';
                    ctx.fillStyle = isDark ? '#94a3b8' : '#64748b';
                    ctx.fillText('CLASS AVG', centerX, centerY + 10);

                    if (hasData) {
                        const isGood = classAvg >= 75;
                        ctx.font = '700 8px Inter, sans-serif';
                        ctx.fillStyle = isGood ? '#10b981' : (classAvg >= 60 ? '#f59e0b' : '#f43f5e');
                        ctx.fillText(isGood ? 'HEALTHY' : 'DEFICIT', centerX, centerY + 22);
                    }
                    ctx.restore();
                }
            }]
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
        if (document.getElementById('myAttendanceSubjectChart') || document.getElementById('myAttendanceStatusChart')) {
            if (typeof initMyAttendanceCharts === 'function') initMyAttendanceCharts();
        }
        if (document.getElementById('facultyStudentAttendanceChart') || document.getElementById('facultyAttendanceTierChart')) {
            if (typeof initFacultyAttendanceReportCharts === 'function') initFacultyAttendanceReportCharts();
        }
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}

// Explicit window assignments for guaranteed global availability
if (typeof window !== 'undefined') {
    window.initCharts = initCharts;
    window.initFacultyCharts = initFacultyCharts;
    window.initStudentCharts = initStudentCharts;
    window.initMyAttendanceCharts = initMyAttendanceCharts;
    window.initFacultyAttendanceReportCharts = initFacultyAttendanceReportCharts;
    window.toggleExportMenu = toggleExportMenu;
    window.exportChartAsPDF = exportChartAsPDF;
    window.exportChartAsExcel = exportChartAsExcel;
}


