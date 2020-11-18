<?php

/*
 * Example: php update_biblio_metadata.php {db_user} {db_server} {db_name} biblio_metadata metadata id http://mdc.cbuc.cat/u?/ https://mdc.csuc.cat/digital/collection/ --dry-run
 */

$dryRun = true;

if (count($argv) < 9) {
	echo "Modo de empleo: scan.php DB_USER HOST DB_NAME FIELD_UPDATE FIELD_UNIQUE STRING REPLACE_STRING\n";
	exit;
}

if (array_key_exists(9, $argv)) {
	if ($argv[9] != '--dry-run') {
		echo "Modo de empleo: --dry-run\n";
		exit;
	}
} else {
	$dryRun = false;
}

$userName = $argv[1];
$server = $argv[2];
$dbName = $argv[3];
$tableName = $argv[4];
$field = $argv[5];
$uniqueField = $argv[6];
$str = $argv[7];
$strNew = $argv[8];
$password = getPassword();

$recordsToUpdate = [];

$conn = connect($userName, $password, $server, $dbName);

$records = getData($conn, $uniqueField, $field, $tableName, $str);

echo "Número de registros en la bbdd que contienen el patrón $str: ".count($records)."\n";

$recordsToUpdate = prepareData($records, $str, $strNew, $field, $uniqueField);

if ($dryRun) {
	echo "Número de entradas con el patrón $str para actulizar step 1: ".count($recordsToUpdate)."\n";
}

if (!$dryRun) {
	$newRecords = updateData($records, $recordsToUpdate, $field, $uniqueField);
	updateDb($newRecords, $conn, $tableName, $field, $uniqueField);
}

// *********** step 2

$records = getData($conn, $uniqueField, $field, $tableName, $str);

echo "Número de registros en la bbdd que contienen el patrón $str: ".count($records)."\n";

$recordsToUpdate = prepareData2($records, $str, $strNew, $field, $uniqueField);

if ($dryRun) {
	echo "Número de entradas con el patrón $str para actulizar step 2: ".count($recordsToUpdate)."\n";
}

if (!$dryRun) {
	$newRecords = updateData($records, $recordsToUpdate, $field, $uniqueField);
	updateDb($newRecords, $conn, $tableName, $field, $uniqueField);
}

function updateDb($newRecords, $conn, $tableName, $field, $uniqueField)
{
	foreach ($newRecords as $key => $record) {
		try {
    	$stmta = $conn->prepare("UPDATE `$tableName` SET `$field`=:meta WHERE `$uniqueField`=:id");
    	$stmta->bindParam(':meta', $record[$field], PDO::PARAM_STR);
			$stmta->bindValue(':id', $record[$uniqueField], PDO::PARAM_INT);
			if (!$stmta->execute()) {
				// var_dump("error:");echo "\n";
				// var_dump($conn->errorInfo());echo "\n";
				// var_dump("debug params:");echo "\n";
	   //  	var_dump($stmta->debugDumpParams());echo "\n";
			} else {
				echo $record[$uniqueField] . " => ";
	    	echo "record updated...\n";
			}
    } catch (Exception $e) {
    	echo $e->getMessage();
    }
	}
}


function updateData($records, $recordsToUpdate, $field, $uniqueField)
{
	foreach ($recordsToUpdate as $key => $record) {
		$id = $record[$uniqueField];
		foreach ($records as $key2 => $value) {

			if ($value[$uniqueField] == $id) {
				$records[$key2][$field] = str_replace($record['old'], $record['new'], $records[$key2][$field]);
			}
		}
	}
	return $records;
}

// actualiza 2244
function prepareData($records, $str, $strNew, $field, $uniqueField)
{
	$recordsToUpdate = [];
	foreach ($records as $key => $record) {
		$xml = new SimpleXMLElement(utf8_encode($record[$field]));
		if ($xml->record->datafield != null) {
			foreach ($xml->record->datafield as $key2 => $value) {
				foreach ((array)$value->subfield as $key3 => $val) {
					if (!is_array($val)) {
						if (strpos($val, $str) !== false) {
							$arr = [];
							$arr[$uniqueField] = $record[$uniqueField];
							$arr['old'] = $val;
							$arr['new'] = getNewString($val);
							$recordsToUpdate[] = $arr;
						}
					}
				}
			}
		}
	}

	return $recordsToUpdate;
}

function prepareData2($records, $str, $strNew, $field, $uniqueField)
{
	$recordsToUpdate = [];
	foreach ($records as $key => $record) {
		$xml = new SimpleXMLElement(utf8_encode($record[$field]));
		if ($xml->datafield != null) {
			foreach ($xml->datafield as $key2 => $value) {
				foreach ((array)$value->subfield as $key3 => $val) {
					if (!is_array($val)) {
						if (strpos($val, $str) !== false) {
							$arr = [];
							$arr[$uniqueField] = $record[$uniqueField];
							$arr['old'] = $val;
							$arr['new'] = getNewString($val);
							$recordsToUpdate[] = $arr;
						}
					}
				}
			}
		}
	}

	return $recordsToUpdate;
}

function getNewString($strToChange)
{
	$strNew = '';
	$urlParts = explode('/', $strToChange);
	if (array_key_exists(4, $urlParts)) {
		$lastPart = explode(',', $urlParts[4]);
		if (count($lastPart) > 1) {
			$strNew = 'https://mdc.csuc.cat/digital/collection/'.$lastPart[0].'/'.$lastPart[1];
		}
	}

	return $strNew;
}

function getData($conn, $uniqueField, $field, $tableName, $str)
{
	$sql = "select `$uniqueField`, `$field` from `$tableName` where `$field` like '%".$str."%'";
	$stmt = $conn->prepare($sql);
	$stmt->execute([$str]);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function connect($userName, $password, $server, $dbName)
{
	try {
	    $dsn = "mysql:host=$server;dbname=$dbName";
	    $conn = new PDO($dsn, $userName, $password);
	} catch (PDOException $e){
	    exit(0);
	}

	return $conn;
}

function getPassword($prompt = "Enter Password:") {
    echo $prompt;
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n\n";
    return $password;
}
