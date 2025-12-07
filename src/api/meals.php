<?php
include '../config.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

/* ============================================================
   GET → Ambil semua data, search, atau by ID
   ============================================================ */
if ($method === 'GET') {

    // ========================
    // 1. SEARCH meals BY NAME
    // ========================
    if (isset($_GET['search'])) {

        $search = "%" . $_GET['search'] . "%";

        $stmt = $conn->prepare("
            SELECT 
                m.id AS meal_id,
                m.meals_name,
                
                mi.amount,
                f.id AS food_id,
                f.foods_name,
                f.calories_per_unit,
                f.protein_per_unit,
                f.carbs_per_unit,
                f.fat_per_unit

            FROM meals m
            LEFT JOIN meal_ingredients mi ON mi.id_meal = m.id
            LEFT JOIN foods f ON f.id = mi.id_food
            WHERE m.meals_name LIKE ?
            ORDER BY m.id DESC
        ");

        $stmt->bind_param("s", $search);
        $stmt->execute();
        $result = $stmt->get_result();

        echo json_encode(buildMealsFromJoin($result));
        exit;
    }



    // ========================
    // 2. GET BY ID
    // ========================
    if (isset($_GET['id'])) {

        $id = $_GET['id'];

        $stmt = $conn->prepare("
            SELECT 
                m.id AS meal_id,
                m.meals_name,

                mi.amount,
                f.id AS food_id,
                f.foods_name,
                f.calories_per_unit,
                f.protein_per_unit,
                f.carbs_per_unit,
                f.fat_per_unit

            FROM meals m
            LEFT JOIN meal_ingredients mi ON mi.id_meal = m.id
            LEFT JOIN foods f ON f.id = mi.id_food
            WHERE m.id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $meals = buildMealsFromJoin($result);
        echo json_encode($meals[0] ?? []);
        exit;
    }



    // ========================
    // 3. GET ALL MEALS
    // ========================
    $sql = "
        SELECT 
            m.id AS meal_id,
            m.meals_name,

            mi.amount,
            f.id AS food_id,
            f.foods_name,
            f.calories_per_unit,
            f.protein_per_unit,
            f.carbs_per_unit,
            f.fat_per_unit

        FROM meals m
        LEFT JOIN meal_ingredients mi ON mi.id_meal = m.id
        LEFT JOIN foods f ON f.id = mi.id_food
        ORDER BY m.id DESC
    ";

    $result = $conn->query($sql);
    echo json_encode(buildMealsFromJoin($result));
    exit;
}

function buildMealsFromJoin($result)
{
    $meals = [];

    while ($row = $result->fetch_assoc()) {

        $mealId = $row["meal_id"];

        if (!isset($meals[$mealId])) {
            $meals[$mealId] = [
                "id" => intval($mealId),
                "mealsName" => $row["meals_name"],

                "calories" => 0,
                "protein" => 0,
                "carbs" => 0,
                "fat" => 0,

                "ingredients" => []
            ];
        }

        // If meal has ingredient
        if ($row["food_id"] !== null) {

            $amount = floatval($row["amount"]);
            $cal = $row["calories_per_unit"] * $amount;
            $pro = $row["protein_per_unit"] * $amount;
            $car = $row["carbs_per_unit"] * $amount;
            $fat = $row["fat_per_unit"] * $amount;

            // Add ingredient info
            $meals[$mealId]["ingredients"][] = [
                "id" => intval($row["food_id"]),
                "foodsName" => $row["foods_name"],
                "amount" => $amount,
                "calories" => $cal,
                "protein" => $pro,
                "carbs" => $car,
                "fat" => $fat
            ];

            // Accumulate totals
            $meals[$mealId]["calories"] += $cal;
            $meals[$mealId]["protein"]  += $pro;
            $meals[$mealId]["carbs"]    += $car;
            $meals[$mealId]["fat"]      += $fat;
        }
    }

    return array_values($meals);
}



/* ============================================================
   POST → Tambah meal baru
   ============================================================ */
if ($method === 'POST') {

    $input = json_decode(file_get_contents("php://input"), true);

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

    $ingredients = $input['ingredients'] ?? [];   // ⭐ NEW (list of FoodModel)

    // ========== INSERT MEAL ==========
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

    // Get inserted meal id
    $mealId = $stmt->insert_id;


    // ========== INSERT INGREDIENTS ==========
    if (!empty($ingredients)) {

        $stmtIng = $conn->prepare("
            INSERT INTO meal_ingredients (id_meal, id_food)
            VALUES (?, ?)
        ");

        foreach ($ingredients as $ing) {

            if (!isset($ing['id'])) continue;

            $idFood = $ing['id'];

            $stmtIng->bind_param("ii", $mealId, $idFood);
            $stmtIng->execute();
        }
    }

    echo json_encode([
        "message" => "Meal added successfully",
        "mealId" => $mealId
    ]);
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
