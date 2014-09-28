$("document").ready(function(){
	$(".jQBracket .score").click(function(e){
		// stop score dialog from showing up
		e.stopPropagation();
		
		// load current scores and player data
		var bracketId = $(this).parents(".teamContainer").attr("data-id");
		var tournamentName = $(this).parents(".jQBracket").attr("data-tournament-name");
		
		loadBracketAdmin(tournamentName, bracketId);
		
		$("#update-score-modal").modal();
	});
});

var loadBracketAdmin = function(tournament, bracketId){
	var scoreModal = $("#update-score-modal");
	scoreModal.modal();
	var modalBody = scoreModal.find(".modal-body");
	modalBody.html(loader());
	$.get("/_admin/" + tournament + "/bracket/" + bracketId, function(output){
		modalBody.html(output);
	});
}