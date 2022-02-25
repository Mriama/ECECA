/**
 * Javascript général ECECA Atos - Avril 2013
 */

var arrayIndiceAActiver = new Array();
var arrayIndiceDetailAActiver = new Array();
var controle_saisie = true;
var nb_organisation = 0;
var buttonRepartitionClick = false;
var errorSaisiCarence = false;

$(document).ready(function() {

	/*
	* RG_134-1 et RG_134-2
	* Le but est de vérifier la valeur de nombre de siéges pourvus avec la valeur actuellement saisie par l'utilisateur
	*
	* */
	// Pour chaque élement de class "cdt-tit"
	$(".cdt_tit").each(function() {

		// Lorsque cet élément est changé
		$(this).change(function () {

			// On récupère la valeur du nombre de sièges pourvus
			var nombre_siege = $("#EleEtablissementType_participation_nbSiegesPourvoir").val();

			// Si cette valeur est égale à 1
			if (nombre_siege == 1) {

				// On paramètre la valeur maximum des inputs de classe cdt_tit à 1
				//$(".cdt_tit").attr('max', '1');

				// Si la valeur courante est supérieur à 1 on affiche un message d'erreur
				if (parseInt($(this).val()) > 1) {

					// Alors cette valeur ne peut pas excéder 1
					alert("le nombre de sièges à pourvoir étant égal à 1, le nombre de candidats titulaires ne peut être supérieur à 1.");
					$(this).val("0");
				}
			}
			// Sinon si le nombre de siège est supérieur à 1
			else if (nombre_siege > 1) {

				// On paramètre une regex pour exclure le chiffre 1 puis != 1 car nombre_siege > 1
				$(".cdt_tit").attr('pattern', '^(0*)([2-9]+)|(1[0-9]+)|0*$');
				if (parseInt($(this).val()) == 1) {

					alert("le nombre de sièges à pourvoir étant supérieur à 1, le nombre de candidats titulaires ne peut être égal à 1.");
					$(this).val("0");
				}
			}

		});
	});

	controle_saisie = $('input#controle_saisie').val() == 1 ? true : false;
	nb_organisation = Number($('#nb_organisation').val());

	/** ********* Ensemble d'évènements pour la saisie des résultats ************ */

	$('input#EleEtablissementType_participation_nbInscrits').change(function() {
		var nbInscrits = document.getElementById('EleEtablissementType_participation_nbInscrits').value;
		var nbVotants = document.getElementById('EleEtablissementType_participation_nbVotants').value;

		document.getElementById('EleEtablissementType_participation_nbInscrits').value = '';

		var div_taux = document.getElementById('id_taux');
		div_taux.innerHTML = '';

		if (nbInscrits < 0 || (parseFloat(nbInscrits) != parseInt(nbInscrits))) {
			alert('Le nombre d\'inscrits doit être un nombre entier positif');
			disabledButtonEnregistrer();
		} else if (Number(nbInscrits) < Number(nbVotants) ) {
			alert('Le nombre d\'inscrits doit être supérieur au nombre de votants');
			disabledButtonEnregistrer();
		} else {
			document.getElementById('EleEtablissementType_participation_nbInscrits').value = nbInscrits;
			calculTauxParticipation();
		}
	});

	$('input#EleEtablissementType_participation_nbVotants').change(function() {
		var nbInscrits = document.getElementById('EleEtablissementType_participation_nbInscrits').value;
		var nbVotants = document.getElementById('EleEtablissementType_participation_nbVotants').value;
		var nbNulsBlancs = document.getElementById('EleEtablissementType_participation_nbNulsBlancs').value;

		document.getElementById('EleEtablissementType_participation_nbVotants').value = '';

		var div_taux = document.getElementById('id_taux');
		div_taux.innerHTML = '';

		if (nbVotants < 0 || (parseFloat(nbVotants) != parseInt(nbVotants))) {
			alert('Le nombre de votants doit être un nombre entier positif');
			disabledButtonEnregistrer();
		} else if (nbInscrits < 0 || (parseFloat(nbInscrits) != parseInt(nbInscrits))) {
			alert('Le nombre d\'inscrits doit être un nombre entier positif');
			disabledButtonEnregistrer();
		} else if (Number(nbInscrits) < Number(nbVotants) ) {
			alert('Le nombre de votants doit être inférieur ou égal au nombre d\'inscrits');
			disabledButtonEnregistrer();
		} else if (Number(nbNulsBlancs) > Number(nbVotants) ) {
			alert('Le nombre de votants ne peut pas être inférieur au nombre de bulletins nuls ou blancs');
			disabledButtonEnregistrer();
		} else {
			document.getElementById('EleEtablissementType_participation_nbVotants').value = nbVotants;
			calculTauxParticipation();
			calculNbExprimes();
		}
	});

	$('input#EleEtablissementType_participation_nbNulsBlancs').change(function() {
		var nbVotants = document.getElementById('EleEtablissementType_participation_nbVotants').value;
		var nbNulsBlancs = document.getElementById('EleEtablissementType_participation_nbNulsBlancs').value;

		document.getElementById('EleEtablissementType_participation_nbNulsBlancs').value = '';

		if (nbNulsBlancs < 0 || (parseFloat(nbNulsBlancs) != parseInt(nbNulsBlancs))) {
			alert('Le nombre de bulletins nuls ou blancs doit être un nombre entier positif');
			disabledButtonEnregistrer();
		} else if (nbVotants < 0 || (parseFloat(nbVotants) != parseInt(nbVotants))) {
			alert('Le nombre de votants doit être un nombre entier positif');
			disabledButtonEnregistrer();
		} else if (Number(nbNulsBlancs) > Number(nbVotants) ) {
			alert('Le nombre de bulletins nuls ou blancs ne peut pas être supérieur au nombre de votants');
			disabledButtonEnregistrer();
		} else {
			document.getElementById('EleEtablissementType_participation_nbNulsBlancs').value = nbNulsBlancs;
			calculNbExprimes();
		}

	});

	$('input#EleEtablissementType_participation_nbExprimes').change(function() {
		calculQuotientElectoral();
		if(controle_saisie) calculRepartitionDesSieges();
		calculLigneToutesListes();
	});

	$('input#EleEtablissementType_participation_nbSiegesPourvoir').change(function() {
		var nbSiegesPourvoir = document.getElementById('EleEtablissementType_participation_nbSiegesPourvoir').value;
		var nbSiegesPourvus = document.getElementById('EleEtablissementType_participation_nbSiegesPourvus').value;

		document.getElementById('EleEtablissementType_participation_nbSiegesPourvoir').value = '';

		var div_quotient = document.getElementById('id_quotient');
		div_quotient.innerHTML = '';

		if (isNaN(nbSiegesPourvoir)) {
			alert('Le nombre de sièges à pourvoir doit être numérique');
		} else if (nbSiegesPourvoir <= 0 || (parseFloat(nbSiegesPourvoir) != parseInt(nbSiegesPourvoir))) {
			alert('Le nombre de sièges à pourvoir doit être un entier positif');
			disabledButtonEnregistrer();
		} else if (Number(nbSiegesPourvoir) < Number(nbSiegesPourvus) ) {
			alert('Le nombre de sièges à pourvoir ne peut pas être inférieur au nombre de sièges pourvus');
			document.getElementById('EleEtablissementType_participation_nbSiegesPourvoir').value = '';
			document.getElementById('EleEtablissementType_participation_nbSiegesPourvus').value = '';
			disabledButtonEnregistrer();
		} else {
			document.getElementById('EleEtablissementType_participation_nbSiegesPourvoir').value = nbSiegesPourvoir;
			calculQuotientElectoral();
			initialisationSiegesAuSort();
			if(controle_saisie)	calculRepartitionDesSieges();
			calculLigneToutesListes();
		}

		// Lorsque le nombre de siège à pourvoir est modifié, vérifier tous les candidats titulaires.
		if (nbSiegesPourvoir == 1) {
			var nbCandidats;
			var error = false;
			for (var i = 0; i < nb_organisation; i++) {
				nbCandidats = $("#EleEtablissementType_resultats_"+i+"_nbCandidats").val();
				if ( Number(nbCandidats) > 1 ) {
					$("#EleEtablissementType_resultats_"+i+"_nbCandidats").val(0);
					error = true;
				}
			}
			if (error) {
				alert("le nombre de sièges à pourvoir étant égal à 1, le nombre de candidats titulaires ne peut être supérieur à 1.");
			}
		} else if (nbSiegesPourvoir > 1) {
			var nbCandidats;
			var error = false;
			for (var i = 0; i < nb_organisation; i++) {
				nbCandidats = $("#EleEtablissementType_resultats_"+i+"_nbCandidats").val();
				if ( Number(nbCandidats) == 1 ) {
					$("#EleEtablissementType_resultats_"+i+"_nbCandidats").val(0);
					error = true;
				}
			}
			if (error) {
				alert("le nombre de sièges à pourvoir étant supérieur à 1, le nombre de candidats titulaires ne peut être égal à 1.");
			}
		}
	});

	$('input#EleEtablissementType_participation_nbSiegesPourvus').change(function() {

		var nbSiegesPourvoir = document.getElementById('EleEtablissementType_participation_nbSiegesPourvoir').value;
		var nbSiegesPourvus = document.getElementById('EleEtablissementType_participation_nbSiegesPourvus').value;

		document.getElementById('EleEtablissementType_participation_nbSiegesPourvus').value = '';

		if (isNaN(nbSiegesPourvus)) {
			alert('Le nombre de sièges pourvus doit être un entier positif.');
			disabledButtonEnregistrer();
		} else if (nbSiegesPourvus < 0 || (parseFloat(nbSiegesPourvus) != parseInt(nbSiegesPourvus))) {
			alert('Le nombre de sièges pourvus doit être un entier positif');
			disabledButtonEnregistrer();
		} else if (Number(nbSiegesPourvus) > Number(nbSiegesPourvoir) ) {
			alert('Le nombre de sièges pourvus ne peut pas être supérieur au nombre de sièges à pourvoir');
			document.getElementById('EleEtablissementType_participation_nbSiegesPourvus').value = '';
			disabledButtonEnregistrer();
		} else {
			document.getElementById('EleEtablissementType_participation_nbSiegesPourvus').value = nbSiegesPourvus;
			if(controle_saisie) calculRepartitionDesSieges();
			calculLigneToutesListes();
		}
	});


	$('#choix_tabBordZone').click(function() {
		$("#tabBordZone").show();
		$("#choix_tabBordZone").hide();
		$("#masquer_tabBordZone").show();
	});

	$('#masquer_tabBordZone').click(function() {
		$("#tabBordZone").hide();
		$("#choix_tabBordZone").show();
		$("#masquer_tabBordZone").hide();
	});

	if ($('#tdbDeplieRetour').val() != null && $('#tdbDeplieRetour').val() != '') {
		$('#choix_tabBordZone').click();
		$("#tdbDeplieRetour").val('');
	}

	// Bug IE onChange
	$('[id^=EleEtablissementType_resultats_][id$=_nbVoix]').each(function(index){
		$(this).change(function (){
			// defect #247 Saisie des résultats : valeur Infinity division par quotient_electoral = 0
			var quotient_electoral = Number($('#quotient_electoral').val());
			var nbSiegesPourvoir = $("#EleEtablissementType_participation_nbSiegesPourvoir").val();
			if (isNaN(quotient_electoral) || quotient_electoral == 0) {
				if (nbSiegesPourvoir.length == 0) {
					alert("Le nombre de sièges à pourvoir doit être renseigné.");
					disabledButtonEnregistrer();
				} else if ($(this).val() > 0){
					alert('Le nombre total de suffrages doit être égal au nombre de suffrages exprimés.');
					disabledButtonEnregistrer();
				}
				$(this).val(0);
				$('#EleEtablissementType_participation_nbSiegesPourvus').val(0);
			}
			// defect 809 pour le cas de retour pour anomalie et saisie d'une carence
			if(controle_saisie){
				initialisationSiegesAuSort();
				calculRepartitionDesSieges(index);
			}else{
				testColonneVoix(index);
			}
			calculLigneToutesListes();

		});
		var nb_detail = $("[id^=ligne_"+index+"_]").length;
		if (nb_detail > 0){
			for( var j = 0 ; j < nb_detail ; j++){
				$('[id^=EleEtablissementType_resultatsDetailles_][id$='+j+'_nbVoix]').change(function (){
					calculeSommesOrganisationsDetaillees(index);
				});
			}
		}
	});

	$('[id^=EleEtablissementType_resultats_][id$=_nbSieges]').each(function(index){
		$(this).change(function (){
			if(!controle_saisie){
				testColonneSiege(index);
			}
		});
		var nb_detail = $("[id^=ligne_"+index+"_]").length;
		if (nb_detail > 0){
			for( var j = 0 ; j < nb_detail ; j++){
				$('[id^=EleEtablissementType_resultatsDetailles_][id$='+j+'_nbSieges]').change(function (){
					calculeSommesOrganisationsDetaillees(index);
					verifNbSiegeSort();
				});
			}
		}
	});

	$('[id^=EleEtablissementType_resultats_][id$=_nbSiegesSort]').each(function(index){
		$(this).change(function (){
			if(controle_saisie){
				verifNbSiegeSort();
			}else{
				testColonneSiegeSort(index);
			}
		});
		var nb_detail = $("[id^=ligne_"+index+"_]").length;
		if (nb_detail > 0){
			for( var j = 0 ; j < nb_detail ; j++){
				$('[id^=EleEtablissementType_resultatsDetailles_][id$='+j+'_nbSiegesSort]').change(function (){
					calculeSommesOrganisationsDetaillees(index);
					verifNbSiegeSort();
				});
			}
		}
	});

	$('[id^=total_]').each(function(index){
		$(this).change(function (){
			testColonneTotal(index);
		});
		var nb_detail = $("[id^=ligne_"+index+"_]").length;
		if (nb_detail > 0){
			for( var j = 0 ; j < nb_detail ; j++){
				$('#total_'+index+'_'+j).change(function (){
					calculeSommesOrganisationsDetaillees(index);
				});
			}
		}
	});

	// Clic sur le bouton de saisie forcée
	$('input#forceSaisie').click(function() {
		if (confirm('Confirmer la levée des contrôles de saisie ?\n(Suppression de la répartition automatique des sièges)')){
			$('input#forceSaisie').remove();
			// mantis 145915 : En cas de levée de contrôle, le bouton "Calcul de la répartition" n'est plus accessible
			$('input#boutonCalculRepartition').remove();
			// 014E au levé des controles le bouton enregistrer doit etre dégrisé
			$('input#enregistrerDonnees').removeAttr('disabled');
			$('#enregistrerDonnees').css('color','#7e1a70');
			controle_saisie = false;
			setParticipationModifiableFields();
		}
	});

	// change nbCandidats
	$('[id^=EleEtablissementType_resultats_][id$=_nbCandidats]').each(function(index) {
		$(this).change(function () {
			calculNombreTotalCandidats();
		});
		var nb_detail = $("[id^=ligne_"+index+"_]").length;
		if (nb_detail > 0) {
			for (var j = 0 ; j < nb_detail ; j++) {
				$('[id^=EleEtablissementType_resultatsDetailles_][id$='+j+'_nbCandidats]').change(function () {
					calculSommeCandidatsDetails(index);
				});
			}
		}
	});

	/* activation des champs inactifs pour l'enregistrement en base */
	$('#form_edit_resultats').submit(function() {
		// si boutton de calcul de répartition alors on submit pas le formulaire
		if (buttonRepartitionClick) {
			buttonRepartitionClick = false;
			return false;
		}
		if (checkCarence()) {
			let messageConfirmation = "Vous êtes dans le cas de carence de candidats. Confirmez-vous l'enregistrement des données ?";
			if(document.getElementById("EleEtablissementType_participation_modaliteVote") != null) {
				document.getElementById("EleEtablissementType_participation_modaliteVote").required = false;
				messageConfirmation += "\n\nSi une modalité de vote a été renseignée, elle sera ignorée.";
			}
			if (confirm(messageConfirmation)) {
				removeAttrDisabledOnSubmit();
			} else {
				return false;
			}
		} else {
			if(document.getElementById("EleEtablissementType_participation_modaliteVote") != null) {
				document.getElementById("EleEtablissementType_participation_modaliteVote").required = true;
			}
			//var nbSiegesARepartir = Number($('#nb_sieges_au_sort').val());
			var nbVoixTotal = Number($('#EleEtablissementType_participation_nbExprimes').val());
			var nbVoixDistribues = Number($('#nbVoixDistribues').html());
			var nbSiegesDistribues = Number($('#nbSiegesDistribues').html());
			var nbSiegesPourvoir = $("#EleEtablissementType_participation_nbSiegesPourvoir").val();
			var nbSiegesPourvus = 0;
			var nbSiegesAutoDist = 0;
			var nbSiegesAuSortDist = 0;

			// commenté mantis 121927
//			if(nbSiegesARepartir > 0){
//				alert('Le nombre de sièges n\'a pas été entièrement réparti.');
//				return false;
//			}

			// ECT nbSiegesPourvusParLigne = min(nb candidats titulaires par ligne, nb total de sièges par ligne)
			// ECT nbSiegesPourvus = somme des nbSiegesPourvusParLigne
			for (var i = 0; i < nb_organisation; i++) {
				var nb_detail = $("[id^=ligne_"+i+"_]").length;
				if (nb_detail > 0) {
					for (var j = 0; j < nb_detail; j++) {
						nbSiegesPourvus += Math.min(Number($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val()), Number($("#total_"+i+"_"+j).val()));
					}
				} else {
					nbSiegesPourvus += Math.min(Number($('input#EleEtablissementType_resultats_'+i+'_nbCandidats').val()), Number($('input#total_'+i).val()));
				}
				nbSiegesAutoDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSieges').val());
				nbSiegesAuSortDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val());
			}
			$('#EleEtablissementType_participation_nbSiegesPourvus').val(nbSiegesPourvus);

			// RG_SAISIE_5 : La somme des voix réparties sur les différentes listes est égale au nombre de suffrages exprimés
			if (nbVoixTotal != nbVoixDistribues) {
				alert('Le nombre total de suffrages doit être égal au nombre de suffrages exprimés');
				disabledButtonEnregistrer();
				return false;
			}

			// RG_SAISIE_6 : Le nombre de sièges pourvus doit être inférieur ou égal à la somme des sièges attribués
			if (nbSiegesPourvus > nbSiegesDistribues) {
				alert('Le nombre de sièges pourvus doit être inférieur ou égal à la somme des sièges attribués');
				disabledButtonEnregistrer();
				return false;
			}

			// 173367 siège atribué au plus age obligatoir s'il est saisissable
			var nbSiegesDistTotal = nbSiegesAutoDist + nbSiegesAuSortDist;
			var nbSiegesTotal = Number($('input#EleEtablissementType_participation_nbSiegesPourvoir').val());
			if (nbSiegesDistTotal < nbSiegesTotal && controle_saisie) {
				// Mantis 173367 complement: modification de message d'erreur
				alert('Le nombre de sièges attribués au candidat le plus âgé doit être renseigné.');
				disabledButtonEnregistrer();
				return false;
			}

			//Verification du total des sieges <= nombre de sieges a pourvoir
			if(nbSiegesDistribues > nbSiegesPourvoir) {
				alert('Le nombre total de sièges attribués doit être inférieur ou égal au nombre de sièges à pourvoir.');
				disabledButtonEnregistrer();
				return false;
			}

			// test libellé d'un détail non vide
			for (var i = 0; i < nb_organisation; i++) {
				// Traitement des données pour les organisations détaillées
				var nb_detail = $("[id^=ligne_"+i+"_]").length;
				for (var j = 0 ; j < nb_detail ; j++) {
					if ($.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_libelle').val()) == "") {
						alert('Le libellé d\'un détail doit être renseigné.');
						disabledButtonEnregistrer();
						return false;
					}
				}
			}

			// verification nbCandidats
			// ECT : on enlève les controles sur le nombre de candidats
//			if (!checkNombreCandidatsParListe()) {
//				return false;
//			}

//			if (!checkNombreTotalCandidats()) {
//				return false;
//			}

			// 014E RG_SAISIE_136 RG_SAISIE_137 RG_SAISIE_138
			if (!checkSuffrageAndCandidatTitulaire() || !checkCandidatTitulaireAndNbSiegePourvoir()) {
				disabledButtonEnregistrer();
				return false;
			}

			if (checkNombreSiegesNonPourvus(true)) {
				removeAttrDisabledOnSubmit();
			} else {
				return false;
			}
		}
	});

	// clic sur le bouton "Calcul de la répartition"
	// reprise de ce qu'il y a dans le submit sans enrgistrement
	$('#boutonCalculRepartition').click(function() {
		buttonRepartitionClick = true;
		if (checkCarence()) {
			if(document.getElementById("EleEtablissementType_participation_modaliteVote") != null) {
				document.getElementById("EleEtablissementType_participation_modaliteVote").required = false;
			}
			$('#enregistrerDonnees').removeAttr('disabled');
			$('#enregistrerDonnees').css('color','#7e1a70');
			alert("Vous êtes dans le cas de carence de candidats.");
		} else {
			if(document.getElementById("EleEtablissementType_participation_modaliteVote") != null) {
				document.getElementById("EleEtablissementType_participation_modaliteVote").required = true;
			}
			var nbVoixTotal = Number($('#EleEtablissementType_participation_nbExprimes').val());
			var nbVoixDistribues = Number($('#nbVoixDistribues').html());
			var nbSiegesDistribues = Number($('#nbSiegesDistribues').html());
			var nbSiegesPourvus = 0;
			var nbSiegesAutoDist = 0;
			var nbSiegesAuSortDist = 0;

			// ECT nbSiegesPourvusParLigne = min(nb candidats titulaires par ligne, nb total de sièges par ligne)
			// ECT nbSiegesPourvus = somme des nbSiegesPourvusParLigne
			for (var i = 0; i < nb_organisation; i++) {
				var nb_detail = $("[id^=ligne_"+i+"_]").length;
				if (nb_detail > 0) {
					for (var j = 0; j < nb_detail; j++) {
						nbSiegesPourvus += Math.min(Number($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val()), Number($("#total_"+i+"_"+j).val()));
					}
				} else {
					nbSiegesPourvus += Math.min(Number($('input#EleEtablissementType_resultats_'+i+'_nbCandidats').val()), Number($('input#total_'+i).val()));
				}

				nbSiegesAutoDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSieges').val());
				nbSiegesAuSortDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val());
			}
			$('#EleEtablissementType_participation_nbSiegesPourvus').val(nbSiegesPourvus);

			// RG_SAISIE_5 : La somme des voix réparties sur les différentes listes est égale au nombre de suffrages exprimés
			if (nbVoixTotal != nbVoixDistribues) {
				alert('Le nombre total de suffrages doit être égal au nombre de suffrages exprimés');
				disabledButtonEnregistrer();
				return false;
			}

			// RG_SAISIE_6 : Le nombre de sièges pourvus doit être inférieur ou égal à la somme des sièges attribués
			if (nbSiegesPourvus > nbSiegesDistribues) {
				alert('Le nombre de sièges pourvus doit être inférieur ou égal à la somme des sièges attribués');
				disabledButtonEnregistrer();
				return false;
			}

			// 173367 siège atribué au plus age obligatoir s'il est saisissable
			var nbSiegesDistTotal = nbSiegesAutoDist + nbSiegesAuSortDist;
			var nbSiegesTotal = Number($('input#EleEtablissementType_participation_nbSiegesPourvoir').val());
			if (nbSiegesDistTotal < nbSiegesTotal && controle_saisie) {
				// Mantis 173367 complement: modification de message d'erreur
				alert('Le nombre de sièges attribués au candidat le plus âgé doit être renseigné.');
				disabledButtonEnregistrer();
				return false;
			}

			// test libellé d'un détail non vide
			for (var i = 0; i < nb_organisation; i++) {
				// Traitement des données pour les organisations détaillées
				var nb_detail = $("[id^=ligne_"+i+"_]").length;
				for (var j = 0 ; j < nb_detail ; j++) {
					if ($.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_libelle').val()) == "") {
						alert('Le libellé d\'un détail doit être renseigné.');
						disabledButtonEnregistrer();
						return false;
					}
					//LISTE DETAILLES : Nombre de suffrage == 0 ET Nombre de candidat == 0
					if (
						($.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbCandidats').val()) == "" || $.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbCandidats').val()) == 0)
						&&
						($.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val()) == "" || $.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val()) == 0)
					) {
						alert('Pour toute liste détaillée, la saisie des résultats est obligatoire. Veuillez vérifier vos saisies ou supprimer les listes pour lesquelles il n\'existe pas de résultats à saisir.');
						disabledButtonEnregistrer();
						return false;
					}
					//LISTE DETAILLES : Nombre de suffrage > 0 ET Nombre de candidat == 0
					else if(
						($.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbCandidats').val()) == "" || $.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbCandidats').val()) == 0)
						&& $.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val()) != ""
						&& $.trim($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val()) > 0
					) {
						alert('Une liste ayant obtenu un nombre de suffrages non nul doit obligatoirement avoir un nombre de candidats titulaires non nul.');
						disabledButtonEnregistrer();
						return false;
					}
				}
			}

			// verification nbCandidats
			// ECT : on enlève les controles sur le nombre de candidats
