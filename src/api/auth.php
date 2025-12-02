<?php
include '../db.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$input = json_decode(file_get_contents("php://input"), true);

function success($data)
{
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

function successCamelCaseResponse($message, $data = null)
{
    if ($data !== null) {
        $data = mapRecursiveCamelCase($data);
    }

    echo json_encode([
        "status" => "success",
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

function error($msg)
{
    echo json_encode(["status" => "error", "message" => $msg]);
    exit;
}

if (!$action)
    error("Action is required: register, login, logout");


// =================================================================
// 1. REGISTER USER
// =================================================================
if ($action === "register") {

    if ($method !== 'POST')
        error("Invalid method");

    if (
        !isset($input['name']) ||
        !isset($input['email']) ||
        !isset($input['password']) ||
        !isset($input['gender'])
    ) {
        error("Fields name, email, password, and gender are required");
    }

    $name = $input['name'];
    $email = $input['email'];
    $gender = $input['gender'];
    $password = password_hash($input['password'], PASSWORD_DEFAULT);

    // optional fields
    $height = $input['height'] ?? 0;
    $weight = $input['weight'] ?? 0;
    $age = $input['age'] ?? 0;
    $dailyCal = $input['daily_calories_target'] ?? 0;

    // check duplicate email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$check)
        error("SQL ERROR PREPARE: " . $conn->error);

    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        error("Email already registered");
    }

    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, gender, height, weight, age, daily_calories_target)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt)
        error("SQL ERROR PREPARE: " . $conn->error);

    $stmt->bind_param(
        "ssssddii",
        $name,
        $email,
        $password,
        $gender,
        $height,
        $weight,
        $age,
        $dailyCal
    );

    if (!$stmt->execute())
        error($stmt->error);

    success("User registered successfully");
}



// =================================================================
// 2. LOGIN USER
// =================================================================
if ($action === "login") {

    if ($method !== 'POST')
        error("Invalid method");

    if (!isset($input['email']) || !isset($input['password'])) {
        error("Email and password are required");
    }

    $email = $input['email'];
    $password = $input['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        error("SQL PREPARE FAILED: " . $conn->error);
    }
    if (!$stmt)
        error("SQL ERROR PREPARE: " . $conn->error);

    $stmt->bind_param("s", $email);

    if (!$stmt->execute())
        error($stmt->error);

    $user = $stmt->get_result()->fetch_assoc();

    if (!$user)
        error("User not found");

    if ($password != $user['password']) {
        error("Invalid password");
    }

    // SUCCESS LOGIN
    success([
        "message" => "Login successful",
        "id" => $user['id'],
        "name" => $user['name'],
        "gender" => $user['gender'],
        "email" => $user['email']
    ]);
}

// ===================================================================
// 4. GET USER PROFILE
// ===================================================================
if ($action === "profile") {

    if ($method !== 'GET')
        error("Invalid method");
    if (!isset($_GET['id']))
        error("User ID is required");

    $id = $_GET['id'];

    $stmt = $conn->prepare("
        SELECT id, name, email, gender, height, weight, age, daily_calories_target, created_at
        FROM users WHERE id = ?
    ");
    if (!$stmt)
        error("SQL ERROR PREPARE: " . $conn->error);

    $stmt->bind_param("i", $id);
    if (!$stmt->execute())
        error($stmt->error);

    $user = $stmt->get_result()->fetch_assoc();
    if (!$user)
        error("User not found");

    success($user);
}



// ===================================================================
// 5. UPDATE USER PROFILE (required: name, email, password, gender)
// ===================================================================
if ($action === "update") {

    if ($method !== 'PUT')
        error("Invalid method");
    if (!isset($_GET['id']))
        error("User ID is required");

    $id = $_GET['id'];

    if (
        !isset($input['name']) ||
        !isset($input['email']) ||
        !isset($input['password']) ||
        !isset($input['gender'])
    ) {
        error("Fields name, email, password, and gender are required");
    }

    $name = $input['name'];
    $email = $input['email'];
    $gender = $input['gender'];
    $password = password_hash($input['password'], PASSWORD_DEFAULT);

    $height = $input['height'] ?? null;
    $weight = $input['weight'] ?? null;
    $age = $input['age'] ?? null;
    $dailyCal = $input['daily_calories_target'] ?? null;

    // Get existing data
    $check = $conn->prepare("SELECT * FROM users WHERE id=?");
    if (!$check)
        error("SQL ERROR PREPARE: " . $conn->error);

    $check->bind_param("i", $id);
    $check->execute();
    $old = $check->get_result()->fetch_assoc();
    if (!$old)
        error("User not found");

    // fallback old data
    $height = $height ?? $old['height'];
    $weight = $weight ?? $old['weight'];
    $age = $age ?? $old['age'];
    $dailyCal = $dailyCal ?? $old['daily_calories_target'];

    // Update
    $stmt = $conn->prepare("
        UPDATE users SET
            name=?, email=?, height=?, weight=?, age=?, gender=?, daily_calories_target=?, password=?
        WHERE id=?
    ");
    if (!$stmt)
        error("SQL ERROR PREPARE: " . $conn->error);

    $stmt->bind_param(
        "ssddisisi",
        $name,
        $email,
        $height,
        $weight,
        $age,
        $gender,
        $dailyCal,
        $password,
        $id
    );

    if (!$stmt->execute())
        error($stmt->error);

    success("User updated successfully");
}

// =================================================================
// 3. LOGOUT USER
// =================================================================
if ($action === "logout") {

    if ($method !== 'POST')
        error("Invalid method");

    success("Logout successful");
}

/* ============================================================
   1. GET HEALTH DATA (cek data user)
   ============================================================ */
if ($method === 'GET' && $action === "get_health") {

    if (!isset($_GET['user_id'])) {
        error("user_id is required");
    }

    $user_id = $_GET['user_id'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt)
        error("SQL ERROR: " . $conn->error);

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    if (!$row)
        error("No user found");

    // List of health fields to check
    $healthFields = [
        "height",
        "weight",
        "age",
        "gender",
        "waist_size",
        "bmi"
    ];

    $missing = [];
    $filled = [];

    foreach ($healthFields as $field) {
        if (!isset($row[$field]) || $row[$field] === null || $row[$field] === "") {
            $missing[] = $field;
        } else {
            $filled[$field] = $row[$field];
        }
    }

    success([
        "user" => $row,
        "missing_fields" => $missing,
        "filled_fields" => $filled,
        "is_complete" => count($missing) === 0
    ]);
}




/* ============================================================
   2. INSERT OR UPDATE HEALTH DATA
   ============================================================ */
if ($method === 'POST' && $action === "update_health") {

    if (!isset($input['id']))
        error("id is required");

    $id = $input['id'];
    $height = $input['height'] ?? null;
    $weight = $input['weight'] ?? null;
    $age = $input['age'] ?? null;
    $gender = $input['gender'] ?? null;
    $waist = $input['waist_size'] ?? null;
    $bmi = calculateBMI($weight, $height);
    $dailyCal = calculateDailyCalories($weight, $height, $age, $gender);
    $bmiCategory = getBMICategory($bmi);

    // CEK APAKAH SUDAH ADA
    $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    if (!$check)
        error("SQL ERROR PREPARE: " . $conn->error);

    $check->bind_param("i", $id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;

    // INSERT
    if (!$exists) {

        $stmt = $conn->prepare("
            INSERT INTO users 
            (id, height, weight, age, gender, waist_size, bmi, daily_calories_target, bmi_category)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt)
            error("SQL ERROR: " . $conn->error);

        $stmt->bind_param(
            "iddiisdii",
            $id,
            $height,
            $weight,
            $age,
            $gender,
            $waist,
            $bmi,
            $dailyCal,
            $bmiCategory
        );

        if (!$stmt->execute())
            error($stmt->error);

        success("Health data inserted successfully");
    }

    // UPDATE
    else {

        $stmt = $conn->prepare("
            UPDATE users
            SET height=?, weight=?, age=?, gender=?, waist_size=?, bmi=?, daily_calories_target=?, bmi_category=?
            WHERE id=?
        ");

        if (!$stmt)
            error("SQL ERROR: " . $conn->error);

        $stmt->bind_param(
            "ddisddisi",
            $height,
            $weight,
            $age,
            $gender,
            $waist,
            $bmi,
            $dailyCal,
            $bmiCategory,
            $id
        );

        if (!$stmt->execute())
            error($stmt->error);

        success("Health data updated successfully");
    }
}

// =================================================================
// INVALID ACTION
// =================================================================
error("Invalid action. Use: register, login, logout");

/* ============================================================
   HELPER FUNCTIONS
   ============================================================ */

function calculateBMI($weight, $height)
{
    if (!$weight || !$height)
        return null;
    // Height cm → m
    $h = $height / 100;
    return round($weight / ($h * $h), 2);
}

function calculateDailyCalories($weight, $height, $age, $gender)
{
    if (!$weight || !$height || !$age || !$gender)
        return null;

    // Mifflin-St Jeor BMR
    if ($gender === "male") {
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
    } else {
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
    }

    // Light activity factor = 1.375 (your choice)
    return round($bmr * 1.375);
}

function getBMICategory($bmi)
{
    if (!$bmi)
        return null;

    if ($bmi < 18.5)
        return "bulking";
    if ($bmi < 25)
        return "keeping";
    return "cutting";
}


?>