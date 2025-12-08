<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit;
}

$username = $_SESSION['username'];

include 'config.php';
include 'db-functions.php';

// Pastikan requireAdmin SUDAH tidak pakai die();
requireAdmin($username);

$message = '';
$error = '';

// Ambil data user & id user
$user = getUserByUsername($username);
if (!$user) {
    $error = 'User not found';
    $userId = 0;
} else {
    $userId = (int) $user['id'];
}
$fullname = $user['fullname'] ?? $username;

// ============================
// DELETE ARTICLE
// ============================
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    if ($deleteId > 0) {
        $result = deleteArticle($deleteId, $userId);

        if ($result['status'] == 200) {
            $message = "Article deleted successfully!";
        } else {
            $error = "Failed to delete article.";
        }
    }
}

// ============================
// ADD ARTICLE
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_article'])) {
    $title   = trim($_POST['title'] ?? '');
    $slug    = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status  = trim($_POST['status'] ?? 'draft');

    if ($title === '' || $content === '') {
        $error = "Title and content are required.";
    } else {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $status,
        ];

        $result = createArticle($data, $userId);

        if ($result['status'] == 201) {
            $message = "Article created successfully!";
        } else {
            $error = "Failed to create article.";
        }
    }
}

// ============================
// EDIT ARTICLE
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_article'])) {
    $articleId = intval($_POST['article_id'] ?? 0);
    $title     = trim($_POST['edit_title'] ?? '');
    $slug      = trim($_POST['edit_slug'] ?? '');
    $content   = trim($_POST['edit_content'] ?? '');
    $status    = trim($_POST['edit_status'] ?? 'draft');

    if ($articleId <= 0) {
        $error = "Invalid article.";
    } elseif ($title === '' || $content === '') {
        $error = "Title and content are required.";
    } else {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $status,
        ];

        $result = updateArticle($articleId, $userId, $data);

        if ($result['status'] == 200) {
            $message = "Article updated successfully!";
        } else {
            $error = "Failed to update article.";
        }
    }
}

// ============================
// FETCH ALL ARTICLES FOR THIS USER
// ============================
$articles = getArticlesByUser($userId);

?>