//			if (!checkNombreCandidatsParListe()) {
//				return false;
//			}

//			if (!checkNombreTotalCandidats()) {
//				return false;
//			}
			// 014E RG_SAISIE_136 RG_SAISIE_137 RG_SAISIE_138
			if ($('#EleEtablissementType_participation_nbInscrits').val() != '' && $('#EleEtablissementType_participation_nbVotants').val() != '' && $('#EleEtablissementType_participation_nbNulsBlancs').val() != '' && !errorSaisiCarence) {
				if (checkSuffrageAndCandidatTitulaire() && checkCandidatTitulaireAndNbSiegePourvoir()) {
					$('#enregistrerDonnees').removeAttr('disabled');
					$('#enregistrerDonnees').css('color','#7e1a70');
					checkNombreSiegesNonPourvus(false);
					return true;
				} else {
					disabledButtonEnregistrer();
					return false;
				}
			}
		}
	});

});

/*
 * Fonction permettant d'afficher ou de masquer les champs communes et
 * etablissement en fonction de choix_etab
 */

function showHideChampsEtabByCBChoixEtab(nomForm) {

	var select_typeElection = document.getElementById(nomForm+"_typeElection");
	var typeElectionId = document.getElementById("typeElection_selectionne").value;
	for (var i = 0; i < select_typeElection.length; i++) {
		if (select_typeElection[i].value == typeElectionId) {
			select_typeElection.selectedIndex = i;
		}
	}
	if (document.getElementById(nomForm+"_choix_etab").checked) {
		$("#choix_commune").show();
		$("#choix_etablissement").show();
	} else {
		$("#choix_commune").hide();
		$("#choix_etablissement").hide();
	}
}

