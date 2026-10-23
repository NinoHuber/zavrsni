class SiteHeader extends HTMLElement {
  connectedCallback() {
    this.innerHTML = `
      <header>
        <h1>Admin</h1>
        <nav>
            <a href="adminPrijave.php">Prijave</a>
            <a href="adminStudenti.php">Studenti</a>
            <a href="adminProfesori.php">Profesori</a>
            <a href="adminStudiji.php">Studiji</a>
        </nav>
        <div>
            <a href="../../index.php">Logout</a>
        </div>
    </header>
    `;
  }
}

customElements.define('admin-header', SiteHeader);