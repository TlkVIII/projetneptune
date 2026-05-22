<?php
session_start();
require_once '../config/database.php';

$error = '';
if (isset($_SESSION['reservation_error'])) {
    $error = $_SESSION['reservation_error'];
    unset($_SESSION['reservation_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        try {
            // Compatibilite avec les deux schemas utilises dans le projet.
            $stmt = $pdo->prepare("SELECT id, nom, prenom, email, password, role FROM users WHERE LOWER(TRIM(email)) = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $passwordField = 'password';
            $sourceTable = 'users';
            
            if (!$user) {
                try {
                    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, role FROM utilisateurs WHERE LOWER(TRIM(email)) = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    $passwordField = 'mot_de_passe';
                    $sourceTable = 'utilisateurs';
                } catch (PDOException $e) {
                    // La table `utilisateurs` peut ne pas exister selon la version du schema.
                }
            }
            
            if ($user) {
                // Vérifier le mot de passe avec password_verify
                $storedPassword = $user[$passwordField] ?? '';
                $isHashed = is_string($storedPassword) && preg_match('/^\$2[aby]\$|\$argon2/i', $storedPassword);
                $passwordOk = false;
                
                if (!empty($storedPassword)) {
                    if ($isHashed) {
                        $passwordOk = password_verify($password, $storedPassword);
                    } else {
                        // Compatibilite avec d'eventuelles anciennes donnees non hachees.
                        $passwordOk = hash_equals((string) $storedPassword, (string) $password);
                        if ($passwordOk) {
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            if ($sourceTable === 'users') {
                                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            } else {
                                $updateStmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                            }
                            $updateStmt->execute([$newHash, $user['id']]);
                        }
                    }
                }
                
                if ($passwordOk) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                    $_SESSION['user_role'] = $user['role'] ?? 'client';
                    
                    // Rediriger vers la page d'accueil ou le tableau de bord admin
                    if ($user['role'] === 'admin') {
                        header('Location: admin/index.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $error = "Email ou mot de passe incorrect";
                }
            } else {
                $error = "Email ou mot de passe incorrect. Vérifie l'email saisi.";
            }
        } catch (PDOException $e) {
            $error = "Une erreur technique est survenue. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color);
        }
        
        .form-control {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .password-group .form-control {
            margin-bottom: 0;
            border-right: 0;
            border-radius: 10px 0 0 10px;
        }

        .password-toggle-btn {
            min-width: 56px;
            border: 1px solid #dbe4f0;
            border-left: 0;
            border-radius: 0 10px 10px 0;
            background: #f4f8ff;
            color: var(--primary-color);
            transition: all 0.2s ease;
        }

        .password-toggle-btn:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            color: #fff;
        }

        .password-toggle-btn:focus {
            box-shadow: none;
            outline: none;
        }

        .password-toggle-btn:active {
            transform: scale(0.98);
        }
        
        .login-title {
            font-size: 24px;
            margin-bottom: 0;
        }
        
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header">
                <h1 class="login-title"><i class="fas fa-user me-2"></i>Connexion</h1>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group password-group">
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                            <button type="button" class="btn password-toggle-btn" id="togglePassword" aria-label="Afficher le mot de passe" title="Afficher / masquer le mot de passe">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
                    <p><a href="reset_password.php">Mot de passe oublie ?</a></p>
                    <p><a href="index.php"><i class="fas fa-home me-1"></i>Retour à l'accueil</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function fillLogin(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }

        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = togglePasswordBtn ? togglePasswordBtn.querySelector('i') : null;

        if (togglePasswordBtn && passwordInput && toggleIcon) {
            togglePasswordBtn.addEventListener('click', function () {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                toggleIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
                togglePasswordBtn.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            });
        }
    </script>
</body>
</html> 