/*
 * Fonction affichant les sommes de voix et de sièges lors de la répartition
 * détaillées
 */

function calculLigneToutesListes() {

	var nbVoixDistribues = 0;
	var nbSiegesDistribues = 0;
	for (var i=0; i<nb_organisation; i++) {
		var value = $('#EleEtablissementType_resultats_'+i+'_nbVoix').val();
		if ((parseFloat(value) != parseInt(value)) || isNaN(value) || Number(value)<0) {
			alert('Le nombre de voix doit être un entier positif.');
			$('#EleEtablissementType_resultats_'+i+'_nbVoix').val(0);
		} else {
			var nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
			var nb_sieges = Number(document.getElementById('total_'+i).value);
			nbVoixDistribues = nbVoixDistribues + nb_voix;
			nbSiegesDistribues = nbSiegesDistribues + nb_sieges;
		}
	}

	$('#nbVoixDistribues').html(nbVoixDistribues);
	$('#nbSiegesDistribues').html(nbSiegesDistribues);
}


/*
 * Fonction permettant le calcul du taux de participation
 */

function calculTauxParticipation() {
	var nbInscrits = document.getElementById('EleEtablissementType_participation_nbInscrits').value;
	var nbVotants = document.getElementById('EleEtablissementType_participation_nbVotants').value;
	var div_taux = document.getElementById('id_taux');
	div_taux.innerHTML = '';

	if (isNaN(nbInscrits)) alert('Le nombre d\'inscrits doit être un entier positif.');
	else if (isNaN(nbVotants)) alert('Le nombre de votants doit être un entier positif.');
	else {
		nbInscrits = Number(nbInscrits);
		nbVotants = Number(nbVotants);
		if (nbInscrits > 0) {
			if (nbVotants >= 0 && (nbVotants <= nbInscrits)) {
				var taux = nbVotants / nbInscrits *100;
				if(taux) {
					div_taux.innerHTML = taux.toFixed(2)+'%';
				}
			}
		}
	}
}

/*
 * Fonction permettant le calcul du quotient électoral
 */

function calculQuotientElectoral() {
	var nbExprimes = $('#EleEtablissementType_participation_nbExprimes').val();
	var nbSieges = $('#EleEtablissementType_participation_nbSiegesPourvoir').val();
	var div_quotient = $('#id_quotient');
	div_quotient.html('');
	$('#quotient_electoral').val('');

	if (isNaN(nbExprimes)) alert('Le nombre de suffrages exprimés doit être un entier positif.');
	else if (isNaN(nbSieges)) alert('Le nombre de sièges à pourvoir doit être un entier positif.');
	else {
		var nbExprimes = Number(nbExprimes);
		var nbSieges = Number(nbSieges);
		if (nbSieges > 0) {
			if (nbExprimes >= 0) {
				var quotient = nbExprimes / nbSieges;
				$('#quotient_electoral').val(quotient.toFixed(10));
				if (quotient) {
					div_quotient.html(quotient.toFixed(2));
				}
			}
		}
	}
}

/**
 * Teste si les données de la colonne des suffrages sont correctes
 */
function testColonneVoix(id){

	var nbVoixTotal = Number($('input#EleEtablissementType_participation_nbExprimes').val());
	var nbSiegesA_Attribue = Number($('input#EleEtablissementType_participation_nbSiegesPourvoir').val());

	var nbVoixDistribues = 0;
	var nombreDeSiegesDistribues = 0;

	for (var i=0; i < nb_organisation; i++) {
		var value = $('input#EleEtablissementType_resultats_'+i+'_nbVoix').val();
		if ((parseFloat(value) != parseInt(value)) || isNaN(value) || Number(value)<0) {
			alert('Le nombre de voix doit être un entier positif.');
			$('input#EleEtablissementType_resultats_'+i+'_nbVoix').val(0);
		} else {
			var nb_voix = Number($('input#EleEtablissementType_resultats_'+i+'_nbVoix').val());
			nbVoixDistribues = nbVoixDistribues + nb_voix;
			var nb_sieges = Number($('#EleEtablissementType_resultats_'+i+'_nbSieges').val());
			nombreDeSiegesDistribues = nombreDeSiegesDistribues + nb_sieges;
		}
	}

	if (nbVoixDistribues > nbVoixTotal) {
		alert('Le nombre de voix distribuées est supérieur au nombre de suffrages exprimés.');
		if (nombreDeSiegesDistribues < nbSiegesA_Attribue) {
			document.getElementById('tirage_au_sort').value = true;
		}
		indice_org = id;
		$('#EleEtablissementType_resultats_'+indice_org+'_nbVoix').val(0);
		$('#EleEtablissementType_resultats_'+indice_org+'_nbSieges').val(0);
		$('#EleEtablissementType_resultats_'+indice_org+'_nbSiegesSort').val(0);

		// RAZ des détails mantis 0121841
		var nb_detail = $("[id^=ligne_"+indice_org+"_]").length;
		if (nb_detail > 0) {
			for (var j = 0; j < nb_detail; j++) {
				$('#EleEtablissementType_resultatsDetailles_'+indice_org+'_'+j+'_nbVoix').val(0);
				$('#EleEtablissementType_resultatsDetailles_'+indice_org+'_'+j+'_nbSieges').val(0);
				$('#EleEtablissementType_resultatsDetailles_'+indice_org+'_'+j+'_nbSiegesSort').val(0);
			}
		}
		disabledButtonEnregistrer();
		return false;
	}else{
		return true;
	}
}

/**
 *
 * @param id
 */
function testColonneSiege(id){

	var i = id;
	var totalSieges =  $('#total_'+i).val();

	var value = $('#EleEtablissementType_resultats_'+i+'_nbSieges').val();
	if ((parseFloat(value) != parseInt(value)) || isNaN(value) || Number(value)<0) {
		alert('Le nombre de sièges attribués au plus âgé doit être un entier positif.');
		$('input#EleEtablissementType_resultats_'+i+'_nbSieges').val(0);
	}else{
		totalSieges = Number(totalSieges) + Number(value);
	}
	$('#total_'+i).val(totalSieges);
	calculTotalSieges();
}

/**
 *
 * @param id
 */
