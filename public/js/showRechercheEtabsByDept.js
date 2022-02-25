$(document).ready(function() {

	var url_getRechercheEtablissementsByNumDepartement = getRechercheEtablissementsByNumDepartementAjaxPath;
	$('#tdb_recherche').click(function() {
		
		// YME - DEFECT HPQC #212 - BBL Defect HPQC 245
		var departement_numero = $('#departement_selectionne').val();
		
		var idTypeEtab = $('#tdbEtabType_typeEtablissement').val();
		var natEtab = $('#tdbEtabType_natureEtablissement').val();
		var codeTypeElect = $('#tdbEtabType_typeElection').val();
		var idSousTypeElect = $('#tdbEtabType_sousTypeElection').val();
		
		var etatTransmis = "";
		var etatValide = "";
		var etatNonEff = "";
		var etatSaisi = "";
		var pvCarence = "";
		var nvElect = "";
		
		if ($('#tdbZoneEtabType_statutPv_0').prop('checked')) {
			etatTransmis = 'T';
		}
		
		if($('#tdbZoneEtabType_statutPv_1').prop('checked')) {
			etatValide = 'V';
		}
		
		if ($('#tdbZoneEtabType_etatAvancement_0').prop('checked')) {
			 etatNonEff = 'N';
		}
		
		if($('#tdbZoneEtabType_etatAvancement_1').prop('checked')) {
			etatSaisi = 'S';
		}
		
		
		if ($('#tdbZoneEtabType_pvCarence').prop('checked')) {
			pvCarence = 1;
		}
		
		if ($('#tdbZoneEtabType_nvElect').prop('checked')) {
			nvElect = 1;
		}
		// console.log(departement_numero);
		$.ajax({
			data : 'departement_numero=' + departement_numero + '&etatTransmis=' + etatTransmis + '&etatValide=' + etatValide + '&etatNonEff=' + etatNonEff + '&etatSaisi=' + etatSaisi + '&pvCarence=' + pvCarence + '&nvElect=' + nvElect + '&idTypeEtab=' + idTypeEtab + '&natEtab=' + natEtab + '&codeTypeElect=' + codeTypeElect  + '&idSousTypeElect=' + idSousTypeElect,
			type: 'POST',
            url : url_getRechercheEtablissementsByNumDepartement,
            success : function(data) {
            	$('#etablissements_by_dept').html(data);
            	if (etatTransmis == "T") {
            		$('#tdbZoneEtabType_statutPv_0').attr('checked','checked');
            	}
        		if (etatNonEff == "N") {
        			$('#tdbZoneEtabType_etatAvancement_0').attr('checked','checked');
        		}
        		if (etatValide == "V") {
        			$('#tdbZoneEtabType_statutPv_1').attr('checked','checked');
        		}
        		if (etatSaisi == "S") {
        			$('#tdbZoneEtabType_etatAvancement_1').attr('checked','checked');
        		}
        		if (pvCarence == 1) {
        			$('#tdbZoneEtabType_pvCarence').attr('checked','checked');
        		}
        		if (nvElect == 1) {
        			$('#tdbZoneEtabType_nvElect').attr('checked','checked');
        		}
            	
            }
        });
	});
});