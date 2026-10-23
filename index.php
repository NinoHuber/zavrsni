<?php
$db = new mysqli("localhost", "root", "", "zavrsni");
$studijiResult = $db->query("SELECT id, naziv FROM studiji ORDER BY naziv ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zavrsni</title>
    <link rel="stylesheet" href="./css/global.css">
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/buttons.css">
</head>
<body>

<div class="landing-wrapper">
    <div class="container">

        <div class="card choice-card" id="choiceSection">
            <div class="brand-header">
                <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <h2>Predaja završnih radova</h2>
                <p>Odaberite opciju za nastavak</p>
            </div>
            <div class="choice-actions">
                <button id="prijavaBtn" class="blueBtn">Registracija</button>
                <button id="loginBtn" class="outlineBtn">Prijava</button>
            </div>
        </div>

        <div class="card form-card prijava hidden" id="prijavaSection">
            <div class="form-header">
                <h2>Registracija</h2>
                <p>Kreirajte novi korisnički račun</p>
                <div id="prijavaAlert" class="alert" style="display: none; margin-top: 10px; padding: 10px; border-radius: 6px; font-size: 0.875rem;"></div>
            </div>
            <div class="form-grid">
                <div class="input-group">
                    <label for="prijavaIme">Ime i prezime</label>
                    <input type="text" id="prijavaIme" placeholder="Unesite vaše ime">
                </div>
                <div class="input-group">
                    <label for="prijavaEmail">Email adresa</label>
                    <input type="email" id="prijavaEmail" placeholder="primjer@email.com">
                </div>
                <div class="input-group">
                    <label for="prijavaLozinka">Lozinka</label>
                    <input type="password" id="prijavaLozinka" placeholder="••••••••">
                </div>
                <div class="input-group">
                    <label for="prijavaLozinka2">Potvrdite lozinku</label>
                    <input type="password" id="prijavaLozinka2" placeholder="••••••••">
                </div>
                <div class="input-group full-width">
                    <label for="prijavaUloga">Uloga</label>
                    <select name="uloga" id="prijavaUloga" onchange="toggleStudijField()">
                        <option value="3" selected>Student</option>
                        <option value="2">Profesor</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
                <div class="input-group full-width" id="studijWrapper">
                    <label for="prijavaStudij">Studij</label>
                    <select name="studij_id" id="prijavaStudij">
                        <option value="">-- Odaberite studij --</option>
                        <?php if ($studijiResult && $studijiResult->num_rows > 0): ?>
                            <?php while ($studij = $studijiResult->fetch_assoc()): ?>
                                <option value="<?php echo $studij['id']; ?>"><?php echo htmlspecialchars($studij['naziv']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button class="redBtn backBtn">Nazad</button>
                <button class="blueBtn" id="prijavaSubmit">Registriraj se</button>
            </div>
        </div>

        <div class="card form-card login hidden" id="loginSection">
            <div class="form-header">
                <h2>Prijava</h2>
                <p>Unesite vaše podatke za pristup</p>
            </div>
            <div class="form-grid">
                <div class="input-group full-width">
                    <label for="loginEmail">Email adresa</label>
                    <input type="email" id="loginEmail" placeholder="primjer@email.com">
                </div>
                <div class="input-group full-width">
                    <label for="loginPassword">Lozinka</label>
                    <input type="password" id="loginPassword" placeholder="••••••••">
                </div>
            </div>
            <div class="form-actions">
                <button class="redBtn backBtn">Nazad</button>
                <button class="blueBtn" id="loginSubmit">Prijavi se</button>
            </div>
        </div>
    </div>
</div>

    <script>
        function toggleStudijField() {
            const ulogaSelect = document.getElementById('prijavaUloga');
            const studijWrapper = document.getElementById('studijWrapper');
            const studijSelect = document.getElementById('prijavaStudij');

            if (ulogaSelect.value === '3') {
                studijWrapper.style.display = 'flex';
                studijSelect.setAttribute('required', 'required');
            } else {
                studijWrapper.style.display = 'none';
                studijSelect.removeAttribute('required');
                studijSelect.value = '';
            }
        }

        window.addEventListener('DOMContentLoaded', toggleStudijField);

        let buttons = document.querySelectorAll("button")
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener("click", (e) => {
                if(e.target.classList.contains("backBtn") || e.target.id === "prijavaSubmit" || e.target.id === "loginSubmit") return;
                
                document.querySelector(".choice-card").classList.add("hidden")
                document.getElementById('prijavaAlert').style.display = 'none';

                switch(e.target.getAttribute("id")) {
                    case "prijavaBtn": 
                        document.querySelector(".prijava").classList.remove("hidden")
                        break;
                    case "loginBtn": 
                        document.querySelector(".login").classList.remove("hidden")
                        break;
                }
            })
        }

        let redButtons = document.querySelectorAll(".redBtn")
        for (let i = 0; i < redButtons.length; i++) {
            redButtons[i].addEventListener("click", () => {
                document.querySelector(".choice-card").classList.remove("hidden")
                document.getElementById('prijavaAlert').style.display = 'none';
                if(!(document.querySelector(".prijava").classList.contains("hidden"))) {
                    document.querySelector(".prijava").classList.add("hidden")
                }
                if(!(document.querySelector(".login").classList.contains("hidden"))) {
                    document.querySelector(".login").classList.add("hidden")
                }
            })
        }
        
        document.querySelector("#prijavaSubmit").addEventListener("click", () => {
            const alertBox = document.getElementById('prijavaAlert');
            alertBox.style.display = 'none';

            if(!verifyPassword()) {
                return
            }

            const ulogaVal = document.getElementById('prijavaUloga').value;
            let prijava = {
                "ime": document.querySelector("#prijavaIme").value,
                "email": document.querySelector("#prijavaEmail").value,
                "lozinka": document.querySelector("#prijavaLozinka").value,
                "uloga": ulogaVal,
                "studij_id": ulogaVal === '3' ? document.querySelector("#prijavaStudij").value : null
            }

            fetch("./backend/prijava.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(prijava)
            })
            .then(response => response.json())
            .then(data => {
                alertBox.style.display = 'block';
                if(data.greska) {
                    alertBox.style.backgroundColor = '#fee2e2';
                    alertBox.style.color = '#991b1b';
                    alertBox.textContent = data.greska;
                } else if(data.poruka) {
                    alertBox.style.backgroundColor = '#d1fae5';
                    alertBox.style.color = '#065f46';
                    alertBox.textContent = data.poruka;
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                alertBox.style.display = 'block';
                alertBox.style.backgroundColor = '#fee2e2';
                alertBox.style.color = '#991b1b';
                alertBox.textContent = "Došlo je do pogreške u komunikaciji s poslužiteljem.";
            });
        })

        function verifyPassword() {
            const alertBox = document.getElementById('prijavaAlert');
            if(!(document.querySelector("#prijavaLozinka").value === document.querySelector("#prijavaLozinka2").value)) {
                alertBox.style.display = 'block';
                alertBox.style.backgroundColor = '#fee2e2';
                alertBox.style.color = '#991b1b';
                alertBox.textContent = "Lozinke nisu iste";
                return false
            }
            if(document.querySelector("#prijavaLozinka").value.length < 8) {
                alertBox.style.display = 'block';
                alertBox.style.backgroundColor = '#fee2e2';
                alertBox.style.color = '#991b1b';
                alertBox.textContent = "Lozinka mora imati barem 8 simbola";
                return false
            }

            return true
        }

        document.querySelector("#loginSubmit").addEventListener("click", () => {
            let login = {
                "email": document.querySelector("#loginEmail").value,
                "password": document.querySelector("#loginPassword").value
            }

            fetch("./backend/login.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(login)
            })
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    console.log(data.error)
                } else {
                    window.location.href = data.path
                }
            })
        })
    </script>
</body>
</html>
<?php 
$db->close(); 
?>