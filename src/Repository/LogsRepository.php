<?php

namespace App\Repository;

class LogsRepository
{
    /**
     * @param $prsnlId
     * @param $idListe
     * @param $nameListe
     * @param $listeUs
     * @param $connection
     */
    public static function createLog($prsnlId,$idListe,$nameListe,$listeUs,$connection): bool
    {
        $nurseUnit = "";
        $currentDate = new \DateTime();
        $nbParams = sizeof($listeUs);
        foreach($listeUs as $key => $value) {
            if($key < $nbParams-1) {
                $nurseUnit .= $value . ",";
            }
            else {
                $nurseUnit .= $value;
            };
        }
        $currentDate= $currentDate->format("Y-m-d H:i:s");

        $query = "INSERT INTO chun_atlas_logs 
                              (PRSNL_ID,
                              MPAGE,
                              LOG_DT_TM,
                              LOG_STATUS,
                              LIST_ID,
                              LIST_NAME,
                              NB_PARAMS,
                              VALUE_PARAMS) 
                        VALUES(:prsnlId,
                              'LISTE_PARAMED', 
                              :currentdate,
                              'KO',
                              :idListe,
                              :nameListe,
                              :nbParams,
                              :nurseUnit)";

        $values = ['prsnlId' => $prsnlId
                 , 'currentdate' => $currentDate
                 , 'idListe' => $idListe
                 , 'nameListe' => $nameListe
                 , 'nbParams' => $nbParams
                 , 'nurseUnit' => $nurseUnit];
        $types = ['prsnlId' => \PDO::PARAM_STR
                , 'currentdate' => \PDO::PARAM_STR
                , 'idListe' => \PDO::PARAM_STR
                , 'nameListe' => \PDO::PARAM_STR
                , 'nbParams' => \PDO::PARAM_STR
                , 'nurseUnit' => \PDO::PARAM_STR];

        $stmt = $connection->executeQuery($query,$values,$types);

        return true;
    }

    /**
     * @param $logId
     * @param $connection
     */
    public static function updateLog($logId,$connection): bool
    {
        $currentDate = new \DateTime();
        $currentDate= $currentDate->format("Y-m-d H:i:s");

        $query = "UPDATE chun_atlas_logs 
                  SET LOG_STATUS = 'OK'
                  , RESPONSE_DT_TM = :currentdate
                  WHERE LOG_ID = :logId";

        $values = ['logId' => $logId
                 , 'currentdate' => $currentDate];
        $types = ['logId' => \PDO::PARAM_INT
                , 'currentdate' => \PDO::PARAM_STR];

        $stmt = $connection->executeQuery($query,$values,$types);

        return true;
    }

    /**
     * @param $prsnlId
     * @param $idListe
     * @param $listeUs
     * @param $connection
     */
    public static function getLastInsertedLog($prsnlId,$idListe,$listeUs,$connection): array
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
        $query = "SELECT 
                  max(LOG_ID) as LOGID
                  FROM chun_atlas_logs
                  WHERE PRSNL_ID = :prsnlId
                  AND LIST_ID = :idListe
                  AND LOG_STATUS = 'KO'
                  AND NB_PARAMS = :nbParams
                  AND VALUE_PARAMS = :nurseUnit";

                  $values = ['prsnlId' => $prsnlId
                 , 'idListe' => $idListe
                 , 'nbParams' => $nbParams
                 , 'nurseUnit' => $nurseUnit];
        $types = ['prsnlId' => \PDO::PARAM_STR
                , 'idListe' => \PDO::PARAM_STR
                , 'nbParams' => \PDO::PARAM_STR
                , 'nurseUnit' => \PDO::PARAM_STR];

        $stmt = $connection->executeQuery($query,$values,$types);

        $LogId = $stmt->fetchAll();

        return $LogId;
    }
}
