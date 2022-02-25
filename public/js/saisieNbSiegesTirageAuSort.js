$(document).ready(function() {
	var nbSiegesPourvus = Number($('#resultatsNbSiegesPourvus').html());
	var nbSiegesPourvoir = Number($('#resultatsNbSiegesPourvoir').html());
	var nbSiegesNonPourvus = nbSiegesPourvoir - nbSiegesPourvus;

	//Commenté pour empechez le double appel à checkNbSiegesTirageAuSort() à la soumission
	/*$('input#ececa_saisie_ts_nbSiegesTirageAuSort').change(function() {
		checkNbSiegesTirageAuSort();
	});*/

	$('#form_saisie_ts').submit(function() {
		if (checkNbSiegesTirageAuSort()) {
			// RG_SAISIE_118
			if (confirm("Confirmez-vous la validation de la saisie du tirage au sort ? Attention ! Cette action est définitive.")) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
    });
	
	function checkNbSiegesTirageAuSort() {
		var nbSiegesTirageAuSort = $('input#ececa_saisie_ts_nbSiegesTirageAuSort').val();
		// RG_SAISIE_116
		if (isNaN(nbSiegesTirageAuSort) || (parseFloat(nbSiegesTirageAuSort) != parseInt(nbSiegesTirageAuSort)) || nbSiegesTirageAuSort < 0 ) { // 0154829 possiblité de saisir 0 pour le tirge au sort
			alert("Le nombre de sièges pourvus par tirage au sort doit être un entier positif.");
			return false;
		} else {
			// RG_SAISIE_117
			if (Number(nbSiegesTirageAuSort) > nbSiegesNonPourvus) {
				alert("Le nombre de sièges pourvus par tirage au sort doit être inférieur ou égal au nombre total de sièges non pourvus : " + nbSiegesNonPourvus + ".");
				return false;
			}
		}
		return true;
	}
});