function testColonneSiegeSort(id){

	var i = id;
	var totalSieges =  $('#total_'+i).val();

	var value = $('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val();
	if ((parseFloat(value) != parseInt(value)) || isNaN(value) || Number(value)<0) {
		alert('Le nombre de sièges attribués au plus âgé doit être un entier positif.');
		$('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val(0);
	}else{
		totalSieges = Number(totalSieges) + Number(value);
	}
	$('#total_'+i).val(totalSieges);
	calculTotalSieges();
}

/**
 *
 * @param id
 */
function testColonneTotal(id){

	var i = id;
	var totalSieges =  $('#total_'+i).val();

	if ((parseFloat(totalSieges) != parseInt(totalSieges)) || isNaN(totalSieges) || Number(totalSieges)<0) {
		alert('Le total des sièges attribués doit être un entier positif.');
		$('#total_'+i).val(0);
	}
	calculLigneToutesListes();
}

/*
 * Fonction permettant d'effectuer la répartition des sièges
 */

function calculRepartitionDesSieges(id) {

	var quotient_electoral = Number(document.getElementById('quotient_electoral').value);
	var nbSiegesA_Attribue = Number(document.getElementById('EleEtablissementType_participation_nbSiegesPourvoir').value);

	document.getElementById('tirage_au_sort').value = false;

	var arrayRestes = new Array();
	var arrayRestesDetails = new Array();
//	var arrayIndicesRestesIdentiques = new Object(); // tableaux associatifs indice => nb_voix mantis 145415
//	var arrayIndicesRestesIdentiquesDetails = new Object(); // tableaux associatifs details indice => nb_voix mantis 145415
	var maxRestes = 0;
	var maxRestesDetails = 0;
	var resteIdentiqueMaxNbVoix = 0; // nb_voix max dans tableaux associatifs indice => nb_voix
	var resteIdentiqueMaxNbVoixDetails = 0; // nb_voix max dans tableaux associatifs details indice => nb_voix
	var resteIdentiqueMaxNbVoixAll = 0; // nb_voix max dans les tableaux arrayIndicesRestesIdentiques et arrayIndicesRestesIdentiquesDetails confondus

	if (isNaN(quotient_electoral)) {
		alert('Le quotient électoral n\'est pas correct, aucun calcul de sièges n\'est possible.');
	} else if(testColonneVoix(id)) {
		var nbSiegesDistPasseUne = 0;

		/*
		 * Premier passage on distribue les sièges en fonction du nombre
		 * de voix obtenues et du quotient électoral
		 */
		for (var i=0; i<nb_organisation; i++) {
			var nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
			var nb_sieges = nb_voix / quotient_electoral;
			var nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
			var nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);

			var nb_detail = $("[id^=ligne_"+i+"_]").length;
			// mantis 121841 et 121833 : 
			// on ne touche au nombre de sieges dans la ligne generique que s'il n'y a pas de liste detaillee
			// si liste detaillee, on ne touche pas au nombre de sieges dans la ligne generique, on intervient sur la liste detaillee
			if (nb_detail == 0) {
				$('#EleEtablissementType_resultats_'+i+'_nbSieges').val(nb_sieges_entier);
				arrayRestes.push(nb_sieges_float);
				nbSiegesDistPasseUne += nb_sieges_entier;
			}

			if (nb_detail > 0) {
				// Répartition pour les résultats détaillés
				for(var j = 0; j < nb_detail; j++){
					var nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val());
					var nb_sieges_detail = nb_voix_detail / quotient_electoral;
					var nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
					var nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
					$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').val(nb_sieges_detail_entier);
					arrayRestesDetails.push(nb_sieges_detail_float);
					nbSiegesDistPasseUne += nb_sieges_detail_entier;
				}
			}

			majLigneGeneriqueOrganisationsDetaillees(i);
		}

		var nbSiegesRestant = nbSiegesA_Attribue - nbSiegesDistPasseUne;
		var nbSiegesDistPasseDeux = 0;

		/*
		 * Second passage on distribue le restant des sièges en fonction
		 * du reste de la division du nombre de sièges obtenus lors du
		 * premier passage
		 */

		// mantis 145415 - 147133
		var arrayIndicesRestesIdentiques = new Object(); // tableaux associatifs indice => nb_voix
		var arrayIndicesRestesIdentiquesDetails = new Object(); // tableaux associatifs details indice => nb_voix
		// mantis 145415 - 147133

		for (var j=0 ; j<nbSiegesRestant; j++) {

			var indice = 0; // va servir quand il n'y aura pas de valeur identique pour la valeur maximale des restes
			var valeurRepere = 0;
			var valeur_identique = false;
			var nb_valeur_egal = 0;

			var reste_identique_nb_valeur_egal_nb_voix = 0; // nb de lignes ayant comme nb_voix = resteIdentiqueMaxNbVoixAll
			var reste_identique_egalite_parfaite_nb_voix = false;

			// on parcourt la liste des restes afin de récupérer la plus
			// grande valeur
			if (arrayRestes.length > 0) {
				maxRestes = Math.max.apply(Math, arrayRestes);
			}

			if (arrayRestesDetails.length > 0) {
				maxRestesDetails = Math.max.apply(Math, arrayRestesDetails);
			}

			var arrayIndiceAActiver = new Array();
			var arrayIndiceDetailAActiver = new Array();

			if (maxRestes == maxRestesDetails) {
				valeurRepere = maxRestes;
				// on prend en compte toutes les listes i.e listes sans détails et listes avec détails
				if (maxRestes > 0) {
					for (var i = 0; i  < nb_organisation; i++) {
						nb_detail = $("[id^=ligne_"+i+"_]").length;
						// liste sans détails
						if (nb_detail == 0) {
							nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
							nb_sieges = nb_voix / quotient_electoral;
							nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
							nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
							if (nb_sieges_float == maxRestes) {
								arrayIndiceAActiver.push(i);
								indice = i;
							}
						} else {
							// liste avec détails
							for (var x = 0; x < nb_detail; x++) {
								nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
								nb_sieges_detail = nb_voix_detail / quotient_electoral;
								nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
								nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
								if (nb_sieges_detail_float == maxRestesDetails) {
									arrayIndiceDetailAActiver.push(i + '_' + x);
									indice = i + '_' + x;
								}
							}
						}
					}
				}
			}

			if (maxRestes > maxRestesDetails) {
				valeurRepere = maxRestes;
				// on ne prend en compte que les listes sans details
				if (maxRestes > 0) {
					for (var i = 0; i  < nb_organisation; i++) {
						nb_detail = $("[id^=ligne_"+i+"_]").length;
						// liste sans détails
						if (nb_detail == 0) {
							nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
							nb_sieges = nb_voix / quotient_electoral;
							nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
							nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
							if (nb_sieges_float == maxRestes) {
								arrayIndiceAActiver.push(i);
								indice = i;
							}
						}
					}
				}
			}

			if (maxRestes < maxRestesDetails) {
				valeurRepere = maxRestesDetails;
				// on ne prend en compte que les listes avec details
				if (maxRestesDetails > 0) {
					for (var i = 0; i  < nb_organisation; i++) {
						nb_detail = $("[id^=ligne_"+i+"_]").length;
						// liste sans détails
						if (nb_detail > 0) {
							// liste avec détails
							for (x = 0; x < nb_detail; x++) {
								nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
								nb_sieges_detail = nb_voix_detail / quotient_electoral;
								nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
								nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
								if (nb_sieges_detail_float == maxRestesDetails) {
									arrayIndiceDetailAActiver.push(i + '_' + x);
									indice = i + '_' + x;
								}
							}
						}
					}
				}
			}

			// nb_valeur_egal : nb de fois où on retrouve la valeur maximale dans toutes les restes - 1
			nb_valeur_egal = arrayIndiceAActiver.length + arrayIndiceDetailAActiver.length - 1;
			if (nb_valeur_egal > 0) {
				valeur_identique = true;
			} else {
				valeur_identique = false;
			}

			if (valeurRepere != 0) {
				// Cas 1 - Une valeur de reste est supérieur à toute les
				// autres
				if (valeur_identique == false) {
					// mantis 121841 : 
					// on ne touche au nombre de sieges dans la ligne generique que s'il n'y a pas de liste detaillee
					var nb_detail_indice;
					var ligneGenerique = 0;
					if (!isNaN(indice)) {
						nb_detail_indice = $("[id^=ligne_"+indice+"_]").length;
					} else {
						ligneGenerique = indice.substr(0, indice.indexOf('_'));
						nb_detail_indice = $("[id^=ligne_"+ligneGenerique+"_]").length;
					}

					if (nb_detail_indice == 0) {
						document.getElementById('EleEtablissementType_resultats_'+indice+'_nbSieges').value = Number(document.getElementById('EleEtablissementType_resultats_'+indice+'_nbSieges').value) + 1;
					} else {
						document.getElementById('EleEtablissementType_resultatsDetailles_'+indice+'_nbSieges').value = Number(document.getElementById('EleEtablissementType_resultatsDetailles_'+indice+'_nbSieges').value) + 1;
						majLigneGeneriqueOrganisationsDetaillees(ligneGenerique);
					}
					nbSiegesDistPasseDeux = nbSiegesDistPasseDeux + 1;

					// RAZ le max reste courant dans son tableau de reste
					for (var y = 0; y < arrayRestes.length; y++) {
						if (arrayRestes[y] == valeurRepere) {
							arrayRestes[y] = 0;
							break;
						}
					}
					for (var z = 0; z < arrayRestesDetails.length; z++) {
						if (arrayRestesDetails[z] == valeurRepere) {
							arrayRestesDetails[z] = 0;
							break;
						}
					}
				}

					// Cas 2 - Plusieurs valeurs de reste sont égales et
				// supérieurs à toute les autres
				else {
					var nbS = nbSiegesRestant - nbSiegesDistPasseDeux;

					// recuperer les nb_voix pour chaque element a reste identique
					for (var i = 0; i  < nb_organisation; i++) {
						nb_detail = $("[id^=ligne_"+i+"_]").length;
						if (nb_detail == 0) {
							nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
							nb_sieges = nb_voix / quotient_electoral;
							nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
							nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
							if (nb_sieges_float == valeurRepere && arrayIndicesRestesIdentiques[i] != 0) {
								arrayIndicesRestesIdentiques[i] = nb_voix;
							}
						} else {
							for (x = 0; x < nb_detail; x++) {
								nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
								nb_sieges_detail = nb_voix_detail / quotient_electoral;
								nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
								nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
								if (nb_sieges_detail_float == valeurRepere && arrayIndicesRestesIdentiquesDetails[i + '_' + x] != 0) {
									arrayIndicesRestesIdentiquesDetails[i + '_' + x] = nb_voix_detail;
								}
							}
						}
					}

					// Taille des tableaux associatifs indice => nb_voix
					var lengthArrayIndicesRestesIdentiques = getLengthObjectArray(arrayIndicesRestesIdentiques);
					var lengthArrayIndicesRestesIdentiquesDetails = getLengthObjectArray(arrayIndicesRestesIdentiquesDetails);

					// nb_voix max dans chaque tableau associatif indice => nb_voix
					if (lengthArrayIndicesRestesIdentiques > 0) {
						resteIdentiqueMaxNbVoix = getMaxObjectArray(arrayIndicesRestesIdentiques);
					}
					if (lengthArrayIndicesRestesIdentiquesDetails > 0) {
						resteIdentiqueMaxNbVoixDetails = getMaxObjectArray(arrayIndicesRestesIdentiquesDetails);
					}

					// determiner la valeur de resteIdentiqueMaxNbVoixAll et reste_identique_nb_valeur_egal_nb_voix
					if (resteIdentiqueMaxNbVoix == resteIdentiqueMaxNbVoixDetails) {
						resteIdentiqueMaxNbVoixAll = resteIdentiqueMaxNbVoix;
						if (lengthArrayIndicesRestesIdentiques > 0) {
							for(var key in arrayIndicesRestesIdentiques) {
								if (arrayIndicesRestesIdentiques[key] == resteIdentiqueMaxNbVoixAll) {
									reste_identique_nb_valeur_egal_nb_voix++;
								}
							}
						}
						if (lengthArrayIndicesRestesIdentiquesDetails > 0) {
							for(var key in arrayIndicesRestesIdentiquesDetails) {
								if (arrayIndicesRestesIdentiquesDetails[key] == resteIdentiqueMaxNbVoixAll) {
									reste_identique_nb_valeur_egal_nb_voix++;
								}
							}
						}
					}
					if (resteIdentiqueMaxNbVoix > resteIdentiqueMaxNbVoixDetails) {
						resteIdentiqueMaxNbVoixAll = resteIdentiqueMaxNbVoix;
						if (lengthArrayIndicesRestesIdentiques > 0) {
							for(var key in arrayIndicesRestesIdentiques) {
								if (arrayIndicesRestesIdentiques[key] == resteIdentiqueMaxNbVoixAll) {
									reste_identique_nb_valeur_egal_nb_voix++;
								}
							}
						}
					}
					if (resteIdentiqueMaxNbVoix < resteIdentiqueMaxNbVoixDetails) {
						resteIdentiqueMaxNbVoixAll = resteIdentiqueMaxNbVoixDetails;
						if (lengthArrayIndicesRestesIdentiquesDetails > 0) {
							for(var key in arrayIndicesRestesIdentiquesDetails) {
								if (arrayIndicesRestesIdentiquesDetails[key] == resteIdentiqueMaxNbVoixAll) {
									reste_identique_nb_valeur_egal_nb_voix++;
								}
							}
						}
					}

					reste_identique_nb_valeur_egal_nb_voix = reste_identique_nb_valeur_egal_nb_voix - 1;

					// Determiner si egalite parfaite des nb_voix
					if ((lengthArrayIndicesRestesIdentiques + lengthArrayIndicesRestesIdentiquesDetails) == (reste_identique_nb_valeur_egal_nb_voix + 1)) {
						reste_identique_egalite_parfaite_nb_voix = true;
					} else {
						reste_identique_egalite_parfaite_nb_voix = false;
					}

					if (reste_identique_egalite_parfaite_nb_voix) {
						// Si on a suffisament de sièges pour toutes les organisations à égalité
						if (nbS > nb_valeur_egal) {
							var compteur = 0;

							for (var i = 0; i  < nb_organisation; i++) {
								nb_detail = $("[id^=ligne_"+i+"_]").length;
								if (nb_detail == 0) {
									nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
									nb_sieges = nb_voix / quotient_electoral;
									nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
									nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
									if (nb_sieges_float == valeurRepere) {
										document.getElementById('EleEtablissementType_resultats_'+i+'_nbSieges').value = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbSieges').value) + 1;
										compteur++;

										//Les listes pour lesquels un siège est attribué sont exclus pour la suite des traitements des restes identiques
										arrayIndicesRestesIdentiques[i] = 0;
									}
								} else {
									for (x = 0; x < nb_detail; x++) {
										nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
										nb_sieges_detail = nb_voix_detail / quotient_electoral;
										nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
										nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
										if (nb_sieges_detail_float == valeurRepere) {
											document.getElementById('EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbSieges').value = Number(document.getElementById('EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbSieges').value) + 1;
											majLigneGeneriqueOrganisationsDetaillees(i);
											compteur++;

											//Les listes pour lesquels un siège est attribué sont exclus pour la suite des traitements des restes identiques
											arrayIndicesRestesIdentiquesDetails[i+'_'+x] = 0;
										}
									}
								}
								majLigneGeneriqueOrganisationsDetaillees(i);
							}

							j = j + nb_valeur_egal;
							nbSiegesDistPasseDeux = nbSiegesDistPasseDeux + compteur;

							// RAZ les max reste courant dans les tableaux de reste
							for (var y = 0; y < arrayRestes.length; y++) {
								if (arrayRestes[y] == valeurRepere) {
									arrayRestes[y] = 0;
								}
							}
							for (var z = 0; z < arrayRestesDetails.length; z++) {
								if (arrayRestesDetails[z] == valeurRepere) {
									arrayRestesDetails[z] = 0;
								}
							}
						}

					} else {
						var nbRestesIdentiquesATraiter = lengthArrayIndicesRestesIdentiques + lengthArrayIndicesRestesIdentiquesDetails;
						var nbSiegesDistPasseTrois = 0;
						if (nbS > reste_identique_nb_valeur_egal_nb_voix) {
							for (var t = 0; t < nbRestesIdentiquesATraiter; t++) {
								if (resteIdentiqueMaxNbVoixAll > 0 && nbS > reste_identique_nb_valeur_egal_nb_voix) {
									// Rajouter +1 au nb_siege par ordre max de nb_voix et retirer nb_voix du tbl des nb_voix
									var compteur = 0;
									if (lengthArrayIndicesRestesIdentiques > 0) {
										for (var key in arrayIndicesRestesIdentiques) {
											if (arrayIndicesRestesIdentiques[key] == resteIdentiqueMaxNbVoixAll && resteIdentiqueMaxNbVoixAll > 0) {
												document.getElementById('EleEtablissementType_resultats_'+key+'_nbSieges').value = Number(document.getElementById('EleEtablissementType_resultats_'+key+'_nbSieges').value) + 1;
												compteur++;
												arrayIndicesRestesIdentiques[key] = 0;
											}
										}
									}
									if (lengthArrayIndicesRestesIdentiquesDetails > 0) {
										for (var key in arrayIndicesRestesIdentiquesDetails) {
											if (arrayIndicesRestesIdentiquesDetails[key] == resteIdentiqueMaxNbVoixAll && resteIdentiqueMaxNbVoixAll > 0) {
												document.getElementById('EleEtablissementType_resultatsDetailles_'+key+'_nbSieges').value = Number(document.getElementById('EleEtablissementType_resultatsDetailles_'+key+'_nbSieges').value) + 1;
												compteur++;

												arrayIndicesRestesIdentiquesDetails[key] = 0;
												var iToMaj = key.substr(0, key.indexOf('_'));
												majLigneGeneriqueOrganisationsDetaillees(iToMaj);
											}
										}
									}

									// MAJ des organisations detaillees
									for (var h = 0; h < nb_organisation; h++) {
										majLigneGeneriqueOrganisationsDetaillees(h);
									}

									j += compteur;
									nbSiegesDistPasseDeux = nbSiegesDistPasseDeux + compteur;

									nbS = nbSiegesRestant - nbSiegesDistPasseDeux;

									nbSiegesDistPasseTrois += compteur;
									// RAZ les max reste courant dans les tableaux de reste
									if (nbSiegesDistPasseTrois == nbRestesIdentiquesATraiter) {
										for (var y = 0; y < arrayRestes.length; y++) {
											if (arrayRestes[y] == valeurRepere) {
												arrayRestes[y] = 0;
											}
										}
										for (var z = 0; z < arrayRestesDetails.length; z++) {
											if (arrayRestesDetails[z] == valeurRepere) {
												arrayRestesDetails[z] = 0;
											}
										}
									}
								}

								// re-determiner le resteIdentiqueMaxNbVoixAll  et reste_identique_nb_valeur_egal_nb_voix
								if (lengthArrayIndicesRestesIdentiques > 0) {
									resteIdentiqueMaxNbVoix = getMaxObjectArray(arrayIndicesRestesIdentiques);
								}
								if (lengthArrayIndicesRestesIdentiquesDetails > 0) {
									resteIdentiqueMaxNbVoixDetails = getMaxObjectArray(arrayIndicesRestesIdentiquesDetails);
								}
								reste_identique_nb_valeur_egal_nb_voix = 0;
								if (resteIdentiqueMaxNbVoix == resteIdentiqueMaxNbVoixDetails) {
									resteIdentiqueMaxNbVoixAll = resteIdentiqueMaxNbVoix;
									if (lengthArrayIndicesRestesIdentiques > 0) {
										for(var key in arrayIndicesRestesIdentiques) {
											if (arrayIndicesRestesIdentiques[key] == resteIdentiqueMaxNbVoixAll && resteIdentiqueMaxNbVoixAll > 0) {
												reste_identique_nb_valeur_egal_nb_voix++;
											}
										}
									}
									if (lengthArrayIndicesRestesIdentiquesDetails > 0) {
										for(var key in arrayIndicesRestesIdentiquesDetails) {
											if (arrayIndicesRestesIdentiquesDetails[key] == resteIdentiqueMaxNbVoixAll && resteIdentiqueMaxNbVoixAll > 0) {
												reste_identique_nb_valeur_egal_nb_voix++;
											}
										}
									}
								}
								if (resteIdentiqueMaxNbVoix > resteIdentiqueMaxNbVoixDetails) {
									resteIdentiqueMaxNbVoixAll = resteIdentiqueMaxNbVoix;
									if (lengthArrayIndicesRestesIdentiques > 0) {
										for(var key in arrayIndicesRestesIdentiques) {
											if (arrayIndicesRestesIdentiques[key] == resteIdentiqueMaxNbVoixAll && resteIdentiqueMaxNbVoixAll > 0) {
												reste_identique_nb_valeur_egal_nb_voix++;
											}
										}
									}
								}
								if (resteIdentiqueMaxNbVoix < resteIdentiqueMaxNbVoixDetails) {
									resteIdentiqueMaxNbVoixAll = resteIdentiqueMaxNbVoixDetails;
									if (lengthArrayIndicesRestesIdentiquesDetails > 0) {
										for(var key in arrayIndicesRestesIdentiquesDetails) {
											if (arrayIndicesRestesIdentiquesDetails[key] == resteIdentiqueMaxNbVoixAll && resteIdentiqueMaxNbVoixAll > 0) {
												reste_identique_nb_valeur_egal_nb_voix++;
											}
										}
									}
								}

								reste_identique_nb_valeur_egal_nb_voix = reste_identique_nb_valeur_egal_nb_voix - 1;

							}
						}
					}
				}
			}

			// mantis 147133 vérification si nécessité d'une itération supplémentaire pour attribution des sièges en absence d'égalité de reste identique 
			// après les passes 1 et 2 et avant l'ouverture au plus âgé
			//j+1 >= nbSiegesRestant && nbSiegesDistPasseDeux < nbSiegesRestant && absence d'egalite de reste => j - 1 pr une nouvelle itération
			var nbListeAvecResteIdentique = determinationNombreListeAvecResteIdentique(arrayRestes, arrayRestesDetails, resteIdentiqueMaxNbVoixAll);
//			console.log("nbSiegesRestant = " + nbSiegesRestant + " j = " + j + " nbSiegesDistPasseDeux = " + nbSiegesDistPasseDeux +" nbListeAvecResteIdentique = " + nbListeAvecResteIdentique);
			if (j+1 >= nbSiegesRestant && nbSiegesDistPasseDeux < nbSiegesRestant && nbListeAvecResteIdentique == 1) {
				j--;
			}

		}
		//console.log("nbSiegesDistPasseDeux = " + nbSiegesDistPasseDeux + " nbSiegesRestant = "+ nbSiegesRestant);
		// Dans le cas où il reste des sièges à distribuer après la
		// passe 1 et 2 alors on active le tirage au sort
		if (nbSiegesDistPasseDeux < nbSiegesRestant) {
			document.getElementById('tirage_au_sort').value = true;
			// mantis 121833 trouver les listes avec egalité de restes à activer, ajouter dans la fonction enabledChampsTirageAuSort
		}
	}

	calculNbSiegeAuSortADistribue();
	enabledChampsTirageAuSort(arrayRestes, arrayRestesDetails, resteIdentiqueMaxNbVoixAll);
	calculTotalSieges();
}

