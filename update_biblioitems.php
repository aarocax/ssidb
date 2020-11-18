<?php

/*
 * Example: php update_biblioitems.php {db_user} {db_server} {db_name} biblioitems url biblioitemnumber http://mdc.cbuc.cat/u?/ https://mdc.csuc.cat/digital/collection/ --dry-run
 */

$dryRun = true;

if (count($argv) < 9) {
	echo "Modo de empleo: update_biblioitems.php DB_USER HOST DB_NAME FIELD_UPDATE FIELD_UNIQUE STRING REPLACE_STRING\n";
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

$conn = connect($userName, $password, $server, $dbName);

$sql = "select `$uniqueField`, `$field` from `$tableName` where `$field` like '%".$str."%'";
$stmt = $conn->prepare($sql);
$stmt->execute();

$i = 0;

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	$urls = explode('|', $row['url']);
	$recordValue = '';
	$updateRecord = false;
	foreach ($urls as $key => $url) {
		$urlParts = explode('/', $url);
		if (array_key_exists(4, $urlParts)) {
			$lastPart = explode(',', $urlParts[4]);
			if (count($lastPart) > 1) {
				$urls[$key] = 'https://mdc.csuc.cat/digital/collection/'.$lastPart[0].'/'.$lastPart[1];
				$updateRecord = true;
			}
			$recordValue .= $urls[$key] . ' | ';
		}
	}

	if ($updateRecord) {
		$recordValue = substr($recordValue, 0, -3);
		if (!$dryRun) {
			try {
				$sql = "UPDATE `$tableName` SET `$field`=? WHERE `$uniqueField`=?";
		    	$stmta = $conn->prepare($sql);
			    if ( !$stmta->execute([$recordValue, $row[$uniqueField]]) ) {
					var_dump($conn->errorInfo());echo "\n";
		    		var_dump($stmta->debugDumpParams());echo "\n";
				}
			    echo $recordValue . " => ";
			    echo "record updated...\n";
		    } catch (Exception $e) {
		    	echo $e->getMessage();
		    }
		}
		
	}
	$i++;
}

$message = ($dryRun) ? "records to update...\n" : "records updated...\n";

echo "\n" . $i . ' ' . $message;

$conn = null;

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