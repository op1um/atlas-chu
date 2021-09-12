<?php

namespace App\Repository;

class CodeValueRepository
{

    /**
     * @param $codeValue
     * @param $connection
     */
    public static function findDisplayByCode($codeValue, $connection): string {
        $query = "SELECT cv.display
                  FROM code_value cv
                  WHERE cv.code_value = :codeValue";
        $values = ['codeValue' => $codeValue];
        $types = ['codeValue' => \PDO::PARAM_INT];

        $stmt = $connection->executeQuery($query,$values,$types);

        $listeParamedicaux = $stmt->fetchAll();

        return $listeParamedicaux[0]['DISPLAY'];
    }
}
