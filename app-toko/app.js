document.addEventListener('DOMContentLoaded', () => {
    const barangBody = document.getElementById('barangBody');
    const loader = document.getElementById('loader');
    const errorMessage = document.getElementById('errorMessage');
    const itemCount = document.getElementById('itemCount');
    const refreshBtn = document.getElementById('refreshBtn');
    const addBarangForm = document.getElementById('addBarangForm');

    // New DOM Elements
    const searchInput = document.getElementById('searchInput');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const pageInfo = document.getElementById('pageInfo');
    const cetakBtn = document.getElementById('cetakBtn');
    const chartTypeSelect = document.getElementById('chartTypeSelect');

    // State Variables
    let currentPage = 1;
    let currentSearch = '';
    let totalPages = 1;
    let chartInstance = null;

    // Relative API Base URL to support both local development and production hosting seamlessly
    const API_BASE = '../api-toko';

    async function fetchBarang() {
        // Reset UI
        barangBody.innerHTML = '';
        loader.classList.remove('hidden');
        errorMessage.classList.add('hidden');

        try {
            const response = await fetch(`${API_BASE}/get_barang.php?page=${currentPage}&cari=${encodeURIComponent(currentSearch)}`);
            const result = await response.json();

            if (result.status === 'success') {
                const serverStatusEl = document.getElementById('serverStatus');
                if (serverStatusEl && result.server) {
                    serverStatusEl.innerHTML = `🟢 Server: <strong>${result.server}</strong>`;
                }
                renderData(result.data);
                
                // Update pagination controls
                totalPages = result.total_pages || 1;
                currentPage = result.current_page || 1;
                pageInfo.textContent = `Halaman ${currentPage} dari ${totalPages}`;
                
                prevPageBtn.disabled = currentPage <= 1;
                nextPageBtn.disabled = currentPage >= totalPages;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            errorMessage.classList.remove('hidden');
            errorMessage.querySelector('p').textContent = `Error: ${error.message}`;
        } finally {
            loader.classList.add('hidden');
        }
    }

    function renderData(data) {
        itemCount.textContent = `${data.length} Items`;
        
        let totalStock = 0;
        let totalPrice = 0;

        if (data.length === 0) {
            document.getElementById('avgStock').textContent = `Avg Stok: 0`;
            document.getElementById('avgPrice').textContent = `Avg Harga: Rp 0`;
            barangBody.innerHTML = '<tr><td colspan="8" style="text-align:center">No data found</td></tr>';
            return;
        }

        data.forEach(item => {
            totalStock += parseInt(item.stok) || 0;
            totalPrice += parseFloat(item.harga) || 0;

            const tr = document.createElement('tr');
            
            // Format Price
            const formattedPrice = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(item.harga);

            // Stock Status
            let stockClass = 'stock-high';
            let stockLabel = 'In Stock';
            if (item.stok <= 0) {
                stockClass = 'stock-empty';
                stockLabel = 'Out of Stock';
            } else if (item.stok < 10) {
                stockClass = 'stock-low';
                stockLabel = 'Low Stock';
            }

            const imageSrc = item.gambar 
                ? `${API_BASE}/${item.gambar}` 
                : 'https://placehold.co/48x48/1e293b/f8fafc?text=No+Img';

            tr.innerHTML = `
                <td>#${item.id}</td>
                <td><img src="${imageSrc}" alt="${item.nama}" class="img-thumbnail" /></td>
                <td style="font-weight: 500">${item.nama}</td>
                <td class="price">${formattedPrice}</td>
                <td>${item.stok}</td>
                <td style="color: var(--text-dim); font-size: 0.875rem">${item.deskripsi}</td>
                <td><span class="stock-tag ${stockClass}">${stockLabel}</span></td>
                <td>
                    <button class="btn-action btn-edit" onclick="editBarang(${item.id}, \`${(item.nama || '').replace(/`/g, '\\`')}\`, ${item.harga}, ${item.stok}, \`${(item.deskripsi || '').replace(/`/g, '\\`')}\`)">Edit</button>
                    <button class="btn-action btn-delete" onclick="deleteBarang(${item.id})">Hapus</button>
                </td>
            `;
            barangBody.appendChild(tr);
        });

        // Update Averages
        const avgStock = Math.round(totalStock / data.length);
        const avgPrice = totalPrice / data.length;
        
        const avgPriceFormatted = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(avgPrice);

        document.getElementById('avgStock').textContent = `Avg Stok: ${avgStock}`;
        document.getElementById('avgPrice').textContent = `Avg Harga: ${avgPriceFormatted}`;
    }

    // Chart.js Visual Dashboard Logic
    async function fetchAndRenderChart() {
        try {
            const response = await fetch(`${API_BASE}/statistik.php`);
            const result = await response.json();

            if (result.status === 'success') {
                renderChart(result.labels, result.values);
            }
        } catch (error) {
            console.error('Error fetching statistics for chart:', error);
        }
    }

    function renderChart(labels, values) {
        const canvas = document.getElementById('dashboardChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const selectedType = chartTypeSelect.value || 'doughnut';
        
        if (chartInstance) {
            chartInstance.destroy();
        }

        // Generate nice colors for the chart
        const colors = [
            'rgba(99, 102, 241, 0.75)',  // Indigo
            'rgba(236, 72, 153, 0.75)',  // Pink
            'rgba(6, 182, 212, 0.75)',   // Cyan
            'rgba(16, 185, 129, 0.75)',  // Emerald
            'rgba(245, 158, 11, 0.75)',  // Amber
            'rgba(139, 92, 246, 0.75)',  // Violet
            'rgba(244, 63, 94, 0.75)',   // Rose
            'rgba(20, 184, 166, 0.75)'   // Teal
        ];
        
        const borderColors = [
            '#6366f1',
            '#ec4899',
            '#06b6d4',
            '#10b981',
            '#f59e0b',
            '#8b5cf6',
            '#f43f5e',
            '#14b8a6'
        ];

        // Format values as IDR currency
        const currencyFormatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        });

        // Set up Chart options based on type
        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#f8fafc',
                        font: {
                            family: 'Outfit, sans-serif',
                            size: 11
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.raw !== null) {
                                label += currencyFormatter.format(context.raw);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {}
        };

        if (selectedType === 'bar' || selectedType === 'line') {
            options.scales = {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8' }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000) + 'jt';
                            }
                            return currencyFormatter.format(value);
                        }
                    }
                }
            };
        }

        chartInstance = new Chart(ctx, {
            type: selectedType,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nilai Aset',
                    data: values,
                    backgroundColor: selectedType === 'line' ? 'rgba(99, 102, 241, 0.15)' : colors.slice(0, labels.length),
                    borderColor: selectedType === 'line' ? '#6366f1' : borderColors.slice(0, labels.length),
                    borderWidth: 2,
                    fill: selectedType === 'line',
                    tension: 0.35,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 6
                }]
            },
            options: options
        });
    }

    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('Service Worker registered!', reg))
                .catch(err => console.log('Service Worker registration failed:', err));
        });
    }

    addBarangForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(addBarangForm);
        const id = formData.get('id');

        const submitBtn = addBarangForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        try {
            // UI Feedback: Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0"></div>';

            const url = id ? `${API_BASE}/edit_barang.php` : `${API_BASE}/tambah_barang.php`;
            const method = 'POST'; // Use POST for both to handle FormData uploads natively in PHP

            const response = await fetch(url, {
                method: method,
                body: formData
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Gagal menyimpan data');
            }

            const result = await response.json();

            if (result.status === 'success') {
                // SUCCESS!
                alert('✨ ' + result.message);
                addBarangForm.reset();
                document.getElementById('barangId').value = '';
                
                submitBtn.innerHTML = `
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18">
                                <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Simpan Barang
                        `;
                
                // Refresh table and chart without full page reload
                currentPage = 1; // reset to page 1 on adding/editing
                await fetchBarang();
                await fetchAndRenderChart();
                
                // Scroll to table to see the new data
                document.getElementById('barangTable').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (error) {
            console.error('Submit Error:', error);
            alert('❌ Oops! ' + error.message);
            submitBtn.innerHTML = originalBtnText;
        } finally {
            submitBtn.disabled = false;
        }
    });

    // Pagination events
    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            fetchBarang();
        }
    });

    nextPageBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            fetchBarang();
        }
    });

    // Search events with debounce
    let searchTimeout;
    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = searchInput.value;
            currentPage = 1;
            fetchBarang();
        }, 300);
    });

    // Chart select event
    chartTypeSelect.addEventListener('change', () => {
        fetchAndRenderChart();
    });

    // Cetak Laporan print button event
    cetakBtn.addEventListener('click', () => {
        const token = 'StockProSecretToken2026';
        window.open(`cetak.html?token=${token}`, '_blank');
    });

    refreshBtn.addEventListener('click', () => {
        currentPage = 1;
        fetchBarang();
        fetchAndRenderChart();
    });

    // Initial Fetch
    fetchBarang();
    fetchAndRenderChart();

    // Global functions for Edit and Delete
    window.editBarang = function(id, nama, harga, stok, deskripsi) {
        document.getElementById('barangId').value = id;
        document.getElementById('nama').value = nama;
        document.getElementById('harga').value = harga;
        document.getElementById('stok').value = stok;
        document.getElementById('deskripsi').value = deskripsi;
        document.getElementById('gambar').value = '';

        const submitBtn = document.querySelector('#addBarangForm button[type="submit"]');
        submitBtn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18">
                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Update Barang
        `;

        document.getElementById('addBarangForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    window.deleteBarang = async function(id) {
        if (!confirm('Apakah anda yakin ingin menghapus barang ini?')) {
            return;
        }

        try {
            const response = await fetch(`${API_BASE}/hapus_barang.php?id=${id}`, {
                method: 'DELETE'
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Gagal menghapus data');
            }

            const result = await response.json();
            if (result.status === 'success') {
                alert('✨ ' + result.message);
                currentPage = 1;
                fetchBarang(); // Refresh the list
                fetchAndRenderChart(); // Refresh the chart
            } else {
                throw new Error(result.message || 'Gagal menghapus data');
            }
        } catch (error) {
            console.error('Delete Error:', error);
            alert('❌ Oops! ' + error.message);
        }
    };
});
