<?php
include '../config.php'; // includes mapToCamelCase + mapArrayToCamelCase
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

/* ============================================================
   GET ARTICLE API
   ============================================================ */
if ($method == 'GET') {
    

    /* ------------------------------
       SEARCH article by title/slug
    ------------------------------ */
    if (isset($_GET['search'])) {
        $stmt = $conn->prepare("
            SELECT *
            FROM articles
            WHERE title LIKE ? 
               OR slug LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("ss", $search, $search);
        $stmt->execute();

        $result = $stmt->get_result();
        $articles = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(mapArrayToCamelCase($articles));
        exit;
    }


    /* ------------------------------
       GET ARTICLE BY ID
    ------------------------------ */
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        echo json_encode(mapToCamelCase($row));
        exit;
    }


    /* ------------------------------
       GET ALL ARTICLES
    ------------------------------ */
    $result = $conn->query("SELECT * FROM articles ORDER BY created_at DESC");
    $articles = $result->fetch_all(MYSQLI_ASSOC);

    $result = json_encode(mapArrayToCamelCase($articles));

$data = mapArrayToCamelCase($articles);

// Bersihkan semua string di array (rekursif)
array_walk_recursive($data, function (&$value) {
    if (is_string($value)) {
        // buang karakter non-UTF8
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        // Atau:
        // $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
    }
});

$json = json_encode($data, JSON_UNESCAPED_UNICODE);

    echo $json;
    exit;
}


/* ============================================================
   INVALID METHOD
   ============================================================ */
echo json_encode(["error" => "Invalid request method"]);
exit;
?>
