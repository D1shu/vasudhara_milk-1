<?php
require_once 'config.php';
require_once 'auth.php';

// If logged in, redirect to appropriate dashboard
if (Auth::isLoggedIn()) {
    if (Auth::isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/user/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50%, -50%); }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            animation: fadeInDown 1s;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            opacity: 0.9;
            animation: fadeInUp 1s;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn-hero {
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            margin: 10px;
            border: 2px solid white;
        }
        
        .btn-hero-primary {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-hero-primary:hover {
            background: transparent;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-hero-outline {
            background: transparent;
            color: white;
        }
        
        .btn-hero-outline:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .features-section {
            padding: 80px 0;
            background: #f7fafc;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 35px;
            color: white;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
        }
        
        .feature-text {
            color: #718096;
            line-height: 1.8;
        }
        
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .cta-section {
            padding: 80px 0;
            background: white;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2d3748;
        }
        
        .footer {
            background: #2d3748;
            color: white;
            padding: 30px 0;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .btn-hero {
                padding: 12px 30px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="mb-4">
                        <i class="fas fa-glass-whiskey fa-4x"></i>
                    </div>
                    <h1 class="hero-title">Vasudhara Dudh Sanjivni Youjna</h1>
                    <p class="hero-subtitle">
                        Streamline your milk distribution with automated order management, 
                        real-time tracking, and comprehensive reporting.
                    </p>
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to System
                        </a>
                        <a href="#features" class="btn btn-hero btn-hero-outline">
                            <i class="fas fa-info-circle"></i> Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center d-none d-lg-block">
                    <div id="loading-car-animation" style="width: 400px; height: 400px; margin: 0 auto;"></div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Key Features</h2>
                <p class="lead text-muted">Everything you need for efficient milk distribution management</p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Mobile OTP Login</h3>
                        <p class="feature-text">
                            Secure authentication using mobile OTP via Fast2SMS. 
                            No passwords to remember, just instant access.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3 class="feature-title">Weekly Orders</h3>
                        <p class="feature-text">
                            Submit weekly milk requirements with automatic calculations 
                            for quantity, bags, and allocations.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Advanced Analytics</h3>
                        <p class="feature-text">
                            Visual dashboards with charts showing trends, 
                            district-wise distribution, and real-time statistics.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <h3 class="feature-title">Route Management</h3>
                        <p class="feature-text">
                            Organize deliveries by routes with vehicle tracking 
                            and driver assignment for efficient logistics.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <h3 class="feature-title">PDF & Excel Reports</h3>
                        <p class="feature-text">
                            Generate comprehensive reports in PDF and Excel formats 
                            for dispatch sheets, summaries, and analysis.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Secure & Reliable</h3>
                        <p class="feature-text">
                            Built with security best practices including CSRF protection, 
                            SQL injection prevention, and activity logging.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fas fa-users"></i> 500+
                        </div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fas fa-building"></i> 1000+
                        </div>
                        <div class="stat-label">Anganwadi Centers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fas fa-clipboard-check"></i> 50K+
                        </div>
                        <div class="stat-label">Orders Processed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">
                            <i class="fas fa-glass-whiskey"></i> 5M+
                        </div>
                        <div class="stat-label">Liters Distributed</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="lead text-muted mb-4">
                Join hundreds of Anganwadi centers already using our system
            </p>
            <a href="login.php" class="btn btn-hero btn-hero-primary" style="border-color: var(--primary-color);">
                <i class="fas fa-rocket"></i> Access System Now
            </a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-2">
                <i class="fas fa-glass-whiskey"></i> 
                <strong>Vasudhara Milk Distribution System</strong>
            </p>
            <p class="mb-0">
                © <?php echo date('Y'); ?> All Rights Reserved | 
                <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="text-white">Contact Support</a>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Load Lottie animation
        lottie.loadAnimation({
            container: document.getElementById('loading-car-animation'),
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: 'Loading_car.json'
        });
    </script>
</body>
</html>