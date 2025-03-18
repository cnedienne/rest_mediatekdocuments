<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "infocommandedocument":
                return $this->selectInfoLivreCommande($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs['id']);
            case "infocommandedocument":
                return $this->selectInfoLivreCommande($champs['idLivreDvd']);
            case "allsuivi":
                return $this->selectAllEtatsSuivi();
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){ 
            case "commandeDocAjout":
                return $this->insertDetailCommande($champs);
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
                case "commandeDocModifier" :
                    return $this->modifierDetailCommande($champs);
                case "commandeDocSupprimer" :
                    return $this->supprimerDetailCommande($champs);
                default:
                    // cas général
                    return $this->updateOneTupleOneTable($table, $id, $champs);
            }
        }
        
        /**
         * demande de suppression (delete)
         * @param string $table
         * @param array|null $champs nom et valeur de chaque champ
         * @return int|null nombre de tuples supprimés ou null si erreur
         * @override
         */	
        protected function traitementDelete(string $table, ?array $champs) : ?int{
            switch($table){
                default:
                    // cas général
                    return $this->deleteTuplesOneTable($table, $champs);
        }
    }
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }


    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère tous les exemplaires d'une revue
     * @param array $champs
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', array: $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les infos d'une commande
     * @param array $champs
     * @return array|null
     */
    private function selectInfoLivreCommande(?array $champs) : ?array {
        if (empty($champs) || !array_key_exists('idLivreDvd', $champs)) {
            return null;
        }

        // Récupérer l'idLivreDvd
        $champNecessaire['idLivreDvd'] = $champs['idLivreDvd'];

        // Modifie la requête pour inclure idLivreDvd dans les résultats
        $requete = "SELECT c.id, c.dateCommande, c.montant, cd.nbExemplaire, s.id AS idSuivi, s.etat, cd.idLivreDvd ";  // Ajouter cd.idLivreDvd
        $requete .= "FROM commande c ";
        $requete .= "JOIN commandedocument cd ON c.id = cd.id ";
        $requete .= "JOIN suivi s ON cd.idSuivi = s.id ";
        $requete .= "WHERE cd.idLivreDvd = :idLivreDvd ";
        $requete .= "ORDER BY c.dateCommande DESC";

        // Exécuter la requête et retourner les résultats
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * renvoie tous les champs dans la table suivi
     */
    private function selectAllEtatsSuivi() : ?array {
        // Requête pour récupérer tous les états distincts de la table suivi
        $requete = "SELECT id, etat FROM suivi";
        
        // Exécuter la requête et retourner les résultats
        return $this->conn->queryBDD($requete, []);
    }

    /**
     * Permet d'ajouter une commande dans la bdd
     * @param array|null $champs
     */
    public function insertDetailCommande(?array $champs): bool {
        // Vérification de la présence de tous les champs nécessaires
        if ($champs === null || 
            !isset($champs['DateCommande']) || 
            !isset($champs['Montant']) || 
            !isset($champs['NbExemplaire']) || 
            !isset($champs['IdLivreDvd']) || 
            !isset($champs['IdSuivi']) || 
            !isset($champs['Etat'])) {
        
            // Retourne false si les paramètres sont manquants
            return false;
        }


        // Requête pour insérer une commande dans la table "commande"
        $requeteCommande = "INSERT INTO commande (dateCommande, montant) VALUES (:dateCommande, :montant)";
        $paramsCommande = [
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant']
        ];
        $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    
        // Requête pour insérer une ligne dans la table "commandedocument"
        $requeteCommandeDocument = "INSERT INTO commandedocument (nbExemplaire, idLivreDvd, idSuivi) VALUES (:nbExemplaire, :idLivreDvd, :idSuivi)";
        $paramsCommandeDocument = [
            'nbExemplaire' => $champs['NbExemplaire'],
            'idLivreDvd' => $champs['IdLivreDvd'],
            'idSuivi' => $champs['IdSuivi']
        ];
        $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
        // Si les deux requêtes ont réussi, on retourne true
        if ($resultCommande > 0 && $resultCommandeDocument > 0) {
            return true;
        } else {
            // Si une des requêtes échoue, on retourne false
            return false;
        }
    }

    /**
     * Permet de modifier une commande dans la bdd
     * @param array|null $champs
     */
    public function modifierDetailCommande(?array $champs): bool{
        if ($champs === null || !isset($champs['Id'])) {
            return false;
        }
        // Requête UPDATE pour la table "commande"
        $requeteCommande = "UPDATE commande SET dateCommande = :dateCommande, montant = :montant WHERE id = :id";
        $paramsCommande = [
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant'],
            'id' => $champs['Id']
        ];
        $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    
        // Requête UPDATE pour la table "commandedocument"
        $requeteCommandeDocument = "UPDATE commandedocument SET nbExemplaire = :nbExemplaire, idLivreDvd = :idLivreDvd, idSuivi = :idSuivi WHERE id = :id";
        $paramsCommandeDocument = [
            'nbExemplaire' => $champs['NbExemplaire'],
            'idLivreDvd' => $champs['IdLivreDvd'],
            'idSuivi' => $champs['IdSuivi'],
            'id' => $champs['Id']

        ];
        $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
        // Si les deux requêtes ont réussi = true
        if ($resultCommande > 0 && $resultCommandeDocument > 0) {
            return true;
        } else {
            // Si une des requêtes échoue = false
            return false;
        }
    }
    
    public function supprimerDetailCommande(?array $champs): bool {
        if ($champs === null || 
        !isset($champs['Id'])) {
        return 0;
    }
    $requeteCommandeDocument = "DELETE FROM commandedocument WHERE id = :id";
    $paramsCommandeDocument = [
        'id' => $champs['Id']
    ];
    $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
    $requeteCommande = "DELETE FROM commande WHERE id = :id";
    $paramsCommande = [
        'id' => $champs['Id']
    ];
    $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    

        return $resultCommande + $resultCommandeDocument;
    
    } 
    
    
}