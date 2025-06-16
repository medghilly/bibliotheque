<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = cleanInput($_POST['nom']);
    $email = cleanInput($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Cet email est déjà utilisé par un administrateur";
    } else {
        // Créer le nouvel admin
        $stmt = $conn->prepare("INSERT INTO admins (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        if ($stmt->execute([$nom, $email, $password])) {
            $_SESSION['admin_id'] = $conn->lastInsertId();
            $_SESSION['admin_nom'] = $nom;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Une erreur s'est produite lors de l'inscription";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Admin | Bibliothèque RT</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
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
        
        .form-floating label {
            padding-left: 45px;
            color: var(--gray);
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--success), #218838);
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
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
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
        
        .brand-logo {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .password-strength {
            margin-top: -10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: var(--gray);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="brand-logo">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2>Création de compte Admin</h2>
            </div>
            
            <div class="register-body">
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="registerForm">
                    <div class="form-floating mb-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom complet" required>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-2">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required minlength="8">
                        </div>
                        <div class="password-strength">
                            <i class="fas fa-info-circle"></i> Le mot de passe doit contenir au moins 8 caractères
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-register mb-4">
                        <i class="fas fa-user-plus me-2"></i> Créer le compte
                    </button>
                    
                    <div class="login-link">
                        <p>Déjà un compte ? <a href="admin_login.php">Se connecter</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation simple du mot de passe
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                alert('Le mot de passe doit contenir au moins 8 caractères');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>