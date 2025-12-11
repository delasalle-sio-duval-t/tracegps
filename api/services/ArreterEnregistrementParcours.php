<?php
// Projet TraceGPS - services web
// fichier : api/services/ArreterEnregistrementParcours.php
// Dernière mise à jour : 16/10/2025 par lB

// Rôle : ce service web permet à un utilisateur de terminer l'enregistrement d'un parcours.
// Le service web doit recevoir 4 paramètres :
//     pseudo : le pseudo de l'utilisateur
//     mdp : le mot de passe de l'utilisateur hashé en sha1
//     idTrace : l'id de la trace à terminer
//     lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

include_once ('C:\wamp64\www\ws-php-td\modele\DAO.php');

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($_GET['pseudo'])) ? "" : $_GET['pseudo'];
$mdpSha1 = ( empty($_GET['mdp'])) ? "" : $_GET['mdp'];
$idTrace = ( empty($_GET['idTrace'])) ? "" : $_GET['idTrace'];
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
    if ($pseudo == "" || $mdpSha1 == "" || $idTrace == "") {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // contrôle de l'authentification
        if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 )
        {   $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        }
        else {
            // contrôle de la trace existante
            $uneTrace = $dao->getUneTrace($idTrace);
            if ($uneTrace == null) {
                $msg = "Erreur : parcours inexistant.";
                $code_reponse = 400;
            } else {
                // il faut être propriétaire de la trace existante
                $unUtilisateur  = $dao->getUnUtilisateur($pseudo);
                if ($dao->getUneTrace($idTrace)->getIdUtilisateur() != $unUtilisateur->getId()) {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 401;
                } else {
                    if ($dao->getUneTrace($idTrace)->getTerminee() == 1) {
                        $msg = "Erreur : cette trace est déjà terminée.";
                        $code_reponse = 303;
                    }
                    else {
                        if (connection_aborted() == 1) {
                            $msg = "Erreur : pas de connexion Internet.";
                            $code_reponse = 408;
                        } else {
                            $msg = "Enregistrement terminé.";
                            $code_reponse = 200;
                        }
                    }
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
    $donnees = creerFluxXML($msg);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg);
}

// envoi de la réponse HTTP
http_response_code($code_reponse);
header("Content-Type: " . $content_type);
echo $donnees;
// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// création du flux XML en sortie
function creerFluxXML($msg)
{	// crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();

    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web SupprimerUnParcours - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);

    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    // place l'élément 'reponse' dans l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    // Mise en forme finale
    $doc->formatOutput = true;

    // renvoie le contenu XML
    return $doc->saveXML();
}

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
?>