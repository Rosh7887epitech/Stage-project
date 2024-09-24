<?php
// Informations de connexion à la base de données
$host = "SV-PROD-202"; // Nom de l'hôte du serveur de base de données
$port = "5432"; // Port de connexion à la base de données
$dbname = "geo_test"; // Nom de la base de données
$user = "postgres"; // Nom d'utilisateur pour la connexion
$password = "-u}488LG59qw;1"; // Mot de passe pour la connexion

// Connexion à la base de données PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Vérification de la connexion
if (!$conn) {
    die("Connexion échouée: " . pg_last_error()); // Affiche un message d'erreur si la connexion échoue
}

// Récupération des thèmes uniques
$theme_query = "SELECT DISTINCT theme FROM a_catalog.all_info_catalog WHERE theme IS NOT NULL";
$theme_result = pg_query($conn, $theme_query);

if (!$theme_result) {
    die("Erreur lors de la récupération des thèmes: " . pg_last_error()); // Affiche un message d'erreur si la requête échoue
}

$themes = [];
while ($row = pg_fetch_assoc($theme_result)) {
    if (!empty($row['theme'])) {
        $themes[] = htmlspecialchars($row['theme']); // Ajoute le thème à la liste après l'avoir échappé
    }
}

// Fermeture de la connexion à la base de données
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche dans le Catalogue</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Styles de base pour le corps de la page */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        /* Centrer le formulaire de recherche */
        .search-form {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            padding: 10px;
        }
        /* Styles pour les boutons dans la barre de recherche */
        .search-form button {
            padding: 10px 15px;
            font-size: 16px;
            border: none;
            background-color: #1e46b5;
            color: white;
            border-radius: 21px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Ajout de l'ombre */
        }
        /* Styles pour les boutons au survol */
        .search-form button:hover {
            background-color: #183a97;
        }
        /* Agrandir la barre de recherche */
        .search-form input[type="text"] {
            flex: 3;
            padding: 10px;
            font-size: 13px;
            border: 1px solid #ccc;
            border-radius: 21px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .search-form select {
            flex: 0.5;
            padding: 10px;
            font-size: 13px;
            border: 1px solid #ccc;
            border-radius: 21px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        /* Styles pour le conteneur principal */
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 21px;
            margin-top: 50px;
        }
        /* Styles pour les résultats de recherche */
        .result {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 21px;
            border-color: #1e46b5;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        /* Styles pour les titres des résultats */
        .result h2 {
            color: #1e46b5;
            margin-top: 1px;
            margin-bottom: 5px;
        }
        /* Styles pour les paragraphes des résultats */
        .result p {
            color: #555;
            margin: 5px 0;
        }
        /* Styles pour les étiquettes des résultats */
        .result .label {
            font-weight: bold;
            color: #333;
        }
        /* Styles pour les liens sans style */
        .no-link-style {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        /* Styles pour la checkbox et son label */
        .search-form label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #555;
            cursor: pointer;
        }

        .search-form input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border: 1px solid #ccc;
            border-radius: 4px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            outline: none;
            cursor: pointer;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .search-form input[type="checkbox"]::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 12px;
            height: 12px;
            background-color: #1e46b5;
            border-radius: 2px;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.2s ease;
        }

        .search-form input[type="checkbox"]:checked::before {
            transform: translate(-50%, -50%) scale(1);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #1e46b5;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #1e46b5;
        }
    </style>
    <script>
        // Fonction de recherche AJAX
        function search() {
            // Récupération des valeurs des champs de recherche
            var query = document.querySelector('input[name="query"]').value;
            var filter = document.querySelector('select[name="filter"]').value;
            var theme = document.querySelector('select[name="theme"]').value;
            var includeSDIG = document.querySelector('input[name="includeSDIG"]').checked;

            // Création d'une requête AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'recherche.php?query=' + encodeURIComponent(query) + '&filter=' + encodeURIComponent(filter) + '&theme=' + encodeURIComponent(theme) + '&includeSDIG=' + includeSDIG, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        // Mise à jour des résultats de recherche
                        document.querySelector('.results').innerHTML = xhr.responseText;
                    } else {
                        console.error('Erreur lors de la requête AJAX');
                    }
                }
            };
            xhr.send();
            return false;
        }
    </script>
</head>
<body>
    <form onsubmit="return search();" class="search-form">
        <input type="text" name="query" placeholder="Rechercher dans le catalogue..." aria-label="Rechercher une donnée">
        <button type="submit">Chercher</button>
        <select name="filter">
            <option value="all">Tous les types</option>
            <option value="nom_lisible">Nom</option>
            <option value="all_schemas_names">Nom du schema</option>
            <option value="all_tables_names">Nom de la table</option>
            <option value="all_categories">Catégories</option>
            <option value="description">Description</option>
        </select>
        <select name="theme">
            <option value="all">Tous les thèmes</option>
            <?php foreach ($themes as $theme): ?>
                <option value="<?php echo $theme; ?>"><?php echo $theme; ?></option>
            <?php endforeach; ?>
        </select>
        <label>
            <input type="checkbox" name="includeSDIG"> Data SDIG
        </label>
    </form>
    <div class="container">
        <div class="results">
            <?php include 'random.php'; ?>
        </div>
    </div>
    <div style="text-align: center; margin-top: 20px;">
        <a href="data_sensible.php" class="btn">Accéder aux données sensibles</a>
    </div>
</body>
</html>
