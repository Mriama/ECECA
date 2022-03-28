var chargement = './../../../images/pictos/chargement.gif';

$(document).ready(function() {

    /****** Ensemble d'évènements pour la création et la modification d'un établissement ********/

    /* modification du code mail lorsque l'uai est modifié */
    $("input[id='etabtype_etab.uai']").change(function() {
        genererMailParUai(this.value);
    });

    /* modification de la liste de communes lorsque le code postal est modifié */
    $("input[id='etabtype_etab.commune_codePostal']").change(function() {
        genereSelectCommunesParCp();
    });

    /* modification du code mail lorque le departement est modifié */
    $("select[id='etabtype_etab.commune_departement']").change(function() {
        genereCodeMailParCommune();
    });

    /* modification du code mail lorque la commune est modifié */
    $("select[id='etabtype_commune']").change(function() {
        genereCodeMailParCommune();
    });

    /* flag permettant de savoir si le code mail est modifié par l'utilisateur */
    $('input#etabtype_contact').change(function() {
        $('#contact_change').val('1');
    });

    /* modification de l'affichage dès lors qu'il y a création de commune */
    $('#new_commune').click(function() {
        $("#choix_commune").hide();
        $("#nouvelle_commune").show();
        document.getElementById("etabtype_etab.commune_departement").disabled = false;

        document.getElementById("etabtype_flagAddCommune").value = 'true';
        document.getElementById("etabtype_etab.commune_libelle").value = '';
    });

    /* modification de l'affichage dès lors qu'il y a listage de commune */
    $('#liste_commune').click(function() {
        $("#choix_commune").show();
        $("#nouvelle_commune").hide();
        document.getElementById("etabtype_etab.commune_departement").disabled = true;

        document.getElementById("etabtype_flagAddCommune").value = 'false';
    });

    /* activation des champs inactifs pour l'enregistrement en base */
    $('#form_etab_edit').submit(function() {
        document.getElementById("etabtype_etab.commune_departement").disabled = false;
    });


    /****** Ensemble d'évènements pour la recherche d'établissements ********/

    /* chargement de la liste d'academie, département et commune */
    $('select#form_academie').change(function() {
        document.getElementById("academie_selectionne").value = $('select#form_academie').val();
        document.getElementById("form_departement").options[0] = new Option("Tous", "");
        document.getElementById("form_departement").options.length = 1;
        document.getElementById("departement_selectionne").value = "";
        if (document.getElementById("form_etablissement")) {
            document.getElementById("form_etablissement").options[0] = new Option("Tous", "");
            document.getElementById("form_etablissement").options.length = 1;
        }
        afficheAcademieDepartementCommune('form');
    });

    /* chargement de la liste d'academie, département et commune */
    $('select#form_departement').change(function() {
        document.getElementById("departement_selectionne").value = $('select#form_departement').val();
        document.getElementById("commune_selectionne").value = $('select#form_commune').val();
        if (document.getElementById("form_etablissement")) {
            document.getElementById("form_etablissement").options[0] = new Option("Tous", "");
            document.getElementById("form_etablissement").options.length = 1;
        }
        afficheAcademieDepartementCommune('form');
    });

    /* chargement de la liste des établissements */
    $('select#form_commune').change(function() {
        document.getElementById("departement_selectionne").value = $('select#form_departement').val();
        document.getElementById("commune_selectionne").value = $('select#form_commune').val();
        if (document.getElementById("form_etablissement")) {
            document.getElementById("form_etablissement").options[0] = new Option("Tous", "");
            document.getElementById("form_etablissement").options.length = 1;
        }
        afficheAcademieDepartementCommune('form');
    });

    /* chargement de la liste des établissement en fonction du type */
    $('select#form_typeEtablissement').change(function() {
        document.getElementById("departement_selectionne").value = $('select#form_departement').val();
        document.getElementById("commune_selectionne").value = $('select#form_commune').val();
        if (document.getElementById("form_etablissement")) {
            document.getElementById("form_etablissement").options[0] = new Option("Tous", "");
            document.getElementById("form_etablissement").options.length = 1;
        }
        afficheAcademieDepartementCommune('form');
    });

    /* chargement de la liste des établissement en fonction du type */
    $('select#form_etablissement').change(function() {
        document.getElementById("etablissement_selectionne").value = $('select#form_etablissement').val();
        //alert(document.getElementById("etablissement_selectionne").value);
    });

    /* afficher les champs academie, departement et commune en fonction du click */
    $('input#form_commune_inconnue').click(function() {
        afficherChampsCommune();
    });

    /* Confirmation suppressions d'un établissement */
    $('.supprimer_etab').click(function() {
        return confirm('Confirmer la suppression ? \n(Dans le cas où l\'établissement possède des données il sera uniquement désactivé, sinon il sera supprimé définitivement.)');
    });

    /****** Ensemble d'évènements pour les statistiques ******/

    /* afficher les champs academie, departement et commune en fonction du click */
    $('input#form_choix_etab').click(function() {
        afficherChampsEtablissement('form');
    });

});

