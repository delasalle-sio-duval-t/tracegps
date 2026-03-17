<?php

include_once ('C:\wamp64\www\ws-php-kg\TRACEGPS\modele\DAO.php');

global $ADR_MAIL_EMETTEUR, $ADR_SERVICE_WEB;

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($_GET['pseudo'])) ? "" : $_GET['pseudo'];
$mdp = ( empty($_GET['mdp'])) ? "" : $_GET['mdp'];
$pseudoDestinataire = ( empty($_GET['pseudoDestinataire'])) ? "" : $_GET['pseudoDestinataire'];
$texteMessage = ( empty($_GET['texteMessage'])) ? "" : $_GET['texteMessage'];
$nomPrenom = ( empty($_GET['nomPrenom'])) ? "" : $_GET['nomPrenom'];
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
    if ($pseudo == "" || $mdp == "" || $pseudoDestinataire == "" || $texteMessage == "" || $nomPrenom == "" ) {
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
        elseif (!($dao->existePseudoUtilisateur($pseudoDestinataire)))
        {
            $message = "Erreur : pseudo utilisateur inexistant.";
            $code_reponse = 401;
        }
        else
        {	$utilisateurDemandeur = $dao->getUnUtilisateur($pseudo);
            $utilisateurDestinataire = $dao->getUnUtilisateur($pseudoDestinataire);
            $adrMailDemandeur = $utilisateurDemandeur->getAdrMail();
            $adrMailDestinaire = $utilisateurDestinataire->getAdrMail();
            $numTelDemandeur = $utilisateurDemandeur->getNumTel();

            // envoi d'un mail d'acceptation à l'intéressé
            $sujetMail = "Demande d'autorisation de la part d'un utilisateur du système TraceGPS";
            $contenuMail = "Cher ou chère " . $pseudoDestinataire . "\n\n";
            $contenuMail .= "Un utilisateur système TraceGPS vous demande l'autorisation de suivre votre parcours.\n\n";
            $contenuMail .= "Voici les données le concernant :\n\n";
            $contenuMail .= "Son pseudo : ". $pseudo . "\n";
            $contenuMail .= "Son adresse mail : ". $adrMailDemandeur . "\n";
            $contenuMail .= "Son numéro de téléphone : ". $numTelDemandeur . "\n";
            $contenuMail .= "Son nom et prénom : ". $nomPrenom . "\n";
            $contenuMail .= "Son message : ". $texteMessage . "\n\n";
            $contenuMail .= "Pour accepter la demande, cliquez sur ce lien :\n";
            $contenuMail .= "http://localhost/ws-php-RD/TRACEGPS/api/ValiderDemandeAutorisation?a=". $mdp;
            $contenuMail .= "&b=" . $pseudoDestinataire ."&c=" . $pseudo ."&d=1\n\n";
            $contenuMail .= "Pour refuser la demande, cliquez sur ce lien :\n";
            $contenuMail .= "http://localhost/ws-php-RD/TRACEGPS/api/ValiderDemandeAutorisation?a=". $mdp;
            $contenuMail .= "&b=" . $pseudoDestinataire ."&c=" . $pseudo ."&d=0";
            $ok = Outils::envoyerMail($adrMailDestinaire, $sujetMail, $contenuMail, $ADR_MAIL_EMETTEUR);
            if ( ! $ok ) {
                $message = "Erreur : l'envoi du courriel au demandeur a rencontré un problème.";
                $code_reponse = 500;
            }
            else {
                $message = "Autorisation envoyé.<br>Votre courriel a été envoyé au destinataire.";
                $code_reponse = 200;
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
         <!--Service web DemanderUneAutorisation - BTS SIO - Lycée De La Salle - Rennes-->
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
    $elt_commentaire = $doc->createComment('Service web DemanderUneAutorisation - BTS SIO - Lycée De La Salle - Rennes');
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