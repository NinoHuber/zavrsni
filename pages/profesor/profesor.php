<?php
session_start();
$db = new mysqli("localhost", "root", "", "zavrsni");

$profesor_id = $_SESSION['korisnik_id'];
$success = "";
$error = "";

// 1. Handle status, review, and comment updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prijava_id']) && isset($_POST['status'])) {
    $prijava_id = (int)$_POST['prijava_id'];
    $new_status = $_POST['status'];
    $komentar = trim($_POST['komentar']);

    $stmt = $db->prepare("UPDATE prijave_rada SET status = ?, komentar = ? WHERE id = ? AND profesor_id = ?");
    $stmt->bind_param("ssii", $new_status, $komentar, $prijava_id, $profesor_id);

    if ($stmt->execute()) {
        $success = "Status i komentar rada su uspješno ažurirani!";
    } else {
        $error = "Greška prilikom ažuriranja rada.";
    }
    $stmt->close();
}

// 2. Handle sending a message from professor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prof_nova_poruka'])) {
    $porukaText = trim($_POST['prof_nova_poruka']);
    $prijavaId = (int)$_POST['chat_prijava_id'];

    if (!empty($porukaText)) {
        $msgStmt = $db->prepare("INSERT INTO poruke (prijava_id, posiljatelj_id, poruka) VALUES (?, ?, ?)");
        $msgStmt->bind_param("iis", $prijavaId, $profesor_id, $porukaText);
        $msgStmt->execute();
        $msgStmt->close();
        header("Location: profesor.php");
        exit();
    }
}

