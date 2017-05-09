<?PHP

	print_r($_POST);
	
	$cols = array(
		'fullName' => 'Name', 'companyName' => 'Company', 'phoneNumberWithType' => 'Phone Number', 'emailAddressWithType' => 'Email Address'
	);
	$pageSize = 50;
	$numRE = "/^\d+$/";
	
	$entryID = (isset($_GET['entryID']) && preg_match($numRE, $_GET['entryID'])) ? $_GET['entryID'] : 0;
	
	require_once('classes/db.php');
	$db = new Database();
	
	
	// edit routines
	if (isset($_GET['create']) && isset($_POST['firstName'])) {
		$db->callProcedure("CALL spSierraAddressBookInsert (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(array('s', $_POST['firstName']), array('s', $_POST['lastName']), array('s', $_POST['title']), array('s', $_POST['companyName']), array('s', $_POST['phoneType']), array('s', $_POST['phoneNumer']), array('s', $_POST['addressType']), array('s', $_POST['address']), array('s', $_POST['address2']), array('s', $_POST['addressCity']), array('s', $_POST['addressState']), array('s', $_POST['addressZip']), array('s', $_POST['addressCountry']), array('s', $_POST['emailType']), array('s', $_POST['emailAddress'])));
		//header("Location: ./");
		exit;
	}
	elseif ($entryID) {
		if (isset($_GET['edit']) && isset($_POST['firstName'])) {
			$db->query("UPDATE SierraAddressBook SET firstName = ".$db->format($_POST['firstName']).", lastName = ".$db->format($_POST['lastName']).", `title` = ".$db->format($_POST['title']).", companyName = ".$db->format($_POST['companyName'])." WHERE entryID = $entryID");
			//header("Location: ./");
			exit;
		}
		elseif (isset($_GET['delete'])) {
			$db->query("DELETE FROM SierraAddressBook WHERE entryID = $entryID");
			//header("Location: ./");
			exit;
		}
		else {
			$db->query("SELECT * FROM SierraAddressBookAddress WHERE entryID = $entryID");
			$addresses = $db->results;
			$db->query("SELECT * FROM SierraAddressBookPhone WHERE entryID = $entryID");
			$phones = $db->results;
			$db->query("SELECT * FROM SierraAddressBookEmail WHERE entryID = $entryID");
			$emails = $db->results;
		}
	}
	
	if (!isset($_GET['create']) || $entryID) {
		// get types
		$db->query("SHOW COLUMNS FROM SierraAddressBookAddress WHERE Field = 'addressType'");
		preg_match("/^enum\(\'(.*)\'\)$/", $db->firstRow['Type'], $matches);
		$addressTypes = explode("','", $matches[1]);
		
		$db->query("SHOW COLUMNS FROM SierraAddressBookPhone WHERE Field = 'phoneType'");
		preg_match("/^enum\(\'(.*)\'\)$/", $db->firstRow['Type'], $matches);
		$phoneTypes = explode("','", $matches[1]);
		
		$db->query("SHOW COLUMNS FROM SierraAddressBookEmail WHERE Field = 'emailType'");
		preg_match("/^enum\(\'(.*)\'\)$/", $db->firstRow['Type'], $matches);
		$emailTypes = explode("','", $matches[1]);
	}
	
	
	
	$whereString = 'entryID = entryID';
	if (isset($_GET['searchText']) && strlen($_GET['searchText'])) {
		$searchText = $db->format($_GET['searchText']);
		$searchText = substr($searchText, 1, strlen($searchText) - 1);
		$whereString .= " AND (fullName LIKE '%$searchText%' OR companyName LIKE '%$searchText%')";
	}
	$sortString = "fullName ASC";
	if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $cols)) {
		$sortString = $_GET['sort'];
		if (isset($_GET['dir']) && (strtolower($_GET['dir']) == 'asc' || strtolower($_GET['dir'] == 'desc')))
			$sortString .= ' '.$_GET['dir'];
	}
	$pagingString = "0,$pageSize";
	if (isset($_GET['page']) && preg_match($numRE, $_GET['page']))
		$pagingString = (((int)$page - 1) * $pageSize).",$pageSize";
			
	$query = "SELECT * FROM vwSierraAddressBookEntries WHERE $whereString ORDER BY $sortString LIMIT $pagingString";
	//echo $query;
	$db->query($query);
	$entryResults = $db->results;
	
	$result = $db->query("SELECT * FROM vwSierraAddressBookEntries WHERE entryID = $entryID");
	$record = array();
	
	if (!$entryID) {
		while ($fieldInfo = mysqli_fetch_field($result)) {
			$record[$fieldInfo->name] = '';
		}
	}
	else
		$record = $db->firstRow;

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Address Book</title>
		<meta charset="UTF-8">
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<link rel="stylesheet" type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
		<script type="text/javascript" src="base.js"></script>
		<script type="text/javascript">
			onload = function () {
				var lis = document.querySelectorAll('nav ul li');
				lis[0].onclick = function () {
					return openForm();
				}
				lis[1].onclick = function () {
					this.style.display = 'none';
					document.getElementById('searchContainer').style.display = 'inline-block';
					document.getElementById('searchText').focus();
				}
				
				document.getElementById('searchCancel').addEventListener('click', function () {
					document.getElementById('searchText').value = '';
					document.getElementById('searchContainer').style.display = 'none';
					document.querySelector('nav ul li')[1].style.display = '';
					window.location.href = '?<?= preg_replace("/&?searchText=\w+/", '', $CGI['QUERY_STRING']) ?>';
				});
				
				document.getElementById('searchButton').addEventListener('click', function () {
					var searchText = document.getElementById('searchText').value;
					window.location.href = '?searchText=' + searchText;
				});
				
				document.getElementById('cancelButton').addEventListener('click', cancelForm);
				
				var deletes = document.getElementsByClassName('deleteMe');
				for (var i = 0; i < deletes.length; i++) {
					deletes[i].addEventListener('click', deleteMe);
				}
				
				// build sortable columns
				var cols = document.querySelectorAll('#resultsContainer table th');
				for (var i = 0; i < cols.length; i++) {
					var col = cols[i];
					if (!col.getAttribute('sortkey'))
						continue;
					col.addEventListener('click', function () {
						window.location.href = '?<?= ($CGI['QUERY_STRING']) ? preg_replace("/&?sort=\w+&dir=(asc|desc)/", $CGI['QUERY_STRING'], '').'&' : '' ?>sort=' + this.getAttribute('sortkey') + '&dir=' + ((this.getAttribute('sortkey') == '<?= $sortArray[0] ?>' && '<?= $sortArray[1] ?>' == 'ASC') ? 'DESC' : 'ASC');
					});
				}
				
				var entries = document.querySelectorAll('#resultsContainer tbody tr');
				for (var i = 0; i < entries.length; i++) {
					entries[i].addEventListener('click', viewDetails);
				}
				
				document.getElementById('editButton_<?= $entryID ?>').addEventListener('click', editMe);
				
				document.getElementById('closeButton').addEventListener('click', function () {
					$('#detailsContainer').dialog('close');
				});
				
				var addressPrimary = document.getElementsByName('addressPrimary');
				for (var i = 0; i < addressPrimary.length; i++) {
					addressPrimary[i].addEventListener('click', setPrimaryAddress);
				}
				var phonePrimary = document.getElementsByName('phonePrimary');
				for (var i = 0; i < phonePrimary.length; i++) {
					phonePrimary[i].addEventListener('click', setPrimaryPhone);
				}
				var emailPrimary = document.getElementsByName('emailPrimary');
				for (var i = 0; i < emailPrimary.length; i++) {
					emailPrimary[i].addEventListener('click', setPrimaryEmail);
				}
				
				var deleteAddress = document.getElementsByClassName('deleteAddress');
				for (var i = 0; i < deleteAddress.length; i++) {
					deleteAddress[i].addEventListener('click', deleteAddress);
				}
				var deletePhone = document.getElementsByClassName('deletePhone');
				for (var i = 0; i < deletePhone.length; i++) {
					deletePhone[i].addEventListener('click', deletePhone);
				}
				var deleteEmail = document.getElementsByClassName('deleteEmail');
				for (var i = 0; i < deleteEmail.length; i++) {
					deleteEmail[i].addEventListener('click', deleteEmail);
				}
				
				// edit form
				if (window.location.search.match(/entryID=\d/)) {
					if (window.location.search.match(/edit/))
						openForm();
					else
						openDetails();
				}
			}
		</script>
		
		<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
		<link rel="stylesheet" type="text/css" href="app.css" />
	</head>
