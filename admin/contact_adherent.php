<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: gestion_emprunts.php");
    exit();
}

$adherent_id = (int)$_GET['id'];

try {
    // Récupération des infos de l'adhérent
    $stmt = $conn->prepare("
        SELECT id, nom, prenom, email, telephone 
        FROM adherents 
        WHERE id = ?
    ");
    $stmt->execute([$adherent_id]);
    $adherent = $stmt->fetch();

    if (!$adherent) {
        header("Location: gestion_emprunts.php");
        exit();
    }

} catch (PDOException $e) {
    header("Location: gestion_emprunts.php");
    exit();
}

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet = trim($_POST['sujet']);
    $message = trim($_POST['message']);
    
    if (!empty($sujet) && !empty($message)) {
        // Ici vous pourriez implémenter l'envoi d'email
        // ou enregistrer le message dans la base de données
        
        $_SESSION['success_message'] = "Message envoyé avec succès à " . htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']);
        header("Location: gestion_emprunts.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacter adhérent | Bibliothèque Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-light: #34495e;
            --secondary: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
        }
        
        .contact-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 2rem auto;
        }
        
        .member-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        
        .contact-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .btn-send {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-send:hover {
            background-color: var(--primary-light);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-envelope me-2"></i>Contacter un adhérent
            </h2>
            <a href="gestion_emprunts.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>

        <div class="contact-container">
            <!-- Infos adhérent -->
            <div class="text-center mb-4">
                <div class="member-avatar">
                    <?= strtoupper(substr($adherent['prenom'], 0, 1) . substr($adherent['nom'], 0, 1)) ?>
                </div>
                <h4><?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?></h4>
                <span class="badge bg-secondary">ID: <?= $adherent['id'] ?></span>
            </div>
            
            <div class="contact-info">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <span class="info-label">Email:</span>
                        <p><?= htmlspecialchars($adherent['email']) ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <span class="info-label">Téléphone:</span>
                        <p><?= htmlspecialchars($adherent['telephone']) ?></p>
                    </div>
                    <?php if (!empty($adherent['adresse'])): ?>
                    <div class="col-12">
                        <span class="info-label">Adresse:</span>
                        <p><?= htmlspecialchars($adherent['adresse']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Formulaire de contact -->
            <form method="POST">
                <div class="mb-3">
                    <label for="sujet" class="form-label">Sujet</label>
                    <input type="text" class="form-control" id="sujet" name="sujet" required>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="copy" name="copy" checked>
                            <label class="form-check-label" for="copy">
                                Recevoir une copie
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-send">
                        <i class="fas fa-paper-plane me-1"></i> Envoyer
                    </button>
                </div>
            </form>
            
            <!-- Options de contact rapide -->
            <div class="mt-4 pt-3 border-top">
                <h5 class="mb-3">Contact rapide</h5>
                <div class="d-flex gap-2">
                    <a href="mailto:<?= htmlspecialchars($adherent['email']) ?>" class="btn btn-outline-primary">
                        <i class="fas fa-envelope me-1"></i> Email direct
                    </a>
                    <?php if (!empty($adherent['telephone'])): ?>
                    <a href="tel:<?= htmlspecialchars($adherent['telephone']) ?>" class="btn btn-outline-success">
                        <i class="fas fa-phone me-1"></i> Appeler
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($adherent['telephone'])): ?>
                    <a href="https://wa.me/<?= htmlspecialchars($adherent['telephone']) ?>" class="btn btn-outline-success" style="background-color: #25D366; color: white;">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>