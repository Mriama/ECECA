$(document).ready(function(){

	var url_getAllAcademie = getAllAcademieAjaxPath;
	var url_getDepartementsByCodeAcademie = getDepartementsByCodeAcademieAjaxPath;
	var url_getEtablissementsByZoneAndUaiOrLibelle = getEtablissementsByZoneAndUaiOrLibelleAjaxPath;
	var url_getEtablissementByUaiOrLibelle = getEtablissementByUaiOrLibelleAjaxPath;
	var typeElect = getTypeElect;

	var arrayAcademie = new Array();
	var arrayAcademieLibelle = new Array();

	var arrayAllDepartement = new Array();
	var arrayAllDepartementLibelle = new Array();
	var arrayDepartement = new Array();
	var arrayDepartementLibelle = new Array();

	var codeAcademie;
	var numeroDepartement;
	var uaiEtablissement;

	var libelleAcademie;
	var libelleDepartement;

	// chargment de toutes les academies
	$.post(url_getAllAcademie, { typeElect: typeElect }, function(data) {
		if (data.responseCode == 200) {
			var objAcademie;
			for (var i = 0; i < data.academies.length; i++) {
				objAcademie = data.academies[i];
				arrayAcademie.push(objAcademie);
				if(objAcademie.display === true) {
					arrayAcademieLibelle.push(objAcademie.libelle);
				}
			}

			// autocomplete sur le champ contacts en academie
			$("#ececa_saisie_courriel_libre_contacts_academie").autocomplete({
				source: arrayAcademieLibelle,
				change: function (event, ui) {
					// reinitialisation des champs departement et etablissement
					$("#ececa_saisie_courriel_libre_contacts_departementaux").val("");
					$("#ececa_saisie_courriel_libre_contacts_etablissements").val("");
					arrayDepartement = [];
					arrayDepartementLibelle = [];

					// recuperation du code de l'académie saisie
					codeAcademie = findCodeAcademieByLibelle(arrayAcademie, $("#ececa_saisie_courriel_libre_contacts_academie").val());

					// reinitialisation des champs departement et etablissement
					$("#ececa_saisie_courriel_libre_contacts_departementaux").val("");
					$("#ececa_saisie_courriel_libre_contacts_etablissements").val("");
					arrayDepartement = [];
					arrayDepartementLibelle = [];

					// chargement des departements en fonction de l'académie saisie
					$.post(url_getDepartementsByCodeAcademie,
						{ academie_code:codeAcademie },
						function(data) {
							if (data.responseCode == 200) {
								var objDepartement;
								for (var i = 0; i < data.departements.length; i++) {
									objDepartement = data.departements[i];
									arrayDepartement.push(objDepartement);
									arrayDepartementLibelle.push(objDepartement.libelle);
								}

								// autocomplete sur le champ contacts departementaux
								$("#ececa_saisie_courriel_libre_contacts_departementaux").autocomplete({
									source: arrayDepartementLibelle
								});
							}
						});

					// chargement des etablissements en fonction de l'academie saisie et des 3 premiers caracteres saisis
					$("#ececa_saisie_courriel_libre_contacts_etablissements").autocomplete({
						minLength: 3,
						source : function(requete, reponse) {
							$.ajax({
								data : 'academie_code='+codeAcademie+'&uai_or_libelle='+$("#ececa_saisie_courriel_libre_contacts_etablissements").val(),
								type: 'POST',
								url : url_getEtablissementsByZoneAndUaiOrLibelle,
								dataType : 'json',
								success : function(data) {
									reponse($.map(data.etablissements, function(item) {
										return item.uai + ' - ' + item.libelle;

									}));
								}
							});
						}
					});
				}
			});
		}
	});

	// chargement de tous les departements
	$.post(url_getDepartementsByCodeAcademie,
		{ academie_code:"" },
		function(data) {
			if (data.responseCode == 200) {
				var objDepartement;
				for (var i = 0; i < data.departements.length; i++) {
					objDepartement = data.departements[i];
					arrayAllDepartement.push(objDepartement);
					arrayAllDepartementLibelle.push(objDepartement.libelle);
				}

				// autocomplete sur le champ contacts departementaux
				$("#ececa_saisie_courriel_libre_contacts_departementaux").autocomplete({
					source: arrayAllDepartementLibelle,
					change: function (event, ui) {
						// reinitialisation du champ etablissement
						$("#ececa_saisie_courriel_libre_contacts_etablissements").val("");

						// recuperation du numero departement saisi
						numeroDepartement = findNumeroDepartementByLibelle(arrayAllDepartement, $("#ececa_saisie_courriel_libre_contacts_departementaux").val());

						// si saisie directe du departement i.e academie = '' => renseigner l'academie
						if ($("#ececa_saisie_courriel_libre_contacts_academie").val() == "") {
							codeAcademie = findCodeAcademieByNumeroDepartement(arrayAllDepartement, numeroDepartement);
							$("#ececa_saisie_courriel_libre_contacts_academie").val(findLibelleAcademieByCode(arrayAcademie, codeAcademie)).trigger('change');
							$("#ececa_saisie_courriel_libre_contacts_departementaux").val(findLibelleDepartementByNumero(arrayAllDepartement, numeroDepartement));
						} else {
							// recuperation du code de l'académie saisie
							codeAcademie = findCodeAcademieByLibelle(arrayAcademie, $("#ececa_saisie_courriel_libre_contacts_academie").val());
						}

						// reinitialisation du champ etablissement
						$("#ececa_saisie_courriel_libre_contacts_etablissements").val("");

						// chargement des etablissements en fonction du departement saisi et des 3 premiers caracteres saisis
						$("#ececa_saisie_courriel_libre_contacts_etablissements").autocomplete({
							minLength: 3,
							source : function(requete, reponse) {
								$.ajax({
									data : 'academie_code='+codeAcademie+'&departement_numero='+numeroDepartement+'&uai_or_libelle='+$("#ececa_saisie_courriel_libre_contacts_etablissements").val(),
									type: 'POST',
									url : url_getEtablissementsByZoneAndUaiOrLibelle,
									dataType : 'json',
									success : function(data) {
										reponse($.map(data.etablissements, function(item) {
											return item.uai + ' - ' + item.libelle;

										}));
									}
								});
							}
						});
					}
				});
			}
		});

	// autocomplete sur etablissement, actif a partir de 3 caracteres saisis
	$("#ececa_saisie_courriel_libre_contacts_etablissements").autocomplete({
		minLength: 3,
		source : function(requete, reponse) {
			$.ajax({
				data : 'uai_or_libelle='+$("#ececa_saisie_courriel_libre_contacts_etablissements").val(),
				type: 'POST',
				url : url_getEtablissementByUaiOrLibelle,
				dataType : 'json',
				success : function(data) {
					reponse($.map(data.etablissements, function(item) {
						return item.uai + ' - ' + item.libelle;

					}));
				}
			});
		},
		change : function (event, ui) {
			uaiEtablissement = findUaiEtablissementByUaiLibelle($("#ececa_saisie_courriel_libre_contacts_etablissements").val());
			if (uaiEtablissement != "") {
				$.post(url_getEtablissementByUaiOrLibelle,
					{ uai_or_libelle:uaiEtablissement.toUpperCase()	},
					function(data) {
						if (data.responseCode == 200) {
							// check si l'etablissement saisi existe
							if (data.etablissements.length > 0) {
								// si saisie directe de l'etablissement i.e academie = '' et/ou departement = '' => renseigner l'academie et/ou le departement
								if ($("#ececa_saisie_courriel_libre_contacts_departementaux").val() == "" || $("#ececa_saisie_courriel_libre_contacts_academie").val() == "") {
									var objEtablissement = data.etablissements[0];
									uaiEtablissement = objEtablissement.uai;
									numeroDepartement = objEtablissement.departement
									codeAcademie = findCodeAcademieByNumeroDepartement(arrayAllDepartement, numeroDepartement);

									$("#ececa_saisie_courriel_libre_contacts_departementaux").val(findLibelleDepartementByNumero(arrayAllDepartement, numeroDepartement)).trigger('change');
									$("#ececa_saisie_courriel_libre_contacts_academie").val(findLibelleAcademieByCode(arrayAcademie, codeAcademie)).trigger('change');

									$("#ececa_saisie_courriel_libre_contacts_departementaux").val(findLibelleDepartementByNumero(arrayAllDepartement, numeroDepartement));
									$("#ececa_saisie_courriel_libre_contacts_etablissements").val(uaiEtablissement + " - " + objEtablissement.libelle);
								}
							} else {
								alert(alert003);
							}
						}
					});
			}  else {
				alert(alert003);
			}
		}
	});

	$("#form_envoi_courriel_libre").submit(function() {
		libelleAcademie = $.trim($("#ececa_saisie_courriel_libre_contacts_academie").val());
		libelleDepartement = $.trim($("#ececa_saisie_courriel_libre_contacts_departementaux").val());
		libelleEtablissement = $.trim($("#ececa_saisie_courriel_libre_contacts_etablissements").val());

		codeAcademie = findCodeAcademieByLibelle(arrayAcademie, libelleAcademie);
		numeroDepartement = findNumeroDepartementByLibelle(arrayAllDepartement, libelleDepartement);

		$("#ececa_saisie_courriel_libre_code_academie").val(codeAcademie);
		$("#ececa_saisie_courriel_libre_numero_departement").val(numeroDepartement);

		// Test qu'il existe un destinataire - connexion dgesco
		if (libelleAcademie == ""
			&& libelleDepartement == ""
			&& libelleEtablissement == "") {
			alert(alert004);
			return false;
		}

		// connexion academie -> #bloc_contact_academie is hidden
		// Test qu'il existe un destinataire -> libelleDepartement != "" ou libelleEtablissement != ""
		if ($('#bloc_contact_academie').css('display') == 'none') {
			$("#ececa_saisie_courriel_libre_code_academie").val("");
			if (libelleDepartement == "" && libelleEtablissement == "") {
				alert(alert004);
				return false;
			}
		}

		// connexion dsden -> #bloc_contact_academie is hidden et bloc_contact_departement is hidden
		// Test qu'il existe un destinataire -> libelleEtablissement != ""
		if ($('#bloc_contact_academie').css('display') == 'none' && $('#bloc_contact_departement').css('display') == 'none') {
			$("#ececa_saisie_courriel_libre_code_academie").val("");
			$("#ececa_saisie_courriel_libre_numero_departement").val("");
			if (libelleEtablissement == "") {
				alert(alert004);
				return false;
			}
		}

		// Si academie seulement saisie => test existance academie
		if (libelleAcademie != ""
			&& libelleDepartement == ""
			&& libelleEtablissement == "") {
			if (codeAcademie == "") {
				alert(alert005);
				return false;
			}
		}

		// Si academie et departement saisis => test si l'academie correspond au departement
		if (libelleAcademie != ""
			&& libelleDepartement != ""
			&& libelleEtablissement == "") {
			if (codeAcademie == "") {
				alert(alert005);
				return false;
			}
			if (numeroDepartement == "") {
				alert(alert006);
				return false;
			}

			const codeAcademieOrigin = findCodeAcademieByNumeroDepartement(arrayAllDepartement, numeroDepartement);
			const codeAcademieFusion = findCodeAcademieFusion(arrayAcademie, codeAcademieOrigin);
			if (codeAcademie !== codeAcademieOrigin && codeAcademie !== codeAcademieFusion) {
				alert(alert007);
				return false;
			}
		}

		// Si academie, departement, etablissement saisis => test si l'academie et le departement correspondent a l'etablissement
		if (libelleAcademie != ""
			&& libelleDepartement != ""
			&& libelleEtablissement != "") {
			if (codeAcademie == "") {
				alert(alert005);
				return false;
			}
			if (numeroDepartement == "") {
				alert(alert006);
				return false;
			}

			const codeAcademieOrigin = findCodeAcademieByNumeroDepartement(arrayAllDepartement, numeroDepartement);
			const codeAcademieFusion = findCodeAcademieFusion(arrayAcademie, codeAcademieOrigin);
			if (codeAcademie !== codeAcademieOrigin && codeAcademie !== codeAcademieFusion) {
				alert(alert007);
				return false;
			}

			var uai_or_libelle;
			var arrayLibelleEtablissement = libelleEtablissement.split(" - ");
			if (arrayLibelleEtablissement.length >= 2) { // le libelle etablissement est constitué de uai - libelle
				uai_or_libelle = arrayLibelleEtablissement[0];
				$("#ececa_saisie_courriel_libre_uai_etablissement").val(uai_or_libelle);
			} else {
				uai_or_libelle = libelleEtablissement;
			}

		}

		// Si etablissement saisi et il manque soit l'uai soit l'academie soit le departement renseigné automatiquement => erreur etablissement
		if ((libelleAcademie == ""
			|| libelleDepartement == "")
			&& libelleEtablissement != "") {
			var arrayLibelleEtab = libelleEtablissement.split(" - ");
			//anomalie 128795 etab invalide
			if (arrayLibelleEtab.length >= 2) { // le libelle etablissement est constitué de uai - libelle
				uai_or_libelle = arrayLibelleEtab[0];
				$("#ececa_saisie_courriel_libre_uai_etablissement").val(uai_or_libelle);
			}
			if (arrayLibelleEtab.length == 1 || $.trim($("#ececa_saisie_courriel_libre_uai_etablissement").val()) == "") { // le libelle etablissement n'est pas constitué de uai - libelle
				alert(alert003);
				return false;
			}
		}
	});

});