<body>
	<header>
		<h1>Address Book</h1>
	</header>
	<nav>
		<ul>
			<li>Create New Record</li>
			<li><span>Search</span><span id="searchContainer"><input type="text" placeholer="Text" id="searchText" /> <span id="searchCancel" class="fa fa-cross"></span> <input type="button" id="searchButton" value="Search" /></span></li>
		</ul>
	</nav>
	<div id="resultsContainer">
<?PHP

	if (!count($entryResults))
		echo 'No results';
	else {
		echo '<table><thead><tr>';
		foreach ($cols as $col => $name) {
			echo "<th sortkey=\"$col\"><span title=\"Sort by $name\">$name</span></th>";
		}
		echo '<th>&nbsp;</th>';
		echo "</tr></thead>\n<tbody>\n";
		foreach ($entryResults as $row) {
			echo '	<tr valign="top" id="entry'.$row['entryID'].'"><td>'.$row['fullName'].'</td><td>'.$row['companyName'].'</td><td>'.$row['phoneNumberWithType'].'</td><td><a href="mailto:'.$row['primaryEmailAddress'].'">'.$row['emailAddressWithType']."</a></td><td><span class=\"fa fa-trash deleteMe\"></span></tr>\n";
		}
		echo "</tbody></table>\n";
	}
