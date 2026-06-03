document.addEventListener('DOMContentLoaded', () => {
    const barangBody = document.getElementById('barangBody');
    const loader = document.getElementById('loader');
    const errorMessage = document.getElementById('errorMessage');
    const itemCount = document.getElementById('itemCount');
    const refreshBtn = document.getElementById('refreshBtn');

    const addBarangForm = document.getElementById('addBarangForm');

    // Dynamic API Base URL to support both local development and production hosting
    const API_BASE = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || window.location.hostname.includes('localhost')
        ? '../api-toko'
        : window.location.protocol + '//stockpro.42web.io/api-toko';

    async function fetchBarang() {
        // Reset UI
        barangBody.innerHTML = '';
        loader.classList.remove('hidden');
        errorMessage.classList.add('hidden');

        try {
            const response = await fetch(`${API_BASE}/api.php`);
            const result = await response.json();

            if (result.status === 'success') {
                const serverStatusEl = document.getElementById('serverStatus');
                if (serverStatusEl && result.server) {
                    serverStatusEl.innerHTML = `🟢 Server: <strong>${result.server}</strong>`;
                }
                renderData(result.data);
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
            barangBody.innerHTML = '<tr><td colspan="7" style="text-align:center">No data found</td></tr>';
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

            tr.innerHTML = `
                <td>#${item.id}</td>
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
        const data = {
            nama: formData.get('nama'),
            harga: formData.get('harga'),
            stok: formData.get('stok'),
            deskripsi: formData.get('deskripsi')
        };
        const id = formData.get('id');

        if (id) {
            data.id = id;
        }

        const submitBtn = addBarangForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        try {
            // UI Feedback: Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0"></div>';

            const url = id ? `${API_BASE}/edit_barang.php` : `${API_BASE}/tambah_barang.php`;
            const method = id ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
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
                
                // Refresh table without full page reload
                await fetchBarang();
                
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

    refreshBtn.addEventListener('click', fetchBarang);

    // Initial Fetch
    fetchBarang();

    // Global functions for Edit and Delete
    window.editBarang = function(id, nama, harga, stok, deskripsi) {
        document.getElementById('barangId').value = id;
        document.getElementById('nama').value = nama;
        document.getElementById('harga').value = harga;
        document.getElementById('stok').value = stok;
        document.getElementById('deskripsi').value = deskripsi;

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
                fetchBarang(); // Refresh the list
            } else {
                throw new Error(result.message || 'Gagal menghapus data');
            }
        } catch (error) {
            console.error('Delete Error:', error);
            alert('❌ Oops! ' + error.message);
        }
    };
});
