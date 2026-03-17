<?php
// ces variables globales sont définies dans le fichier modele/parametres.php
global $ADR_MAIL_EMETTEUR, $ADR_SERVICE_WEB;

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoARetirer = ( empty($this->request['pseudoARetirer'])) ? "" : $this->request['pseudoARetirer'];
$texteMessage = ( empty($this->request['texteMessage'])) ? "" : $this->request['texteMessage'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{$msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else {
    // Les paramètres doivent être présents et corrects
    if ( $mdpSha1 == "" || $pseudo == "" || $pseudoARetirer == "")
    {	$message = "Erreur : données incomplètes ou incorrectes.";
        $code_reponse = 400;
    }
    else
        {	$niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdpSha1);
            if ( $niveauConnexion == 0 )
    	    {  $message = "Erreur : authentification incorrecte.";
    	   $code_reponse = 401;
    	    }
            else{

                $unUtilisateur = $dao->getUnUtilisateur($pseudoARetirer);
                if ($unUtilisateur == null)
                    {  $message = "Erreur : pseudo utilisateur inexistant.";
                    $code_reponse = 400;
                    }
                else{

                    $utilisateurDemandeur = $dao->getUnUtilisateur($pseudo);
                    $utilisateurRetirer = $dao->getUnUtilisateur($pseudoARetirer);
                    $idDemandeur = $utilisateurDemandeur->getId();
                    $idRetirer = $utilisateurRetirer->getId();
                    if (!$dao->autoriseAconsulter($idDemandeur, $idRetirer)) {
                        $message = "Erreur : l'autorisation n'était pas accordée.";
                        $code_reponse = 600;
                    }
                    else {
                        if (!$dao->supprimerUneAutorisation($idDemandeur, $idRetirer)) {
                            $message = "Erreur : problème lors de la suppression de l'autorisation.";
                            $code_reponse = 700;
                        }
                        else {
                            if ($texteMessage == "") {
                                $message = "Autorisation supprimée.";
                                $code_reponse = 800;
                            }
                            else {
                                $adrMailDemandeur = $utilisateurRetirer->getAdrMail();
                                $sujetMail = "Suppression d'autorisation de la part d'un utilisateur du système TraceGPS";
                                $contenuMail = "Cher ou chère " . $pseudoARetirer . "\n\n";
                                $contenuMail .= "L'utilisateur " . $pseudo . " du système TraceGPS vous retire l'autorisation de suivre ses parcours.\n\n";
                                $contenuMail .= "Son message : " . $texteMessage . "\n\n";
                                $contenuMail .= "Cordialement,\n L'administrateur du système TraceGPS";
                                if(Outils::envoyerMail($adrMailDemandeur, $sujetMail, $contenuMail, $ADR_MAIL_EMETTEUR)){
                                    $message = "Autorisation supprimée ; "  . $pseudoARetirer . " va recevoir un courriel de notification.";
                                    $code_reponse = 801;
                                }
                                else {
                                    $message = "Erreur : autorisation supprimée ; l'envoi du courriel de notification a rencontré un problème.";
                                    $code_reponse = 900;
                                }

                            }
                        }
                    }
                }
            }
        }
    }
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($message);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($message);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

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