<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localiser les magasins</title>
</head>

<body>
    <form action="#" method="post">
        <label for="Produit">Saisir le nom du produit</label>
        <input type="text" id="Produit" name="Produit" required><br />
        <label for="Ville">Saisir le nom d'une ville</label>
        <input type="text" id="Ville" name="Ville" required><br />
        <label for="Rayon">Saisir le rayon</label>
        <input type="number" name="Rayon" id="Rayon" value="10" min="10" max="100" required>km<br />
        <button type="submit">Rechercher</button>
    </form>
    <iframe src="http://127.0.0.1/WampProjects/API-PHP/carte" frameborder="0"id="inlineFrameExample" title="Inline Frame Example" width="300" height="200"></iframe>
    <?php
    if (isset($_POST['Produit']) && isset($_POST['Ville']) && isset($_POST['Rayon'])) {
        //On récupère le JSON de tous les magasins en France
        $jsonEntreprise = file_get_contents("https://magosm.magellium.com/geoserver/wfs?request=GetFeature&version=2.0.0&count=10000&outputFormat=application/json&typeName=magosm:france_shops_point&srsName=EPSG:3857&bbox=-1501530.3425145051,4785263.316753057,2607724.29809657,7033123.44456352");
        $magasins = json_decode($jsonEntreprise);
        //Je récupère 1 à 1 les magasins que je mets dans un tableau
        $tabMagasins = $magasins->{'features'};

        $prod = str_replace(" ", "+", $_POST['Produit']);
        $pordJson = json_decode(file_get_contents("https://fr.openfoodfacts.org/cgi/search.pl?action=process&search_terms=" . $prod . "&sort_by=unique_scans_n&page_size=24&page=1&json=1"));

        $villeSaisie = str_replace(" ", "+", $_POST['Ville']);
        $jsonVilles = json_decode(file_get_contents("http://api.geonames.org/search?name_equals=" . $villeSaisie . "&maxRows=10&featureClass=P&username=shinokiku&type=json&country=FR"));

        if (preg_match("/[0-9]+/", $_POST['Rayon'])) {
            $jsonRayon = json_decode(file_get_contents("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "/Conversion/kilometersToCoordinates/$_POST[Rayon]"));
            if ($pordJson->{'count'} == 0) {
                echo 'Désolé, le produit cherché n\'a pas été trouvé. Veuillez reéssayer';
            } elseif ($jsonVilles->{'totalResultsCount'} == 0) {
                echo 'La ville recherchée n\'existe pas';
            } else {
                $envoiCarte = new \stdClass();
                $envoiCarte->{'Villes'} = $jsonVilles->{'geonames'};
                $envoiCarte->{'magasins'} = [];
                $envoiCarte->{'rayon'} = $jsonRayon;
                //Je récupère 1 à 1 les magasins qui proposent le produit
                $tableaumagasinsDisponibles = explode(",", $pordJson->{'products'}[0]->{'stores'});
                //Parcours de tous les magasins qui propose ce produit
                foreach ($tableaumagasinsDisponibles as $magasinsDispo) {
                    //Parcours de tous les magasins 
                    foreach ($tabMagasins as $magasin) {
                        //Récupère le nom du magasin
                        $nomMagasin = $magasin->{'properties'}->{'name'};

                        if ($nomMagasin == "E. Leclerc") {
                            $nomMagasin = "Leclerc";
                        }
                        //Récupère dans un tableau les 2 coordonnées (x,y) du magasin
                        $coordonneesMagasin = $magasin->{'geometry'}->{'coordinates'};
                        //Si le magasin est disponible
                        if ($magasinsDispo == $nomMagasin) {
                            $coordsMag = longitude_coversion($coordonneesMagasin[0], $coordonneesMagasin[1]);
                            foreach ($envoiCarte->{'Villes'} as $ville) {
                                if (
                                    ($coordsMag->{'long'} > ($ville->{'lng'} - $jsonRayon->{'longitude'})) &&
                                    ($coordsMag->{'long'} < ($ville->{'lng'} + $jsonRayon->{'longitude'})) &&
                                    ($coordsMag->{'lat'} > ($ville->{'lat'} - $jsonRayon->{'latitude'})) &&
                                    ($coordsMag->{'lat'} < ($ville->{'lat'} + $jsonRayon->{'latitude'}))
                                ) {
                                    $magasin = new \stdClass();
                                    $magasin->{'nom'} = $magasinsDispo;
                                    $magasin->{'coords'} = $coordsMag;
                                    $envoiCarte->{'magasins'}[] = $magasin;
                                }
                            }
                        }
                    }
                }
                var_dump($envoiCarte);
            }
        } else {
            echo 'Veuillez saisir un rayon valide';
            var_dump($_POST['Rayon']);
        }
        echo '<p> Votre produit ce trouve dans les magasins : ' . $pordJson->{'products'}[0]->{'stores'} . '</p>';
    }

    function longitude_coversion($long3857, $lat3857)
    {
        $json = json_decode(file_get_contents("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "/Conversion/3857To4326/$long3857/$lat3857/"));
        $latitude = $json->{'lat4326'};
        $longitude = $json->{'long4326'};
        $coord = new \stdClass();
        $coord->{'long'} = $longitude;
        $coord->{'lat'} = $latitude;
        return $coord;
    }

    ?>
</body>

</html>