/* MODIF
 * Fonction concernant l'afflichage des champs académie et departement
 * Cette fonction permet  de cacher les champs académie et departement du formulaire s'ils sont affichés
 */
function afficherAcademieDepartementCommuneEtab(nomForm) {

    $("#academie").attr("disabled", true);
    $("#departement").attr("disabled", true);
    document.getElementById(nomForm + "_academie").disabled = true;
    document.getElementById(nomForm + "_departement").disabled = true;
    //$("#_parents").hide();
}


/*
 * Fonction concernant la création ou la modification d'établissement
 * Cette fonction permet l'initialisation des différents champs communes et département
 */

function afficherCommuneEtab() {

    $("#choix_commune").hide();
    $("#nouvelle_commune").hide();
    document.getElementById("etabtype_commune").disabled = true;
    document.getElementById("etabtype_etab.commune_departement").disabled = true;
}


/*
 * Fonction concernant la création d'un établissement
 * Cette fonction permet de generer le code mail en fonction de l'uai entré
 */

function genererMailParUai(uai) {

    var email = $("input[id='etabtype_etab.contact']").val();
    if (email == '')
        email = 'ce.numeroUAI@ac-codeAcademie.fr';

    var reg = new RegExp("[@]+", "g");
    var tableau = email.split(reg);
    var debut = tableau[0];
    var fin = tableau[1];

    if (uai != '')
        debut = 'ce.' + $("input[id='etabtype_etab.uai']").val();
    else
        debut = 'ce.numeroUAI';

    // indice permettant de savoir si l'email a été modifié par l'utilisateur
    if ($('#contact_change').val() != '1') {
        $("input[id='etabtype_etab.contact']").val(debut + '@' + fin);
    }

}

/*
 * Fonction concernant la création d'un établissement
 * Cette fonction permet de generer le code mail en fonction du code academie entré
 */

function genererMailParCodeAcademie(codeMail) {

    var email = $("input[id='etabtype_etab.contact']").val();
    if (email == '')
        email = 'ce.numeroUAI@ac-codeAcademie.fr';

    var reg = new RegExp("[@]+", "g");
    var tableau = email.split(reg);
    var debut = tableau[0];

    var fin = 'ac-' + codeMail + '.fr';

    // indice permettant de savoir si l'email a été modifié par l'utilisateur
    if ($('#contact_change').val() != '1') {
        $("input[id='etabtype_etab.contact']").val(debut + '@' + fin);
    }
}

/*
 * Fonction concernant la création et la modification d'un établissement
 * Cette fonction permet de generer la liste de commune en fonction du code postal entré
 * Elle affiche le departement en fonction de la commune
 * Elle modifie le code mail en fonction de la commune
 * Dans le cas où le code postal est inconnu (400) elle positionne l'utilisateur sur nouvelle commune
 */

