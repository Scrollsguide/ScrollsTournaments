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

	$("#update-score-modal #save-bracket").click(function(){
		var updateForm = $("#update-score-modal form[name='submit-match-score']");
		if (updateForm.length){
			updateForm.submit(function(e){
				e.preventDefault();
				$.ajax({
					type: "POST",
					url: updateForm.attr("action"),
					data: updateForm.serialize(),
					success: function(output){
						console.log(output);
					}
				});
			}).submit();
		}
	});
});

var loadBracketAdmin = function(tournament, bracketId){
	var scoreModal = $("#update-score-modal");
	scoreModal.modal();
	var modalBody = scoreModal.find(".modal-body");
	modalBody.html(loader());
	$.get("/_admin/" + tournament + "/bracket/" + bracketId, function(output){
		modalBody.html(output);
		clickActions();
	});
}

var clickActions = function(){
	$("#update-score-modal .player-result").click(function(){
		console.log("click");
		$("#update-score-modal .player-result.win").removeClass("win");
		$(this).addClass("win");
	});
}