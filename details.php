<?PHP

	$numRE = "/^\d+$/";
	
	$entryID = (isset($_GET['entryID']) && preg_match($numRE, $_GET['entryID'])) ? $_GET['entryID'] : 0;
	if (!$entryID)
		exit;
	
	$db = new Database();
	
	if (isset($_GET['insertaddress']) && isset($_POST['address'])) {
		header("Content-type: application/json");
		echo '{';
		
		$db->callProcedure('CALL spSierraAddressBookInsertAddress (?, ?, ?, ?, ?, ?, ?, ?, ?)', array(array('i', $entryID), array('s', $_POST['addressType']), array('s', $_POST['address']), array('s', $_POST['address2']), array('s', $_POST['addressCity']), array('s', $_POST['addressState']), array('s', $_POST['addressZip']), array('s', $_POST['addressCountry'])));
		
		echo '}';
	}
	
	elseif (isset($_GET['insertphone']) && isset($_POST['phoneNumer'])) {
	
	}
	
	elseif (isset($_GET['insertemail']) && isset($_POST['emailAddress'])) {
		
	}

?>