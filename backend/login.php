<?php
$db = new mysqli("localhost", "root", "", "zavrsni");

$json = file_get_contents("php://input");
$data = json_decode($json, true);

$email = $data["email"];
$password = $data["password"];
$hashedPassword = "";

$stmt = $db->prepare('SELECT id, status, lozinka FROM korisnici WHERE email = ?');
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id, $status, $hashedPassword);

if (!$stmt->fetch()) {
    $stmt->close();
    echo json_encode(["error" => "Korisnik s tim emailom ne postoji"]);
    exit;
}
$stmt->close();

if ($status == "REGISTRACIJA") {
    echo json_encode(["error" => "Korisnik nije prihvaćen još"]);
    exit;
}

if (!password_verify($password, $hashedPassword)) {
    echo json_encode(["error" => "Netočna lozinka"]);
    exit;
}

$query = "SELECT k.id, u.uloga FROM uloge u
        LEFT JOIN korisnici k ON u.id = k.uloga_id
        WHERE k.email = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$uloga = $row["uloga"];
$korisnik_id = $row["id"];

switch($uloga) {
        case "admin":
                echo json_encode(["path" => "./pages/admin/admin.php"]);
                break;
        case "student":
            session_start();
            $_SESSION["korisnik_id"] = $korisnik_id;
            $_SESSION["uloga"] = $uloga;
            echo json_encode(["path" => "./pages/student/student.php"]);
            break;
        case "profesor":
            session_start();
            $_SESSION["korisnik_id"] = $korisnik_id;
            $_SESSION["uloga"] = $uloga;
            echo json_encode(["path" => "./pages/profesor/profesor.php"]);
            break;
}

?>