<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriTrack - Article Management</title>
    <link href="./output.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
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
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes mobileMenuOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(-8px);
            }
        }

        .modal-panel {
            transform: translateY(12px);
            opacity: 0;
        }

        .modal-panel.show {
            transform: translateY(0);
            opacity: 1;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
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
                    <li><a href="dashboard.php" class="transition duration-200 text-hover-light">Dashboard</a></li>
                    <li><a href="user.php" class="transition duration-200 hover:scale-105">User</a></li>
                    <!-- <li><a href="season.php" class="transition duration-200 hover:scale-105">Season</a></li> -->
                    <li><a href="meal.php" class="transition duration-200 hover:scale-105">Meal</a></li>
                    <li><a href="food.php" class="transition duration-200 hover:scale-105">Food</a></li>
                    <li><a href="daily.php" class="transition duration-200 hover:scale-105">Daily</a></li>
                    <li><a href="article.php" class="font-semibold text-[#3dccc7]">Article</a></li>
                    <li><a href="report.php">Report</a></li>
                </ul>
                <div class="hidden md:flex items-center space-x-3">
                    <span class="dark:text-dark-text whitespace-nowrap">Hello,
                        <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php"
                        class="inline-flex justify-center gap-2 text-white bg-[#3dccc7] hover:bg-[#68d8d6] px-4 py-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 w-full">Logout</a>
                </div>
                <div class="md:hidden">
                    <button id="menu-toggle-btn" type="button" aria-expanded="false" aria-controls="mobile-menu"
                        aria-label="Toggle navigation"
                        class="p-2 rounded-lg transition text-gray-800 dark:text-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3dccc7]">
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
                        <a href="dashboard.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Dashboard</a>
                        <a href="user.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">User</a>
                        <a href="food.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Food</a>
                        <a href="article.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Article</a>
                        <a href="meal.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Meal</a>
                        <a href="daily.php"
                            class="block text-base font-medium transition-colors duration-200 hover:text-[#3dccc7]">Daily</a>
                    </div>
                    <div class="flex flex-col gap-3 py-3 border-t border-neutral-200 dark:border-neutral-700">
                        <span class="text-sm opacity-70">Hello,
                            <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="logout.php"
                            class="inline-flex justify-center items-center gap-2 text-sm font-medium rounded-md py-2 px-4 text-white bg-[#3dccc7] hover:bg-[#68d8d6] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#3dccc7]">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="pt-28 md:pt-36 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-widest opacity-60">Article</p>
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Article Management</h1>
                    <p class="mt-2 text-base opacity-80">Manage your articles and publish content for your users.</p>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)) { ?>
                <div
                    class="text-sm text-green-700 bg-green-100 dark:bg-green-900/20 dark:text-green-400 rounded-md px-4 py-3">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>
            <?php if (!empty($error)) { ?>
                <div
                    class="text-sm text-red-700 bg-red-100 dark:bg-red-900/20 dark:text-red-400 rounded-md px-4 py-3">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Add Article -->
                <div class="p-6 rounded-lg shadow-md card">
                    <h2 class="text-xl font-semibold">Add Article</h2>
                    <form class="mt-4 space-y-4" action="article.php" method="POST">
                        <input type="hidden" name="add_article" value="1">

                        <div>
                            <label for="title" class="block text-sm font-medium mb-2">Title</label>
                            <input id="title" name="title" type="text" required
                                class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                placeholder="e.g., How to Eat Healthy">
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-medium mb-2">Slug (optional)</label>
                            <input id="slug" name="slug" type="text"
                                class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                placeholder="auto-generated if empty">
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium mb-2">Content</label>
                            <textarea id="content" name="content" rows="6"
                                class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500"
                                placeholder="Write your article content here..."></textarea>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium mb-2">Status</label>
                            <select id="status" name="status"
                                class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        <div>
                            <button type="submit"
                                class="inline-flex justify-center gap-2 text-white bg-[#3dccc7] hover:bg-[#68d8d6] px-4 py-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                                Add Article
                            </button>
                        </div>
                    </form>
                </div>

                <!-- List Articles -->
                <div class="p-6 rounded-lg shadow-md card">
                    <h2 class="text-xl font-semibold">Your Articles</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left opacity-70">
                                    <th class="py-2 pr-4">Title</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Created At</th>
                                    <th class="py-2 pr-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($articles)) { ?>
                                    <tr>
                                        <td colspan="4" class="py-4 opacity-70">No articles yet.</td>
                                    </tr>
                                <?php } else { ?>
                                    <?php foreach ($articles as $a) { ?>
                                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                            <td class="py-2 pr-4">
                                                <?php echo htmlspecialchars($a['title']); ?>
                                            </td>
                                            <td class="py-2 pr-4">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo $a['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo htmlspecialchars($a['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-2 pr-4 text-xs">
                                                <?php echo htmlspecialchars($a['created_at']); ?>
                                            </td>
                                            <td class="py-2 pr-4 space-x-3">
                                                <button type="button"
                                                    class="text-cyan-600 hover:underline dark:text-cyan-300 edit-article-btn"
                                                    data-id="<?php echo (int) $a['id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($a['title']); ?>"
                                                    data-slug="<?php echo htmlspecialchars($a['slug']); ?>"
                                                    data-content="<?php echo htmlspecialchars($a['content']); ?>"
                                                    data-status="<?php echo htmlspecialchars($a['status']); ?>">
                                                    Edit
                                                </button>
                                                <a href="article.php?delete=<?php echo (int) $a['id']; ?>"
                                                    class="text-red-600 hover:underline dark:text-red-400"
                                                    onclick="return confirm('Delete this article?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Article Modal -->
    <div id="edit-article-modal" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl mx-auto card rounded-xl shadow-2xl p-6 fade-in">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold">Edit Article</h3>
                <button type="button" id="close-edit-modal" class="opacity-80">✕</button>
            </div>
            <form id="edit-article-form" class="space-y-4" method="POST" action="article.php">
                <input type="hidden" name="edit_article" value="1">
                <input type="hidden" name="article_id" id="edit-article-id">

                <div>
                    <label for="edit-title" class="block text-sm font-medium mb-2">Title</label>
                    <input id="edit-title" name="edit_title" type="text" required
                        class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </div>

                <div>
                    <label for="edit-slug" class="block text-sm font-medium mb-2">Slug</label>
                    <input id="edit-slug" name="edit_slug" type="text"
                        class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </div>

                <div>
                    <label for="edit-content" class="block text-sm font-medium mb-2">Content</label>
                    <textarea id="edit-content" name="edit_content" rows="6"
                        class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500"></textarea>
                </div>

                <div>
                    <label for="edit-status" class="block text-sm font-medium mb-2">Status</label>
                    <select id="edit-status" name="edit_status"
                        class="block w-full px-3 py-2 card rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" id="cancel-edit-modal"
                        class="px-4 py-2 rounded-md border border-gray-300 hover:bg-white/10">Cancel</button>
                    <button type="submit"
                        class="inline-flex justify-center gap-2 text-white bg-[#3dccc7] hover:bg-[#68d8d6] px-4 py-3 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS (menu, modal, dll) -->
    <script>
        // === Mobile Menu Logic (sama seperti di food.php) ===
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

            const closeMobileMenu = () => {
                mobileMenuPanel.classList.remove('animate-open');
                mobileMenuPanel.classList.add('animate-close');
                menuToggleBtn.setAttribute('aria-expanded', 'false');
                setMenuIcon('close');
                document.body.style.overflow = '';
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
                    closeMobileMenu();
                }
            });
        }

        // === Sticky Header Logic sederhana ===
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

        // === Edit Article Modal Logic ===
        const editModal = document.getElementById('edit-article-modal');
        const editButtons = document.querySelectorAll('.edit-article-btn');
        const closeEditModalBtn = document.getElementById('close-edit-modal');
        const cancelEditModalBtn = document.getElementById('cancel-edit-modal');

        const editArticleIdInput = document.getElementById('edit-article-id');
        const editTitleInput = document.getElementById('edit-title');
        const editSlugInput = document.getElementById('edit-slug');
        const editContentInput = document.getElementById('edit-content');
        const editStatusSelect = document.getElementById('edit-status');

        const setBodyScroll = (locked) => {
            document.body.style.overflow = locked ? 'hidden' : '';
        };

        const openEditModal = () => {
            if (!editModal) return;
            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
            setBodyScroll(true);
        };

        const closeEditModal = () => {
            if (!editModal) return;
            editModal.classList.add('hidden');
            editModal.classList.remove('flex');
            setBodyScroll(false);
        };

        editButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const { id, title, slug, content, status } = btn.dataset;

                editArticleIdInput.value = id || '';
                editTitleInput.value = title || '';
                editSlugInput.value = slug || '';
                editContentInput.value = content || '';
                editStatusSelect.value = status || 'draft';

                openEditModal();
            });
        });

        closeEditModalBtn && closeEditModalBtn.addEventListener('click', closeEditModal);
        cancelEditModalBtn && cancelEditModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeEditModal();
        });

        editModal && editModal.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
    </script>
</body>

</html>
