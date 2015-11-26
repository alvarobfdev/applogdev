<?php
/**
 * Created by PhpStorm.
 * User: alvarobanofos
 * Date: 25/11/15
 * Time: 11:38
 */

namespace App\Http\Controllers;



class ImporterController extends Controller
{
    const FILE_SCHEMA = "schemaDB";
    private $schemas = array();
    private $columnsTypesFunctions = array(
        "smallint" => "smallInteger",
        "char" => "string",
        "decimal" => "decimal",
        "integer" => "integer",
        "date" => "date",
        "money" => "decimal",
        "serial" => "bigIncrements",
        "time" => "time",
    );

    public function getImportSchema() {

        $contents = \Storage::disk("local")->get(ImporterController::FILE_SCHEMA);
        $this->filterContents($contents);
        $lines = $this->getLines($contents);
        $this->prepareSchemas($lines);

    }

    /**
     * @param $contents
     */
    public function filterContents(&$contents)
    {
        $contents = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $contents);
    }

    /**
     * @param $contents
     * @return array
     */
    public function getLines($contents)
    {
        $lines = explode(PHP_EOL, $contents);
        return $lines;
    }

    public function prepareSchemas($lines)
    {
        $lastSchema = null;
        $columnsdDefinition = false;


        foreach($lines as $line) {

            if($line) {
                if (strpos($line, "Columnas de la tabla :") !== false) {
                    $tableName = explode(":", $line);
                    $tableName = end($tableName);
                    str_replace(" ", "", $tableName);
                    $this->schemas[$tableName] = array();
                    $lastSchema = $tableName;
                    $columnsdDefinition = false;
                }

                if (strpos($line, "Indice duplicado  :") !== false) {
                    $columnsdDefinition = false;

                }

                if (strpos($line, "Indice unico      :") !== false) {
                    $columnsdDefinition = false;

                }

                if (strpos($line, "Clave primaria    :") !== false) {
                    $columnsdDefinition = false;
                }

                if ($columnsdDefinition) {
                    $column = $this->getColumnDefinition($line);
                }

                if ($line == "-----------------------------------------------------------------------") {
                    $columnsdDefinition = true;
                }
            }
        }
    }

    public function getColumnDefinition($line) {
        $columnDefinitions = explode(" ", $line);
        foreach($columnDefinitions as $index => $columnDefinition) {
            if(!$columnDefinition)
                unset($columnDefinitions[$index]);
        }
        $columnDefinitions = array_values($columnDefinitions);
        $definitionsConverted = $this->convertColumnDefinitions($columnDefinitions);
    }

    public function convertColumnDefinitions($columnDefinitions)
    {
        $result = array();
        $result["columnName"] = $columnDefinitions[0];
        $result["columnType"] = $this->getColumnTypeFunction($columnDefinitions[1]);
        if($columnDefinitions[2] == "N") {
            $result["columnNotNull"] = true;
            $result["length"] = $columnDefinitions[3];
            array_splice($columnDefinitions, 0, 4);
        }
        else {
            $result["columnNotNull"] = false;
            $result["length"] = $columnDefinitions[2];
            array_splice($columnDefinitions, 0, 3);
        }

        $result["comment"] = implode(" ", $columnDefinitions);
        var_dump($result);
    }



    public function getColumnTypeFunction($columnType) {
        $result = null;
        if(array_key_exists($columnType, $this->columnsTypesFunctions)) {
            $result = $this->columnsTypesFunctions[$columnType];
        }
        return $result;
    }

}