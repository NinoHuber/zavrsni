<?php
$db = new mysqli("localhost", "root", "", "zavrsni");

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['naziv'])) {
    $naziv = trim($_POST['naziv']);
    
    if (!empty($naziv)) {
        $stmt = $db->prepare("INSERT INTO studiji (naziv) VALUES (?)");
        $stmt->bind_param("s", $naziv);
        
        if ($stmt->execute()) {
            $success = "Studij je uspješno dodan!";
        } else {
            $error = "Greška prilikom dodavanja studija.";
        }
        $stmt->close();
    } else {
        $error = "Naziv studija ne može biti prazan.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    $stmt = $db->prepare("DELETE FROM studiji WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = "Studij je uspješno obrisan!";
    } else {
        $error = "Greška prilikom brisanja studija.";
    }
    $stmt->close();
}

$result = $db->query("SELECT id, naziv FROM studiji ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Studiji</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../../css/adminHeader.css">
    <link rel="stylesheet" href="../../css/adminStudiji.css">
    <script src="../../components/adminHeader.php"></script>
</head>
<body>
    <admin-header></admin-header>

    <main class="admin-main">
        <section class="welcome-banner">
            <div class="welcome-text">
                <h1>Upravljanje studijima</h1>
                <p>Pregledajte postojeće studije, dodajte nove ili obrišite postojeće iz sustava.</p>
            </div>
        </section>

        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-content-grid">
            <div class="dashboard-card">
                <h2>Dodaj novi studij</h2>
                <p>Unesite naziv novog studija koji će biti dostupan u sustavu.</p>
                
                <form action="" method="POST">
                    <div class="input-group" style="margin-bottom: 1rem;">
                        <label for="naziv">Naziv studija</label>
                        <input type="text" id="naziv" name="naziv" placeholder="npr. Računarstvo" required>
                    </div>
                    <button type="submit" class="blueBtn" style="width: 100%; height: 42px; font-weight: 600;">Dodaj studij</button>
                </form>
            </div>

            <div class="dashboard-card">
                <h2>Postojeći studiji</h2>
                <p>Popis svih studija evidentiranih u bazi podataka.</p>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Naziv studija</th>
                                <th style="text-align: right;">Akcija</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['naziv']); ?></strong></td>
                                        <td style="text-align: right;">
                                            <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Jeste li sigurni da želite obrisati ovaj studij?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="redBtn" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem; width: auto; height: auto; font-weight: 500;">Obriši</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted);">Trenutno nema unesenih studija.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
<?php 
$db->close(); 
?>