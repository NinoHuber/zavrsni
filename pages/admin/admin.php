<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <link rel="stylesheet" href="../../css/adminHeader.css">
    <script src="../../components/adminHeader.php"></script>
    <title>Admin Dashboard</title>
</head>
<body>
    <admin-header></admin-header>

    <main class="admin-main">
        <section class="welcome-banner">
            <div class="welcome-text">
                <h1>Dobrodošli natrag, Administrator</h1>
                <p>Pregledajte pristigle prijave, upravljajte korisnicima i pratite status završnih radova.</p>
            </div>
            <div class="welcome-date">
                <span class="date-badge">Sustav aktivan</span>
            </div>
        </section>

<section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-content">
                    <h3>Ukupno korisnika</h3>
                    <?php 
                    $db = new mysqli("localhost", "root", "", "zavrsni");
                    $query = 'SELECT COUNT(*) AS total_users FROM korisnici';
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $ukupno = $stmt->get_result()->fetch_assoc()["total_users"];
                    $stmt->close();
                    echo '<p class="stat-number">'. $ukupno .'</p>';
                    ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-content">
                    <h3>Čeka odobrenje</h3>
                    <?php 
                    $queryCeka = 'SELECT COUNT(*) AS pending_users FROM korisnici WHERE status != "AKTIVAN"';
                    $stmtCeka = $db->prepare($queryCeka);
                    $stmtCeka->execute();
                    $cekaOdobrenje = $stmtCeka->get_result()->fetch_assoc()["pending_users"];
                    $stmtCeka->close();
                    echo '<p class="stat-number">'. $cekaOdobrenje .'</p>';
                    ?>
                    <span class="stat-trend warning">Zahtijeva pažnju</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="stat-content">
                    <h3>Odobreni radovi</h3>
                    <?php 
                    $queryRadovi = 'SELECT COUNT(*) AS approved_theses FROM prijave_rada WHERE status = "Odobreno"';
                    $stmtRadovi = $db->prepare($queryRadovi);
                    $stmtRadovi->execute();
                    $odobreniRadovi = $stmtRadovi->get_result()->fetch_assoc()["approved_theses"];
                    $stmtRadovi->close();
                    $db->close();
                    echo '<p class="stat-number">'. $odobreniRadovi .'</p>';
                    ?>
                    <span class="stat-trend positive">U tijeku izrade</span>
                </div>
            </div>
        </section>
    </main>
</body>
</html>