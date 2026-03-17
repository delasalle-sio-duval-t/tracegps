<?php

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$lang = (empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// initialisation
$nbReponses = 0;
$lesUtilisateurs = array();
$msg = "";
$code_reponse = 400; // valeur par défaut

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Les paramètres doivent être présents
    if ($pseudo == "" || $mdpSha1 == "") {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérifie l'authentification
        $niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdpSha1);

        if ($niveauConnexion == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Récupération utilisateur
            $utilisateur = $dao->getUnUtilisateur($pseudo);

            if ($utilisateur == null) {
                $msg = "Erreur : utilisateur introuvable.";
                $code_reponse = 404;
            } else {
                $idUtilisateur = $utilisateur->getId();

                // Récupération de la liste des utilisateurs autorisés
                $lesUtilisateurs = $dao->getLesUtilisateursAutorises($idUtilisateur);

                $nbReponses = sizeof($lesUtilisateurs);

                if ($nbReponses == 0) {
                    $msg = "Aucun utilisateur accordé par " . $pseudo . ".";
                } else {
                    $msg = $nbReponses . " utilisateur(s) autorisé(s).";
                }

                $code_reponse = 200;
            }
        }
    }
}

// ferme la connexion
unset($dao);

// ================================================================================================
// Création du flux de sortie (XML ou JSON)
// ================================================================================================

$content_type = ($lang == "xml")
    ? "application/xml; charset=utf-8"
    : "application/json; charset=utf-8";

$donnees = ($lang == "xml")
    ? creerFluxXML($msg)
    : creerFluxJSON($msg);

// Envoi de la réponse HTTP
http_response_code($code_reponse);
header("Content-Type: " . $content_type);
echo $donnees;

exit;


// ================================================================================================
// Création du flux XML
// ================================================================================================

function creerFluxXML($msg)
{
    /*
        Exemple :

        <?xml version="1.0" encoding="UTF-8"?>
        <!--Service web ChangerDeMdp - BTS SIO - Lycée De La Salle - Rennes-->
        <data>
            <reponse>Erreur : authentification incorrecte.</reponse>
        </data>
    */

    $doc = new DOMDocument("1.0", "UTF-8");

    // Commentaire
    $commentaire = $doc->createComment(
        "Service web ChangerDeMdp - BTS SIO - Lycée De La Salle - Rennes"
    );
    $doc->appendChild($commentaire);

    // Racine <data>
    $data = $doc->createElement("data");
    $doc->appendChild($data);

    // <reponse>
    $reponse = $doc->createElement("reponse", $msg);
    $data->appendChild($reponse);

    $doc->formatOutput = true;

    return $doc->saveXML();
}


// ================================================================================================
// Création du flux JSON
// ================================================================================================

function creerFluxJSON($msg)
{
    /*
        Exemple :

        {
            "data": {
                "reponse": "Erreur : authentification incorrecte."
            }
        }
    */

    $racine = [
        "data" => [
            "reponse" => $msg
        ]
    ];

    return json_encode($racine, JSON_PRETTY_PRINT);
}

?>