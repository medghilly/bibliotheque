<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fonction de nettoyage des inputs



// Ajout d'un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $titre = cleanInput($_POST['titre']);
    $auteur = cleanInput($_POST['auteur']);
    $annee = !empty($_POST['annee']) ? (int)$_POST['annee'] : NULL;
    $categorie = cleanInput($_POST['categorie']);
    $nb_exemplaires = (int)$_POST['nb_exemplaires'];
    $prix_vente = !empty($_POST['prix_vente']) ? (float)$_POST['prix_vente'] : NULL;
    $nb_exemplaires_vente = (int)$_POST['nb_exemplaires_vente'];

    $stmt = $conn->prepare("INSERT INTO livres (titre, auteur, annee, categorie, nb_exemplaires, prix_vente, nb_exemplaires_vente) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titre, $auteur, $annee, $categorie, $nb_exemplaires, $prix_vente, $nb_exemplaires_vente]);
    
    $_SESSION['success_message'] = "Livre ajouté avec succès!";
    header("Location: gestion_livres.php");
    exit();
}

// Suppression d'un livre
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $conn->prepare("DELETE FROM livres WHERE id = ?")->execute([$id]);
    
    $_SESSION['success_message'] = "Livre supprimé avec succès!";
    header("Location: gestion_livres.php");
    exit();
}

// Recherche
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$where = $search ? "WHERE titre LIKE '%$search%' OR auteur LIKE '%$search%'" : '';

$livres = $conn->query("SELECT * FROM livres $where ORDER BY titre")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des livres | Bibliothèque Admin</title>
    
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
        
        /* Cartes */
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-bottom: none;
            background-color: white;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        /* Tableaux */
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
        
        /* Boutons */
        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            transition: all 0.3s;
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
        
        /* Modal */
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        .btn-close-white {
            filter: invert(1);
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
                    <a class="nav-link active" href="gestion_livres.php">
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
                    <h1 class="h4 mb-0"><i class="fas fa-book me-2"></i>Gestion des livres</h1>
                </div>
                
                <div class="topbar-nav">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="fas fa-plus me-1"></i> Nouveau livre
                    </button>
                </div>
            </nav>
            
            <!-- Contenu -->
            <div class="container-fluid pt-4">
                <!-- Success Message -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher par titre ou auteur..." value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-primary" type="submit">
                                        Rechercher
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="gestion_livres.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-sync-alt me-1"></i> Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Books Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des livres</h5>
                        <span class="badge bg-primary"><?= count($livres) ?> livre(s)</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Titre</th>
                                        <th>Auteur</th>
                                        <th>Année</th>
                                        <th>Disponibles</th>
                                        <th>Prix</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($livres) > 0): ?>
                                        <?php foreach ($livres as $livre): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($livre['titre']) ?></td>
                                                <td><?= htmlspecialchars($livre['auteur']) ?></td>
                                                <td><?= $livre['annee'] ?? '-' ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $livre['nb_exemplaires'] > 0 ? 'success' : 'danger' ?>">
                                                        <?= $livre['nb_exemplaires'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($livre['prix_vente']): ?>
                                                        <span class="badge bg-info"><?= number_format($livre['prix_vente'], 2) ?> MRU</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Non vendable</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="modifierlivre.php?id=<?= $livre['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?supprimer=<?= $livre['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce livre?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucun livre trouvé</h5>
                                                <?php if ($search): ?>
                                                    <p>Essayez une autre recherche ou <a href="gestion_livres.php">affichez tous les livres</a></p>
                                                <?php else: ?>
                                                    <p>Commencez par ajouter un nouveau livre</p>
                                                <?php endif; ?>
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

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBookModalLabel"><i class="fas fa-plus-circle me-2"></i>Ajouter un nouveau livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="titre" class="form-label">Titre *</label>
                                <input type="text" class="form-control" id="titre" name="titre" required>
                            </div>
                            <div class="col-md-6">
                                <label for="auteur" class="form-label">Auteur *</label>
                                <input type="text" class="form-control" id="auteur" name="auteur" required>
                            </div>
                            <div class="col-md-4">
                                <label for="annee" class="form-label">Année de publication</label>
                                <input type="number" class="form-control" id="annee" name="annee" min="1000" max="<?= date('Y') ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="categorie" class="form-label">Catégorie</label>
                                <input type="text" class="form-control" id="categorie" name="categorie">
                            </div>
                            <div class="col-md-4">
                                <label for="nb_exemplaires" class="form-label">Exemplaires (emprunt) *</label>
                                <input type="number" class="form-control" id="nb_exemplaires" name="nb_exemplaires" min="0" value="1" required>
                            </div>
                            <div class="col-md-4">
                                <label for="prix_vente" class="form-label">Prix de vente (MRU)</label>
                                <input type="number" step="0.01" class="form-control" id="prix_vente" name="prix_vente" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="nb_exemplaires_vente" class="form-label">Exemplaires à vendre</label>
                                <input type="number" class="form-control" id="nb_exemplaires_vente" name="nb_exemplaires_vente" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Annuler</button>
                        <button type="submit" name="ajouter" class="btn btn-primary"><i class="fas fa-save me-1"></i> Enregistrer</button>
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
    </script>
</body>
</html>