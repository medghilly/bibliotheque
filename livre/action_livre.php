<?php
session_start();
require_once __DIR__ . '/../db/config.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Veuillez vous connecter pour accéder à cette fonctionnalité.";
    header("Location: ../utilisateur/login.php");
    exit();
}

// Validation des paramètres GET
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = "Paramètres manquants.";
    header("Location: livres.php");
    exit();
}

$id_livre = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$action = in_array($_GET['action'], ['emprunter', 'acheter']) ? $_GET['action'] : null;
$user_id = $_SESSION['user_id'];

if (!$id_livre || !$action) {
    $_SESSION['error'] = "Requête invalide.";
    header("Location: livres.php");
    exit();
}

// Récupération du livre
try {
    $stmt = $conn->prepare("SELECT * FROM livres WHERE id = ?");
    $stmt->execute([$id_livre]);
    $livre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$livre) {
        $_SESSION['error'] = "Livre introuvable.";
        header("Location: livres.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données.";
    header("Location: livres.php");
    exit();
}

// Vérification disponibilité
$disponible = false;
$max_quantite = 0;

if ($action === 'emprunter') {
    $disponible = ($livre['nb_exemplaires'] > 0);
    $max_quantite = $livre['nb_exemplaires'];
} elseif ($action === 'acheter') {
    $disponible = ($livre['nb_exemplaires_vente'] > 0 && $livre['prix_vente'] > 0);
    $max_quantite = $livre['nb_exemplaires_vente'];
}

if (!$disponible) {
    $_SESSION['error'] = "Ce livre n'est plus disponible pour cette action.";
    header("Location: livres.php");
    exit();
}

// Récupération infos adhérent
try {
    $stmt = $conn->prepare("SELECT nom, prenom, email, telephone FROM adherents WHERE id = ?");
    $stmt->execute([$user_id]);
    $adherent = $stmt->fetch();
} catch (PDOException $e) {
    $adherent = ['telephone' => ''];
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    $banque = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $numero_transaction = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_STRING);
    $mode_reception = filter_input(INPUT_POST, 'delivery_method', FILTER_SANITIZE_STRING);
    $adresse_livraison = ($mode_reception === 'delivery') ? 
        filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING) : '';

    if (empty($banque) || empty($numero_transaction) || empty($mode_reception)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs correctement.";
        header("Location: action_livre.php?id=$id_livre&action=$action");
        exit();
    }

    try {
        $conn->beginTransaction();

        // Génération numéro facture
        $numero_facture = 'FACT-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        if ($action === 'emprunter') {
            $date_retour = date('Y-m-d', strtotime('+7 days'));
            
            // Enregistrement emprunt
            $stmt = $conn->prepare("INSERT INTO emprunts (id_livre, id_adherent, date_emprunt, date_retour, caution, penalite_jour, telephone, numero_transaction, banque, statut, mode_reception, adresse_livraison, numero_facture) 
                                   VALUES (?, ?, CURDATE(), ?, 200, 10, ?, ?, ?, 'en cours', ?, ?, ?)");
            $stmt->execute([
                $id_livre, 
                $user_id, 
                $date_retour, 
                $adherent['telephone'], 
                $numero_transaction, 
                $banque,
                $mode_reception,
                $adresse_livraison,
                $numero_facture
            ]);
            
            // Mise à jour stock
            $stmt = $conn->prepare("UPDATE livres SET nb_exemplaires = nb_exemplaires - 1 WHERE id = ?");
            $stmt->execute([$id_livre]);
            
            $message_success = "Emprunt confirmé. Date de retour: " . date('d/m/Y', strtotime($date_retour));
            
        } elseif ($action === 'acheter') {
            $prix_total = $livre['prix_vente'];
            $frais_livraison = ($mode_reception === 'delivery') ? 50.00 : 0.00;
            $total_a_payer = $prix_total + $frais_livraison;
            
            // Enregistrement vente
            $stmt = $conn->prepare("INSERT INTO ventes (id_livre, id_adherent, date_vente, prix, frais_livraison, total, telephone, numero_transaction, banque, mode_reception, adresse_livraison, numero_facture) 
                                  VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id_livre, 
                $user_id, 
                $prix_total,
                $frais_livraison,
                $total_a_payer,
                $adherent['telephone'], 
                $numero_transaction, 
                $banque,
                $mode_reception,
                $adresse_livraison,
                $numero_facture
            ]);
            
            // Mise à jour stock
            $stmt = $conn->prepare("UPDATE livres SET nb_exemplaires_vente = nb_exemplaires_vente - 1 WHERE id = ?");
            $stmt->execute([$id_livre]);
            
            $message_success = "Achat confirmé. Total: " . number_format($total_a_payer, 2) . "MRU";
        }

        $conn->commit();
        
        // Enregistrement paiement
        $stmt = $conn->prepare("INSERT INTO paiements (id_adherent, id_livre, type_action, montant, numero_transaction, banque) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $id_livre,
            ($action === 'acheter' ? 'achat' : 'emprunt'),
            ($action === 'acheter' ? $total_a_payer : 20.00),
            $numero_transaction,
            $banque
        ]);
        
        $_SESSION['success'] = $message_success;
        $_SESSION['facture_numero'] = $numero_facture;
        $_SESSION['livre_titre'] = $livre['titre'];
        $_SESSION['livre_auteur'] = $livre['auteur'];
        $_SESSION['mode_reception'] = $mode_reception;
        $_SESSION['adresse_livraison'] = $adresse_livraison;
        $_SESSION['email'] = $adherent['email'] ?? '';
        $_SESSION['user_name'] = ($adherent['prenom'] ?? '') . ' ' . ($adherent['nom'] ?? '');
        
        header("Location: afficher_facture.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer.";
        header("Location: action_livre.php?id=$id_livre&action=$action");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation <?= htmlspecialchars($action) ?> | Bibliothèque RT</title>
    
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
        
        .confirmation-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 850px;
        }
        
        .card-header {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        
        .book-img {
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        .info-card {
            background-color: #f8f9fc;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--secondary);
        }
        
        .info-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .bank-logo {
            width: 30px;
            height: 20px;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .delivery-address { 
            display: none;
            margin-top: 1rem;
        }
        
        .btn-confirm { 
            background-color: var(--secondary);
            color: white;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-confirm:hover { 
            background-color: #c82333;
            color: white;
        }
        
        .alert-instructions {
            background-color: #e8f4fd;
            border-left: 4px solid #4e73df;
        }
        
        .price-badge {
            background-color: var(--secondary);
            color: white;
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
        }
        
        @media (max-width: 768px) {
            .book-img {
                height: 150px;
                margin-bottom: 1rem;
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
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="livres.php">
                            <i class="fas fa-book me-1"></i> Livres
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars(($adherent['prenom'] ?? '') . ' ' . ($adherent['nom'] ?? '')) ?>
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
        <div class="confirmation-container">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="fas fa-<?= $action === 'acheter' ? 'shopping-cart' : 'book' ?> me-2"></i>
                    Confirmation de <?= $action === 'acheter' ? "l'achat" : "l'emprunt" ?>
                </div>
                
                <div class="card-body p-4">
                    <!-- Affichage livre -->
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-4 text-center">
                            <img src="../images/<?= htmlspecialchars($livre['image'] ?? 'default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($livre['titre']) ?>" 
                                 class="book-img img-fluid">
                        </div>
                        <div class="col-md-8">
                            <h3 class="mb-2"><?= htmlspecialchars($livre['titre']) ?></h3>
                            <p class="text-muted mb-3"><?= htmlspecialchars($livre['auteur']) ?></p>
                            
                            <?php if ($action === 'acheter'): ?>
                            <div class="d-flex align-items-center">
                                <span class="price-badge rounded-pill me-3">
                                    <?= number_format($livre['prix_vente'], 2) ?> MRU
                                </span>
                                <small class="text-muted">Prix unitaire</small>
                            </div>
                            <?php endif; ?>
                            <?php if ($action === 'emprunter'): ?>
                           <div class="d-flex align-items-center">
                                <span class="price-badge rounded-pill me-3">
                                      200 MRU
                                 </span>
                             <small class="text-muted">Caution d'emprunt</small>
                           </div>
<?php endif; ?>

                        </div>
                    </div>
                    
                    <!-- Instructions paiement -->
                    <div class="alert alert-instructions mb-4">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle fa-lg me-3 mt-1 text-primary"></i>
                            <div>
                                <h5 class="info-title mb-2">Instructions de paiement</h5>
                                <ol class="mb-0">
                                    <li class="mb-2">Effectuez le virement à l'un de nos comptes :</li>
                                    <ul class="mb-3 ps-4">
                                        <li>
                                            <strong>Bankily / Sedad / Masrivi :</strong> 44444242 (Bibliothèque RT)
                                        </li>
                                    </ul>
                                    <li class="mb-2">Indiquez en référence : <strong>LIVRE-<?= $id_livre ?></strong></li>
                                    <?php if ($action === 'acheter'): ?>
                                    <li>Montant à payer : <span class="fw-bold" id="montant-total"><?= number_format($livre['prix_vente'], 2) ?> MRU</span></li>
                                    <?php endif; ?>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire -->
                    <form method="POST" id="payment-form">
                        <div class="info-card mb-4">
                            <h5 class="info-title"><i class="fas fa-credit-card me-2"></i> Méthode de paiement</h5>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="bankily" value="Bankily" checked>
                                <label class="form-check-label d-flex align-items-center" for="bankily">
                                    <img src="../images/bankily.jpeg" alt="Bankily" class="bank-logo"> Bankily
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="sedad" value="Sedad">
                                <label class="form-check-label d-flex align-items-center" for="sedad">
                                    <img src="../images/sedad.jpeg" alt="Sedad" class="bank-logo"> Sedad
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="masrivi" value="Masrivi">
                                <label class="form-check-label d-flex align-items-center" for="masrivi">
                                    <img src="../images/masrivi.png" alt="Masrivi" class="bank-logo"> Masrivi
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="transaction_id" class="form-label fw-semibold">Numéro de transaction</label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" required
                                >
                            <small class="text-muted">Visible dans l'historique de votre application mobile</small>
                        </div>

                        <div class="mb-4">
                            <label for="delivery_method" class="form-label fw-semibold">Mode de réception</label>
                            <select class="form-select" id="delivery_method" name="delivery_method" required>
                                <option value="pickup">Retrait en bibliothèque</option>
                                <option value="delivery">Livraison à domicile (+50 MRU)</option>
                            </select>
                        </div>

                        <div id="delivery-address" class="delivery-address">
                            <label for="address" class="form-label fw-semibold">Adresse complète</label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="N° maison, rue, ville, code postal"></textarea>
                        </div>

                        <div class="d-grid gap-3 mt-4">
                            <button type="submit" class="btn btn-confirm btn-lg py-2">
                                <i class="fas fa-check-circle me-2"></i>
                                Confirmer <?= $action === 'acheter' ? "l'achat" : "l'emprunt" ?>
                            </button>
                            <a href="catalogue.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Retour au catalogue
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion affichage adresse livraison
        document.getElementById('delivery_method').addEventListener('change', function() {
            const addressField = document.getElementById('delivery-address');
            if (this.value === 'delivery') {
                addressField.style.display = 'block';
                // Mettre à jour le montant total si achat
                <?php if ($action === 'acheter'): ?>
                updateTotal();
                <?php endif; ?>
            } else {
                addressField.style.display = 'none';
                <?php if ($action === 'acheter'): ?>
                updateTotal();
                <?php endif; ?>
            }
        });

        // Calcul montant total pour achat
        <?php if ($action === 'acheter'): ?>
        function updateTotal() {
            const livraison = document.getElementById('delivery_method').value === 'delivery' ? 50 : 0;
            const total = (<?= $livre['prix_vente'] ?> + livraison).toFixed(2);
            document.getElementById('montant-total').textContent = total + ' MRU';
        }
        <?php endif; ?>
    </script>
</body>
</html>