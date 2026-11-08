
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('financeChart');

    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    new Chart(canvas, {
        type: 'bar',

        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],

            datasets: [
                {
                    label: 'Pemasukan',
                    data: [4500000, 5200000, 4800000, 6000000, 5500000, 6500000],
                    backgroundColor: '#26966c',
                    borderRadius: 7,
                    barThickness: 15
                },
                {
                    label: 'Pengeluaran',
                    data: [2500000, 3000000, 2800000, 3500000, 3200000, 3800000],
                    backgroundColor: '#e8a0a5',
                    borderRadius: 7,
                    barThickness: 15
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    position: 'top',
                    align: 'end'
                },

                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.dataset.label + ': Rp ' +
                                context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return 'Rp ' + (value / 1000000) + ' jt';
                        }
                    },
                    grid: {
                        color: '#edf2ee'
                    }
                },

                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}); 