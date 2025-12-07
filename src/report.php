<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit;
}

$username = $_SESSION['username'];

include 'config.php';
include 'db-functions.php';

// Hanya admin bisa akses
requireAdmin($username);

// Ambil admin data
$adminUser = getUserByUsername($username);
$adminId = $adminUser ? (int)$adminUser['id'] : 0;

// Ambil semua user non-admin
$usersList = getAllNonAdminUsers();

// User dipilih dari dropdown
$selectedUserId = isset($_GET['uid']) ? (int)$_GET['uid'] : $adminId;

// Ambil report data berdasarkan user yg dipilih
$today = date('Y-m-d');
$dailyReport = getDailyReport($selectedUserId, $today);
$weekly = getWeeklyCalories($selectedUserId);
$monthly = getMonthlyReport($selectedUserId);

// Ambil user fullname untuk display
$selectedUserData = null;

if ($selectedUserId == $adminId) {
    $selectedUserData = $adminUser;
} else {
    foreach ($usersList as $u) {
        if ($u['id'] == $selectedUserId) {
            $selectedUserData = $u;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriTrack - Report</title>

    <link href="./output.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>

<body>
<!-- HEADER -->
<header class="fixed z-50 w-full transition-all duration-300 ease-in-out py-6 bg-dark-bg">
    <div class="max-w-7xl mx-auto px-4">
        <nav class="relative flex justify-between items-center">
            <div><h1 class="text-2xl font-bold">NutriTrack+</h1></div>

            <ul class="hidden md:flex items-center space-x-8">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="user.php">User</a></li>
                <li><a href="meal.php">Meal</a></li>
                <li><a href="food.php">Food</a></li>
                <li><a href="daily.php">Daily</a></li>
                <li><a href="article.php">Article</a></li>
                <li><a href="report.php" class="font-semibold text-[#3dccc7]">Report</a></li>
            </ul>

            <div class="hidden md:flex items-center space-x-3">
                <span>Hello, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="px-4 py-2 bg-[#3dccc7] rounded-md text-white">Logout</a>
            </div>
        </nav>
    </div>
</header>

<main class="pt-28 pb-12 md:pt-36">
    <div class="max-w-7xl mx-auto px-4 space-y-10">

        <!-- TITLE -->
        <div>
            <h1 class="text-4xl font-bold">Report Summary</h1>
            <p class="opacity-75 mt-2">Analytics for user nutrition activity.</p>
        </div>

        <!-- FILTER USER DROPDOWN -->
        <form method="GET" class="mt-4">
            <label class="block text-sm font-semibold mb-1">Select User</label>

            <select name="uid" onchange="this.form.submit()" 
                    class="px-3 py-2 rounded-md card shadow border w-full md:w-64">

                <!-- Admin -->
                <option value="<?= $adminId ?>" <?= ($selectedUserId == $adminId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($adminUser['fullname']) ?> (Admin)
                </option>

                <?php foreach ($usersList as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($selectedUserId == $u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['fullname'] ?: $u['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- SELECTED USER TITLE -->
        <div class="mt-2">
            <p class="opacity-80 text-sm">
                Showing report for: 
                <span class="font-semibold text-[#3dccc7]">
                    <?= htmlspecialchars($selectedUserData['fullname'] ?: $selectedUserData['username']) ?>
                </span>
            </p>
        </div>

        <!-- EXPORT PDF BUTTON -->
        <!--<div class="mt-4">-->
        <!--    <a href="export-report.php?uid=<?= $selectedUserId ?>" -->
        <!--       class="inline-block px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-md shadow">-->
        <!--        Export PDF-->
        <!--    </a>-->
        <!--</div>-->

        <!-- DAILY SUMMARY TABLE -->
        <div class="p-6 card shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Daily Summary</h2>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="opacity-70 border-b">
                        <th class="py-2 text-left">Date</th>
                        <th class="py-2 text-left">Total Calories</th>
                        <th class="py-2 text-left">Items Logged</th>
                        <th class="py-2 text-left">Meals</th>
                        <th class="py-2 text-left">Foods</th>
                    </tr>
                </thead>

                <tbody>
                    <tr class="border-b">
                        <td class="py-2"><?= $today ?></td>
                        <td class="py-2"><?= $dailyReport['total_calories'] ?></td>
                        <td class="py-2"><?= $dailyReport['total_items'] ?></td>
                        <td class="py-2"><?= $dailyReport['meals_logged'] ?></td>
                        <td class="py-2"><?= $dailyReport['foods_logged'] ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- WEEKLY TABLE -->
        <div class="p-6 card shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Weekly Calories Overview</h2>

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="opacity-70 border-b">
                        <th class="py-2 text-left">Date</th>
                        <th class="py-2 text-left">Calories</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($weekly['labels'])): ?>
                        <tr><td colspan="2" class="py-4 opacity-70">No weekly data.</td></tr>
                    <?php else: ?>
                        <?php foreach ($weekly['labels'] as $i => $date): ?>
                            <tr class="border-b">
                                <td class="py-2"><?= $date ?></td>
                                <td class="py-2"><?= $weekly['values'][$i] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MONTHLY SUMMARY TABLE -->
        <div class="p-6 card shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Monthly Summary (Last 30 Days)</h2>

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="opacity-70 border-b">
                        <th class="py-2 text-left">Metric</th>
                        <th class="py-2 text-left">Value</th>
                    </tr>
                </thead>

                <tbody>
                    <tr class="border-b">
                        <td class="py-2">Total Calories</td>
                        <td class="py-2"><?= $monthly['total_calories'] ?></td>
                    </tr>

                    <tr class="border-b">
                        <td class="py-2">Average Calories / Day</td>
                        <td class="py-2"><?= $monthly['average_per_day'] ?></td>
                    </tr>

                    <tr class="border-b">
                        <td class="py-2">Highest Day</td>
                        <td class="py-2">
                            <?= $monthly['highest_day'] ?> (<?= $monthly['highest_value'] ?> kcal)
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TODAY DETAIL TABLE -->
        <div class="p-6 card shadow rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Today’s Detailed Logs</h2>

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="opacity-70 border-b">
                        <th class="py-2 text-left">Type</th>
                        <th class="py-2 text-left">Name</th>
                        <th class="py-2 text-left">Calories</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($dailyReport['details'])): ?>
                        <tr><td colspan="3" class="py-4 opacity-70">No logs today.</td></tr>
                    <?php else: ?>
                        <?php foreach ($dailyReport['details'] as $item): ?>
                            <tr class="border-b">
                                <td class="py-2"><?= !empty($item['meal_name']) ? "Meal" : "Food" ?></td>
                                <td class="py-2"><?= htmlspecialchars($item['meal_name'] ?: $item['food_name']) ?></td>
                                <td class="py-2"><?= $item['meal_calories'] ?: $item['food_calories'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</main>

</body>
</html>
