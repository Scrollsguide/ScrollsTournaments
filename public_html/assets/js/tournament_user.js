$(document).ready(function(){
	$("#mydetails button[type='submit']").click(function(e){
		e.preventDefault();

		// validate decks:
		var valid = true;
		$("textarea[name='decks[]'], textarea[name='sideboard']").each(function(){
			try {
				var deckInfo = $.parseJSON($(this).val());

				if (typeof deckInfo.types != 'undefined'){
					var scrolls = deckInfo.types;

					valid &= isValidDeck(scrolls);
				} else {
					valid = false;
				}
			} catch (err){
				valid = false;
			}
		});

		if (valid){
			$("#mydetails form").submit();
		} else {
			alert("One or more decks/sideboard is invalid.");
		}
	});
});