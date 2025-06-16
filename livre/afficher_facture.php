<?php
session_start();
require_once __DIR__ . '/../db/config.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: /../utilisateur/login.php");
    exit();
}

if (!isset($_SESSION['facture_numero'])) {
    header("Location: livres.php");
    exit();
}

// Récupération des données de la session
$numero_facture = $_SESSION['facture_numero'];
$livre_titre = $_SESSION['livre_titre'];
$livre_auteur = $_SESSION['livre_auteur'];
$mode_reception = $_SESSION['mode_reception'];
$adresse_livraison = $_SESSION['adresse_livraison'] ?? '';
$email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['user_name'] ?? '';

// Nettoyage des données de session
unset($_SESSION['facture_numero']);
unset($_SESSION['livre_titre']);
unset($_SESSION['livre_auteur']);
unset($_SESSION['mode_reception']);
unset($_SESSION['adresse_livraison']);
unset($_SESSION['email']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture | Bibliothèque RT</title>
    
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
        }
        
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.05rem;
        }
        
        .invoice-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 850px;
        }
        
        .invoice-header {
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .invoice-title {
            color: var(--primary);
            font-weight: 700;
        }
        
        .info-card {
            background-color: #f8f9fc;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--secondary);
        }
        
        .info-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .badge-success {
            background-color: var(--secondary);
            color: white;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        
        .btn-print {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-print:hover {
            background-color: var(--primary-light);
            color: white;
        }
        
        .user-info {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .alert-confirmation {
            background-color: #e8f4fd;
            border-left: 4px solid #4e73df;
        }
        
        @media print {
            body { background: none; padding: 0; }
            .no-print { display: none; }
            .invoice-container { 
                box-shadow: none; 
                border: none; 
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark no-print">
        <div class="container">
            <a class="navbar-brand" href="../utilisateur/index.php">
                <i class="fas fa-book-open me-2"></i>Bibliothèque RT
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="catalogue.php">
                            <i class="fas fa-book me-1"></i> Catalogue
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($user_name) ?>
                    </span>
                    <a href="../utilisateur/logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mb-5">
        <div class="invoice-container">
            <div class="invoice-header text-center">
                <h1 class="invoice-title">FACTURE</h1>
                <p class="mb-1 text-muted">Référence #<?= htmlspecialchars($numero_facture) ?></p>
            </div>
            
            <div class="user-info">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-building me-2"></i> Bibliothèque RT</h5>
                        <p class="mb-1">ISCAE </p>
                        <p class="mb-1">Noukchott,Mauritanie</p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-user me-2"></i> Client</h5>
                        <p class="mb-1"><?= htmlspecialchars($user_name) ?></p>
                        <p class="mb-1">Email: <?= htmlspecialchars($email) ?></p>
                        <p class="mb-1">Date: <?= date('d/m/Y H:i') ?></p>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Commande confirmée</h5>
                        <p class="mb-0 text-muted">Votre réservation est validée</p>
                    </div>
                </div>
                <div class="text-end">
                    <div class="badge badge-success">
                        <i class="fas fa-check-circle me-1"></i> Paiement accepté
                    </div>
                </div>
            </div>
            
            <div class="info-card">
                <h5 class="info-title"><i class="fas fa-book me-2"></i> Détails du livre</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Titre:</strong> <?= htmlspecialchars($livre_titre) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Auteur:</strong> <?= htmlspecialchars($livre_auteur) ?></p>
                    </div>
                </div>
                
                <h5 class="info-title mt-4"><i class="fas fa-truck me-2"></i> Mode de réception</h5>
                <?php if ($mode_reception === 'pickup'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Type:</strong> Retrait en bibliothèque</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Heures d'ouverture:</strong> 9h-18h (Lundi-Vendredi)</p>
                        </div>
                    </div>
                    <p class="mt-2 mb-0 text-muted">Présentez cette facture lors du retrait</p>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Type:</strong> Livraison à domicile</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Délai estimé:</strong> 48 heures</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <p class="mb-1"><strong>Adresse:</strong></p>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($adresse_livraison)) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-confirmation mt-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3 text-primary"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Information importante</h5>
                        <p class="mb-0">Un email de confirmation a été envoyé à <?= htmlspecialchars($email) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="text-center my-4 no-print">
                <button onclick="window.print()" class="btn btn-print me-3">
                    <i class="fas fa-print me-1"></i> Imprimer
                </button>
                <a href="livres.php" class="btn btn-secondary">
                    <i class="fas fa-book me-1"></i> Retour au catalogue
                </a>
            </div>
            
            <div class="text-center mt-5 text-muted">
                <p class="mb-1">Merci pour votre confiance</p>
                <p class="mb-0">Pour toute question, contactez-nous à bibliotheque@gmail.com</p>
                <p class="mt-3 mb-0">© Bibliothèque RT <?= date('Y') ?> - Tous droits réservés</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>