/**
 * findCodeAcademieByLibelle recherche le code d'une académie selectionnée dans le tableau des academies préalablement chargé par ajax
 * @param arrayAcademie
 * @param libelle
 * @returns {String}
 */
function findCodeAcademieByLibelle(arrayAcademie, libelle) {
	var retour = "";
	for (var i = 0; i < arrayAcademie.length; i++) {
		if (arrayAcademie[i].libelle == libelle) {
			retour = arrayAcademie[i].code;
			break;
		}
	}
	return retour;
}

/**
 * findLibelleAcademieByCode recherche le libelle d'une académie selectionnée dans le tableau des academies préalablement chargé par ajax
 * @param arrayAcademie
 * @param code
 * @returns {String}
 */
function findLibelleAcademieByCode(arrayAcademie, code) {
	let retour = "";
	const findIndex = arrayAcademie.findIndex(aca => (aca.code === code.toUpperCase()));
	if (findIndex !== -1) {
		if(arrayAcademie[findIndex].codeFusion !== null && !arrayAcademie[findIndex].display) {
			const findIndexFusion = arrayAcademie.findIndex(aca => (aca.code === arrayAcademie[findIndex].codeFusion));
			if (findIndexFusion !== -1) {
				retour = arrayAcademie[findIndexFusion].libelle;
			}
		} else {
			retour = arrayAcademie[findIndex].libelle;
		}
	}
	return retour;
}

