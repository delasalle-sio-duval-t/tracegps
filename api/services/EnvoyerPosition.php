<?php

// =======================================================
// Service web : EnvoyerPosition
// Projet TraceGPS
// =======================================================

include_once("C:/wamp64/www/ws-php-fg/tracegps/modele/DAO.php");

// récupération des paramètres (GET ou POST)
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$latitude = empty($this->request['latitude']) ? "" : $this->request['latitude'];
$longitude = empty($this->request['longitude']) ? "" : $this->request['longitude'] ;
$altitude = empty($this->request['altitude']) ? "" : $this->request['altitude'];
$rythmeCardio = empty($this->request['rythmeCardio']) ? "" : $this->request['rythmeCardio'] ;
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// par défaut XML si paramètre lang incorrect
if ($lang != "json") $lang = "xml";

// connexion à la base
$dao = new DAO();

// vérification des données
if ($pseudo == "" || $mdpSha1 == "" || $latitude == "" || $longitude == "") {
    $msg = "Erreur : données incomplètes.";
    $code_reponse = 400;
} else {
    // vérification authentification
    $niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdpSha1);
    if ($niveauConnexion == 0) {
        $msg = "Erreur : authentification incorrecte.";
        $code_reponse = 401;
    } else {
        // récupération de l'utilisateur
        $utilisateur = $dao->getUnUtilisateur($pseudo);
        $idUtilisateur = $utilisateur->getId();

        // enregistrement de la position
        $ok = $dao->ajouterUneTrace($idUtilisateur, $latitude, $longitude, $altitude, $rythmeCardio);

        if ($ok) {
            $msg = "Position enregistrée avec succès.";
            $code_reponse = 200;
        } else {
            $msg = "Erreur lors de l'enregistrement de la position.";
            $code_reponse = 500;
        }
    }
}

// génération du flux de sortie
if ($lang == "json") {
    header("Content-Type: application/json; charset=utf-8");
    echo creerFluxJSON($msg);
} else {
    header("Content-Type: application/xml; charset=utf-8");
    echo creerFluxXML($msg);
}

// fermeture DAO
unset($dao);
exit;

// ==================== Fonctions =======================

function creerFluxXML($msg)
{
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    $doc->appendChild($doc->createComment('Service web EnvoyerPosition - TraceGPS'));
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    $doc->formatOutput = true;
    return $doc->saveXML();
}

function creerFluxJSON($msg)
{
    $elt_data = ["reponse" => $msg];
    $elt_racine = ["data" => $elt_data];
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

?>