<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit;
}

$username = $_SESSION['username'];
include 'config.php';
include 'db-functions.php'; 
requireAdmin($username);

// 1. Get User Data
$user = getUserByUsername($username);
$id_user = $user['id'] ?? 0;
$fullname = $user['fullname'] ?? $username;

// Calculate statistics for today
$today = date('Y-m-d');

$totalCalories = 0;
$foodsCount = 0;
$mealsCount = 0;

if ($id_user > 0) {

    $safe_id_user = escape($id_user);
    $safe_today = escape($today);

    $sql_today_logs = "
        SELECT 
            d.id_meals,
            d.id_foods,
            m.calories AS meal_calories, 
            f.calories_per_unit AS food_calories
        FROM 
            diary d
        LEFT JOIN 
            meals m ON d.id_meals = m.id
        LEFT JOIN 
            foods f ON d.id_foods = f.id
        
    ";
// WHERE 
//             d.id_user = '$safe_id_user' 
//             AND d.date = '$safe_today'
    $result_today_logs = dbQuery($sql_today_logs);
    
    if ($result_today_logs) {
        while ($log = mysqli_fetch_assoc($result_today_logs)) {
            if (!empty($log['id_meals']) && $log['meal_calories'] !== null) {
                $totalCalories += floatval($log['meal_calories']);
                $mealsCount++;
            }

            if (!empty($log['id_foods']) && $log['food_calories'] !== null) {
                $totalCalories += floatval($log['food_calories']); 
                $foodsCount++;
            }
        }
        mysqli_free_result($result_today_logs);
    }
}

$totalItemsLogged = $mealsCount + $foodsCount; 

// 3. Get total foods count (all time)
$totalFoodsCount = 0;
$sql_total_foods = "SELECT COUNT(id) AS total_count FROM foods";
$result_total_foods = dbQuery($sql_total_foods);

if ($result_total_foods && mysqli_num_rows($result_total_foods) > 0) {
    $data = mysqli_fetch_assoc($result_total_foods);
    $totalFoodsCount = intval($data['total_count']);
    mysqli_free_result($result_total_foods);
}

?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriTrack - Dashboard</title>
    <link href="./output.css" rel="stylesheet">

    <!-- === CHART ADDED === -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;300;400;600;700;800&display=swap" rel="stylesheet">
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
                <li><a href="dashboard.php" class="font-semibold text-[#3dccc7]">Dashboard</a></li>
                <li><a href="user.php">User</a></li>
                <!-- <li><a href="season.php">Season</a></li> -->
                <li><a href="meal.php">Meal</a></li>
                <li><a href="food.php">Food</a></li>
                <li><a href="daily.php">Daily</a></li>
                <li><a href="article.php">Article</a></li>
                <li><a href="report.php">Report</a></li>
            </ul>

            <div class="hidden md:flex items-center space-x-3">
                <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="bg-[#3dccc7] hover:bg-[#68d8d6] px-4 py-2 text-white rounded-md">Logout</a>
            </div>

        </nav>
    </div>
</header>

<!-- Main -->
<main>
    <section class="pt-28 pb-12 md:pt-36">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-8">
                <h1 class="text-3xl sm:text-4xl font-bold">Dashboard</h1>
                <p class="mt-2 text-lg">Welcome back, 
                    <span class="font-semibold">
                        <?php echo htmlspecialchars($fullname); ?>
                    </span>.
                </p>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="p-6 rounded-lg shadow-md card">
                    <div class="text-sm opacity-80 mb-1">Calories Today</div>
                    <div class="mt-2 text-3xl font-semibold text-[#3dccc7]">
                        <?php echo number_format($totalCalories, 0); ?>
                    </div>
                    <div class="text-xs opacity-60 mt-1">kcal</div>
                </div>

                <div class="p-6 rounded-lg shadow-md card">
                    <div class="text-sm opacity-80 mb-1">Items Logged</div>
                    <div class="mt-2 text-3xl font-semibold text-[#3dccc7]">
                        <?php echo $totalItemsLogged; ?>
                    </div>
                    <div class="text-xs opacity-60 mt-1">
                        <?php echo $mealsCount; ?> meals, <?php echo $foodsCount; ?> foods
                    </div>
                </div>

                <div class="p-6 rounded-lg shadow-md card">
                    <div class="text-sm opacity-80 mb-1">Total Foods</div>
                    <div class="mt-2 text-3xl font-semibold text-[#3dccc7]">
                        <?php echo $totalFoodsCount; ?>
                    </div>
                    <div class="text-xs opacity-60 mt-1">all time</div>
                </div>

            </div>

            <!-- === CHART ADDED (CARD) === -->
            <div class="mt-10 p-6 rounded-lg shadow-md card fade-in">
                <h2 class="text-xl font-semibold mb-4">Calories Overview (Last 7 Days)</h2>
                <canvas id="caloriesChart" height="120"></canvas>
            </div>

        </div>
    </section>
</main>

<!-- Theme Switcher -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-center space-y-4">
        <div class="p-1 rounded-full card shadow-md transition-all duration-300">
            <a href="setting.php" id="settings-btn"
                class="flex items-center justify-center p-2 rounded-full transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.042c1.523-.878 3.25.848 2.372 2.372a1.724 1.724 0 001.042 2.591c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.042 2.591c.878 1.523-.849 3.25-2.372 2.372a1.724 1.724 0 00-2.591 1.042c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.591-1.042c-1.523.878-3.25-.849-2.372-2.372a1.724 1.724 0 00-1.042-2.591c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.042-2.591c-.878-1.524.849-3.25 2.372-2.372a1.724 1.724 0 002.591-1.042z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </a>
        </div>

        <div id="theme-switcher"
            class="flex flex-col p-1 rounded-full card transition-all duration-300">
            <button id="system-btn"
                class="flex items-center justify-center p-2 rounded-full transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                </svg>
            </button>
            <button id="light-btn"
                class="flex items-center justify-center p-2 rounded-full transition-colors duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
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

<!-- === CHART SCRIPT === -->
<script>
document.addEventListener("DOMContentLoaded", () => {

    // Dummy data (replace later with PHP-powered data)
    const chartLabels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
    const chartCalories = [1200, 1500, 1800, 1600, 1700, 2000, 1900];

    const ctx = document.getElementById("caloriesChart").getContext("2d");

    new Chart(ctx, {
        type: "line",
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: "Calories",
                    data: chartCalories,
                    borderColor: "#3dccc7",
                    backgroundColor: "rgba(61,204,199,0.2)",
                    borderWidth: 3,
                    tension: 0.35,
                    pointBackgroundColor: "#3dccc7",
                    pointBorderColor: "#fff",
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: { color: "#666" },
                    grid: { color: "rgba(200,200,200,0.2)" }
                },
                x: {
                    ticks: { color: "#666" },
                    grid: { color: "rgba(200,200,200,0.1)" }
                }
            }
        }
    });
});
</script>

</body>
</html>
