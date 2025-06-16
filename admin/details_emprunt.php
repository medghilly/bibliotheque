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

$emprunt_id = (int)$_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT e.*, l.titre, l.auteur, l.image, 
               a.nom, a.prenom, a.email, a.telephone,
               DATEDIFF(e.date_retour_reel, e.date_retour) AS jours_retard
        FROM emprunts e
        JOIN livres l ON e.id_livre = l.id
        JOIN adherents a ON e.id_adherent = a.id
        WHERE e.id = ?
    ");
    $stmt->execute([$emprunt_id]);
    $emprunt = $stmt->fetch();

    if (!$emprunt) {
        header("Location: gestion_emprunts.php");
        exit();
    }

    // Calcul du statut
    $today = new DateTime();
    $date_retour = new DateTime($emprunt['date_retour']);
    $statut = '';

    if ($emprunt['date_retour_reel']) {
        $statut = 'Retourné';
        $retard = $emprunt['jours_retard'] > 0 ? $emprunt['jours_retard'] . ' jours de retard' : 'Aucun retard';
    } else {
        if ($today > $date_retour) {
            $retard = $date_retour->diff($today)->days . ' jours de retard';
            $statut = 'En retard';
        } else {
            $jours_restants = $today->diff($date_retour)->days;
            $statut = 'En cours';
            $retard = $jours_restants . ' jours restants';
        }
    }

} catch (PDOException $e) {
    header("Location: gestion_emprunts.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails emprunt | Bibliothèque Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-light: #34495e;
            --secondary: #1cc88a;
            --danger: #e74a3b;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
        }
        
        .detail-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .book-img {
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .member-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .badge-status {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>Détails de l'emprunt
            </h2>
            <a href="gestion_emprunts.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>

        <div class="detail-container">
            <div class="row">
                <!-- Colonne Livre -->
                <div class="col-md-5">
                    <div class="d-flex align-items-center mb-4">
                        <img src="../images/<?= htmlspecialchars($emprunt['image'] ?? 'default.jpg') ?>" 
                             class="book-img me-4" 
                             alt="<?= htmlspecialchars($emprunt['titre']) ?>">
                        <div>
                            <h4><?= htmlspecialchars($emprunt['titre']) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($emprunt['auteur']) ?></p>
                            <span class="badge bg-primary">ID: <?= $emprunt['id_livre'] ?></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Dates</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label">Date emprunt:</span>
                            <span><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="info-label">Date retour prévue:</span>
                            <span><?= date('d/m/Y', strtotime($emprunt['date_retour'])) ?></span>
                        </div>
                        <?php if ($emprunt['date_retour_reel']): ?>
                        <div class="d-flex justify-content-between">
                            <span class="info-label">Date retour effectif:</span>
                            <span><?= date('d/m/Y', strtotime($emprunt['date_retour_reel'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Colonne Adhérent -->
                <div class="col-md-4">
                    <h5 class="mb-3"><i class="fas fa-user me-2"></i>Adhérent</h5>
                    <div class="d-flex align-items-center mb-3">
                        <div class="member-avatar me-3">
                            <?= strtoupper(substr($emprunt['prenom'], 0, 1) . substr($emprunt['nom'], 0, 1)) ?>
                        </div>
                        <div>
                            <h6><?= htmlspecialchars($emprunt['prenom'] . ' ' . $emprunt['nom']) ?></h6>
                            <span class="badge bg-secondary">ID: <?= $emprunt['id_adherent'] ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="info-label">Email:</span>
                        <p><?= htmlspecialchars($emprunt['email']) ?></p>
                    </div>
                    <div class="mb-3">
                        <span class="info-label">Téléphone:</span>
                        <p><?= htmlspecialchars($emprunt['telephone']) ?></p>
                    </div>
                </div>

                <!-- Colonne Statut -->
                <div class="col-md-3">
                    <h5 class="mb-3"><i class="fas fa-chart-line me-2"></i>Statut</h5>
                    <div class="mb-4">
                        <?php if ($statut === 'Retourné'): ?>
                            <span class="badge bg-success badge-status"><?= $statut ?></span>
                            <p class="mt-2"><?= $retard ?></p>
                        <?php elseif ($statut === 'En retard'): ?>
                            <span class="badge bg-danger badge-status"><?= $statut ?></span>
                            <p class="mt-2"><?= $retard ?></p>
                        <?php else: ?>
                            <span class="badge bg-primary badge-status"><?= $statut ?></span>
                            <p class="mt-2"><?= $retard ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!$emprunt['date_retour_reel']): ?>
                    <div class="d-grid gap-2">
                        <a href="?retourner=<?= $emprunt['id'] ?>" class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Marquer comme retourné
                        </a>
                        <a href="mailto:<?= htmlspecialchars($emprunt['email']) ?>" class="btn btn-warning">
                            <i class="fas fa-envelope me-1"></i> Contacter
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

           
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>