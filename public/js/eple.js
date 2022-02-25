/**
 * Javascript général EPLE
 * Atos - Mars 2013
 */

$(document).ready(function() {
	/* Menu accordéon */
	$('nav#menu ul.pliable').each(function() {
		var etiquette = $(this).children(":first");
		etiquette.attr('tabindex', '0'); // tabulable au clavier
		etiquette.css('cursor', 'pointer');
		etiquette.hover(
			function() {
				$(this).css('background-color', '#c912aa');
			},
			function() {
				$(this).css('background-color', '');
			}
		);
		etiquette.focusin(function() {
			$(this).css('background-color', '#c912aa');
		});
		etiquette.focusout(function() {
			$(this).css('background-color', '');
		});
		
		var sousMenu = $(this).find("ul");
		sousMenu.css('display', 'none');
		
		etiquette.click(function() {
			sousMenu.toggle(50);
		});
		etiquette.keypress(function(e) {
			if (e.which == 13) {
				$(this).click();
		    }
		});
		
	});

	/* Confirmation suppressions */
	$('.supprimer').each(function() {
		$(this).click(function() {
			return confirm('Confirmer la suppression ?');
		})
	});
	
	/* Ajout d'un attribut 'id' sur chaque élément 'option' d'une liste d'académies dans un formulaire SF2 */
	$('select#zonetetabtyp_academie').each(function() {
		var academieOptions = this.getElementsByTagName('option');
		for (var i=0; i<academieOptions.length; i++) {
			if (academieOptions[i].value != "") { academieOptions[i].setAttribute('id', academieOptions[i].value); }
		} 
	})


	// Vérification qu'au moins un champ email est remplit
    var inputs =  $( "#eple_edit_contact_email1, #eple_edit_contact_email2" );
    inputs.each(function() {
    	inputs.not(this).attr('required', $(this).val() == "");
        $( this ).blur(function() {
            inputs.not(this).attr('required', $(this).val() == "");
        });
    });

});