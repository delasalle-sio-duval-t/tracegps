<?php
// Projet TraceGPS - services web
// fichier :  api/services/DemanderMdp.php
// Dernière mise à jour : 16/10/2025 par tD

//Rôle : ce service web permet à un utilisateur de demander un nouveau mot de passe s'il l'a oublié.
//Paramètres à fournir :
//• pseudo : le pseudo de l'utilisateur
//• lang : le langage utilisé pour le flux de données ("xml" ou "json")
//Description du traitement :
//• Vérifier que les données transmises sont complètes
//• Vérifier que le pseudo de l'utilisateur existe
//• Générer un nouveau mot de passe
//• Enregistrer le nouveau mot de passe
//• Envoyer un courriel à l'utilisateur avec son nouveau mot de passe

// Les paramètres doivent être passés par la méthode GET :
//     http://<hébergeur>/tracegps/api/ChangerDeMdppseudo=europa&mdp=13e3668bbee30b004380052b086457b014504b3e&nouveauMdp=123&confirmationMdp=123&lang=xml

include_once ('C:\wamp64\www\ws-php-kg\tracegps\modele\DAO.php');

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($_GET['pseudo'])) ? "" : $_GET['pseudo'];
$lang = ( empty($_GET['lang'])) ? "" : $_GET['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($_SERVER['REQUEST_METHOD'] != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else {
    // Les paramètres doivent être présents
    if ($pseudo == "") {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {    // contrôle d'existence du pseudo de l'utilisateur
        $unUtilisateur = $dao->getUnUtilisateur($pseudo);
        if ($unUtilisateur == null) {
            $msg = "Erreur : pseudo inexistant.";
            $code_reponse = 400;
        } else {
            // génération d’un nouveau mdp aleatoire (8 caracteres/4 syllable)
            $nouveauMdp = genererMdp();
            // enregistre le nouveau mot de passe de l'utilisateur dans la bdd après l'avoir codé en sha1
            $ok = $dao->modifierMdpUtilisateur($pseudo, sha1($nouveauMdp));
            if (!$ok) {
                $msg = "Erreur : problème lors de l'enregistrement du mot de passe.";
                $code_reponse = 500;
            } else {
                // envoie un courriel  à l'utilisateur avec son nouveau mot de passe
                $ok = $dao->envoyerMdp($pseudo, $nouveauMdp);
                if (!$ok) {
                    $msg = "Enregistrement effectué ; l'envoi du courriel  de confirmation a rencontré un problème.";
                    $code_reponse = 500;
                } else {
                    $msg = "Enregistrement effectué ; vous allez recevoir un courriel de confirmation.";
                    $code_reponse = 200;
                }
            }
        }
    }
}


// ferme la connexion à MySQL :
unset($dao);

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
        <!--Service web ChangerDeMdp - BTS SIO - Lycée De La Salle - Rennes-->
        <data>
            <reponse>Erreur : authentification incorrecte.</reponse>
        </data>
     */

    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();

    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web ChangerDeMdp - BTS SIO - Lycée De La Salle - Rennes');
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
             "data": {
                "reponse": "Erreur : authentification incorrecte."
             }
         }
     */

    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];

    // construction de la racine
    $elt_racine = ["data" => $elt_data];

    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
//fonction pour générer un mot de passe (intégrable dans le code mais par soucis de practicité et de propreté
//elle est réalisé en fonction.
function genererMdp()
{
    $consonnes = ['b','c','d','f','g','h','j','k','l','m','n','p','r','s','t','v','w','x','z'];
    $voyelles = ['a','e','i','o','u','y'];

    $motDePasse = '';
    for ($i = 0; $i < 4; $i++) {
        $motDePasse .= $consonnes[array_rand($consonnes)];
        $motDePasse .= $voyelles[array_rand($voyelles)];
    }

    return $motDePasse;
}
// ================================================================================================
?>