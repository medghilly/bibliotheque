<?php
session_start();
require_once __DIR__  . '/../db/config.php';

// Activation des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: ../utilisateur/login.php");
    exit();
}
// Récupération et validation des paramètres
$search = trim($_GET['search'] ?? '');
$categorie = $_GET['categorie'] ?? '';

// Liste blanche pour le tri
$allowed_sort_columns = ['titre', 'auteur', 'annee', 'date_ajout'];
$tri = isset($_GET['tri']) && in_array($_GET['tri'], $allowed_sort_columns) ? $_GET['tri'] : 'titre';

// Construction sécurisée de la requête
$sql = "SELECT l.*, 
               (SELECT COUNT(*) FROM emprunts WHERE id_livre = l.id AND date_retour_reel IS NULL) AS nb_emprunts
        FROM livres l WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (titre LIKE ? OR auteur LIKE ? OR description LIKE ?)";
    $params = array_merge($params, array_fill(0, 3, "%$search%"));
}

if (!empty($categorie)) {
    $sql .= " AND categorie = ?";
    $params[] = $categorie;
}

// Utilisation d'une map pour les colonnes de tri
$sort_columns = [
    'titre' => 'l.titre',
    'auteur' => 'l.auteur',
    'annee' => 'l.annee',
    'date_ajout' => 'l.date_ajout DESC' // Tri décroissant pour les plus récents
];
$sql .= " ORDER BY " . $sort_columns[$tri];

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $livres = $stmt->fetchAll();
    
    $categories = $conn->query("SELECT DISTINCT categorie FROM livres WHERE categorie IS NOT NULL ORDER BY categorie")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des livres: " . $e->getMessage();
    header("Location: catalogue.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue | Bibliothèque RT</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
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
            line-height: 1.6;
        }
        
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.05rem;
            font-size: 1.4rem;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        #librarySlider {
        border-radius: 0.75rem;
        overflow: hidden;
    }
    
    .carousel-control-prev, .carousel-control-next {
        width: 5%;
    }
    
    .carousel-control-prev-icon, .carousel-control-next-icon {
        background-size: 1.2rem;
        width: 2.5rem;
        height: 2.5rem;
    }
    
    .carousel-indicators {
        bottom: 20px;
    }
    
    .carousel-indicators button {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.5);
        border: none;
    }
    
    .carousel-indicators button.active {
        background-color: white;
    }
    
    .carousel-caption {
        bottom: 30%;
        left: 10%;
        right: 10%;
        text-align: left;
    }
    
    @media (max-width: 768px) {
        .carousel-caption {
            bottom: 20px;
            left: 5%;
            right: 5%;
        }
        
        #librarySlider .carousel-item img {
            height: 300px !important;
        }
    }

        .hero-title {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-weight: 300;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            padding: 1.75rem;
            margin-bottom: 2.5rem;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(44, 62, 80, 0.15);
        }
        
        .input-group-text {
            background-color: var(--light);
            border-color: #e0e0e0;
        }
        
        .book-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 1rem 2.5rem rgba(0, 0, 0, 0.15);
        }
        
        .book-cover {
            height: 300px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.5s ease;
        }
        
        .book-card:hover .book-cover {
            transform: scale(1.03);
        }
        
        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .card-text {
            color: var(--gray);
            flex-grow: 1;
        }
        
        .badge-category {
            background-color: var(--primary-light);
            color: white;
            font-weight: 500;
            padding: 0.5em 0.75em;
            border-radius: 0.5rem;
        }
        
        .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }
        
        .btn {
            padding: 0.65rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-borrow {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-borrow:hover {
            background-color: var(--primary-light);
            color: white;
        }
        
        .btn-buy {
            background-color: var(--secondary);
            color: white;
            border: none;
        }
        
        .btn-buy:hover {
            background-color: #d62c1a;
            color: white;
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-outline-secondary {
            border-color: var(--gray);
            color: var(--gray);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--gray);
            color: white;
        }
        
        .no-books {
            padding: 4rem 0;
            text-align: center;
        }
        
        .no-books-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--gray);
            opacity: 0.5;
        }
        
        .no-books-title {
            font-weight: 300;
            color: var(--gray);
            margin-bottom: 1.5rem;
        }
        
        .alert {
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
        }
        
        @media (max-width: 992px) {
            .book-cover {
                height: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 3rem 0;
            }
            
            .book-cover {
                height: 220px;
            }
            
            .filter-section {
                padding: 1.25rem;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .book-cover {
                height: 200px;
            }
            
            .card-body, .card-footer {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../utilisateur/index.php">
                <i class="fas fa-book-open me-2"></i>Bibliothèque RT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                   
                    <li class="nav-item">
                        <a class="nav-link active" href="../utilisateur/index.php">
                            <i class="fas fa-home me-1"></i> Accueil
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
                    </span>
                    <a href="../utilisateur/logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="hero-title display-5 fw-bold">Explorez notre catalogue</h1>
            <p class="hero-subtitle lead">Découvrez, empruntez ou achetez parmi notre large sélection de livres</p>
        </div>
    </section>
    <!-- Ajoutez ceci juste après la section Hero, avant le container principal -->
<section class="container mb-5">
    <div id="librarySlider" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner rounded-3 overflow-hidden" style="box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
                     class="d-block w-100" 
                     alt="Bibliothèque moderne"
                     style="height: 400px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block" style="background: rgba(44, 62, 80, 0.7); border-radius: 0.5rem; padding: 1rem;">
                    <h5>Notre Espace Lecture</h5>
                    <p>Découvrez notre environnement propice à la lecture</p>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="carousel-item">
                <img src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
                     class="d-block w-100" 
                     alt="Rayons de livres"
                     style="height: 400px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block" style="background: rgba(44, 62, 80, 0.7); border-radius: 0.5rem; padding: 1rem;">
                    <h5>Nos Collections</h5>
                    <p>Plus de 10,000 ouvrages à votre disposition</p>
                </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="carousel-item">
                <img src="https://images.unsplash.com/photo-1535905557558-afc4877a26fc?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
                     class="d-block w-100" 
                     alt="Espace de travail"
                     style="height: 400px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block" style="background: rgba(44, 62, 80, 0.7); border-radius: 0.5rem; padding: 1rem;">
                    <h5>Espace Étude</h5>
                    <p>Un cadre idéal pour vos recherches</p>
                </div>
            </div>
        </div>
        
        <!-- Contrôles du carousel -->
        <button class="carousel-control-prev" type="button" data-bs-target="#librarySlider" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-primary rounded-circle p-2" aria-hidden="true"></span>
            <span class="visually-hidden">Précédent</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#librarySlider" data-bs-slide="next">
            <span class="carousel-control-next-icon bg-primary rounded-circle p-2" aria-hidden="true"></span>
            <span class="visually-hidden">Suivant</span>
        </button>
    </div>
</section>



    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Messages -->
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="filter-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label small text-muted">Recherche</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Titre, auteur ou description..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="categorie" class="form-label small text-muted">Catégorie</label>
                    <select name="categorie" id="categorie" class="form-select">
                        <option value="">Toutes catégories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['categorie']) ?>" 
                                <?= $cat['categorie'] == $categorie ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['categorie']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tri" class="form-label small text-muted">Trier par</label>
                    <select name="tri" id="tri" class="form-select">
                        <option value="titre" <?= $tri == 'titre' ? 'selected' : '' ?>>Titre</option>
                        <option value="auteur" <?= $tri == 'auteur' ? 'selected' : '' ?>>Auteur</option>
                        <option value="annee" <?= $tri == 'annee' ? 'selected' : '' ?>>Année</option>
                        <option value="date_ajout" <?= $tri == 'date_ajout' ? 'selected' : '' ?>>Récent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Appliquer
                    </button>
                </div>
            </form>
        </div>

        <!-- Livres -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (count($livres) > 0): ?>
            <?php foreach($livres as $livre): ?>
                <?php $disponible = ($livre['nb_exemplaires'] - $livre['nb_emprunts']) > 0; ?>
                <div class="col">
                    <div class="card book-card h-100">
                        <img src="../images/<?= htmlspecialchars($livre['image'] ?? 'default.jpg') ?>" 
                             class="card-img-top book-cover" 
                             alt="Couverture de <?= htmlspecialchars($livre['titre']) ?>"
                             loading="lazy">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($livre['titre']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($livre['auteur']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge badge-category"><?= htmlspecialchars($livre['categorie']) ?></span>
                                <small class="text-muted"><?= $livre['annee'] ?></small>
                            </div>
                            <p class="card-text">
                                <span class="badge <?= $disponible ? 'bg-success' : 'bg-danger' ?>">
                                    <i class="fas <?= $disponible ? 'fa-check' : 'fa-times' ?> me-1"></i>
                                    <?= $disponible ? 'Disponible' : 'Indisponible' ?>
                                </span>
                            </p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-grid gap-2">
                                <a href="details.php?id=<?= $livre['id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-info-circle me-1"></i> Détails
                                </a>

                                <?php if ($disponible): ?>
                                    <a href="action_livre.php?id=<?= $livre['id'] ?>&action=emprunter" class="btn btn-borrow">
                                        <i class="fas fa-book me-1"></i> Emprunter
                                    </a>
                                <?php else: ?>
                                    <a href="reservation.php?id=<?= $livre['id'] ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-clock me-1"></i> Réserver
                                    </a>
                                <?php endif; ?>

                                <?php if($livre['nb_exemplaires_vente'] > 0 && $livre['prix_vente'] > 0): ?>
                                    <a href="action_livre.php?id=<?= $livre['id'] ?>&action=acheter" class="btn btn-buy">
                                        <i class="fas fa-shopping-cart me-1"></i> 
                                        <?= number_format($livre['prix_vente'], 2) ?> MRU
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 no-books">
                <i class="fas fa-book-open no-books-icon"></i>
                <h4 class="no-books-title">Aucun livre trouvé</h4>
                <a href="catalogue.php" class="btn btn-primary px-4">
                    Réinitialiser les filtres
                </a>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>