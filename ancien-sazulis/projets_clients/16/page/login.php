<?php
session_start();
// Connexion à la base de données
$conn = new mysqli('localhost', 'root', '', 'les_doudou');
if ($conn->connect_error) {
    die('Erreur de connexion à la base de données');
}
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$sql = "SELECT * FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $username, $password);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $_SESSION['user'] = $username;
    header('Location: galerie.php');
    exit();
} else {
    header('Location: login.html?error=1');
    exit();
}
