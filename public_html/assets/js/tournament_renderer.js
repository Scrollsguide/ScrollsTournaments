$(document).ready(function(){
	refreshView();
	
	$(".jQBracket .teamContainer").click(function(){
		// load current scores and player data
		var bracketId = $(this).attr("data-id");
		var tournamentName = $(this).parents(".jQBracket").attr("data-tournament-name");
		
		loadBracketData(tournamentName, bracketId);
	});
});

var loader = function(){
	return "<img src='/assets/images/ajax-loader.gif' class='modal-loader' />";
}

var loadBracketData = function(tournament, bracketId){
	var scoreModal = $("#view-score-modal");
	scoreModal.modal();
	var modalBody = scoreModal.find(".modal-body");
	modalBody.html(loader());
	$.get("/_/" + tournament + "/bracket/" + bracketId, function(output){
		modalBody.html(output);
	});
}

var refreshView = function(){
	// connect all connectors
	
	var teamContainers = [];
	$(".bracket-graph-se .teamContainer").each(function(){
		var winner = 'none';
		
		if (!$(this).hasClass("np")){
			var teams = $(this).find(".team");
			var results = [
				teams.eq(0).attr('data-result'),
				teams.eq(1).attr('data-result')
			];
			if (results[0] === results[1]){
			
			} else {
				if (results[0] > results[1]){
					winner = {
						pos: 'top',
						team: teams.eq(0).attr("data-team-id")
					};
					teams.eq(0).addClass("win");
					teams.eq(1).addClass("lose");
				} else {
					winner = {
						pos: 'bottom',
						team: teams.eq(1).attr("data-team-id")
					};
					teams.eq(1).addClass("win");
					teams.eq(0).addClass("lose");					
				}
			}
		}
		
		teamContainers[parseInt($(this).attr("data-id"))] = {
			jObj: $(this),
			winner: winner
		};
		
	});
	
	for (var teamContainerNr in teamContainers){
		var teamContainer = teamContainers[teamContainerNr];
		
		var jObj = teamContainer.jObj;
	
		var id = jObj.attr("data-id");
		var child = jObj.attr('data-child')
		if (child){
			var childObj = teamContainers[child].jObj;
			
			var position = jObj.parent().position();
			var top = position.top + parseInt(jObj.css("top"));
			
			var offset = {
				left: 0,
				right: 0
			};
			
			var childPosition = childObj.parent().position();
			var childTop = childPosition.top + parseInt(childObj.css("top"));
			
			var parents = $(".bracket-graph-se").find("[data-child='" + child + "']");
			
			if (parents.length === 1){ // byes, connect to bottom
			
			} else if (parents.length === 2){
				// console.log("two parents");
			}
			
			var connectors = jObj.find(".connector");
			var connector = connectors.eq(0);
			
			if (teamContainer.winner.pos === 'top'){
				offset.left = -15;
			} else if (teamContainer.winner.pos === 'bottom'){
				offset.left = 15;
			}
			
			if (top > childTop){
				connector.css("border-top", "none");
				connector.css("height", (top - childTop + offset.left + 1) + "px");
				connector.css("bottom", (30 - offset.left) + "px");
				
				connectors.eq(1).css("top", "0px");
			} else {
				connector.css("border-bottom", "none");
				connector.css("height", (childTop - top - offset.left + 1) + "px");
				connector.css("top", (30 + offset.left) + "px");
			}
			connectors.css("display", "block");
		}
	}
	
	$(".bracket-graph-se .jQBracket .team").hover(function(){
		var team = $(this).attr("data-team-id");
		if (team){
			$(".bracket-graph-se .team[data-team-id='" + team + "']").addClass("highlight");
		}
	}, function(){
		$(".highlight").removeClass("highlight");
	});
}