document.addEventListener('DOMContentLoaded', () => {
    // Auth Guard check
    const token = localStorage.getItem('pwa_token');
    if (!token) {
        window.location.replace('login.php');
        return;
    }

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
    const logoutBtn = document.getElementById('logoutBtn');

    // State Variables
    let currentPage = 1;
    let currentSearch = '';
    let totalPages = 1;
    let chartInstance = null;

    let html5QrCode = null;
    let currentScanMode = 'cari';

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
            barangBody.innerHTML = '<tr><td colspan="9" style="text-align:center">No data found</td></tr>';
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

            // Google Maps location URL
            let lokasiHtml = '<span style="color: var(--text-dim)">-</span>';
            if (item.latitude && item.longitude) {
                const mapUrl = `https://www.google.com/maps?q=${item.latitude},${item.longitude}`;
                lokasiHtml = `<a href="${mapUrl}" target="_blank" class="map-link" style="color: var(--primary); display: inline-flex; align-items: center; gap: 0.25rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Lihat Peta
                </a>`;
            }

            tr.innerHTML = `
                <td>#${item.id}</td>
                <td><img src="${imageSrc}" alt="${item.nama}" class="img-thumbnail" /></td>
                <td style="font-weight: 500">${item.nama}</td>
                <td class="price">${formattedPrice}</td>
                <td>${item.stok}</td>
                <td style="color: var(--text-dim); font-size: 0.875rem">${item.deskripsi}</td>
                <td><span class="stock-tag ${stockClass}">${stockLabel}</span></td>
                <td>${lokasiHtml}</td>
                <td>
                    <button class="btn-action btn-edit" onclick="editBarang(${item.id}, \`${(item.nama || '').replace(/`/g, '\\`')}\`, ${item.harga}, ${item.stok}, \`${(item.deskripsi || '').replace(/`/g, '\\`')}\`, \`${(item.kode_qr || '').replace(/`/g, '\\`')}\`, \`${(item.latitude || '')}\`, \`${(item.longitude || '')}\`)">Edit</button>
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

            const headers = {};
            const token = localStorage.getItem('pwa_token');
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: headers
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
                document.getElementById('kode_qr').value = '';
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                
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
        const activeToken = localStorage.getItem('pwa_token') || 'StockProSecretToken2026';
        window.open(`cetak.html?token=${activeToken}`, '_blank');
    });

    // Logout button event
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('Apakah Anda yakin ingin keluar dari StockPro?')) {
                localStorage.removeItem('pwa_token');
                localStorage.removeItem('pwa_user');
                window.location.replace('login.php');
            }
        });
    }

    refreshBtn.addEventListener('click', () => {
        currentPage = 1;
        fetchBarang();
        fetchAndRenderChart();
    });

    // Initial Fetch
    fetchBarang();
    fetchAndRenderChart();

    // Global functions for Edit and Delete
    window.editBarang = function(id, nama, harga, stok, deskripsi, kode_qr, latitude, longitude) {
        document.getElementById('barangId').value = id;
        document.getElementById('nama').value = nama;
        document.getElementById('harga').value = harga;
        document.getElementById('stok').value = stok;
        document.getElementById('deskripsi').value = deskripsi || '';
        document.getElementById('kode_qr').value = kode_qr || '';
        document.getElementById('latitude').value = latitude || '';
        document.getElementById('longitude').value = longitude || '';
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
            const headers = {};
            const token = localStorage.getItem('pwa_token');
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const response = await fetch(`${API_BASE}/hapus_barang.php?id=${id}`, {
                method: 'DELETE',
                headers: headers
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

    // Geolocation / GPS
    window.dapatkanLokasi = function() {
        const gpsBtn = document.getElementById('gpsBtn');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        
        if (!navigator.geolocation) {
            alert('Geolocation tidak didukung oleh browser Anda.');
            return;
        }

        const originalBtnContent = gpsBtn.innerHTML;
        gpsBtn.disabled = true;
        gpsBtn.innerHTML = '<div class="spinner" style="width:14px;height:14px;border-width:2px;margin:0"></div>';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                latInput.value = position.coords.latitude.toFixed(8);
                lngInput.value = position.coords.longitude.toFixed(8);
                gpsBtn.disabled = false;
                gpsBtn.innerHTML = originalBtnContent;
            },
            (error) => {
                console.error('GPS Error:', error);
                alert('Gagal mendapatkan lokasi: ' + error.message);
                gpsBtn.disabled = false;
                gpsBtn.innerHTML = originalBtnContent;
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    };

    // Stop and clear QR scanner helper
    function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            return html5QrCode.stop().then(() => {
                console.log("Scanner stopped.");
            }).catch(err => {
                console.error("Failed to stop scanner:", err);
            });
        }
        return Promise.resolve();
    }

    // Initialize QR code scanner
    window.initMainQrScanner = function() {
        tampilQrStatus('loading');
        
        stopScanner().then(() => {
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText, decodedResult) => {
                    console.log(`Scan result: ${decodedText}`, decodedResult);
                    
                    if (currentScanMode === 'input') {
                        // Direct input mode: fill form field and close scanner
                        document.getElementById('kode_qr').value = decodedText;
                        stopScanner().then(() => {
                            document.getElementById('qrModal').classList.add('hidden');
                            alert('QR Code berhasil dipindai!');
                        });
                    } else {
                        // Gateway / search mode: lookup database
                        tampilQrStatus('loading');
                        
                        fetch(`${API_BASE}/barang.php?kode_qr=${encodeURIComponent(decodedText)}`)
                            .then(res => res.json())
                            .then(result => {
                                if (result.status === 'success' && result.data && result.data.length > 0) {
                                    const item = result.data[0];
                                    tampilQrStatus('found', item);
                                } else {
                                    tampilQrStatus('not_found', decodedText);
                                }
                            })
                            .catch(err => {
                                console.error("Error looking up QR code:", err);
                                tampilQrStatus('not_found', decodedText);
                            });
                    }
                },
                (errorMessage) => {
                    // Ignore noisy scanning errors
                }
            ).then(() => {
                tampilQrStatus('reset');
            }).catch(err => {
                console.error("Camera start failed:", err);
                alert("Gagal mengakses kamera: " + err);
                document.getElementById('qrModal').classList.add('hidden');
            });
        });
    };

    // Show/hide status cards in scanner modal
    window.tampilQrStatus = function(status, data) {
        const loadingCard = document.getElementById('qrStatusLoading');
        const foundCard = document.getElementById('qrStatusDitemukan');
        const notFoundCard = document.getElementById('qrStatusNotFound');
        
        // Hide all first
        loadingCard.classList.add('hidden');
        foundCard.classList.add('hidden');
        notFoundCard.classList.add('hidden');
        
        if (status === 'loading') {
            loadingCard.classList.remove('hidden');
        } else if (status === 'found') {
            foundCard.classList.remove('hidden');
            
            const formattedPrice = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(data.harga);
            
            document.getElementById('foundBarangNama').textContent = data.nama;
            document.getElementById('foundBarangStok').textContent = `Stok: ${data.stok} unit`;
            document.getElementById('foundBarangHarga').textContent = `Harga: ${formattedPrice}`;
        } else if (status === 'not_found') {
            notFoundCard.classList.remove('hidden');
            document.getElementById('notFoundQrCode').textContent = data;
            
            // Setup click handler for "+ Tambah Barang Baru" button inside the warning card
            const btnTambah = document.getElementById('btnQrTambahBarang');
            btnTambah.onclick = () => {
                document.getElementById('kode_qr').value = data;
                stopScanner().then(() => {
                    document.getElementById('qrModal').classList.add('hidden');
                    // Scroll & Focus Nama Barang field
                    document.getElementById('addBarangForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => {
                        document.getElementById('nama').focus();
                    }, 800);
                });
            };
        }
    };

    // Open scanner modal
    window.bukaModalQrScan = function(mode) {
        currentScanMode = mode;
        const modal = document.getElementById('qrModal');
        modal.classList.remove('hidden');
        initMainQrScanner();
    };

    // Register QR modal close events
    document.getElementById('closeQrModalBtn').addEventListener('click', () => {
        stopScanner().then(() => {
            document.getElementById('qrModal').classList.add('hidden');
        });
    });

    document.getElementById('qrModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('qrModal')) {
            stopScanner().then(() => {
                document.getElementById('qrModal').classList.add('hidden');
            });
        }
    });

    // Trigger QR Scanner from Header "QR Gateway" button
    document.getElementById('scanQrGatewayBtn').addEventListener('click', () => {
        bukaModalQrScan('cari');
    });

    // Trigger QR Scanner from form "Scan QR" button
    document.getElementById('formScanQrBtn').addEventListener('click', () => {
        bukaModalQrScan('input');
    });
});
