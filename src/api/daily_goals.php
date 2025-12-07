<?php
include '../config.php';
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
   GET → AUTO CALCULATE + UPSERT DAILY GOALS
   ============================================================ */
if ($method === 'GET' && isset($_GET['userId']) && isset($_GET['date'])) {

    $userId = intval($_GET['userId']);
    $tanggal = $_GET['date'];  // e.g. "2025-12-03"

    // Determine 7-day range: from 6 days before until today (total 7 days)
    $startDate = date('Y-m-d', strtotime($tanggal . ' -6 day'));
    $endDate = $tanggal;

    /* ============================================================
       1. USER PROFILE — base calorie target & macro split
       ============================================================ */
    $stmtUser = $conn->prepare("
        SELECT weight, daily_calories_target 
        FROM users 
        WHERE id = ?
    ");
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $user = $stmtUser->get_result()->fetch_assoc();

    if (!$user)
        errorRes("User not found");

    $calGoalBase = intval($user['daily_calories_target']);

    // Macro goals base (if daily_goals not present for that day)
    $proteinGoalBase = round(($calGoalBase * 0.20) / 4);
    $carbsGoalBase = round(($calGoalBase * 0.50) / 4);
    $fatGoalBase = round(($calGoalBase * 0.30) / 9);

    /* ============================================================
       2. TODAY — Fetch consumed macros from diary
       ============================================================ */
    $stmtDiaryToday = $conn->prepare("
        SELECT 
            SUM(IF(d.id_meals IS NOT NULL, m.protein,  f.protein_per_unit)) AS protein,
            SUM(IF(d.id_meals IS NOT NULL, m.carbs,    f.carbs_per_unit))   AS carbs,
            SUM(IF(d.id_meals IS NOT NULL, m.fat,      f.fat_per_unit))     AS fat,
            SUM(IF(d.id_meals IS NOT NULL, m.calories, f.calories_per_unit)) AS calories
        FROM diary d
        LEFT JOIN meals m ON d.id_meals = m.id
        LEFT JOIN foods f ON d.id_foods = f.id
        WHERE d.id_user = ?
        AND d.date = ?
    ");
    $stmtDiaryToday->bind_param("is", $userId, $tanggal);
    $stmtDiaryToday->execute();
    $nutrToday = $stmtDiaryToday->get_result()->fetch_assoc();

    $consumedCaloriesToday = intval($nutrToday['calories'] ?? 0);
    $consumedProteinToday = intval($nutrToday['protein'] ?? 0);
    $consumedCarbsToday = intval($nutrToday['carbs'] ?? 0);
    $consumedFatToday = intval($nutrToday['fat'] ?? 0);

    /* ============================================================
       3. TODAY — Water total
       ============================================================ */
    $stmtWater = $conn->prepare("
        SELECT SUM(amount_ml) AS water 
        FROM water_log 
        WHERE id_user = ? AND date = ?
    ");
    $stmtWater->bind_param("is", $userId, $tanggal);
    $stmtWater->execute();
    $water = $stmtWater->get_result()->fetch_assoc()['water'] ?? 0;

    /* ============================================================
       4. TODAY — Goals (use daily_goals if exists, else base)
       ============================================================ */
    $stmtGoalToday = $conn->prepare("
        SELECT calorie_goal, protein_goal, carbs_goal, fat_goal
        FROM daily_goals
        WHERE user_id = ? AND tanggal = ?
        LIMIT 1
    ");
    $stmtGoalToday->bind_param("is", $userId, $tanggal);
    $stmtGoalToday->execute();
    $goalTodayRow = $stmtGoalToday->get_result()->fetch_assoc();

    if ($goalTodayRow) {
        $calGoalToday = intval($goalTodayRow['calorie_goal']);
        $proteinGoalToday = intval($goalTodayRow['protein_goal']);
        $carbsGoalToday = intval($goalTodayRow['carbs_goal']);
        $fatGoalToday = intval($goalTodayRow['fat_goal']);
    } else {
        // fallback to base
        $calGoalToday = $calGoalBase;
        $proteinGoalToday = $proteinGoalBase;
        $carbsGoalToday = $carbsGoalBase;
        $fatGoalToday = $fatGoalBase;
    }

    $remainingCaloriesToday = max(0, $calGoalToday - $consumedCaloriesToday);
    $remainingProteinToday = max(0, $proteinGoalToday - $consumedProteinToday);
    $remainingCarbsToday = max(0, $carbsGoalToday - $consumedCarbsToday);
    $remainingFatToday = max(0, $fatGoalToday - $consumedFatToday);

    /* ============================================================
       5. TODAY — Recommendations text
       ============================================================ */
    $rec = [];

    if ($remainingProteinToday > 20) {
        $rec[] = "Increase protein intake (chicken, tofu, eggs).";
    }
    if ($remainingCarbsToday > 30) {
        $rec[] = "Add more carbs (rice, oats, bread, fruits).";
    }
    if ($remainingFatToday > 10) {
        $rec[] = "Add healthy fats (nuts, avocado, olive oil).";
    }
    if ($consumedCaloriesToday < ($calGoalToday * 0.50)) {
        $rec[] = "You're below 50% of your calorie target — consider eating a meal.";
    }
    if ($water < 1000) {
        $rec[] = "Drink more water today.";
    }

    if (empty($rec)) {
        $rec[] = "Great job! You're on track today.";
    }

    $recommendationText = implode("\n", $rec);

    /* ============================================================
       6. UPSERT TODAY into daily_goals (store goals & recommendation)
       ============================================================ */
    $stmtCheck = $conn->prepare("
        SELECT id FROM daily_goals 
        WHERE user_id = ? AND tanggal = ?
    ");
    $stmtCheck->bind_param("is", $userId, $tanggal);
    $stmtCheck->execute();
    $exists = $stmtCheck->get_result()->fetch_assoc();

    if ($exists) {
        $stmtUp = $conn->prepare("
            UPDATE daily_goals SET 
                calorie_goal   = ?, 
                protein_goal   = ?, 
                carbs_goal     = ?, 
                fat_goal       = ?,
                recommendation = ?
            WHERE id = ?
        ");
        $stmtUp->bind_param(
            "iiiisi",
            $calGoalToday,
            $proteinGoalToday,
            $carbsGoalToday,
            $fatGoalToday,
            $recommendationText,
            $exists['id']
        );
        if (!$stmtUp->execute()) {
            errorRes("UPDATE ERROR: " . $stmtUp->error);
        }
        $goalId = $exists['id'];
    } else {
        $stmtIn = $conn->prepare("
            INSERT INTO daily_goals 
                (user_id, tanggal, calorie_goal, protein_goal, carbs_goal, fat_goal, recommendation)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmtIn->bind_param(
            "isiiiss",
            $userId,
            $tanggal,
            $calGoalToday,
            $proteinGoalToday,
            $carbsGoalToday,
            $fatGoalToday,
            $recommendationText
        );
        if (!$stmtIn->execute()) {
            errorRes("INSERT ERROR: " . $stmtIn->error);
        }
        $goalId = $stmtIn->insert_id;
    }

    /* ============================================================
       7. HISTORY (7 days) — goals + consumed (calories & macros)
       ============================================================ */

    // Initialize history for each of the 7 days with base goals & 0 consumption
    $historyMap = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime($tanggal . " -$i day"));
        $historyMap[$d] = [
            "date" => $d,
            "goal" => [
                "calories" => $calGoalBase,
                "protein" => $proteinGoalBase,
                "carbs" => $carbsGoalBase,
                "fat" => $fatGoalBase
            ],
            "consumed" => [
                "calories" => 0,
                "protein" => 0,
                "carbs" => 0,
                "fat" => 0
            ]
        ];
    }

    // Override goals with daily_goals if available for those days
    $stmtGoal7 = $conn->prepare("
        SELECT 
            tanggal AS date,
            calorie_goal,
            protein_goal,
            carbs_goal,
            fat_goal
        FROM daily_goals
        WHERE user_id = ?
        AND tanggal BETWEEN ? AND ?
        ORDER BY tanggal ASC
    ");
    $stmtGoal7->bind_param("iss", $userId, $startDate, $endDate);
    $stmtGoal7->execute();
    $goals7 = $stmtGoal7->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($goals7 as $g) {
        $d = $g['date'];
        if (!isset($historyMap[$d]))
            continue;
        $historyMap[$d]['goal'] = [
            "calories" => intval($g['calorie_goal']),
            "protein" => intval($g['protein_goal']),
            "carbs" => intval($g['carbs_goal']),
            "fat" => intval($g['fat_goal'])
        ];
    }

    // Attach consumed macros per day from diary
    $stmt7 = $conn->prepare("
        SELECT 
            d.date,
            SUM(IF(d.id_meals IS NOT NULL, m.calories, f.calories_per_unit)) AS calories,
            SUM(IF(d.id_meals IS NOT NULL, m.protein,  f.protein_per_unit))  AS protein,
            SUM(IF(d.id_meals IS NOT NULL, m.carbs,    f.carbs_per_unit))    AS carbs,
            SUM(IF(d.id_meals IS NOT NULL, m.fat,      f.fat_per_unit))      AS fat
        FROM diary d
        LEFT JOIN meals m ON d.id_meals = m.id
        LEFT JOIN foods f ON d.id_foods = f.id
        WHERE d.id_user = ?
        AND d.date BETWEEN ? AND ?
        GROUP BY d.date
        ORDER BY d.date ASC
    ");
    $stmt7->bind_param("iss", $userId, $startDate, $endDate);
    $stmt7->execute();
    $diary7 = $stmt7->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($diary7 as $dRow) {
        $d = $dRow['date'];
        if (!isset($historyMap[$d]))
            continue;

        $historyMap[$d]['consumed'] = [
            "calories" => intval($dRow['calories']),
            "protein" => intval($dRow['protein']),
            "carbs" => intval($dRow['carbs']),
            "fat" => intval($dRow['fat'])
        ];
    }

    // Convert map to indexed array, sorted by date ASC
    $history7Days = array_values($historyMap);

    // AI Recommendation
    $aiInsights = geminiAnalyze($history7Days);

    /* ============================================================
       8. FINAL RESPONSE (matches your example + history7Days)
       ============================================================ */
    success([
        "id" => $goalId,
        "userId" => $userId,
        "tanggal" => $tanggal,

        "goal" => [
            "calorieGoal" => $calGoalToday,
            "proteinGoal" => $proteinGoalToday,
            "carbsGoal" => $carbsGoalToday,
            "fatGoal" => $fatGoalToday
        ],

        "consumed" => [
            "calories" => $consumedCaloriesToday,
            "protein" => $consumedProteinToday,
            "carbs" => $consumedCarbsToday,
            "fat" => $consumedFatToday,
            "water" => intval($water)
        ],

        "remaining" => [
            "calories" => $remainingCaloriesToday,
            "protein" => $remainingProteinToday,
            "carbs" => $remainingCarbsToday,
            "fat" => $remainingFatToday
        ],

        "recommendation" => $aiInsights,

        // NEW: last 7 days (including today) → goal & consumed per day
        "history7Days" => $history7Days
    ]);
}

function geminiAnalyze($history7Days)
{
    $apiKey = "AIzaSyAOIybZG8VL1neBpumwaCrroKib0XdHeTA";

    if (!$apiKey) {
        return ["error" => "Missing API Key"];
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";

    // -----------------------------
    // PROMPT LEBIH STABIL & MOTIVASI
    // -----------------------------
    $prompt = "
Kamu adalah AI nutrisi profesional namun ramah.

Instruksi Output (WAJIB DIPATUHI):
1. Jawaban HANYA berupa JSON valid.
2. Tidak boleh JSON dalam bentuk string.
3. Tidak boleh menggunakan markdown (tidak ada ```).
4. Tidak boleh ada teks sebelum '{' atau sesudah '}'.
5. Nada penulisan positif, suportif, dan memotivasi.
6. Jika data pengguna rendah/kurang lengkap, tetap beri dukungan & dorongan tanpa menghakimi.

Format JSON:
{
  \"ringkasan_mingguan\": {
    \"gambaran_umum\": \"\",
    \"konsumsi_kalori\": \"\"
  },
  \"analisis_makro\": {
    \"protein\": \"\",
    \"karbohidrat\": \"\",
    \"lemak\": \"\",
    \"kekurangan_atau_berlebihan\": \"\"
  },
  \"area_diperbaiki\": [
    {
      \"judul\": \"\",
      \"dampak\": \"\"
    }
  ],
  \"rekomendasi\": [
    \"\"
  ]
}

Mulai jawabanmu *langsung* dengan '{'.

Data 7 hari pengguna:
" . json_encode($history7Days, JSON_PRETTY_PRINT);


    $postData = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    // -----------------------------
    // CURL REQUEST
    // -----------------------------
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "?key=" . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ⚠ aktifkan true di production

    $result = curl_exec($ch);
    $error  = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) return ["error" => "cURL ERROR: $error"];
    if ($status !== 200) return ["error" => "HTTP ERROR $status", "raw" => $result];

    $json = json_decode($result, true);

    $text = $json["candidates"][0]["content"]["parts"][0]["text"] ?? null;

    if (!$text) return ["error" => "Gemini returned no text", "raw" => $result];


    // ============================================================
    //  AUTO JSON CONVERTER — VERSI PALING KUAT & STABIL
    // ============================================================

    // 1. Bersihkan whitespace & karakter aneh
    $clean = trim($text);
    $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean); // invisible chars

    // 2. Hapus backticks & codeblock
    $clean = str_replace("```", "", $clean);
    $clean = preg_replace('/```json/i', '', $clean);

    // 3. Hapus prefix teks sebelum JSON
    $pos = strpos($clean, "{");
    if ($pos !== false) {
        $clean = substr($clean, $pos);
    }

    // 4. Ambil sampai karakter '}' terakhir
    $end = strrpos($clean, "}");
    if ($end !== false) {
        $clean = substr($clean, 0, $end + 1);
    }

    // 5. Coba decode JSON normal
    $decoded = json_decode($clean, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // 6. Try fix minor JSON errors (quotes & trailing commas)
    $clean2 = preg_replace('/,\s*}/', '}', $clean);
    $clean2 = preg_replace('/,\s*]/', ']', $clean2);

    $decoded2 = json_decode($clean2, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded2;
    }

    // 7. If still fails → return raw output for debugging
    return [
        "error" => "AI JSON parsing failed: " . json_last_error_msg(),
        "raw"   => $text,
        "clean_attempt" => $clean
    ];
}
