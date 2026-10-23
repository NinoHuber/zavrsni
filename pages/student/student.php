<?php
session_start();
$db = new mysqli("localhost", "root", "", "zavrsni");

if (!isset($_SESSION['korisnik_id']) || $_SESSION['uloga'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['korisnik_id'];
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profesor_id'])) {
    $profesor_id = (int)$_POST['profesor_id'];
    $tema = trim($_POST['tema'] ?? '');

    if (empty($tema)) {
        $error = "Molimo unesite predloženu temu za završni rad.";
    } else {
        $check = $db->prepare("SELECT id FROM prijave_rada WHERE student_id = ?");
        $check->bind_param("i", $student_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $stmt = $db->prepare("UPDATE prijave_rada SET profesor_id = ?, tema = ?, status = 'Ceka odobrenje' WHERE student_id = ?");
            $stmt->bind_param("isi", $profesor_id, $tema, $student_id);
        } else {
            $stmt = $db->prepare("INSERT INTO prijave_rada (student_id, profesor_id, tema, status) VALUES (?, ?, ?, 'Ceka odobrenje')");
            $stmt->bind_param("iis", $student_id, $profesor_id, $tema);
        }
        
        if ($stmt->execute()) {
            $success = "Zahtjev za mentora i tema su uspješno poslani!";
        } else {
            $error = "Greška prilikom slanja zahtjeva.";
        }
        $stmt->close();
        $check->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['rad_pdf'])) {
    if (isset($_FILES['rad_pdf']) && $_FILES['rad_pdf']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['rad_pdf']['tmp_name'];
        $fileName = $_FILES['rad_pdf']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'pdf') {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'student_' . $student_id . '_' . time() . '.pdf';
            $dest_path = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $stmt = $db->prepare("UPDATE prijave_rada SET pdf_path = ?, status = 'Ceka odobrenje' WHERE student_id = ?");
                $stmt->bind_param("si", $dest_path, $student_id);
                $stmt->execute();
                $stmt->close();
                $success = "Završni rad je uspješno poslan na pregled!";
            } else {
                $error = "Greška prilikom spremanja datoteke na poslužitelj.";
            }
        } else {
            $error = "Dopušteni su samo dokumenti u PDF formatu.";
        }
    } else {
        $error = "Molimo odaberite PDF datoteku.";
    }
}

