<?php

namespace App\Repository;

class PrefRepository
{
    /**
     * @param $prsnlId
     * @param $idListe
     * @param $listeUs
     * @param $connection
     */
    public static function createPref($prsnlId,$idListe,$listeUs,$connection): bool
    {
        $nurseUnit = "";
        $nbParams = sizeof($listeUs);
        foreach($listeUs as $key => $value) {
            if($key < $nbParams-1) {
                $nurseUnit .= $value . ",";
            }
            else {
                $nurseUnit .= $value;
            };
        }

        $query = "INSERT INTO cust_mpage_preferences 
                              (CMP_ID,
                              CMP_KEYWORD,
                              CMP_VALUE1,
                              CMP_VALUE2) 
                        VALUES(:prsnlId,
                              'atlas.listeParamed',
                              :idListe,
                              :nurseUnit)";

        $values = ['prsnlId' => $prsnlId
                 , 'idListe' => $idListe
                 , 'nurseUnit' => $nurseUnit];
        $types = ['prsnlId' => \PDO::PARAM_STR
                , 'idListe' => \PDO::PARAM_STR
                , 'nurseUnit' => \PDO::PARAM_STR];

        $stmt = $connection->executeQuery($query,$values,$types);

        return true;
    }

    /**
     * @param $logId
     * @param $idListe
     * @param $listeUs
     * @param $connection
     */
    public static function updatePref($prsnlId,$idListe,$listeUs,$connection): bool
    {
        $nurseUnit = "";
        $nbParams = sizeof($listeUs);
        foreach($listeUs as $key => $value) {
            if($key < $nbParams-1) {
                $nurseUnit .= $value . ",";
            }
            else {
                $nurseUnit .= $value;
            };
        }

        $query = "UPDATE cust_mpage_preferences 
                  SET CMP_VALUE1 = :idListe
                  , CMP_VALUE2 = :nurseUnit
                  WHERE CMP_ID = :prsnlId,
                  AND CMP_KEYWORD = 'atlas.listeParamed'";

        $values = ['prsnlId' => $prsnlId
                 , 'idListe' => $idListe
                 , 'nurseUnit' => $nurseUnit];
        $types = ['prsnlId' => \PDO::PARAM_INT
                , 'idListe' => \PDO::PARAM_STR
                , 'nurseUnit' => \PDO::PARAM_STR];

        $stmt = $connection->executeQuery($query,$values,$types);

        return true;
    }

    /**
     * @param $prsnlId
     * @param $connection
     */
    public static function getExistingPref($prsnlId,$connection): array
    {
        $query = "SELECT 
                  CMP_ID as PREF_ID
                  FROM cust_mpage_preferences
                  WHERE CMP_ID = :prsnlId
                  AND CMP_KEYWORD = 'atlas.listeParamed'";

        $values = ['prsnlId' => $prsnlId];
        $types = ['prsnlId' => \PDO::PARAM_STR];

        $stmt = $connection->executeQuery($query,$values,$types);

        $prefId = $stmt->fetchAll();

        return $prefId;
    }
}
