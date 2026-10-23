<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin prijave</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/adminHeader.css">
    <link rel="stylesheet" href="../../css/buttons.css">
    <link rel="stylesheet" href="../../css/adminPrijave.css">
    <script src="../../components/adminHeader.php"></script>
</head>
<body>
    <admin-header></admin-header>

<div class="dashboard-wrapper">
    <div class="container">
        <?php 
        $db = new mysqli("localhost", "root", "", "zavrsni");
        $limit = 12;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $query = "SELECT COUNT(*) FROM korisnici WHERE status = 'REGISTRACIJA'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $totalRows = $stmt->get_result()->fetch_row()[0];
        $totalPages = ceil($totalRows / $limit);
        $stmt->close();

        $query = "SELECT k.id, k.ime, u.uloga FROM korisnici k
        LEFT JOIN uloge u ON u.id = k.uloga_id
        WHERE status = 'REGISTRACIJA'
        LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        
        $stmt->bind_result($id, $ime, $uloga);
        while($stmt->fetch()) {
            $roleDisplay = !empty($uloga) ? htmlspecialchars($uloga) : 'Nema uloge';
            echo '<div class="prijava" id="'. $id .'">
            <div class="prijava-info">
                <h2>'. $ime .'</h2>
                <span class="prijava-badge">'. $roleDisplay .'</span>
            </div>
            <div class="prijava-actions">
                <button class="redBtn">Odbij</button>
                <button class="blueBtn">Prihvati</button>
            </div>
            </div>
            ';
        }
        $stmt->close();
        $db->close();
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <div class="pagination-container">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="page-link">&laquo; Prethodna</a>
            <?php else: ?>
                <span class="page-link disabled">&laquo; Prethodna</span>
            <?php endif; ?>
 
            <span class="page-indicator">Stranica <strong><?php echo $page; ?></strong> od <strong><?php echo $totalPages; ?></strong></span>
 
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="page-link">Sljedeća &raquo;</a>
            <?php else: ?>
                <span class="page-link disabled">Sljedeća &raquo;</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

    <script>
        let odbijBtns = document.querySelectorAll(".redBtn")
        for (let i = 0; i < odbijBtns.length; i++) {
            odbijBtns[i].addEventListener("click", (e) => {
                let id = e.target.parentElement.parentElement.getAttribute("id")
                fetch(`../../backend/prijava.php?id=${id}`, {
                    method: "DELETE"
                })
                .then(() => {window.location.reload()})
            })
        }

        let prihvatiBtns = document.querySelectorAll(".blueBtn")
        for (let i = 0; i < prihvatiBtns.length; i++) {
            prihvatiBtns[i].addEventListener("click", (e) => {
                let id = e.target.parentElement.parentElement.getAttribute("id")
                fetch("../../backend/prijava.php", {
                    method: "POST",
                    body: JSON.stringify({"prihvacenId": id})
                })
                .then(response => response.json())
                .then(() => {window.location.reload()})
            })
        }
    </script>
</body>
</html>