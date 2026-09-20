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
                        padding: 12,
                        displayColors: false,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }

    // Pie Topology Component Config
    const deptCtx = document.getElementById('departmentChart');
    if (deptCtx) {
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
                    hoverOffset: 8
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
                            padding: 24,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#475569',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
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

    // Faculty Assessments Bar Chart
    const assessCtx = document.getElementById('facultyAssessChart');
    if (assessCtx) {
        const labels = window.facultyChartData?.assessments?.labels || ['Course 1', 'Course 2'];
        const data = window.facultyChartData?.assessments?.data || [0, 0];
        
        new Chart(assessCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Assessments',
                    data: data,
                    backgroundColor: isDark ? 'rgba(99, 102, 241, 0.8)' : 'rgba(79, 70, 229, 0.8)',
                    borderRadius: 6
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
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Faculty Materials Doughnut
    const matCtx = document.getElementById('facultyMatChart');
    if (matCtx) {
        const labels = window.facultyChartData?.materials?.labels || ['Course 1', 'Course 2'];
        const data = window.facultyChartData?.materials?.data || [0, 0];
        
        new Chart(matCtx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#10b981', '#f59e0b', '#4f46e5', '#f43f5e', '#8b5cf6', '#ec4899', '#06b6d4'],
                    borderWidth: isDark ? 2 : 0,
                    borderColor: isDark ? '#1e293b' : 'transparent',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#475569',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
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