// Pagination, Search, and Filter configuration
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$studij_filter = isset($_GET['studij_filter']) ? (int)$_GET['studij_filter'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%" . $search . "%";

// Build dynamic WHERE clause
$conditions = ["p.profesor_id = ?"];
$types = "i";
$bindParams = [$profesor_id];

if (!empty($status_filter)) {
    $conditions[] = "p.status = ?";
    $types .= "s";
    $bindParams[] = $status_filter;
}

if ($studij_filter > 0) {
    $conditions[] = "st.studij_id = ?";
    $types .= "i";
    $bindParams[] = $studij_filter;
}

if (!empty($search)) {
    $conditions[] = "k.ime LIKE ?";
    $types .= "s";
    $bindParams[] = $searchParam;
}

$whereClause = "WHERE " . implode(" AND ", $conditions);

// Count total matching rows
$countQuery = "SELECT COUNT(DISTINCT p.id) FROM prijave_rada p 
               JOIN korisnici k ON p.student_id = k.id 
               JOIN studenti st ON p.student_id = st.korisnik_id 
               $whereClause";

$stmt = $db->prepare($countQuery);
$bindArgs = [$types];
foreach ($bindParams as $key => $value) {
    $bindArgs[] = &$bindParams[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bindArgs);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_row()[0];
$totalPages = ceil($totalRows / $limit);
$stmt->close();

// Fetch paginated, filtered, and searched student applications
$query = "SELECT p.*, k.ime AS student_ime, k.email AS student_email, s.naziv AS studij_naziv 
          FROM prijave_rada p 
          JOIN korisnici k ON p.student_id = k.id 
          JOIN studenti st ON p.student_id = st.korisnik_id 
          JOIN studiji s ON st.studij_id = s.id 
          $whereClause 
          ORDER BY p.id DESC LIMIT ? OFFSET ?";

$typesData = $types . "ii";
$bindParamsData = $bindParams;
$bindParamsData[] = $limit;
$bindParamsData[] = $offset;

$stmt = $db->prepare($query);
$bindArgsData = [$typesData];
foreach ($bindParamsData as $key => $value) {
    $bindArgsData[] = &$bindParamsData[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bindArgsData);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all majors for the filter dropdown
$studijiResult = $db->query("SELECT id, naziv FROM studiji ORDER BY naziv ASC");
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesor - Pregled radova studenata</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/profesor.css">
</head>
<body>
    <header class="prof-header">
        <div class="header-content">
            <h2>Portal za profesore</h2>
            <a href="../../index.php" class="logout-link">Odjava</a>
        </div>
    </header>

    <main class="prof-main">
        <section class="prof-banner">
            <h1>Dobrodošli, <?php echo htmlspecialchars($_SESSION['ime'] ?? 'Profesor'); ?></h1>
            <p>Pregledajte zahtjeve studenata, teme radova, ocjenjujte dokumente i razmjenjujte poruke.</p>
        </section>

        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="prof-card">
            <!-- Toolbar with Search and Filters -->
            <div class="prof-toolbar">
                <form action="" method="GET" class="filter-form" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="input-group" style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="search">Pretraži:</label>
                        <input type="text" name="search" id="search" placeholder="Ime studenta..." value="<?php echo htmlspecialchars($search); ?>" style="height: 38px; padding: 0 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    </div>

                    <div class="input-group" style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="studij_filter">Studij:</label>
                        <select name="studij_filter" id="studij_filter">
                            <option value="">-- Svi studiji --</option>
                            <?php while ($studij = $studijiResult->fetch_assoc()): ?>
                                <option value="<?php echo $studij['id']; ?>" <?php if($studij_filter == $studij['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($studij['naziv']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="input-group" style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="status_filter">Status:</label>
                        <select name="status_filter" id="status_filter">
                            <option value="">-- Svi statusi --</option>
                            <option value="Ceka odobrenje" <?php if($status_filter == 'Ceka odobrenje') echo 'selected'; ?>>Čeka odobrenje</option>
                            <option value="Odobreno" <?php if($status_filter == 'Odobreno') echo 'selected'; ?>>Odobreno</option>
                            <option value="Ispravak" <?php if($status_filter == 'Ispravak') echo 'selected'; ?>>Ispravak</option>
                        </select>
                    </div>

                    <button type="submit" class="blueBtn" style="height: 38px; padding: 0 1rem; font-size: 0.875rem;">Primijeni</button>

                    <?php if (!empty($status_filter) || $studij_filter > 0 || !empty($search)): ?>
                        <a href="profesor.php" class="clear-search">Poništi filtere</a>
                    <?php endif; ?>
                </form>

                <div class="prof-count">
                    Ukupno prijava: <strong><?php echo $totalRows; ?></strong>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student / Studij</th>
                            <th>Tema rada</th>
                            <th>Predani rad</th>
                            <th>Status</th>
                            <th>Recenzija i komentar</th>
                            <th>Poruke / Chat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['student_ime']); ?></strong><br>
                                        <span class="text-muted" style="font-size: 0.8125rem;"><?php echo htmlspecialchars($row['student_email']); ?></span><br>
                                        <span class="badge" style="background-color: #e0e7ff; color: #4338ca; margin-top: 4px;"><?php echo htmlspecialchars($row['studij_naziv'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['tema'] ?? 'Nije uneseno'); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['pdf_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['pdf_path']); ?>" target="_blank" class="pdf-link">Pregledaj PDF</a>
                                        <?php else: ?>
                                            <span class="text-muted">Nije predano</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="" method="POST" class="review-form">
                                            <input type="hidden" name="prijava_id" value="<?php echo $row['id']; ?>">
                                            
                                            <div class="form-row">
                                                <select name="status" required>
                                                    <option value="Ceka odobrenje" <?php if($row['status'] == 'Ceka odobrenje') echo 'selected'; ?>>Čeka odobrenje</option>
                                                    <option value="Odobreno" <?php if($row['status'] == 'Odobreno') echo 'selected'; ?>>Odobreno</option>
                                                    <option value="Ispravak" <?php if($row['status'] == 'Ispravak') echo 'selected'; ?>>Zatraži ispravak</option>
                                                </select>
                                            </div>

                                            <div class="form-row">
                                                <textarea name="komentar" placeholder="Upute za ispravak..."><?php echo htmlspecialchars($row['komentar'] ?? ''); ?></textarea>
                                            </div>

                                            <button type="submit" class="blueBtn">Spremi</button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="student-chat-container">
                                            <div class="chat-box-mini">
                                                <?php
                                                $msgQuery = "SELECT p.*, k.ime AS posiljatelj_ime FROM poruke p JOIN korisnici k ON p.posiljatelj_id = k.id WHERE p.prijava_id = ? ORDER BY p.vrijeme ASC";
                                                $msgStmt = $db->prepare($msgQuery);
                                                $prijavaId = $row['id'];
                                                $msgStmt->bind_param("i", $prijavaId);
                                                $msgStmt->execute();
                                                $messages = $msgStmt->get_result();

                                                if ($messages && $messages->num_rows > 0):
                                                    while ($msg = $messages->fetch_assoc()):
                                                        $isMe = ($msg['posiljatelj_id'] == $profesor_id);
                                                ?>
                                                        <div class="chat-message <?php echo $isMe ? 'sent' : 'received'; ?>">
                                                            <span class="msg-author"><?php echo htmlspecialchars($msg['posiljatelj_ime']); ?></span>
                                                            <p><?php echo nl2br(htmlspecialchars($msg['poruka'])); ?></p>
                                                            <span class="msg-time"><?php echo date('d.m. H:i', strtotime($msg['vrijeme'])); ?></span>
                                                        </div>
                                                <?php 
                                                    endwhile;
                                                else:
                                                ?>
                                                    <p class="text-muted" style="font-size: 0.8125rem; text-align: center; margin: 1rem 0;">Nema poruka.</p>
                                                <?php 
                                                endif;
                                                $msgStmt->close();
                                                ?>
                                            </div>

                                            <form action="" method="POST" class="chat-form-mini">
                                                <input type="hidden" name="chat_prijava_id" value="<?php echo $row['id']; ?>">
                                                <input type="text" name="prof_nova_poruka" placeholder="Napiši poruku..." required>
                                                <button type="submit" class="blueBtn">Pošalji</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-data">Trenutno nema prijava s odabranim kriterijima.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination maintaining search and filter parameters -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <div class="pagination-container">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status_filter=<?php echo urlencode($status_filter); ?>&studij_filter=<?php echo $studij_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Prethodna</a>
                    <?php else: ?>
                        <span class="page-link disabled">&laquo; Prethodna</span>
                    <?php endif; ?>
         
                    <span class="page-indicator">Stranica <strong><?php echo $page; ?></strong> od <strong><?php echo $totalPages; ?></strong></span>
         
                    <?php if($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status_filter=<?php echo urlencode($status_filter); ?>&studij_filter=<?php echo $studij_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Sljedeća &raquo;</a>
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