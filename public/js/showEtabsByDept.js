$(document).ready(function() {

	var url_getEtablissementsByNumDepartement = getEtablissementsByNumDepartementAjaxPath;
	var departement_numero= $('#dept_num').val();
	$('[id^="lien_departement_"]').click(function() {
		var tbl_id = $(this).attr('id').split("_");
		departement_numero = tbl_id[2];
		var idTypeEtab = $('#tdbEtabType_typeEtablissement').val();
		var natEtab = $('#tdbEtabType_natureEtablissement').val();
		var codeTypeElect = $('#tdbEtabType_typeElection').val();
		var idSousTypeElect = $('#tdbEtabType_sousTypeElection').val();
		$.ajax({
			data : 'departement_numero='+departement_numero + '&idTypeEtab=' + idTypeEtab + '&natEtab=' + natEtab + '&codeTypeElect=' + codeTypeElect  + '&idSousTypeElect=' + idSousTypeElect,
			type: 'POST',
            url : url_getEtablissementsByNumDepartement,
            success : function(data) {
            	$('#etablissements_by_dept').html(data);
            	// YME - DEFECT HPQC #212 mise à jour du numero de département sélectionné
            	$('#departement_selectionne').val(departement_numero);
            }
        });
	});

	if (departement_numero != null && departement_numero != '') {
		$('#lien_departement_'+departement_numero).click();
		$("#tabBordZone").show();
		$("#choix_tabBordZone").hide();
		$("#masquer_tabBordZone").show();
		$('#lien_departement_'+departement_numero).focus();
		$('#dept_num').val('');
	}
});