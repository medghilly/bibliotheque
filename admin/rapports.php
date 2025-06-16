<?php
session_start();
require_once __DIR__ . '/../db/config.php';

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Paramètres de période
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'mois';
$annee = isset($_GET['annee']) ? (int)$_GET['annee'] : date('Y');

// Statistiques générales
$stats = [
    'total_livres' => $conn->query("SELECT COUNT(*) FROM livres")->fetchColumn(),
    'total_adherents' => $conn->query("SELECT COUNT(*) FROM adherents")->fetchColumn(),
    'total_emprunts' => $conn->query("SELECT COUNT(*) FROM emprunts")->fetchColumn(),
    'total_ventes' => $conn->query("SELECT COUNT(*) FROM ventes")->fetchColumn(),
    'ca_total' => $conn->query("SELECT SUM(prix) FROM ventes")->fetchColumn()
];

// Statistiques mensuelles
$stats_mensuelles = $conn->query("
    SELECT 
        MONTH(date_vente) as mois,
        COUNT(*) as nb_ventes,
        SUM(prix) as ca_mensuel
    FROM ventes
    WHERE YEAR(date_vente) = $annee
    GROUP BY MONTH(date_vente)
    ORDER BY mois ASC
")->fetchAll();

// Livres les plus vendus
$livres_populaires = $conn->query("
    SELECT l.titre, COUNT(v.id) as nb_ventes, SUM(v.prix) as total_ventes
    FROM ventes v
    JOIN livres l ON v.id_livre = l.id
    GROUP BY v.id_livre
    ORDER BY nb_ventes DESC
    LIMIT 5
")->fetchAll();

// Adhérents les plus actifs
$adherents_actifs = $conn->query("
    SELECT a.nom, a.prenom, COUNT(e.id) as nb_emprunts
    FROM emprunts e
    JOIN adherents a ON e.id_adherent = a.id
    GROUP BY e.id_adherent
    ORDER BY nb_emprunts DESC
    LIMIT 5
")->fetchAll();

// Dernières ventes
$ventes_recentes = $conn->query("
    SELECT v.*, l.titre, a.nom, a.prenom 
    FROM ventes v
    JOIN livres l ON v.id_livre = l.id
    JOIN adherents a ON v.id_adherent = a.id
    ORDER BY v.date_vente DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports | Bibliothèque Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-dark: rgb(98, 109, 142);
            --primary: #2c3e50;
            --primary-light: #34495e;
            --secondary: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --info: #36b9cc;
            --light: #f8f9fc;
            --dark: #2a3042;
            --gray: #858796;
            --purple: #6f42c1;
            --pink: #e83e8c;
            --teal: #20c997;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        /* Sidebar stylisée */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
            transition: all 0.3s;
            z-index: 1000;
            padding-bottom: 20px;
        }
        
        .sidebar-brand {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .sidebar-brand:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand-icon {
            font-size: 1.8rem;
            margin-right: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 1.5rem;
        }
        
        .nav-item {
            margin: 0.3rem 1.25rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 0.5rem;
            padding: 0.85rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link.active:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: white;
        }
        
        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 1rem;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .nav-link:hover i {
            transform: scale(1.1);
        }
        
        /* Contenu principal */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        /* Topbar */
        .topbar {
            height: 80px;
            background: white;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2.5rem;
            border-radius: 0 0 15px 15px;
        }
        
        /* Cartes de statistiques */
        .stat-card {
            border-radius: 15px;
            transition: all 0.4s;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            border-left: 5px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card:after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        .stat-card.success {
            border-left-color: var(--secondary);
            background: linear-gradient(135deg, var(--secondary) 0%, var(--teal) 100%);
            color: white;
        }
        
        .stat-card.info {
            border-left-color: var(--info);
            background: linear-gradient(135deg, var(--info) 0%, #4dc8f3 100%);
            color: white;
        }
        
        .stat-card.warning {
            border-left-color: var(--warning);
            background: linear-gradient(135deg, var(--warning) 0%, #f8b84e 100%);
            color: white;
        }
        
        .stat-card.purple {
            border-left-color: var(--purple);
            background: linear-gradient(135deg, var(--purple) 0%, #9b6bcc 100%);
            color: white;
        }
        
        .stat-card.danger {
            border-left-color: var(--danger);
            background: linear-gradient(135deg, var(--danger) 0%, #f06f6b 100%);
            color: white;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            opacity: 0.9;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            top: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            opacity: 0.5;
        }
        
        /* Tableaux */
        .table-card {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .table-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .table-card .card-header {
            border-bottom: none;
            background-color: white;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .table-card .card-header h5 {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
        }
        
        .table-card .card-header h5 i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .table th {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            color: var(--gray);
            border-top: none;
            padding: 1rem;
            background-color: #f9fafd;
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .table tr:hover td {
            background-color: rgba(78, 115, 223, 0.03);
        }
        
        /* Badges */
        .badge {
            font-weight: 600;
            padding: 0.4em 0.8em;
            font-size: 0.75em;
            letter-spacing: 0.05em;
            border-radius: 50px;
        }
        
        .badge.bg-primary {
            background-color: var(--primary) !important;
        }
        
        .badge.bg-success {
            background-color: var(--secondary) !important;
        }
        
        .badge.bg-danger {
            background-color: var(--danger) !important;
        }
        
        .badge.bg-warning {
            background-color: var(--warning) !important;
            color: var(--dark);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .topbar {
                padding: 0 1.5rem;
                height: 70px;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
        }
        
        /* Filtres rapports */
        .report-filters {
            background-color: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .report-filters .form-label {
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Graphique placeholder */
        .graph-placeholder {
            background-color: #f8f9fc;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            color: var(--gray);
            margin-bottom: 2rem;
        }
        
        .graph-placeholder i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-light);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="admin_dashboard.php" class="sidebar-brand">
                <i class="fas fa-book-open sidebar-brand-icon"></i>
                <span class="sidebar-brand-text">Bibliothèque Admin</span>
            </a>
            
            <div class="sidebar-divider"></div>
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="gestion_livres.php">
                        <i class="fas fa-book"></i>
                        <span>Gestion des livres</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="gestion_adherents.php">
                        <i class="fas fa-users"></i>
                        <span>Gestion des adhérents</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="gestion_emprunts.php">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Gestion des emprunts</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="gestion_ventes.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Gestion des ventes</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link active" href="rapports.php">
                        <i class="fas fa-chart-pie"></i>
                        <span>Rapports</span>
                    </a>
                </li>
                
                <div class="sidebar-divider"></div>
                
                <li class="nav-item">
                    <a class="nav-link" href="../utilisateur/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Contenu principal -->
        <div class="main-content w-100">
            <!-- Topbar -->
            <nav class="topbar">
                <button class="btn btn-link d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-flex align-items-center">
                    <h1 class="h4 mb-0"><i class="fas fa-chart-pie me-2"></i>Rapports et statistiques</h1>
                </div>
                
                <div class="topbar-nav">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimer
                    </button>
                </div>
            </nav>
            
            <!-- Contenu -->
            <div class="container-fluid pt-4">
                <!-- Filtres -->
                <div class="report-filters">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="periode" class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode">
                                <option value="jour" <?= $periode === 'jour' ? 'selected' : '' ?>>Journalier</option>
                                <option value="semaine" <?= $periode === 'semaine' ? 'selected' : '' ?>>Hebdomadaire</option>
                                <option value="mois" <?= $periode === 'mois' ? 'selected' : '' ?>>Mensuel</option>
                                <option value="annee" <?= $periode === 'annee' ? 'selected' : '' ?>>Annuel</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="annee" class="form-label">Année</label>
                            <select class="form-select" id="annee" name="annee">
                                <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                    <option value="<?= $y ?>" <?= $annee === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <a href="rapports.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync-alt me-1"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Statistiques globales -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card primary mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Livres</h5>
                                        <h2 class="stat-value"><?= $stats['total_livres'] ?></h2>
                                    </div>
                                    <i class="fas fa-book stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card success mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Adhérents</h5>
                                        <h2 class="stat-value"><?= $stats['total_adherents'] ?></h2>
                                    </div>
                                    <i class="fas fa-users stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card info mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Emprunts</h5>
                                        <h2 class="stat-value"><?= $stats['total_emprunts'] ?></h2>
                                    </div>
                                    <i class="fas fa-exchange-alt stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card purple mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">CA Total</h5>
                                        <h2 class="stat-value"><?= number_format($stats['ca_total'], 2) ?> DH</h2>
                                    </div>
                                    <i class="fas fa-coins stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Graphiques -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card table-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Ventes mensuelles (<?= $annee ?>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="graph-placeholder">
                                    <i class="fas fa-chart-bar"></i>
                                    <h5>Graphique des ventes mensuelles</h5>
                                    <p>Données pour l'année <?= $annee ?></p>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mois</th>
                                                <th>Ventes</th>
                                                <th>CA (DH)</th>
                                                <th>% du total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats_mensuelles as $stat): 
                                                $mois_nom = DateTime::createFromFormat('!m', $stat['mois'])->format('F');
                                                $pourcentage = $stats['ca_total'] > 0 ? ($stat['ca_mensuel'] / $stats['ca_total'] * 100) : 0;
                                            ?>
                                                <tr>
                                                    <td><?= ucfirst($mois_nom) ?></td>
                                                    <td><?= $stat['nb_ventes'] ?></td>
                                                    <td><?= number_format($stat['ca_mensuel'], 2) ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pourcentage ?>%" 
                                                                aria-valuenow="<?= $pourcentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <?= number_format($pourcentage, 1) ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card table-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>Top 5 des livres les plus vendus</h5>
                            </div>
                            <div class="card-body">
                                <div class="graph-placeholder">
                                    <i class="fas fa-chart-pie"></i>
                                    <h5>Graphique des livres populaires</h5>
                                    <p>Répartition des ventes par livre</p>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Livre</th>
                                                <th>Ventes</th>
                                                <th>CA (DH)</th>
                                                <th>Part</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($livres_populaires as $livre): 
                                                $pourcentage = $stats['ca_total'] > 0 ? ($livre['total_ventes'] / $stats['ca_total'] * 100) : 0;
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($livre['titre']) ?></td>
                                                    <td><?= $livre['nb_ventes'] ?></td>
                                                    <td><?= number_format($livre['total_ventes'], 2) ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pourcentage ?>%" 
                                                                aria-valuenow="<?= $pourcentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <?= number_format($pourcentage, 1) ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tableaux supplémentaires -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card table-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Top 5 des adhérents les plus actifs</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Adhérent</th>
                                                <th>Emprunts</th>
                                                <th>Dernier emprunt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($adherents_actifs as $adherent): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?></td>
                                                    <td><?= $adherent['nb_emprunts'] ?></td>
                                                    <td><?= date('d/m/Y') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card table-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Dernières ventes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Livre</th>
                                                <th>Adhérent</th>
                                                <th>Montant</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ventes_recentes as $vente): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($vente['date_vente'])) ?></td>
                                                    <td><?= htmlspecialchars($vente['titre']) ?></td>
                                                    <td><?= htmlspecialchars($vente['prenom'] . ' ' . $vente['nom']) ?></td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <?= number_format($vente['prix'], 2) ?> DH
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Boutons d'export -->
                <div class="text-center mt-4">
                    <button class="btn btn-primary me-2">
                        <i class="fas fa-file-excel me-1"></i> Exporter en Excel
                    </button>
                    <button class="btn btn-success me-2">
                        <i class="fas fa-file-pdf me-1"></i> Exporter en PDF
                    </button>
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimer le rapport
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script pour le toggle de la sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>