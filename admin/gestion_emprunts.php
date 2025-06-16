<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Traitement du retour d'un livre
if (isset($_GET['retourner'])) {
    $emprunt_id = (int)$_GET['retourner'];
    $today = date('Y-m-d');
    
    $conn->beginTransaction();
    try {
        // Marquer comme retourné
        $stmt = $conn->prepare("UPDATE emprunts SET date_retour_reel = ?, statut = 'retourne' WHERE id = ?");
        $stmt->execute([$today, $emprunt_id]);
        
        // Incrémenter les exemplaires disponibles
        $livre_id = $conn->query("SELECT id_livre FROM emprunts WHERE id = $emprunt_id")->fetchColumn();
        $conn->query("UPDATE livres SET nb_exemplaires = nb_exemplaires + 1 WHERE id = $livre_id");
        
        $conn->commit();
        $_SESSION['success_message'] = "Emprunt retourné avec succès!";
        header("Location: gestion_emprunts.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Erreur lors du retour: " . $e->getMessage();
    }
}

// Traitement du nouvel emprunt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_emprunt'])) {
    $id_livre = (int)$_POST['id_livre'];
    $id_adherent = (int)$_POST['id_adherent'];
    $date_emprunt = $_POST['date_emprunt'];
    $date_retour = $_POST['date_retour'];
    $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;
    
    try {
        $conn->beginTransaction();
        
        // Vérifier la disponibilité
        $disponible = $conn->query("SELECT nb_exemplaires FROM livres WHERE id = $id_livre")->fetchColumn();
        if ($disponible < 1) {
            throw new Exception("Ce livre n'est plus disponible pour l'emprunt");
        }
        
        // Enregistrer l'emprunt
        $stmt = $conn->prepare("INSERT INTO emprunts (id_livre, id_adherent, date_emprunt, date_retour, notes, statut) 
                               VALUES (?, ?, ?, ?, ?, 'en_cours')");
        $stmt->execute([$id_livre, $id_adherent, $date_emprunt, $date_retour, $notes]);
        
        // Décrémenter le nombre d'exemplaires disponibles
        $conn->query("UPDATE livres SET nb_exemplaires = nb_exemplaires - 1 WHERE id = $id_livre");
        
        $conn->commit();
        
        $_SESSION['success_message'] = "Emprunt enregistré avec succès!";
        header("Location: gestion_emprunts.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Erreur lors de l'enregistrement: " . $e->getMessage();
        header("Location: gestion_emprunts.php");
        exit();
    }
}

// Onglet actif
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'en_cours';

// Emprunts en cours
$emprunts_en_cours = $conn->query("
    SELECT e.*, l.titre, a.nom, a.prenom, a.telephone, a.email
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id
    JOIN adherents a ON e.id_adherent = a.id
    WHERE e.date_retour_reel IS NULL
    ORDER BY e.date_retour ASC
")->fetchAll();

// Emprunts retournés (historique)
$emprunts_retournes = $conn->query("
    SELECT e.*, l.titre, a.nom, a.prenom, a.telephone, a.email,
           DATEDIFF(e.date_retour_reel, e.date_retour) AS jours_retard
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id
    JOIN adherents a ON e.id_adherent = a.id
    WHERE e.date_retour_reel IS NOT NULL
    ORDER BY e.date_retour_reel DESC
    LIMIT 100
")->fetchAll();

// Statistiques
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM emprunts WHERE date_retour_reel IS NULL")->fetchColumn(),
    'en_retard' => $conn->query("SELECT COUNT(*) FROM emprunts WHERE date_retour < CURDATE() AND date_retour_reel IS NULL")->fetchColumn(),
    'a_venir' => $conn->query("SELECT COUNT(*) FROM emprunts WHERE date_retour >= CURDATE() AND date_retour_reel IS NULL")->fetchColumn(),
    'retournes' => $conn->query("SELECT COUNT(*) FROM emprunts WHERE date_retour_reel IS NOT NULL")->fetchColumn()
];

// Messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Récupération des livres disponibles et adhérents actifs pour le formulaire
$livres_disponibles = $conn->query("SELECT id, titre, auteur FROM livres WHERE nb_exemplaires > 0 ORDER BY titre")->fetchAll();
$adherents_actifs = $conn->query("SELECT id, nom, prenom FROM adherents ORDER BY nom")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des emprunts | Bibliothèque Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            overflow-x: hidden;
        }
        
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
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
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
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            color: white;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, var(--danger) 0%, #f06f6b 100%);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, var(--secondary) 0%, #1abc9c 100%);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, var(--info) 0%, #5dade2 100%);
        }
        
        .table-card {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .table-card .card-header {
            border-bottom: none;
            background-color: white;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
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
        
        .late-row {
            background-color: #fff3f3;
        }
        
        .badge {
            font-weight: 600;
            padding: 0.4em 0.8em;
            font-size: 0.75em;
            letter-spacing: 0.05em;
            border-radius: 50px;
        }
        
        .member-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--gray);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            background-color: transparent;
        }
        
        .nav-tabs .nav-link:hover:not(.active) {
            border-bottom: 3px solid rgba(44, 62, 80, 0.2);
        }
        
        .tab-content {
            background-color: white;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        /* Styles pour le modal */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
            color: var(--primary);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
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
        }
        
        @media (max-width: 768px) {
            .topbar {
                padding: 0 1.5rem;
                height: 70px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
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
                    <a class="nav-link active" href="gestion_emprunts.php">
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
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Gestion des paiements</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="rapports.php">
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
                    <h1 class="h4 mb-0"><i class="fas fa-exchange-alt me-2"></i>Gestion des emprunts</h1>
                </div>
                
                <div class="topbar-nav">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouvelEmpruntModal">
                        <i class="fas fa-plus me-1"></i> Nouvel emprunt
                    </button>
                </div>
            </nav>
            
            <!-- Contenu -->
            <div class="container-fluid pt-4">
                <!-- Notifications -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card primary mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Emprunts en cours</h5>
                                        <h2 class="stat-value"><?= $stats['total'] ?></h2>
                                    </div>
                                    <i class="fas fa-book-reader fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card danger mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">En retard</h5>
                                        <h2 class="stat-value"><?= $stats['en_retard'] ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card success mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">À venir</h5>
                                        <h2 class="stat-value"><?= $stats['a_venir'] ?></h2>
                                    </div>
                                    <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card info mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Retournés</h5>
                                        <h2 class="stat-value"><?= $stats['retournes'] ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglets -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab === 'en_cours' ? 'active' : '' ?>" 
                           href="?tab=en_cours">
                            <i class="fas fa-book-open me-1"></i> Emprunts en cours
                            <span class="badge bg-primary ms-2"><?= $stats['total'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab === 'retournes' ? 'active' : '' ?>" 
                           href="?tab=retournes">
                            <i class="fas fa-history me-1"></i> Historique des retours
                            <span class="badge bg-secondary ms-2"><?= $stats['retournes'] ?></span>
                        </a>
                    </li>
                </ul>

                <!-- Contenu des onglets -->
                <div class="tab-content">
                    <?php if ($active_tab === 'en_cours'): ?>
                        <!-- Emprunts en cours -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Livre</th>
                                        <th>Adhérent</th>
                                        <th>Date emprunt</th>
                                        <th>Date retour</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($emprunts_en_cours) > 0): ?>
                                        <?php foreach ($emprunts_en_cours as $emprunt): 
                                            $is_late = strtotime($emprunt['date_retour']) < time();
                                            $days_late = $is_late ? floor((time() - strtotime($emprunt['date_retour'])) / (60 * 60 * 24)) : 0;
                                        ?>
                                            <tr class="<?= $is_late ? 'late-row' : '' ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-book me-3 text-primary"></i>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($emprunt['titre']) ?></div>
                                                            <small class="text-muted">ID: <?= $emprunt['id_livre'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="member-avatar me-3">
                                                            <?= strtoupper(substr($emprunt['prenom'], 0, 1) . substr($emprunt['nom'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($emprunt['prenom'] . ' ' . $emprunt['nom']) ?></div>
                                                            <small class="text-muted">ID: <?= $emprunt['id_adherent'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($emprunt['date_retour'])) ?></td>
                                                <td>
                                                    <?php if ($is_late): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation-circle me-1"></i> Retard: <?= $days_late ?> j
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle me-1"></i> En cours
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="?retourner=<?= $emprunt['id'] ?>" class="btn btn-sm btn-success" title="Marquer comme retourné">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="details_emprunt.php?id=<?= $emprunt['id'] ?>" class="btn btn-sm btn-info" title="Détails">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="contact_adherent.php?id=<?= $emprunt['id_adherent'] ?>" class="btn btn-sm btn-warning" title="Contacter">
                                                            <i class="fas fa-envelope"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucun emprunt en cours</h5>
                                                <p>Commencez par enregistrer un nouvel emprunt</p>
                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouvelEmpruntModal">
                                                    <i class="fas fa-plus me-1"></i> Nouvel emprunt
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Historique des retours -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Livre</th>
                                        <th>Adhérent</th>
                                        <th>Date emprunt</th>
                                        <th>Date retour prévue</th>
                                        <th>Date retour réelle</th>
                                        <th>Retard</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($emprunts_retournes) > 0): ?>
                                        <?php foreach ($emprunts_retournes as $emprunt): 
                                            $retard = $emprunt['jours_retard'] > 0 ? $emprunt['jours_retard'] . ' jours' : 'Aucun';
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-book me-3 text-primary"></i>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($emprunt['titre']) ?></div>
                                                            <small class="text-muted">ID: <?= $emprunt['id_livre'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="member-avatar me-3">
                                                            <?= strtoupper(substr($emprunt['prenom'], 0, 1) . substr($emprunt['nom'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($emprunt['prenom'] . ' ' . $emprunt['nom']) ?></div>
                                                            <small class="text-muted">ID: <?= $emprunt['id_adherent'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($emprunt['date_retour'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($emprunt['date_retour_reel'])) ?></td>
                                                <td>
                                                    <?php if ($emprunt['jours_retard'] > 0): ?>
                                                        <span class="badge bg-danger"><?= $retard ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><?= $retard ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="details_emprunt.php?id=<?= $emprunt['id'] ?>" class="btn btn-sm btn-info" title="Détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucun retour enregistré</h5>
                                                <p>Les retours apparaîtront ici</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour nouvel emprunt -->
    <div class="modal fade" id="nouvelEmpruntModal" tabindex="-1" aria-labelledby="nouvelEmpruntModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nouvelEmpruntModalLabel"><i class="fas fa-plus me-2"></i>Nouvel emprunt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="livre" class="form-label">Livre</label>
                                <select class="form-select" id="livre" name="id_livre" required>
                                    <option value="" selected disabled>Sélectionnez un livre</option>
                                    <?php foreach ($livres_disponibles as $livre): ?>
                                        <option value="<?= $livre['id'] ?>"><?= htmlspecialchars($livre['titre']) ?> (<?= htmlspecialchars($livre['auteur']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="adherent" class="form-label">Adhérent</label>
                                <select class="form-select" id="adherent" name="id_adherent" required>
                                    <option value="" selected disabled>Sélectionnez un adhérent</option>
                                    <?php foreach ($adherents_actifs as $adherent): ?>
                                        <option value="<?= $adherent['id'] ?>"><?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="date_emprunt" class="form-label">Date d'emprunt</label>
                                <input type="date" class="form-control" id="date_emprunt" name="date_emprunt" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="date_retour" class="form-label">Date de retour prévue</label>
                                <input type="date" class="form-control" id="date_retour" name="date_retour" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
                            </div>
                            
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes (optionnel)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" name="ajouter_emprunt">Enregistrer l'emprunt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        
        // Calcul automatique de la date de retour
        document.getElementById('date_emprunt').addEventListener('change', function() {
            const dateEmprunt = new Date(this.value);
            if (!isNaN(dateEmprunt.getTime())) {
                const dateRetour = new Date(dateEmprunt);
                dateRetour.setDate(dateRetour.getDate() + 15);