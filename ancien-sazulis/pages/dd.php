<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
session_start();

echo '<pre style="background:#222;color:#fff;padding:1em;">';
echo '</pre>';
?>
<?php include '../security_headers.php'; ?>
<?php include_once __DIR__.'/../securite-maximale.php'; ?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <title>Dashboard Admin | Sazulis</title>
    <meta name="description" content="Dashboard administrateur Sazulis : gérez utilisateurs, commandes, projets et statistiques dans un espace sécurisé et moderne." />
    <meta name="keywords" content="dashboard, admin, sazulis, gestion, utilisateurs, commandes, statistiques, sécurité, administration, web" />
    <link rel="icon" type="image/x-icon" href="../assets/img/sazulis-ico.ico" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
      :root {
        --primary-gold: #1976d2;
        --secondary-gold: #2196f3;
        --light-cream: #e3f2fd;
        --warm-white: #f8faff;
        --dark-brown: #0d47a1;
        --medium-brown: #1565c0;
        --gradient-primary: linear-gradient(
          135deg,
          var(--primary-gold) 0%,
          var(--secondary-gold) 60%,
          #42a5f5 100%
        );
        --gradient-hero: linear-gradient(
          135deg,
          #f8faff 0%,
          #e3f2fd 50%,
          #e8eaf6 100%
        );
        --shadow-premium: 0 20px 60px rgba(25, 118, 210, 0.15);
        --shadow-hover: 0 30px 80px rgba(25, 118, 210, 0.25);
        --success-green: #4caf50;
        --error-red: #f44336;
        --info-blue: #03a9f4;
        --warning-orange: #ff9800;
        --purple-accent: #673ab7;
        --sidebar-width: 280px;
        --header-height: 80px;
      }
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: var(--dark-brown);
        background: var(--warm-white);
        overflow-x: hidden;
      }
      .dashboard-container {
        display: flex;
        min-height: 100vh;
      }
      .dashboard-sidebar {
        width: var(--sidebar-width);
        background: white;
        box-shadow: 4px 0 20px rgba(25, 118, 210, 0.1);
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        transition: all 0.3s ease;
      }
      .sidebar-header {
        background: var(--gradient-primary);
        padding: 2rem;
        text-align: center;
        color: white;
      }
      .admin-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem;
        border: 3px solid rgba(255, 255, 255, 0.3);
      }
      .admin-name {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
      }
      .admin-type {
        font-size: 0.9rem;
        opacity: 0.9;
      }
      .sidebar-nav {
        padding: 2rem 0;
      }
      .nav-item {
        display: block;
        padding: 1rem 2rem;
        color: var(--dark-brown);
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
      }
      .nav-item:hover,
      .nav-item.active {
        background: var(--light-cream);
        border-left-color: var(--primary-gold);
        color: var(--primary-gold);
      }
      .nav-item i {
        width: 20px;
        margin-right: 1rem;
      }
      .dashboard-main {
        flex: 1;
        margin-left: var(--sidebar-width);
        background: var(--warm-white);
      }
      .dashboard-header {
        background: white;
        padding: 1.5rem 2rem;
        box-shadow: 0 2px 10px rgba(25, 118, 210, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
      }
      .header-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dark-brown);
      }
      .header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
      }
      .notification-btn {
        position: relative;
        background: var(--light-cream);
        border: none;
        padding: 0.8rem;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
      }
      .notification-btn:hover {
        background: var(--secondary-gold);
        color: white;
      }
      .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--error-red);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .dashboard-content {
        padding: 2rem;
      }
      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
      }
      .stat-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: var(--shadow-premium);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
      }
      .stat-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--gradient-primary);
      }
      .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
      }
      .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
      }
      .stat-title {
        font-size: 0.9rem;
        color: var(--medium-brown);
        font-weight: 600;
      }
      .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
      }
      .stat-value {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--dark-brown);
        margin-bottom: 0.5rem;
      }
      .stat-change {
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
      }
      .stat-change.positive {
        color: var(--success-green);
      }
      .stat-change.negative {
        color: var(--error-red);
      }
      .section-card {
        background: white;
        border-radius: 25px;
        padding: 2.5rem;
        box-shadow: var(--shadow-premium);
        margin-bottom: 2rem;
      }
      .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--light-cream);
      }
      .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-brown);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .filter-tabs {
        display: flex;
        gap: 0.5rem;
      }
      .filter-tab {
        background: var(--light-cream);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        color: var(--dark-brown);
      }
      .filter-tab.active {
        background: var(--gradient-primary);
        color: white;
      }
      .admin-btn {
        background: var(--gradient-primary);
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
      }
      .admin-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(25, 118, 210, 0.3);
      }
      .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(25, 118, 210, 0.1);
      }
      .data-table th {
        background: var(--gradient-primary);
        color: white;
        padding: 1.2rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      .data-table td {
        padding: 1.2rem;
        border-bottom: 1px solid var(--light-cream);
      }
      .data-table tr:hover {
        background: rgba(25, 118, 210, 0.05);
      }
      .action-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 600;
        margin: 0 0.25rem;
        transition: all 0.3s ease;
      }
      .btn-edit {
        background: var(--warning-orange);
        color: white;
      }
      .btn-delete {
        background: var(--error-red);
        color: white;
      }
      .btn-view {
        background: var(--info-blue);
        color: white;
      }
      .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      }
      .form-group {
        margin-bottom: 1.5rem;
      }
      .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--dark-brown);
        font-weight: 600;
        font-size: 0.9rem;
      }
      .form-group input,
      .form-group select,
      .form-group textarea {
        width: 100%;
        padding: 1rem;
        border: 2px solid var(--light-cream);
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
      }
      .form-group input:focus,
      .form-group select:focus,
      .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
      }
      .admin-section {
        display: none;
      }
      .admin-section.active {
        display: block;
      }
      .alert {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        margin-bottom: 2rem;
        display: none;
      }
      .alert.show {
        display: block;
      }
      .alert-success {
        background: #d3f9d8;
        color: #2b8a3e;
        border: 2px solid #51cf66;
      }
      .alert-error {
        background: #ffe3e3;
        color: #c92a2a;
        border: 2px solid #ff6b6b;
      }
      .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
      }
      .badge-danger {
        background: #ffe3e3;
        color: #c92a2a;
      }
      .badge-success {
        background: #d3f9d8;
        color: #2b8a3e;
      }
      .badge-warning {
        background: #fff3cd;
        color: #856404;
      }
      .search-box {
        width: 100%;
        padding: 1rem 1.5rem;
        border: 2px solid var(--light-cream);
        border-radius: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
      }
      .search-box:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
      }
      .filter-section {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
      }
      .filter-select {
        padding: 0.8rem 1.2rem;
        border: 2px solid var(--light-cream);
        border-radius: 12px;
        font-weight: 600;
        color: var(--dark-brown);
        cursor: pointer;
        transition: all 0.3s ease;
      }
      .filter-select:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
      }
      .role-select,
      .status-select {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        border: 2px solid;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
      }
      .role-select:hover:not(:disabled),
      .status-select:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }
      .role-select:disabled,
      .status-select:disabled {
        cursor: not-allowed;
        opacity: 0.6;
      }
      .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
      }
      .switch input {
        opacity: 0;
        width: 0;
        height: 0;
      }
      .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 34px;
      }
      .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
      }
      input:checked + .slider {
        background-color: #4caf50;
      }
      input:checked + .slider:before {
        transform: translateX(26px);
      }
      @keyframes slideIn {
        from {
          transform: translateX(400px);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }
      @keyframes slideOut {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(400px);
          opacity: 0;
        }
      }
      @media (max-width: 768px) {
        .dashboard-main {
          margin-left: 0;
        }
        .dashboard-sidebar {
          transform: translateX(-100%);
        }
        .stats-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>
  <body>
  <noscript>
    <div style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:#111;color:#fff;display:flex;align-items:center;justify-content:center;z-index:99999;font-size:1.5em;text-align:center;">
      🚫 JavaScript est désactivé.<br>
      Pour accéder à ce site, veuillez activer JavaScript dans votre navigateur.<br>
      <a href="https://www.enable-javascript.com/fr/" style="color:#0ff;text-decoration:underline;" target="_blank">Comment activer JavaScript ?</a>
    </div>
  </noscript>
    <div class="dashboard-container">
      <aside class="dashboard-sidebar">
        <div class="sidebar-header">
          <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
          <div class="admin-name">Administrateur Sazulis</div>
          <div class="admin-type">Accès Complet • Version Pro</div>
        </div>
        <nav class="sidebar-nav">
          <a href="#" class="nav-item active" data-section="dashboard"
            ><i class="fas fa-chart-line"></i>Tableau de bord</a
          >
          <a href="#" class="nav-item" data-section="security-unban"
            ><i class="fas fa-shield-alt"></i>Sécurité & Débannissement</a
          >
          <a href="#" class="nav-item" data-section="users"
            ><i class="fas fa-users"></i>Gestion Utilisateurs</a
          >
          <a href="#" class="nav-item" data-section="projects"
            ><i class="fas fa-tasks"></i>Gestion Projets</a
          >
          <a href="#" class="nav-item" data-section="invoices"
            ><i class="fas fa-file-invoice-dollar"></i>Factures & Workflow</a
          >
          <a href="#" class="nav-item" data-section="completed-projects"
            ><i class="fas fa-archive"></i>Projets Terminés + Factures</a
          >
          <a href="#" class="nav-item" data-section="analytics"
            ><i class="fas fa-chart-bar"></i>Analytics Avancés</a
          >
          <a href="#" class="nav-item" data-section="settings"
            ><i class="fas fa-cog"></i>Paramètres Site</a
          >
          <a href="#" class="nav-item" data-section="reports"
            ><i class="fas fa-file-alt"></i>Rapports</a
          >
        </nav>
      </aside>
      <main class="dashboard-main">
        <header class="dashboard-header">
          <h1 class="header-title">
            🔧 Administration Sazulis - Interface Pro
          </h1>
          <div class="header-actions">
            <button class="notification-btn">
              <i class="fas fa-bell"></i
              ><span class="notification-badge">3</span>
            </button>
            <button class="admin-btn">
              <i class="fas fa-plus"></i>Nouveau
            </button>
            <button
              class="admin-btn"
              id="logoutBtn"
              style="background: var(--error-red); color: white"
            >
              <i class="fas fa-sign-out-alt"></i>Déconnexion
            </button>
          </div>
        </header>
        <div class="dashboard-content">
          <div id="dashboard" class="admin-section active">
            <div class="stats-grid">
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">Utilisateurs Total</div>
                  <div class="stat-icon" style="background: var(--info-blue)">
                    <i class="fas fa-users"></i>
                  </div>
                </div>
                <div class="stat-value" id="totalUsers">1,347</div>
                <div class="stat-change positive">
                  <i class="fas fa-arrow-up"></i>+12% ce mois
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">Commandes</div>
                  <div
                    class="stat-icon"
                    style="background: var(--success-green)"
                  >
                    <i class="fas fa-shopping-cart"></i>
                  </div>
                </div>
                <div class="stat-value" id="monthlyOrders">289</div>
                <div class="stat-change positive">
                  <i class="fas fa-arrow-up"></i>+8% ce mois
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">Revenus</div>
                  <div
                    class="stat-icon"
                    style="background: var(--warning-orange)"
                  >
                    <i class="fas fa-euro-sign"></i>
                  </div>
                </div>
                <div class="stat-value" id="totalRevenue">€47,580</div>
                <div class="stat-change positive">
                  <i class="fas fa-arrow-up"></i>+24% ce mois
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">Produits Actifs</div>
                  <div
                    class="stat-icon"
                    style="background: var(--purple-accent)"
                  >
                    <i class="fas fa-box"></i>
                  </div>
                </div>
                <div class="stat-value" id="activeProducts">156</div>
                <div class="stat-change positive">
                  <i class="fas fa-arrow-up"></i>+3 ce mois
                </div>
              </div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-clock"></i>Activité Récente
                </h2>
                <div class="filter-tabs">
                  <button class="filter-tab active">Toutes</button
                  ><button class="filter-tab">Commandes</button
                  ><button class="filter-tab">Utilisateurs</button>
                </div>
              </div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Date & Heure</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Type</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>04/01/2025 10:30</td>
                    <td>Marie Dubois</td>
                    <td>Nouvelle commande #1347</td>
                    <td>
                      <span
                        style="color: var(--success-green); font-weight: 600"
                        >Commande</span
                      >
                    </td>
                    <td>
                      <span
                        style="color: var(--success-green); font-weight: 600"
                        >✓ Confirmée</span
                      >
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div id="security-unban" class="admin-section">
            <div id="unbanAlertContainer"></div>
            <div class="stats-grid">
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">Utilisateurs Bannis</div>
                  <div class="stat-icon" style="background: var(--error-red)">
                    <i class="fas fa-user-slash"></i>
                  </div>
                </div>
                <div class="stat-value" id="statBannedUsers">0</div>
                <div class="stat-change" style="color: var(--medium-brown)">
                  Total des bans actifs
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">Violations Totales</div>
                  <div
                    class="stat-icon"
                    style="background: var(--warning-orange)"
                  >
                    <i class="fas fa-exclamation-triangle"></i>
                  </div>
                </div>
                <div class="stat-value" id="statViolations">0</div>
                <div class="stat-change" style="color: var(--medium-brown)">
                  Activités suspectes détectées
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">IP en Blacklist</div>
                  <div
                    class="stat-icon"
                    style="background: var(--purple-accent)"
                  >
                    <i class="fas fa-ban"></i>
                  </div>
                </div>
                <div class="stat-value" id="statBlacklist">0</div>
                <div class="stat-change" style="color: var(--medium-brown)">
                  Blocages permanents
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-title">Statut Système</div>
                  <div
                    class="stat-icon"
                    style="background: var(--success-green)"
                  >
                    <i class="fas fa-shield-alt"></i>
                  </div>
                </div>
                <div
                  class="stat-value"
                  style="font-size: 2rem; color: var(--success-green)"
                >
                  ✓ ACTIF
                </div>
                <div class="stat-change positive">
                  Toutes protections opérationnelles
                </div>
              </div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-bolt"></i>Actions Rapides
                </h2>
              </div>
              <div style="display: flex; gap: 1rem; flex-wrap: wrap">
                <button
                  class="admin-btn"
                  style="background: var(--success-green)"
                  onclick="unbanAllUsers()"
                >
                  <i class="fas fa-unlock"></i>Débannir Tous</button
                ><button
                  class="admin-btn"
                  style="background: var(--warning-orange)"
                  onclick="clearAllLogs()"
                >
                  <i class="fas fa-trash"></i>Effacer Logs</button
                ><button
                  class="admin-btn"
                  style="background: var(--info-blue)"
                  onclick="exportSecurityData()"
                >
                  <i class="fas fa-download"></i>Exporter Données</button
                ><button class="admin-btn" onclick="refreshSecurityData()">
                  <i class="fas fa-sync"></i>Actualiser
                </button>
              </div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-network-wired"></i>Débannissement par IP
                </h2>
              </div>
              <div class="form-group">
                <label>Adresse IP à débannir</label
                ><input
                  type="text"
                  id="unbanIPInput"
                  placeholder="Ex: 192.168.1.1"
                />
              </div>
              <button
                class="admin-btn"
                style="background: var(--success-green)"
                onclick="unbanByIP()"
              >
                <i class="fas fa-unlock"></i>Débannir cette IP
              </button>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-fingerprint"></i>Débannissement par Empreinte
                  Digitale
                </h2>
              </div>
              <div class="form-group">
                <label>Device ID (Empreinte digitale)</label
                ><input
                  type="text"
                  id="unbanFingerprintInput"
                  placeholder="Ex: abc123def456..."
                />
              </div>
              <button
                class="admin-btn"
                style="background: var(--success-green)"
                onclick="unbanByFingerprint()"
              >
                <i class="fas fa-unlock"></i>Débannir cet Appareil
              </button>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-user-slash"></i>Utilisateurs Bannis
                </h2>
              </div>
              <div class="form-group">
                <input
                  type="text"
                  id="searchBanned"
                  placeholder="🔍 Rechercher par IP, Device ID, raison..."
                  onkeyup="filterBannedTable()"
                />
              </div>
              <div id="bannedUsersTableContainer"></div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-history"></i>Historique des Violations (50
                  dernières)
                </h2>
              </div>
              <div id="violationsLogContainer"></div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-ban"></i>Blacklist IP Permanente
                </h2>
              </div>
              <div id="blacklistTableContainer"></div>
              <div
                style="
                  margin-top: 2rem;
                  padding-top: 2rem;
                  border-top: 2px solid var(--light-cream);
                "
              >
                <h3 style="color: var(--dark-brown); margin-bottom: 1rem">
                  Ajouter une IP à la Blacklist
                </h3>
                <div class="form-group">
                  <input
                    type="text"
                    id="addBlacklistInput"
                    placeholder="Adresse IP"
                  />
                </div>
                <button
                  class="admin-btn"
                  style="background: var(--error-red)"
                  onclick="addToBlacklist()"
                >
                  <i class="fas fa-plus"></i>Ajouter à la Blacklist
                </button>
              </div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-tools"></i>Outils de Débogage
                </h2>
              </div>
              <div style="display: flex; gap: 1rem; flex-wrap: wrap">
                <button class="admin-btn" onclick="viewRawSecurityData()">
                  <i class="fas fa-code"></i>Voir Données Brutes</button
                ><button
                  class="admin-btn"
                  style="background: var(--error-red)"
                  onclick="resetSecuritySystem()"
                >
                  <i class="fas fa-exclamation-triangle"></i>Réinitialiser
                  Système
                </button>
              </div>
              <div id="debugOutputContainer" style="margin-top: 1rem"></div>
            </div>
          </div>
          <div id="users" class="admin-section">
            <div id="roleStatsContainer"></div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-users"></i>Gestion des Utilisateurs & Rôles
                </h2>
                <button
                  class="admin-btn"
                  onclick="roleManager.showAddUserModal()"
                >
                  <i class="fas fa-plus"></i>Nouvel utilisateur
                </button>
              </div>
              <div class="filter-section">
                <input
                  type="text"
                  id="searchUsers"
                  class="search-box"
                  placeholder="🔍 Rechercher par nom, email, ID..."
                  style="flex: 1; min-width: 300px"
                /><select
                  id="roleFilter"
                  class="filter-select"
                  onchange="roleManager.applyFilters()"
                >
                  <option value="">Tous les rôles</option>
                  <option value="client">👥 Clients</option>
                  <option value="admin">👤 Admins</option>
                  <option value="superadmin">🛡️ Superadmins</option></select
                ><select
                  id="statusFilter"
                  class="filter-select"
                  onchange="roleManager.applyFilters()"
                >
                  <option value="">Tous les statuts</option>
                  <option value="active">✓ Actifs</option>
                  <option value="suspended">⏸ Suspendus</option>
                  <option value="banned">🚫 Bannis</option></select
                ><button
                  class="admin-btn"
                  onclick="roleManager.exportUsers()"
                  style="background: var(--success-green)"
                >
                  <i class="fas fa-download"></i>Exporter CSV
                </button>
              </div>
              <div id="usersTableContainer">
                <div style="text-align: center; padding: 3rem; color: #666">
                  <div style="font-size: 3rem; margin-bottom: 1rem">⏳</div>
                  <p>Chargement des utilisateurs...</p>
                </div>
              </div>
            </div>
          </div>
          <div id="projects" class="admin-section">
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-tasks"></i>Gestion des Projets
                </h2>
                <button
                  class="admin-btn"
                  style="background: var(--info-blue); margin-left: 1rem"
                  onclick="SazulisAdminDashboard.addTestProject()"
                >
                  <i class="fas fa-magic"></i>Générer projet de test admin
                </button>
              </div>
              <div id="projectsListContainer">
                <p style="text-align: center; padding: 3rem; color: #666">
                  Les factures acceptées apparaîtront ici avec le suivi du
                  projet.
                </p>
              </div>
            </div>
          </div>
          <div id="invoices" class="admin-section">
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-file-invoice-dollar"></i>Factures & Workflow
                </h2>
                <button
                  class="admin-btn"
                  id="showInvoicesBtn"
                  style="background: var(--info-blue)"
                >
                  <i class="fas fa-file-invoice"></i>Voir les factures à traiter
                </button>
              </div>
              <div id="invoicesListContainer">
                <p style="text-align: center; padding: 3rem; color: #666">
                  Clique sur le bouton ci-dessus pour afficher les factures à
                  traiter.
                </p>
              </div>
            </div>
          </div>
          <div id="analytics" class="admin-section">
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-chart-bar"></i>Analytics Avancés
                </h2>
              </div>
              <div style="margin-bottom: 2rem; text-align: center">
                <button
                  id="seoAuditBtn"
                  class="admin-btn"
                  style="background: var(--info-blue)"
                >
                  <i class="fas fa-search"></i>Analyser le SEO
                </button>
              </div>
              <div id="seoReportContainer">
                <p style="text-align: center; color: #666">
                  Cliquez sur "Analyser le SEO" pour obtenir un rapport sur
                  toutes les pages.
                </p>
              </div>
            </div>
          </div>
          <div id="settings" class="admin-section">
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-gift"></i>Gestion des Thèmes par Fête
                </h2>
                <p style="color: #666">
                  Associez un thème à chaque fête pour personnaliser l'ambiance
                  du site selon la période.
                </p>
              </div>
              <div
                id="fetesThemesList"
                style="
                  display: grid;
                  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                  gap: 2em;
                "
              ></div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-palette"></i>Gestion des Thèmes
                </h2>
                <div style="display: flex; gap: 1rem; align-items: center">
                  <span style="color: var(--medium-brown); font-weight: 600"
                    >Thème actif:
                    <span
                      id="currentThemeName"
                      style="color: var(--primary-gold)"
                      >Bleu Moderne</span
                    ></span
                  ><button
                    class="admin-btn"
                    style="background: var(--error-red)"
                    onclick="themeManager.resetToDefault()"
                  >
                    <i class="fas fa-undo"></i>Réinitialiser
                  </button>
                </div>
              </div>
              <div
                style="
                  background: var(--light-cream);
                  padding: 1.5rem;
                  border-radius: 15px;
                  margin-bottom: 2rem;
                "
              >
                <h3
                  style="
                    color: var(--dark-brown);
                    margin-bottom: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                  "
                >
                  <i class="fas fa-info-circle"></i>Comment ça fonctionne ?
                </h3>
                <p style="color: var(--medium-brown); line-height: 1.6">
                  Le système de thèmes utilise
                  <strong>localStorage</strong> pour sauvegarder vos
                  préférences. Cliquez sur un thème pour l'appliquer
                  instantanément à tout le site. Le thème restera actif même
                  après fermeture du navigateur.
                </p>
              </div>
              <div id="themesGallery"></div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-paint-brush"></i>Créer un Thème Personnalisé
                </h2>
              </div>
              <div
                style="
                  display: grid;
                  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                  gap: 2rem;
                "
              >
                <div class="form-group">
                  <label>Nom du thème</label
                  ><input
                    type="text"
                    id="customThemeName"
                    placeholder="Mon thème personnalisé"
                  />
                </div>
                <div class="form-group">
                  <label>Couleur primaire</label
                  ><input
                    type="color"
                    id="customPrimary"
                    value="#1976d2"
                    style="height: 60px; cursor: pointer"
                  />
                </div>
                <div class="form-group">
                  <label>Couleur secondaire</label
                  ><input
                    type="color"
                    id="customSecondary"
                    value="#2196f3"
                    style="height: 60px; cursor: pointer"
                  />
                </div>
                <div class="form-group">
                  <label>Couleur d'arrière-plan</label
                  ><input
                    type="color"
                    id="customBackground"
                    value="#f8faff"
                    style="height: 60px; cursor: pointer"
                  />
                </div>
                <div class="form-group">
                  <label>Couleur du texte</label
                  ><input
                    type="color"
                    id="customText"
                    value="#0d47a1"
                    style="height: 60px; cursor: pointer"
                  />
                </div>
                <div class="form-group">
                  <label>Couleur claire</label
                  ><input
                    type="color"
                    id="customLight"
                    value="#e3f2fd"
                    style="height: 60px; cursor: pointer"
                  />
                </div>
              </div>
              <div style="display: flex; gap: 1rem; margin-top: 2rem">
                <button
                  class="admin-btn"
                  style="background: var(--success-green)"
                  onclick="themeManager.saveCustomTheme()"
                >
                  <i class="fas fa-save"></i>Sauvegarder et Appliquer</button
                ><button
                  class="admin-btn"
                  onclick="themeManager.previewCustomTheme()"
                >
                  <i class="fas fa-eye"></i>Prévisualiser
                </button>
              </div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-download"></i>Mes Thèmes Personnalisés
                </h2>
              </div>
              <div id="customThemesList"></div>
            </div>
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-tools"></i>Mode Maintenance
                </h2>
              </div>
              <div
                style="
                  background: var(--light-cream);
                  padding: 1.5rem;
                  border-radius: 15px;
                  margin-bottom: 1.5rem;
                "
              >
                <h3
                  style="
                    color: var(--dark-brown);
                    margin-bottom: 0.5rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                  "
                >
                  <i class="fas fa-info-circle"></i>À propos du mode maintenance
                </h3>
                <p
                  style="
                    color: var(--medium-brown);
                    line-height: 1.6;
                    margin: 0;
                  "
                >
                  Lorsque le mode maintenance est activé, une page d'information
                  sera affichée aux visiteurs. Vous pourrez toujours accéder au
                  dashboard admin.
                </p>
              </div>
              <div
                style="
                  display: flex;
                  gap: 2rem;
                  align-items: center;
                  padding: 2rem;
                  background: white;
                  border: 2px solid var(--light-cream);
                  border-radius: 15px;
                "
              >
                <div style="flex: 1">
                  <h3
                    style="
                      color: var(--dark-brown);
                      margin: 0 0 0.5rem 0;
                      font-size: 1.3rem;
                    "
                  >
                    Statut actuel du site
                  </h3>
                  <div
                    id="maintenanceStatus"
                    style="font-size: 1.1rem; font-weight: 600"
                  ></div>
                </div>
                <div>
                  <label
                    style="
                      display: flex;
                      align-items: center;
                      cursor: pointer;
                      gap: 1rem;
                    "
                    ><span style="font-weight: 600; color: var(--dark-brown)"
                      >Mode Maintenance</span
                    >
                    <div style="position: relative; width: 80px; height: 40px">
                      <input
                        type="checkbox"
                        id="maintenanceToggle"
                        style="display: none"
                        onchange="themeManager.toggleMaintenance()"
                      />
                      <div
                        id="maintenanceSwitch"
                        style="
                          width: 80px;
                          height: 40px;
                          background: #e0e0e0;
                          border-radius: 40px;
                          position: relative;
                          transition: all 0.3s ease;
                          cursor: pointer;
                        "
                        onclick="document.getElementById('maintenanceToggle').click()"
                      >
                        <div
                          id="maintenanceSwitchKnob"
                          style="
                            width: 32px;
                            height: 32px;
                            background: white;
                            border-radius: 50%;
                            position: absolute;
                            top: 4px;
                            left: 4px;
                            transition: all 0.3s ease;
                            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                          "
                        ></div>
                      </div></div
                  ></label>
                </div>
              </div>
            </div>
          </div>
          <div
            id="completed-projects"
            class="admin-section"
            style="display: none"
          >
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-archive"></i>Projets Terminés & Factures
                  (URSSAF)
                </h2>
                <p style="color: #666">
                  Liste des projets terminés avec factures, triés par année puis
                  par mois. Totaux pour calcul URSSAF.
                </p>
              </div>
              <div
                id="completedProjectsFilter"
                style="
                  margin-bottom: 1.5em;
                  display: flex;
                  gap: 1em;
                  align-items: center;
                "
              >
                <label>Année :<select id="completedYearSelect"></select></label
                ><label>Mois :<select id="completedMonthSelect"></select></label
                ><button
                  class="admin-btn"
                  id="completedProjectsExportBtn"
                  style="background: #1976d2; color: white"
                >
                  <i class="fas fa-file-export"></i>Exporter CSV
                </button>
              </div>
              <div id="completedProjectsTableContainer">
                <p style="text-align: center; padding: 2em; color: #888">
                  Aucune donnée à afficher pour l'instant.
                </p>
              </div>
            </div>
          </div>
          <div id="reports" class="admin-section">
            <div class="section-card">
              <div class="section-header">
                <h2 class="section-title">
                  <i class="fas fa-file-alt"></i>Rapports
                </h2>
              </div>
              <div
                style="
                  margin-bottom: 2rem;
                  display: flex;
                  gap: 1rem;
                  flex-wrap: wrap;
                  align-items: center;
                "
              >
                <select
                  id="logTypeFilter"
                  style="padding: 0.5rem; border-radius: 8px"
                >
                  <option value="">Tous types</option>
                  <option value="error">Erreur</option>
                  <option value="info">Info</option>
                  <option value="warning">Warning</option></select
                ><input
                  type="text"
                  id="logSearchInput"
                  placeholder="Recherche mot-clé..."
                  style="padding: 0.5rem; border-radius: 8px"
                /><select
                  id="logOrderFilter"
                  style="padding: 0.5rem; border-radius: 8px"
                >
                  <option value="desc">Plus récents</option>
                  <option value="asc">Plus anciens</option></select
                ><button
                  class="admin-btn"
                  id="showLogsBtn"
                  style="background: var(--info-blue)"
                >
                  <i class="fas fa-search"></i>Voir les logs
                </button>
              </div>
              <div id="logsListContainer">
                <p style="text-align: center; padding: 3rem; color: #666">
                  Utilise les filtres ci-dessus pour afficher les logs du site.
                </p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) {
          logoutBtn.addEventListener("click", function () {
            fetch("api/logout.php", {
              method: "POST",
              credentials: "include",
            }).finally(() => {
              window.location.href = "connexion.php";
            });
          });
        }
      });
      class SazulisAdminDashboard {
        static async applyFeteThemeOnSite() {
          let feteId = null;
          const now = new Date();
          const month = now.getMonth() + 1;
          const day = now.getDate();
          if (month === 12 && day >= 10) feteId = "noel";
          else if (month === 1 && day <= 7) feteId = "nouvelan";
          else if (month === 2 && day >= 10 && day <= 20)
            feteId = "saintvalentin";
          else if (month === 10 && day >= 25) feteId = "halloween";
          let fetesThemes = JSON.parse(
            localStorage.getItem("fetesThemes") || "{}"
          );
          const themeId = feteId ? fetesThemes[feteId] : null;
          if (!themeId) return;
          let theme = null;
          try {
            const resp = await fetch(
              "api/themes.php?action=get&id=" + encodeURIComponent(themeId)
            );
            const data = await resp.json();
            if (data.success && data.theme) theme = data.theme;
          } catch (e) {}
          if (!theme) return;
          const root = document.documentElement;
          Object.keys(theme).forEach((k) => {
            if (
              typeof theme[k] === "string" &&
              /^#([0-9a-f]{3}){1,2}$/i.test(theme[k])
            ) {
              root.style.setProperty(`--${k}`, theme[k]);
            }
          });
        }
        static FETES = [
          { id: "noel", nom: "Noël", emoji: "🎄" },
          { id: "nouvelan", nom: "Nouvel An", emoji: "🎆" },
          { id: "paques", nom: "Pâques", emoji: "🐣" },
          { id: "halloween", nom: "Halloween", emoji: "🎃" },
          { id: "ete", nom: "Été", emoji: "🌞" },
          { id: "hiver", nom: "Hiver", emoji: "❄️" },
          { id: "printemps", nom: "Printemps", emoji: "🌸" },
          { id: "rentree", nom: "Rentrée", emoji: "📚" },
          { id: "saintvalentin", nom: "Saint-Valentin", emoji: "💘" },
          { id: "feteMeres", nom: "Fête des Mères", emoji: "👩‍👧‍👦" },
          { id: "fetePeres", nom: "Fête des Pères", emoji: "👨‍👧‍👦" },
          { id: "blackfriday", nom: "Black Friday", emoji: "🛍️" },
          { id: "custom", nom: "Autre...", emoji: "✨" },
        ];
        async renderFetesThemes() {
          const container = document.getElementById("fetesThemesList");
          if (!container) return;
          let fetesThemes = JSON.parse(
            localStorage.getItem("fetesThemes") || "{}"
          );
          let themes = [];
          try {
            const resp = await fetch("api/themes.php?action=list");
            const data = await resp.json();
            if (data.success && Array.isArray(data.themes)) {
              themes = data.themes;
            }
          } catch (e) {}
          container.innerHTML = SazulisAdminDashboard.FETES.map((fete) => {
            const themeId = fetesThemes[fete.id] || "";
            let themeLabel = "Aucun";
            let themeColors = "";
            if (themeId && themes.length) {
              const t = themes.find(
                (t) => t.id == themeId || t.theme_id == themeId
              );
              if (t) {
                themeLabel = t.nom || t.name || t.theme_name || t.id;
                const colorKeys = Object.keys(t).filter(
                  (k) =>
                    typeof t[k] === "string" &&
                    /^#([0-9a-f]{3}){1,2}$/i.test(t[k])
                );
                if (colorKeys.length) {
                  themeColors =
                    `<div style='display:flex;gap:0.5em;flex-wrap:wrap;margin-top:0.5em;'>` +
                    colorKeys
                      .map(
                        (k) =>
                          `<span title='${k}' style='display:flex;align-items:center;gap:0.3em;'><span style='display:inline-block;width:22px;height:22px;border-radius:6px;background:${t[k]};border:1px solid #ccc;'></span><span style='font-size:0.85em;color:#888;'>${k}</span></span>`
                      )
                      .join("") +
                    `</div>`;
                }
              } else themeLabel = themeId;
            }
            const selectId = `select-theme-${fete.id}`;
            return `<div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px #e2d3b2;padding:1.5em;display:flex;flex-direction:column;gap:1em;align-items:flex-start;"><div style="font-size:2em;">${
              fete.emoji
            }</div><div style="font-size:1.2em;font-weight:700;color:#1976d2;">${
              fete.nom
            }</div><div style="color:#666;">Thème associé : <span style="font-weight:600;color:#8c744e;">${themeLabel}</span>${themeColors}</div><label style='font-size:0.98em;'>Changer le thème :<select id='${selectId}' style='margin-left:0.5em;padding:0.4em 1em;border-radius:8px;'><option value=''>Aucun</option>${themes
              .map(
                (t) =>
                  `<option value='${t.id}' ${
                    t.id == themeId ? "selected" : ""
                  }>${t.nom || t.name || t.theme_name || t.id}</option>`
              )
              .join("")}</select></label></div>`;
          }).join("");
          SazulisAdminDashboard.FETES.forEach((fete) => {
            const select = document.getElementById(`select-theme-${fete.id}`);
            if (select) {
              select.onchange = async (e) => {
                let fetesThemes = JSON.parse(
                  localStorage.getItem("fetesThemes") || "{}"
                );
                fetesThemes[fete.id] = select.value;
                localStorage.setItem(
                  "fetesThemes",
                  JSON.stringify(fetesThemes)
                );
                await this.renderFetesThemes();
                this.showNotification(
                  "Thème associé à la fête mis à jour !",
                  "success"
                );
                if (
                  select.value &&
                  window.themeSync &&
                  typeof window.themeSync.applyThemeById === "function"
                ) {
                  window.themeSync.applyThemeById(select.value);
                }
              };
            }
          });
        }
        async selectThemeForFete(feteId, themes) {
          if (!Array.isArray(themes) || !themes.length) {
            try {
              const resp = await fetch("api/themes.php?action=list");
              const data = await resp.json();
              if (data.success && Array.isArray(data.themes)) {
                themes = data.themes;
              }
            } catch (e) {
              themes = [];
            }
          }
          if (!themes.length) {
            alert("Aucun thème disponible dans la base.");
            return;
          }
          const choix = prompt(
            "ID du thème à associer à cette fête :\n" +
              themes
                .map((t) => `${t.id} - ${t.nom || t.name || t.theme_name}`)
                .join("\n")
          );
          if (!choix) return;
          let fetesThemes = JSON.parse(
            localStorage.getItem("fetesThemes") || "{}"
          );
          fetesThemes[feteId] = choix;
          localStorage.setItem("fetesThemes", JSON.stringify(fetesThemes));
          this.renderFetesThemes();
          this.showNotification(
            "Thème associé à la fête mis à jour !",
            "success"
          );
        }
        renderCompletedProjects() {
          const tableContainer = document.getElementById(
            "completedProjectsTableContainer"
          );
          if (!tableContainer) return;
          tableContainer.innerHTML =
            '<p style="text-align:center;padding:2em;color:#888;">Chargement des projets terminés...</p>';
          fetch("api/factures.php", { credentials: "include" })
            .then((r) => r.json())
            .then((data) => {
              if (!data.success) {
                tableContainer.innerHTML =
                  '<div style="color:#f44336;">Erreur API : ' +
                  (data.error || "") +
                  "</div>";
                return;
              }
              const factures = (data.factures || []).filter(
                (f) =>
                  f.status === "completed" ||
                  f.status === "terminé" ||
                  f.status === "termine"
              );
              if (factures.length === 0) {
                tableContainer.innerHTML =
                  '<div style="color:#888;">Aucun projet terminé trouvé.</div>';
                return;
              }
              const grouped = {};
              const yearTotals = {};
              const monthTotals = {};
              const allMonths = [
                "01",
                "02",
                "03",
                "04",
                "05",
                "06",
                "07",
                "08",
                "09",
                "10",
                "11",
                "12",
              ];
              function getYearMonthFromNumFacture(f) {
                if (f.num && /^\d{6}/.test(f.num)) {
                  const n = f.num;
                  const year = "20" + n.substring(0, 2);
                  const month = n.substring(2, 4);
                  const day = n.substring(4, 6);
                  return { year, month, day };
                }
                return null;
              }
              function getValidDate(f) {
                let d = null;
                if (f.date && !isNaN(Date.parse(f.date))) d = new Date(f.date);
                else if (f.created_at && !isNaN(Date.parse(f.created_at)))
                  d = new Date(f.created_at);
                else if (f.updated_at && !isNaN(Date.parse(f.updated_at)))
                  d = new Date(f.updated_at);
                else d = new Date();
                return d;
              }
              factures.forEach((f) => {
                let year, month;
                const fromNum = getYearMonthFromNumFacture(f);
                if (fromNum) {
                  year = fromNum.year;
                  month = fromNum.month;
                } else {
                  const date = getValidDate(f);
                  year = date.getFullYear().toString();
                  month = (date.getMonth() + 1).toString().padStart(2, "0");
                }
                if (!grouped[year]) grouped[year] = {};
                if (!grouped[year][month]) grouped[year][month] = [];
                grouped[year][month].push(f);
                yearTotals[year] =
                  (yearTotals[year] || 0) + parseFloat(f.total || 0);
                const ym = year + "-" + month;
                monthTotals[ym] =
                  (monthTotals[ym] || 0) + parseFloat(f.total || 0);
              });
              let html = '<div style="display:flex;gap:2em;flex-wrap:wrap;">';
              html += '<div style="min-width:320px;max-width:420px;">';
              html += '<h3 style="color:#1976d2;">Années & Mois</h3>';
              html += '<ul style="list-style:none;padding-left:0;">';
              const years = Object.keys(grouped)
                .filter((y) => !isNaN(Number(y)))
                .sort((a, b) => b - a);
              years.forEach((year) => {
                html += `<li style='margin-bottom:0.7em;'><span class='folder-year' data-year='${year}' style='font-weight:700;color:#1976d2;'><i class="fas fa-folder"></i> ${year} <span style="color:#888;font-weight:400;">(${yearTotals[
                  year
                ].toFixed(
                  2
                )} €)</span></span><ul style='margin-left:1.5em;' id='months-${year}'>`;
                allMonths.forEach((month) => {
                  const ym = year + "-" + month;
                  html += `<li style='margin-bottom:0.4em;'><span class='folder-month' data-year='${year}' data-month='${month}' style='cursor:pointer;color:#1976d2;'><i class="fas fa-folder-open"></i> ${month} <span style="color:#888;font-weight:400;">(${
                    monthTotals[ym] ? monthTotals[ym].toFixed(2) : "0.00"
                  } €)</span></span></li>`;
                });
                html += "</ul></li>";
              });
              html += "</ul>";
              html += "</div>";
              html +=
                '<div id="facturesDetailsZone" style="flex:1;min-width:320px;"></div>';
              html += "</div>";
              html +=
                '<div style="margin-top:2em;"><h3 style="color:#1976d2;">Diagramme Chiffre d\'Affaires</h3>';
              html += '<canvas id="caChart" height="120"></canvas></div>';
              tableContainer.innerHTML = html;
              tableContainer
                .querySelectorAll(".folder-month")
                .forEach((mEl) => {
                  mEl.addEventListener("click", (e) => {
                    e.stopPropagation();
                    const y = mEl.dataset.year;
                    const m = mEl.dataset.month;
                    const list =
                      grouped[y] && grouped[y][m] ? grouped[y][m] : [];
                    let details = `<h4 style='color:#1976d2;'>Factures ${m}/${y}</h4>`;
                    if (list.length === 0) {
                      details +=
                        '<div style="color:#888;">Aucune facture pour cette période.</div>';
                    } else {
                      details += `<table class="data-table"><thead><tr><th>Numéro</th><th>Client</th><th>Date</th><th>Total (€)</th><th>Statut</th></tr></thead><tbody>`;
                      list.forEach((f) => {
                        let dateAff = "";
                        const fromNum = getYearMonthFromNumFacture(f);
                        if (fromNum) {
                          dateAff = `${fromNum.day}/${fromNum.month}/${fromNum.year}`;
                        } else {
                          const d = getValidDate(f);
                          dateAff = d.toLocaleDateString("fr-FR");
                        }
                        details += `<tr><td>${
                          f.num ? f.num : "#" + f.id
                        }</td><td>${
                          f.client_nom || f.client || ""
                        }</td><td>${dateAff}</td><td>${parseFloat(
                          f.total || 0
                        ).toFixed(2)}</td><td>${f.status}</td></tr>`;
                      });
                      details += `</tbody></table><div style="margin-top:1em;font-weight:700;">Total pour ${m}/${y} : <span style="color:#1976d2;">${
                        monthTotals[y + "-" + m]
                          ? monthTotals[y + "-" + m].toFixed(2)
                          : "0.00"
                      } €</span></div>`;
                    }
                    tableContainer.querySelector(
                      "#facturesDetailsZone"
                    ).innerHTML = details;
                  });
                });
              if (window.Chart) {
                const ctx = document.getElementById("caChart").getContext("2d");
                const datasets = years.map((year, idx) => ({
                  label: year,
                  data: allMonths.map((m) =>
                    monthTotals[year + "-" + m]
                      ? monthTotals[year + "-" + m]
                      : 0
                  ),
                  backgroundColor: `rgba(${60 + idx * 60},${
                    120 + idx * 30
                  },210,0.2)`,
                  borderColor: `rgba(${60 + idx * 60},${120 + idx * 30},210,1)`,
                  borderWidth: 2,
                  fill: false,
                }));
                new Chart(ctx, {
                  type: "line",
                  data: { labels: allMonths, datasets: datasets },
                  options: {
                    responsive: true,
                    plugins: {
                      legend: { position: "top" },
                      title: {
                        display: true,
                        text: "Comparatif CA par mois et année",
                      },
                    },
                    scales: { y: { beginAtZero: true } },
                  },
                });
              } else {
                tableContainer.querySelector("#caChart").outerHTML =
                  '<div style="color:#f44336;">Chart.js non chargé</div>';
              }
            })
            .catch((err) => {
              tableContainer.innerHTML =
                '<div style="color:#f44336;">Erreur de connexion : ' +
                err.message +
                "</div>";
            });
        }
        constructor() {
          this.currentSection = "dashboard";
          this.init();
        }
        init() {
          this.setupNavigation();
          this.setupNotifications();
          this.loadSecurityStats();
          console.log("🔧 Dashboard Admin Sazulis initialisé");
          if (document.getElementById("fetesThemesList")) {
            this.renderFetesThemes();
          }
        }
        setupNavigation() {
          document.querySelectorAll(".nav-item").forEach((link) => {
            link.addEventListener("click", (e) => {
              e.preventDefault();
              const sectionId = link.getAttribute("data-section");
              this.showSection(sectionId);
              document
                .querySelectorAll(".nav-item")
                .forEach((l) => l.classList.remove("active"));
              link.classList.add("active");
            });
          });
        }
        showSection(sectionId) {
          document.querySelectorAll(".admin-section").forEach((section) => {
            section.classList.remove("active");
            section.style.display = "none";
          });
          const targetSection = document.getElementById(sectionId);
          if (targetSection) {
            targetSection.classList.add("active");
            targetSection.style.display = "";
            this.currentSection = sectionId;
            if (sectionId === "security-unban") {
              refreshSecurityData();
            } else if (sectionId === "users" && window.roleManager) {
              roleManager.loadUsers();
            } else if (sectionId === "settings" && window.themeManager) {
              themeManager.renderThemesGallery();
            } else if (sectionId === "projects") {
              this.renderProjects();
            } else if (sectionId === "completed-projects") {
              if (typeof this.renderCompletedProjects === "function") {
                this.renderCompletedProjects();
              }
            }
          }
        }
        setupNotifications() {
          if (!document.getElementById("notifications-container")) {
            const container = document.createElement("div");
            container.id = "notifications-container";
            container.style.cssText =
              "position:fixed;top:20px;right:20px;z-index:10000;max-width:400px;";
            document.body.appendChild(container);
          }
        }
        showNotification(message, type = "info") {
          const container = document.getElementById("notifications-container");
          const notification = document.createElement("div");
          const colors = {
            success: "#4caf50",
            error: "#f44336",
            warning: "#ff9800",
            info: "#2196f3",
          };
          notification.style.cssText = `background:${colors[type]};color:white;padding:1rem 1.5rem;border-radius:10px;margin-bottom:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);transform:translateX(100%);transition:transform 0.3s ease;font-weight:600;`;
          notification.textContent = message;
          container.appendChild(notification);
          setTimeout(
            () => (notification.style.transform = "translateX(0)"),
            100
          );
          setTimeout(() => {
            notification.style.transform = "translateX(100%)";
            setTimeout(() => {
              if (notification.parentNode)
                notification.parentNode.removeChild(notification);
            }, 300);
          }, 4000);
        }
        loadSecurityStats() {
          const blockedUntil = localStorage.getItem(
            "sazulis_security_blockedUntil"
          );
          const blockedPermanently = localStorage.getItem(
            "sazulis_security_blockedPermanently"
          );
          const activities = JSON.parse(
            localStorage.getItem("sazulis_security_suspiciousActivities") ||
              "[]"
          );
          const blacklist = JSON.parse(
            localStorage.getItem("sazulis_security_ipBlacklist") || "[]"
          );
          const statBanned = document.getElementById("statBannedUsers");
          const statViolations = document.getElementById("statViolations");
          const statBlacklistCount = document.getElementById("statBlacklist");
          if (statBanned)
            statBanned.textContent =
              blockedUntil || blockedPermanently ? "1" : "0";
          if (statViolations) statViolations.textContent = activities.length;
          if (statBlacklistCount)
            statBlacklistCount.textContent = blacklist.length;
        }
        renderProjects() {
          const container = document.getElementById("projectsListContainer");
          if (!container) return;
          container.innerHTML =
            '<div style="font-size:1.2em;color:#1976d2;font-weight:600;">Chargement des projets...</div>';
          fetch("api/factures.php", { credentials: "include" })
            .then((r) => r.json())
            .then((data) => {



              if (!data.success) {
                container.innerHTML =
                  '<div style="color:#f44336;">Erreur API : ' +
                  (data.error || "") +
                  "</div>";
                return;
              }
              const factures = data.factures || [];
              if (factures.length === 0) {
                container.innerHTML =
                  '<div style="color:#888;">Aucun projet/facture trouvé.</div>';
                return;
              }
              container.innerHTML = "";
              factures.forEach((facture) => {
                const total = facture.total ? parseFloat(facture.total) : 0;
                let acompte = 0;
                if (
                  facture.acompte != null &&
                  !isNaN(parseFloat(facture.acompte))
                ) {
                  acompte = parseFloat(facture.acompte);
                } else if (total > 0) {
                  acompte = Math.round(total * 0.2 * 100) / 100;
                }
                acompte = isNaN(acompte) ? 0 : acompte;
                acompte = acompte.toFixed(2);
                const reste = (total - parseFloat(acompte)).toFixed(2);
                const itemsHtml = (facture.items || [])
                  .map(
                    (item) =>
                      `<li>${item.nom} (${item.quantite} × ${item.prix_unitaire} €) = ${item.total_ligne} €</li>`
                  )
                  .join("");
                let status = facture.status || "pending";
                let acompteReceived = facture.acompteReceived || false;
                let contractReceived = facture.contractReceived || false;
                let soldePaid = facture.solde_paid || false;
                if (typeof facture.acompte_paid !== "undefined")
                  acompteReceived = !!facture.acompte_paid;
                if (typeof facture.contrat_signed !== "undefined")
                  contractReceived = !!facture.contrat_signed;
                if (typeof facture.solde_paid !== "undefined")
                  soldePaid = !!facture.solde_paid;
                let localProjects = JSON.parse(
                  localStorage.getItem("sazulis_projects") || "[]"
                );
                let local = localProjects.find((p) => p.id == facture.id);
                if (local) {
                  status = local.status || status;
                  acompteReceived = local.acompteReceived || acompteReceived;
                  contractReceived = local.contractReceived || contractReceived;
                  soldePaid = local.soldePaid || soldePaid;
                }
                let buttonsHtml = "";
                if (
                  status === "pending" ||
                  status === "en_attente" ||
                  status === "nouveau"
                ) {
                  buttonsHtml = `<button class="admin-btn" style="background:#4caf50;color:white;" onclick="adminDash.actionProjet(${facture.id},'accepter')"><i class='fas fa-check'></i> Accepter</button><button class="admin-btn" style="background:#f44336;color:white;" onclick="adminDash.actionProjet(${facture.id},'refuser')"><i class='fas fa-times'></i> Refuser</button><button class="admin-btn" style="background:#888;color:white;" onclick="adminDash.actionProjet(${facture.id},'supprimer')"><i class='fas fa-trash'></i> Supprimer</button>`;
                } else if (
                  status === "accepted" ||
                  status === "accepter" ||
                  status === "accepte"
                ) {
                  if (!acompteReceived || !contractReceived) {
                    if (!acompteReceived) {
                      buttonsHtml += `<button class="admin-btn" style="background:#1976d2;color:white;" onclick="adminDash.actionProjet(${facture.id},'payer_acompte')"><i class='fas fa-euro-sign'></i> Acompte reçu</button>`;
                    } else {
                      buttonsHtml += `<button class="admin-btn" style="background:#aaa;color:white;" disabled><i class='fas fa-euro-sign'></i> Acompte reçu ✓</button>`;
                    }
                    if (!contractReceived) {
                      buttonsHtml += `<button class="admin-btn" style="background:#2196f3;color:white;" onclick="adminDash.actionProjet(${facture.id},'signer_contrat')"><i class='fas fa-file-signature'></i> Contrat reçu</button>`;
                    } else {
                      buttonsHtml += `<button class="admin-btn" style="background:#aaa;color:white;" disabled><i class='fas fa-file-signature'></i> Contrat reçu ✓</button>`;
                    }
                  }
                  if (acompteReceived && contractReceived) {
                    buttonsHtml = `<button class="admin-btn" style="background:#4caf50;color:white;" onclick="adminDash.actionProjet(${facture.id},'start_development')"><i class='fas fa-play'></i> Démarrer développement</button>`;
                  }
                } else if (status === "active") {
                  buttonsHtml = `<button class="admin-btn" style="background:#1976d2;color:white;" onclick="adminDash.actionProjet(${facture.id},'complete_development')"><i class='fas fa-flag-checkered'></i> Développement terminé</button>`;
                } else if (status === "refused" || status === "refuser") {
                  buttonsHtml = `<span style='color:#f44336;font-weight:600;'>Projet refusé</span>`;
                } else if (status === "completed") {
                  if (!soldePaid) {
                    buttonsHtml = `<button class="admin-btn" style="background:#4caf50;color:white;" onclick="adminDash.actionProjet(${facture.id},'solde_recu')"><i class='fas fa-euro-sign'></i> Solde reçu</button>`;
                  } else {
                    buttonsHtml = `<div style='display:flex;align-items:center;gap:0.5rem;'><span style='color:#4caf50;font-weight:600;font-size:1.1em;'>✓ Solde reçu</span><span style='background:#d3f9d8;color:#2b8a3e;padding:0.5rem 1rem;border-radius:10px;font-weight:600;'>Projet terminé</span></div>`;
                  }
                }
                container.innerHTML += `<div class="facture-box section-card" style="margin-bottom:2em;"><h3 style="margin-bottom:0.5em;">Projet #${
                  facture.id
                } - ${
                  facture.client_nom
                }</h3><div style="margin-bottom:0.5em;">Email client : <b>${
                  facture.client_email
                }</b></div><div style="margin-bottom:0.5em;">Total : <b>${total.toFixed(
                  2
                )} €</b></div><div style="margin-bottom:0.5em;">Acompte (20%) : <b style="color:#1976d2;">${acompte} €</b></div><div style="margin-bottom:0.5em;">Reste à payer : <b style="color:#f44336;">${reste} €</b></div><ul style="margin-top:1em;">${itemsHtml}</ul><div style="margin-top:1em;display:flex;gap:1em;flex-wrap:wrap;">${buttonsHtml}</div></div>`;
              });
            })
            .catch((err) => {
              container.innerHTML =
                '<div style="color:#f44336;">Erreur de connexion : ' +
                err.message +
                "</div>";
            });
        }
        actionProjet(id, action) {
          if (
            action === "supprimer" &&
            !confirm("Supprimer ce projet ? Cette action est irréversible.")
          )
            return;
          fetch("api/projet_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: action, id: id }),
          })
            .then((res) => res.json())
            .then((result) => {
              if (result.success) {
                const messages = {
                  accepter: "Projet accepté !",
                  refuser: "Projet refusé !",
                  supprimer: "Projet supprimé !",
                  payer_acompte: "✅ Acompte marqué comme reçu !",
                  signer_contrat: "✅ Contrat marqué comme reçu !",
                  complete_development: "🏁 Développement terminé !",
                  start_development: "🚀 Développement démarré !",
                  solde_recu: "💶 Solde marqué comme réglé !",
                };
                const type =
                  action === "refuser" || action === "supprimer"
                    ? "error"
                    : "success";
                this.showNotification(
                  messages[action] || "Action effectuée !",
                  type
                );
                this.renderProjects();
              } else {
                this.showNotification(
                  result.error || "Erreur lors de l'action",
                  "error"
                );
              }
            })
            .catch(() => {
              this.showNotification("Erreur de connexion au serveur", "error");
            });
        }
        static addTestProject() {
          const demoProject = {
            id: Date.now(),
            name: "Site E-commerce Premium",
            client: "Jean Dupont",
            clientEmail: "jean.dupont@example.com",
            budget: 12500,
            acompte: Math.round(12500 * 0.2),
            acomptePaid: false,
            acompteReceived: false,
            contractSigned: false,
            contractReceived: false,
            soldePaid: false,
            status: "pending",
            description:
              "Développement d'un site e-commerce premium avec paiement en ligne, gestion de catalogue, et optimisation SEO.",
            createdAt: new Date().toISOString(),
            date: new Date().toLocaleDateString("fr-FR"),
            montant: 12500,
            projet: "Site E-commerce Premium",
            panier: ["Catalogue", "Paiement", "SEO"],
          };
          let allProjects = JSON.parse(
            localStorage.getItem("sazulis_projects") || "[]"
          );
          allProjects.push(demoProject);
          localStorage.setItem("sazulis_projects", JSON.stringify(allProjects));
          if (
            window.adminDash &&
            typeof window.adminDash.renderProjects === "function"
          ) {
            window.adminDash.renderProjects();
            window.adminDash.showNotification(
              "Projet de test admin généré !",
              "success"
            );
          }
        }
      }
      async function loadSecurityStats() {
        const blockedUntil = localStorage.getItem(
          "sazulis_security_blockedUntil"
        );
        const blockedPermanently = localStorage.getItem(
          "sazulis_security_blockedPermanently"
        );
        const activities = JSON.parse(
          localStorage.getItem("sazulis_security_suspiciousActivities") || "[]"
        );
        const blacklist = JSON.parse(
          localStorage.getItem("sazulis_security_ipBlacklist") || "[]"
        );
        document.getElementById("statViolations").textContent =
          activities.length;
        document.getElementById("statBlacklist").textContent = blacklist.length;
        try {
          const resp = await fetch("api/security.php?endpoint=get-banned-ips", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: "{}",
            credentials: "include",
          });
          const data = await resp.json();
          if (data.success && Array.isArray(data.banned_ips)) {
            document.getElementById("statBannedUsers").textContent =
              data.banned_ips.length;
            renderBannedUsersTable(data.banned_ips);
          } else {
            document.getElementById("statBannedUsers").textContent = "0";
            renderBannedUsersTable([]);
          }
        } catch (e) {
          document.getElementById("statBannedUsers").textContent = "?";
          renderBannedUsersTable([]);
        }
      }
      function renderBannedUsersTable(bans) {
        const container = document.getElementById("bannedUsersTableContainer");
        if (!container) return;
        if (!bans.length) {
          container.innerHTML =
            '<div style="color:#888;text-align:center;padding:2em;">Aucun utilisateur banni actuellement.</div>';
          return;
        }
        let html = `<table class="data-table"><thead><tr><th>IP</th><th>Fin du ban</th><th>Raison</th><th>Action</th></tr></thead><tbody>`;
        for (const ban of bans) {
          html += `<tr><td>${ban.ip}</td><td>${
            ban.ban_until
              ? ban.ban_until.replace("T", " ").substring(0, 19)
              : ""
          }</td><td>${
            ban.reason || ""
          }</td><td><button class='admin-btn' style='background:#10b981;font-size:0.9em;padding:0.3em 0.8em' onclick="unbanIp('${
            ban.ip
          }')">Débannir</button></td></tr>`;
        }
        html += "</tbody></table>";
        container.innerHTML = html;
      }
      async function unbanIp(ip) {
        if (!confirm("Débannir l'IP " + ip + " ?")) return;
        try {
          const resp = await fetch("api/security.php?endpoint=unban-ip", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ip }),
            credentials: "include",
          });
          const data = await resp.json();
          if (data.success) {
            showUnbanAlert("✅ IP " + ip + " débannie avec succès", "success");
            loadSecurityStats();
          } else {
            showUnbanAlert(
              "Erreur : " + (data.error || "Impossible de débannir"),
              "error"
            );
          }
        } catch (e) {
          showUnbanAlert("Erreur réseau", "error");
        }
      }
      function refreshSecurityData() {
        loadSecurityStats();
        showUnbanAlert("🔄 Données actualisées", "success");
      }
      function unbanAllUsers() {
        if (
          confirm(
            "⚠️ ATTENTION !\n\nCela va débannir TOUS les utilisateurs.\n\nÊtes-vous sûr ?"
          )
        ) {
          localStorage.removeItem("sazulis_security_blockedUntil");
          localStorage.removeItem("sazulis_security_blockedPermanently");
          localStorage.removeItem("sazulis_security_blockReason");
          localStorage.removeItem("sazulis_security_failedAttempts");
          localStorage.removeItem("sazulis_security_ipBlacklist");
          showUnbanAlert(
            "✅ Tous les utilisateurs ont été débannis !",
            "success"
          );
          refreshSecurityData();
        }
      }
      function clearAllLogs() {
        if (confirm("⚠️ Effacer tous les logs de sécurité ?")) {
          localStorage.removeItem("sazulis_security_suspiciousActivities");
          localStorage.removeItem("sazulis_security_securityLog");
          showUnbanAlert("✅ Tous les logs ont été effacés", "success");
          refreshSecurityData();
        }
      }
      function exportSecurityData() {
        const data = {
          blockedUntil: localStorage.getItem("sazulis_security_blockedUntil"),
          blockedPermanently: localStorage.getItem(
            "sazulis_security_blockedPermanently"
          ),
          blockReason: localStorage.getItem("sazulis_security_blockReason"),
          currentIP: localStorage.getItem("sazulis_security_currentIP"),
          deviceFingerprint: localStorage.getItem(
            "sazulis_security_deviceFingerprint"
          ),
          suspiciousActivities: JSON.parse(
            localStorage.getItem("sazulis_security_suspiciousActivities") ||
              "[]"
          ),
          blacklist: JSON.parse(
            localStorage.getItem("sazulis_security_ipBlacklist") || "[]"
          ),
          exportDate: new Date().toISOString(),
        };
        const blob = new Blob([JSON.stringify(data, null, 2)], {
          type: "application/json",
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `sazulis-security-${Date.now()}.json`;
        a.click();
        showUnbanAlert("✅ Données exportées avec succès", "success");
      }
      function showUnbanAlert(message, type = "success") {
        const container = document.getElementById("unbanAlertContainer");
        const alertClass = type === "success" ? "alert-success" : "alert-error";
        container.innerHTML = `<div class="alert ${alertClass} show">${message}</div>`;
        setTimeout(() => (container.innerHTML = ""), 5000);
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
      function filterBannedTable() {
        const input = document
          .getElementById("searchBanned")
          .value.toLowerCase();
        const table = document.querySelector(
          "#bannedUsersTableContainer table"
        );
        if (!table) return;
        const rows = table.querySelectorAll("tbody tr");
        rows.forEach((row) => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(input) ? "" : "none";
        });
      }
      function unbanByIP() {
        const ip = document.getElementById("unbanIPInput").value.trim();
        if (!ip) {
          showUnbanAlert("❌ Veuillez entrer une adresse IP", "error");
          return;
        }
        unbanIp(ip);
      }
      function unbanByFingerprint() {
        const fingerprint = document
          .getElementById("unbanFingerprintInput")
          .value.trim();
        if (!fingerprint) {
          showUnbanAlert("❌ Veuillez entrer une empreinte digitale", "error");
          return;
        }
        const currentFingerprint = localStorage.getItem(
          "sazulis_security_deviceFingerprint"
        );
        if (currentFingerprint === fingerprint) {
          localStorage.removeItem("sazulis_security_blockedUntil");
          localStorage.removeItem("sazulis_security_blockedPermanently");
          localStorage.removeItem("sazulis_security_blockReason");
          showUnbanAlert("✅ Appareil débanni avec succès", "success");
          refreshSecurityData();
        } else {
          showUnbanAlert("❌ Empreinte digitale non trouvée", "error");
        }
      }
      function addToBlacklist() {
        const ip = document.getElementById("addBlacklistInput").value.trim();
        if (!ip) {
          showUnbanAlert("❌ Veuillez entrer une adresse IP", "error");
          return;
        }
        let blacklist = JSON.parse(
          localStorage.getItem("sazulis_security_ipBlacklist") || "[]"
        );
        if (blacklist.includes(ip)) {
          showUnbanAlert("❌ Cette IP est déjà dans la blacklist", "error");
          return;
        }
        blacklist.push(ip);
        localStorage.setItem(
          "sazulis_security_ipBlacklist",
          JSON.stringify(blacklist)
        );
        showUnbanAlert(`✅ IP ${ip} ajoutée à la blacklist`, "success");
        document.getElementById("addBlacklistInput").value = "";
        renderBlacklist();
      }
      function renderBlacklist() {
        const container = document.getElementById("blacklistTableContainer");
        if (!container) return;
        const blacklist = JSON.parse(
          localStorage.getItem("sazulis_security_ipBlacklist") || "[]"
        );
        if (blacklist.length === 0) {
          container.innerHTML =
            '<div style="text-align:center;padding:2rem;color:#888;">Aucune IP en blacklist</div>';
          return;
        }
        let html = `<table class="data-table"><thead><tr><th>Adresse IP</th><th>Date d'ajout</th><th>Actions</th></tr></thead><tbody>`;
        blacklist.forEach((ip) => {
          html += `<tr><td><strong>${ip}</strong></td><td>${new Date().toLocaleDateString(
            "fr-FR"
          )}</td><td><button class="action-btn btn-delete" onclick="removeFromBlacklist('${ip}')"><i class="fas fa-trash"></i> Retirer</button></td></tr>`;
        });
        html += "</tbody></table>";
        container.innerHTML = html;
      }
      function removeFromBlacklist(ip) {
        if (!confirm(`Retirer ${ip} de la blacklist ?`)) return;
        let blacklist = JSON.parse(
          localStorage.getItem("sazulis_security_ipBlacklist") || "[]"
        );
        blacklist = blacklist.filter((i) => i !== ip);
        localStorage.setItem(
          "sazulis_security_ipBlacklist",
          JSON.stringify(blacklist)
        );
        showUnbanAlert(`✅ IP ${ip} retirée de la blacklist`, "success");
        renderBlacklist();
      }
      function viewRawSecurityData() {
        const container = document.getElementById("debugOutputContainer");
        const data = {
          blockedUntil: localStorage.getItem("sazulis_security_blockedUntil"),
          blockedPermanently: localStorage.getItem(
            "sazulis_security_blockedPermanently"
          ),
          blockReason: localStorage.getItem("sazulis_security_blockReason"),
          currentIP: localStorage.getItem("sazulis_security_currentIP"),
          deviceFingerprint: localStorage.getItem(
            "sazulis_security_deviceFingerprint"
          ),
          suspiciousActivities: JSON.parse(
            localStorage.getItem("sazulis_security_suspiciousActivities") ||
              "[]"
          ),
          blacklist: JSON.parse(
            localStorage.getItem("sazulis_security_ipBlacklist") || "[]"
          ),
        };
        container.innerHTML = `<div style="background:#f8faff;padding:1.5rem;border-radius:12px;margin-top:1rem;"><h4 style="color:var(--dark-brown);margin-bottom:1rem;">Données Brutes localStorage</h4><pre style="background:white;padding:1rem;border-radius:8px;overflow-x:auto;font-size:0.85rem;">${JSON.stringify(
          data,
          null,
          2
        )}</pre></div>`;
      }
      function resetSecuritySystem() {
        if (
          !confirm(
            "⚠️ DANGER !\n\nCela va RÉINITIALISER COMPLÈTEMENT le système de sécurité.\n\nToutes les données seront perdues !\n\nContinuer ?"
          )
        ) {
          return;
        }
        localStorage.removeItem("sazulis_security_blockedUntil");
        localStorage.removeItem("sazulis_security_blockedPermanently");
        localStorage.removeItem("sazulis_security_blockReason");
        localStorage.removeItem("sazulis_security_currentIP");
        localStorage.removeItem("sazulis_security_deviceFingerprint");
        localStorage.removeItem("sazulis_security_suspiciousActivities");
        localStorage.removeItem("sazulis_security_securityLog");
        localStorage.removeItem("sazulis_security_ipBlacklist");
        localStorage.removeItem("sazulis_security_failedAttempts");
        showUnbanAlert("✅ Système de sécurité réinitialisé", "success");
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      }
      class RoleManager {
        constructor() {
          this.apiUrl = "api/roles.php";
          this.users = [];
          this.stats = {};
          this.init();
        }
        init() {
          this.loadUsers();
          this.setupEventListeners();
          console.log("✅ Gestionnaire de rôles initialisé");
        }
        async loadUsers(search = "", roleFilter = "", statusFilter = "") {
          try {
            const params = new URLSearchParams({
              search,
              role: roleFilter,
              status: statusFilter,
            });
            const response = await fetch(
              `${this.apiUrl}?action=list&${params.toString()}`
            );
            const data = await response.json();
            if (data.success) {
              this.users = data.users;
              this.stats = data.stats;
              this.renderUsers();
              this.renderStats();
            } else {
              this.showNotification("❌ Erreur de chargement", "error");
            }
          } catch (error) {
            console.error("Erreur chargement utilisateurs:", error);
            this.showNotification("❌ Erreur de chargement", "error");
          }
        }
        renderStats() {
          const statsHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-bottom:2rem;"><div style="background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:white;padding:2rem;border-radius:20px;box-shadow:0 8px 25px rgba(59,130,246,0.3);"><div style="font-size:3rem;font-weight:900;margin-bottom:0.5rem;">${
            this.stats.client || 0
          }</div><div style="font-size:1rem;opacity:0.95;font-weight:600;">👥 Clients</div></div><div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:white;padding:2rem;border-radius:20px;box-shadow:0 8px 25px rgba(245,158,11,0.3);"><div style="font-size:3rem;font-weight:900;margin-bottom:0.5rem;">${
            this.stats.admin || 0
          }</div><div style="font-size:1rem;opacity:0.95;font-weight:600;">👤 Admins</div></div><div style="background:linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%);color:white;padding:2rem;border-radius:20px;box-shadow:0 8px 25px rgba(139,92,246,0.3);"><div style="font-size:3rem;font-weight:900;margin-bottom:0.5rem;">${
            this.stats.superadmin || 0
          }</div><div style="font-size:1rem;opacity:0.95;font-weight:600;">🛡️ Superadmins</div></div></div>`;
          const statsContainer = document.getElementById("roleStatsContainer");
          if (statsContainer) statsContainer.innerHTML = statsHTML;
        }
        renderUsers() {
          const container = document.getElementById("usersTableContainer");
          if (!container) return;
          if (this.users.length === 0) {
            container.innerHTML =
              '<div style="text-align:center;padding:3rem;color:#666;"><div style="font-size:3rem;margin-bottom:1rem;">🔍</div><p>Aucun utilisateur trouvé</p></div>';
            return;
          }
          const tableHTML = `<table class="data-table"><thead><tr><th>ID</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Dernière Connexion</th><th>Actions</th></tr></thead><tbody>${this.users
            .map((user) => this.renderUserRow(user))
            .join("")}</tbody></table>`;
          container.innerHTML = tableHTML;
        }
        renderUserRow(user) {
          const roleColors = {
            client: "#3b82f6",
            admin: "#f59e0b",
            superadmin: "#8b5cf6",
          };
          const statusColors = {
            active: "#10b981",
            suspended: "#f59e0b",
            banned: "#ef4444",
          };
          const isProtected = user.email === "sazulis@outlook.fr";
          const lastLogin = user.last_login
            ? new Date(user.last_login).toLocaleDateString("fr-FR", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit",
              })
            : "Jamais";
          return `<tr><td><strong>#${
            user.id
          }</strong></td><td><div style="display:flex;align-items:center;gap:0.5rem;"><span style="font-size:1.5rem;">${
            user.avatar || "👤"
          }</span><div><div style="font-weight:600;">${
            user.username || "N/A"
          }</div><div style="font-size:0.85em;color:#666;">${
            user.prenom || ""
          } ${user.nom || ""}</div></div></div></td><td>${
            user.email
          }</td><td><select class="role-select" data-user-id="${
            user.id
          }" data-old-role="${user.role}" ${
            isProtected ? "disabled" : ""
          } style="border-color:${roleColors[user.role]};background:${
            roleColors[user.role]
          }15;color:${roleColors[user.role]};"><option value="client" ${
            user.role === "client" ? "selected" : ""
          }>👥 Client</option><option value="admin" ${
            user.role === "admin" ? "selected" : ""
          }>👤 Admin</option><option value="superadmin" ${
            user.role === "superadmin" ? "selected" : ""
          }>🛡️ Superadmin</option></select></td><td><select class="status-select" data-user-id="${
            user.id
          }" data-old-status="${user.account_status}" ${
            isProtected ? "disabled" : ""
          } style="border-color:${
            statusColors[user.account_status]
          };background:${statusColors[user.account_status]}15;color:${
            statusColors[user.account_status]
          };"><option value="active" ${
            user.account_status === "active" ? "selected" : ""
          }>✓ Actif</option><option value="suspended" ${
            user.account_status === "suspended" ? "selected" : ""
          }>⏸ Suspendu</option><option value="banned" ${
            user.account_status === "banned" ? "selected" : ""
          }>🚫 Banni</option></select></td><td>${lastLogin}</td><td><button class="action-btn btn-view" onclick="roleManager.viewUser(${
            user.id
          })"><i class="fas fa-eye"></i></button>${
            !isProtected
              ? `<button class="action-btn btn-delete" onclick="roleManager.deleteUser(${user.id},'${user.email}')"><i class="fas fa-trash"></i></button>`
              : '<span style="color:#6b7280;font-size:0.85em;">🔒 Protégé</span>'
          }</td></tr>`;
        }
        setupEventListeners() {
          const searchInput = document.getElementById("searchUsers");
          if (searchInput) {
            searchInput.addEventListener("input", () => this.applyFilters());
          }
          document.addEventListener("change", (e) => {
            if (e.target.classList.contains("role-select")) {
              this.changeRole(e.target);
            }
            if (e.target.classList.contains("status-select")) {
              this.changeStatus(e.target);
            }
          });
        }
        applyFilters() {
          const search = document.getElementById("searchUsers").value;
          const roleFilter = document.getElementById("roleFilter").value;
          const statusFilter = document.getElementById("statusFilter").value;
          this.loadUsers(search, roleFilter, statusFilter);
        }
        async changeRole(selectElement) {
          const userId = parseInt(selectElement.dataset.userId);
          const newRole = selectElement.value;
          const oldRole = selectElement.dataset.oldRole;
          if (
            !confirm(`Changer le rôle de cet utilisateur en "${newRole}" ?`)
          ) {
            selectElement.value = oldRole;
            return;
          }
          try {
            let allUsers = JSON.parse(
              localStorage.getItem("demo_users") || "[]"
            );
            const userIndex = allUsers.findIndex((u) => u.id === userId);
            if (userIndex !== -1) {
              allUsers[userIndex].role = newRole;
              localStorage.setItem("demo_users", JSON.stringify(allUsers));
              this.showNotification(
                `✅ Rôle changé en "${newRole}"`,
                "success"
              );
              selectElement.dataset.oldRole = newRole;
              this.loadUsers();
            }
          } catch (error) {
            console.error("Erreur:", error);
            this.showNotification(
              "❌ Erreur lors du changement de rôle",
              "error"
            );
            selectElement.value = oldRole;
          }
        }
        async changeStatus(selectElement) {
          const userId = parseInt(selectElement.dataset.userId);
          const newStatus = selectElement.value;
          const oldStatus = selectElement.dataset.oldStatus;
          if (
            !confirm(`Changer le statut de cet utilisateur en "${newStatus}" ?`)
          ) {
            selectElement.value = oldStatus;
            return;
          }
          try {
            let allUsers = JSON.parse(
              localStorage.getItem("demo_users") || "[]"
            );
            const userIndex = allUsers.findIndex((u) => u.id === userId);
            if (userIndex !== -1) {
              allUsers[userIndex].account_status = newStatus;
              localStorage.setItem("demo_users", JSON.stringify(allUsers));
              this.showNotification(
                `✅ Statut changé en "${newStatus}"`,
                "success"
              );
              selectElement.dataset.oldStatus = newStatus;
              this.loadUsers();
            }
          } catch (error) {
            console.error("Erreur:", error);
            this.showNotification(
              "❌ Erreur lors du changement de statut",
              "error"
            );
            selectElement.value = oldStatus;
          }
        }
        viewUser(userId) {
          const user = this.users.find((u) => u.id === userId);
          if (!user) return;
          const modal = `<div style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:99999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(5px);" onclick="this.remove()"><div style="background:white;border-radius:25px;padding:2.5rem;max-width:600px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);" onclick="event.stopPropagation()"><div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem;padding-bottom:1rem;border-bottom:2px solid var(--light-cream);"><span style="font-size:3rem;">${
            user.avatar || "👤"
          }</span><div><h2 style="color:#1976d2;margin:0;">Profil Utilisateur</h2><p style="color:#666;margin:0.3rem 0 0 0;">${
            user.username
          }</p></div></div><div style="display:grid;gap:1rem;"><div style="display:grid;grid-template-columns:150px 1fr;gap:0.5rem;padding:0.8rem;background:#f8faff;border-radius:10px;"><strong style="color:#666;">ID:</strong><span>#${
            user.id
          }</span></div><div style="display:grid;grid-template-columns:150px 1fr;gap:0.5rem;padding:0.8rem;background:#f8faff;border-radius:10px;"><strong style="color:#666;">Email:</strong><span>${
            user.email
          }</span></div><div style="display:grid;grid-template-columns:150px 1fr;gap:0.5rem;padding:0.8rem;background:#f8faff;border-radius:10px;"><strong style="color:#666;">Nom complet:</strong><span>${
            user.prenom
          } ${
            user.nom
          }</span></div><div style="display:grid;grid-template-columns:150px 1fr;gap:0.5rem;padding:0.8rem;background:#f8faff;border-radius:10px;"><strong style="color:#666;">Rôle:</strong><span style="font-weight:600;">${
            user.role
          }</span></div><div style="display:grid;grid-template-columns:150px 1fr;gap:0.5rem;padding:0.8rem;background:#f8faff;border-radius:10px;"><strong style="color:#666;">Statut:</strong><span style="font-weight:600;">${
            user.account_status
          }</span></div><div style="display:grid;grid-template-columns:150px 1fr;gap:0.5rem;padding:0.8rem;background:#f8faff;border-radius:10px;"><strong style="color:#666;">Inscription:</strong><span>${new Date(
            user.created_at
          ).toLocaleDateString("fr-FR", {
            year: "numeric",
            month: "long",
            day: "numeric",
          })}</span></div><div style="display:grid;grid-template-columns:150px 1fr;gap:0.5rem;padding:0.8rem;background:#f8faff;border-radius:10px;"><strong style="color:#666;">Dernière connexion:</strong><span>${
            user.last_login
              ? new Date(user.last_login).toLocaleDateString("fr-FR", {
                  year: "numeric",
                  month: "long",
                  day: "numeric",
                  hour: "2-digit",
                  minute: "2-digit",
                })
              : "Jamais"
          }</span></div></div><button onclick="this.closest('[style*=fixed]').remove()" style="margin-top:2rem;width:100%;padding:1rem;background:linear-gradient(135deg,#1976d2 0%,#2196f3 100%);color:white;border:none;border-radius:12px;cursor:pointer;font-weight:600;font-size:1rem;transition:all 0.3s ease;">Fermer</button></div></div>`;
          document.body.insertAdjacentHTML("beforeend", modal);
        }
        showNotification(message, type = "info") {
          const colors = {
            success: "#10b981",
            error: "#ef4444",
            info: "#3b82f6",
          };
          const notif = document.createElement("div");
          notif.style.cssText = `position:fixed;top:20px;right:20px;background:${colors[type]};color:white;padding:1rem 1.5rem;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.2);z-index:999999;font-weight:600;animation:slideIn 0.3s ease;`;
          notif.textContent = message;
          document.body.appendChild(notif);
          setTimeout(() => {
            notif.style.animation = "slideOut 0.3s ease";
            setTimeout(() => {
              if (document.body.contains(notif))
                document.body.removeChild(notif);
            }, 300);
          }, 4000);
        }
      }
      let dashboard;
      let roleManager;
      let themeManager;
      document.addEventListener("DOMContentLoaded", function () {
        dashboard = new SazulisAdminDashboard();
        window.adminDash = dashboard;
        roleManager = new RoleManager();
        window.roleManager = roleManager;
        if (
          window.themeManager &&
          typeof window.themeManager.renderThemesGallery === "function"
        ) {
          window.themeManager.renderThemesGallery();
        } else {
          setTimeout(function () {
            if (
              window.themeManager &&
              typeof window.themeManager.renderThemesGallery === "function"
            ) {
              window.themeManager.renderThemesGallery();
            }
          }, 500);
        }
        if (
          document.querySelector(
            '.nav-item.active[data-section="completed-projects"]'
          )
        ) {
          dashboard.showSection("completed-projects");
        }
        console.log("✅ Dashboard Admin initialisé avec succès");
      });
      if (document.getElementById("security-unban")) {
        setTimeout(() => {
          loadSecurityStats();
          renderBlacklist();
        }, 500);
      }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="assets/js/theme-sync-js.js"></script>
    <script src="assets/js/logout.js"></script>
    <script>
      if (typeof SazulisAdminDashboard?.applyFeteThemeOnSite === "function") {
        SazulisAdminDashboard.applyFeteThemeOnSite();
      }
    </script>
  </body>
</html>
