<?php

namespace App\Repository;

class NurseUnitRepository
{
    /**
     * @param $nurseUnit
     * @param $connection
     */
    public static function findPreloadedNurseUnits($nurseUnit, $connection): array
    {
        $listeUsPreloaded = array();

        $query = "SELECT
	              nurse_unit_cd as us_id
	            , active_status as preload_ind
                FROM chun_preload_us u
                WHERE u.nurse_unit_cd in (:nurseUnit)
                ORDER BY preload_ind";

        $values = ['nurseUnit' => $nurseUnit];
        $types = ['nurseUnit' => $connection::PARAM_INT_ARRAY];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeUsPreloaded = $stmt->fetchAll();

        return $listeUsPreloaded;
    }

    /**
     * @param $connection
     */
    public static function findAllFacilities($connection): array
    {
        $listeFacilities = array();
        $currentDate = new \DateTime();
        $currentDate= $currentDate->format("Y-m-d H:i:s");

        $query = "SELECT DISTINCT
	              n.loc_facility_cd as f_id
	            , cv1.description as f_label
                FROM nurse_unit n
                INNER JOIN code_value cv1
                    ON cv1.code_value = n.loc_facility_cd
                WHERE n.active_ind = 1
                AND n.end_effective_dt_tm > :currentdate
                AND n.loc_facility_cd != 0
                AND n.loc_building_cd != 0
                ORDER BY
	              f_label";

        $values = ['currentdate' => $currentDate];
        $types = ['currentdate' => \PDO::PARAM_STR];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeFacilities = $stmt->fetchAll();

        return $listeFacilities;
    }

    /**
     * @param $connection
     * @param $facility
     */
    public static function findAllBuildings($connection, $facility): array
    {
        $listeBuildings = array();
        $currentDate = new \DateTime();
        $currentDate= $currentDate->format("Y-m-d H:i:s");

        $query = "SELECT DISTINCT
	              n.loc_building_cd as b_id
	            , cv1.description as b_label
                FROM nurse_unit n
                INNER JOIN code_value cv1
                    ON cv1.code_value = n.loc_building_cd
                WHERE n.active_ind = 1
                AND n.end_effective_dt_tm > :currentdate
                AND n.loc_facility_cd != 0
                AND n.loc_building_cd != 0
                AND n.loc_facility_cd = :facility
                ORDER BY
	              b_label";

        $values = ['currentdate' => $currentDate,
                   'facility' => $facility];
        $types = ['currentdate' => \PDO::PARAM_STR,
                  'facility' => \PDO::PARAM_INT];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeBuildings = $stmt->fetchAll();

        return $listeBuildings;
    }

    /**
     * @param $connection
     * @param $building
     */
    public static function findAllLocations($connection, $building): array
    {
        $listeLocations = array();
        $currentDate = new \DateTime();
        $currentDate= $currentDate->format("Y-m-d H:i:s");

        $query = "SELECT DISTINCT
	              n.location_cd as l_id
	            , cv1.description as l_label
                FROM nurse_unit n
                INNER JOIN code_value cv1
                    ON cv1.code_value = n.location_cd
                WHERE n.active_ind = 1
                AND n.end_effective_dt_tm > :currentdate
                AND n.loc_facility_cd != 0
                AND n.loc_building_cd != 0
                AND n.loc_building_cd = :building
                ORDER BY
                  l_label";

        $values = ['currentdate' => $currentDate,
                   'building' => $building];
        $types = ['currentdate' => \PDO::PARAM_STR,
                  'building' => \PDO::PARAM_INT];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeLocations = $stmt->fetchAll();

        return $listeLocations;
    }
}
