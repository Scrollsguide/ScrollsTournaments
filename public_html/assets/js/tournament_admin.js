$("document").ready(function(){
	$(".jQBracket .score").click(function(){
		// load current scores and player data
		var bracketId = $(this).parents(".teamContainer").attr("data-id");
		var tournamentName = $(this).parents(".jQBracket").attr("data-tournament-name");
		
		loadBracketData(tournamentName, bracketId);
		
		$("#update-score-modal").modal();
	});
});