$stmt = $db->prepare("SELECT p.*, k.ime AS profesor_ime FROM prijave_rada p LEFT JOIN korisnici k ON p.profesor_id = k.id WHERE p.student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

$professors = $db->query("SELECT id, ime, email FROM korisnici WHERE uloga_id = 2 AND status = 'AKTIVAN'");
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student - Upravljanje završnim radom</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/student.css">
</head>
<body>
    <header class="student-header">
        <div class="header-content">
            <h2>Portal za studente</h2>
            <a href="../../index.php" class="logout-link">Odjava</a>
        </div>
    </header>

    <main class="student-main">
        <section class="student-banner">
            <h1>Dobrodošli, <?php echo htmlspecialchars($_SESSION['ime'] ?? 'Student'); ?></h1>
            <p>Odaberite mentora, prenesite svoj završni rad i pratite povratne informacije.</p>
        </section>

        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="student-grid">
            <!-- Section 1: Mentor Selection & Topic -->
<div class="dashboard-card">
    <h2>Odabir mentora i teme</h2>
    <?php if (!empty($application['profesor_ime'])): ?>
        <div class="status-box">
            <p>Vaš odabrani mentor: <strong><?php echo htmlspecialchars($application['profesor_ime']); ?></strong></p>
            <p>Predložena tema: <strong><?php echo htmlspecialchars($application['tema'] ?? 'Nije unesena'); ?></strong></p>
            <p>Status zahtjeva: <span class="badge <?php echo strtolower(str_replace(' ', '-', $application['status'])); ?>"><?php echo htmlspecialchars($application['status']); ?></span></p>
        </div>
        <?php if ($application['status'] === 'Ispravak' || empty($application['pdf_path'])): ?>
            <p class="card-hint">Možete izmijeniti mentora ili temu ako je potrebno:</p>
            <form action="" method="POST">
                <div class="input-group" style="margin-bottom: 1rem;">
                    <label for="profesor_id">Promijeni mentora</label>
                    <select name="profesor_id" id="profesor_id" required>
                        <option value="">-- Odaberite profesora --</option>
                        <?php 
                        $professors->data_seek(0);
                        while($prof = $professors->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $prof['id']; ?>" <?php echo ($prof['id'] == $application['profesor_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prof['ime']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group" style="margin-bottom: 1rem;">
                    <label for="tema">Predložena tema rada</label>
                    <input type="text" name="tema" id="tema" value="<?php echo htmlspecialchars($application['tema'] ?? ''); ?>" required placeholder="Unesite naziv teme...">
                </div>
                <button type="submit" class="blueBtn" style="width: 100%;">Ažuriraj zahtjev</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <p>Odaberite profesora i predložite vlastitu temu završnog rada:</p>
        <form action="" method="POST">
            <div class="input-group" style="margin-bottom: 1rem;">
                <label for="profesor_id">Popis profesora</label>
                <select name="profesor_id" id="profesor_id" required>
                    <option value="">-- Odaberite profesora --</option>
                    <?php while($prof = $professors->fetch_assoc()): ?>
                        <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['ime']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="input-group" style="margin-bottom: 1rem;">
                <label for="tema">Naziv/Tema završnog rada</label>
                <input type="text" name="tema" id="tema" placeholder="npr. Izrada web aplikacije u PHP-u" required>
            </div>
            <button type="submit" class="blueBtn" style="width: 100%;">Pošalji zahtjev</button>
        </form>
    <?php endif; ?>
</div>

            <div class="dashboard-card">
                <h2>Predaja završnog rada</h2>
                <?php if (!empty($application['profesor_ime'])): ?>
                    <div class="submission-status">
                        <p>Status rada: <span class="badge <?php echo strtolower(str_replace(' ', '-', $application['status'] ?? '')); ?>"><?php echo htmlspecialchars($application['status'] ?? 'Nije predano'); ?></span></p>
                        
                        <?php if (!empty($application['komentar'])): ?>
                            <div class="comment-box">
                                <strong>Komentar mentora:</strong>
                                <p><?php echo nl2br(htmlspecialchars($application['komentar'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($application['pdf_path'])): ?>
                            <p class="file-info">Predana datoteka: <a href="<?php echo htmlspecialchars($application['pdf_path']); ?>" target="_blank">Pregledaj PDF</a></p>
                        <?php endif; ?>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" style="margin-top: 1.5rem;">
                        <div class="input-group" style="margin-bottom: 1rem;">
                            <label for="rad_pdf"><?php echo !empty($application['pdf_path']) ? 'Ponovno pošalji ispravljeni rad (.pdf)' : 'Pošalji završni rad (.pdf)'; ?></label>
                            <input type="file" name="rad_pdf" id="rad_pdf" accept="application/pdf" required>
                        </div>
                        <button type="submit" class="blueBtn" style="width: 100%;">Predaj rad</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Morate najprije odabrati mentora prije nego što možete predati završni rad.</p>
                <?php endif; ?>
            </div>

            <?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_poruka']) && !empty($application['id'])) {
    $porukaText = trim($_POST['nova_poruka']);
    $prijavaId = $application['id'];

    if (!empty($porukaText)) {
        $msgStmt = $db->prepare("INSERT INTO poruke (prijava_id, posiljatelj_id, poruka) VALUES (?, ?, ?)");
        $msgStmt->bind_param("iis", $prijavaId, $student_id, $porukaText);
        $msgStmt->execute();
        $msgStmt->close();
        header("Location: student.php");
        exit();
    }
}

$messages = [];
if (!empty($application['id'])) {
    $msgQuery = "SELECT p.*, k.ime AS posiljatelj_ime FROM poruke p JOIN korisnici k ON p.posiljatelj_id = k.id WHERE p.prijava_id = ? ORDER BY p.vrijeme ASC";
    $msgStmt = $db->prepare($msgQuery);
    $msgStmt->bind_param("i", $application['id']);
    $msgStmt->execute();
    $messages = $msgStmt->get_result();
    $msgStmt->close();
}
?>

<?php if (!empty($application['profesor_ime'])): ?>
<div class="dashboard-card chat-card">
    <h2>Komunikacija s mentorom</h2>
    <p>Razmijenite poruke i upute s mentorem u stvarnom vremenu.</p>
    
    <div class="chat-box">
        <?php if ($messages && $messages->num_rows > 0): ?>
            <?php while ($msg = $messages->fetch_assoc()): ?>
                <div class="chat-message <?php echo ($msg['posiljatelj_id'] == $student_id) ? 'sent' : 'received'; ?>">
                    <span class="msg-author"><?php echo htmlspecialchars($msg['posiljatelj_ime']); ?></span>
                    <p><?php echo nl2br(htmlspecialchars($msg['poruka'])); ?></p>
                    <span class="msg-time"><?php echo date('d.m.Y. H:i', strtotime($msg['vrijeme'])); ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted" style="text-align: center; padding: 1rem;">Nema poruka. Započnite razgovor s mentorom.</p>
        <?php endif; ?>
    </div>

    <form action="" method="POST" class="chat-form">
        <textarea name="nova_poruka" placeholder="Napišite poruku mentoru..." required></textarea>
        <button type="submit" class="blueBtn">Pošalji</button>
    </form>
</div>
<?php endif; ?>
        </div>
    </main>
</body>
</html>
<?php $db->close(); ?>