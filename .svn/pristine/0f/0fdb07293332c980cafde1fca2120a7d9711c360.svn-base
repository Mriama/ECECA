/**
 * Javascript général EPLE
 * Atos - Avril 2013
 */

$(document).ready(function() {
	
	/* Ajout d'un attribut 'id' sur chaque élément 'option' d'une liste d'académies dans un formulaire SF2 */
	$('select#resultatZoneEtabType_academie').each(function() {
		var academieOptions = this.getElementsByTagName('option');
		for (var i=0; i<academieOptions.length; i++) {
			if (academieOptions[i].value != "") { academieOptions[i].setAttribute('id', academieOptions[i].value); }
		} 
	})
	
	/****** Ensemble d'évènements pour la recherche de zone et/ou d'établissements ********/
                
	
	/* chargement de la liste d'academie */
	$('select#resultatZoneEtabType_academie').change(function() {
                $("#academie_selectionne").val($('select#resultatZoneEtabType_academie').val());
                $("#departement_selectionne").val('');
                $("#commune_selectionne").val('');
                $("#etablissement_selectionne").val('');
//		document.getElementById("resultatZoneEtabType_departement").options[0] = new Option("Tous", "");
//		document.getElementById("resultatZoneEtabType_departement").options.length = 1;
//		document.getElementById("departement_selectionne").value = "";
//		if (document.getElementById("resultatZoneEtabType_etablissement")) {
//			document.getElementById("resultatZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("resultatZoneEtabType_etablissement").options.length = 1;
//		}
                afficheAcademieDepartementCommune('resultatZoneEtabType');
                disableCheckBoxes('resultatZoneEtabType');
	});
	
	/* chargement de la liste d'academie, département et commune */
	$('select#resultatZoneEtabType_departement').change(function() {
		$("#departement_selectionne").val($('select#resultatZoneEtabType_departement').val());
        $("#commune_selectionne").val('');
        $("#etablissement_selectionne").val('');
//		document.getElementById("commune_selectionne").value = $('select#resultatZoneEtabType_commune').val();
//		if (document.getElementById("resultatZoneEtabType_etablissement")) {
//			document.getElementById("resultatZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("resultatZoneEtabType_etablissement").options.length = 1;
//		}
		afficheAcademieDepartementCommune('resultatZoneEtabType');
	});
	
	/* chargement de la liste des établissements */
	$('select#resultatZoneEtabType_commune').change(function() {
                $("#commune_selectionne").val($('select#resultatZoneEtabType_commune').val());
                $("#etablissement_selectionne").val('');
//		document.getElementById("departement_selectionne").value = $('select#resultatZoneEtabType_departement').val();
//		if (document.getElementById("resultatZoneEtabType_etablissement")) {
//			document.getElementById("resultatZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("resultatZoneEtabType_etablissement").options.length = 1;
//		}
		afficheAcademieDepartementCommune('resultatZoneEtabType');
	});
	
	/* chargement de la liste des établissement en fonction du type */
	$('select#resultatZoneEtabType_typeEtablissement').change(function() {
		
//		document.getElementById("departement_selectionne").value = $('select#resultatZoneEtabType_departement').val();
//		document.getElementById("commune_selectionne").value = $('select#resultatZoneEtabType_commune').val();
//		if (document.getElementById("resultatZoneEtabType_etablissement")) {
//			document.getElementById("resultatZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("resultatZoneEtabType_etablissement").options.length = 1;
//		}		
        afficheAcademieDepartementCommune('resultatZoneEtabType');
        afficheSousTypeElection('resultatZoneEtabType');
	});
	
	
	$('select#resultatZoneEtabType_etablissement').change(function() {
		// chargement de la liste des établissements en fonction du type
		$("#etablissement_selectionne").val($('select#resultatZoneEtabType_etablissement').val());
		disableCheckBoxes('resultatZoneEtabType');		
	});
	
	/* afficher les champs commune et liste des établissements en fonction du click */
	$('input#resultatZoneEtabType_choix_etab').click(function() {
		afficherChampsEtablissement('resultatZoneEtabType');
	});	
});

/**
 * Vérifie si au moins un état d'avancement a été sélectionné dans le formulaire
 * de saisie
 * @returns {Boolean}
 */
function verifieEtatSaisie(nomForm){
	if( !$('#'+nomForm+'_etatSaisie').is("input")
	&&	!$('#'+nomForm+'_etatSaisie_0').prop('checked')
	&&	!$('#'+nomForm+'_etatSaisie_1').prop('checked')
	&& 	!$('#'+nomForm+'_etatSaisie_2').prop('checked')){
		alert('Sélectionner au moins un état d\'avancement');
		return false;
	}else{
		return formSubmit(nomForm);
	}
}
