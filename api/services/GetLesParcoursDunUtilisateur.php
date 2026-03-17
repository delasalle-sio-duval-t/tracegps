<?php
// Projet TraceGPS - services web
// fichier : api/services/GetLesParcoursDunUtilisateur.php
// Rôle : ce service permet à un utilisateur d'obtenir la liste des parcours d'un utilisateur qui l'autorise

$dao = new DAO();

// Récupération des paramètres
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoConsulte = (empty($this->request['pseudoConsulte'])) ? "" : $this->request['pseudoConsulte'];
$lang = (empty($this->request['lang'])) ? "" : $this->request['lang'];
if ($lang != "json") $lang = "xml";


$msg = "";
$code_reponse = null;
$lesTraces = null;

// "xml" par défaut
if ($lang != "json") $lang = "xml";

// Vérification de la méthode HTTP
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else if ($pseudo == "" || $mdpSha1 == "" || $pseudoConsulte == "") {
    $msg = "Erreur : données incomplètes.";
    $code_reponse = 400;
}
else if ($dao->getNiveauConnexion($pseudo, $mdpSha1) == 0) {
    $msg = "Erreur : authentification incorrecte.";
    $code_reponse = 401;
}
else if (!$dao->existePseudoUtilisateur($pseudoConsulte)) {
    $msg = "Erreur : pseudo consulté inexistant.";
    $code_reponse = 404;
}
else {
    $idAutorise = $dao->getUnUtilisateur($pseudo)->getId();
    $idAutorisant = $dao->getUnUtilisateur($pseudoConsulte)->getId();

    if (!$dao->autoriseAConsulter($idAutorisant, $idAutorise) && $idAutorise != $idAutorisant) {
        $msg = "Erreur : Vous n'êtes pas autorisé par le propriétaire du parcours.";
        $code_reponse = 403;
    }
    else {
        $lesTraces = $dao->getLesTraces($idAutorisant);
        $nbTraces = sizeof($lesTraces);
        $msg = ($nbTraces == 0) ? "Aucune trace pour l'utilisateur ".$pseudoConsulte
            : $nbTraces." trace(s) pour l'utilisateur ".$pseudoConsulte;
        $code_reponse = 200;
    }
}

// Fermeture de la connexion
unset($dao);

// Création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";
    $donnees = creerFluxXML($msg, $lesTraces);
} else {
    $content_type = "application/json; charset=utf-8";
    $donnees = creerFluxJSON($msg, $lesTraces);
}

// Envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);
exit;

// ================================================================================================
// Création du flux XML
function creerFluxXML($msg, $lesTraces) {
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    $doc->appendChild($doc->createComment('Service web GetLesParcoursDunUtilisateur - BTS SIO - Lycée De La Salle - Rennes'));

    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    if ($lesTraces != null && sizeof($lesTraces) > 0) {
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);

        $elt_lesTraces = $doc->createElement('lesTraces');
        $elt_donnees->appendChild($elt_lesTraces);

        foreach ($lesTraces as $uneTrace) {
            $elt_trace = $doc->createElement('trace');
            $elt_lesTraces->appendChild($elt_trace);

            $elt_trace->appendChild($doc->createElement('id', $uneTrace->getId()));
            $elt_trace->appendChild($doc->createElement('dateHeureDebut', $uneTrace->getDateHeureDebut()));
            $elt_trace->appendChild($doc->createElement('terminee', $uneTrace->getTerminee() ? '1' : '0'));

            if ($uneTrace->getTerminee() && $uneTrace->getDateHeureFin() != null) {
                $elt_trace->appendChild($doc->createElement('dateHeureFin', $uneTrace->getDateHeureFin()));
            }
            if ($uneTrace->getDistanceTotale() != null) {
                $elt_trace->appendChild($doc->createElement('distance', $uneTrace->getDistanceTotale()));
            }

            $elt_trace->appendChild($doc->createElement('idUtilisateur', $uneTrace->getIdUtilisateur()));


        }
    }

    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================
// Création du flux JSON
function creerFluxJSON($msg, $lesTraces) {
    if ($lesTraces == null || sizeof($lesTraces) == 0) {
        $elt_data = ["reponse" => $msg];
    } else {
        $tableauTraces = [];
        foreach ($lesTraces as $uneTrace) {
            $objetTrace = [
                "id" => $uneTrace->getId(),
                "dateHeureDebut" => $uneTrace->getDateHeureDebut(),
                "terminee" => $uneTrace->getTerminee(),
                "idUtilisateur" => $uneTrace->getIdUtilisateur()
            ];
            if ($uneTrace->getTerminee() && $uneTrace->getDateHeureFin() != null) {
                $objetTrace["dateHeureFin"] = $uneTrace->getDateHeureFin();
            }
            if ($uneTrace->getDistanceTotale() != null) {
                $objetTrace["distance"] = $uneTrace->getDistanceTotale();
            }

            $tableauTraces[] = $objetTrace;
        }

        $elt_donnees = ["lesTraces" => $tableauTraces];
        $elt_data = ["reponse" => $msg, "donnees" => $elt_donnees];
    }

    return json_encode(["data" => $elt_data], JSON_PRETTY_PRINT);
}
?>