function genereSelectCommunesParCp() {

    var url_getCommuneParCP = getCommuneParCpAjaxPath;

    hideIndicatorChargement();

    $.post(url_getCommuneParCP, {
        formCodePostal: $("input[id='etabtype_etab.commune_codePostal']").val(),
        formUAI: $("input[id='etabtype_etab.uai']").val(),
        other: "attributes",
        beforeSend: function() {
            showIndicatorChargement();
        }
    }, function(data) {

        if (data.responseCode == 200) {

            var indice = 0;
            for (var i = 0; i < data.communes.length; i++) {

                document.getElementById("etabtype_commune").options[i] = new Option(data.communes[i]['libelle'], data.communes[i]['id']);

                if (data.select_commune == data.communes[i]['id']) {
                    document.getElementById("etabtype_commune").selectedIndex = i;
                }
                indice++;
            }
            document.getElementById('etabtype_commune').options.length = indice;

            for (var i = 0; i < document.getElementById("etabtype_etab.commune_departement").options.length; i++) {
                if (document.getElementById("etabtype_etab.commune_departement").options[i].value == data.communes[0]['numero']) {
                    if (document.getElementById("etabtype_etab.commune_departement").selectedIndex != i) {
                        document.getElementById("etabtype_etab.commune_departement").selectedIndex = i;
                    }
                    break;
                }
            }

            $('#choix_commune').show();
            $('#nouvelle_commune').hide();

            document.getElementById('etabtype_commune').disabled = false;
            document.getElementById('etabtype_etab.commune_departement').disabled = true;

            document.getElementById('etabtype_flagAddCommune').value = 'false';

            genererMailParCodeAcademie(data.communes[0]['code_mail']);
            hideIndicatorChargement();
        }
        else if (data.responseCode == 400) {//bad request
            if ($("input[id='etabtype_etab.commune_codePostal']").val() != '')
                document.getElementById("etabtype_commune").options[0] = new Option('Inconnue', -1);
            else
                document.getElementById("etabtype_commune").options[0] = new Option('Veuillez saisir un code postal', -1);

            document.getElementById("etabtype_commune").options.length = 1;

            $("#choix_commune").hide();
            $("#nouvelle_commune").show();

            document.getElementById("etabtype_commune").disabled = true;
            document.getElementById("etabtype_etab.commune_departement").disabled = false;

            document.getElementById("etabtype_flagAddCommune").value = 'true';
            document.getElementById("etabtype_etab.commune_libelle").value = '';

            genererMailParCodeAcademie('codeAcademie');
            hideIndicatorChargement();
        }
        else {
            alert("Une erreur s\'est produite.");
        }
    });

    return false;
}

/*
 * Fonction concernant la création et la modification d'un établissement
 * Cette fonction permet de generer le code mail en fonction de la commune ou du departement selectionné
 */

function genereCodeMailParCommune() {

    var url_getCodeMailparCommune = getCodeMailParCommuneAjaxPath;

    $.post(url_getCodeMailparCommune, {
        formCommune: $("select[id='etabtype_commune']").val(),
        formDepartement: $("select[id='etabtype_etab.commune_departement']").val(),
        formCodePostal: $("input[id='etabtype_etab.commune_codePostal']").val(),
        other: "attributes"
    }, function(data) {

        if (data.responseCode == 200) {
            var select_departement = document.getElementById("etabtype_etab.commune_departement");
            select_departement.selectedIndex = 0;

            for (var i = 0; i < select_departement.options.length; i++) {
                if (select_departement.options[i].value == data.departement_id) {
                    if (select_departement.selectedIndex != i) {
                        select_departement.selectedIndex = i;
                    }
                    break;
                }
            }

            genererMailParCodeAcademie(data.code_email);

        }
        else if (data.responseCode == 400) {
            var select_departement = document.getElementById("etabtype_etab.commune_departement");
            select_departement.selectedIndex = 0;

            genererMailParCodeAcademie('codeAcademie');

        }
        else {
            alert("Une erreur s\'est produite.");
        }

    }).fail(function() {
    });
}

/*
 * Fonction concernant la recherche d'établissements
 * Cette fonction permet d'initialiser les champs academie, departement et commune
 * si commune inconnu selectionné alors on désactive academie, departement et commune
 * sinon on rend actif academie, departement et commune
 */

function afficherChampsCommune() {

    if (document.getElementById("form_commune_inconnue").checked) {
        document.getElementById("form_academie").selectedIndex = 0;
        document.getElementById("form_departement").selectedIndex = 0;
        document.getElementById("form_academie").disabled = true;
        document.getElementById("form_departement").disabled = true;
        $("#choix_commune").hide();
    } else {
        //document.getElementById("form_academie").disabled = false;
        document.getElementById("form_departement").disabled = false;
        $("#choix_commune").show();
    }
}

