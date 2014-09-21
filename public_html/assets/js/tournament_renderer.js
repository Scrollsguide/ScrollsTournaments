$(document).ready(function(){
	// connect all connectors
	
	var teamContainers = [];
	$(".teamContainer").each(function(){
	
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
			console.log(results);
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
			console.log("actual top of " + id + ": " + top);
			
			var offset = {
				left: 0,
				right: 0
			};
			
			var childPosition = childObj.parent().position();
			var childTop = childPosition.top + parseInt(childObj.css("top"));
			
			var parents = $("#bracket-graph").find("[data-child='" + child + "']");
			
			if (parents.length === 1){ // byes, connect to bottom
			
			} else if (parents.length === 2){
				console.log("two parents");
			}
			
			var connectors = jObj.find(".connector");
			var connector = connectors.eq(0);
			
			if (teamContainer.winner.pos === 'top'){
				offset.left = -13;
			} else if (teamContainer.winner.pos === 'bottom'){
				offset.left = 13;
			}
			
			if (top > childTop){
				console.log(id + " is lower than " + childObj.attr("data-id"));
				connector.css("border-top", "none");
				connector.css("height", (top - childTop + offset.left + 1) + "px");
				connector.css("bottom", (26 - offset.left) + "px");
				
				connectors.eq(1).css("top", "0px");
			} else {
				console.log(id + " is higher than " + childObj.attr("data-id"));
				connector.css("border-bottom", "none");
				connector.css("height", (childTop - top - offset.left + 1) + "px");
				connector.css("top", (26 + offset.left) + "px");
			}
			connectors.css("display", "block");
		}
	};
});