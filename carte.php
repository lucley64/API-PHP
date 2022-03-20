<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <!-- Nous chargeons les fichiers CDN de Leaflet. Le CSS AVANT le JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ==" crossorigin="" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/0.4.2/leaflet.draw.css" />
    <style type="text/css" id="style">
    </style>
    <title>Carte</title>

</head>

<body>
    <!-- <form action="#" method="post">
        <label for="NomVille">Entrer le nom de la ville</label>
        <input type="text" id="NomVille" name="NomVille">
        <button type="submit">Rechercher</button>
        <a href="clear.php">Clear</a>
    </form> -->
    <div id="map">
        <!-- Ici s'affichera la carte -->
    </div>
    <!-- Ici on charge l'api leaflet pour afficher une carte depuis l'api openstreetmap -->
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw==" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/0.4.2/leaflet.draw.js"></script>
    <!-- On charge du ajax pour faire des requêtes sur l'api db-ip et l'api ipinfo -->
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <?php
    // Ou initialise le tableau des villes
    $listVilles = [];
    // on recupère les villes déjà saisies
    if (isset($_SESSION['Villes'])) {
        $listVilles = unserialize($_SESSION['Villes']);
    }
    // si une nouvelle ville est saisie on recupère ses coordonées
    if (isset($_POST["NomVille"])) {
        $jsonVilles = json_decode(file_get_contents("http://api.geonames.org/search?name_equals=" . $_POST["NomVille"] . "&maxRows=10&featureClass=P&username=shinokiku&type=json&country=FR"));
        foreach ($jsonVilles->{'geonames'} as &$ville) {
            $listVilles[] = $ville;
        }
    }
    // on pointe les villes sur la carte
    echo '<script>function addVilles(macarte){';
    foreach ($listVilles as &$ville) {
        echo 'ajouterMarker(\'' . $ville->{'name'} . '\', ' . $ville->{'lat'} . ', ' . $ville->{'lng'} . ', macarte);';
    }
    echo '}</script>';
    $_SESSION['Villes'] = serialize($listVilles);
    ?>

    <!-- Ici le script pour traiter et afficher la map -->
    <!-- Ecrit en JavaScript car la map doit être dynamique et on a besoin de l'IP du client -->
    <script>
        function ajouterMarker(nom, lat, lon, macarte) {
            var mark = L.marker([lat, lon]).addTo(macarte);
            mark.bindPopup(nom);
        }

        var macarte = null;

        function afficherMap() {
            // On definit la hauteur et la largeur de la map sinon elle ne s'affiche pas
            document.getElementById('style').innerHTML = "#map{height:" + (window.innerHeight - 25) + "px;width:" + (window.innerWidth - 25) + "px;}"
            // On initialise la carte
            // On recupere l'IP de l'utilisateur avec l'API db-ip
            $.getJSON('https://api.db-ip.com/v2/free/self', function(data) {
                var ipaddr = data.ipAddress;
                // On recupère les coordonées de l'utilisateur depuis son IP avec l'API ipinfo
                $.getJSON('https://ipinfo.io/' + ipaddr + '?token=4072775d60b219', function(data) {
                    var loc = data.loc.split(',');
                    var lat = parseFloat(loc[0]);
                    var lon = parseFloat(loc[1]);
                    // Créé l'objet "macarte" et l'insère dans l'élément HTML qui a l'ID "map"
                    macarte = L.map('map').setView([lat, lon], 15);
                    // Leaflet ne récupère pas les cartes (tiles) sur un serveur par défaut. Nous devons lui préciser où nous souhaitons les récupérer. Ici, openstreetmap.fr
                    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                        // Il est toujours bien de laisser le lien vers la source des données
                        attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>',
                    }).addTo(macarte);
                    // On ajoute un marqueur à la position de l'utilisateur
                    var marker = L.marker([lat, lon]).addTo(macarte)
                    marker.bindPopup("Vous êtes à peut près ici");
                    addVilles(macarte);
                });
            });
        }
    </script>
    <script type="text/javascript">
        // On change la taille de la carte au redumentionnement de la fenêtre
        window.onresize = function() {
            document.getElementById('style').innerHTML = "#map{height:" + (window.innerHeight - 25) + "px;width:" + (window.innerWidth - 25) + "px;}"
        }

        window.onload = () => {
            afficherMap();
        };
    </script>
</body>

</html>