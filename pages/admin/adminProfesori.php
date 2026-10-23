<?php
$db = new mysqli("localhost", "root", "", "zavrsni");

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    $stmt = $db->prepare("DELETE FROM korisnici WHERE id = ? AND uloga_id = 2");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = "Profesor je uspješno uklonjen iz sustava.";
    } else {
        $error = "Greška prilikom brisanja profesora.";
    }
    $stmt->close();
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%" . $search . "%";

$countQuery = "SELECT COUNT(*) FROM korisnici WHERE uloga_id = 2 AND status = 'AKTIVAN' AND ime LIKE ?";
$stmt = $db->prepare($countQuery);
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_row()[0];
$totalPages = ceil($totalRows / $limit);
$stmt->close();

$query = "SELECT id, ime, email FROM korisnici WHERE uloga_id = 2 AND status = 'AKTIVAN' AND ime LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$stmt->bind_param("sii", $searchParam, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Profesori</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/adminHeader.css">
    <link rel="stylesheet" href="../../css/adminProfesori.css">
    <script src="../../components/adminHeader.php"></script>
</head>
<body>
    <admin-header></admin-header>

    <main class="prof-main">
        <section class="prof-banner">
            <div class="prof-banner-text">
                <h1>Upravljanje profesorima</h1>
                <p>Pregled aktivnih profesora u sustavu, pretraživanje i upravljanje računima.</p>
            </div>
        </section>

        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="prof-card">
            <div class="prof-toolbar">
                <form action="" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Pretraži po imenu..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="blueBtn">Pretraži</button>
                    <?php if (!empty($search)): ?>
                        <a href="adminProfesori.php" class="clear-search">Poništi</a>
                    <?php endif; ?>
                </form>
                <div class="prof-count">
                    Aktivni profesori: <strong><?php echo $totalRows; ?></strong>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ime i prezime</th>
                            <th>Email adresa</th>
                            <th style="text-align: right;">Akcija</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['ime']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td style="text-align: right;">
                                        <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Jeste li sigurni da želite obrisati ovog profesora?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="redBtn">Obriši</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="no-data">Nema pronađenih aktivnih profesora.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <div class="pagination-container">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Prethodna</a>
                    <?php else: ?>
                        <span class="page-link disabled">&laquo; Prethodna</span>
                    <?php endif; ?>
         
                    <span class="page-indicator">Stranica <strong><?php echo $page; ?></strong> od <strong><?php echo $totalPages; ?></strong></span>
         
                    <?php if($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Sljedeća &raquo;</a>
                    <?php else: ?>
                        <span class="page-link disabled">Sljedeća &raquo;</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
<?php 
$stmt->close();
$db->close(); 
?>