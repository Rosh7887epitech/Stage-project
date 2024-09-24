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
    die("Connexion échouée: " . pg_last_error()); // Affiche un message d'erreur si la connexion échoue
}

// Requête SQL pour sélectionner 10 enregistrements aléatoires
$sql = "SELECT * FROM a_catalog.all_info_catalog ORDER BY RANDOM() LIMIT 10";
$result = pg_query($conn, $sql); // Exécution de la requête SQL

// Vérification du résultat de la requête
if ($result) {
    // Parcours des résultats de la requête
    while ($row = pg_fetch_assoc($result)) {
        // Récupération et échappement des valeurs des colonnes
        $schema = htmlspecialchars($row["all_schemas_names"]);
        $table = htmlspecialchars($row["all_tables_names"]);
        $lisible_name = htmlspecialchars($row["nom_lisible"]);
        $desc = htmlspecialchars($row["description"]);
        $theme_res = htmlspecialchars($row["theme"]);
        
        // Création d'une description courte si nécessaire
        $short_desc = substr($desc, 0, 100);
        if (strlen($desc) > 100) {
            $short_desc .= '...'; // Ajout de points de suspension si la description est trop longue
        }
        
        // Affichage des résultats dans un format HTML
        echo "<div class='result'>";
        echo "<h2><a href='detail.php?schema=" . urlencode($schema) . "&table=" . urlencode($table) . "' class='no-link-style'>" . $lisible_name . "</a></h2>";
        echo "<p><span class='label'>Thème:</span> " . $theme_res . "</p>";
        echo "<p><span class='label'>Description:</span> " . $short_desc . "</p>";
        echo "</div>";
    }
} else {
    // Affichage d'un message d'erreur si la requête échoue
    echo "Erreur dans la requête SQL : " . pg_last_error($conn);
}

// Fermeture de la connexion à la base de données
pg_close($conn);
?>
