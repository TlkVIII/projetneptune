<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validation
    if (empty($nom)) $errors[] = "Le nom est requis";
    if (empty($prenom)) $errors[] = "Le prénom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    if (empty($password)) $errors[] = "Le mot de passe est requis";
    if ($password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas";

    if (empty($errors)) {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé";
            } else {
                // Créer le compte
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, 'client')");
                $stmt->execute([$nom, $prenom, $email, $hashed_password]);

                // Connecter l'utilisateur
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_role'] = 'client';
                $_SESSION['user_name'] = $prenom . ' ' . $nom;

                header('Location: index.php');
                exit();
            }
        } catch(PDOException $e) {
            $errors[] = "Une erreur technique est survenue lors de l'inscription. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Hôtel Neptune</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }

        .register-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .register-form {
            padding: 2rem;
        }

        .form-control {
            border-radius: 5px;
            padding: 0.8rem;
            margin-bottom: 1rem;
        }

        .btn-register {
            background: var(--secondary-color);
            border: none;
            padding: 0.8rem;
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: -0.5rem;
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h3><i class="fas fa-hotel me-2"></i>Hôtel Neptune</h3>
            <p class="mb-0">Créer un compte</p>
        </div>
        <div class="register-form">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group password-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" class="btn password-toggle-btn" data-toggle-target="password" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-requirements">
                        Le mot de passe doit contenir au moins 8 caractères
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                    <div class="input-group password-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="btn password-toggle-btn" data-toggle-target="confirm_password" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-register">S'inscrire</button>
            </form>

            <div class="login-link">
                <p class="mb-0">Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.password-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const inputId = btn.getAttribute('data-toggle-target');
                const input = document.getElementById(inputId);
                const icon = btn.querySelector('i');
                if (!input || !icon) return;

                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                icon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
                btn.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            });
        });
    </script>
</body>
</html> 