<?php
$db = new mysqli("localhost", "root", "", "zavrsni");

$json = file_get_contents("php://input");
$data = json_decode($json, true);

if($_SERVER["REQUEST_METHOD"] == "POST" && !isset($data["prihvacenId"])) {
        $ime = $data["ime"];
        $email = $data["email"];
        $lozinka = password_hash($data["lozinka"], PASSWORD_BCRYPT);
        $uloga_id = (int)$data["uloga"];
        $studij_id = isset($data["studij_id"]) ? (int)$data["studij_id"] : 0;
        
        $checkStmt = $db->prepare("SELECT id FROM korisnici WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->close();
            echo json_encode(["greska" => "Korisnik s ovom e-mail adresom već postoji."]);
            exit;
        }
        $checkStmt->close();

        $query = "INSERT INTO korisnici(ime, email, lozinka, uloga_id)
                VALUES (?, ?, ?, ?)";
            
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssi", $ime, $email, $lozinka, $uloga_id);
        
        if ($stmt->execute()) {
            $korisnik_id = $db->insert_id;
            $stmt->close();

            if ($uloga_id === 3 && $studij_id > 0) {
                $stmtStud = $db->prepare("INSERT INTO studenti (korisnik_id, studij_id) VALUES (?, ?)");
                $stmtStud->bind_param("ii", $korisnik_id, $studij_id);
                $stmtStud->execute();
                $stmtStud->close();
            }

            echo json_encode(["poruka" => "Prijava uspješno dodana"]);
        } else {
            echo json_encode(["greska" => "Greška prilikom registracije"]);
        }
        exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($data["prihvacenId"])) {
        $id = $data["prihvacenId"];
        $query = 'UPDATE korisnici SET status = "AKTIVAN" WHERE id = ?';
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(["status" => "status promjenut"]);
        exit;
}

if($_SERVER["REQUEST_METHOD"] == "DELETE") {
        $id = $_GET["id"];
        $query = "DELETE FROM korisnici WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        exit;
}
?>