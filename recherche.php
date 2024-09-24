<?php
// Informations de connexion à la base de données
$host = ""; // Nom de l'hôte du serveur de base de données
$port = ""; // Port de connexion à la base de données
$dbname = ""; // Nom de la base de données
$user = ""; // Nom d'utilisateur pour la connexion
$password = ""; // Mot de passe pour la connexion

// Connexion à la base de données PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Vérification de la connexion
if (!$conn) {
    die("Connexion échouée: " . pg_last_error());
}

// Récupération des paramètres de la requête GET
$query = isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '';
$filter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';
$theme = isset($_GET['theme']) ? htmlspecialchars($_GET['theme']) : 'all';
$includeSDIG = isset($_GET['includeSDIG']) ? $_GET['includeSDIG'] === 'true' : false;

// Construction de la requête SQL
$sql = "SELECT * FROM a_catalog.all_info_catalog WHERE 1=1";
$params = array();

// Ajouter la condition de recherche si la requête n'est pas vide
if (!empty($query)) {
    $words = array_filter(explode(' ', $query), function($word) {
        return !empty($word) && preg_match('/^[\\p{L}\\p{N}_]+$/u', $word);
    });

    if (!empty($words)) {
        $search_query = implode(' & ', array_map(function($word) {
            return $word . ':*';
        }, $words));

        if ($filter != 'all') {
            $sql .= " AND to_tsvector('french', unaccent(lower($filter))) @@ to_tsquery('french', unaccent(lower($1)))";
            $params[] = $search_query;
        } else {
            $sql .= " AND (to_tsvector('french', unaccent(lower(all_schemas_names))) @@ to_tsquery('french', unaccent(lower($1))) 
                    OR to_tsvector('french', unaccent(lower(all_tables_names))) @@ to_tsquery('french', unaccent(lower($1))) 
                    OR to_tsvector('french', unaccent(lower(all_categories))) @@ to_tsquery('french', unaccent(lower($1))) 
                    OR to_tsvector('french', unaccent(lower(description))) @@ to_tsquery('french', unaccent(lower($1))) 
                    OR to_tsvector('french', unaccent(lower(location))) @@ to_tsquery('french', unaccent(lower($1))) 
                    OR to_tsvector('french', unaccent(lower(nom_lisible))) @@ to_tsquery('french', unaccent(lower($1))))";
            $params[] = $search_query;
        }
    }
}

// Ajouter la condition de filtre de thème si un thème spécifique est sélectionné
if ($theme != 'all') {
    $sql .= " AND theme = $".(count($params) + 1);
    $params[] = $theme;
}

// Exclusion des données SDIG si nécessaire
if (!$includeSDIG) {
    $sql .= " AND droit != 'SDIG'";
}

// Enregistrement des logs pour la requête SQL et les paramètres
error_log("SQL Query: " . $sql);
error_log("Params: " . print_r($params, true));

// Exécution de la requête SQL avec les paramètres
$result = pg_query_params($conn, $sql, $params);

// Vérification du résultat de la requête
if ($result) {
    if (pg_num_rows($result) > 0) {
        while ($row = pg_fetch_assoc($result)) {
            $schema = htmlspecialchars($row["all_schemas_names"]);
            $table = htmlspecialchars($row["all_tables_names"]);
            $lisible_name = htmlspecialchars($row["nom_lisible"]);
            $desc = htmlspecialchars($row["description"]);
            $theme_res = htmlspecialchars($row["theme"]);
            $short_desc = substr($desc, 0, 100);
            if (strlen($desc) > 100) {
                $short_desc .= '...';
            }
            echo "<div class='result'>";
            if (!$includeSDIG) {
                echo "<h2><a href='detail.php?schema=" . urlencode($schema) . "&table=" . urlencode($table) . "' class='no-link-style'>" . $lisible_name . "</a></h2>";
            } else {
                echo "<h2><a href='detail.php?schema=" . urlencode($schema) . "&table=" . urlencode($table) . "' class='no-link-style'>" . $table . "</a></h2>";
            }
            echo "<p><span class='label'>Thème:</span> " . $theme_res . "</p>";
            echo "<p><span class='label'>Description:</span> " . $short_desc . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>Aucun résultat trouvé</p>";
    }
} else {
    echo "Erreur dans la requête SQL : " . pg_last_error($conn);
}

// Fermeture de la connexion à la base de données
pg_close($conn);
?>