?>
	</div>
	
	<div id="detailsContainer">
		<input type="button" value="Edit" id="editButton_<?= $entryID ?>" />
		<fieldset>
			<legend>Details</legend>
			<p>Last Edit: <?= date("n/j/Y", strtotime((($record['updateDate']) ? $record['updateDate'] : $record['createDate'])))  ?></p>
			<div id="titleContainer">
				<label for="title">Title</label><?= $record['title'] ?>
			</div>
			<div>
				<label for="firstName">First Name</label><?= $record['firstName'] ?>
			</div>
			<div>
				<label for="lastName">Last Name</label><?= $record['lastName'] ?>
			</div>
			<div>
				<label for="companyName">Company</label><?= $record['companyName'] ?>
			</div>
<?PHP

	foreach ($addresses as $i=>$address) {

?>
			<div class="addressContainer" id="addressContainer<?= $address['addressID'] ?>">
			<fieldset>
				<legend>Address <span class="fa fa-trash deleteAddress" id="deleteAddress<?= $address['addressID'] ?>"></span></legend>
				<div>
					<p><label for="addressType">Type</label><select id="addressType_<?= $address['addressID'] ?>">
<?PHP

		foreach ($addressTypes as $type) {
			echo "				<option value=\"$type\"";
			if ($type == $address['addressType'])
				echo ' selected';
			echo ">$type</option>\n";
		}

?>
					</select></p>
					<p><label for="addressPrimary_<?= $address['addressID'] ?>">Primary</label><input type="radio" name="addressPrimary" value="<?= $address['addressID'] ?>" id="addressPrimary_<?= $address['addressID'] ?>"<?= ($address['isPrimary']) ? ' checked' : '' ?> /></p>
				</div>
				<div>
					<label for="address">Address</label><input id="address_<?= $address['addressID'] ?>" type="text" maxlength="255" value="<?= $address['address'] ?>" />
				</div>
				<div>
					<label for="address2">Address 2</label><input id="address2_<?= $address['addressID'] ?>" type="text" maxlength="255" value="<?= $address['address2'] ?>" />
				</div>
				<div>
					<label for="addressCity">City</label><input id="addressCity_<?= $address['addressID'] ?>" type="text" maxlength="255" value="<?= $address['addressCity'] ?>" />
				</div>
				<div>
					<label for="addressState">State/Province</label><input id="addressState_<?= $address['addressID'] ?>" type="text" maxlength="2" value="<?= $address['addressState'] ?>" />
				</div>
				<div>
					<label for="addressZip">Zip/Postal Code</label><input id="addressZip_<?= $address['addressID'] ?>" type="text" maxlength="10" value="<?= $address['addressZip'] ?>" />
				</div>
				<div>
					<label for="addressCountry">Country</label><input id="addressCountry_<?= $address['addressID'] ?>" type="text" maxlength="255" value="<?= $address['addressCountry'] ?>" />
				</div>
				<div>
					<button id="saveAddress_<?= $address['addressID'] ?>" class="saveAddressButton">Save</button>
			</fieldset>
			</div>
<?PHP
	}
	
	foreach ($phones as $i=>$phone) {
?>
			<div id="phoneContainer_<?= $phone['phoneID'] ?>">
				<fieldset>
				<legend>Type: <select id="phoneType_<?= $phone['phoneID'] ?>">
<?PHP

		foreach ($phoneTypes as $type) {
			echo "				<option value=\"$type\"";
			if ($type == $phone['phoneType'])
				echo ' selected';
			echo ">$type</option>\n";
		}

?>
				</select> | <input type="radio" name="phonePrimary" value="<?= $phone['phoneID'] ?>"<?= ($phone['isPrimary']) ? ' checked' : '' ?> /> Primary</legend>
				<p><label for="phoneNumber">Phone Number</label><input id="phoneNumber_<?= $phone['phoneID'] ?>" type="tel" maxlength="255" value="<?= $phone['phoneNumer'] ?>" /></p>
				</fieldset>
			</div>
<?PHP

	}
	
	foreach ($emails as $i=>$email) {

?>
			<div>
				<fieldset>
					<legend>Type: <select id="emailType_<?= $email['emailID'] ?>">
<?PHP

		foreach ($emailTypes as $type) {
			echo "				<option value=\"$type\"";
			if ($type == $email['emailType'])
				echo ' selected';
			echo ">$type</option>\n";
		}

?>
				</select> | <input type="radio" name="emailPrimary" value="<?= $email['emailID'] ?>"<?= ($email['isPrimary']) ? ' checked' : '' ?> /> Primary</legend>
				<p><label for="emailAddress">Email Address</label><input id="emailAddress_<?= $email['emailID'] ?>" type="email" maxlength="255" /></p>
				</fieldset>
			</div>
<?PHP
	}
