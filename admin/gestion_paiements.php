<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Traitement du nouveau paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_paiement'])) {
    $adherent_id = (int)$_POST['adherent_id'];
    $livre_id = (int)$_POST['livre_id'];
    $type_action = $_POST['type_action'];
    $montant = (float)$_POST['montant'];
    $numero_transaction = htmlspecialchars($_POST['numero_transaction']);
    $banque = htmlspecialchars($_POST['banque']);
    $date = date('Y-m-d H:i:s');

    $conn->beginTransaction();
    try {
        // Enregistrer le paiement
        $stmt = $conn->prepare("INSERT INTO paiements (id_adherent, id_livre, type_action, montant, numero_transaction, banque, date_paiement) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$adherent_id, $livre_id, $type_action, $montant, $numero_transaction, $banque, $date]);
        
        // Si c'est un paiement d'emprunt, mettre à jour l'emprunt
        if ($type_action === 'emprunt') {
            $stmt_emprunt = $conn->prepare("UPDATE emprunts SET numero_transaction = ?, banque = ? WHERE id_adherent = ? AND id_livre = ? AND statut = 'en cours' ORDER BY date_emprunt DESC LIMIT 1");
            $stmt_emprunt->execute([$numero_transaction, $banque, $adherent_id, $livre_id]);
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Paiement enregistré avec succès!";
        header("Location: gestion_paiements.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Erreur lors de l'enregistrement: " . $e->getMessage();
    }
}

// Liste des paiements récents
$paiements = $conn->query("
    SELECT p.*, a.nom, a.prenom, a.telephone, l.titre 
    FROM paiements p
    JOIN adherents a ON p.id_adherent = a.id
    JOIN livres l ON p.id_livre = l.id
    ORDER BY p.date_paiement DESC
    LIMIT 50
")->fetchAll();

// Livres disponibles
$livres = $conn->query("SELECT id, titre FROM livres")->fetchAll();

// Adhérents
$adherents = $conn->query("SELECT id, nom, prenom, telephone FROM adherents")->fetchAll();

// Messages de notification
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des paiements | Bibliothèque Admin</title>
    
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
        
        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning) 0%, #f39c12 100%);
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
        
        .badge {
            font-weight: 600;
            padding: 0.4em 0.8em;
            font-size: 0.75em;
            letter-spacing: 0.05em;
            border-radius: 50px;
        }
        
        .price-badge {
            font-size: 1rem;
            padding: 0.35em 0.65em;
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
        
        .badge-achat {
            background-color: var(--secondary);
        }
        
        .badge-emprunt {
            background-color: var(--info);
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
        div.table-container {
         overflow-x: auto;
        white-space: nowrap;
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
                    <a class="nav-link active" href="gestion_paiements.php">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Gestion des paiements</span>
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
                    <h1 class="h4 mb-0"><i class="fas fa-money-bill-wave me-2"></i>Gestion des paiements</h1>
                </div>
                
                <div class="topbar-nav">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
                        <i class="fas fa-plus me-1"></i> Nouveau paiement
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
                    <div class="col-md-4">
                        <div class="card stat-card primary mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Paiements aujourd'hui</h5>
                                        <?php 
                                            $paiements_aujourdhui = $conn->query("
                                                SELECT COUNT(*) as count, SUM(montant) as total 
                                                FROM paiements 
                                                WHERE DATE(date_paiement) = CURDATE()
                                            ")->fetch();
                                        ?>
                                        <h2 class="stat-value"><?= $paiements_aujourdhui['count'] ?></h2>
                                        <small><?= number_format($paiements_aujourdhui['total'], 2) ?> MRU</small>
                                    </div>
                                    <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card success mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Total des paiements</h5>
                                        <?php 
                                            $total_paiements = $conn->query("SELECT SUM(montant) as total FROM paiements")->fetchColumn();
                                        ?>
                                        <h2 class="stat-value"><?= number_format($total_paiements, 2) ?> MRU</h2>
                                    </div>
                                    <i class="fas fa-coins fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card info mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Répartition</h5>
                                        <?php 
                                            $achats = $conn->query("SELECT COUNT(*) FROM paiements WHERE type_action = 'achat'")->fetchColumn();
                                            $emprunts = $conn->query("SELECT COUNT(*) FROM paiements WHERE type_action = 'emprunt'")->fetchColumn();
                                        ?>
                                        <h2 class="stat-value"><?= $achats + $emprunts ?></h2>
                                        <small><?= $achats ?> achats / <?= $emprunts ?> emprunts</small>
                                    </div>
                                    <i class="fas fa-chart-pie fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Derniers paiements -->
                <div class="card table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Derniers paiements</h5>
                        <span class="badge bg-primary">50 derniers</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Adhérent</th>
                                        <th>Livre</th>
                                        <th>Type</th>
                                        <th class="text-end">Montant</th>
                                        <th>Transaction</th>
                                        <th>Banque</th>
                                        <th>Téléphone</th>
                                        <th>Initiales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($paiements) > 0): ?>
                                        <?php foreach ($paiements as $paiement): ?>
                                            <tr>
                                               
                                                <td>
                                                    <span class="d-block"><?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?></span>
                                                    <small class="text-muted"><?= date('H:i', strtotime($paiement['date_paiement'])) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($paiement['prenom'] . ' ' . $paiement['nom']) ?></td>
                                                <td><?= htmlspecialchars($paiement['titre']) ?></td>
                                                <td>
                                                    <span class="badge <?= $paiement['type_action'] === 'achat' ? 'badge-achat' : 'badge-emprunt' ?>">
                                                        <?= ucfirst($paiement['type_action']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-success price-badge">
                                                        <?= number_format($paiement['montant'], 2) ?> MRU
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($paiement['numero_transaction']) ?></td>
                                                <td><?= htmlspecialchars($paiement['banque']) ?></td>
                                                <td><?=htmlspecialchars($paiement['telephone']) ?></td>
                                                <td>
                                                    <div class="member-avatar">
                                                        <?= strtoupper(substr($paiement['prenom'], 0, 1) . substr($paiement['nom'], 0, 1)) ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucun paiement enregistré</h5>
                                                <p>Commencez par enregistrer un nouveau paiement</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nouveau Paiement -->
    <div class="modal fade" id="newPaymentModal" tabindex="-1" aria-labelledby="newPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="newPaymentModalLabel"><i class="fas fa-money-bill-wave me-2"></i>Nouveau paiement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="adherent_id" class="form-label">Adhérent *</label>
                                <select class="form-select" id="adherent_id" name="adherent_id" required>
                                    <option value="">Sélectionner un adhérent</option>
                                    <?php foreach ($adherents as $adherent): ?>
                                        <option value="<?= $adherent['id'] ?>" data-telephone="<?= htmlspecialchars($adherent['telephone']) ?>">
                                            <?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="livre_id" class="form-label">Livre *</label>
                                <select class="form-select" id="livre_id" name="livre_id" required>
                                    <option value="">Sélectionner un livre</option>
                                    <?php foreach ($livres as $livre): ?>
                                        <option value="<?= $livre['id'] ?>">
                                            <?= htmlspecialchars($livre['titre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="type_action" class="form-label">Type *</label>
                                <select class="form-select" id="type_action" name="type_action" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="achat">Achat</option>
                                    <option value="emprunt">Emprunt</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="montant" class="form-label">Montant (MRU) *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="montant" name="montant" required>
                                    <span class="input-group-text">MRU</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="numero_transaction" class="form-label">N° Transaction *</label>
                                <input type="text" class="form-control" id="numero_transaction" name="numero_transaction" required>
                            </div>
                            <div class="col-md-6">
                                <label for="banque" class="form-label">Banque *</label>
                                <input type="text" class="form-control" id="banque" name="banque" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="submit" name="enregistrer_paiement" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i> Enregistrer le paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Mise à jour automatique du prix pour les emprunts
        document.getElementById('type_action').addEventListener('change', function() {
            if (this.value === 'emprunt') {
                document.getElementById('montant').value = '20.00';
            }
        });
    </script>
</body>
</html>
