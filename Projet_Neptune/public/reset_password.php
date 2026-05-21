<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caracteres.";
    } else {
        try {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatedRows = 0;

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE LOWER(TRIM(email)) = ?");
            $stmt->execute([$newHash, $email]);
            $updatedRows += $stmt->rowCount();

            try {
                $stmtLegacy = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE LOWER(TRIM(email)) = ?");
                $stmtLegacy->execute([$newHash, $email]);
                $updatedRows += $stmtLegacy->rowCount();
            } catch (PDOException $e) {
                // Compatibilite: la table legacy peut ne pas exister.
            }

            if ($updatedRows > 0) {
                $success = "Mot de passe reinitialise avec succes. Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Aucun compte trouve avec cet email.";
            }
        } catch (PDOException $e) {
            $error = "Une erreur technique est survenue. Veuillez reessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reinitialiser le mot de passe - Hotel Neptune</title>
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

        .reset-container {
            max-width: 450px;
            margin: 80px auto;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: #fff;
            text-align: center;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
        }

        .password-group .form-control {
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
    <div class="container reset-container">
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0"><i class="fas fa-key me-2"></i>Reinitialiser le mot de passe</h1>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email du compte</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <div class="input-group password-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <button type="button" class="btn password-toggle-btn" data-toggle-target="new_password" aria-label="Afficher le mot de passe">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                        <div class="input-group password-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                            <button type="button" class="btn password-toggle-btn" data-toggle-target="confirm_password" aria-label="Afficher le mot de passe">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Mettre a jour le mot de passe</button>
                </form>

                <div class="mt-3 text-center">
                    <a href="login.php"><i class="fas fa-arrow-left me-1"></i>Retour a la connexion</a>
                </div>
            </div>
        </div>
    </div>
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
