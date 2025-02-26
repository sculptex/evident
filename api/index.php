<?php

include("config.php");
include("mysql.php");

$net = false;
$usr = false;
$hash = false;
$sig = false;
$action = "publish";

if(isset($_GET["net"])) {
	$net = $_GET["net"];
}

if(isset($_GET["usr"])) {
	$usr = $_GET["usr"];
}

if(isset($_GET["hash"])) {
	$hash = $_GET["hash"];
}

if(isset($_GET["sig"])) {
	$sig = $_GET["sig"];
}

if(isset($_GET["action"])) {
	$action = $_GET["action"];
}

$output = false;
$return = false;

function isValidHex(string $str): bool {
    return preg_match('/^[0-9a-f]+$/i', $str);
}

function isValidNet(string $str): bool {
    if (($str == "sol") || ($str == "zcn")) {
		return true;
	}
	return false;
}

function isValidHash(string $str): bool {
    return isValidHex($str);
}

function isValidUsr($usr) {
	if(strlen($usr) != 42) {
		return false;
	}
	if(!(substr($usr, 0, 2) == "0x")) {
		return false;
	}
	if(!isValidHex(substr($usr, 2))) {
		return false;
	}
	return true;
}

$data = array();
$data["success"] = "false";
$data["error"] = "";

if(!isValidNet($net)) {
	$data["error"] = "Invalid Network";
} else {
	if(!isValidHash($hash)) {
		$data["error"] = "Invalid hash";
	} else {
		if(!isValidUsr($usr)) {
			$data["error"] = "Invalid usr";
		} else {
			$sql = "SELECT * FROM ".$net." WHERE hash = '".$hash."'";
			if ($result = $mysqli -> query($sql)) {
				while ($row = $result -> fetch_assoc()) {
					$data["tx"] = $row["tx"];
					if($row["usr"] == $usr) {
						$data["error"] = "Already exists by you";
					} else {
						$data["error"] = "Already exists by USR: ".$row["usr"];
					}
				}
			}
		}
	}
}

if ($data["error"] == "") {
	if($action == "publish") {
	
		$msg = "EVIDENT.live/?".$hash.":".$usr;
		
		if($net == "zcn") {
			exec($appdir."/zcn/zwallet send --configDir ".$appdir."/zcn  --wallet myzcn.json --to_client_id c70826708e39951ba307d8c3637f0bfed163aa451008f1caea663891416d0d9b --tokens 0.000017 --silent --json --desc ".$msg , $output, $return); 
			if($return==0) {
				$json = $output[0];
				$res = json_decode($json, true);
				$tx = $res["tx"];
				if(strlen($tx) == 64) {
					$data["success"] = "true";
					$data["tx"] = $tx;
				} else {
					$data["error"] = "Invalid tx length";
				}
			} else {
				$data["error"] = print_r($output, true);
			}
		}
		
		if($net == "sol") {
			exec($appdir."/sol/solana transfer -k ".$appdir."/mysol.json 9X76P57BR3JPZ9eF5TYHWTx1KDXfEGogsRkLM4Ua6BV9 0.000017 --with-memo ".$msg , $output, $return); 
			if($return==0) {
				$tx = $output[1];
				$tx = str_replace("Signature: ", "", $tx);
				if((strlen($tx) > 80) && (strlen($tx) <= 88)) {
					$data["success"] = "true";
					$data["tx"] = $tx;
				} else {
					$data["error"] = "Invalid tx length";
				}
			} else {
				$data["error"] = print_r($output, true);
			}
		}
	}
}

if ($data["error"] == "") {
	if($action == "verify") {
		$data["error"] = "Not Found";
	}
}
header("Content-Type: application/json; charset=utf-8");
echo json_encode($data);

include("mysql.php");

if($data["success"] == "true") {
	$sql = "INSERT INTO ".$net." (usr, hash, tx, dt) VALUES ('".$usr."', '".$hash."', '".$tx."', now())";
	$mysqli->query($sql);
}

$mysqli -> close();
