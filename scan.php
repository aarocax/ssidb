<?php

/*
 * Example: php scan.php {db_user} {db_server} {db_name} http://mdc.cbuc.cat/u?/
 */

if (count($argv) < 5) {
	echo "Modo de empleo: scan.php DB_USER HOST DB_NAME REPLACE_STRING\n";
	exit;
}

$userName = $argv[1];
$server = $argv[2];
$dbName = $argv[3];
$str = $argv[4];
$password = getPassword();
$conn = connect($userName, $password, $server, $dbName);

$tablesToScan = getTables($conn, $dbName);

foreach ($tablesToScan as $key => $table) {
	$fields = getTableFields($conn, $table);
	foreach ($fields as $key2 => $field) {
		searchString($conn, $table, $field, $str);
	}
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

function getTables($conn, $dbName)
{
	$stmt = $conn->prepare("SHOW TABLES FROM `$dbName`");
	$stmt->execute();
	$tables = [];
	while( $row = $stmt->fetch(PDO::FETCH_OBJ) ) {
		$property_name = 'Tables_in_'.$dbName;
		$tables[] = $row->$property_name;
	}
	return $tables;
}

function getTableFields($conn, $tableName)
{
	$q = $conn->prepare("DESCRIBE `$tableName`");
	$q->execute();
	$table_fields = $q->fetchAll(PDO::FETCH_COLUMN);
	$fields = [];
	foreach ($table_fields as $key2 => $field) {
		$fields[] = $field;
	}
	return $fields;
}

function searchString($conn, $table, $field, $str)
{
	$sql = "SELECT count(*) FROM `$table` where `$field` like '%".$str."%'";
	$result = $conn->prepare($sql);
	$result->execute();
	$number_of_rows = $result->fetchColumn();
	if ($number_of_rows > 0) {
		echo 'table: '.$table.' - field: '.$field . ' -> '; var_export((integer)$number_of_rows); echo " records\n";
	}
}

function getPassword($prompt = "Enter Password:") {
    echo $prompt;
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n\n";
    return $password;
}

$conn = null;