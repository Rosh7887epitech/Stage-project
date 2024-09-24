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
    // Si la connexion échoue, afficher un message d'erreur et arrêter l'exécution du script
    die("Connexion échouée: " . pg_last_error());
}
// Récupération des paramètres de la requête GET (ceux passés dans l'URL)
$schema = isset($_GET['schema']) ? htmlspecialchars($_GET['schema']) : ''; // Le schéma de la table
$table = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : ''; // Le nom de la table
// Requête SQL pour récupérer les informations de la table dans a_catalog.all_info_catalog
$sql_info = "SELECT * FROM a_catalog.all_info_catalog WHERE all_schemas_names = $1 AND all_tables_names = $2";
$result_info = pg_query_params($conn, $sql_info, array($schema, $table));
// Traitement des résultats de la requête pour les informations
$info = null;
if ($result_info) {
    // Si la requête réussit, récupérer les informations sous forme de tableau associatif
    $info = pg_fetch_assoc($result_info);
} else {
    // Si la requête échoue, afficher un message d'erreur et arrêter l'exécution du script
    die("Erreur dans la requête SQL pour les informations : " . pg_last_error($conn));
}
// Requête SQL pour récupérer les données géographiques de a_catalog.car_comm_cap
$sql_geo = "SELECT ST_AsGeoJSON(c.geom) as geojson 
            FROM a_catalog.car_comm_cap c
            JOIN (
                SELECT unnest(string_to_array(location, '/')) as ville
                FROM a_catalog.all_info_catalog
                WHERE all_schemas_names = $1 AND all_tables_names = $2
            ) a ON a.ville = c.nom";
