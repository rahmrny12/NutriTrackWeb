<?php
include '../db.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

/* ============================================================
   GET → Ambil semua data, search, atau by ID
   ============================================================ */
if ($method === 'GET') {

    // SEARCH meals BY NAME
    if (isset($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";

        $stmt = $conn->prepare("
            SELECT * FROM meals 
            WHERE meals_name LIKE ?
            ORDER BY id DESC
        ");
        $stmt->bind_param("s", $search);

        if (!$stmt->execute()) {
            echo json_encode(["error" => $stmt->error]);
            exit;
        }

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(mapArrayToCamelCase($rows));
        exit;
    }


    // GET BY ID
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        $stmt = $conn->prepare("SELECT * FROM meals WHERE id = ?");
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            echo json_encode(["error" => $stmt->error]);
            exit;
        }

        $row = $stmt->get_result()->fetch_assoc();
        echo json_encode(mapToCamelCase($row));
        exit;
    }


    // GET ALL MEALS
    $result = $conn->query("SELECT * FROM meals ORDER BY id DESC");

    if (!$result) {
        echo json_encode(["error" => $conn->error]);
        exit;
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(mapArrayToCamelCase($rows));
    exit;
}



/* ============================================================
   POST → Tambah meal baru
   ============================================================ */
if ($method === 'POST') {

    if (
        !isset($input['idUser']) ||
        !isset($input['mealsName']) ||
        !isset($input['calories'])
    ) {
        echo json_encode(["error" => "Required: idUser, mealsName, calories"]);
        exit;
    }

    $idUser      = $input['idUser'];
    $mealsName   = $input['mealsName'];
    $description = $input['description'] ?? "";
    $calories    = $input['calories'];
    $protein     = $input['protein'] ?? 0;
    $carbs       = $input['carbs'] ?? 0;
    $fat         = $input['fat'] ?? 0;
    echo json_encode($mealsName);

    $stmt = $conn->prepare("
        INSERT INTO meals (id_user, meals_name, description, calories, protein, carbs, fat)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "issdddd",
        $idUser,
        $mealsName,
        $description,
        $calories,
        $protein,
        $carbs,
        $fat
    );

    if (!$stmt->execute()) {
        echo json_encode(["error" => $stmt->error]);
        exit;
    }

    echo json_encode(["message" => "Meal added successfully"]);
    exit;
}



/* ============================================================
   PUT → Update meal
   ============================================================ */
if ($method === 'PUT') {

    if (!isset($_GET['id'])) {
        echo json_encode(["error" => "ID is required"]);
        exit;
    }

    $id          = $_GET['id'];
    $mealsName   = $input['mealsName'] ?? "";
    $description = $input['description'] ?? "";
    $calories    = $input['calories'] ?? 0;
    $protein     = $input['protein'] ?? 0;
    $carbs       = $input['carbs'] ?? 0;
    $fat         = $input['fat'] ?? 0;

    $stmt = $conn->prepare("
        UPDATE meals
        SET meals_name = ?, description = ?, calories = ?, protein = ?, carbs = ?, fat = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssddddi",
        $mealsName,
        $description,
        $calories,
        $protein,
        $carbs,
        $fat,
        $id
    );

    if (!$stmt->execute()) {
        echo json_encode(["error" => $stmt->error]);
        exit;
    }

    echo json_encode(["message" => "Meal updated successfully"]);
    exit;
}



/* ============================================================
   DELETE → Hapus meal
   ============================================================ */
if ($method === 'DELETE') {

    if (!isset($_GET['id'])) {
        echo json_encode(["error" => "ID is required"]);
        exit;
    }

    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM meals WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        echo json_encode(["error" => $stmt->error]);
        exit;
    }

    echo json_encode(["message" => "Meal deleted successfully"]);
    exit;
}



/* ============================================================
   INVALID METHOD
   ============================================================ */
echo json_encode(["error" => "Invalid request method"]);
exit;

?>
