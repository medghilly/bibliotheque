<?php
session_start();
require_once __DIR__ . '/../db/config.php';

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Récupération des statistiques
$stats = [
    'livres' => $conn->query("SELECT COUNT(*) FROM livres")->fetchColumn(),
    'adherents' => $conn->query("SELECT COUNT(*) FROM adherents")->fetchColumn(),
    'emprunts' => $conn->query("SELECT COUNT(*) FROM emprunts WHERE date_retour_reel IS NULL")->fetchColumn(),
    'retards' => $conn->query("SELECT COUNT(*) FROM emprunts WHERE date_retour < CURDATE() AND date_retour_reel IS NULL")->fetchColumn(),
    'ventes' => $conn->query("SELECT COUNT(*) FROM ventes")->fetchColumn(),
    'ca_total' => $conn->query("SELECT SUM(total) FROM ventes")->fetchColumn()
];

// Derniers emprunts en retard
$retards = $conn->query("
    SELECT e.*, l.titre, a.nom, a.prenom 
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id
    JOIN adherents a ON e.id_adherent = a.id
    WHERE e.date_retour < CURDATE() AND e.date_retour_reel IS NULL
    ORDER BY e.date_retour ASC
    LIMIT 5
")->fetchAll();

// Dernières ventes
$ventes_recentes = $conn->query("
    SELECT v.*, l.titre, a.nom, a.prenom 
    FROM ventes v
    JOIN livres l ON v.id_livre = l.id
    JOIN adherents a ON v.id_adherent = a.id
    ORDER BY v.date_vente DESC
    LIMIT 5
")->fetchAll();

// Statistiques mensuelles pour les rapports
$stats_mensuelles = $conn->query("
    SELECT 
        MONTH(date_vente) as mois,
        COUNT(*) as nb_ventes,
        SUM(prix) as ca_mensuel
    FROM ventes
    WHERE YEAR(date_vente) = YEAR(CURDATE())
    GROUP BY MONTH(date_vente)
    ORDER BY mois DESC
    LIMIT 6
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | Bibliothèque Admin</title>
    
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
        
        .topbar-search {
            width: 350px;
            position: relative;
        }
        
        .topbar-search input {
            border-radius: 30px;
            padding: 0.6rem 1.5rem;
            border: 1px solid #e0e0e0;
            background-color: #f8f9fc;
            transition: all 0.3s;
            padding-left: 45px;
        }
        
        .topbar-search input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.15);
        }
        
        .topbar-search .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .topbar-nav {
            display: flex;
            align-items: center;
        }
        
        .topbar-divider {
            width: 1px;
            background-color: #e3e6f0;
            height: 35px;
            margin: 0 1.5rem;
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
        
        /* Avatar utilisateur */
        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(78, 115, 223, 0.3);
            transition: all 0.3s;
        }
        
        .avatar:hover {
            transform: scale(1.1);
        }
        
        /* Boutons */
        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 0.35rem 0.85rem;
            font-size: 0.8rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }
        
        .btn-success {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-success:hover {
            background-color: #17a673;
            border-color: #17a673;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 200, 138, 0.3);
        }
        
        /* Menu déroulant */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 0.5rem;
            margin-top: 10px;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.25rem;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .dropdown-divider {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        /* Titres */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            color: var(--dark);
        }
        
        .text-muted {
            color: var(--gray) !important;
        }
        
        /* Responsive */
        @media (max-width: 1199px) {
            .sidebar {
                width: 250px;
            }
            
            .main-content {
                margin-left: 250px;
            }
        }
        
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
            
            .topbar-search {
                width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1.5rem;
            }
            
            .topbar {
                padding: 0 1.5rem;
                height: 70px;
            }
            
            .topbar-search {
                display: none;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .topbar {
                padding: 0 1rem;
            }
        }
        
        /* Animation pour le chargement */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadein {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        /* Délai d'animation pour les éléments */
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
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
                    <a class="nav-link active" href="admin_dashboard.php">
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
                    <a class="nav-link" href="gestion_paiements.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Gestion des paiements</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="tab.php">
                        <i class="fas fa-chart-pie"></i>
                        <span>Rapports</span>
                    </a>
                </li>
                
                <div class="sidebar-divider"></div>
                
                <li class="nav-item">
                    <a class="nav-link" href="admin_logout.php">
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
                
                <form class="topbar-search d-none d-lg-block">
                    <div class="input-group">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control ps-4" placeholder="Rechercher un livre, adhérent...">
                    </div>
                </form>
                
                <div class="topbar-nav">
                    <!-- Notifications -->
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($stats['retards'] > 0): ?>
                                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle"><?= $stats['retards'] ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Notifications</span>
                                <span class="badge bg-primary"><?= $stats['retards'] ?> nouveau(x)</span>
                            </h6>
                            <?php if ($stats['retards'] > 0): ?>
                                <a class="dropdown-item" href="#">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-exclamation-triangle text-danger"></i>
                                        </div>
                                        <div>
                                            <div><?= $stats['retards'] ?> emprunt(s) en retard</div>
                                            <small class="text-muted">À traiter rapidement</small>
                                        </div>
                                    </div>
                                </a>
                            <?php else: ?>
                                <a class="dropdown-item text-muted" href="#">
                                    <div class="text-center py-2">
                                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                                        <div>Aucune notification</div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center text-primary" href="#">
                                Voir toutes les notifications
                            </a>
                        </div>
                    </div>
                    
                    <div class="topbar-divider d-none d-lg-block"></div>
                    
                    <!-- Profil utilisateur -->
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                            <div class="avatar me-2">
                                <?= strtoupper(substr($_SESSION['admin_nom'], 0, 1)) ?>
                            </div>
                            <span class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['admin_nom']) ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Connecté en tant que</h6>
                            <a class="dropdown-item" href="#">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-3" style="width: 35px; height: 35px;">
                                        <?= strtoupper(substr($_SESSION['admin_nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div><?= htmlspecialchars($_SESSION['admin_nom']) ?></div>
                                        <small class="text-muted">Administrateur</small>
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user me-2"></i> Profil
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-cog me-2"></i> Paramètres
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="admin_logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Contenu -->
            <div class="container-fluid pt-4">
                <div class="d-flex justify-content-between align-items-center mb-4 animate-fadein">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                    </h1>
                    <div class="text-muted">
                        <i class="fas fa-calendar-day me-2"></i><?= date('l, d F Y') ?>
                    </div>
                </div>
                
                <!-- Cartes de statistiques -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 animate-fadein delay-1">
                        <div class="card stat-card primary mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Livres</h5>
                                        <h2 class="stat-value"><?= $stats['livres'] ?></h2>
                                        <small class="opacity-75">+5.2% ce mois</small>
                                    </div>
                                    <i class="fas fa-book stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 animate-fadein delay-2">
                        <div class="card stat-card success mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Adhérents</h5>
                                        <h2 class="stat-value"><?= $stats['adherents'] ?></h2>
                                        <small class="opacity-75">+12.7% ce mois</small>
                                    </div>
                                    <i class="fas fa-users stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 animate-fadein delay-3">
                        <div class="card stat-card info mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Emprunts</h5>
                                        <h2 class="stat-value"><?= $stats['emprunts'] ?></h2>
                                        <small class="opacity-75">En cours</small>
                                    </div>
                                    <i class="fas fa-exchange-alt stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 animate-fadein delay-4">
                        <div class="card stat-card purple mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">CA Total</h5>
                                        <h2 class="stat-value"><?= number_format($stats['ca_total'], 2) ?>MRU</h2>
                                        <small class="opacity-75"><?= $stats['ventes'] ?> vente(s)</small>
                                    </div>
                                    <i class="fas fa-coins stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tableaux -->
                <div class="row">
                    <!-- Emprunts en retard -->
                    <div class="col-lg-6 mb-4 animate-fadein delay-1">
                        <div class="card table-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Emprunts en retard
                                </h5>
                                <span class="badge bg-danger"><?= $stats['retards'] ?> retard(s)</span>
                            </div>
                            <div class="card-body">
                                <?php if (count($retards) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Livre</th>
                                                    <th>Adhérent</th>
                                                    <th>Retard</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($retards as $retard): 
                                                    $jours_retard = (new DateTime())->diff(new DateTime($retard['date_retour']))->days;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($retard['titre']) ?></strong><br>
                                                            <small class="text-muted">Retour prévu: <?= date('d/m/Y', strtotime($retard['date_retour'])) ?></small>
                                                        </td>
                                                        <td><?= htmlspecialchars($retard['prenom'] . ' ' . $retard['nom']) ?></td>
                                                        <td>
                                                            <span class="badge bg-danger"><?= $jours_retard ?> jour(s)</span>
                                                        </td>
                                                        <td>
                                                            <a href="gestion_emprunts.php?retourner=<?= $retard['id'] ?>" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check me-1"></i> Retour
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <a href="gestion_retours.php" class="btn btn-sm btn-primary">
                                            Voir tous les retards <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle fa-3x text-success"></i>
                                        </div>
                                        <h5 class="text-muted">Aucun emprunt en retard</h5>
                                        <p class="text-muted small">Tous les livres ont été retournés à temps</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dernières ventes -->
                    <div class="col-lg-6 mb-4 animate-fadein delay-2">
                        <div class="card table-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>Dernières ventes
                                </h5>
                                <span class="badge bg-primary"><?= $stats['ventes'] ?> vente(s)</span>
                            </div>
                            <div class="card-body">
                                <?php if (count($ventes_recentes) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
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
                                                        <td>
                                                            <?= date('d/m/Y', strtotime($vente['date_vente'])) ?>
                                                            <small class="text-muted d-block"><?= date('H:i', strtotime($vente['date_vente'])) ?></small>
                                                        </td>
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
                                    <div class="text-end mt-3">
                                        <a href="gestion_ventes.php" class="btn btn-sm btn-primary">
                                            Voir toutes les ventes <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                                        </div>
                                        <h5 class="text-muted">Aucune vente enregistrée</h5>
                                        <p class="text-muted small">Commencez à vendre des livres dès maintenant</p>
                                        <a href="gestion_ventes.php" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus me-1"></i> Nouvelle vente
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rapports mensuels -->
                    <div class="col-lg-6 mb-4 animate-fadein delay-3">
                        <div class="card table-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Rapports mensuels
                                </h5>
                                <span class="badge bg-purple">Statistiques</span>
                            </div>
                            <div class="card-body">
                                <?php if (count($stats_mensuelles) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Mois</th>
                                                    <th>Ventes</th>
                                                    <th>CA (DH)</th>
                                                    <th>Tendance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stats_mensuelles as $stat): 
                                                    $mois_nom = DateTime::createFromFormat('!m', $stat['mois'])->format('F');
                                                ?>
                                                    <tr>
                                                        <td><?= ucfirst($mois_nom) ?></td>
                                                        <td><?= $stat['nb_ventes'] ?></td>
                                                        <td><?= number_format($stat['ca_mensuel'] ?? 0, 2) ?></td>
                                                        <td>
                                                            <?php if ($stat['ca_mensuel'] > 0): ?>
                                                                <i class="fas fa-arrow-up text-success"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-minus text-muted"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button class="btn btn-sm btn-primary" onclick="window.print()">
                                            <i class="fas fa-print me-1"></i> Imprimer le rapport
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-chart-bar fa-3x text-muted"></i>
                                        </div>
                                        <h5 class="text-muted">Aucune donnée statistique</h5>
                                        <p class="text-muted small">Les rapports apparaîtront ici après les premières ventes</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section graphiques -->
                <div class="row animate-fadein delay-4">
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Statistiques annuelles
                                </h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                        <i class="fas fa-filter me-1"></i> Année <?= date('Y') ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#"><?= date('Y')-1 ?></a></li>
                                        <li><a class="dropdown-item" href="#"><?= date('Y')-2 ?></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-center mb-3"><i class="fas fa-book me-2"></i>Activité des emprunts</h6>
                                        <img src="https://via.placeholder.com/600x300?text=Graphique+emprunts" alt="Graphique emprunts" class="img-fluid rounded">
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-center mb-3"><i class="fas fa-coins me-2"></i>Chiffre d'affaires</h6>
                                        <img src="https://via.placeholder.com/600x300?text=Graphique+CA" alt="Graphique CA" class="img-fluid rounded">
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="rapports.php" class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Exporter les rapports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
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
        
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-fadein');
            elements.forEach(el => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>