<?PHP

	$numRE = "/^\d+$/";
	$fieldInfo = array();
	
	function validateField ($table, $field, $val) {
		global $numRE, $fieldInfo;
		
		if (!isset($fieldInfo[$table]))
			return false;
		if (!isset($fieldInfo[$table][$field]))
			return false;
		
		if ($fieldInfo[$table][$field]['type'] == 'string')
			return ($fieldInfo[$table][$field]['length'] < strlen($val));
		elseif ($fieldInfo[$table][$field]['type'] == 'int')
			return (in_array('not_null', explode(' ', $fieldInfo[$table][$field]['flags'])) && ($val === NULL || $val === ''));
		
	}
	
	function harvestFields ($table) {
		global $fieldInfo, $db;
		
		$result = $db->query("SELECT * FROM $table WHERE 0 = 1", false);
		$fieldInfo[$table] = array();
		while ($f = $result->fetch_field) {
			$fieldInfo[$table][$f->name] = array('type' => $db->dataTypeToText($f->type), 'length' => $f->max_len, 'flags' => $db->flagsToText($f->flags));
		}
	}
	
	if (!isset($_GET['action']))
		exit;
	
	require_once('classes/db.php');
	$db = new Database();
	
	harvestFields('SierraAddressBook');
	harvestFields('SierraAddressBookAddress');
	harvestFields('SierraAddressBookPhone');
	harvestFields('SierraAddressBookEmail');
	
	switch (strtolower($_GET['action'])) {
		case 'insert':
			$statement = $db->callProcedure("spSierraAddressBookInsert(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array((string)$firstName, (string)$lastName, (string)$title, (string)$companyName, (int)$phoneTypeID, (string)$phoneNumber, (int)$addressTypeID, (string)$address, (string)$address2, (string)$addressCity, (string)$addressState, (string)$addressZip, (string)$addressCountry));
		break;
		
		case 'insertphone':
			if (!isset($_GET['entryID']) || !isset($_GET['phoneTypeID']))
				exit;
			if (!preg_match($numRE, $_GET['entryID']) || !preg_match($numRE, $_GET['phoneTypeID']))
				exit;
			$params = array($_GET['entryID'], $_GET['phoneTypeID']);
			foreach ($fieldInfo['SierraAddressBookPhone'] as $col => $colInfo) {
				if (isset($_GET[$col]) && ($colInfo['type'] == 'string' || ($colInfo['type'] == 'int' && preg_match($numRE, $_GET[$col])) || ($colInfo['type'] == 'bit' && ($_GET[$col] == 'true' || $_GET[$col] == 'false' || $_GET[$col] == '1' || $_GET[$col] == '0' || !$_GET[$col])))) {
					$params[] = $_GET[$col];
				}
				else
					$params[] = NULL;
			}
			$statement = $db->callProcedure("spSierraAddressBookInsertPhone(?, ?, ?, ?)", $params);
		break;
		
		case 'insertaddress':
			if (!isset($_GET['entryID']) || !isset($_GET['addressTypeID']))
				exit;
			if (!preg_match($numRE, $_GET['entryID']) || !preg_match($numRE, $_GET['addressTypeID']))
				exit;
			$params = array($_GET['entryID'], $_GET['addressTypeID']);
			foreach ($fieldInfo['SierraAddressBookPhone'] as $col => $colInfo) {
				if (isset($_GET[$col]) && ($colInfo['type'] == 'string' || ($colInfo['type'] == 'int' && preg_match($numRE, $_GET[$col])) || ($colInfo['type'] == 'bit' && ($_GET[$col] == 'true' || $_GET[$col] == 'false' || $_GET[$col] == '1' || $_GET[$col] == '0' || !$_GET[$col])))) {
					$params[] = $_GET[$col];
				}
				else
					$params[] = NULL;
			}
			$statement = $db->callProcedure("spSierraAddressBookInsertAddress(?, ?, ?, ?, ?, ?, ?, ?, ?)", $params);
		break;
		
		case 'update':
			if (!isset($_GET['entryID']))
				exit;
			if (!preg_match($numRE, $_GET['entryID']))
				exit;
			$columnSets = [];
			$params = [];
			foreach ($fieldInfo['SierraAddressBook'] as $col => $colInfo) {
				if (isset($_GET[$col]) && ($colInfo['type'] == 'string' || ($colInfo['type'] == 'int' && preg_match($numRE, $_GET[$col])))) {
					$columnSets[] = "$col = ?";
					$params[] = $_GET[$col];
				}
				$params[] = $_GET['entryID'];
			}
			$db->query("UPDATE SierraAddressBook SET ".implode(', ', $columnSets)." WHERE entryID = ?", $params);
		break;
		
		case 'updatephone':
			if (!isset($_GET['entryID']) || !isset($_GET['phoneTypeID']))
				exit;
			if (!preg_match($numRE, $_GET['entryID']) || !preg_match($numRE, $_GET['phoneTypeID']))
				exit;
			$columnSets = [];
			$params = [];
			foreach ($fieldInfo['SierraAddressBookPhone'] as $col => $colInfo) {
				if (isset($_GET[$col]) && ($colInfo['type'] == 'string' || ($colInfo['type'] == 'int' && preg_match($numRE, $_GET[$col])))) {
					$columnSets[] = "$col = ?";
					$params[] = $_GET[$col];
				}
				$params[] = $_GET['entryID'];
				$params[] = $_GET['phoneTypeID'];
			}
			$db->query("UPDATE SierraAddressBookPhone SET ".implode(', ', $columnSets)." WHERE entryID = ? AND phoneTypeID = ?", $params);
		break;
		
		case 'updateaddress':
			if (!isset($_GET['entryID']) || !isset($_GET['addressTypeID']))
				exit;
			if (!preg_match($numRE, $_GET['entryID']) || !preg_match($numRE, $_GET['addressTypeID']))
				exit;
			$columnSets = [];
			$params = [];
			foreach ($fieldInfo['SierraAddressBookAddress'] as $col => $colInfo) {
				if (isset($_GET[$col]) && ($colInfo['type'] == 'string' || ($colInfo['type'] == 'int' && preg_match($numRE, $_GET[$col])))) {
					$columnSets[] = "$col = ?";
					$params[] = $_GET[$col];
				}
				$params[] = $_GET['entryID'];
				$params[] = $_GET['addressTypeID'];
			}
			$db->query("UPDATE SierraAddressBook SET ".implode(', ', $columnSets)." WHERE entryID = ? AND addressTypeID = ?", $params);
		break;
		
		case 'delete':
			$entryID = (isset($_GET['entryID']) && preg_match($numRE, $_GET['entryID'])) ? $_GET['entryID'] : 0;
			if ($entryID)
				$db->query("DELETE FROM SierraAddressBook WHERE entryID = ?", array((int)$entryID));
		break;
		
		case 'deletephone':
			$entryID = (isset($_GET['entryID']) && preg_match($numRE, $_GET['entryID'])) ? $_GET['entryID'] : 0;
			$phoneTypeID = (isset($_GET['phoneTypeID']) && preg_match($numRE, $_GET['phoneTypeID'])) ? $_GET['phoneTypeID'] : 0;
			if ($entryID && $phoneTypeID)
				$db->query("DELETE FROM SierraAddressBookPhone WHERE entryID = ? AND phoneTypeID = ?", array((int)$entryID, (int)$phoneTypeID));
		break;
		
		case 'deleteaddress':
			$entryID = (isset($_GET['entryID']) && preg_match($numRE, $_GET['entryID'])) ? $_GET['entryID'] : 0;
			$addressTypeID = (isset($_GET['addressTypeID']) && preg_match($numRE, $_GET['addressTypeID'])) ? $_GET['addressTypeID'] : 0;
			if ($entryID && $phoneTypeID)
				$db->query("DELETE FROM SierraAddressBookAddress WHERE entryID = ? AND addressTypeID = ?", array((int)$entryID, (int)$addressTypeID));
		break;
	}
	
	$db->disconnect();

?>