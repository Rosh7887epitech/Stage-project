<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Données Sensibles</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .data, .form-container {
            margin-top: 20px;
        }
        .data p, .form-container p {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-container input[type="text"], .form-container input[type="submit"] {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        .form-container input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Données Sensibles</h1>
        <div class="form-container">
            <form method="post" action="">
                <p>Veuillez entrer le code PIN pour accéder aux données sensibles :</p>
                <input type="text" name="pin" placeholder="Entrez le code PIN" required>
                <input type="submit" value="Valider">
            </form>
        </div>
        <div class="data">
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $enteredPin = $_POST['pin'];
                $correctPin = "0508"; // Remplacez par le code PIN réel

                if ($enteredPin === $correctPin) {
                    // Afficher les données sensibles
                    $sensitiveData = [
                        "Nom" => "Robin SCHUFFENECKER",
                        "E-mail" => "robin.schuffenecker@epitech.eu",
                        "GitHub" => "https://github.com/Rosh7887epitech/Stage-project",
                        "URL" => "https://youtu.be/dQw4w9WgXcQ?si=RdR9SfnbUMztZT44"
                    ];

                    foreach ($sensitiveData as $key => $value) {
                        echo "<p><strong>$key:</strong> $value</p>";
                    }
                } else {
                    echo "<p style='color: red;'>Code PIN incorrect. Veuillez réessayer.</p>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
