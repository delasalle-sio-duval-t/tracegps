<?php
// Projet TraceGPS - version web mobile
// fichier : vues/VueDemanderMdp.php
?>
<!doctype html>
<html>
<head>
    <?php include_once ('vues/head.php'); ?>

    <script>
        <?php if ($typeMessage != '') { ?>
        $(document).bind('pageinit', function() {
            $.mobile.changePage('#affichage_message', {transition: "<?php echo $transition; ?>"});
        });
        <?php } ?>
    </script>
</head>

<body>
<div data-role="page" id="page_principale">
    <div data-role="header" data-theme="<?php echo $themeNormal; ?>">
        <h4><?php echo $TITRE_APPLI; ?></h4>
        <a href="index.php?action=Connecter" data-ajax="false" data-transition="<?php echo $transition; ?>">Retour accueil</a>
    </div>

    <div data-role="content">
        <h4 style="text-align: center;">Demander un nouveau mot de passe</h4>

        <?php if ($typeMessage != 'information') { ?>

            <form action="index.php?action=DemanderMdp" method="post" data-ajax="false">

                <p>Indiquez votre pseudo et votre adresse mail.</p>

                <div data-role="fieldcontain" class="ui-hide-label">
                    <input type="text" name="txtPseudo" placeholder="Pseudo"
                           required value="<?php echo $pseudo; ?>">
                </div>

                <!--  AJOUT EMAIL -->
                <div data-role="fieldcontain" class="ui-hide-label">
                    <input type="email" name="txtAdrMail" placeholder="Adresse mail"
                           required pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$"
                           value="<?php echo $adrMail; ?>">
                </div>

                <div data-role="fieldcontain">
                    <input type="submit" value="M'envoyer un nouveau mot de passe">
                </div>

            </form>

        <?php } ?>

    </div>

    <div data-role="footer" data-theme="<?php echo $themeNormal; ?>">
        <h4><?php echo $NOM_APPLI; ?></h4>
    </div>
</div>

<?php include_once ('vues/dialog_message.php'); ?>
</body>
</html>