<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Traitement de la nouvelle vente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendre'])) {
    $livre_id = (int)$_POST['livre_id'];
    $adherent_id = (int)$_POST['adherent_id'];
    $prix = (float)$_POST['prix'];
    $numero_transaction = htmlspecialchars($_POST['numero_transaction']);
    $banque = htmlspecialchars($_POST['banque']);
    $date = date('Y-m-d H:i:s');

    $conn->beginTransaction();
    try {
        // Enregistrer la vente
        $stmt = $conn->prepare("INSERT INTO ventes (id_livre, id_adherent, date_vente, prix, numero_transaction, banque) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$livre_id, $adherent_id, $date, $prix, $numero_transaction, $banque]);
        
        // Décrémenter le stock
        $conn->query("UPDATE livres SET nb_exemplaires_vente = nb_exemplaires_vente - 1 WHERE id = $livre_id");
        
        $conn->commit();
        $_SESSION['success_message'] = "Vente enregistrée avec succès!";
        header("Location: gestion_ventes.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Erreur lors de l'enregistrement: " . $e->getMessage();
    }
}

// Liste des ventes récentes
$ventes = $conn->query("
    SELECT v.*, l.titre, a.nom, a.prenom, a.telephone 
    FROM ventes v
    JOIN livres l ON v.id_livre = l.id
    JOIN adherents a ON v.id_adherent = a.id
    ORDER BY v.date_vente DESC
    LIMIT 50
")->fetchAll();

// Livres disponibles à la vente
$livres_vente = $conn->query("SELECT id, titre, prix_vente FROM livres WHERE prix_vente IS NOT NULL AND nb_exemplaires_vente > 0")->fetchAll();

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
    <title>Gestion des ventes | Bibliothèque Admin</title>
    
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
        
        /* Modal Styles (ajouté pour le formulaire) */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            background-color: var(--primary);
            color: white;
            border-bottom: none;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .btn-close-white {
            filter: invert(1);
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
                    <a class="nav-link" href="gestion_emprunts.php">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Gestion des emprunts</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link active" href="gestion_ventes.php">
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
                    <h1 class="h4 mb-0"><i class="fas fa-shopping-cart me-2"></i>Gestion des ventes</h1>
                </div>
                
                <div class="topbar-nav">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSaleModal">
                        <i class="fas fa-plus me-1"></i> Nouvelle vente
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
                                        <h5 class="stat-label">Ventes aujourd'hui</h5>
                                        <?php 
                                            $ventes_aujourdhui = $conn->query("
                                                SELECT COUNT(*) as count, SUM(prix) as total 
                                                FROM ventes 
                                                WHERE DATE(date_vente) = CURDATE()
                                            ")->fetch();
                                        ?>
                                        <h2 class="stat-value"><?= $ventes_aujourdhui['count'] ?></h2>
                                        <small><?= number_format($ventes_aujourdhui['total'], 2) ?> MRU</small>
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
                                        <h5 class="stat-label">Chiffre d'affaires</h5>
                                        <?php 
                                            $ca_total = $conn->query("SELECT SUM(prix) as total FROM ventes")->fetchColumn();
                                        ?>
                                        <h2 class="stat-value"><?= number_format($ca_total, 2) ?> MRU</h2>
                                    </div>
                                    <i class="fas fa-coins fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card warning mb-4 h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="stat-label">Livres en stock</h5>
                                        <?php 
                                            $livres_stock = $conn->query("SELECT SUM(nb_exemplaires_vente) as total FROM livres WHERE prix_vente IS NOT NULL")->fetchColumn();
                                        ?>
                                        <h2 class="stat-value"><?= $livres_stock ?></h2>
                                    </div>
                                    <i class="fas fa-boxes fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dernières ventes -->
                <div class="card table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Dernières ventes</h5>
                        <span class="badge bg-primary">50 dernières</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Livre</th>
                                        <th>Adhérent</th>
                                        <th class="text-end">Montant</th>
                                        <th>Transaction</th>
                                        <th>Banque</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($ventes) > 0): ?>
                                        <?php foreach ($ventes as $vente): ?>
                                            <tr>
                                                <td>
                                                    <span class="d-block"><?= date('d/m/Y', strtotime($vente['date_vente'])) ?></span>
                                                    <small class="text-muted"><?= date('H:i', strtotime($vente['date_vente'])) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($vente['titre']) ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="member-avatar me-3">
                                                            <?= strtoupper(substr($vente['prenom'], 0, 1) . substr($vente['nom'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($vente['prenom'] . ' ' . $vente['nom']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($vente['telephone']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-success price-badge">
                                                        <?= number_format($vente['prix'], 2) ?> MRU
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($vente['numero_transaction']) ?></td>
                                                <td><?= htmlspecialchars($vente['banque']) ?></td>
                                                <td>
                                                    <a href="details_vente.php?id=<?= $vente['id'] ?>" class="btn btn-sm btn-info" title="Détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucune vente enregistrée</h5>
                                                <p>Commencez par enregistrer une nouvelle vente</p>
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

    <!-- Modal Nouvelle Vente -->
    <div class="modal fade" id="newSaleModal" tabindex="-1" aria-labelledby="newSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="newSaleModalLabel"><i class="fas fa-cash-register me-2"></i>Nouvelle vente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="livre_id" class="form-label">Livre *</label>
                                <select class="form-select" id="livre_id" name="livre_id" required>
                                    <option value="">Sélectionner un livre</option>
                                    <?php foreach ($livres_vente as $livre): ?>
                                        <option value="<?= $livre['id'] ?>" data-prix="<?= $livre['prix_vente'] ?>">
                                            <?= htmlspecialchars($livre['titre']) ?> (<?= number_format($livre['prix_vente'], 2) ?> MRU)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="adherent_id" class="form-label">Adhérent *</label>
                                <select class="form-select" id="adherent_id" name="adherent_id" required>
                                    <option value="">Sélectionner un adhérent</option>
                                    <?php foreach ($adherents as $adherent): ?>
                                        <option value="<?= $adherent['id'] ?>">
                                            <?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="prix" class="form-label">Prix (MRU) *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" id="prix" name="prix" required>
                                    <span class="input-group-text">MRU</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="numero_transaction" class="form-label">N° Transaction *</label>
                                <input type="text" class="form-control" id="numero_transaction" name="numero_transaction" required>
                            </div>
                            <div class="col-md-4">
                                <label for="banque" class="form-label">Banque *</label>
                                <select class="form-select" id="banque" name="banque" required>
                                    <option value="">Sélectionner</option>
                                    <option value="Bankily">Bankily</option>
                                    <option value="Sedad">Sedad</option>
                                    <option value="Masrivi">Masrivi</option>
                                    <option value="Espèce">Espèce</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Le stock du livre sera automatiquement décrémenté après la vente.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Annuler
                        </button>
                        <button type="submit" name="vendre" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i> Enregistrer la vente
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

        // Mise à jour automatique du prix
        document.getElementById('livre_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const prix = selectedOption.getAttribute('data-prix');
            if (prix) {
                document.getElementById('prix').value = prix;
            }
        });
    </script>
</body>
</html>