/*
 * Fonction concernant la recherche d'établissement
 * Cette fonction permet de generer la liste de commune, de departement et d'academie
 * en fonction de l'academie ou du departement selectionné
 * Dans le cas ou une academie est selectionnée, chargement liste departements et communes
 * Dans le cas ou un département est selectionné, selection academie, chargement liste departements de l'academie et communes du departement 
 */
function afficheAcademieDepartementCommune(nomForm) {

    hideIndicatorChargement();

    if (!nomForm || 0 === nomForm.length) {
        nomForm = 'form';
    }

    var select_academie = document.getElementById(nomForm + "_academie");
    var select_departement = document.getElementById(nomForm + "_departement");
    var select_commune = document.getElementById(nomForm + "_commune");
    var select_etablissement = document.getElementById(nomForm + "_etablissement");

    var url_getAcaDepComParZone = getAcademieDepartementCommuneFromZoneAjaxPath;
    var academie_selectionne = $("#academie_selectionne").val();
    var departement_selectionne = $("#departement_selectionne").val();
    var commune_selectionne = $("#commune_selectionne").val();
    var etablissement_selectionne = $("#etablissement_selectionne").val();

    $.post(url_getAcaDepComParZone, {
        formAcademie: academie_selectionne,
        formDepartement: departement_selectionne,
        formCommune: commune_selectionne,
        formTypeEtab: $('select#'+nomForm+'_typeEtablissement').val(),
        idZoneUser: $('input#id_zone_user').val(),
        other: "attributes",
        beforeSend: function() {
            showIndicatorChargement(nomForm);
        }
    }, function(data) {

        select_departement.disabled = true;
        select_commune.disabled = true;
        select_etablissement.disabled = true;

        select_departement.options.length = 0;
        select_commune.options.length = 0;
        select_etablissement.options.length = 0;

        if (data.responseCode == 200) {

            // Liste des départements
            select_departement.options[0] = new Option('Tous', '');
            if (data.liste_departement.length > 0) {
                select_departement.disabled = false;
                var indice = 1;
                for (var i in data.liste_departement) {
                    select_departement.options[indice] = new Option(data.liste_departement[i]['libelle'], data.liste_departement[i]['numero']);
                    if (data.liste_departement[i]['numero'] == departement_selectionne) {
                        select_departement.options[indice].setAttribute("selected", "selected");
                    }
                    indice++;
                }
                select_departement.options.length = indice;
            }

            // Liste des communes
            select_commune.options[0] = new Option('Toutes', '');
            if (data.liste_commune.length > 0) {
                select_commune.disabled = false;
                select_departement.disabled = false;
                var indice = 1;
                for (var i in data.liste_commune) {
                    var cp = ('' != data.liste_commune[i]['cp']) ? ' (' + data.liste_commune[i]['cp'] + ')' : '' ;
                    select_commune.options[indice] = new Option(data.liste_commune[i]['libelle'] + cp , data.liste_commune[i]['id']);
                    if (data.liste_commune[i]['id'] == commune_selectionne) {
                        select_commune.options[indice].setAttribute("selected", "selected");
                    }
                    indice++;
                }
                select_commune.options.length = indice;
            }

            // Liste des établissements
            select_etablissement.options[0] = new Option('Tous', '');
            if (data.liste_etablissement.length > 0) {
                select_etablissement.disabled = false;
                var indice = 1;
                for (var i in data.liste_etablissement) {
                    var generic = ('' != data.liste_etablissement[i]['libelle']) ? '(' + data.liste_etablissement[i]['uai'] + ')' + ' - ' + data.liste_etablissement[i]['libelle'] : data.liste_etablissement[i]['uai'];
                    select_etablissement.options[indice] = new Option(generic, data.liste_etablissement[i]['uai']);
                    if (data.liste_etablissement[i]['uai'] == etablissement_selectionne) {
                        select_etablissement.options[indice].setAttribute("selected", "selected");
                    }
                    indice++;
                }
                select_etablissement.options.length = indice;
            }

            // Une seule académie disponible
            if(select_academie.options.length == 2){
                select_academie.disabled = true;
                select_academie.options[1].setAttribute("selected", "selected");
            }

            // Un seul département disponible
            if(select_departement.options.length == 2){
                select_departement.disabled = true;
                select_departement.options[1].setAttribute("selected", "selected");
            }

            // Une seule commune disponible
            if(select_commune.options.length == 2){
                select_commune.disabled = true;
                select_commune.options[1].setAttribute("selected", "selected");
            }

            // Un seul établissement disponible
            if(select_etablissement.options.length == 2){
                select_etablissement.disabled = true;
                select_etablissement.options[1].setAttribute("selected", "selected");
            }

            afficherChampsEtablissement(nomForm);
            disableCheckBoxes(nomForm);

            hideIndicatorChargement();
        }
        else if (data.responseCode == 400) {
            alert('Réponse 400');
        }
        else {
            alert("Une erreur s\'est produite.");
        }

    }).fail(function() {});

}

