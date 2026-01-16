<?php
// index.php — Public Landing Page for Warzone Gym CRM
session_start();

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warzone Gym CRM — AI-Powered Fitness Coaching</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a2e',
                        secondary: '#16213e',
                        accent: '#0f3460',
                        highlight: '#e94560',
                        success: '#06d6a0'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .hero-gradient {
            background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
        }
        .feature-card {
            transition: all 0.4s ease;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(233, 69, 96, 0.4); }
            70% { box-shadow: 0 0 0 12px rgba(233, 69, 96, 0); }
            100% { box-shadow: 0 0 0 0 rgba(233, 69, 96, 0); }
        }
    </style>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    <!-- Navbar -->
    <nav class="hero-gradient text-white py-4">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                <h1 class="text-xl font-bold">Warzone Gym CRM</h1>
            </div>
            <div class="hidden md:flex space-x-6">
                <a href="#features" class="hover:text-highlight transition">Features</a>
                <a href="#about" class="hover:text-highlight transition">About</a>
                <a href="#testimonials" class="hover:text-highlight transition">Testimonials</a>
            </div>
            <div class="flex space-x-4">
                <a href="login.php" class="px-4 py-2 rounded-lg bg-white text-primary font-medium hover:bg-gray-100 transition">Login</a>
                <a href="register.php" class="px-4 py-2 rounded-lg bg-highlight text-white font-medium hover:bg-opacity-90 transition">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                <span class="block">Fitness Coaching,</span>
                <span class="block text-highlight">Rebuilt for the Filipino Athlete</span>
            </h1>
            <p class="text-lg md:text-xl max-w-3xl mx-auto mb-10 text-gray-300">
                Warzone Gym CRM combines AI coaching, attendance tracking, and personalized feedback — so you never train alone again.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="register.php" class="px-8 py-4 bg-highlight text-white font-bold rounded-lg text-lg hover:bg-opacity-90 transition transform hover:scale-105">
                    Get Started — Free
                </a>
                <a href="#features" class="px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-lg text-lg hover:bg-white hover:text-primary transition">
                    Learn More
                </a>
            </div>
            
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="w-16 h-16 bg-highlight rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-robot text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold">AI Coach</h3>
                    <p class="text-gray-300 mt-2">Filipino-fluent, science-backed, and brutally honest.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-success rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold">Progress Tracking</h3>
                    <p class="text-gray-300 mt-2">Workouts, mood, attendance — all in one place.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold">For Trainers & Gyms</h3>
                    <p class="text-gray-300 mt-2">Admin dashboard, bulk management, analytics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Why Warzone Stands Out</h2>
                <p class="text-gray-600">
                    Built by Filipino developers, for Filipino athletes and coaches — no generic Western templates here.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl border">
                    <div class="w-14 h-14 bg-highlight text-white rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Filipino-First AI Coach</h3>
                    <p class="text-gray-600">
                        Understands local context: adobo nutrition hacks, tinapa macros, and classic <em>"Anong bulkan?"</em> jokes. No robotic replies.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl border">
                    <div class="w-14 h-14 bg-success text-white rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Mobile-First & Offline Ready</h3>
                    <p class="text-gray-600">
                        Log workouts or chat with your AI coach — even with spotty internet. Syncs when back online.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl border">
                    <div class="w-14 h-14 bg-blue-600 text-white rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Science-Backed Coaching</h3>
                    <p class="text-gray-600">
                        Built on RP, Norton, Israetel principles — adapted for *Filipino bodies, diets, and schedules*.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl border">
                    <div class="w-14 h-14 bg-purple-600 text-white rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Mood & Recovery Tracking</h3>
                    <p class="text-gray-600">
                        Because gains vanish when stress is high. Warzone adapts to *you* — not just your reps.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl border">
                    <div class="w-14 h-14 bg-orange-500 text-white rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Realistic Filipino Nutrition</h3>
                    <p class="text-gray-600">
                        No salmon or quinoa mandates. Learn to eat adobo, sinigang, and pancit — *the smart way*.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl border">
                    <div class="w-14 h-14 bg-gray-800 text-white rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Open, Local, Proudly PH</h3>
                    <p class="text-gray-600">
                        Developed by BayaniH4ck team — Lebantino, Cortado, Gagarin, Tejada — for the Filipino fitness community.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="py-20 bg-secondary text-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">What Coaches & Athletes Say</h2>
                <p class="text-gray-300 max-w-2xl mx-auto">
                    Real feedback from early adopters in Manila, Cebu, and Davao.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-800 p-6 rounded-xl">
                    <div class="text-yellow-400 mb-2">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="italic mb-4">
                        “The AI actually *gets* my schedule as a jeepney driver. It suggested 20-min home workouts — and I gained 8kg in 4 months!”
                    </p>
                    <p class="font-bold">— Ashley, Caloocan</p>
                </div>

                <div class="bg-gray-800 p-6 rounded-xl">
                    <div class="text-yellow-400 mb-2">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="italic mb-4">
                        “Finally, a system that doesn’t shame me for eating rice. The ‘ulam-first’ advice changed everything.”
                    </p>
                    <p class="font-bold">— Jesrael, Candon</p>
                </div>

                <div class="bg-gray-800 p-6 rounded-xl">
                    <div class="text-yellow-400 mb-2">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="italic mb-4">
                        “My clients love the mood tracker. When attendance drops, Warzone *pivots* to recovery — not guilt. Genius.”
                    </p>
                    <p class="font-bold">— Coach Tomy Start, Mars</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 bg-highlight text-white text-center">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Train Like a Warzone Athlete?</h2>
            <p class="text-xl max-w-2xl mx-auto mb-8">
                Join 500+ Filipinos already using Warzone to transform their fitness — the *smart*, *sustainable*, *Filipino* way.
            </p>
            <a href="register.php" class="px-10 py-4 bg-white text-highlight font-bold text-lg rounded-lg hover:bg-gray-100 transition transform hover:scale-105 pulse inline-block">
                Sign Up Free — No Credit Card
            </a>
            <p class="mt-4 text-gray-200 text-sm">
                Free forever for individual users. Admin features for gyms (contact us).
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-primary text-white pt-12 pb-6">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-dumbbell text-highlight text-2xl"></i>
                        <h2 class="text-xl font-bold">Warzone Gym CRM</h2>
                    </div>
                    <p class="text-gray-400">
                        AI-powered fitness coaching built for the Filipino body, palate, and spirit.
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="login.php" class="hover:text-highlight">Login</a></li>
                        <li><a href="register.php" class="hover:text-highlight">Register</a></li>
                        <li><a href="#features" class="hover:text-highlight">Features</a></li>
                        <li><a href="#about" class="hover:text-highlight">About</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">For Coaches</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-highlight">Gym Integration</a></li>
                        <li><a href="#" class="hover:text-highlight">Bulk Onboarding</a></li>
                        <li><a href="#" class="hover:text-highlight">API Docs</a></li>
                        <li><a href="#" class="hover:text-highlight">Contact Sales</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Connect</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-highlight transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-highlight transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-highlight transition">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center text-gray-500 text-sm">
                <p>© 2026 Warzone Gym CRM by <strong>BayaniH4ck</strong></p>
                <p class="mt-1">
                    Lebantino, Aldwin C. • Cortado, Crisdhan Harben D. • Gagarin, Vincent Yuri P. • Tejada, John Lloyd R.
                </p>
                <p class="mt-2">
                    “Fitness is pain — but we make it *Filipino* fun.” 💪🇵🇭
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>