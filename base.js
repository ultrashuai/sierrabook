	function openForm () {
		$('#formContainer').dialog({
			modal:true, width:600, height:400
		});
	}
	
	function cancelForm () {
		$('#formContainer').dialog('close');
	}
	
	function viewDetails (ev) {
		window.location.href = '?entryID=' + this.id.substr(5);
	}
	
	function openDetails () {
		$('#detailsContainer').dialog({
			modal:true, width:800, height:600
		});
	}
	
	function deleteMe () {
		if (!confirm('Are you sure you want to delete this record?'))
			return false;
		window.location.href = '?entryID=' + this.parentNode.parentNode.id.substr(5) + '?delete';
	}
	
	function editMe () {
		window.location.href = '?entryID=' + this.id.substr(11) + '&edit';
	}
	
	
	function setPrimaryAddress () {
		setPrimary('address', this.id.split('_')[1]);
	}
	function setPrimaryPhone () {
		setPrimary('phone', this.id.split('_')[1]);
	}
	function setPrimaryEmail () {
		setPrimary('email', this.id.split('_')[1]);
	}