function refreshActivesAcademies(nomForm) {

    hideIndicatorChargement();

    if (!nomForm || 0 === nomForm.length) {
        nomForm = 'form';
    }

    var select_campagne = document.getElementById(nomForm + "_campagne").value;
    var select_academie = document.getElementById(nomForm + "_academie");
    var select_departement = document.getElementById(nomForm + "_departement");
    var select_commune = document.getElementById(nomForm + "_commune");
    var select_etablissement = document.getElementById(nomForm + "_etablissement");

    var url_getAcaDepComParZone = getAcademieDepartementCommuneFromZoneAjaxPath;
    var academie_selectionne = $("#academie_selectionne").val();
    var departement_selectionne = $("#departement_selectionne").val();
    var commune_selectionne = $("#commune_selectionne").val();
    var etablissement_selectionne = $("#etablissement_selectionne").val();
    
    $.post(url_getAcaDepComParZone, {
        formCampagne: select_campagne,
        formAcademie: academie_selectionne,
        formDepartement: departement_selectionne,
        formCommune: commune_selectionne,
        formTypeEtab: $('select#'+nomForm+'_typeEtablissement').val(),
        idZoneUser: $('input#id_zone_user').val(),
        other: "attributes",
        beforeSend: function() {
            showIndicatorChargement(nomForm);
        }
    }, function(data) {

        select_academie.disabled = true;
        select_departement.disabled = true;
        select_commune.disabled = true;
        select_etablissement.disabled = true;

        select_academie.options.length = 0;
        select_departement.options.length = 0;
        select_commune.options.length = 0;
        select_etablissement.options.length = 0;

        if (data.responseCode == 200) {
            // Liste des académies
            select_academie.options[0] = new Option('Toutes', '');
            if (data.liste_academies.length > 0) {
                select_academie.disabled = false;
                var indice = 1;
                for (var i in data.liste_academies) {
                    select_academie.options[indice] = new Option(data.liste_academies[i]['libelle'], data.liste_academies[i]['code']);
                    if (data.liste_academies[i]['code'] == academie_selectionne) {
                        select_academie.options[indice].setAttribute("selected", "selected");
                    }
                    indice++;
                }
                select_academie.options.length = indice;
            }

            // Liste des départements
            select_departement.options[0] = new Option('Tous', '');
            if (data.liste_departement.length > 0) {
                select_departement.disabled = false;
                var indice = 1;
                for (var i in data.liste_departement) {
                    select_departement.options[indice] = new Option(data.liste_departement[i]['libelle'], data.liste_departement[i]['numero']);
                    if (data.liste_departement[i]['numero'] == departement_selectionne) {
                        select_departement.options[indice].setAttribute("selected", "selected");
                    }
                    indice++;
                }
                select_departement.options.length = indice;
            }
          
            // Liste des communes
            select_commune.options[0] = new Option('Toutes', '');
            if (data.liste_commune.length > 0) {
                select_commune.disabled = false;
                select_departement.disabled = false;
                var indice = 1;
                for (var i in data.liste_commune) {
                    var cp = ('' != data.liste_commune[i]['cp']) ? ' (' + data.liste_commune[i]['cp'] + ')' : '' ;
                    select_commune.options[indice] = new Option(data.liste_commune[i]['libelle'] + cp , data.liste_commune[i]['id']);
                    if (data.liste_commune[i]['id'] == commune_selectionne) {
                        select_commune.options[indice].setAttribute("selected", "selected");
                    }
                    indice++;
                }
                select_commune.options.length = indice;
            }

            // Liste des établissements
            select_etablissement.options[0] = new Option('Tous', '');           
            if (data.liste_etablissement.length > 0) {
                select_etablissement.disabled = false;
                var indice = 1;
                for (var i in data.liste_etablissement) {
                    var generic = ('' != data.liste_etablissement[i]['libelle']) ? '(' + data.liste_etablissement[i]['uai'] + ')' + ' - ' + data.liste_etablissement[i]['libelle'] : data.liste_etablissement[i]['uai'];
                    select_etablissement.options[indice] = new Option(generic, data.liste_etablissement[i]['uai']);
                    if (data.liste_etablissement[i]['uai'] == etablissement_selectionne) {
                        select_etablissement.options[indice].setAttribute("selected", "selected");
                    }
                    indice++;
                }
                select_etablissement.options.length = indice;
            }

            // Une seule académie disponible
            if(select_academie.options.length == 2){
                select_academie.disabled = true;
                select_academie.options[1].setAttribute("selected", "selected");
            }

            // Un seul département disponible
            if(select_departement.options.length == 2){
                select_departement.disabled = true;
                select_departement.options[1].setAttribute("selected", "selected");
            }
            
            // Une seule commune disponible
            if(select_commune.options.length == 2){
                select_commune.disabled = true;
                select_commune.options[1].setAttribute("selected", "selected");
            }
            
            // Un seul établissement disponible
            if(select_etablissement.options.length == 2){
                select_etablissement.disabled = true;
                select_etablissement.options[1].setAttribute("selected", "selected");
            }

            afficherChampsEtablissement(nomForm);
        	disableCheckBoxes(nomForm);
            
            hideIndicatorChargement();
        }
        else if (data.responseCode == 400) {
            alert('Réponse 400');
        }
        else {
            alert("Une erreur s\'est produite.");
        }

    }).fail(function() {});

}