?>
		</fieldset>
		
		<input type="button" value="Close" id="closeButton" />
	</div>
	
	<div id="formContainer">
		<form method="post" action="?<?= ($entryID) ? "entryID=$entryID" : 'create' ?>" onSubmit="validate(this);">
		<fieldset>
			<legend><?= ($entryID) ? 'Edit' : 'Add' ?> Record</legend>
			<div id="titleContainer">
				<label for="title">Title</label>
			</div>
			<div>
				<label for="firstName">First Name</label><input id="firstName" name="firstName" type="text" value="<?= $record['firstName'] ?>" required />
			</div>
			<div>
				<label for="lastName">Last Name</label><input id="lastName" name="lastName" type="text" value="<?= $record['lastName'] ?>" required />
			</div>
			<div>
				<label for="companyName">Company</label><input id="companyName" name="companyName" type="text" value="<?= $record['companyName'] ?>" />
			</div>
<?PHP
	if (!$entry) {
?>
			<div id="addressContainer">
			<fieldset>
				<legend>Address</legend>
				<div>
					<label for="addressType">Type</label><select id="addressType" name="addressType">
<?PHP

		foreach ($addressTypes as $type) {
			echo "				<option value=\"$type\">$type</option>\n";
		}

?>
					</select>
				<div>
					<label for="address">Address</label><input id="address" name="address" type="text" maxlength="255" />
				</div>
				<div>
					<label for="address2">Address 2</label><input id="address2" name="address2" type="text" maxlength="255" />
				</div>
				<div>
					<label for="addressCity">City</label><input id="addressCity" name="addressCity" type="text" maxlength="255" />
				</div>
				<div>
					<label for="addressState">State/Province</label><input id="addressState" name="addressState" type="text" maxlength="2" />
				</div>
				<div>
					<label for="addressZip">Zip/Postal Code</label><input id="addressZip" name="addressZip" type="text" maxlength="10" />
				</div>
				<div>
					<label for="addressCountry">Country</label><input id="addressCountry" name="addressCountry" type="text" maxlength="255" />
				</div>
			</fieldset>
			</div>
			<div>
				<label for="phoneNumber">Phone Number</label><select id="phoneType" name="phoneType">
<?PHP

		foreach ($phoneTypes as $type) {
			echo "				<option value=\"$type\">$type</option>\n";
		}

?>
				</select><input id="phoneNumber" name="phoneNumber" type="tel" maxlength="255" />
			</div>
			<div>
				<label for="emailAddress">Email Address</label><select id="emailType" name="emailType">
<?PHP

		foreach ($emailTypes as $type) {
			echo "				<option value=\"$type\">$type</option>\n";
		}

?>
				</select><input id="emailAddress" name="emailAddress" type="email" maxlength="255" />
			</div>
<?PHP
	}
?>
			<div>
				<input type="submit" value="Save" id="saveButton" /> &nbsp; <input type="button" value="Cancel" id="cancelButton" />
			</div>
		</fieldset>
		</form>
	</div>
</body>
</html>