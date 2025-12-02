<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nutritracknew";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function toCamelCase($string) {
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
}

function mapToCamelCase($row) {
    $new = [];
    foreach ($row as $key => $value) {
        $new[toCamelCase($key)] = $value;
    }
    return $new;
}

function mapArrayToCamelCase($rows) {
    return array_map('mapToCamelCase', $rows);
}

function mapRecursiveCamelCase($data) {
    if (is_array($data)) {
        $new = [];
        foreach ($data as $key => $value) {
            $new[toCamelCase($key)] = mapRecursiveCamelCase($value);
        }
        return $new;
    }
    return $data;
}


?>