/**
 * Permet d'afficher ou de cacher les champs communes et liste des établissements
 * Sur clic de la case à cocher "Recherche par établissement"
 * @param nomForm
 */
function afficherChampsEtablissement(nomForm) {

    if ($("input#" + nomForm + "_choix_etab").prop('checked')) {
        $("#choix_commune").show();
        $("#choix_etablissement").show();
        const select_etablissement = document.getElementById(nomForm + "_etablissement");
        // Si un seul établissement disponible, on le selectionne
        if(select_etablissement.options.length === 2){
            select_etablissement.disabled = true;
            select_etablissement.options[0].selected = false;
            select_etablissement.options[1].selected = true;
        }
    } else {
    	// defect #264
    	$("#"+nomForm+"_commune").val("");
    	$("#"+nomForm+"_etablissement").val("");
        $("#choix_commune").hide();
        $("#choix_etablissement").hide();
    }
}


function ajouterIndicatorChargement(nomForm) {

    if ($('#' + nomForm + '_academie')) {
        var objTo = document.getElementById('academie');
        var img = document.createElement("img");
        img.setAttribute('id', 'ajax-loading-aca');
        img.className = 'chargement';
        img.src = chargement;
        objTo.appendChild(img);
        $('#ajax-loading-aca').hide();
    }
    if ($('#' + nomForm + '_departement')) {
        var objTo = document.getElementById('departement');
        var img = document.createElement("img");
        img.setAttribute('id', 'ajax-loading-dep');
        img.className = 'chargement';
        img.src = chargement;
        objTo.appendChild(img);
    }
    if ($('#' + nomForm + '_commune')) {
        var objTo = document.getElementById('choix_commune');
        var img = document.createElement("img");
        img.setAttribute('id', 'ajax-loading-com');
        img.className = 'chargement';
        img.src = chargement;
        objTo.appendChild(img);
    }
    if ($('#' + nomForm + '_etablissement')) {
        var objTo = document.getElementById('choix_etablissement');
        var img = document.createElement("img");
        img.setAttribute('id', 'ajax-loading-etab');
        img.className = 'chargement';
        img.src = chargement;
        objTo.appendChild(img);
    }
}

