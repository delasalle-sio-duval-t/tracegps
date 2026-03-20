<?php
// Projet TraceGPS - version web mobile
// fichier : controleurs/CtrlDemanderMdp.php

if ( ! isset ($_POST ["txtPseudo"]) ) {

    $pseudo = '';
    $adrMail = '';
    $message = '';
    $typeMessage = '';
    $themeFooter = $themeNormal;

    include_once ('vues/VueDemanderMdp.php');
}
else {
    // récupération
    $pseudo = empty($_POST["txtPseudo"]) ? "" : $_POST["txtPseudo"];
    $adrMail = empty($_POST["txtAdrMail"]) ? "" : $_POST["txtAdrMail"];

    if ($pseudo == '' || $adrMail == '') {

        $message = "Erreur : données incomplètes.";
        $typeMessage = 'avertissement';
        $themeFooter = $themeProbleme;

        include_once ('vues/VueDemanderMdp.php');
    }
    else {

        include_once ('modele/DAO.php');
        $dao = new DAO();

        //  récupération utilisateur
        $unUtilisateur = $dao->getUnUtilisateur($pseudo);

        //  vérification correspondance pseudo + mail
        if ( $unUtilisateur == null || $unUtilisateur->getAdrMail() != $adrMail ) {

            $message = "Erreur : le pseudo et l'adresse mail ne correspondent pas.";
            $typeMessage = 'avertissement';
            $themeFooter = $themeProbleme;

            unset($dao);
            include_once ('vues/VueDemanderMdp.php');
        }
        else {

            // génération mdp
            $nouveauMdp = Outils::creerMdp();

            $ok = $dao->modifierMdpUtilisateur($pseudo, $nouveauMdp);

            if ( ! $ok ) {

                $message = "Erreur : problème lors de l'enregistrement du mot de passe.";
                $typeMessage = 'avertissement';
                $themeFooter = $themeProbleme;

                unset($dao);
                include_once ('vues/VueDemanderMdp.php');
            }
            else {

                $ok = $dao->envoyerMdp($pseudo, $nouveauMdp);

                if ( ! $ok ) {

                    $message = "Enregistrement effectué.<br>L'envoi du courriel a rencontré un problème.";
                    $typeMessage = 'avertissement';
                    $themeFooter = $themeProbleme;

                    unset($dao);
                    include_once ('vues/VueDemanderMdp.php');
                }
                else {

                    $message = "Vous allez recevoir un courriel avec votre nouveau mot de passe.";
                    $typeMessage = 'information';
                    $themeFooter = $themeNormal;

                    unset($dao);
                    include_once ('vues/VueDemanderMdp.php');
                }
            }
        }
    }
}