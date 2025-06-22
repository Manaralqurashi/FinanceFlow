document.addEventListener('DOMContentLoaded', () => {
    if (window.jspdf && window.jspdf.jsPDF) {
        console.log("jsPDF is loaded");
    } else {
        console.error("jsPDF failed to load");
    }

    let budgetChart = null;
    let expenseChart = null;
    let expensesLineChart = null;

    async function fetchDashboardData() {
        try {
            const token = localStorage.getItem("authToken");
            if (!token) {
                window.location.href = "login.html";
                return;
            }

            const response = await fetch(`${window.location.origin}/api/dashboard.php`, {
                method: "POST",
                headers: {
                    Authorization: "Bearer " + token,
                    "Content-Type": "application/json",
                }
            });

            const data = await response.json();

            if (response.ok) {
                updateDashboard(data);
            } else {
                console.error("Failed to fetch dashboard data");
            }
        } catch (error) {
            console.error("Error:", error);
        }
    }

    function updateDashboard(data) {
        document.getElementById('total-budget').textContent = `$${data.total_budget.toFixed(2)}`;
        document.getElementById('total-expenses').textContent = `$${data.total_expenses.toFixed(2)}`;

        updateBudgetAllocationChart(data.categories);
        updateExpenseDistributionChart(data.categories);
        updateMonthlyExpensesChart(data.monthly_expenses);
    }

    function updateBudgetAllocationChart(categories) {
        const ctx = document.getElementById('budgetAllocationChart').getContext('2d');
        
        if (budgetChart) {
            budgetChart.destroy();
        }

        budgetChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categories.map(cat => cat.category_name),
                datasets: [{
                    data: categories.map(cat => cat.allocated_amount),
                    backgroundColor: [
                        '#34D399', '#10B981', '#6EE7B7', '#A7F3D0',
                        '#86EFAC', '#4ADE80', '#22C55E', '#16A34A'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Budget Allocation by Category'
                    }
                }
            }
        });
    }

    function updateExpenseDistributionChart(categories) {
        const ctx = document.getElementById('expenseDistributionChart').getContext('2d');
        
        if (expenseChart) {
            expenseChart.destroy();
        }

        expenseChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories.map(cat => cat.category_name),
                datasets: [{
                    label: 'Expenses',
                    data: categories.map(cat => cat.spent_amount),
                    backgroundColor: '#34D399'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Expense Distribution by Category'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Categories'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount ($)'
                        }
                    }
                }
            }
        });
    }

    function updateMonthlyExpensesChart(monthlyExpenses) {
        const ctx = document.getElementById('monthlyExpensesLineChart').getContext('2d');
        
        if (expensesLineChart) {
            expensesLineChart.destroy();
        }

        expensesLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyExpenses.map(item => item.month),
                datasets: [{
                    label: 'Monthly Expenses',
                    data: monthlyExpenses.map(item => item.total_expenses),
                    backgroundColor: 'rgba(34, 197, 151, 0.2)',
                    borderColor: '#10B981',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Monthly Expenses Over Time'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount ($)'
                        }
                    }
                }
            }
        });
    }

    document.getElementById('download-dashboard-pdf').addEventListener('click', async () => {
        const dashboardElement = document.querySelector('main'); // Adjust selector to target the full dashboard

        html2canvas(dashboardElement, { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('dashboard_report.pdf');
        });
    });

    document.addEventListener('DOMContentLoaded', fetchDashboardData);
});