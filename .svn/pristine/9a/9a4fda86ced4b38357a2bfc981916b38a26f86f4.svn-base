/**
 * Javascript général EPLE
 * Atos - Avril 2013
 */

$(document).ready(function() {
	
	/* Afffecter à l'attribut value de chaque option la valeur de son attribut text
	 */
	$('select#recapitulatifParticipationEtabType_typeEtablissement').each(function() {
		var academieOptions = this.getElementsByTagName('option');
		for (var i=0; i<academieOptions.length; i++) {
			academieOptions[i].setAttribute('value', academieOptions[i].text);
		}
	});
	
	
	/* changement de la catégorie d'établissements */
	$('select#recapitulatifParticipationEtabType_categorie').change(function() {
		var categorie = document.getElementById("recapitulatifParticipationEtabType_categorie");
		var typeEtablissement = document.getElementById("recapitulatifParticipationEtabType_typeEtablissement");
		
		$.post(EPLEElectionBundle_recapitulatif_participation, 
			{ 
				formCategory: categorie,
				formTypeEtab: typeEtablissement,
				function(data) {
					
				}
			}
		);
		
		/*document.getElementById("campagneZoneEtabType_departement").options[0] = new Option("Tous", "");
		document.getElementById("campagneZoneEtabType_departement").options.length = 1;
		document.getElementById("departement_selectionne").value = "";
		if(document.getElementById("campagneZoneEtabType_etablissement")){
			document.getElementById("campagneZoneEtabType_etablissement").options[0] = new Option("Tous", "");
			document.getElementById("campagneZoneEtabType_etablissement").options.length = 1;
		}
    	afficheAcademieDepartementCommune('campagneZoneEtabType');
		
		alert(categorie.options[categorie.selectedIndex].value + " " + typeEtablissment.options[typeEtablissment.selectedIndex].text);*/
	});
	
	/* changement du type d'établissements */
	
	$('select#recapitulatifParticipationEtabType_typeEtablissement').change(function() {
		var categorie = document.getElementById("recapitulatifParticipationEtabType_categorie");
		var typeEtablissement = document.getElementById("recapitulatifParticipationEtabType_typeEtablissement");
		document.getElementById("campagneZoneEtabType_departement").options[0] = new Option("Tous", "");
		document.getElementById("campagneZoneEtabType_departement").options.length = 1;
		document.getElementById("departement_selectionne").value = "";
		if(document.getElementById("campagneZoneEtabType_etablissement")){
			document.getElementById("campagneZoneEtabType_etablissement").options[0] = new Option("Tous", "");
			document.getElementById("campagneZoneEtabType_etablissement").options.length = 1;
		}
    	afficheAcademieDepartementCommune('campagneZoneEtabType');
		
		alert(categorie.options[categorie.selectedIndex].value + " " +typeEtablissment.options[typeEtablissment.selectedIndex].value);
	});

	
	
});