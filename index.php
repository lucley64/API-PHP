<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localiser les magasins</title>
</head>
<body>
    <form action="#" method="post">
        <label for="Produit">Saisir le nom du produit</label>
        <input type="text" id="Produit" name="Produit">
        <button type="submit">Rechercher</button>
    </form>
    <?php
        if (isset($_POST['Produit'])) {
            //On récupère le JSON de tous les magasins en France
            $jsonEntreprise = file_get_contents("https://magosm.magellium.com/geoserver/wfs?request=GetFeature&version=2.0.0&count=10000&outputFormat=application/json&typeName=magosm:france_shops_point&srsName=EPSG:3857&bbox=-1501530.3425145051,4785263.316753057,2607724.29809657,7033123.44456352");
            $magasins = json_decode($jsonEntreprise);
            //Je récupère 1 à 1 les magasins que je mets dans un tableau
            $tabMagasins = $magasins->{'features'};
            
            $prod = str_replace(" ", "+", $_POST['Produit']);
            $pordJson = json_decode(file_get_contents("https://fr.openfoodfacts.org/cgi/search.pl?action=process&search_terms=".$prod."&sort_by=unique_scans_n&page_size=24&page=1&json=1"));
            if ($pordJson->{'count'} == 0) {
                echo 'Désolé, le produit cherché n\'a pas été trouvé. Veuillez reéssayer';
            }
            else {
                //Je récupère 1 à 1 les magasins qui proposent le produit
                $tableaumagasinsDisponibles = explode(",",$pordJson->{'products'}[0]->{'stores'});
                //Parcours de tous les magasins qui propose ce produit
                foreach($tableaumagasinsDisponibles as $magasinsDispo)
                {
                    //Parcours de tous les magasins 
                    foreach($tabMagasins as $magasin)
                    {
                        //Récupère le nom du magasin
                        $nomMagasin = $magasin->{'properties'}->{'name'};
                        
                        if($nomMagasin == "E. Leclerc")
                        {
                            $nomMagasin = "Leclerc";
                        }
                        //Récupère dans un tableau les 2 coordonnées (x,y) du magasin
                        $coordonneesMagasin = $magasin->{'geometry'}->{'coordinates'};
                        //Si le magasin est disponible
                        if($magasinsDispo == $nomMagasin)
                        {
                            var_dump($magasinsDispo);
                            longitude_coversion($coordonneesMagasin[0],$coordonneesMagasin[1]);
                            
                        }
                    }
                }
                }
                echo '<p> Votre produit ce trouve dans les magasins : '.$pordJson->{'products'}[0]->{'stores'}.'</p>';
                
            }

            // Il faudrait créer une API de conversion
            function longitude_coversion($long3857, $lat3857)
            {
                $json = json_decode(file_get_contents("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "/Conversion/3857To4326/$long3857/$lat3857/"));
                $latitude = $json->{'lat4326'};
                $longitude = $json->{'long4326'};
                var_dump($latitude);
                var_dump($longitude);        
            }
        
    ?>
</body>
</html>