/**
 * findAcademieFusion recherche le libelle d'une académie fusionnée selectionnée dans le tableau des academies préalablement chargé par ajax
 * @param arrayAcademie
 * @param code
 * @returns {String}
 */
function findCodeAcademieFusion(arrayAcademie, code) {
	let retour = "";
	const findIndex = arrayAcademie.findIndex(aca => (aca.code === code.toUpperCase()));
	if (findIndex !== -1) {
		retour = arrayAcademie[findIndex].codeFusion;
	}
	return retour;
}

/**
 * findNumeroDepartementByLibelle recherche le numero d'un département selectionné dans le tableau des départements préalablement chargé par ajax
 * @param arrayDepartement
 * @param libelle
 * @returns {String}
 */
function findNumeroDepartementByLibelle(arrayDepartement, libelle) {
	var retour = "";
	for (var i = 0; i < arrayDepartement.length; i++) {
		if (arrayDepartement[i].libelle == libelle) {
			retour = arrayDepartement[i].numero;
			break;
		}
	}
	return retour;
}

/**
 * findLibelleDepartementByNumero recherche le libelle d'un département selectionné dans le tableau des départements préalablement chargé par ajax
 * @param arrayDepartement
 * @param numero
 * @returns {String}
 */
function findLibelleDepartementByNumero(arrayDepartement, numero) {
	var retour = "";
	for (var i = 0; i < arrayDepartement.length; i++) {
		if (arrayDepartement[i].numero == numero) {
			retour = arrayDepartement[i].libelle;
			break;
		}
	}
	return retour;
}

/**
 * findCodeAcademieByNumeroDepartement recherche le code d'une academie a partir d'un numero de departement
 * @param arrayDepartement
 * @param numero
 * @returns {String}
 */
function findCodeAcademieByNumeroDepartement(arrayDepartement, numero) {
	var retour = "";
	for (var i = 0; i < arrayDepartement.length; i++) {
		if (arrayDepartement[i].numero == numero) {
			retour = arrayDepartement[i].academie;
			break;
		}
	}
	return retour;
}

/**
 * findUaiEtablissementByUaiLibelle recherche l'UAI d'un établissement dans le libelle composé de l'uai - libelle
 * @param arrayEtablissement
 * @param libelle
 * @returns {String}
 */
function findUaiEtablissementByUaiLibelle(uaiLibelle) {
	var arrayLibelleEtablissement = uaiLibelle.split(" - ");
	if (arrayLibelleEtablissement.length >= 2) { // le libelle etablissement est constitué de uai - libelle
		return arrayLibelleEtablissement[0];
	}
	return "";
}