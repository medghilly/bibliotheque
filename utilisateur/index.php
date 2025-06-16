<?php
session_start();
require_once __DIR__  . '/../db/config.php';

// Récupération des 6 derniers livres ajoutés
$livres = $conn->query("SELECT * FROM livres ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des catégories pour le filtre
$categories = $conn->query("SELECT DISTINCT categorie FROM livres WHERE categorie IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil | Bibliothèque RT</title>
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
        
        /* Navigation */
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.05rem;
        }
        
        /* Hero Section */
        .hero-section {
            background: url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1890&q=80') no-repeat center center;
            background-size: cover;
            padding: 6rem 0;
            color: white;
            text-align: center;
            position: relative;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, rgba(44, 62, 80, 0.8), rgba(44, 62, 80, 0.6));
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .hero-subtitle {
            font-weight: 300;
            margin-bottom: 2rem;
            opacity: 0.9;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        /* Search & Filter */
        .search-container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .search-input {
            border-radius: 30px;
            padding: 0.8rem 1.5rem;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .search-btn {
            border-radius: 30px;
            padding: 0.8rem 1.8rem;
            margin-left: -50px;
            border: none;
            background: linear-gradient(135deg, var(--secondary), #c0392b);
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            transform: translateX(5px);
        }
        
        .filter-btn {
            border-radius: 20px;
            margin: 0.3rem;
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: white;
            color: var(--primary);
            border-color: white;
        }
        
        /* Books Section */
        .books-section {
            padding: 4rem 0;
        }
        
        .section-title {
            font-weight: 600;
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--secondary);
        }
        
        .book-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            height: 100%;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .book-cover {
            height: 280px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.5s;
        }
        
        .book-card:hover .book-cover {
            transform: scale(1.05);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .book-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .book-author {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .badge-category {
            background-color: var(--primary-light);
            color: white;
            font-weight: 500;
            padding: 0.4rem 0.8rem;
        }
        
        .btn-details {
            background-color: var(--primary);
            color: white;
            border-radius: 30px;
            padding: 0.5rem 1.2rem;
            transition: all 0.3s;
        }
        
        .btn-details:hover {
            background-color: var(--primary-light);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Features Section */
        .features-section {
            background-color: var(--primary);
            color: white;
            padding: 4rem 0;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--secondary);
        }
        
        .feature-title {
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        /* Footer */
        footer {
            background-color: var(--primary-light);
            color: white;
            padding: 3rem 0 1.5rem;
        }
        
        .footer-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--secondary);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }
        
        .social-icons a {
            color: white;
            font-size: 1.2rem;
            margin-right: 1rem;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            color: var(--secondary);
            transform: translateY(-3px);
        }
        
        .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 2rem;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 4rem 0;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .book-cover {
                height: 200px;
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
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="text-white me-3 d-none d-md-block align-self-center">
                        Bonjour, <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
                    </span>
                    <a class="navbar-brand" href="../utilisateur/index.php">
                        <i class="fas fa-home me-1"></i> 
                    </a>
                    <a href="mon_compte.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-user"></i>
                        <span class="d-none d-md-inline">Mon compte</span>
                    </a>
                
                    <a href="logout.php" class="btn btn-light">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-none d-md-inline">Déconnexion</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="d-none d-md-inline">Connexion</span>
                    </a>
                    <a href="register.php" class="btn btn-light">
                        <i class="fas fa-user-plus"></i>
                        <span class="d-none d-md-inline">Inscription</span>
                    </a>
                  
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <h1 class="hero-title animate">Bienvenue à la Bibliothèque RT</h1>
            <p class="hero-subtitle lead animate delay-1">Découvrez notre collection exceptionnelle de livres</p>
            
            <div class="search-container animate delay-2">
                <div class="input-group mb-4">
                    <input type="text" id="searchInput" class="form-control search-input" placeholder="Rechercher un livre...">
                    <button class="btn btn-primary search-btn" type="button" id="searchButton">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div class="filters animate delay-3">
                    <button class="btn btn-sm btn-outline-light filter-btn active" data-category="all">Tous</button>
                    <?php foreach($categories as $categorie): ?>
                        <button class="btn btn-sm btn-outline-light filter-btn" data-category="<?= htmlspecialchars($categorie) ?>">
                            <?= htmlspecialchars($categorie) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Books Section -->
    <section class="books-section">
        <div class="container">
            <h2 class="section-title text-center">Nos dernières acquisitions</h2>
            
            <div class="row" id="booksContainer">
                <?php foreach($livres as $livre): ?>
                <div class="col-lg-4 col-md-6 book-item" 
                     data-category="<?= htmlspecialchars($livre['categorie'] ?? '') ?>"
                     data-title="<?= htmlspecialchars(strtolower($livre['titre'])) ?>">
                    <div class="book-card card">
                       <img src="../images/<?= htmlspecialchars($livre['image'] ?? 'default.jpg') ?>"
                             class="book-cover card-img-top" 
                             alt="<?= htmlspecialchars($livre['titre']) ?>">
                        <div class="card-body">
                            <h5 class="book-title"><?= htmlspecialchars($livre['titre']) ?></h5>
                            <p class="book-author"><?= htmlspecialchars($livre['auteur']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge badge-category"><?= htmlspecialchars($livre['categorie']) ?></span>
                                <a href="../livre/details.php?id=<?= $livre['id'] ?>" class="btn btn-details">
                                    <i class="fas fa-info-circle me-1"></i> Détails
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="../livre/livres.php" class="btn btn-primary btn-lg px-4 py-2">
                    <i class="fas fa-book me-2"></i> Voir tous les livres
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-5 mb-md-0">
                    <div class="feature-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3 class="feature-title">Large sélection</h3>
                    <p>Des milliers de livres couvrant tous les genres et sujets</p>
                </div>
                <div class="col-md-4 mb-5 mb-md-0">
                    <div class="feature-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <h3 class="feature-title">Horaires flexibles</h3>
                    <p>Ouvert 6 jours sur 7 pour s'adapter à votre emploi du temps</p>
                </div>
                <div class="col-md-4">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 class="feature-title">Support client</h3>
                    <p>Une équipe dédiée pour répondre à toutes vos questions</p>
                </div>
            </div>
        </div>
    </section>
   <?php $travaux = $conn->query("SELECT * FROM travaux ORDER BY date_publication DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
$seminaires = $conn->query("SELECT * FROM seminaires ORDER BY date_event ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$annonces = $conn->query("SELECT * FROM annonces ORDER BY date_publication DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$galerie = $conn->query("SELECT * FROM galerie ORDER BY date_upload DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Travaux étudiants -->
<section class="books-section">
    <div class="container">
        <h2 class="section-title text-center">Travaux des étudiants</h2>
        <div class="row">
            <?php foreach ($travaux as $t): ?>
            <div class="col-md-4">
                <div class="book-card card">
                    <div class="card-body">
                        <h5 class="book-title"><?= htmlspecialchars($t['titre']) ?></h5>
                        <p class="book-author">Par <?= htmlspecialchars($t['auteur']) ?></p>
                        <a href="../travaux/fichiers/<?= htmlspecialchars($t['fichier']) ?>" class="btn btn-details" target="_blank">
                            📄 Télécharger
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Séminaires -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title text-center text-white">Séminaires à venir</h2>
        <div class="row">
            <?php foreach ($seminaires as $s): ?>
            <div class="col-md-4 mb-4">
                <div class="card bg-light text-dark h-100">
                    <img src="../images/<?= htmlspecialchars($s['image']) ?? 'default.jpg'?>" class="card-img-top" alt="<?= $s['titre'] ?>" style="height:200px; object-fit:cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($s['titre']) ?></h5>
                        <p class="card-text"><strong>Intervenant:</strong> <?= $s['intervenant'] ?></p>
                        <p class="card-text"><strong>Date:</strong> <?= date("d/m/Y", strtotime($s['date_event'])) ?></p>
                        <p><?= htmlspecialchars($s['description']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Galerie -->
<section class="books-section">
    <div class="container">
        <h2 class="section-title text-center">Galerie d’événements</h2>
        <div class="row">
            <?php foreach ($galerie as $g): ?>
            <div class="col-md-4 mb-4">
                <div class="book-card card">
                    <img src="../images/<?= htmlspecialchars($g['image']) ?>" class="book-cover card-img-top" alt="<?= htmlspecialchars($g['titre']) ?>">
                    <div class="card-body">
                        <h5 class="book-title"><?= htmlspecialchars($g['titre']) ?></h5>
                        <p class="book-author"><?= htmlspecialchars($g['categorie']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Annonces -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title text-center text-white">Annonces & Publicités</h2>
        <div class="row">
            <?php foreach ($annonces as $a): ?>
            <div class="col-md-4 mb-4">
                <div class="card bg-light text-dark h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($a['titre']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars(substr($a['contenu'], 0, 120)) ?>...</p>
                        <p class="text-muted">Publié le <?= date("d/m/Y", strtotime($a['date_publication'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <h4 class="footer-title">Bibliothèque RT</h4>
                    <p>Votre portail vers la connaissance et la culture depuis 2023.</p>
                    <div class="social-icons mt-4">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <h4 class="footer-title">Horaires</h4>
                    <ul class="footer-links">
                        <li>Lundi - Vendredi: 9h - 18h</li>
                        <li>Samedi: 10h - 17h</li>
                        <li>Dimanche: Fermé</li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h4 class="footer-title">Contact</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Rue de L'ISCAE, Nouakchoot</li>
                        <li><i class="fas fa-phone me-2"></i>+222 46071882</li>
                        <li><i class="fas fa-envelope me-2"></i> contact@bibliotheque-rt.fr</li>
                    </ul>
                </div>
            </div>
            <div class="copyright text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Bibliothèque RT. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filtrage des livres
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.dataset.category;
                const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                
                filterBooks(category, searchTerm);
            });
        });
        
        // Recherche de livres
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const activeCategory = document.querySelector('.filter-btn.active').dataset.category;
            
            filterBooks(activeCategory, searchTerm);
        });
        
        function filterBooks(category, searchTerm) {
            document.querySelectorAll('.book-item').forEach(item => {
                const itemCategory = item.dataset.category;
                const itemTitle = item.dataset.title;
                
                const categoryMatch = category === 'all' || itemCategory.includes(category);
                const searchMatch = itemTitle.includes(searchTerm);
                
                if (categoryMatch && searchMatch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>