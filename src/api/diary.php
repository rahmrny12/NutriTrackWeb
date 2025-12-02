<?php
include '../db.php'; // includes camelCase helpers

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

function errorRes($msg)
{
    echo json_encode(["status" => "error", "message" => $msg]);
    exit;
}

function success($data)
{
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

/* ============================================================
   BASE SELECT (used for GET ALL & GET BY ID)
   ============================================================ */

$BASE_SELECT = "
    SELECT 
        d.*,
        IF(d.id_meals IS NOT NULL, m.meals_name, f.foods_name) AS name,
        IF(d.id_meals IS NOT NULL, m.calories, f.calories_per_unit) AS calories,
        IF(d.id_meals IS NOT NULL, m.carbs, f.carbs_per_unit) AS carbs,
        IF(d.id_meals IS NOT NULL, m.protein, f.protein_per_unit) AS protein,
        IF(d.id_meals IS NOT NULL, m.fat, f.fat_per_unit) AS fat,
        IF(d.id_meals IS NOT NULL, 'meal', 'food') AS type
    FROM diary d
    LEFT JOIN meals m ON d.id_meals = m.id
    LEFT JOIN foods f ON d.id_foods = f.id
";

/* ============================================================
   MONTHLY SUMMARY (full month from start to end)
   ============================================================ */
if ($method === 'GET' && isset($_GET['monthlySummary'])) {

    if (!isset($_GET['userId']) || !isset($_GET['month'])) {
        errorRes("Required: userId, month");
    }

    $userId = intval($_GET['userId']);
    $month = intval($_GET['month']);
    $year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

    /* ==== 1. Get user goal + weight ==== */
    $stmtUser = $conn->prepare("
        SELECT daily_calories_target, weight
        FROM users
        WHERE id = ?
    ");
    $stmtUser->bind_param("i", $userId);
    if (!$stmtUser) {
        errorRes("SQL ERROR: " . $conn->error . " | QUERY: " . $query);
    }
    $stmtUser->execute();
    $user = $stmtUser->get_result()->fetch_assoc();

    if (!$user)
        errorRes("User not found");

    $calorieGoal = intval($user["daily_calories_target"]);
    $waterGoal = intval($user["weight"]) * 35; // ml


    /* ==== 2. Get all diary food data for the month ==== */
    $stmtDiary = $conn->prepare("
        SELECT 
            d.date,
            SUM(IF(d.id_meals IS NOT NULL, m.calories, f.calories_per_unit)) AS food_calories
        FROM diary d
        LEFT JOIN meals m ON d.id_meals = m.id
        LEFT JOIN foods f ON d.id_foods = f.id
        WHERE d.id_user = ?
        AND MONTH(d.date) = ?
        AND YEAR(d.date) = ?
        GROUP BY d.date
    ");

    $stmtDiary->bind_param("iii", $userId, $month, $year);
    $stmtDiary->execute();
    $foodResult = $stmtDiary->get_result();

    $foodMap = [];
    while ($row = $foodResult->fetch_assoc()) {
        $foodMap[$row["date"]] = intval($row["food_calories"]);
    }

    /* ==== 3. Get all water logs for the month ==== */
    $stmtWater = $conn->prepare("
        SELECT date, SUM(amount_ml) AS water_ml
        FROM water_log
        WHERE id_user = ?
        AND MONTH(date) = ?
        AND YEAR(date) = ?
        GROUP BY date
    ");
    if (!$stmtWater) {
        errorRes("SQL ERROR: " . $conn->error);
    }
    $stmtWater->bind_param("iii", $userId, $month, $year);
    $stmtWater->execute();
    $waterResult = $stmtWater->get_result();

    $waterMap = [];
    while ($row = $waterResult->fetch_assoc()) {
        $waterMap[$row["date"]] = intval($row["water_ml"]);
    }


    /* ==== 4. Build full month date list ==== */
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $summary = [];

    for ($day = 1; $day <= $daysInMonth; $day++) {

        $date = sprintf("%04d-%02d-%02d", $year, $month, $day);

        $foodCalories = $foodMap[$date] ?? 0;
        $waterIntake = $waterMap[$date] ?? 0;

        /* If no activity on this date → return data:null */
        if ($foodCalories == 0 && $waterIntake == 0) {

            $summary[] = [
                "date" => $date,
                "data" => null
            ];

            continue;
        }

        /* Otherwise return full structured data */
        $remaining = $calorieGoal - $foodCalories;
        if ($remaining < 0)
            $remaining = 0;

        $gauge = $calorieGoal > 0
            ? intval(($foodCalories / $calorieGoal) * 100)
            : 0;
        if ($gauge > 100)
            $gauge = 100;

        $summary[] = [
            "date" => $date,
            "data" => [
                "calorieGoal" => $calorieGoal,
                "foodCalories" => $foodCalories,
                "waterIntake" => $waterIntake,
                "waterGoal" => $waterGoal,
                "remainingCalories" => $remaining,
                "gaugePercent" => $gauge
            ]
        ];
    }

    success(mapArrayToCamelCase($summary));
}

/* ============================================================
   GET → Ambil data diary
   ============================================================ */
if ($method === 'GET') {

    // GET BY ID
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        $stmt = $conn->prepare($BASE_SELECT . " WHERE d.id = ?");
        $stmt->bind_param("i", $id);

        if (!$stmt->execute())
            errorRes($stmt->error);

        $row = $stmt->get_result()->fetch_assoc();
        success(mapToCamelCase($row));
    }

    // FILTER OR GET ALL
    $query = $BASE_SELECT . " WHERE 1=1 ";
    $params = [];
    $types = "";

    if (isset($_GET['id_user'])) {
        $query .= " AND d.id_user = ?";
        $params[] = $_GET['id_user'];
        $types .= "i";
    }

    if (isset($_GET['date'])) {
        $query .= " AND d.date = ?";
        $params[] = $_GET['date'];
        $types .= "s";
    }

    if (isset($_GET['category'])) {
        $query .= " AND d.category = ?";
        $params[] = $_GET['category'];
        $types .= "s";
    }

    $query .= " ORDER BY d.date DESC, d.id DESC";

    $stmt = $conn->prepare($query);

    if (!empty($params))
        $stmt->bind_param($types, ...$params);

    if (!$stmt) {
        errorRes("SQL ERROR: " . $conn->error . " | QUERY: " . $query);
    }
    if (!$stmt->execute())
        errorRes($stmt->error);

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    success(mapArrayToCamelCase($rows));
}

/* ============================================================
   POST → Insert diary
   ============================================================ */
if ($method === 'POST') {

    if (!isset($input['idUser']) || !isset($input['date']) || !isset($input['category'])) {
        errorRes("Required: idUser, date, category");
    }

    $stmt = $conn->prepare("
        INSERT INTO diary (id_custom_meals, id_user, id_meals, id_foods, date, category)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiiiss",
        $input['idCustomMeals'],
        $input['idUser'],
        $input['idMeals'],
        $input['idFoods'],
        $input['date'],
        $input['category']
    );

    if (!$stmt->execute())
        errorRes($stmt->error);

    success("Diary added successfully");
}

/* ============================================================
   PUT → Update diary
   ============================================================ */
if ($method === 'PUT') {

    if (!isset($_GET['id']))
        errorRes("ID is required");
    $id = $_GET['id'];

    $stmt = $conn->prepare("
        UPDATE diary
        SET id_custom_meals = ?, id_user = ?, id_meals = ?, id_foods = ?, date = ?, category = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "iiiissi",
        $input['idCustomMeals'],
        $input['idUser'],
        $input['idMeals'],
        $input['idFoods'],
        $input['date'],
        $input['category'],
        $id
    );

    if (!$stmt->execute())
        errorRes($stmt->error);

    success("Diary updated successfully");
}

/* ============================================================
   DELETE
   ============================================================ */
if ($method === 'DELETE') {

    if (!isset($_GET['id']))
        errorRes("ID is required");

    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM diary WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute())
        errorRes($stmt->error);

    success("Diary deleted successfully");
}

/* ============================================================
   INVALID
   ============================================================ */
errorRes("Invalid request method");
?>