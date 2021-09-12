<?php

namespace App\Repository;

class EncounterRepository
{
    /**
     * @param $listeId
     * @param $nurseUnit
     * @param $connection
     */
    public static function findPreloadedListeParamed($listeId, $nurseUnit, $connection): array
    {
        $listeParamedicaux = array();
        $currentDate = new \DateTime();
        $previousDate = clone $currentDate;
        $previousDate->modify('-70 days')->setTime(0, 0);
        $previousDate = $previousDate->format("Y-m-d H:i:s");
        $currentDate = $currentDate->format("Y-m-d H:i:s");

        $query = "SELECT
                  p.person_id
                , p.name_full_formatted
                , p.birth_dt_tm
                , pa.alias
                , e.encntr_id
                , e.loc_nurse_unit_cd
                , cv1.display as loc_nurse_unit_display
                , e.loc_room_cd
                , cv2.display as loc_room_display
                , e.loc_bed_cd
                , cv3.display as loc_bed_display
                , o.order_id
                , o.catalog_cd
                , cv4.display as catalog_display
                , to_char(cast(o.current_start_dt_tm as timestamp) at time zone 'Europe/Athens','YYYY-MM-DD hh24:MI:SS') as date_presc
                , o.order_status_cd
                , pr.name_full_formatted as last_updater
                  FROM chun_preload_paramedicaux cp
                  INNER JOIN encounter e
                    ON cp.encntr_id = e.encntr_id
                    AND e.disch_dt_tm IS NULL
                    AND e.loc_nurse_unit_cd in (:nurseUnit)
                    AND e.active_ind = 1
                  INNER JOIN orders o
                    ON cp.order_id = o.order_id
                    AND o.active_ind = 1
                    AND o.template_order_id = 0
                    AND o.order_status_cd = 2550
                    AND o.updt_dt_tm > :previousDate
                  INNER JOIN person p
                    ON cp.person_id = p.person_id
                    AND p.active_ind = 1
                  INNER JOIN person_alias pa
                    ON cp.person_id = pa.person_id
                    AND pa.active_ind = 1
                    AND pa.person_alias_type_cd = 10
                    AND :currentDate BETWEEN pa.beg_effective_dt_tm and pa.end_effective_dt_tm
                INNER JOIN prsnl pr
                    ON o.updt_id = pr.person_id
                INNER JOIN code_value cv1
                    ON cv1.code_value = e.loc_nurse_unit_cd
                INNER JOIN code_value cv2
                    ON cv2.code_value = e.loc_room_cd
                INNER JOIN code_value cv3
                    ON cv3.code_value = e.loc_bed_cd
                INNER JOIN code_value cv4
                    ON cv4.code_value = o.catalog_cd
                  WHERE cp.nurse_unit_cd in (:nurseUnit)
                  AND cp.liste_id = :listeId
                  ORDER BY cp.order_id";
        $values = ['nurseUnit' => $nurseUnit
                 , 'currentDate' => $currentDate
                 , 'previousDate' => $previousDate
                 , 'listeId' => $listeId];
        $types = ['nurseUnit' => $connection::PARAM_INT_ARRAY
                , 'currentDate' => \PDO::PARAM_STR
                , 'previousDate' => \PDO::PARAM_STR
                , 'listeId' => \PDO::PARAM_INT];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeParamedicaux = $stmt->fetchAll();

        return $listeParamedicaux;
    }

    /**
     * @param $activityType
     * @param $activitySsType
     * @param $nurseUnit
     * @param $connection
     */
    public static function findDeltaListeParamed($activityType, $activitySsType, $nurseUnit, $connection): array
    {
        $listeParamedicaux = array();
        $currentDate = new \DateTime(); 

        $lastRun = clone $currentDate;
        $lastRun->modify('-2 hours');
        $currentDate = $currentDate->format("Y-m-d H:i:s");
        $lastRun = $lastRun->format("Y-m-d H:i:s");

        $query = "SELECT 
                  p.person_id
                , p.name_full_formatted
                , p.birth_dt_tm
                , pa.alias
                , e.encntr_id
                , e.loc_nurse_unit_cd
                , cv1.display as loc_nurse_unit_display
                , e.loc_room_cd
                , cv2.display as loc_room_display
                , e.loc_bed_cd
                , cv3.display as loc_bed_display
                , o.order_id
                , o.catalog_cd
                , cv4.display as catalog_display
                , to_char(cast(o.current_start_dt_tm as timestamp) at time zone 'Europe/Athens','YYYY-MM-DD hh24:MI:SS') as date_presc
                , o.order_status_cd
                , pr.name_full_formatted as last_updater
                FROM encounter e
                INNER JOIN orders o
                    ON e.encntr_id = o.encntr_id
                    AND o.updt_dt_tm BETWEEN :lastRun and :currentDate
                    AND o.active_ind = 1
                    AND o.template_order_id = 0
                    AND o.order_status_cd = 2550
                    AND NOT EXISTS (SELECT * FROM chun_preload_paramedicaux cp
                                    WHERE o.order_id = cp.order_id)
                INNER JOIN order_catalog oc
                    ON o.catalog_cd = oc.catalog_cd
                    AND oc.active_ind = 1
                    AND oc.activity_type_cd in (:activityType)";
                    if (!empty($activitySsType)) {
                        $query .= " AND oc.activity_subtype_cd in (:activitySsType)";
                    }
        $query .= " INNER JOIN person p
                        ON p.person_id = e.person_id
                        AND p.active_ind = 1
                    INNER JOIN person_alias pa
                        ON pa.person_id = p.person_id
                        AND pa.active_ind = 1
                        AND pa.person_alias_type_cd = 10
                        AND :currentDate BETWEEN pa.beg_effective_dt_tm and pa.end_effective_dt_tm
                    INNER JOIN prsnl pr
                        ON o.updt_id = pr.person_id
                    INNER JOIN code_value cv1
                        ON cv1.code_value = e.loc_nurse_unit_cd
                    INNER JOIN code_value cv2
                        ON cv2.code_value = e.loc_room_cd
                    INNER JOIN code_value cv3
                        ON cv3.code_value = e.loc_bed_cd
                    INNER JOIN code_value cv4
                        ON cv4.code_value = o.catalog_cd
                    WHERE e.loc_nurse_unit_cd in (:nurseUnit)
                    AND e.disch_dt_tm IS NULL
                    AND e.active_ind = 1
                    ORDER BY o.order_id";
                
        $values = ['nurseUnit' => $nurseUnit
                 , 'currentDate' => $currentDate
                 , 'lastRun' => $lastRun
                 , 'activityType' => $activityType
                 , 'activitySsType' => $activitySsType];
        $types = ['nurseUnit' => $connection::PARAM_INT_ARRAY
                , 'currentDate' => \PDO::PARAM_STR
                , 'lastRun' => \PDO::PARAM_STR
                , 'activityType' => $connection::PARAM_INT_ARRAY
                , 'activitySsType' => $connection::PARAM_INT_ARRAY];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeParamedicaux = $stmt->fetchAll();

        return $listeParamedicaux;
    }

