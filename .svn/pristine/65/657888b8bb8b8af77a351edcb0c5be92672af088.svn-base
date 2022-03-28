/**
 * Javascript général EPLE
 * Atos - Avril 2013
 */

$(document).ready(function() {
	
	/* Ajout d'un attribut 'id' sur chaque élément 'option' d'une liste d'académies dans un formulaire SF2 */
	$('select#campagneZoneEtabType_academie').each(function() {
		var academieOptions = this.getElementsByTagName('option');
		for (var i=0; i<academieOptions.length; i++) {
			if (academieOptions[i].value != "") { academieOptions[i].setAttribute('id', academieOptions[i].value); }
		} 
	})
	
	/****** Ensemble d'évènements pour la recherche de zone et/ou d'établissements ********/
	/* chargement de la liste d'academie */
	$('select#campagneZoneEtabType_campagne').change(function() {
		console.log("OOOOOOOOOOOOH YEAFH")
		$("#academie_selectionne").val('');
		$("#departement_selectionne").val('');
		$("#commune_selectionne").val('');
		$("#etablissement_selectionne").val('');
		refreshActivesAcademies('campagneZoneEtabType');
		disableCheckBoxes('campagneZoneEtabType');
	});
	
	/* chargement de la liste d'academie */
	$('select#campagneZoneEtabType_academie').change(function() {
                $("#academie_selectionne").val($('select#campagneZoneEtabType_academie').val());
                $("#departement_selectionne").val('');
                $("#commune_selectionne").val('');
                $("#etablissement_selectionne").val('');
//		document.getElementById("campagneZoneEtabType_departement").options[0] = new Option("Tous", "");
//		document.getElementById("campagneZoneEtabType_departement").options.length = 1;
//		document.getElementById("departement_selectionne").value = "";
//		if (document.getElementById("campagneZoneEtabType_etablissement")) {
//			document.getElementById("campagneZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("campagneZoneEtabType_etablissement").options.length = 1;
//		}
                afficheAcademieDepartementCommune('campagneZoneEtabType');
                disableCheckBoxes('campagneZoneEtabType');
	});
	
	/* chargement de la liste d'academie, département et commune */
	$('select#campagneZoneEtabType_departement').change(function() {
		$("#departement_selectionne").val($('select#campagneZoneEtabType_departement').val());
        $("#commune_selectionne").val('');
        $("#etablissement_selectionne").val('');
//		document.getElementById("commune_selectionne").value = $('select#campagneZoneEtabType_commune').val();
//		if (document.getElementById("campagneZoneEtabType_etablissement")) {
//			document.getElementById("campagneZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("campagneZoneEtabType_etablissement").options.length = 1;
//		}
		afficheAcademieDepartementCommune('campagneZoneEtabType');
	});
	
	/* chargement de la liste des établissements */
	$('select#campagneZoneEtabType_commune').change(function() {
                $("#commune_selectionne").val($('select#campagneZoneEtabType_commune').val());
                $("#etablissement_selectionne").val('');
//		document.getElementById("departement_selectionne").value = $('select#campagneZoneEtabType_departement').val();
//		if (document.getElementById("campagneZoneEtabType_etablissement")) {
//			document.getElementById("campagneZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("campagneZoneEtabType_etablissement").options.length = 1;
//		}
		afficheAcademieDepartementCommune('campagneZoneEtabType');
	});
	
	/* chargement de la liste des établissement en fonction du type */
	$('select#campagneZoneEtabType_typeEtablissement').change(function() {
//		document.getElementById("departement_selectionne").value = $('select#campagneZoneEtabType_departement').val();
//		document.getElementById("commune_selectionne").value = $('select#campagneZoneEtabType_commune').val();
//		if (document.getElementById("campagneZoneEtabType_etablissement")) {
//			document.getElementById("campagneZoneEtabType_etablissement").options[0] = new Option("Tous", "");
//			document.getElementById("campagneZoneEtabType_etablissement").options.length = 1;
//		}		
        afficheAcademieDepartementCommune('campagneZoneEtabType');
	});
	
	/* chargement de la liste des établissement en fonction du type */
	$('select#campagneZoneEtabType_etablissement').change(function() {
		$("#etablissement_selectionne").val($('select#campagneZoneEtabType_etablissement').val());
		disableCheckBoxes('campagneZoneEtabType');
	});
	
	/* afficher les champs commune et liste des établissements en fonction du click */
	$('input#campagneZoneEtabType_choix_etab').click(function() {
		afficherChampsEtablissement('campagneZoneEtabType');
	});	
	
	/* afficher la liste des établissements en fonction du click */
//	$('input#campagneZoneEtabType_choix_etab').click(function() {
//		afficherListeEtablissement('campagneZoneEtabType');
//	});
	
	
	
	/* desactiver le select sur les campagnes dans statistiques generales */
	if ($("div#stats_campagne > select#campagneZoneEtabType_campagne").length) {
		$("div#stats_campagne > select#campagneZoneEtabType_campagne").attr("disabled", true);
	}
});
