<?php
session_start();
require_once __DIR__  . '/../db/config.php';


// Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT id, nom, prenom, email, telephone, date_inscription FROM adherents WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Utilisateur non trouvé - déconnecter
    session_destroy();
    header("Location: login.php");
    exit();
}

// Récupérer les emprunts en cours
$emprunts = $conn->prepare("
    SELECT e.*, l.titre, l.auteur, l.image 
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id
    WHERE e.id_adherent = ? AND e.date_retour_reel IS NULL
    ORDER BY e.date_retour ASC
");
$emprunts->execute([$user_id]);
$emprunts = $emprunts->fetchAll();

// Récupérer l'historique des emprunts
$historique_emprunts = $conn->prepare("
    SELECT e.*, l.titre, l.auteur, l.image 
    FROM emprunts e
    JOIN livres l ON e.id_livre = l.id
    WHERE e.id_adherent = ? AND e.date_retour_reel IS NOT NULL
    ORDER BY e.date_retour_reel DESC
    LIMIT 10
");
$historique_emprunts->execute([$user_id]);
$historique_emprunts = $historique_emprunts->fetchAll();

// Récupérer l'historique des achats
$achats = $conn->prepare("
    SELECT v.*, l.titre, l.auteur, l.image, l.prix_vente as prix
    FROM ventes v
    JOIN livres l ON v.id_livre = l.id
    WHERE v.id_adherent = ?
    ORDER BY v.date_vente DESC
    LIMIT 10
");
$achats->execute([$user_id]);
$achats = $achats->fetchAll();

// Calculer les statistiques avec des requêtes préparées
$stmt = $conn->prepare("SELECT COUNT(*) FROM emprunts WHERE id_adherent = ?");
$stmt->execute([$user_id]);
$total_emprunts = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM emprunts WHERE id_adherent = ? AND date_retour_reel IS NULL");
$stmt->execute([$user_id]);
$emprunts_en_cours = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM ventes WHERE id_adherent = ?");
$stmt->execute([$user_id]);
$total_achats = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COALESCE(SUM(prix), 0) FROM ventes WHERE id_adherent = ?");
$stmt->execute([$user_id]);
$montant_depense = $stmt->fetchColumn();

$stats = [
    'total_emprunts' => $total_emprunts,
    'emprunts_en_cours' => $emprunts_en_cours,
    'total_achats' => $total_achats,
    'montant_depense' => $montant_depense
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon compte | Bibliothèque RT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-light: #34495e;
            --secondary: #e74c3c;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: var(--dark);
        }
        
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .section-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--secondary);
        }
        
        .book-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .book-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .book-cover {
            height: 120px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        
        .book-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .book-meta {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        
        .badge-status {
            font-weight: 500;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
        
        .badge-en-cours {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-retourne {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-achete {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .nav-tabs .nav-link {
            color: var(--gray);
            font-weight: 500;
            border: none;
            padding: 0.8rem 1.5rem;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 3px solid var(--secondary);
            background-color: transparent;
        }
        
        .tab-content {
            padding: 1.5rem 0;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e9ecef;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .profile-header {
                text-align: center;
                padding: 1.5rem;
            }
            
            .avatar {
                margin: 0 auto 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book-open me-2"></i>Bibliothèque RT
            </a>
            <div class="d-flex">
                <a  href="../utilisateur/index.php"class="btn btn-light">
                    <i class="fas fa-home me-1"></i> 
                     <span class="d-none d-md-inline">Acceuil</span>

                </a>
                    
                <a href="logout.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="d-none d-md-inline">Déconnexion</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- En-tête du profil -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center text-md-start">
                    <div class="avatar">
                        <?= strtoupper(substr(htmlspecialchars($user['prenom']), 0, 1) . substr(htmlspecialchars($user['nom']), 0, 1)) ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h2><?= htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']) ?></h2>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($user['email']) ?></p>
                    <p class="mb-0"><i class="fas fa-phone me-2"></i> <?= htmlspecialchars($user['telephone'] ?? 'Non renseigné') ?></p>
                </div>
                <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                    <p class="mb-0">Membre depuis <?= date('d/m/Y', strtotime($user['date_inscription'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <div class="stat-value"><?= $stats['total_emprunts'] ?></div>
                    <div class="stat-label">Emprunts total</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?= $stats['emprunts_en_cours'] ?></div>
                    <div class="stat-label">En cours</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?= $stats['total_achats'] ?></div>
                    <div class="stat-label">Achats</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['montant_depense'], 2) ?> €</div>
                    <div class="stat-label">Dépensé</div>
                </div>
            </div>
        </div>
        
        <!-- Onglets -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="emprunts-tab" data-bs-toggle="tab" data-bs-target="#emprunts" type="button" role="tab">
                    <i class="fas fa-book me-2"></i>Emprunts en cours
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historique-tab" data-bs-toggle="tab" data-bs-target="#historique" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Historique des emprunts
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="achats-tab" data-bs-toggle="tab" data-bs-target="#achats" type="button" role="tab">
                    <i class="fas fa-shopping-cart me-2"></i>Achats
                </button>
            </li>
        </ul>
        
        <!-- Contenu des onglets -->
        <div class="tab-content" id="myTabContent">
            <!-- Emprunts en cours -->
            <div class="tab-pane fade show active" id="emprunts" role="tabpanel">
                <?php if (count($emprunts) > 0): ?>
                    <div class="row">
                        <?php foreach ($emprunts as $emprunt): 
                            $jours_restants = (new DateTime($emprunt['date_retour']))->diff(new DateTime())->days;
                            $is_late = (new DateTime()) > (new DateTime($emprunt['date_retour']));
                        ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="book-card card">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                           <img src="../images/<?= htmlspecialchars($emprunt['image'] ?? 'default.jpg') ?>"
                                                 class="img-fluid rounded-start h-100 w-100" 
                                                 style="object-fit: cover; min-height: 120px;"
                                                 alt="<?= htmlspecialchars($emprunt['titre']) ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body">
                                                <h5 class="book-title"><?= htmlspecialchars($emprunt['titre']) ?></h5>
                                                <p class="book-meta"><?= htmlspecialchars($emprunt['auteur']) ?></p>
                                                <p class="book-meta">
                                                    <small class="text-muted">Emprunté le: <?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></small><br>
                                                    <small class="text-muted">Retour avant: <?= date('d/m/Y', strtotime($emprunt['date_retour'])) ?></small>
                                                </p>
                                                <span class="badge badge-status <?= $is_late ? 'bg-danger' : 'badge-en-cours' ?>">
                                                    <?= $is_late ? 'En retard (' . $jours_restants . ' jours)' : 'À rendre dans ' . $jours_restants . ' jours' ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-book-open"></i>
                        <h4>Aucun emprunt en cours</h4>
                        <p>Vous n'avez actuellement aucun livre emprunté.</p>
                        <a href="../livre/livres.php" class="btn btn-primary">Parcourir le catalogue</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Historique des emprunts -->
            <div class="tab-pane fade" id="historique" role="tabpanel">
                <?php if (count($historique_emprunts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Auteur</th>
                                    <th>Date emprunt</th>
                                    <th>Date retour</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique_emprunts as $emprunt): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../images/<?= htmlspecialchars($emprunt['image'] ?? 'default.jpg') ?>"
                                                     class="rounded me-3" 
                                                     width="40" 
                                                     alt="<?= htmlspecialchars($emprunt['titre']) ?>">
                                                <?= htmlspecialchars($emprunt['titre']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($emprunt['auteur']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($emprunt['date_retour_reel'])) ?></td>
                                        <td><span class="badge badge-status badge-retourne">Retourné</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-history"></i>
                        <h4>Aucun historique d'emprunt</h4>
                        <p>Vous n'avez pas encore emprunté de livre.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Achats -->
            <div class="tab-pane fade" id="achats" role="tabpanel">
                <?php if (count($achats) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Auteur</th>
                                    <th>Date achat</th>
                                    <th>Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($achats as $achat): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                            <img src="../images/<?= htmlspecialchars(isset($achat['image']) && $achat['image'] ? $achat['image'] : 'default.jpg') ?>"
                                                 class="rounded me-3"
                                                 width="40"
                                                 alt="Couverture du livre intitulé <?= htmlspecialchars($achat['titre']) ?> écrit par <?= htmlspecialchars($achat['auteur']) ?>, posé sur une table dans une bibliothèque lumineuse. La couverture affiche le titre <?= htmlspecialchars($achat['titre']) ?>. Ambiance calme et studieuse.">
                                            <?= htmlspecialchars($achat['titre']) ?>
                                        </div>
                                        </td>
                                        <td><?= htmlspecialchars($achat['auteur']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($achat['date_vente'])) ?></td>
                                        <td><?= number_format($achat['prix'], 2) ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-shopping-cart"></i>
                        <h4>Aucun achat enregistré</h4>
                        <p>Vous n'avez pas encore acheté de livre.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>