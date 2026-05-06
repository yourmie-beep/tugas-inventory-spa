document.addEventListener('DOMContentLoaded', () => {
    const barangBody = document.getElementById('barangBody');
    const loader = document.getElementById('loader');
    const errorMessage = document.getElementById('errorMessage');
    const itemCount = document.getElementById('itemCount');
    const refreshBtn = document.getElementById('refreshBtn');

    const addBarangForm = document.getElementById('addBarangForm');

    async function fetchBarang() {
        // Reset UI
        barangBody.innerHTML = '';
        loader.classList.remove('hidden');
        errorMessage.classList.add('hidden');

        try {
            const response = await fetch('../api-toko/api.php');
            const result = await response.json();

            if (result.status === 'success') {
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
        
        if (data.length === 0) {
            barangBody.innerHTML = '<tr><td colspan="6" style="text-align:center">No data found</td></tr>';
            return;
        }

        data.forEach(item => {
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
            `;
            barangBody.appendChild(tr);
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
        const data = {
            nama: formData.get('nama'),
            harga: formData.get('harga'),
            stok: formData.get('stok'),
            deskripsi: formData.get('deskripsi')
        };

        const submitBtn = addBarangForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        try {
            // UI Feedback: Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0"></div>';

            const response = await fetch('../api-toko/tambah_barang.php', {
                method: 'POST',
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
                
                // Refresh table without full page reload
                await fetchBarang();
                
                // Scroll to table to see the new data
                document.getElementById('barangTable').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (error) {
            console.error('Submit Error:', error);
            alert('❌ Oops! ' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    refreshBtn.addEventListener('click', fetchBarang);

    // Initial Fetch
    fetchBarang();
});
