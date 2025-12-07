<?php
header("Content-Type: application/json");
include '../config.php';

/* ============================================================
	 JSON RESPONSE HELPERS
	 ============================================================ */
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
	 VALIDATE METHOD + ACTION
	 ============================================================ */
$method = $_SERVER["REQUEST_METHOD"];

if ($method !== "GET" || !isset($_GET["action"])) {
	errorRes("Invalid request. Required: GET + action parameter.");
}

$action = $_GET["action"];

/* ============================================================
	 VALIDATE USER INPUT (common)
	 ============================================================ */
if (!isset($_GET["userId"])) {
	errorRes("userId is required");
}

$userId = intval($_GET["userId"]);
$tanggal = $_GET["date"] ?? date("Y-m-d");
$startDate = date("Y-m-d", strtotime($tanggal . " -6 day"));
$endDate = $tanggal;

/* ============================================================
	 ACTION: WEEKLY MACROS
	 ============================================================ */
if ($action === "weekly_macros") {

	$stmt = $conn->prepare("
        SELECT
            SUM(IF(d.id_meals IS NOT NULL, m.protein,  f.protein_per_unit)) AS protein,
            SUM(IF(d.id_meals IS NOT NULL, m.carbs,    f.carbs_per_unit))   AS carbs,
            SUM(IF(d.id_meals IS NOT NULL, m.fat,      f.fat_per_unit))     AS fat
        FROM diary d
        LEFT JOIN meals m ON d.id_meals = m.id
        LEFT JOIN foods f ON d.id_foods = f.id
        WHERE d.id_user = ?
        AND d.date BETWEEN ? AND ?
    ");

	if (!$stmt)
		errorRes("SQL ERROR: " . $conn->error);

	$stmt->bind_param("iss", $userId, $startDate, $endDate);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc();

	success([
		"userId" => $userId,
		"periode" => ["start" => $startDate, "end" => $endDate],
		"total_macros" => [
			"protein" => intval($row["protein"] ?? 0),
			"karbohidrat" => intval($row["carbs"] ?? 0),
			"lemak" => intval($row["fat"] ?? 0)
		],
		"summary" => "Makronutrien minggu ini dihitung dari periode $startDate hingga $endDate."
	]);
}


/* ============================================================
	 ACTION: WEEKLY CALORIES
	 ============================================================ */
if ($action === "weekly_calories") {

	$stmt = $conn->prepare("
    SELECT d.date,
           SUM(
               COALESCE(m.calories, f.calories_per_unit, 0)
           ) AS calories
    FROM diary d
    LEFT JOIN meals m ON d.id_meals = m.id
    LEFT JOIN foods f ON d.id_foods = f.id
    WHERE d.id_user = ?
    AND d.date BETWEEN ? AND ?
    GROUP BY d.date
    ORDER BY d.date ASC
");


	if (!$stmt)
		errorRes("SQL ERROR: " . $conn->error);

	$stmt->bind_param("iss", $userId, $startDate, $endDate);
	$stmt->execute();
	$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

	// Build result for 7 days
	$history = [];
	for ($i = 6; $i >= 0; $i--) {
		$d = date("Y-m-d", strtotime($tanggal . " -$i day"));
		$history[$d] = ["date" => $d, "calories" => 0];
	}

	foreach ($rows as $r) {
		$history[$r["date"]]["calories"] = intval($r["calories"]);
	}

	success([
		"userId" => $userId,
		"periode" => ["start" => $startDate, "end" => $endDate],
		"weekly_calories" => array_values($history)
	]);
}


/* ============================================================
	 IF ACTION NOT FOUND
	 ============================================================ */
errorRes("Unknown action: $action");
