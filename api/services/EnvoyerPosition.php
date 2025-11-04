<?php
// projet TraceGps - web
// fichier :  api/services/EnvoyerPosition.php
// dernière modification : 16/10/2025 par kG

include_once ('C:\wamp64\www\ws-php-RD\tracegps\modele\PointDeTrace.php');
include_once ('C:\wamp64\www\ws-php-RD\tracegps\modele\DAO.php');
// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($_GET['pseudo'])) ? "" : $_GET['pseudo'];
$mdpSha1 = ( empty($_GET['mdp'])) ? "" : $_GET['mdp'];
$idTrace = ( empty($_GET['idTrace'])) ? "" : $_GET['idTrace'];
$dateHeure = ( empty($_GET['dateHeure'])) ? "" : $_GET['dateHeure'];
$latitude = ( empty($_GET['latitude'])) ? "" : $_GET['latitude'];
$longitude = ( empty($_GET['longitude'])) ? "" : $_GET['longitude'];
$altitude = ( empty($_GET['altitude'])) ? "" : $_GET['altitude'];
$rythmeCardio = ( empty($_GET['rythmeCardio'])) ? "" : $_GET['rythmeCardio'];
$lang = ( empty($_GET['lang'])) ? "" : $_GET['lang'];


// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

if ($_SERVER['REQUEST_METHOD'] != "GET")
{   $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else {// Les paramètres doivent être présents
    if ( $pseudo == "" || $mdpSha1 == "" || $idTrace == "" || $dateHeure =="" || $latitude == "" || $longitude == "" || $altitude == "" || $rythmeCardio == "") {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
    else {

        $reponse = $dao->getNiveauConnexion($pseudo, $mdpSha1);

        if (empty($reponse)|| $reponse == 0) {
            $msg = " Erreur : authentification incorrecte.";
            $code_reponse = 203;
        }

        else {
            $uneTrace = $dao->getUneTrace($idTrace);

            if (is_null($uneTrace)) {
                $msg = " Erreur : le numéro de trace n'existe pas.";
                $code_reponse = 404;
            }

            else {
                $idUtilisateur = $uneTrace->getIdUtilisateur();
                $unUtilisateur = $dao->getUnUtilisateur($pseudo);

                if ($idUtilisateur != $unUtilisateur->getId()) {
                    $msg = " Erreur : le numéro de la trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 409;
                }

                 else {
                     $uneTraceTerminee = $uneTrace->getTerminee();

                     if ($uneTraceTerminee == 1) {
                         $msg = " Erreur : la trace est déjà terminée.";
                         $code_reponse = 409;
                     }

                     else {

                         $point = new PointDeTrace($idTrace,sizeof($dao->getLesPointsDeTrace($idTrace))+1, $latitude, $longitude, $altitude, $dateHeure, $rythmeCardio,0,0,0);
                         $dao->creerUnPointDeTrace($point);
                         $msg = "Point créé";
                         $code_reponse = 201;
                     }
                 }
            }
        }
    }
}

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg);
}

// envoi de la réponse HTTP

http_response_code($code_reponse);
header("Content-Type: " . $content_type);
echo $donnees;

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg)
{
    /* Exemple de code XML
         <?xml version="1.0" encoding="UTF-8"?>
         <!--Service web Connecter - BTS SIO - Lycée De La Salle - Rennes-->
         <data>
            <reponse>Erreur : données incomplètes.</reponse>
         </data>
     */

    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();

    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web Connecter - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);

    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    // place l'élément 'reponse' juste après l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    // Mise en forme finale
    $doc->formatOutput = true;

    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg)
{
    /* Exemple de code JSON
         {
             "data":{
                "reponse": "authentification incorrecte."
             }
         }
     */

    // 2 notations possibles pour créer des tableaux associatifs (la deuxième est en commentaire)

    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];
//     $elt_data = array("reponse" => $msg);

    // construction de la racine
    $elt_racine = ["data" => $elt_data];
//     $elt_racine = array("data" => $elt_data);

    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);

}

// ================================================================================================
?>

