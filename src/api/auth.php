<?php
include '../config.php';

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
        !isset($input['fullname']) ||
        !isset($input['email']) ||
        !isset($input['password'])
    ) {
        error("Fields fullname, email, and password are required");
    }

    $fullname = $input['fullname'];
    $email = $input['email'];
    $password = password_hash($input['password'], PASSWORD_DEFAULT);

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
        INSERT INTO users (fullname, email, password)
        VALUES (?, ?, ?)
    ");
    if (!$stmt)
        error("SQL ERROR PREPARE: " . $conn->error);

    $stmt->bind_param(
        "sss",
        $fullname,
        $email,
        $password,
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

    if (!password_verify($password, $user['password'])) {
        error("Invalid password");
    }

    // SUCCESS LOGIN
    success([
        "message" => "Login successful",
        "id" => $user['id'],
        "fullname" => $user['fullname'],
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
        SELECT id, fullname, email, gender, height, weight, age, daily_calories_target, created_at
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
// 5. UPDATE USER PROFILE (required: fullname, email, password, gender)
// ===================================================================
// if ($action === "update") {

//     if ($method !== 'PUT')
//         error("Invalid method");
//     if (!isset($_GET['id']))
//         error("User ID is required");

//     $id = $_GET['id'];

//     $fullname = $input['fullname'];
//     $email = $input['email'];
//     $gender = $input['gender'];
//     $password = password_hash($input['password'], PASSWORD_DEFAULT);

//     $height = $input['height'] ?? null;
//     $weight = $input['weight'] ?? null;
//     $age = $input['age'] ?? null;

//     $bmi = calculateBMI($weight, $height);
//     $dailyCal = calculateDailyCalories($weight, $height, $age, $gender);
//     $bmiCategory = getBMICategory($bmi);

//     // Get existing data
//     $check = $conn->prepare("SELECT * FROM users WHERE id=?");
//     if (!$check)
//         error("SQL ERROR PREPARE: " . $conn->error);

//     $check->bind_param("i", $id);
//     $check->execute();
//     $old = $check->get_result()->fetch_assoc();
//     if (!$old)
//         error("User not found");

//     // fallback old data
//     $height = $height ?? $old['height'];
//     $weight = $weight ?? $old['weight'];
//     $age = $age ?? $old['age'];
//     $dailyCal = $dailyCal ?? $old['daily_calories_target'];

//     // Update
//     $stmt = $conn->prepare("
//         UPDATE users 
// SET fullname=?, 
//     email=?, 
//     height=?, 
//     weight=?, 
//     age=?, 
//     gender=?, 
//     bmi=?, 
//     daily_calories_target=?, 
//     bmi_category=?, 
//     password=?
//         WHERE id=?
//     ");
//     if (!$stmt)
//         error("SQL ERROR PREPARE: " . $conn->error);

//     $stmt->bind_param(
//         "ssddisdissi",
//         $fullname,
//         $email,
//         $height,
//         $weight,
//         $age,
//         $gender,
//         $bmi,
//         $dailyCal,
//         $bmiCategory,
//         $password,
//         $id
//     );

//     if (!$stmt->execute())
//         error($stmt->error);

//     // Ambil ulang data user terbaru
//     $stmt2 = $conn->prepare("SELECT id, fullname, email, gender, height, weight, age, bmi, daily_calories_target, bmi_category FROM users WHERE id = ?");
//     $stmt2->bind_param("i", $id);
//     $stmt2->execute();
//     $updatedUser = $stmt2->get_result()->fetch_assoc();

//     success([
//         "message" => "User updated successfully",
//         "user" => $updatedUser
//     ]);

// }

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
   2. INSERT OR UPDATE HEALTH DATA (ALL FIELDS OPTIONAL)
   ============================================================ */
if ($method === 'POST' && $action === "update_health") {

    if (!isset($input['id']))
        error("id is required");

    $id = $input['id'];

    // Ambil data lama dulu
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt)
        error("SQL ERROR PREPARE: " . $conn->error);

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();

    if (!$old)
        error("User not found");

    // Gunakan nilai baru jika dikirim, jika tidak pakai nilai lama
    $height = $input['height'] ?? $old['height'] ?? null;
    $weight = $input['weight'] ?? $old['weight'] ?? null;
    $age = $input['age'] ?? $old['age'] ?? null;
    $gender = $input['gender'] ?? $old['gender'] ?? null;
    $waist = $input['waist_size'] ?? $old['waist_size'] ?? null;

    // Hitung ulang BMI & Daily Calories
    $bmi = calculateBMI($weight, $height);
    $dailyCal = calculateDailyCalories($weight, $height, $age, $gender);
    $bmiCategory = getBMICategory($bmi);


    /* ============================================================
    A) SIMPAN HISTORI SEBELUM UPDATE (only once per day)
   ============================================================ */
    date_default_timezone_set("Asia/Jakarta");
    $today = date("Y-m-d");

    $checkHist = $conn->prepare("
    SELECT id FROM health_history 
    WHERE user_id = ? AND DATE(created_at) = ?
");
    $checkHist->bind_param("is", $id, $today);
    $checkHist->execute();

    $result = $checkHist->get_result(); // <-- Panggil sekali

    $alreadyExists = $result->num_rows > 0;


    // Insert hanya jika belum ada histori hari ini
    if (!$alreadyExists) {

        $history = $conn->prepare("
        INSERT INTO health_history 
        (user_id, height, weight, age, gender, waist_size, bmi, daily_calories_target, bmi_category)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        if (!$history)
            error("SQL ERROR PREPARE HIST: " . $conn->error);

        $history->bind_param(
            "iddisddds",
            $id,
            $old['height'],
            $old['weight'],
            $old['age'],
            $old['gender'],
            $old['waist_size'],
            $old['bmi'],
            $old['daily_calories_target'],
            $old['bmi_category']
        );

        $history->execute();
    }


    /* ============================================================
        B) UPDATE DATA USER SAAT INI
       ============================================================ */
    $stmt2 = $conn->prepare("
        UPDATE users
        SET height=?, weight=?, age=?, gender=?, waist_size=?, 
            bmi=?, daily_calories_target=?, bmi_category=?
        WHERE id=?
    ");

    if (!$stmt2)
        error("SQL ERROR PREPARE UPDATE: " . $conn->error);

    $stmt2->bind_param(
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

    $stmt2->execute();


    /* ============================================================
        C) KIRIM DATA TERBARU KE FRONTEND
       ============================================================ */
    $stmt3 = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt3->bind_param("i", $id);
    $stmt3->execute();
    $updated = $stmt3->get_result()->fetch_assoc();

    success([
        "message" => "Health data updated successfully",
        "user" => $updated,
        "history_saved" => true
    ]);
}

/* ============================================================
   3. GET USER PROGRESS HISTORY (OPTIONAL DATE RANGE)
   ============================================================ */
if ($method === 'GET' && $action === "get_progress") {

    if (!isset($_GET['id']))
        error("id is required");

    $user_id = $_GET['id'];

    // Ambil rentang tanggal opsional
    $start = $_GET['start_date'] ?? null;
    $end = $_GET['end_date'] ?? null;

    /* ---------------------------
       Jika ada rentang tanggal
    ---------------------------- */
    if ($start && $end) {

        $stmt = $conn->prepare("
            SELECT * FROM health_history
            WHERE user_id = ?
              AND DATE(created_at) BETWEEN ? AND ?
            ORDER BY created_at ASC
        ");

        if (!$stmt)
            error("SQL ERROR PROGRESS: " . $conn->error);

        $stmt->bind_param("iss", $user_id, $start, $end);
    }

    /* ---------------------------
       Jika tanpa rentang tanggal
       → ambil semua histori
    ---------------------------- */ else {

        $stmt = $conn->prepare("
            SELECT * FROM health_history
            WHERE user_id = ?
            ORDER BY created_at ASC
        ");

        if (!$stmt)
            error("SQL ERROR PROGRESS: " . $conn->error);

        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    success([
        "message" => "Progress history loaded",
        "count" => count($history),
        "history" => $history
    ]);
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