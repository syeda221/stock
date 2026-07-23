<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Unilever WMS - Prowave Technologies</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary: #025091;
            --primary-dark: #013765;
            --primary-gradient: linear-gradient(135deg, #025091 0%, #1e40af 50%, #2563eb 100%);
            --accent: #38bdf8;
            --bg-dark: #070b12;
            --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        * {
            box-sizing: border-box;
            font-family: var(--font-family);
        }

        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            background-color: var(--bg-dark);
            overflow: hidden;
        }

        .login-wrapper {
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: radial-gradient(circle at 10% 20%, rgba(2, 80, 145, 0.4) 0%, transparent 45%),
                        radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.25) 0%, transparent 45%),
                        var(--bg-dark);
            padding: 1rem;
        }

        /* Ambient Glow Spheres */
        .glow-sphere-1 {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(2, 80, 145, 0.35) 0%, rgba(0, 0, 0, 0) 70%);
            top: -80px;
            left: -80px;
            pointer-events: none;
            filter: blur(40px);
            animation: pulse 8s infinite alternate ease-in-out;
        }

        .glow-sphere-2 {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
            bottom: -100px;
            right: -80px;
            pointer-events: none;
            filter: blur(50px);
            animation: pulse 10s infinite alternate-reverse ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1) translate(0, 0); opacity: 0.7; }
            100% { transform: scale(1.1) translate(15px, -15px); opacity: 1; }
        }

        /* Main Container Card */
        .login-card-container {
            width: 100%;
            max-width: 1020px;
            max-height: calc(100vh - 2rem);
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.85);
            overflow: hidden;
            z-index: 10;
        }

        /* Brand Panel (Left Side - Translucent Glassmorphism) */
        .brand-panel {
            background: linear-gradient(135deg, rgba(8, 16, 32, 0.72) 0%, rgba(2, 35, 70, 0.78) 100%),
                        url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&q=80&w=1000') center/cover no-repeat;
            background-blend-mode: overlay;
            padding: 2rem 2.2rem;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            height: 100%;
        }

        .logo-box {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(11, 19, 36, 0.55);
            padding: 8px 16px;
            border-radius: 14px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            width: fit-content;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
            margin-bottom: 0.8rem;
        }

        .logo-img {
            max-height: 38px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .brand-badge {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #38bdf8;
        }

        .prowave-header-tag {
            font-size: 0.82rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.1;
            letter-spacing: 0.3px;
        }

        /* Hero Text Container Box with Translucent Glass Backdrop */
        .brand-hero-box {
            background: rgba(11, 19, 36, 0.52);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            margin-bottom: 0.8rem;
        }

        .brand-title {
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.4px;
            margin-bottom: 0.5rem;
            color: #ffffff;
            text-shadow: 0 2px 6px rgba(0,0,0,0.5);
        }

        .brand-title span.highlight-cyan {
            color: #38bdf8;
            text-shadow: 0 0 16px rgba(56, 189, 248, 0.5);
        }

        .brand-desc {
            color: #f1f5f9;
            font-size: 0.88rem;
            line-height: 1.5;
            margin-bottom: 0;
            font-weight: 500;
            text-shadow: 0 1px 4px rgba(0,0,0,0.6);
        }

        /* Feature Cards - Translucent Glass Cards */
        .feature-grid {
            display: grid;
            gap: 10px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(11, 19, 36, 0.52);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
        }

        .feature-item:hover {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(56, 189, 248, 0.5);
            transform: translateX(4px);
        }

        .feature-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(2, 80, 145, 0.65);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .feature-text h6 {
            margin: 0;
            font-size: 0.86rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.2px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }

        .feature-text p {
            margin: 0;
            font-size: 0.76rem;
            color: #cbd5e1;
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }

        .panel-footer-text {
            color: #ffffff !important;
            font-size: 0.8rem;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.7);
        }

        /* Form Panel (Right Side) */
        .form-panel {
            padding: 2.2rem 2.8rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .form-header h3 {
            font-size: 1.7rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.2rem;
            letter-spacing: -0.4px;
        }

        .form-header p {
            color: #475569;
            font-size: 0.88rem;
            margin-bottom: 1.4rem;
            font-weight: 500;
        }

        .form-group-custom {
            margin-bottom: 1.1rem;
        }

        .form-label-custom {
            font-size: 0.83rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.4rem;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            font-size: 1.05rem;
            transition: color 0.2s ease;
            pointer-events: none;
        }

        .form-control-custom {
            width: 100%;
            height: 46px;
            padding: 0.5rem 1rem 0.5rem 42px;
            font-size: 0.92rem;
            font-weight: 600;
            color: #0f172a;
            background-color: #f8fafc;
            border: 1.5px solid #94a3b8;
            border-radius: 10px;
            transition: all 0.25s ease;
        }

        .form-control-custom:focus {
            background-color: #ffffff;
            border-color: #025091;
            box-shadow: 0 0 0 3.5px rgba(2, 80, 145, 0.18);
            outline: none;
        }

        .form-control-custom:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #025091;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #475569;
            cursor: pointer;
            font-size: 1.05rem;
            padding: 4px;
            border-radius: 6px;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #0f172a;
        }

        .btn-submit {
            width: 100%;
            height: 48px;
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-size: 0.96rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            box-shadow: 0 8px 18px -4px rgba(2, 80, 145, 0.4);
            transition: all 0.25s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 0.8rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 22px -3px rgba(2, 80, 145, 0.5);
            background: linear-gradient(135deg, #013765 0%, #1d4ed8 50%, #1e40af 100%);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-danger-custom {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.83rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.2rem;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        /* Prowave Footer */
        .security-footer {
            margin-top: 1.4rem;
            padding-top: 0.9rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .prowave-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #334155;
            font-weight: 600;
        }

        .prowave-highlight {
            color: #025091;
            font-weight: 800;
        }

        /* Responsive Fixes */
        @media (max-width: 991.98px) {
            html, body {
                height: auto;
                overflow-y: auto;
            }
            .login-wrapper {
                height: auto;
                min-height: 100vh;
                padding: 1.5rem 1rem;
            }
            .login-card-container {
                max-height: none;
            }
            .brand-panel {
                padding: 2rem 1.5rem;
            }
            .form-panel {
                padding: 2rem 1.5rem;
            }
            .brand-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="glow-sphere-1"></div>
    <div class="glow-sphere-2"></div>

    <div class="login-card-container">
        <div class="row g-0 align-items-stretch">
            <!-- Left Side: Unilever WMS & Prowave Technologies -->
            <div class="col-lg-6 brand-panel">
                <div>
                    <!-- Logo / Brand Header -->
                    <div class="logo-box mb-3">
                        @if(file_exists(public_path('logo.png')))
                            <img src="{{ asset('logo.png') }}" alt="Unilever Logo" class="logo-img">
                        @else
                            <i class="bi bi-box-seam-fill text-info fs-4"></i>
                        @endif
                        <div>
                            <div class="brand-badge">UNILEVER WMS</div>
                            <div class="prowave-header-tag">PROWAVE TECHNOLOGIES</div>
                        </div>
                    </div>

                    <div class="brand-hero-box">
                        <h2 class="brand-title">
                            Smart FMCG <span class="highlight-cyan">Warehouse</span> Portal
                        </h2>
                        <p class="brand-desc">
                            Streamlining Unilever stock operations, batch tracking, Quality Control (QC), inbound receipts, and outbound logistics with precision.
                        </p>
                    </div>

                </div>

                <!-- Feature Highlights (Dark Glass High Contrast) -->
                <div class="feature-grid my-2">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <div class="feature-text">
                            <h6>Real-time Stock Management</h6>
                            <p>Instant batch tracking, opening stock & pallet allocation</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="feature-text">
                            <h6>Quality Control (QC) Gate</h6>
                            <p>Automated QC status verification & clearance logs</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-truck-front"></i>
                        </div>
                        <div class="feature-text">
                            <h6>Outbound & Dispatch Sync</h6>
                            <p>Effortless Delivery Challan (DC) and invoice generation</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Footer Info -->
                <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-3 border border-white border-opacity-20 panel-footer-text" style="background: rgba(11, 19, 36, 0.52); backdrop-filter: blur(12px);">
                    <span><i class="bi bi-cpu me-1 text-info"></i> Powered by <strong>Prowave Technologies</strong></span>
                    <span>v2.4 Enterprise</span>
                </div>

            </div>

            <!-- Right Side: Login Form -->
            <div class="col-lg-6 form-panel">
                <div class="form-header">
                    <h3>Sign In to WMS</h3>
                    <p>Enter your credentials to access the Unilever warehouse dashboard.</p>
                </div>

                @if($errors->any())
                    <div class="alert-danger-custom">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" id="loginForm">
                    @csrf

                    <!-- Email Field -->
                    <div class="form-group-custom">
                        <label class="form-label-custom" for="email">
                            Email Address
                        </label>
                        <div class="input-wrapper">
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control-custom @error('email') is-invalid @enderror" 
                                   placeholder="admin@unilever-wms.com" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autocomplete="email"
                                   autofocus>
                            <i class="bi bi-envelope input-icon"></i>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group-custom">
                        <label class="form-label-custom" for="password">
                            Password
                        </label>
                        <div class="input-wrapper">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control-custom" 
                                   placeholder="••••••••••••" 
                                   required 
                                   autocomplete="current-password">
                            <i class="bi bi-lock input-icon"></i>
                            <button type="button" class="password-toggle" id="togglePasswordBtn" title="Toggle password visibility">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span>Sign In to Dashboard</span>
                        <i class="bi bi-arrow-right-short fs-4"></i>
                    </button>
                </form>

                <!-- Prowave Technologies Footer -->
                <div class="security-footer">
                    <div class="prowave-badge">
                        <i class="bi bi-cpu-fill text-primary me-1"></i>
                        <span>Powered by <span class="prowave-highlight">Prowave Technologies</span></span>
                    </div>
                    <div style="color: #64748b; font-size: 0.78rem;">
                        Unilever WMS v2.4
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle Password Visibility
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            if (type === 'text') {
                togglePasswordIcon.classList.remove('bi-eye');
                togglePasswordIcon.classList.add('bi-eye-slash');
            } else {
                togglePasswordIcon.classList.remove('bi-eye-slash');
                togglePasswordIcon.classList.add('bi-eye');
            }
        });
    }

    // Submit Loading State
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span>Authenticating...</span>
            `;
        });
    }
</script>

</body>
</html>


