<?php
// Projet TraceGPS
// fichier : modele/Trace.php
// Rôle : la classe Trace représente une trace ou un parcours
// Dernière mise à jour : 9/7/2021 par dP
include_once ('PointDeTrace.php');
use modele\Point;
class Trace
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Attributs privés de la classe -------------------------------------
    // ------------------------------------------------------------------------------------------------------
    private $id; // identifiant de la trace
    private $dateHeureDebut; // date et heure de début
    private $dateHeureFin; // date et heure de fin
    private $terminee; // true si la trace est terminée, false sinon
    private $idUtilisateur; // identifiant de l'utilisateur ayant créé la trace
    private $lesPointsDeTrace; // la collection (array) des objets PointDeTrace formant la trace

    // ------------------------------------------------------------------------------------------------------
    // ----------------------------------------- Constructeur -----------------------------------------------
    // ------------------------------------------------------------------------------------------------------

    public function __construct($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur) {
        $this-> id = $unId;
        $this->dateHeureDebut = $uneDateHeureDebut;
        $this->dateHeureFin = $uneDateHeureFin;
        $this->terminee = $terminee;
        $this->idUtilisateur = $unIdUtilisateur;
        $this->lesPointsDeTrace = array();
    }

    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Getters et Setters ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function getId() {return $this->id;}
    public function setId($unId) {$this->id = $unId;}

    public function getDateHeureDebut() {return $this->dateHeureDebut;}
    public function setDateHeureDebut($uneDateHeureDebut) {$this->dateHeureDebut = $uneDateHeureDebut;}
    public function getDateHeureFin() {return $this->dateHeureFin;}
    public function setDateHeureFin($uneDateHeureFin) {$this->dateHeureFin= $uneDateHeureFin;}

    public function getTerminee() {return $this->terminee;}
    public function setTerminee($terminee) {$this->terminee = $terminee;}

    public function getIdUtilisateur() {return $this->idUtilisateur;}
    public function setIdUtilisateur($unIdUtilisateur) {$this->idUtilisateur = $unIdUtilisateur;}
    public function getLesPointsDeTrace() {return $this->lesPointsDeTrace;}
    public function setLesPointsDeTrace($lesPointsDeTrace) {$this->lesPointsDeTrace = $lesPointsDeTrace;}

    // Fournit une chaine contenant toutes les données de l'objet
    public function toString() {
        $msg = "Id : " . $this->getId() . "<br>";
        $msg .= "Utilisateur : " . $this->getIdUtilisateur() . "<br>";
        if ($this->getDateHeureDebut() != null) {
            $msg .= "Heure de début : " . $this->getDateHeureDebut() . "<br>";
        }
        if ($this->getTerminee()) {
            $msg .= "Terminée : Oui <br>";
        }
        else {
            $msg .= "Terminée : Non <br>";
        }
        $msg .= "Nombre de points : " . $this->getNombrePoints() . "<br>";
        if ($this->getNombrePoints() > 0) {
            if ($this->getDateHeureFin() != null) {
                $msg .= "Heure de fin : " . $this->getDateHeureFin() . "<br>";
            }
            $msg .= "Durée en secondes : " . $this->getDureeEnSecondes() . "<br>";
            $msg .= "Durée totale : " . $this->getDureeTotale() . "<br>";
            $msg .= "Distance totale en Km : " . $this->getDistanceTotale() . "<br>";
            $msg .= "Dénivelé en m : " . $this->getDenivele() . "<br>";
            $msg .= "Dénivelé positif en m : " . $this->getDenivelePositif() . "<br>";
            $msg .= "Dénivelé négatif en m : " . $this->getDeniveleNegatif() . "<br>";
            $msg .= "Vitesse moyenne en Km/h : " . $this->getVitesseMoyenne() . "<br>";
            $msg .= "Centre du parcours : " . "<br>";
            $msg .= " - Latitude : " . $this->getCentre()->getLatitude() . "<br>";
            $msg .= " - Longitude : " . $this->getCentre()->getLongitude() . "<br>";
            $msg .= " - Altitude : " . $this->getCentre()->getAltitude() . "<br>";
        }
        return $msg;
    }

    public function getNombrePoints(){
        return sizeof($this->lesPointsDeTrace);
    }

    public function getCentre()
    {
        if (sizeof($this->lesPointsDeTrace) == 0) {
            return null;
        } else {
            $unPoint = $this->lesPointsDeTrace[0];

            $latitudeMin = $unPoint->getLatitude();
            $latitudeMax = $unPoint->getLatitude();

            $longitudeMin = $unPoint->getLongitude();
            $longitudeMax = $unPoint->getLongitude();


            for ($i = 1; $i < sizeof($this->lesPointsDeTrace) - 1; $i++) {
                $unPoint = $this->lesPointsDeTrace[$i];

                if ($latitudeMin > $unPoint->getLatitude()) {
                    $latitudeMin = $unPoint->getLatitude();
                }

                if ($latitudeMax < $unPoint->getLatitude()) {
                    $latitudeMax = $unPoint->getLatitude();
                }

                if ($longitudeMin > $unPoint->getLongitude()) {
                    $longitudeMin = $unPoint->getLongitude();
                }

                if ($longitudeMax < $unPoint->getLongitude()) {
                    $longitudeMax = $unPoint->getLongitude();
                }
            }
            $PointCentre = new Point(0, 0, 0);
            $PointCentre->setLatitude(($latitudeMin + $latitudeMax) / 2);
            $PointCentre->setLongitude(($longitudeMin + $longitudeMax) / 2);

            return $PointCentre;
        }
    }

        public function getDenivele(){
        if (sizeof($this->lesPointsDeTrace) == 0)
        {
            return 0;
        }

        else
        {
            $unPoint = $this->lesPointsDeTrace[0];

            $altitudeMin = $unPoint->getAltitude();
            $altitudeMax = $unPoint->getAltitude();

            for ($i = 1; $i < sizeof($this->lesPointsDeTrace) - 1; $i++)
            {
                $unPoint = $this->lesPointsDeTrace[$i];

                if ($altitudeMin > $unPoint->getAltitude())
                {
                    $altitudeMin = $unPoint->getAltitude();
                }

                if ($altitudeMax < $unPoint->getAltitude())
                {
                    $altitudeMax = $unPoint->getAltitude();
                }

            }

            return $altitudeMax - $altitudeMin;
        }

    }

    public function getDureeEnSecondes(){
        if (sizeof($this->lesPointsDeTrace) == 0)
        {
            return 0;
        }
        else
        {
            $TempsEnSecondes = $this->lesPointsDeTrace[sizeof($this->lesPointsDeTrace)-1]->getTempsCumule();
            return $TempsEnSecondes;
        }
    }

    public function getDureeTotale(){
        $heures = 0;
        $minutes = 0;
        $secondes = 0;

        if (sizeof($this->lesPointsDeTrace) == 0)
        {
            return sprintf("%02d",$heures) . ":" . sprintf("%02d",$minutes) . ":" . sprintf("%02d",$secondes);
        }
        else
        {
            $TempsEnSecondes = self::getDureeEnSecondes();

            $heures = $TempsEnSecondes / 3600;
            $minutes = ($TempsEnSecondes % 3600) / 60;
            $secondes = ($TempsEnSecondes % 3600) % 60;
            return sprintf("%02d",$heures) . ":" . sprintf("%02d",$minutes) . ":" . sprintf("%02d",$secondes);
        }
    }

    public function getDistanceTotale(){
        if (sizeof($this->lesPointsDeTrace) == 0)
           {
               return 0;
           }

        else
        {
            $unPoint = $this->lesPointsDeTrace[sizeof($this->lesPointsDeTrace) - 1];
            return $unPoint->getDistanceCumulee();
        }
    }

    public function getDenivelePositif(){
        if (sizeof($this->lesPointsDeTrace) == 0)
        {
        
                return 0;
        }

        else
        {
            $unPoint = $this->lesPointsDeTrace[0];

            $altitude = $unPoint->getAltitude();

            $deniveleP = 0;

            for ($i = 1; $i < sizeof($this->lesPointsDeTrace); $i++)
            {
                $unPoint = $this->lesPointsDeTrace[$i];

                if ($altitude < $unPoint->getAltitude())
                {
                    $deniveleP += $unPoint->getAltitude() - $altitude;
                }

                $altitude = $unPoint->getAltitude();

            }

            return $deniveleP;
        }

    }

        public function getDeniveleNegatif(){
            if (sizeof($this->lesPointsDeTrace) == 0)
            {
                return 0;
            }

            else
            {
                $unPoint = $this->lesPointsDeTrace[0];

                $altitude = $unPoint->getAltitude();

                $deniveleN = 0;

                for ($i = 1; $i < sizeof($this->lesPointsDeTrace); $i++)
                {
                    $unPoint = $this->lesPointsDeTrace[$i];

                    if ($altitude > $unPoint->getAltitude())
                    {
                        $deniveleN += $altitude - $unPoint->getAltitude();
                    }

                    $altitude = $unPoint->getAltitude();
                }

                return $deniveleN;
            }

        }

    public function getVitesseMoyenne(){
        if (sizeof($this->lesPointsDeTrace) == 0)
        {
            return 0;
        }

        else
        {
            $distanceTotale = $this->getDistanceTotale();
            $temps = $this->getDureeEnSecondes();

            $vitesseMoyenne = $distanceTotale / ($temps / 3600);

            return $vitesseMoyenne;
        }
    }

    public function ajouterPoint($unPoint){
        if (sizeof($this->lesPointsDeTrace) == 0)
        {
            $unPoint->setDistanceCumulee(0);
            $unPoint->setTempsCumule(0);
            $unPoint->setVitesse(0);
        }
        else
        {

            $dernier = $this->lesPointsDeTrace[count($this->lesPointsDeTrace) - 1];

            $distance = Point::getDistance($unPoint, $dernier);
            $distanceCumulee = $dernier->getDistanceCumulee() + $distance;
            $unPoint->setDistanceCumulee($distanceCumulee);

            $tempsSec = strtotime($unPoint->getDateHeure()) - strtotime($dernier->getDateHeure());

            $unPoint->setTempsCumule($dernier->getTempsCumule() + $tempsSec);

            $vitesse = 0;
            if ($tempsSec > 0)
            {
                $heures = $tempsSec / 3600.0;
                $vitesse = $distance / $heures;
            }
            else
            {
                $vitesse = 0;
            }
            $unPoint->setVitesse($vitesse);
        }

        $this->lesPointsDeTrace[] = $unPoint;
    }

    public function viderListePoints()
    {
        for ($i = 0; $i < sizeof($this->lesPointsDeTrace); $i++) {
            unset($this->lesPointsDeTrace[0]);
        }

    }

} // fin de la classe Trace
// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!!
// ceci est un test