/*
 * Fonction permettant d'activer ou désactiver l'ensemble des cases de nb sièges
 * au sort
 */

function enabledChampsTirageAuSort(arrayRestes, arrayRestesDetails, resteIdentiqueMaxNbVoixAll) {
	//console.log(arrayRestes);
	//console.log(arrayRestesDetails);
	//console.log(resteIdentiqueMaxNbVoixAll);

	var desactive=true;
	//	mantis 145415
	arrayIndiceAActiver = new Array(); // pas de var devant parce que qqn l'a déjà declaré global au début
	arrayIndiceDetailAActiver = new Array();
	//	mantis 145415
	var maxRestes = 0;
	var maxRestesDetails = 0;
	var quotient_electoral = Number(document.getElementById('quotient_electoral').value);
	var nb_voix, nb_sieges, nb_sieges_entier, nb_sieges_float;
	var nb_detail, nb_voix_detail, nb_sieges_detail, nb_sieges_detail_entier, nb_sieges_detail_float;

	if ($('#tirage_au_sort').val() == 'true') {
		desactive=false;
		// mantis 121833 trouver les listes avec egalité de restes à activer
		if (!isNaN(quotient_electoral) && quotient_electoral != 0) {
			// determiner le maximum des restes
			if (arrayRestes.length > 0) {
				maxRestes = Math.max.apply(Math, arrayRestes);
			}

			if (arrayRestesDetails.length > 0) {
				maxRestesDetails = Math.max.apply(Math, arrayRestesDetails);
			}

			// repérer les indices des listes à activer, ceux qui ont comme valeur le maximum des restes			
			if (maxRestes == maxRestesDetails) {
				// on prend en compte toutes les listes i.e listes sans détails et listes avec détails
				if (maxRestes > 0) {
					for (var i = 0; i  < nb_organisation; i++) {
						nb_detail = $("[id^=ligne_"+i+"_]").length;
						// liste sans détails
						if (nb_detail == 0) {
							nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
							nb_sieges = nb_voix / quotient_electoral;
							nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
							nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
							if (nb_sieges_float == maxRestes) {
								if (resteIdentiqueMaxNbVoixAll > 0) {
									if (resteIdentiqueMaxNbVoixAll == nb_voix) {
										arrayIndiceAActiver.push(i);
									}
								} else {
									arrayIndiceAActiver.push(i);
								}
//								console.log("A activer i : " + i + " reste : " + nb_sieges_float);
							}
						} else {
							// liste avec détails
							for (var x = 0; x < nb_detail; x++) {
								nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
								nb_sieges_detail = nb_voix_detail / quotient_electoral;
								nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
								nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
								if (nb_sieges_detail_float == maxRestesDetails) {
									if (resteIdentiqueMaxNbVoixAll > 0) {
										if (resteIdentiqueMaxNbVoixAll == nb_voix_detail) {
											arrayIndiceDetailAActiver.push(i + '_' + x);
										}
									} else {
										arrayIndiceDetailAActiver.push(i + '_' + x);
									}
//									console.log("A activer detail  : " + i + "_" + x + " reste : " + nb_sieges_detail_float);
								}
							}
						}

					}
				}
			}

			if (maxRestes > maxRestesDetails) {
				// on ne prend en compte que les listes sans details
				if (maxRestes > 0) {
					for (var i = 0; i  < nb_organisation; i++) {
						nb_detail = $("[id^=ligne_"+i+"_]").length;
						// liste sans détails
						if (nb_detail == 0) {
							nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
							nb_sieges = nb_voix / quotient_electoral;
							nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
							nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
							if (nb_sieges_float == maxRestes) {
								if (resteIdentiqueMaxNbVoixAll > 0) {
									if (resteIdentiqueMaxNbVoixAll == nb_voix) {
										arrayIndiceAActiver.push(i);
									}
								} else {
									arrayIndiceAActiver.push(i);
								}
//								console.log("A activer i : " + i + " reste : " + nb_sieges_float);
							}
						}
					}
				}
			}

			if (maxRestes < maxRestesDetails) {
				// on ne prend en compte que les listes avec details
				if (maxRestesDetails > 0) {
					for (var i = 0; i  < nb_organisation; i++) {
						nb_detail = $("[id^=ligne_"+i+"_]").length;
						// liste sans détails
						if (nb_detail > 0) {
							// liste avec détails
							for (var x = 0; x < nb_detail; x++) {
								nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
								nb_sieges_detail = nb_voix_detail / quotient_electoral;
								nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
								nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
								if (nb_sieges_detail_float == maxRestesDetails) {
									if (resteIdentiqueMaxNbVoixAll > 0) {
										if (resteIdentiqueMaxNbVoixAll == nb_voix_detail) {
											arrayIndiceDetailAActiver.push(i + '_' + x);
										}
									} else {
										arrayIndiceDetailAActiver.push(i + '_' + x);
									}
//									console.log("A activer detail  : " + i + "_" + x + " reste : " + nb_sieges_detail_float);
								}
							}
						}
					}
				}
			}
		}

	}
	//setParticipationModifiableFields();
	for (var i=0; i<nb_organisation; i++) {
		// On ne s'occupe ici que des organisations non détaillées
		var nb_detail = $("[id^=ligne_"+i+"_]").length;
		if( nb_detail == 0){
			$('#EleEtablissementType_resultats_'+i+'_nbSieges').attr('disabled', controle_saisie);
			if (Number($('#EleEtablissementType_resultats_'+i+'_nbVoix').val()) > 0 && $.inArray(i, arrayIndiceAActiver) != -1){
				$('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').attr('disabled', desactive);
			}else{
				$('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').attr('disabled', controle_saisie);
			}
		}else{
			// Organisations détaillées
			for (var j=0; j<nb_detail; j++){
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').attr('disabled',controle_saisie);
				if (Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val()) > 0 && $.inArray(i + "_" + j, arrayIndiceDetailAActiver) != -1){
					$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').attr('disabled',desactive);
				}else{
					$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').attr('disabled',controle_saisie);
				}
			}
		}
	}
}

