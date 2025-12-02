<?php
include '../db.php'; // already includes mapToCamelCase + mapArrayToCamelCase

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);


/* ============================================================
   GET
   ============================================================ */
if ($method == 'GET') {

    // SEARCH food
    if (isset($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";

        $stmt = $conn->prepare("
            SELECT *
            FROM foods
            WHERE foods_name LIKE ?
            ORDER BY foods_name ASC
        ");
        $stmt->bind_param("s", $search);
        $stmt->execute();

        $result = $stmt->get_result();

        $foods = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(mapArrayToCamelCase($foods));
        exit;
    }


    // GET by ID
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        $stmt = $conn->prepare("SELECT * FROM foods WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        echo json_encode(mapToCamelCase($row));
        exit;
    }


    // GET ALL
    $result = $conn->query("SELECT * FROM foods ORDER BY created_at DESC");

    $foods = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(mapArrayToCamelCase($foods));
    exit;
}



/* ============================================================
   POST
   ============================================================ */
if ($method == 'POST') {

    if (!isset($input['foodsName']) || !isset($input['caloriesPerUnit'])) {
        echo json_encode(["error" => "foodsName and caloriesPerUnit required"]);
        exit;
    }

    $name     = $input['foodsName'];
    $calories = $input['caloriesPerUnit'];
    $protein  = $input['proteinPerUnit'] ?? 0;
    $carbs    = $input['carbsPerUnit'] ?? 0;
    $fat      = $input['fatPerUnit'] ?? 0;

    $stmt = $conn->prepare("
        INSERT INTO foods (foods_name, calories_per_unit, protein_per_unit, carbs_per_unit, fat_per_unit)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sdddd", $name, $calories, $protein, $carbs, $fat);
    $stmt->execute();

    echo json_encode(["message" => "Food added successfully"]);
    exit;
}



/* ============================================================
   PUT
   ============================================================ */
if ($method == 'PUT') {

    if (!isset($_GET['id'])) {
        echo json_encode(["error" => "ID is required"]);
        exit;
    }

    $id       = $_GET['id'];
    $name     = $input['foodsName'];
    $calories = $input['caloriesPerUnit'];
    $protein  = $input['proteinPerUnit'];
    $carbs    = $input['carbsPerUnit'];
    $fat      = $input['fatPerUnit'];

    $stmt = $conn->prepare("
        UPDATE foods
        SET foods_name = ?, calories_per_unit = ?, protein_per_unit = ?, carbs_per_unit = ?, fat_per_unit = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sddddi", $name, $calories, $protein, $carbs, $fat, $id);
    $stmt->execute();

    echo json_encode(["message" => "Food updated successfully"]);
    exit;
}



/* ============================================================
   DELETE
   ============================================================ */
if ($method == 'DELETE') {

    if (!isset($_GET['id'])) {
        echo json_encode(["error" => "ID is required"]);
        exit;
    }

    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM foods WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(["message" => "Food deleted successfully"]);
    exit;
}


/* ============================================================
   INVALID METHOD
   ============================================================ */
echo json_encode(["error" => "Invalid request method"]);
exit;
?>