function ajouterIndicatorChargementCommune(nomForm) {
    if ($('#' + nomForm + '_etab.commune_codePostal')) {
        var objTo = document.getElementById('cp');
        var img = document.createElement("img");
        img.setAttribute('id', 'ajax-loading-com');
        img.className = 'chargement';
        img.src = chargement;
        objTo.appendChild(img);
        $('#ajax-loading-com').hide();
    }
}

function showIndicatorChargement(nomForm) {
    if ($('select#' + nomForm + '_academie').val() == '') {
        if ($('#ajax-loading-aca'))
            $('#ajax-loading-aca').show();
    }
    if (!$('select#' + nomForm + '_departement').val()) {
        if ($('#ajax-loading-dep'))
            $('#ajax-loading-dep').show();
    }
    if ($('#ajax-loading-com'))
        $('#ajax-loading-com').show();
    if ($('#ajax-loading-etab'))
        $('#ajax-loading-etab').show();
}

function hideIndicatorChargement() {
    if ($('#ajax-loading-aca'))
        $('#ajax-loading-aca').hide();
    if ($('#ajax-loading-dep'))
        $('#ajax-loading-dep').hide();
    if ($('#ajax-loading-com'))
        $('#ajax-loading-com').hide();
    if ($('#ajax-loading-etab'))
        $('#ajax-loading-etab').hide();
}

function formSubmit(nomForm) {
    $('input#' + nomForm + '_choix_etab').removeAttr("disabled");
    $('select#' + nomForm + '_commune').removeAttr("disabled");
    $('select#' + nomForm + '_etablissement').removeAttr("disabled");
    $('select#' + nomForm + '_departement').removeAttr("disabled");
    $('select#' + nomForm + '_academie').removeAttr("disabled");
	$('#'+nomForm+'_etatSaisie_0').removeAttr("disabled");
	$('#'+nomForm+'_etatSaisie_1').removeAttr("disabled");
	$('#'+nomForm+'_etatSaisie_2').removeAttr("disabled");
	if ($("div#stats_campagne > select#"+nomForm+"_campagne").length) {
		$("div#stats_campagne > select#"+nomForm+"_campagne").removeAttr("disabled");
	}
    return true;
}

/**
 * 
 * @param nomForm
 */
function disableCheckBoxes(nomForm){
	
	// mantis 0123687 on décoche les états et on grise les cases
	if($('select#'+nomForm+'_etablissement').val() != ''){
		$('#'+nomForm+'_etatSaisie_0').attr("disabled", true);
		$('#'+nomForm+'_etatSaisie_1').attr("disabled", true);
		$('#'+nomForm+'_etatSaisie_2').attr("disabled", true);
	}else{
		$('#'+nomForm+'_etatSaisie_0').removeAttr("disabled");
		$('#'+nomForm+'_etatSaisie_1').removeAttr("disabled");
		$('#'+nomForm+'_etatSaisie_2').removeAttr("disabled");
	}
	
	// mantis 0123687 si la selection se fait par établissement on doit désactiver la case à cocher de recherche par établissement
	if ($('#isLimitedToEtabs').val() == 1){
		$('#'+nomForm+'_choix_etab').attr("disabled", true);
	}else{
		$('#'+nomForm+'_choix_etab').removeAttr("disabled");
	}
}

function afficheSousTypeElection(nomForm) {
    if ($("select#" + nomForm + "_typeEtablissement").val() == 5) {
        $("#choix_sous_type_election").show();
    } else {
        $("#choix_sous_type_election").hide();
        $("select#" + nomForm + "_sousTypeElection").val('');
    }
}

function afficheTypeEtab(nomForm, codeUrlTypeElect) {
	//BBL defect 249  HPQC pas de 1er degré pour  résultats ASS_ATE et PEE
    if (codeUrlTypeElect == 'ass_ate' || codeUrlTypeElect == 'pee' ) {
    	$('#'+nomForm+"_typeEtablissement option[value='1']").remove();
    }
}