/**
 * Fonction permettant de calculer le nombre de listes avec reste identique
 * mantis 147133
 * @param arrayRestes
 * @param arrayRestesDetails
 * @param resteIdentiqueMaxNbVoixAll
 * @returns
 */
function determinationNombreListeAvecResteIdentique(arrayRestes, arrayRestesDetails, resteIdentiqueMaxNbVoixAll) {
	arrayIndiceAActiver = new Array(); // pas de var devant parce que qqn l'a déjà declaré global au début
	arrayIndiceDetailAActiver = new Array();
	var maxRestes = 0;
	var maxRestesDetails = 0;
	var quotient_electoral = Number(document.getElementById('quotient_electoral').value);
	var nb_voix, nb_sieges, nb_sieges_entier, nb_sieges_float;
	var nb_detail, nb_voix_detail, nb_sieges_detail, nb_sieges_detail_entier, nb_sieges_detail_float;

	if (!isNaN(quotient_electoral) && quotient_electoral != 0) {
		// determiner le maximum des restes
		if (arrayRestes.length > 0) {
			maxRestes = Math.max.apply(Math, arrayRestes);
		}

		if (arrayRestesDetails.length > 0) {
			maxRestesDetails = Math.max.apply(Math, arrayRestesDetails);
		}

		// repérer les indices des listes à activer, ceux qui ont comme valeur le maximum des restes			
		if (maxRestes == maxRestesDetails) {
			// on prend en compte toutes les listes i.e listes sans détails et listes avec détails
			if (maxRestes > 0) {
				for (var i = 0; i  < nb_organisation; i++) {
					nb_detail = $("[id^=ligne_"+i+"_]").length;
					// liste sans détails
					if (nb_detail == 0) {
						nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
						nb_sieges = nb_voix / quotient_electoral;
						nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
						nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
						if (nb_sieges_float == maxRestes) {
							if (resteIdentiqueMaxNbVoixAll > 0) {
								if (resteIdentiqueMaxNbVoixAll == nb_voix) {
									arrayIndiceAActiver.push(i);
								}
							} else {
								arrayIndiceAActiver.push(i);
							}
						}
					} else {
						// liste avec détails
						for (var x = 0; x < nb_detail; x++) {
							nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
							nb_sieges_detail = nb_voix_detail / quotient_electoral;
							nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
							nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
							if (nb_sieges_detail_float == maxRestesDetails) {
								if (resteIdentiqueMaxNbVoixAll > 0) {
									if (resteIdentiqueMaxNbVoixAll == nb_voix_detail) {
										arrayIndiceDetailAActiver.push(i + '_' + x);
									}
								} else {
									arrayIndiceDetailAActiver.push(i + '_' + x);
								}
							}
						}
					}

				}
			}
		}

		if (maxRestes > maxRestesDetails) {
			// on ne prend en compte que les listes sans details
			if (maxRestes > 0) {
				for (var i = 0; i  < nb_organisation; i++) {
					nb_detail = $("[id^=ligne_"+i+"_]").length;
					// liste sans détails
					if (nb_detail == 0) {
						nb_voix = Number(document.getElementById('EleEtablissementType_resultats_'+i+'_nbVoix').value);
						nb_sieges = nb_voix / quotient_electoral;
						nb_sieges_entier = isNaN(Math.floor(nb_sieges)) ? 0 : Math.floor(nb_sieges);
						nb_sieges_float = (nb_sieges - nb_sieges_entier).toFixed(10);
						if (nb_sieges_float == maxRestes) {
							if (resteIdentiqueMaxNbVoixAll > 0) {
								if (resteIdentiqueMaxNbVoixAll == nb_voix) {
									arrayIndiceAActiver.push(i);
								}
							} else {
								arrayIndiceAActiver.push(i);
							}
						}
					}
				}
			}
		}

		if (maxRestes < maxRestesDetails) {
			// on ne prend en compte que les listes avec details
			if (maxRestesDetails > 0) {
				for (var i = 0; i  < nb_organisation; i++) {
					nb_detail = $("[id^=ligne_"+i+"_]").length;
					// liste sans détails
					if (nb_detail > 0) {
						// liste avec détails
						for (var x = 0; x < nb_detail; x++) {
							nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+x+'_nbVoix').val());
							nb_sieges_detail = nb_voix_detail / quotient_electoral;
							nb_sieges_detail_entier = isNaN(Math.floor(nb_sieges_detail)) ? 0 : Math.floor(nb_sieges_detail);
							nb_sieges_detail_float = (nb_sieges_detail - nb_sieges_detail_entier).toFixed(10);
							if (nb_sieges_detail_float == maxRestesDetails) {
								if (resteIdentiqueMaxNbVoixAll > 0) {
									if (resteIdentiqueMaxNbVoixAll == nb_voix_detail) {
										arrayIndiceDetailAActiver.push(i + '_' + x);
									}
								} else {
									arrayIndiceDetailAActiver.push(i + '_' + x);
								}
							}
						}
					}
				}
			}
		}
	}

	return arrayIndiceAActiver.length + arrayIndiceDetailAActiver.length;
}

function setParticipationModifiableFields(){

	var desactive = $('#tirage_au_sort').val() == 'true' ? false : controle_saisie;

	for (var i=0; i<nb_organisation; i++) {
		// Organisations non détaillées
		var nb_detail = $("[id^=ligne_"+i+"_]").length;
		$('input#total_'+i).attr('disabled',true);
		$('input#EleEtablissementType_resultats_'+i+'_nbSieges').attr('disabled', controle_saisie);
		if (Number($('#EleEtablissementType_resultats_'+i+'_nbVoix').val()) > 0 && $.inArray(i, arrayIndiceAActiver) != -1){
			$('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').attr('disabled', desactive);
		}else{
			$('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').attr('disabled', controle_saisie);
		}
		// Organisations détaillées
		for (var j=0; j<nb_detail; j++){
			$('input#EleEtablissementType_resultats_'+i+'_nbCandidats').attr('disabled', true);
			$('input#EleEtablissementType_resultats_'+i+'_nbVoix').attr('disabled', true);
			$('input#EleEtablissementType_resultats_'+i+'_nbSieges').attr('disabled', true);
			$('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').attr('disabled', true);
			$('input#total_'+i+'_'+j).attr('disabled',true);
			$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').attr('disabled',controle_saisie);
			if (Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val()) > 0 && $.inArray(i + "_" + j, arrayIndiceDetailAActiver) != -1){
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').attr('disabled',desactive);
			}else{
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').attr('disabled',controle_saisie);
			}
		}
	}
}

/*
 * Fonction verifiant l'intégrité des valeurs renseignées dans les champs sièges
 * au sort
 */

