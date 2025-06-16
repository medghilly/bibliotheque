<?php
session_start();
require_once __DIR__  . '/../db/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: action_livre.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM livres WHERE id = ?");
$stmt->execute([$id]);
$livre = $stmt->fetch();

if (!$livre) {
    $_SESSION['error'] = "Livre introuvable.";
    header("Location: action_livre.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du livre | Bibliothèque RT</title>
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
        
        .navbar-brand, .nav-link {
            color: white !important;
        }
        
        .book-cover {
            height: 400px;
            object-fit: contain;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .btn {
            border-radius: 20px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: var(--dark);
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
        }
        
        .book-details {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .book-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .book-author {
            color: var(--gray);
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
        
        .book-meta {
            margin-bottom: 1.5rem;
        }
        
        .book-description {
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        
        .action-buttons {
            display: grid;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .book-cover {
                height: 300px;
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="../utilisateur/index.php">
                <i class="fas fa-home me-1"></i> Accueil
             </a>

        <div class="d-flex align-items-center">
            <span class="text-white me-3"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
            <a href="livres.php" class="btn btn-outline-light">
                <i class="fas fa-book"></i>
                <span class="d-none d-md-inline">Catalogue</span>
            </a>
            
                   
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-5 mb-4">
            <img src="../images/<?= htmlspecialchars($livre['image'] ?? 'default.jpg') ?>"
                 class="img-fluid book-cover" 
                 alt="<?= htmlspecialchars($livre['titre']) ?>">
        </div>
        <div class="col-lg-7">
            <div class="book-details">
                <h1 class="book-title"><?= htmlspecialchars($livre['titre']) ?></h1>
                <p class="book-author"><?= htmlspecialchars($livre['auteur']) ?></p>
                
                <div class="book-meta">
                    <p><strong>Année de publication :</strong> <?= htmlspecialchars($livre['annee']) ?></p>
                    <p><strong>Catégorie :</strong> <span class="badge bg-secondary"><?= htmlspecialchars($livre['categorie']) ?></span></p>
                </div>
                
                <div class="book-description">
                    <h5>Description :</h5>
                    <p><?= nl2br(htmlspecialchars($livre['description'] ?? 'Aucune description disponible.')) ?></p>
                </div>
                
                <div class="action-buttons">
                    <?php if(isset($livre['nb_exemplaires']) && $livre['nb_exemplaires'] > 0): ?>
                        <a href="action_livre.php?id=<?= $livre['id'] ?>&action=emprunter" class="btn btn-success">
                            <i class="fas fa-book-reader me-2"></i>Emprunter ce livre
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="fas fa-times-circle me-2"></i>Indisponible pour l'emprunt
                        </button>
                    <?php endif; ?>
                    
                    <?php if(isset($livre['nb_exemplaires_vente'], $livre['prix_vente']) && $livre['nb_exemplaires_vente'] > 0 && $livre['prix_vente'] > 0): ?>
                        <a href="action_livre.php?id=<?= $livre['id'] ?>&action=acheter" class="btn btn-warning">
                            <i class="fas fa-shopping-cart me-2"></i>Acheter - <?= number_format($livre['prix_vente'], 2) ?> €
                        </a>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <?php if(!empty($livre['fichier_pdf'])): ?>
                        <a href="download.php?id=<?= $livre['id'] ?>" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>Télécharger le PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>