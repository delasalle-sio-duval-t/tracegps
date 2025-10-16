<?php
// projet TraceGps - web
// fichier :  api/services/EnvoyerPosition.php
// dernière modification : 16/10/2025 par kG

include_once ('C:\wamp64\www\ws-php-kg\tracegps\modele\PointDeTrace.php');
include_once ('C:\wamp64\www\ws-php-kg\tracegps\modele\DAO.php');
// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace = ( empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$dateHeure = ( empty($this->request['dateHeure'])) ? "" : $this->request['dateHeure'];
$latitude = ( empty($this->request['latitude'])) ? "" : $this->request['latitude'];
$longitude = ( empty($this->request['longitude'])) ? "" : $this->request['longitude'];
$altitude = ( empty($this->request['altitude'])) ? "" : $this->request['altitude'];
$rythmeCardio = ( empty($this->request['rythmeCardio'])) ? "" : $this->request['rythmeCardio'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];


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

        if (empty($reponse) || $reponse == 0) {
            $msg = " Erreur : authentification incorrecte.";
        }

        else {
            $uneTrace = $dao->getUneTrace($idTrace);

            if (is_null($uneTrace)) {
                $msg = " Erreur : le numéro de trace n'existe pas.";
            }

            else {
                $idUtilisateur = $uneTrace->getIdUtilisateur();
                $unUtilisateur = $dao->getUnUtilisateur($pseudo);

                if ($idUtilisateur != $unUtilisateur->getId()) {
                    $msg = " Erreur : le numéro de la trace ne correspond pas à cet utilisateur.";
                }

                 else {
                     $uneTraceTerminee = $uneTrace->getTerminee();

                     if ($uneTraceTerminee == 1) {
                         $msg = " Erreur : la trace est déjà terminée.";
                     }

                     else {
                         $point = new PointDeTrace($idTrace, "A mettre le des id +1", $latitude, $longitude, $altitude, $dateHeure, $rythmeCardio);
                         $dao->creerUnPointDeTrace($point);
                         //code à terminer et vérifier, mettre max id
                     }
                 }
            }
        }


    }
}

