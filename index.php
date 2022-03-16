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
            $prod = str_replace(" ", "+", $_POST['Produit']);
            $pordJson = json_decode(file_get_contents("https://fr.openfoodfacts.org/cgi/search.pl?action=process&search_terms=".$prod."&sort_by=unique_scans_n&page_size=24&page=1&json=1"));
            if ($pordJson->{'count'} == 0) {
                echo 'Désolé, le produit cherché n\'a pas été trouvé. Veuillez reéssayer';
            }
            else {
                echo '<p> Votre produit ce trouve dans les magasins : '.$pordJson->{'products'}[0]->{'stores'}.'</p>';
            }
        }
    ?>
</body>
</html>