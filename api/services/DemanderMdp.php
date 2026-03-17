<?php
$dao = new DAO();

// Récupération des données transmises
$pseudo = empty($this->request['pseudo']) ? "" : $this->request['pseudo'];
$lang   = empty($this->request['lang']) ? "" : $this->request['lang'];
if ($lang != "json") $lang = "xml";

$msg = "";
$code_reponse = 200;

if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} elseif ($pseudo == "") {
    $msg = "Erreur : données incomplètes.";
    $code_reponse = 400;
} else {
    // Récupération de l'utilisateur par son pseudo
    $unUtilisateur = $dao->getUnUtilisateur($pseudo);

    if (!$unUtilisateur) {
        $msg = "Erreur : pseudo inexistant.";
        $code_reponse = 404;
    } else {
        // Génération d'un nouveau mot de passe
        $nouveauMdp = Outils::CreerMdp();

        // Hashage du mot de passe
        $mdpHash = sha1($nouveauMdp);

        // Enregistrement du nouveau mot de passe dans la base de donnée
        $ok = $dao->modifierMdpUtilisateur($pseudo, $mdpHash);

        if (!$ok) {
            $msg = "Erreur : problème lors de l'enregistrement du mot de passe.";
            $code_reponse = 500;
        } else {
            // Envoi du courriel à l'utilisateur
            $ok = $dao->envoyerMdp($pseudo, $nouveauMdp);

            if (!$ok) {
                $msg = "Enregistrement effectué ; l'envoi du courriel de confirmation a rencontré un problème.";
                $code_reponse = 500;
            } else {
                $msg = "Enregistrement effectué ; vous allez recevoir un courriel de confirmation.";
                $code_reponse = 200;
            }
        }
    }
}

// Ferme la connexion
unset($dao);

// Création du flux de sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";
    $donnees = creerFluxXML($msg);
} else {
    $content_type = "application/json; charset=utf-8";
    $donnees = creerFluxJSON($msg);
}

// Envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);
exit;

// ================================================================================================
// création du flux XML
function creerFluxXML($msg) {
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    $elt_commentaire = $doc->createComment('Service web DemanderMdp - BTS SIO - Lycée De La Salle - Rennes');
    $doc->appendChild($elt_commentaire);

    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================
// création du flux JSON
function creerFluxJSON($msg) {
    $elt_data = ["reponse" => $msg];
    $elt_racine = ["data" => $elt_data];
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}
?>