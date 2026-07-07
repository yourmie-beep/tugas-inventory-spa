<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - StockPro PWA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg-dark: #0f172a;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-main: #1e293b;
            --text-dim: #64748b;
            --border: #e2e8f0;
            --success-bg: #d1fae5;
            --success-border: #10b981;
            --success-text: #065f46;
            --danger-bg: #fee2e2;
            --danger-border: #f87171;
            --danger-text: #991b1b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow-x: hidden;
            padding: 2rem 0;
        }

        /* Background Blobs (Sesuai dengan Dashboard) */
        .background-blobs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            filter: blur(85px);
            border-radius: 50%;
            opacity: 0.35;
        }

        .blob-1 {
            width: 450px;
            height: 450px;
            background: #6366f1;
            top: -100px;
            right: -100px;
        }

        .blob-2 {
            width: 350px;
            height: 350px;
            background: #ec4899;
            bottom: -50px;
            left: -50px;
        }

        .blob-3 {
            width: 300px;
            height: 300px;
            background: #06b6d4;
            top: 30%;
            left: 20%;
        }

        /* Container Card */
        .login-card {
            width: 100%;
            max-width: 450px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            backdrop-filter: blur(12px);
            z-index: 10;
        }

        /* Header Login */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
            margin-bottom: 1rem;
        }

        .logo-icon svg {
            width: 30px;
            height: 30px;
        }

        .login-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .login-header h1 span {
            color: var(--primary);
        }

        .login-header p {
            font-size: 0.875rem;
            color: var(--text-dim);
            margin-bottom: 1.25rem;
        }

        /* Badges */
        .badges-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.4rem;
            margin-bottom: 1rem;
        }

        .badge-pill {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 100px;
            border: 1px solid transparent;
        }

        .badge-p3 { background: #ecfdf5; color: #10b981; border-color: #a7f3d0; }
        .badge-p4 { background: #f5f3ff; color: #8b5cf6; border-color: #ddd6fe; }
        .badge-p5 { background: #fffbeb; color: #f59e0b; border-color: #fde68a; }
        .badge-p6 { background: #fef2f2; color: #ef4444; border-color: #fca5a5; }
        .badge-p7 { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
        .badge-p8 { background: #ecfeff; color: #06b6d4; border-color: #a5f3fc; }
        .badge-p9 { background: #faf5ff; color: #d946ef; border-color: #f5d0fe; }

        .separator {
            height: 1px;
            background: #e2e8f0;
            margin: 1.5rem 0;
            width: 100%;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            text-align: left;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        input {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: all 0.2s;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-dim);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem;
        }

        .password-toggle:hover {
            color: var(--text-main);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.85rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Demo Account Box */
        .demo-box {
            background: #e6fcf5;
            border: 1px solid #c3fae8;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            color: #2b8a3e;
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            line-height: 1.4;
        }

        .demo-box-title {
            font-weight: bold;
            color: #2b8a3e;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Footer Info */
        .footer-info {
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-dim);
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .badge-footer {
            background: #faf5ff;
            color: #d946ef;
            border: 1px solid #f5d0fe;
            padding: 0.15rem 0.5rem;
            border-radius: 100px;
            font-weight: 500;
        }

        .page-footer {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #94a3b8;
            text-align: center;
            font-family: 'Inter', sans-serif;
            width: 100%;
        }

        /* Alert Box */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: none;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid transparent;
            text-align: left;
        }

        .alert-danger {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger-text);
        }

        /* Spinner */
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <!-- Scripts to prevent FOUC / Redirect if already logged in -->
    <script>
        if (localStorage.getItem('pwa_token')) {
            window.location.replace('index.html');
        }
    </script>

    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="login-card">
        <div class="login-header">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
            </div>
            <h1>Toko <span>PWA</span></h1>
            <p>Aplikasi Terintegrasi — Pertemuan 3 s/d 9</p>

            <div class="badges-container">
                <span class="badge-pill badge-p3">P3 · Fetch API</span>
                <span class="badge-pill badge-p4">P4 · PWA</span>
                <span class="badge-pill badge-p5">P5 · POST</span>
                <span class="badge-pill badge-p6">P6 · DELETE</span>
                <span class="badge-pill badge-p7">P7 · PUT</span>
                <span class="badge-pill badge-p8">P8 · Deploy</span>
                <span class="badge-pill badge-p9">P9 · Auth</span>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Alert Error -->
        <div class="alert alert-danger" id="errorAlert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span id="errorMessage">Username atau password salah.</span>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" placeholder="Masukkan username..." required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" placeholder="Masukkan password..." required autocomplete="current-password">
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Tampilkan password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" stroke-linecap="round" stroke-linejoin="round" id="eyeIcon">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round" id="loginIcon">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                <span id="btnText">Masuk ke Dashboard</span>
            </button>
        </form>

        <!-- Demo Account Box -->
        <div class="demo-box">
            <div class="demo-box-title">
                🔑 Akun Demo (Password: <span style="font-family: monospace; background: rgba(0,0,0,0.06); padding: 0.1rem 0.3rem; border-radius: 4px; font-weight: normal;">password</span> )
            </div>
            <div>Username: <strong>admin</strong> atau <strong>kasir</strong></div>
        </div>

        <!-- Footer Info -->
        <div class="footer-info">
            <span class="badge-footer">P9 · Token-Based Authentication</span>
            <span>— Token disimpan di</span>
            <code style="background: #faf5ff; border: 1px solid #f5d0fe; color: #d946ef; padding: 0.1rem 0.3rem; border-radius: 4px; font-family: monospace;">localStorage</code>
        </div>
    </div>

    <!-- Teacher's copyright footer -->
    <div class="page-footer">
        Sucipto, M.Kom &bull; Platform Belajar PWA
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        const eyeIcon = document.getElementById('eyeIcon');
        const btnSubmit = document.getElementById('btnSubmit');
        const btnText = document.getElementById('btnText');
        const loginIcon = document.getElementById('loginIcon');
        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');

        // Toggle Password Visibility
        passwordToggle.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        });

        // Form Submit
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Hide previous errors
            errorAlert.style.display = 'none';

            // Loading state
            btnSubmit.disabled = true;
            btnText.textContent = 'Memverifikasi...';
            loginIcon.style.display = 'none';
            
            // Create spinner
            const spinner = document.createElement('div');
            spinner.className = 'spinner';
            spinner.id = 'submitSpinner';
            btnSubmit.insertBefore(spinner, btnText);

            const username = usernameInput.value;
            const password = passwordInput.value;

            try {
                const response = await fetch('../api-toko/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    // Success! Store token and redirect
                    localStorage.setItem('pwa_token', result.data.token);
                    localStorage.setItem('pwa_user', result.data.username);
                    
                    btnText.textContent = 'Sukses! Mengalihkan...';
                    
                    setTimeout(() => {
                        window.location.replace('index.html');
                    }, 500);
                } else {
                    throw new Error(result.message || 'Username atau password salah.');
                }
            } catch (error) {
                // Show Error
                errorMessage.textContent = error.message;
                errorAlert.style.display = 'flex';
                
                // Reset submit button
                btnSubmit.disabled = false;
                btnText.textContent = 'Masuk ke Dashboard';
                loginIcon.style.display = 'inline-block';
                const existingSpinner = document.getElementById('submitSpinner');
                if (existingSpinner) {
                    existingSpinner.remove();
                }
            }
        });
    </script>
</body>
</html>
