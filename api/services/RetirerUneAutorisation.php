<?php

include_once ('C:\wamp64\www\ws-php-td\modele\DAO.php');

global $ADR_MAIL_EMETTEUR, $ADR_SERVICE_WEB;

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($_GET['pseudo'])) ? "" : $_GET['pseudo'];
$mdp = ( empty($_GET['mdp'])) ? "" : $_GET['mdp'];
$pseudoARetirer = ( empty($_GET['pseudoARetirer'])) ? "" : $_GET['pseudoARetirer'];
$texteMessage = ( empty($_GET['texteMessage'])) ? "" : $_GET['texteMessage'];
$lang = ( empty($_GET['lang'])) ? "" : $_GET['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($_SERVER['REQUEST_METHOD'] != "GET")
{	$message = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else {
    // Les paramètres doivent être présents
    if ($pseudo == "" || $mdp == "" || $pseudoARetirer == "") {
        $message = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }

    else
    {	// test de l'authentification de l'utilisateur
        // la méthode getNiveauConnexion de la classe DAO retourne les valeurs 0 (non identifié) ou 1 (utilisateur) ou 2 (administrateur)
        $niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdp);

        if ( $niveauConnexion == 0 )
        {  $message = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        }
        elseif (!($dao->existePseudoUtilisateur($pseudoARetirer)))
        {
            $message = "Erreur : pseudo utilisateur inexistant.";
            $code_reponse = 401;
        }
        else
        {	$utilisateur = $dao->getUnUtilisateur($pseudo);
            $utilisateurARetirer = $dao->getUnUtilisateur($pseudoARetirer);
            $idAutorisant = $utilisateur->getId();
            $idAutorise = $utilisateurARetirer->getId();
            $adrMailDemandeur = $utilisateur->getAdrMail();
            $adrMailDestinaire = $utilisateurARetirer->getAdrMail();

            if (!($dao->autoriseAConsulter($idAutorisant, $idAutorise)))
            {	$message = "Erreur : l'autorisation n'était pas accordée.";
                $code_reponse = 400;
            }

            else {
                // envoi d'un mail d'acceptation à l'intéressé
                $utilisateurARetirer = $dao->getUnUtilisateur($pseudoARetirer);
                $utilisateur = $dao->getUnUtilisateur($pseudo);
                $idAutorisant = $utilisateur->getId();
                $idARetirer = $utilisateurARetirer->getId();
                $adrMailAutorise = $utilisateurARetirer->getAdrMail();

                $dao->supprimerUneAutorisation($idAutorisant, $idAutorise);

                if ($texteMessage != "") {
                    // envoi d'un mail d'acceptation à l'intéressé
                    $sujetMail = "Demande d'autorisation de la part d'un utilisateur du système TraceGPS";
                    $contenuMail = "Cher ou chère " . $pseudoARetirer . "\n\n";
                    $contenuMail .= "L'utilisateur" . $pseudo . " du systeme TraceGPS vous retire l'autorisation de suivre ses parcours.\n\n";
                    $contenuMail .= "Son message  : " . $texteMessage . "\n\n";
                    $contenuMail .= "Cordialement\n";
                    $contenuMail .= "L'administrateur de TraceGPS";
                    $ok = Outils::envoyerMail($adrMailDestinaire, $sujetMail, $contenuMail, $ADR_MAIL_EMETTEUR);
                    if (!$ok) {
                        $message = "Erreur : autorisation supprimée ; l'envoi du courriel de notification a rencontré un problème";
                        $code_reponse = 500;
                    } else {
                        $message = "Autorisation supprimée ; " . $pseudoARetirer . " va recevoir un courriel de notification.";
                        $code_reponse = 200;
                    }
                }
                else
                {
                    $message = "Autorisation supprimée ;";
                    $code_reponse = 200;
                }
            }

        }
    }
}
unset($dao);   // ferme la connexion à MySQL

if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($message);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($message);
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
         <!--Service web RetirerUneAutorisation - BTS SIO - Lycée De La Salle - Rennes-->
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
    $elt_commentaire = $doc->createComment('Service web RetirerUneAutorisation - BTS SIO - Lycée De La Salle - Rennes');
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

?>
