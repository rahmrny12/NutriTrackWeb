<?php
session_start();
include 'config.php';
include 'db-functions.php';

// contoh kalau tidak perlu cek admin:
$testimonials = getLatestTestimonials();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriTrack - reviews</title>
    <link href="./output.css" rel="stylesheet">
    <style>
        body {
            /* font-family: 'Inter', sans-serif; */
            font-family: 'Plus Jakarta Sans', sans-serif;
            /* font-family: "Geist", sans-serif; */
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-menu-panel {
            transform-origin: top right;
        }

        .mobile-menu-panel.animate-open {
            animation: mobileMenuIn 0.25s ease forwards;
        }

        .mobile-menu-panel.animate-close {
            animation: mobileMenuOut 0.2s ease forwards;
        }

        @keyframes mobileMenuIn {
            from {
                opacity: 0;
                transform: translateY(-12px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes mobileMenuOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            to {
                opacity: 0;
                transform: translateY(-8px) scale(0.95);
            }
        }

        #menu-toggle-btn svg {
            transition: transform 0.2s ease;
        }

        #menu-toggle-btn[aria-expanded="true"] svg {
            transform: rotate(90deg);
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
        rel="stylesheet">
</head>

<body>

    <!-- Header -->
    <header id="sticky-header" class="fixed z-50 w-full transition-all duration-300 ease-in-out py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="relative flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold">NutriTrack+</h1>
                </div>
                <ul class="hidden md:flex items-center space-x-8">
                    <li><a href="index.php" class="transition duration-200 transform">Home</a></li>
                    <li><a href="about.php" class="transition duration-200 transform">Tentang Kami</a></li>
                    <li><a href="features.php" class="transition duration-200 transform">Unggulan</a>
                    </li>
                    <li><a href="reviews.php" class="transition duration-200 transform">Reviews</a></li>
                    <li><a href="#" class="transition duration-200 transform">Unduh</a></li>
                </ul>
                <div class="hidden md:flex items-center space-x-3">
                    <a href="signin.php"
                        class="whitespace-nowrap transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none">
                        Sign In
                    </a>
                    <a href="signup.php"
                        class="inline-flex justify-center gap-2 text-white bg-[#3dccc7] hover:bg-[#68d8d6] px-4 py-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                        Sign Up
                    </a>
                </div>
                <div class="md:hidden">
                    <button id="menu-toggle-btn" type="button" aria-expanded="false" aria-controls="mobile-menu"
                        aria-label="Toggle navigation"
                        class="p-2 rounded-lg transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3dccc7]">
                        <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
            </nav>
            <div id="mobile-menu" class="md:hidden hidden mt-3">
                <div class="mobile-menu-panel card shadow-lg rounded-xl p-6 space-y-4">
                    <div class="flex flex-col space-y-3">
                        <a href="index.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Home</a>
                        <a href="about.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">About
                            Us</a>
                        <a href="features.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Features</a>
                        <a href="reviews.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Reviews</a>
                        <a href="#"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Download</a>
                    </div>
                    <div class="flex flex-col gap-3 py-3 border-t border-neutral-200 dark:border-neutral-700">
                        <a href="signin.php"
                            class="inline-flex justify-center items-center gap-2 text-sm font-medium rounded-md py-2 px-4 card transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3dccc7]">Sign
                            In</a>
                        <a href="signup.php"
                            class="inline-flex justify-center items-center gap-2 text-sm font-medium rounded-md py-2 px-4 text-white bg-[#3dccc7] hover:bg-[#68d8d6] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3dccc7]">Sign
                            Up</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main>
        <!-- Hero / Intro -->
        <section class="relative w-full min-h-screen flex items-center overflow-hidden px-[5%] py-20">

            <div
                class="absolute top-1/2 right-0 w-[600px] h-[600px] bg-primary/5 rounded-full blur-[100px] -translate-y-1/2 pointer-events-none">
            </div>

            <div class="relative z-10 max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">

                <div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
                        Disukai oleh <span class="text-primary">10.000+</span> <br> Pengguna Sehat.
                    </h1>
                    <p class="text-lg opacity-80 mb-8 leading-relaxed max-w-md">
                        Lihat <i>review</i> dari pengguna NutriTrack yang berhasil mencapai tujuan kesehatan mereka
                        melalui kebiasaan yang lebih baik dan tracking nutrisi yang cerdas.
                    </p>

                    <div class="flex items-center gap-4">
                        <button
                            class="inline-flex items-center justify-center gap-2 bg-primary text-white px-8 py-3.5 rounded-full font-semibold transition-all duration-300 hover:bg-primary/90 hover:-translate-y-1 hover:shadow-[0_10px_20px_rgba(61,204,199,0.3)]">
                            Lihat
                        </button>
                        <button
                            class="inline-flex items-center justify-center gap-2 card opacity-80 px-6 py-3 rounded-full font-semibold backdrop-blur-sm transition duration-300 hover:bg-white/5">
                            Ceritakan Pengalamanmu
                        </button>
                    </div>

                    <div class="mt-10 flex items-center gap-3">
                        <div class="flex text-yellow-400 text-xl">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <span class="font-semibold">4.9/5</span>
                        <span class="opacity-70 text-sm border-l border-gray-700 pl-3"><i>Rating</i> Pengguna</span>
                    </div>
                </div>

                <div class="relative h-[500px] flex items-center justify-center">

                    <div class="absolute inset-0 border border-white/5 rounded-full scale-75 animate-pulse"></div>
                    <div class="absolute inset-0 border border-white/5 rounded-full scale-110 opacity-50"></div>

                    <div class="relative z-20 backdrop-blur-xl card p-8 rounded-3xl shadow-2xl max-w-md">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Sarah"
                                    class="w-12 h-12 rounded-full bg-gray-700">
                                <div>
                                    <h4 class="font-bold">Budi Utomo</h4>
                                    <p class="text-primary text-sm">Berhasil turun 15kg dalam 3 bulan</p>
                                </div>
                            </div>
                            <i class="fas fa-quote-right text-4xl text-white/10"></i>
                        </div>
                        <p class="opacity-60 leading-relaxed mb-4">
                            "Aplikasi ini benar-benar mengubah cara saya memahami makanan. Fitur AI-nya sangat akurat
                            dan
                            pilihan resepnya enak-enak!"
                        </p>
                        <div class="flex gap-1 text-yellow-400 text-sm">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>

                    <div
                        class="absolute top-10 right-10 z-10 card p-4 rounded-2xl shadow-xl w-64 transform rotate-6 opacity-60 hover:opacity-100 transition duration-300">
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-500 text-xs font-bold">
                                BJ</div>
                            <div class="flex text-yellow-400 text-xs">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                <i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="opacity-60 text-xs">"Tracking makro jadi super gampang."</p>
                    </div>

                    <div
                        class="absolute bottom-10 left-0 z-10 card p-4 rounded-2xl shadow-xl w-64 transform -rotate-6 opacity-60 hover:opacity-100 transition duration-300">
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-500 text-xs font-bold">
                                AD</div>
                            <div class="flex text-yellow-400 text-xs">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                <i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                        <p class="opacity-60 text-xs">"UI-nya bersih dan modern banget!"</p>
                    </div>

                </div>
            </div>
        </section>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Reviews List -->
            <section id="list" class="py-16 sm:py-24">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold">Apa Kata Pengguna Kami</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <?php if (empty($testimonials)) { ?>

                        <!-- Jika tidak ada data -->
                        <div class="col-span-3 text-center opacity-70">
                            Belum ada testimoni.
                        </div>

                    <?php } else { ?>

                        <?php foreach ($testimonials as $r): ?>

                            <?php
                            // Tentukan avatar
                            $initial = strtoupper(substr($r['name'], 0, 1));
                            $avatar = $r['avatar_url']
                                ? htmlspecialchars($r['avatar_url'])
                                : "https://placehold.co/80x80/34373b/ffffff?text={$initial}";

                            // Username fallback
                            $username = !empty($r['username'])
                                ? htmlspecialchars($r['username'])
                                : strtolower($initial . "user");
                            ?>

                            <div class="p-8 rounded-md card transition-all duration-300 hover:border-[#0F9E99] flex flex-col">

                                <!-- Message -->
                                <p class="text-base leading-relaxed mb-8 flex-grow">
                                    <?= htmlspecialchars($r['message']); ?>
                                </p>

                                <!-- User Info -->
                                <div class="flex items-center gap-3 mt-auto">
                                    <img src="<?= $avatar; ?>" class="h-12 w-12 rounded-full object-cover" />
                                    <div>
                                        <div class="font-medium"><?= htmlspecialchars($r['name']); ?></div>
                                        <div class="text-sm opacity-80">@<?= $username; ?></div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    <?php } ?>

                </div>
            </section>

            <!-- Call to Action -->
            <section class="py-16">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h3 class="text-2xl sm:text-3xl font-bold">Siap Memulai?</h3>
                    <p class="mt-3 opacity-80">Unduh NutriTrack dan mulai perjalanan hidup sehatmu hari ini.</p>
                    <div class="mt-6 flex justify-center gap-3">
                        <a href="#"
                            class="px-5 py-3 rounded-md text-sm font-medium text-white bg-[#3dccc7] hover:bg-[#68d8d6]">Unduh
                            Aplikasi</a>
                        <a href="features.php" class="px-5 py-3 rounded-md text-sm font-medium card">Lihat Fitur</a>
                    </div>
                </div>
            </section>

        </div>
    </main>


    <!-- Footer -->
    <footer class="my-24 sm:py-24">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 items-start">
                <div class="space-y-4">
                    <a href="https://www.instagram.com/nutritrack.plus/"
                        class="text-lg hover:underline block">@nutritrack.plus</a>

                    <div class="flex space-x-4">

                        <!-- Instagram -->
                        <a href="https://www.instagram.com/nutritrack.plus/" target="_blank"
                            class="hover:text-[#E1306C] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 3h9A4.5 4.5 0 0 1 21 7.5v9A4.5 4.5 0 0 1 16.5 21h-9A4.5 4.5 0 0 1 3 16.5v-9A4.5 4.5 0 0 1 7.5 3Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.25 6.75h.008v.008h-.008V6.75Z" />
                            </svg>
                        </a>

                        <!-- YouTube -->
                        <a href="https://www.youtube.com/watch?v=7qhEhwEtS1Q" target="_blank"
                            class="hover:text-red-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12c0-2.485 0-3.727.637-4.662a4 4 0 0 1 1.101-1.101C4.923 5.6 6.165 5.6 8.65 5.6h6.7c2.485 0 3.727 0 4.662.637a4 4 0 0 1 1.101 1.101C21.75 8.273 21.75 9.515 21.75 12s0 3.727-.637 4.662a4 4 0 0 1-1.101 1.101c-.935.637-2.177.637-4.662.637H8.65c-2.485 0-3.727 0-4.662-.637a4 4 0 0 1-1.101-1.101C2.25 15.727 2.25 14.485 2.25 12Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 9.75v4.5l4-2.25-4-2.25Z" />
                            </svg>
                        </a>
                    </div>
                </div>


                <div>
                    <h4 class="font-medium">Lebih Lanjut</h4>
                    <ul class="mt-4 space-y-4 text-sm">
                        <li><a href="index.php" class="opacity-80">Beranda</a>
                        </li>
                        <li><a href="features.php" class="opacity-80">Unggulan</a>
                        </li>
                        <li><a href="#" class="opacity-80">Unduh</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-medium">Dukungan</h4>
                    <ul class="mt-4 space-y-4 text-sm">
                        <li><a href="4ever-young.php" class="opacity-80">4Ever
                                Young</a></li>
                        <li><a href="#" class="opacity-80">Community</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-medium">Apa Kata Mereka</h4>
                    <ul class="mt-4 space-y-4 text-sm">
                        <li><a href="reviews.php" class="opacity-80">Reviews</a>
                        </li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <div class="relative inline-block text-left w-full">
                        <div>
                            <button id="dropdownButton" type="button"
                                class="inline-flex justify-start w-full rounded-md card shadow-sm px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#000000]"
                                aria-expanded="true" aria-haspopup="true">
                                Language
                                <svg class="-mr-1 ml-auto h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div id="dropdownMenu"
                            class="hidden origin-top-right absolute right-0 mt-2 w-auto rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none fade-in"
                            role="menu" aria-orientation="vertical" aria-labelledby="dropdownButton">
                            <div class="py-1" role="none">
                                <a href="#" class="block px-4 py-2 text-sm" role="menuitem">English</a>
                                <a href="#" class="block px-4 py-2 text-sm" role="menuitem">Bahasa Indonesia</a>
                            </div>
                        </div>
                    </div>

                    <div class="flex space-x-2">
                        <div id="theme-switcher" class="flex p-1 rounded-full card shadow-sm">
                            <button id="system-btn"
                                class="flex items-center justify-center p-2 rounded-full transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                                </svg>
                            </button>

                            <button id="light-btn"
                                class="flex items-center justify-center p-2 rounded-full transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                </svg>
                            </button>

                            <button id="dark-btn"
                                class="flex items-center justify-center p-2 rounded-full transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-20 pt-20 dark:text-gray-400 text-md">
                <p>© 2025 Made By 4Ever Young</p>
            </div>
        </div>
    </footer>

    <script>
        // === Mobile Menu Logic ===
        const menuToggleBtn = document.getElementById('menu-toggle-btn');
        const menuIconPath = document.querySelector('#menu-icon path');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuPanel = mobileMenu ? mobileMenu.querySelector('.mobile-menu-panel') : null;

        if (menuToggleBtn && menuIconPath && mobileMenu && mobileMenuPanel) {
            const MOBILE_MENU_ICONS = {
                open: 'M4 6h16M4 12h16m-7 6h7',
                close: 'M6 18L18 6M6 6l12 12'
            };

            const setMenuIcon = (state) => {
                menuIconPath.setAttribute('d', state === 'open' ? MOBILE_MENU_ICONS.close : MOBILE_MENU_ICONS.open);
            };

            const openMobileMenu = () => {
                mobileMenu.classList.remove('hidden');
                mobileMenuPanel.classList.remove('animate-close');
                mobileMenuPanel.classList.remove('animate-open');
                void mobileMenuPanel.offsetWidth;
                mobileMenuPanel.classList.add('animate-open');
                menuToggleBtn.setAttribute('aria-expanded', 'true');
                setMenuIcon('open');
                document.body.style.overflow = 'hidden';
            };

            const closeMobileMenu = ({
                focusToggle = false
            } = {}) => {
                mobileMenuPanel.classList.remove('animate-open');
                mobileMenuPanel.classList.add('animate-close');
                menuToggleBtn.setAttribute('aria-expanded', 'false');
                setMenuIcon('close');
                document.body.style.overflow = '';
                if (focusToggle) {
                    menuToggleBtn.focus();
                }
            };

            mobileMenuPanel.addEventListener('animationend', (event) => {
                if (event.animationName === 'mobileMenuOut') {
                    mobileMenu.classList.add('hidden');
                    mobileMenuPanel.classList.remove('animate-close');
                }
            });

            menuToggleBtn.addEventListener('click', () => {
                const isExpanded = menuToggleBtn.getAttribute('aria-expanded') === 'true';
                if (isExpanded) {
                    closeMobileMenu();
                } else {
                    openMobileMenu();
                }
            });

            mobileMenu.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => closeMobileMenu());
            });

            document.addEventListener('click', (event) => {
                const isClickInsideMenu = mobileMenu.contains(event.target) || menuToggleBtn.contains(event.target);
                if (!isClickInsideMenu && menuToggleBtn.getAttribute('aria-expanded') === 'true') {
                    closeMobileMenu();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && menuToggleBtn.getAttribute('aria-expanded') === 'true') {
                    closeMobileMenu({
                        focusToggle: true
                    });
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768 && menuToggleBtn.getAttribute('aria-expanded') === 'true') {
                    closeMobileMenu();
                    mobileMenu.classList.add('hidden');
                    mobileMenuPanel.classList.remove('animate-close');
                }
            });
        }

        // === Dropdown Menu Logic ===
        const dropdownButton = document.getElementById('dropdownButton');
        const dropdownMenu = document.getElementById('dropdownMenu');

        dropdownButton.addEventListener('click', () => {
            const expanded = dropdownButton.getAttribute('aria-expanded') === 'true' || false;
            dropdownButton.setAttribute('aria-expanded', !expanded);
            dropdownMenu.classList.toggle('hidden');
        });

        window.addEventListener('click', (event) => {
            if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.classList.add('hidden');
                dropdownButton.setAttribute('aria-expanded', 'false');
            }
        });

        // === Theme Switcher Logic ===
        const body = document.body;
        const systemBtn = document.getElementById('system-btn');
        const lightBtn = document.getElementById('light-btn');
        const darkBtn = document.getElementById('dark-btn');
        const buttons = [systemBtn, lightBtn, darkBtn];

        const getActiveTheme = () => {
            if (localStorage.theme === 'dark') return 'dark';
            if (localStorage.theme === 'light') return 'light';
            return 'system';
        };

        const applyTheme = (theme) => {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            } else if (theme === 'light') {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                localStorage.removeItem('theme');
            }
            updateButtonStyles(theme);
        };

        const updateButtonStyles = (activeTheme) => {
            buttons.forEach(btn => {
                btn.classList.remove('btn-active', 'btn-inactive');
                if (btn.id.includes(activeTheme)) {
                    btn.classList.add('btn-active');
                } else {
                    btn.classList.add('btn-inactive');
                }
            });
        };

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (!('theme' in localStorage)) {
                applyTheme('system');
            }
        });

        systemBtn.addEventListener('click', () => applyTheme('system'));
        lightBtn.addEventListener('click', () => applyTheme('light'));
        darkBtn.addEventListener('click', () => applyTheme('dark'));

        // Initialize theme on page load
        const initialTheme = getActiveTheme();
        applyTheme(initialTheme);

        // === Sticky Header Logic ===
        const header = document.getElementById('sticky-header');
        const scrollThreshold = 50;

        window.addEventListener('scroll', () => {
            if (window.scrollY > scrollThreshold) {
                header.classList.add('bg-light-bg', 'dark:bg-dark-bg', 'shadow-lg', 'backdrop-blur-sm', 'bg-opacity-80', 'py-4');
                header.classList.remove('py-6');
            } else {
                header.classList.remove('bg-light-bg', 'dark:bg-dark-bg', 'shadow-lg', 'backdrop-blur-sm', 'bg-opacity-80', 'py-4');
                header.classList.add('py-6');
            }
        });
    </script>
</body>

</html>