$result_geo = pg_query_params($conn, $sql_geo, array($schema, $table));
// Traitement des résultats de la requête pour les données géographiques
$geojson = [];
if ($result_geo) {
    // Si la requête réussit, ajouter chaque résultat à un tableau
    while ($row_geo = pg_fetch_assoc($result_geo)) {
        $geojson[] = $row_geo["geojson"];
    }
} else {
    // Si la requête échoue, afficher un message d'erreur et arrêter l'exécution du script
    die("Erreur dans la requête SQL pour les données géographiques : " . pg_last_error($conn));
}
// Requête SQL pour récupérer les données de "Cap" si emprise = "Cap"
$geojson_cap = [];
if ($info['emprise'] === 'Cap') {
    $sql_cap = "SELECT ST_AsGeoJSON(geom) as geojson FROM a_catalog.car_comm_cap WHERE nom = 'Cap'";
    $result_cap = pg_query($conn, $sql_cap);

    if ($result_cap) {
        // Si la requête réussit, ajouter chaque résultat à un tableau
        while ($row_cap = pg_fetch_assoc($result_cap)) {
            $geojson_cap[] = $row_cap["geojson"];
        }
    } else {
        // Si la requête échoue, afficher un message d'erreur et arrêter l'exécution du script
        die("Erreur dans la requête SQL pour les données Cap : " . pg_last_error($conn));
    }
}
// Récupérer les catégories et les comparer avec les alias
$categories = explode(' / ', $info['all_categories']);
$alias_list = [];
foreach ($categories as $category) {
    $sql_alias = "SELECT alias_champ FROM a_catalog.alias_catalog WHERE nom_champ = $1";
    $result_alias = pg_query_params($conn, $sql_alias, array($category));
    if ($result_alias) {
        $alias = pg_fetch_assoc($result_alias);
        if ($alias) {
            $alias_list[] = $alias['alias_champ'];
        } else {
            $alias_list[] = $category; // Si aucun alias trouvé, utiliser le nom original
        }
    } else {
        $alias_list[] = $category; // Si la requête échoue, utiliser le nom original
    }
}
// Afficher les alias sous forme de liste séparée par des virgules
$alias_string = implode(', ', $alias_list);
// Fermeture de la connexion à la base de données
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la Table</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        /* Styles de base pour le corps de la page */
        body { 
            font-family: Arial, sans-serif; 
            background-color: #ffffff; 
            margin: 0; 
            padding: 0;
            background-image: url('log.png');
            background-size: 35%;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        /* Styles pour le conteneur principal */
        .container { 
            border: 1px solid #ccc;
            display: flex; 
            flex-direction: row; 
            justify-content: space-between; 
            width: 80%; 
            margin: 0 auto; 
            padding: 20px;
            border-color: #1e46b5;
            background-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
            border-radius: 21px;
            margin-top: 20px; 
            flex-wrap: wrap;
        }
        /* Styles pour la section des détails */
        .details { 
            flex: 2.5; 
            margin-right: 20px; 
            margin-top: 20px; 
        }
        /* Styles pour la carte */
        #map { 
            flex: 2.5; 
            height: 500px; 
            margin-top: 20px; 
            margin-left: 50px;
            border-radius: 21px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
        }
        .table-title {
            font-size: 2.5em;
            color: #333;
            text-align: center; 
            margin-top: 2px;
            margin-bottom: 20px;
            font-family: 'Arial', sans-serif;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        /* Styles pour le titre principal */
        h1 { 
            text-align: center; 
            width: 100%; 
            color: #333; 
        }
        /* Styles pour les résultats */
        .result { 
            border: 1px solid #ccc; 
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 21px;
            border-color: #1e46b5;
            background-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
        }
        /* Styles pour les titres des résultats */
        .result h2 { 
            color: #4CAF50; 
            margin-bottom: 15px; 
            margin-top: 2px; 
        }
        /* Styles pour les paragraphes des résultats */
        .result p { 
            color: #555; 
            margin: 5px 0; 
            margin-bottom: 20px; 
        }
        /* Styles pour les étiquettes des résultats */
        .result .label { 
            font-weight: bold; 
            color: #333; 
        }
        .result .title {
            text-align: left;
            font-size: large;
            font-weight: bold; 
            color: #333; 
            margin-bottom: 20px;
            margin-top: 35px; 
        }
        .result .title_d {
            text-align: left;
            font-size: medium;
            font-weight: bold; 
            color: #1e46b5; 
            margin-bottom: 20px;
            margin-top: 2px; 
        }
        /* Styles pour le bouton d'accueil */
        .home-button {
            padding: 10px 15px;
            font-size: 16px;
            border: none;
            background-color: #1e46b5;
            color: white;
            border-radius: 21px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
        }
        .home-button:hover {
            background-color: #183a97;
        }
    </style>
</head>
<body>
    <a href="javascript:history.back()" class="home-button">Retour</a>
    <div class="container">
        <h1 class="table-title"><?php echo htmlspecialchars($info["nom_lisible"]); ?></h1>
        <div class="details">
            <div class="result">
                <p><span class="label">Description :</span> <?php echo htmlspecialchars($info["description"]); ?></p>
            </div>
            <div class="result">
                <h1 class="title_d">DÉTAILS DE LA TABLE</h1>
                <p><span class="label">Source :</span> <?php echo htmlspecialchars($info["data_source"]); ?></p>
                <p><span class="label">Dernière mise à jour :</span> <?php echo htmlspecialchars($info["last_date_upt"]); ?></p>
                <p><span class="label">Date de création :</span> <?php echo htmlspecialchars($info["creation_date"]); ?></p>
                <?php if ($info['droit'] === 'Restriction'): ?>
                    <p><span class="label">Consultation :</span> <?php echo htmlspecialchars($info["droit"]); ?></p>
                <?php endif; ?>
            </div>
            <div class="result">
                <h1 class="title_d">DÉTAILS TECHNIQUES</h1>
                <p><span class="label">Lien utile :</span> <a href="<?php echo htmlspecialchars($info["url"]); ?>" target="_blank"><?php echo htmlspecialchars($info["url"]); ?></a></p>
                <p><span class="label">Catégorie :</span> <?php echo htmlspecialchars($alias_string); ?></p>
                <p><span class="label">Schéma :</span> <?php echo htmlspecialchars($info["all_schemas_names"]); ?></p>
                <p><span class="label">Table :</span> <?php echo htmlspecialchars($info["all_tables_names"]); ?></p>
                <?php if (empty($geojson)): ?>
                    <p><span class="label">Note :</span> Aucune donnée géographique disponible.</p>
                <?php endif; ?>
            </div>
        </div>
        <div id="map"></div>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        // Initialisation de la carte
        var map = L.map('map').setView([47.309073, -2.405163], 13);

        // Couches de tuiles
        var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var satellite = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenTopoMap contributors'
        });

        // Couches de données GeoJSON
        var geojsonData = <?php echo json_encode($geojson); ?>;
        var geojsonLayer = L.geoJSON(); // Crée une couche GeoJSON vide

        geojsonData.forEach(function(data) {
            if (data) {
                geojsonLayer.addData(JSON.parse(data)); // Ajoute chaque élément GeoJSON à la couche
            }
        });

        // Ajoute la couche GeoJSON à la carte par défaut
        geojsonLayer.addTo(map);

        // Couches de données GeoJSON pour "Cap"
        var geojsonCapData = <?php echo json_encode($geojson_cap); ?>;
        var geojsonCapLayer = L.geoJSON(null, {
            style: {
                color: 'purple'
            }
        });

        geojsonCapData.forEach(function(data) {
            if (data) {
                geojsonCapLayer.addData(JSON.parse(data)); // Ajoute chaque élément GeoJSON à la couche
            }
        });

        // Ajoute la couche GeoJSON "Cap" à la carte si elle existe
        if (geojsonCapData.length > 0) {
            geojsonCapLayer.addTo(map);
        }

        // Contrôle des couches
        var baseLayers = {
            "Carte de Base": osm,
            "Carte Satellite": satellite
        };

        var overlayLayers = {
            "Donnée(s) actuel": geojsonLayer,
            "Emprise réel": geojsonCapLayer
        };

        L.control.layers(baseLayers, overlayLayers).addTo(map);

        // Ajustement de la vue de la carte en fonction des données GeoJSON
        if (geojsonData.length > 0) {
            var bounds = geojsonLayer.getBounds();
            map.fitBounds(bounds);
        } else {
            console.error('GeoJSON data is invalid or empty.');
        }
    </script>
</body>
</html>