function verifNbSiegeSort() {

	var nbSiegesAutoDist = 0;
	var nbSiegesAuSortDist = 0;

	for (var i=0; i<nb_organisation; i++) {
		var value = document.getElementById('EleEtablissementType_resultats_'+i+'_nbSiegesSort').value;
		if ((parseFloat(value) != parseInt(value)) || isNaN(value) || Number(value)<0) {
			alert('Le nombre de sièges attribués au tirage au sort doit être un entier positif.');
			$('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val(0);
		} else {
			if (Number(value) > 1 && controle_saisie) { // mantis 121833
				// Mantis 173367 complement: modification de message d'erreur
				alert('Par liste, le nombre maximal de sièges attribués au candidat le plus âgé est 1.');
				$('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val(0);
				var nb_detail = $("[id^=ligne_"+i+"_]").length;
				if (nb_detail > 0) {
					for (var j = 0; j < nb_detail; j++) {
						$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').val(0);
					}
				}
				disabledButtonEnregistrer();
			}
			nbSiegesAutoDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSieges').val());
			nbSiegesAuSortDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val());
		}


	}

	var nbSiegesDistTotal = nbSiegesAutoDist + nbSiegesAuSortDist;
	var nbSiegesTotal = Number($('input#EleEtablissementType_participation_nbSiegesPourvoir').val());

	if (nbSiegesDistTotal > nbSiegesTotal && controle_saisie) {
		// Mantis 173367 complement: modification de message d'erreur
		alert('Erreur : Le nombre de sièges attribués au candidat le plus âgé a été dépassé.');

		initialisationSiegesAuSort();
		//calculeSommesOrganisationsDetaillees();
		disabledButtonEnregistrer();

		$('#nb_sieges_au_sort').val(nbSiegesTotal - nbSiegesAutoDist);
	}
	else {
		$('#nb_sieges_au_sort').val(nbSiegesTotal - nbSiegesDistTotal);
	}
	calculTotalSieges();
}

/*
 * Fonction calculant le nombre de siège au sort restant à ditribuer
 */

function calculNbSiegeAuSortADistribue() {

	var nbsiegesTotal = Number($('input#EleEtablissementType_participation_nbSiegesPourvoir').val());
	var nbSiegesDist = 0;

	for (var i=0; i<nb_organisation; i++) {
		nbSiegesDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSieges').val());
		nbSiegesDist += Number($('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val());
	}
	document.getElementById('nb_sieges_au_sort').value = (nbsiegesTotal - nbSiegesDist);
}

/*
 * Fonction initialisant les sièges au sort à 0
 */

function initialisationSiegesAuSort() {

	for (var i=0; i<nb_organisation; i++) {
		//Traitement pour les organisations détaillées
		var nb_detail = $("[id^=ligne_"+i+"_]").length;
		if(nb_detail > 0){
			for (var j=0; j<nb_detail; j++){
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').val(0);
			}
		} else {
			// Traitement pour les organisation non détaillées
			$('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val(0);
		}
	}


}

/*
 * Fonction calculant la somme des sièges distribués et des sièges au sort pour
 * chaque organisation
 */

function calculTotalSieges() {

	for (var i=0; i<nb_organisation; i++) {
		$('#total_'+i).val(
			Number($('#EleEtablissementType_resultats_'+i+'_nbSieges').val())
			+ Number($('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val())
		);

		// Calcul du total du détail si l'organisation est détaillée
		var nb_detail = $("[id^=ligne_"+i+"_]").length;

		if(nb_detail > 0){
			for (var j=0; j<nb_detail; j++){
				$('#total_'+i+'_'+j).val(
					Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').val())
					+ Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').val())
				);
			}
		}
	}
	//Recalcule la somme des suffrages et des sièges
	calculLigneToutesListes();
}

/*
 * Fonction ajoutant une ligne de résultat détaillé sur clic du bouton '+' sur
 * une organisation
 */
function addDetail(numLigne, idOrganisation, pathPictoDelete){

	var n = ($("[id^=ligne_"+numLigne+"]").length)-1;
	var nb_resultats_detailles = $('#nb_resultats_detailles').val();

	var id = 'ligne_'+numLigne+'_'+n;

	var input_id = 'EleEtablissementType_resultatsDetailles_' + numLigne + '_' + n;

	var code = '<tr id='+id+'>';
	code += '<td>';
	code += '   <input id="EleEtablissementType_resultatsDetailles_'+nb_resultats_detailles+'_organisation" type="hidden" value="'+idOrganisation+'" name="EleEtablissementType[resultatsDetailles]['+nb_resultats_detailles+'][organisation]">';
	code += '	<img class="retirer" title="Retirer la liste détaillée" alt="X" onClick="removeDetail($(this).closest(\'tr\').attr(\'id\'));" src="'+pathPictoDelete+'"/>';
	code += '	<input type="text" class="libelleDetail" id="'+input_id+'_libelle" name="EleEtablissementType[resultatsDetailles]['+nb_resultats_detailles+'][libelle]"/>';
	code += '</td>';
	code += '<td>';
	code += '	<input onchange="calculSommeCandidatsDetails('+numLigne+');" type="number" value="0" id="'+input_id+'_nbCandidats" name="EleEtablissementType[resultatsDetailles]['+nb_resultats_detailles+'][nbCandidats]"/>';
	code += '</td>';
	code += '<td>';
	code += '	<input onchange="calculeSommesOrganisationsDetaillees('+numLigne+');" type="number" value="0" id="'+input_id+'_nbVoix" name="EleEtablissementType[resultatsDetailles]['+nb_resultats_detailles+'][nbVoix]"/>';
	code += '</td>';
	code += '<td>';
	code += '	<input onchange="calculeSommesOrganisationsDetaillees('+numLigne+');" type="number" value="0" id="'+input_id+'_nbSieges" name="EleEtablissementType[resultatsDetailles]['+nb_resultats_detailles+'][nbSieges]"/>';
	code += '</td>';
	code += '<td>';
	code += '	<input onchange="calculeSommesOrganisationsDetaillees('+numLigne+'); verifNbSiegeSort();" type="number" value="0" id="'+input_id+'_nbSiegesSort" name="EleEtablissementType[resultatsDetailles]['+nb_resultats_detailles+'][nbSiegesSort]"/>';
	code += '</td>';
	code += '<td>';
	code += '	<input onchange="calculeSommesOrganisationsDetaillees('+numLigne+');" type="text" value="0" id="total_'+ numLigne + '_' + n + '" />';
	code += '</td>';

	code += '</tr>';

	// Insertion de la ligne
	var derniereLigne = (n == 0) ? "#ligne_"+numLigne : "#ligne_"+numLigne+"_"+(n-1);
	$(code).insertAfter(derniereLigne);

	// On grise le nombre de voix de la cellule mère car il est calculé
	// automatiquement en fonction du nombre de voix
	$('#EleEtablissementType_resultats_'+numLigne+'_nbVoix').attr('disabled',true);
	$('#EleEtablissementType_resultats_'+numLigne+'_nbSieges').attr('disabled',true); // mantis 0121840
	$('#EleEtablissementType_resultats_'+numLigne+'_nbSiegesSort').attr('disabled',true); // mantis 0121840
	$('#EleEtablissementType_resultats_'+numLigne+'_nbCandidats').attr('disabled',true);

	// Evol sur la levée des champs à désactiver
	setParticipationModifiableFields();

	//nombre de résultats détaillés
	$('#nb_resultats_detailles').val(parseInt(nb_resultats_detailles)+1);

	// Recalcul des sommes
	calculeSommesOrganisationsDetaillees(numLigne);
}

/*
 * Fonction retirant une ligne de résultat détaillé sur clic du bouton "X" à
 * côté de l'organisation fille
 */
function removeDetail(id){

//	$('#'+id).remove();

	// Un peu cracra, mais pas besoin d'identifier les éléments à l'intérieur du
	// tr
	var parts = id.split("_");
	var numLigne = parts[1];
	var numDetail = parts[2];

	// de numDetail à nbreDetail
	var nbreDetail = $("[id^=ligne_"+numLigne+"_]").length;

	// décrémenter le n° de détail
	// décrémenter les ids des inputs qui sont après l'élément supprimé
	for(var n = parseInt(numDetail)+1 ;n <= nbreDetail; n++){
		// alert ('ligne_'+numLigne+'_'+ n +' -> '+
		// 'ligne_'+numLigne+'_'+(n-1));
//		$('#ligne_'+numLigne+'_'+n).attr('id', 'ligne_'+numLigne+'_'+(n-1));
//		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbCandidats').attr('id', 'EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbCandidats');
//		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbVoix').attr('id', 'EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbVoix');
//		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbSieges').attr('id', 'EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbSieges');
//		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbSiegesSort').attr('id', 'EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbSiegesSort');

		// changement de valeur au lieu de id
		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_libelle').val($('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_libelle').val());
		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbCandidats').val($('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbCandidats').val());
		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbVoix').val($('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbVoix').val());
		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbSieges').val($('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbSieges').val());
		$('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+(n-1)+'_nbSiegesSort').val($('#EleEtablissementType_resultatsDetailles_'+numLigne+'_'+n+'_nbSiegesSort').val());
		$('#total_'+numLigne+'_'+(n-1)).val($('#total_'+numLigne+'_'+n).val());

	}

	// suppression de la derniere ligne de détail apres decalage des elements details
	$('#ligne_'+numLigne+'_'+(nbreDetail-1)).remove();

	// Réactivation de la saisie du nombre de voix dans la cellule mère
	if($("[id^=ligne_"+numLigne+"_]").length == 0){
		$('#EleEtablissementType_resultats_'+numLigne+'_nbCandidats').removeAttr('disabled');
		$('#EleEtablissementType_resultats_'+numLigne+'_nbCandidats').val(0);
		$('#EleEtablissementType_resultats_'+numLigne+'_nbVoix').removeAttr('disabled');
		$('#EleEtablissementType_resultats_'+numLigne+'_nbVoix').val(0);
		$('#EleEtablissementType_resultats_'+numLigne+'_nbSieges').removeAttr('disabled'); // mantis 0121840
		$('#EleEtablissementType_resultats_'+numLigne+'_nbSiegesSort').removeAttr('disabled'); // mantis 0121840
	}

	// Recalcul des sommes
	calculeSommesOrganisationsDetaillees(numLigne);
	calculSommeCandidatsDetails(numLigne);

	setParticipationModifiableFields();

}

/*
 * Fonction qui recalcule le nombre de voix en fonction du détail
 */
function calculeSommesOrganisationsDetaillees(numLigne){

	var i = numLigne;
	var nb_detail = $("[id^=ligne_"+i+"_]").length;
	if(nb_detail > 0){
		var nbCandidatTotal = 0;
		var nbVoixTotal = 0;
		var nbSiegesTotal = 0;
		var nbSiegesSortTotal = 0;

		var valueNbCandidat = 0;
		var valueNbVoix = 0;
		var valueNbSieges = 0;
		var valueNbSiegesSort = 0; // mantis 0121841

		// defect #247 Saisie des résultats : valeur Infinity division par quotient_electoral = 0
		var quotient_electoral = Number($('#quotient_electoral').val());

		for(var j = 0; j < nb_detail; j++){

			valueNbCandidat = $('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbCandidats').val();
			valueNbVoix = $('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val();
			valueNbSieges = $('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').val();

			valueNbSiegesSort = $('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').val();

			if ((parseFloat(valueNbVoix) != parseInt(valueNbVoix)) || isNaN(valueNbVoix) || Number(valueNbVoix)<0) {
				alert('Le nombre de voix doit être un entier positif.');
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val(0);
			}

			// defect #247 Saisie des résultats : valeur Infinity division par quotient_electoral = 0
			if (Number(valueNbVoix) > 0 && (isNaN(quotient_electoral) || quotient_electoral == 0)) {
				alert("Le nombre de sièges à pourvoir doit être renseigné.");
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val(0);
			}

			if ((parseFloat(valueNbSieges) != parseInt(valueNbSieges)) || isNaN(valueNbSieges) || Number(valueNbSieges)<0) {
				alert('Le nombre de sièges attribués au quotient et au plus fort reste doit être un entier positif.');
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').val(0);
			}

			if ((parseFloat(valueNbSiegesSort) != parseInt(valueNbSiegesSort)) || isNaN(valueNbSiegesSort) || Number(valueNbSiegesSort)<0) {
				alert('Le nombre de sièges attribués au tirage au sort doit être un entier positif.');
				$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').val(0);
			} else {
				if (Number(valueNbSiegesSort) > 1 && controle_saisie) { // mantis 0121841
					alert('Le nombre maximal de sièges attribués au tirage au sort, autorisé dans la liste détaillée, est 1.');
					$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').val(0);
				}
			}

			nbCandidatTotal += parseInt(valueNbCandidat);
			nbVoixTotal += parseInt(valueNbVoix);
			nbSiegesTotal += parseInt(valueNbSieges);
			nbSiegesSortTotal += parseInt(valueNbSiegesSort);
		}

		$('input#EleEtablissementType_resultats_'+i+'_nbCandidats').val(nbCandidatTotal);
		$('input#EleEtablissementType_resultats_'+i+'_nbVoix').val(nbVoixTotal);
		$('input#EleEtablissementType_resultats_'+i+'_nbSieges').val(nbSiegesTotal);
		$('input#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val(nbSiegesSortTotal);
	}

	// Rappel des calculs pour la liste mère
	if(controle_saisie){
		calculRepartitionDesSieges(i);
	}else{
		verifNbSiegeSort();
	}

	calculLigneToutesListes();
}

/*
 * Fonction qui calcule le nombre de suffrages exprimés en fonction du nombre de votants et du nombre de bulletins nuls ou blancs
 */
function calculNbExprimes(){

	var nbVotants = document.getElementById('EleEtablissementType_participation_nbVotants').value;
	var nbNulsBlancs = document.getElementById('EleEtablissementType_participation_nbNulsBlancs').value;

	var div_quotient = document.getElementById('id_quotient');
	div_quotient.innerHTML = '';

	var nbExprimes = nbVotants - nbNulsBlancs;

	document.getElementById('EleEtablissementType_participation_nbExprimes').value = nbExprimes;
	$('#EleEtablissementType_participation_nbExprimes').trigger('change'); // pour declencher le onchange sur un input en readonly

}

function majLigneGeneriqueOrganisationsDetaillees(i) {
	var nb_detail = $("[id^=ligne_"+i+"_]").length;

	if (nb_detail > 0) {
		var nbVoixTotal = 0;
		var nbSiegesTotal = 0;
		var nbSiegesSortTotal = 0;

		for (var j = 0; j < nb_detail; j++) {
			nbVoixTotal += parseInt($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val());
			nbSiegesTotal += parseInt($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').val());
			nbSiegesSortTotal += parseInt($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').val());
		}

		$('#EleEtablissementType_resultats_'+i+'_nbVoix').val(nbVoixTotal);
		$('#EleEtablissementType_resultats_'+i+'_nbSieges').val(nbSiegesTotal);
		$('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').val(nbSiegesSortTotal);
	}

}

function getMaxObjectArray(obj) {
	var arrTmp = new Array();
	for(var key in obj) {
		arrTmp.push(obj[key]);
	}
	return Math.max.apply(Math, arrTmp);
}

function getLengthObjectArray(obj) {
	var taille = 0;
	for(var key in obj) {
		taille++;
	}
	return taille;
}

/*
 * Fonction affichant la somme des nombres de candidats titulaires
 * 
 */
function calculNombreTotalCandidats() {
	var nbTotalCandidats = 0;
	var nbCandidats;
	for (var i = 0; i < nb_organisation; i++) {
		nbCandidats = $("#EleEtablissementType_resultats_"+i+"_nbCandidats").val();
		if (parseFloat(nbCandidats) != parseInt(nbCandidats) || isNaN(nbCandidats) || Number(nbCandidats) < 0) {
			alert("Le nombre de candidats titulaires doit être un entier positif.");
			$("#EleEtablissementType_resultats_"+i+"_nbCandidats").val(0);
		} else {
			nbTotalCandidats += Number(nbCandidats);
		}
	}

	$("#nbTotalCandidatsTitulaires").html(nbTotalCandidats);
}

/*
 * Fonction qui recalcule le nombre de candidats en fonction du détail
 */
function calculSommeCandidatsDetails(numLigne) {
	var i = numLigne;
	var nb_detail = $("[id^=ligne_"+i+"_]").length;
	if (nb_detail > 0) {
		var nbCandidatsTotalLigne = 0;
		var nbCandidatsDetail;
		for (var j = 0; j < nb_detail; j++) {
			nbCandidatsDetail = $("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val();
			if (parseFloat(nbCandidatsDetail) != parseInt(nbCandidatsDetail) || isNaN(nbCandidatsDetail) || Number(nbCandidatsDetail) < 0) {
				alert("Le nombre de candidats titulaires doit être un entier positif.");
				$("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val(0);
			} else {
				////////anomalie 0218933 //////////
				// On récupère la valeur du nombre de sièges pourvus
				var nombre_siege = $("#EleEtablissementType_participation_nbSiegesPourvoir").val();

				if (nombre_siege == 1) {
					// Si la valeur courante est supérieur à 1 on affiche un message d'erreur
					if (nbCandidatsDetail > 1) {
						// Alors cette valeur ne peut pas excéder 1
						alert("le nombre de sièges à pourvoir étant égal à 1, le nombre de candidats titulaires ne peut être supérieur à 1.");
						$("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val(0);
						nbCandidatsDetail = 0;
					}
				}
				// Sinon si le nombre de siège est supérieur à 1
				else if (nombre_siege > 1) {
					// On paramètre une regex pour exclure le chiffre 1 puis != 1 car nombre_siege > 1
					$("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").attr('pattern', '^(0*)([2-9]+)|(1[0-9]+)|0*$');
					if (nbCandidatsDetail == 1) {
						alert("le nombre de sièges à pourvoir étant supérieur à 1, le nombre de candidats titulaires ne peut être égal à 1.");
						$("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val(0);
						nbCandidatsDetail = 0;
					}
				}
				//////// FIN anomalie 0218933 //////////
				nbCandidatsTotalLigne += Number(nbCandidatsDetail);
			}
		}
		$("input#EleEtablissementType_resultats_"+i+"_nbCandidats").val(nbCandidatsTotalLigne);
	}

	calculNombreTotalCandidats();
}

/*
 * RG_SAISIE_105 : Le nombre total de candidats titulaires saisi sur les listes doit être inférieur ou égal au nombre de sièges à pourvoir
 */
//function checkNombreTotalCandidats() {
//	var nbTotalCandidats = Number($("#nbTotalCandidatsTitulaires").html());
//	var nbSiegesPourvoir = Number($("#EleEtablissementType_participation_nbSiegesPourvoir").val());
//	if (nbTotalCandidats > nbSiegesPourvoir) {
//		alert("Le nombre total de candidats titulaires doit être inférieur ou égal au nombre de sièges à pourvoir.");
//		return false;
//	}
//	return true;
//}

/*
 * Fonction qui vérifie le nombre de de candidats sur chaque liste
 * Le nombre de candidats titulaires pour une liste doit être inférieur ou égal au nombre de sièges calculés par la répartition.
 * 
 */
//function checkNombreCandidatsParListe() {
//	var nbCandidats;
//	var nbSiegesAttribuesLigne;
//	var nb_detail;
//	var nomListe;
//	var messageErreurNbCandidatsListe = "Le nombre de candidats titulaires pour une liste doit être inférieur ou égal au nombre de sièges calculés par la répartition : ";
//	var nbErreur = 0;
//	for (var i = 0; i < nb_organisation; i++) {
//		nb_detail = $("[id^=ligne_"+i+"_]").length;
//		if (nb_detail > 0) {
//			var nbCandidatsDetail;
//			var nbSiegesAttribuesDetail;
//			for (var j = 0; j < nb_detail; j++) {
//				nbCandidatsDetail = Number($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val());
//				nbSiegesAttribuesDetail = Number($("#total_"+i+"_"+j).val());
//				
//				if (nbCandidatsDetail > nbSiegesAttribuesDetail) {
//					nomListe = $.trim($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_libelle").val());
//					messageErreurNbCandidatsListe += "\n"  + nomListe;
//					nbErreur++;
//				}
//			}
//			
//		} else {
//			nbCandidats = Number($("#EleEtablissementType_resultats_"+i+"_nbCandidats").val());
//			nbSiegesAttribuesLigne = Number($("#total_"+i).val());
//					
//			if (nbCandidats > nbSiegesAttribuesLigne) {
//				nomListe = $.trim($("tr#ligne_"+i).find("td:first").html());
//				nomListe = nomListe.replace(/(<([^>]+)>)/ig,""); // strip_tags
//				messageErreurNbCandidatsListe += "\n"  + nomListe;
//				nbErreur++;
//			}
//		}
//	}
//
//	if (nbErreur > 0) {
//		alert(messageErreurNbCandidatsListe);
//		return false;
//	}
//	
//	return true;
//}

/*
 * Fonction qui vérifie le nombre de sièges non pourvus pour chaque liste
 * Envoi un alert ou un confirm
 * Appelée sur clic du bouton "Calcul de la repartition" -> avecConfirmation = false, alert et du bouton "Enregistrer les données" -> avecConfirmation = true, confirm
 * 
 */
function checkNombreSiegesNonPourvus(avecConfirmation) {
	// pour chaque ligne, si nbCandidats < nbSiegesAttribuesLigne => alert Il y a n sièges non pourvus pour la liste X
	var nbCandidats;
	var nbSiegesAttribuesLigne;
	var nbSiegesNonPourvus;
	var nomListe;
	var messageErreurSiegesNonPourvus = "";
	var nb_detail;
	var messageConfirmDeficit = getMessageSiegesNonPourvus;

	for (var i = 0; i < nb_organisation; i++) {
		nb_detail = $("[id^=ligne_"+i+"_]").length;
		if (nb_detail > 0) {
			var nbCandidatsDetail;
			var nbSiegesAttribuesDetail;
			for (var j = 0; j < nb_detail; j++) {
				nbCandidatsDetail = Number($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val());
				nbSiegesAttribuesDetail = Number($("#total_"+i+"_"+j).val());
				if (nbCandidatsDetail < nbSiegesAttribuesDetail) {
					// nombre de sièges non pourvus = nombre de sièges à pourvoir – nombre de candidats titulaires, et X : nom de la liste
					nbSiegesNonPourvus = nbSiegesAttribuesDetail - nbCandidatsDetail;
					nomListe = $.trim($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_libelle").val());
					nomListe = nomListe.replace(/(\r\n|\n|\r)/gm, "").trim(); // strip_tags
					messageErreurSiegesNonPourvus += "Il y a " + nbSiegesNonPourvus + " siège(s) non pourvu(s) pour la liste " + nomListe;
				}
			}
		} else {
			nbCandidats = Number($("#EleEtablissementType_resultats_"+i+"_nbCandidats").val());
			nbSiegesAttribuesLigne = Number($("#total_"+i).val());
			if (nbCandidats < nbSiegesAttribuesLigne) {
				// nombre de sièges non pourvus = nombre de sièges à pourvoir – nombre de candidats titulaires, et X : nom de la liste
				nbSiegesNonPourvus = nbSiegesAttribuesLigne - nbCandidats;
				nomListe = $.trim($("tr#ligne_"+i).find("td:first").html());
				nomListe = nomListe.replace(/(<([^>]+)>)/ig,""); // strip_tags
				nomListe = nomListe.replace(/(\r\n|\n|\r)/gm, "").trim(); // strip_tags
				messageErreurSiegesNonPourvus += "Il y a " + nbSiegesNonPourvus + " siège(s) non pourvu(s) pour la liste " + nomListe;
			}
		}
	}

	if (messageErreurSiegesNonPourvus != "") {
		if (avecConfirmation) {
			// message de confirmation
			if (confirm(messageConfirmDeficit + "\n\nConfirmez-vous l'enregistrement des données ?")) {
				return true;
			} else {
				return false;
			}
		} else {
			alert(messageErreurSiegesNonPourvus);
		}
	}
	return true;
}

/*
 * Fonction qui vérifie si on est dans un cas de carence de candidats
 * RG_SAISIE_108 : Absence de candidat, renseigner uniquement le nombre d’inscrits, avec une valeur numérique supérieure ou égale à 0.
 * Le nombre de votants, de bulletins nuls ou blancs et de suffrages exprimés, si non renseignés, seront valorisés à 0.
 * Aucune donnée dans le tableau de répartition
 * 
 */
function checkCarence() {

	var nbInscrits = $("#EleEtablissementType_participation_nbInscrits").val();
	var nbVotants = $("#EleEtablissementType_participation_nbVotants").val();
	var nbNulsBlancs = $("#EleEtablissementType_participation_nbNulsBlancs").val();
	var nbExprimes = $("#EleEtablissementType_participation_nbExprimes").val();
	var nbSiegesPourvoir = $("#EleEtablissementType_participation_nbSiegesPourvoir").val();
	var nbSiegesPourvus = $("#EleEtablissementType_participation_nbSiegesPourvus").val();
	var nbTotalCandidatsTitulaires = $("#nbTotalCandidatsTitulaires").html();
	var nbVoixDistribues = $("#nbVoixDistribues").html();
	var nbSiegesDistribues = $("#nbSiegesDistribues").html();

	// verifier que nbInscrits est renseigné avec une valeur >= 0
	if (nbInscrits.length != 0 && nbInscrits >= 0 && parseFloat(nbInscrits) == parseInt(nbInscrits)) {
		// verifier que nb votants, de bulletins nuls ou blancs, de suffrages exprimés, de sièges pourvus sont vides ou = 0
		if ((nbVotants.length == 0 || nbVotants == 0)
			&& (nbNulsBlancs.length == 0 || nbNulsBlancs == 0)
			&& (nbExprimes.length == 0 || nbExprimes == 0)
			&& (nbSiegesPourvus.length == 0 || nbSiegesPourvus == 0)) {
			// si vide => set à 0
			if (nbVotants.length == 0) {
				$("#EleEtablissementType_participation_nbVotants").val(0);
			}

			if (nbNulsBlancs.length == 0) {
				$("#EleEtablissementType_participation_nbNulsBlancs").val(0);
			}

			if (nbExprimes.length == 0) {
				$("#EleEtablissementType_participation_nbExprimes").val(0);
			}

			if (nbSiegesPourvus.length == 0) {
				$("#EleEtablissementType_participation_nbSiegesPourvus").val(0);
			}

			// verifier que les champs du tableau de repartition ne sont pas renseignés
			if (nbTotalCandidatsTitulaires == 0 && nbVoixDistribues == 0 && nbSiegesDistribues == 0) {
				// verifier que le nombre de sièges à pourvoir est renseigné
				if (nbSiegesPourvoir.length == 0) {
					alert("Le nombre de sièges à pourvoir doit être renseigné.");
					disabledButtonEnregistrer();
					errorSaisiCarence = true;
				} else {
					return true;
				}

			} else {
				alert("Attention ! En l'absence de candidats, seul le nombre d'inscrits doit être renseigné.");
				disabledButtonEnregistrer();
				errorSaisiCarence = true;
			}
		}
	}
	return false;
}


function removeAttrDisabledOnSubmit() {
	for (var i = 0; i < nb_organisation; i++) {

		// Traitement des données pour les organisations détaillées
		var nb_detail = $("[id^=ligne_"+i+"_]").length;
		for (var j = 0; j < nb_detail; j++) {
			$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSieges').removeAttr('disabled');
			$('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbSiegesSort').removeAttr('disabled');
		}

		$('#EleEtablissementType_resultats_'+i+'_nbCandidats').removeAttr('disabled');
		$('#EleEtablissementType_resultats_'+i+'_nbVoix').removeAttr('disabled');
		$('#EleEtablissementType_resultats_'+i+'_nbSieges').removeAttr('disabled');
		$('#EleEtablissementType_resultats_'+i+'_nbSiegesSort').removeAttr('disabled');

	}

	$('#EleEtablissementType_participation_nbSiegesPourvoir').removeAttr('disabled'); // mantis 121759, mis a false pour pouvoir recuperer la valeur
	$('#EleEtablissementType_participation_nbExprimes').removeAttr('disabled'); // mis a false pour pouvoir recuperer la valeur
}

function checkSuffrageAndCandidatTitulaire() {
	//014E RG_SAISIE_131 : Si le nombre de suffrages est renseigné alors le nombre de candidats titulaires doit obligatoirement etre renseigné
	for (var i=0; i<nb_organisation; i++) {
		var valueNbVoix = Number($('#EleEtablissementType_resultats_'+i+'_nbVoix').val());
		var valueNbCandidats = Number($('#EleEtablissementType_resultats_'+i+'_nbCandidats').val());
		if (valueNbVoix > 0 && valueNbCandidats <= 0 ) {
			alert('Une liste ayant obtenu un nombre de suffrages non nul doit obligatoirement avoir un nombre de candidats titulaires non nul.');
			return false;
		}
		// controle pour les listes détaillées
		var nb_detail = $("[id^=ligne_"+i+"_]").length;
		if (nb_detail > 0){
			for( var j = 0 ; j < nb_detail ; j++){
				var valueNbCandidatsDetaille = Number($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val());
				var nb_voix_detail = Number($('#EleEtablissementType_resultatsDetailles_'+i+'_'+j+'_nbVoix').val());
				if (nb_voix_detail > 0 && valueNbCandidatsDetaille <= 0 ) {
					alert('Une liste ayant obtenu un nombre de suffrages non nul doit obligatoirement avoir un nombre de candidats titulaires non nul.');
					return false;
				}
			}
		}
	}
	return true;
}

function checkCandidatTitulaireAndNbSiegePourvoir() {
	//014E RG_SAISIE_134 RG_SAISIE_135 : Par liste le nombre total de candidats titulaires doit etre  inférieur ou égal au nombre de sièges à pourvoir
	var messageCandidatTitulaire = getMessageCandidatTitulaire;
	var nbSieges = Number($('#EleEtablissementType_participation_nbSiegesPourvoir').val());
	for (var i=0; i<nb_organisation; i++) {
		// controle pour les listes détaillées
		var nb_detail = $("[id^=ligne_"+i+"_]").length;
		if (nb_detail > 0){
			for( var j = 0 ; j < nb_detail ; j++){
				var valueNbCandidatsDetaille = Number($("#EleEtablissementType_resultatsDetailles_"+i+"_"+j+"_nbCandidats").val());
				if (valueNbCandidatsDetaille > nbSieges) {
					alert(messageCandidatTitulaire);
					return false;
				}
			}
		} else{
			var valueNbCandidats = Number($('#EleEtablissementType_resultats_'+i+'_nbCandidats').val());
			if (valueNbCandidats > nbSieges) {
				alert(messageCandidatTitulaire);
				return false;
			}
		}
	}
	return true;
}

function disabledButtonEnregistrer() {
	if (controle_saisie) {
		$('#enregistrerDonnees').prop('disabled', true);
		$('#enregistrerDonnees').css('color','grey');
	}
}