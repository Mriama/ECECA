$(document).ready(function() {
	
    // Gestion filtres tdb Acceuil

    // BBL defects HPQC 233- 235- 234
    
	// disabled nature etab si c pas 1er degre
	if($('select#tdbEtabType_typeEtablissement').val() != ID_TYP_1ER_DEGRE){
		$('#tdbEtabType_natureEtablissement').attr("disabled", true);
		$('#tdbEtabType_typeElection').removeAttr("disabled");
		document.getElementById("tdbEtabType_natureEtablissement").value = '';
	}else{
		$('#tdbEtabType_natureEtablissement').removeAttr("disabled");
		$('#tdbEtabType_typeElection').attr("disabled", true);
		document.getElementById("tdbEtabType_typeElection").value = CODE_PE;
		
	}

	if($('select#tdbEtabType_typeEtablissement').val() != ''  && $('select#tdbEtabType_typeEtablissement').val() != ID_TYP_1ER_DEGRE  && $('select#tdbEtabType_typeEtablissement').val() != ID_TYP_EREA_ERPD){			
		// Sous type elect ASS et ATE, PEE
		$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").remove();
		$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_SS+"']").remove();
		if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_ASS_ATE+"']").length == 0) {
			$("#tdbEtabType_sousTypeElection option[value='']").remove();
			$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_ASS_ATE+"'>ASS et ATE</option>");
			$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
			document.getElementById("tdbEtabType_sousTypeElection").value = '';
		}
	} else if($('select#tdbEtabType_typeEtablissement').val() == ID_TYP_EREA_ERPD){
		if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").length == 0) {
			$("#tdbEtabType_sousTypeElection option[value='']").remove();
			$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_SS+"'>SS</option>");
			$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_A_ATTE+"'>A et ATTE</option>");
			$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
		}
		$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_ASS_ATE+"']").remove();
	} else {
		document.getElementById("tdbEtabType_sousTypeElection").value = '';
		$('#tdbEtabType_sousTypeElection').attr("disabled", true);
	} 
	
    //////////////////////////////////////////////////////////////

	if($('select#tdbEtabType_typeElection').val() == CODE_RP){
		$('#tdbEtabType_sousTypeElection').removeAttr("disabled");
		$('#tdbEtabType_typeEtablissement').removeAttr("disabled");
		
		$("#tdbEtabType_typeEtablissement option[value='"+ID_TYP_1ER_DEGRE+"']").remove();
		$("#tdbEtabType_typeEtablissement option[value='']").remove();
	} else {
		$('#tdbEtabType_sousTypeElection').attr("disabled", true);
		
		document.getElementById("tdbEtabType_sousTypeElection").value = '';
		
		if ($("#tdbEtabType_typeEtablissement option[value='']").length == 0) {
			$("#tdbEtabType_typeEtablissement").prepend("<option value='"+ID_TYP_1ER_DEGRE+"'>1er degré</option>");
			$("#tdbEtabType_typeEtablissement").prepend("<option value=''>Tous</option>");
			document.getElementById("tdbEtabType_typeEtablissement").value = '';
		}
		if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").length == 0) {
			$("#tdbEtabType_sousTypeElection option[value='']").remove();
			$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_SS+"'>SS</option>");
			$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_A_ATTE+"'>A et ATTE</option>");
			$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
			document.getElementById("tdbEtabType_sousTypeElection").value = '';
		}
	}
    /////////////////////////////////////////////////////////////////////////////////
	
	// Si Aet ATTE ou SS type etab = EREA ERPD et grisé type etab
	if($('select#tdbEtabType_sousTypeElection').val() == ID_TYP_ELECT_A_ATTE || $('select#tdbEtabType_sousTypeElection').val() == ID_TYP_ELECT_SS){
		$('#tdbEtabType_typeEtablissement').attr("disabled", true);
		document.getElementById("tdbEtabType_typeEtablissement").value = ID_TYP_EREA_ERPD;
	}else{
		$('#tdbEtabType_typeEtablissement').attr("disabled", false);
	}
	
	////////////////////////////////////////////////////////////////////////////////////
	
	// On change  Type Elect
	$('#tdbEtabType_typeElection').change(function() {
		if($('select#tdbEtabType_typeElection').val() == CODE_RP) {
			
			$('#tdbEtabType_sousTypeElection').removeAttr("disabled");
			$('#tdbEtabType_typeEtablissement').removeAttr("disabled");
			
			$("#tdbEtabType_typeEtablissement option[value='"+ID_TYP_1ER_DEGRE+"']").remove();
			$("#tdbEtabType_typeEtablissement option[value='']").remove();
			$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").remove();
			$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_SS+"']").remove();

			if($('select#tdbEtabType_typeEtablissement').val() == ID_TYP_EREA_ERPD){
				if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").length == 0) {
					$("#tdbEtabType_sousTypeElection option[value='']").remove();
					$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_SS+"'>SS</option>");
					$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_A_ATTE+"'>A et ATTE</option>");
					$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
				}
				$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_ASS_ATE+"']").remove();
				document.getElementById("tdbEtabType_sousTypeElection").value = '';
			} else if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_ASS_ATE+"']").length == 0) {
				$("#tdbEtabType_sousTypeElection option[value='']").remove();
				$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_ASS_ATE+"'>ASS et ATE</option>");
				$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
				document.getElementById("tdbEtabType_sousTypeElection").value = '';
			}
		} else {
			$('#tdbEtabType_sousTypeElection').attr("disabled", true);
			$('#tdbEtabType_typeEtablissement').removeAttr("disabled");

			document.getElementById("tdbEtabType_sousTypeElection").value = '';
			
			if ($("#tdbEtabType_typeEtablissement option[value='']").length == 0) {
				$("#tdbEtabType_typeEtablissement").prepend("<option value='"+ID_TYP_1ER_DEGRE+"'>1er degré</option>");
				$("#tdbEtabType_typeEtablissement").prepend("<option value=''>Tous</option>");
			}
			if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").length == 0) {
				$("#tdbEtabType_sousTypeElection option[value='']").remove();
				$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_SS+"'>SS</option>");
				$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_A_ATTE+"'>A et ATTE</option>");
				$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
				document.getElementById("tdbEtabType_sousTypeElection").value = '';
			}
		}
	});
	 ///////////////////////////////////////////////////////////////////////////////////////////////////
	
	// On change Sous Type Elect
	$('#tdbEtabType_sousTypeElection').change(function() {
		if($('select#tdbEtabType_sousTypeElection').val() == ID_TYP_ELECT_A_ATTE || $('select#tdbEtabType_sousTypeElection').val() == ID_TYP_ELECT_SS){
			$('#tdbEtabType_typeEtablissement').attr("disabled", true);
			document.getElementById("tdbEtabType_typeEtablissement").value = ID_TYP_EREA_ERPD;
		}else{
			$('#tdbEtabType_typeEtablissement').attr("disabled", false);
		}
	});
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// On change  Type Etab
	$('#tdbEtabType_typeEtablissement').change(function() {
		// disabled nature etab si c pas 1er degre
		if($('select#tdbEtabType_typeEtablissement').val() != ID_TYP_1ER_DEGRE){
			$('#tdbEtabType_natureEtablissement').attr("disabled", true);
			$('#tdbEtabType_typeElection').removeAttr("disabled");
			document.getElementById("tdbEtabType_natureEtablissement").value = '';
		}else{
			$('#tdbEtabType_natureEtablissement').removeAttr("disabled");
			$('#tdbEtabType_typeElection').attr("disabled", true);
			document.getElementById("tdbEtabType_typeElection").value = CODE_PE;
			
		}
	
		if($('select#tdbEtabType_typeEtablissement').val() != ''  && $('select#tdbEtabType_typeEtablissement').val() != ID_TYP_1ER_DEGRE  && $('select#tdbEtabType_typeEtablissement').val() != ID_TYP_EREA_ERPD){			
			// Sous type elect ASS et ATE, PEE
			$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").remove();
			$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_SS+"']").remove();
			if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_ASS_ATE+"']").length == 0) {
				$("#tdbEtabType_sousTypeElection option[value='']").remove();
				$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_ASS_ATE+"'>ASS et ATE</option>");
				$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
				document.getElementById("tdbEtabType_sousTypeElection").value = '';
			}
		} else if($('select#tdbEtabType_typeEtablissement').val() == ID_TYP_EREA_ERPD){
			if ($("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_A_ATTE+"']").length == 0) {
				$("#tdbEtabType_sousTypeElection option[value='']").remove();
				$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_SS+"'>SS</option>");
				$("#tdbEtabType_sousTypeElection").prepend("<option value='"+ID_TYP_ELECT_A_ATTE+"'>A et ATTE</option>");
				$("#tdbEtabType_sousTypeElection").prepend("<option value=''>Tous</option>");
			}
			$("#tdbEtabType_sousTypeElection option[value='"+ID_TYP_ELECT_ASS_ATE+"']").remove();
			document.getElementById("tdbEtabType_sousTypeElection").value = '';
		} else {
			document.getElementById("tdbEtabType_sousTypeElection").value = '';
			$('#tdbEtabType_sousTypeElection').attr("disabled", true);
		} 
	});
	// fin tdb acceuil

});

function formTdbSubmit(nomForm) {
    $('select#' + nomForm + '_sousTypeElection').removeAttr("disabled");
    $('select#' + nomForm + '_typeElecetion').removeAttr("disabled");
    $('select#' + nomForm + '_natureEtablissement').removeAttr("disabled");
    $('select#' + nomForm + '_typeEtablissement').removeAttr("disabled");
    $('select#' + nomForm + '_departement').removeAttr("disabled");
    $('select#' + nomForm + '_academie').removeAttr("disabled");
    return true;
}