    /**
     * @param $activityType
     * @param $activitySsType
     * @param $nurseUnit
     * @param $nbJour
     * @param $connection
     */
    public static function findAllListeParamedicaux($activityType, $activitySsType, $nurseUnit, $nbJour, $connection): array
    {
        $listeParamedicaux = array();
        $currentDate = new \DateTime(); 

        if ($nbJour != 0) {
            $previousDate = clone $currentDate;
            $previousDate->modify('-'.$nbJour.' days')->setTime(0, 0);
            $nextDate = clone $currentDate;
            $nextDate->modify('+1 day')->setTime(0, 0);
            $currentDate= $currentDate->format("Y-m-d H:i:s");
            $previousDate = $previousDate->format("Y-m-d H:i:s");
            $nextDate = $nextDate->format("Y-m-d H:i:s");
        }

        $query = "SELECT 
                  p.person_id
                , p.name_full_formatted
                , p.birth_dt_tm
                , pa.alias
                , e.encntr_id
                , e.loc_nurse_unit_cd
                , cv1.display as loc_nurse_unit_display
                , e.loc_room_cd
                , cv2.display as loc_room_display
                , e.loc_bed_cd
                , cv3.display as loc_bed_display
                , o.order_id
                , o.catalog_cd
                , cv4.display as catalog_display
                , to_char(cast(o.current_start_dt_tm as timestamp) at time zone 'Europe/Athens','YYYY-MM-DD hh24:MI:SS') as date_presc
                , o.order_status_cd
                , pr.name_full_formatted as last_updater
                FROM order_catalog oc
                INNER JOIN orders o
                    ON o.active_ind = 1
                    AND o.template_order_id = 0
                    AND o.order_status_cd = 2550
                    AND o.catalog_cd = oc.catalog_cd";                    
                    if ($nbJour != 0) {
                        $query .= " AND o.updt_dt_tm BETWEEN :previousDate and :nextDate";
                    }
    $query .= " INNER JOIN encounter e
                    ON e.encntr_id = o.encntr_id
                    AND e.disch_dt_tm IS NULL
                    AND e.loc_nurse_unit_cd in (:nurseUnit)
                    AND e.active_ind = 1
                INNER JOIN person p
                    ON p.person_id = e.person_id
                    AND p.active_ind = 1
                INNER JOIN person_alias pa
                    ON pa.person_id = p.person_id
                    AND pa.active_ind = 1
                    AND pa.person_alias_type_cd = 10
                    AND :currentDate BETWEEN pa.beg_effective_dt_tm and pa.end_effective_dt_tm
                INNER JOIN prsnl pr
                    ON o.updt_id = pr.person_id
                INNER JOIN code_value cv1
                    ON cv1.code_value = e.loc_nurse_unit_cd
                INNER JOIN code_value cv2
                    ON cv2.code_value = e.loc_room_cd
                INNER JOIN code_value cv3
                    ON cv3.code_value = e.loc_bed_cd
                INNER JOIN code_value cv4
                    ON cv4.code_value = o.catalog_cd
                WHERE oc.active_ind = 1
                AND oc.activity_type_cd in (:activityType)";
                if (!empty($activitySsType)) {
                    $query .= " AND oc.activity_subtype_cd in (:activitySsType)";
                }
    $query .= " ORDER BY pa.alias ASC";  

        $values = ['nurseUnit' => $nurseUnit
                    , 'currentDate' => $currentDate
                    , 'previousDate' => $previousDate
                    , 'nextDate' => $nextDate
                    , 'activityType' => $activityType
                    , 'activitySsType' => $activitySsType];
        $types = ['nurseUnit' => $connection::PARAM_INT_ARRAY
                , 'currentDate' => \PDO::PARAM_STR
                , 'previousDate' => \PDO::PARAM_STR
                , 'nextDate' => \PDO::PARAM_STR
                , 'activityType' => $connection::PARAM_INT_ARRAY
                , 'activitySsType' => $connection::PARAM_INT_ARRAY];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeParamedicaux = $stmt->fetchAll();

        return $listeParamedicaux;
    }
}
