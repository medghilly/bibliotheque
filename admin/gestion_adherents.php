<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}



// Recherche et filtres
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$where = $search ? "WHERE nom LIKE '%$search%' OR prenom LIKE '%$search%' OR email LIKE '%$search%'" : '';

$adherents = $conn->query("SELECT * FROM adherents $where ORDER BY nom, prenom")->fetchAll();

// Message de succès
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des adhérents | Bibliothèque Admin</title>
    
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
        
        /* Avatar adhérent */
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(78, 115, 223, 0.3);
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
                    <a class="nav-link" href="gestion_livres.php">
                        <i class="fas fa-book"></i>
                        <span>Gestion des livres</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link active" href="gestion_adherents.php">
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
                    <h1 class="h4 mb-0"><i class="fas fa-users me-2"></i>Gestion des adhérents</h1>
                </div>
                
                <div class="topbar-nav">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="fas fa-plus me-1"></i> Nouvel adhérent
                    </button>
                </div>
            </nav>
            
            <!-- Contenu -->
            <div class="container-fluid pt-4">
                <!-- Success Message -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher par nom, prénom ou email..." value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-primary" type="submit">
                                        Rechercher
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="gestion_adherents.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-sync-alt me-1"></i> Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Members Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des adhérents</h5>
                        <span class="badge bg-primary"><?= count($adherents) ?> adhérent(s)</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Adhérent</th>
                                        <th>Email</th>
                                        <th>Inscription</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($adherents) > 0): ?>
                                        <?php foreach ($adherents as $adherent): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="member-avatar me-3">
                                                            <?= strtoupper(substr($adherent['prenom'], 0, 1) . substr($adherent['nom'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?></div>
                                                            <div class="text-muted small">ID: <?= $adherent['id'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($adherent['email']) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?= date('d/m/Y', strtotime($adherent['date_inscription'] ?? 'now')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="modifier_adherent.php?id=<?= $adherent['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="historique_adherent.php?id=<?= $adherent['id'] ?>" class="btn btn-sm btn-info" title="Historique">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                        <a href="supprimer_adherent.php?id=<?= $adherent['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet adhérent?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucun adhérent trouvé</h5>
                                                <?php if ($search): ?>
                                                    <p>Essayez une autre recherche ou <a href="gestion_adherents.php">affichez tous les adhérents</a></p>
                                                <?php else: ?>
                                                    <p>Commencez par ajouter un nouvel adhérent</p>
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

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMemberModalLabel"><i class="fas fa-user-plus me-2"></i>Nouvel adhérent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="ajouter_adherent.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Enregistrer</button>
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