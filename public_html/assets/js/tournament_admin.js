var is_updating = false;

$("document").ready(function () {
	$(".jQBracket .score").click(function (e) {
		// stop score dialog from showing up
		e.stopPropagation();

		// load current scores and player data
		var bracketId = $(this).parents(".teamContainer").attr("data-id");
		var tournamentName = $(this).parents(".jQBracket").attr("data-tournament-name");

		loadBracketAdmin(tournamentName, bracketId);

		$("#update-score-modal").modal();
	});

	$("#update-score-modal #save-bracket").click(function () {
		if (is_updating) {
			return;
		}
		is_updating = true;
		var updateForm = $("#update-score-modal form[name='submit-match-score']");
		if (updateForm.length) {
			updateForm.submit(function (e) {
				e.preventDefault();
				$.ajax({
					type: "POST",
					url: updateForm.attr("action"),
					data: updateForm.serialize(),
					success: function (output) {
						// score saved
						console.log(output);

						is_updating = false;
					}
				});
			}).submit();
		}
	});
});

var loadBracketAdmin = function (tournament, bracketId) {
	var scoreModal = $("#update-score-modal");
	scoreModal.modal();
	var modalBody = scoreModal.find(".modal-body");
	modalBody.html(loader());
	$.get("/_admin/" + tournament + "/bracket/" + bracketId, function (output) {
		modalBody.html(output);
		clickActions();
	});
}

var clickActions = function () {
	$("#update-score-modal .player-details").click(function () {
		var parent = $(this).parent();
		if (parent.hasClass("win")) {
			parent.removeClass("win");
			$("input[name='winner']").val(-1);
		} else {
			$("#update-score-modal .player-result.win").removeClass("win");
			parent.addClass("win");
			$("input[name='winner']").val(parent.attr("data-player-id"));
		}
	});
}