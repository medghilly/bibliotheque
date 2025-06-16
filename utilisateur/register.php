<?php
session_start();
require_once __DIR__ . '/../db/config.php';

// Redirection si déjà connecté
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif (!preg_match('/@(gmail\.com|email\.com)$/', $email)) {
        $error = "Seules les adresses @gmail.com et @email.com sont acceptées.";
    } elseif (!preg_match('/^\d{8}$/', $telephone)) {
        $error = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            // Vérification si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM adherents WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Hashage du mot de passe
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertion dans la base de données
                $stmt = $conn->prepare("INSERT INTO adherents (nom, prenom, email, telephone, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $email, $telephone, $password_hash]);
                
                // Récupération de l'ID du nouvel utilisateur
                $user_id = $conn->lastInsertId();
                
                // Connexion automatique après inscription
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $prenom . ' ' . $nom;
                
                $success = "Inscription réussie ! Redirection...";
                header("Refresh: 2; url=index.php");
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription | Bibliothèque RT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-light: #34495e;
            --secondary: #e74c3c;
            --success: #28a745;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background:url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1890&q=80') no-repeat center center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .register-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: white;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-header h2 {
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .brand-logo {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .register-body {
            padding: 2rem;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding-left: 20px;
            margin-bottom: 1.5rem;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary);
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            height: 50px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.2);
        }
        
        .error-message {
            background-color: #fff5f5;
            color: #f03e3e;
            border-left: 4px solid #f03e3e;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
        }
        
        .login-link a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: var(--secondary);
        }
        
        .password-strength {
            height: 5px;
            background: #eee;
            margin-top: -15px;
            margin-bottom: 20px;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        .password-wrapper, .phone-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
        }
        
        .phone-prefix {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-weight: 500;
        }
        
        .phone-input {
            padding-left: 50px !important;
        }
        
        .alert-success {
            border-left: 4px solid var(--success);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="brand-logo">
                    <i class="fas fa-book-open"></i>
                </div>
                <h2>Création de compte</h2>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="registerForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                       value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" placeholder="Prénom" required>
                                <label for="prenom">Prénom</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" placeholder="Nom" required>
                                <label for="nom">Nom</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-floating">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="" required>
                            </div>
                            <label for="email" style="padding-left: 45px;">Adresse Email</label>
                        </div>
                        <small class="text-muted">Format accepté: @gmail.com ou @email.com</small>
                    </div>
                    
                    <div class="mb-3 phone-wrapper">
                        <div class="form-floating">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control phone-input" id="telephone" name="telephone" 
                                       value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" 
                                       placeholder="" 
                                       pattern="\d{8}" 
                                       maxlength="8" required>
                            </div>
                            <label for="telephone" style="padding-left: 45px;">Téléphone</label>
                        </div>
                        <small class="text-muted">8 chiffres sans espaces </small>
                    </div>
                    
                    <div class="mb-3 password-wrapper">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                            <label for="password">Mot de passe</label>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                        <small class="text-muted">Minimum 8 caractères</small>
                    </div>
                    
                    <div class="mb-3 password-wrapper">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-register mb-4">
                        <i class="fas fa-user-plus me-2"></i> S'inscrire
                    </button>
                    
                    <div class="login-link">
                        <p>Déjà membre ? <a href="login.php">Connectez-vous</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher/masquer le mot de passe
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.querySelector(`#${fieldId} + .password-toggle i`);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        // Indicateur de force du mot de passe
        document.getElementById('password').addEventListener('input', function() {
            const strengthBar = document.getElementById('password-strength-bar');
            const strength = calculatePasswordStrength(this.value);
            
            if (strength < 30) {
                strengthBar.style.backgroundColor = '#dc3545';
                strengthBar.style.width = strength + '%';
            } else if (strength < 70) {
                strengthBar.style.backgroundColor = '#ffc107';
                strengthBar.style.width = strength + '%';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
                strengthBar.style.width = strength + '%';
            }
        });
        
        function calculatePasswordStrength(password) {
            let strength = 0;
            
            // Longueur
            strength += Math.min(password.length * 5, 50);
            
            // Diversité des caractères
            if (password.match(/[a-z]/)) strength += 10;
            if (password.match(/[A-Z]/)) strength += 10;
            if (password.match(/[0-9]/)) strength += 10;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
            
            return Math.min(strength, 100);
        }
        
        // Validation du numéro de téléphone
        document.getElementById('telephone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 8);
        });
        
        // Validation du formulaire côté client
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            if (!email.match(/@(gmail\.com|email\.com)$/)) {
                alert("Seules les adresses @gmail.com et @email.com sont acceptées.");
                e.preventDefault();
            }
            
            const phone = document.getElementById('telephone').value;
            if (!phone.match(/^\d{8}$/)) {
                alert("Le numéro de téléphone doit contenir exactement 8 chiffres.");
                e.preventDefault();
            }
            
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                alert("Le mot de passe doit contenir au moins 8 caractères.");
                e.preventDefault();
            }
            
            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                alert("Les mots de passe ne correspondent pas.");
                e.preventDefault();
            }
        });
    </script>
</body>
</html>