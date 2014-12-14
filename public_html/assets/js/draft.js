var socket;
var isConnected = false;
var pingInterval;
var deck = [];
var scrolls = [];
var isSaving = false;
var slider;
var canPick = false;
var deckSlider;
var finished = false;
var timer;
var timeoutSeconds;

var resources = [ "Growth", "Order", "Energy", "Decay" ];

$(document).ready(function(){
	/*
	$("#right_wrap").append($("<div/>", {
		id: "hoverbox",
		class: "block",
		html: "<div class='blockheader'>Scroll</div><div class='subblock'></div>"
	}));
	*/

	slider = $("#rightblockslider");
	log("Connecting...");
	$.get("http://a.scrollsguide.com/scrolls", function(output){
		var cardTypes = output.data;
		for (var i = 0; i < cardTypes.length; i++){
			var scroll = cardTypes[i];
			for (var j = 0; j < resources.length; j++){
				var key = "cost" + resources[j].toLowerCase();
				if (scroll[key] > 0){
					scroll.costType = resources[j];
					break;
				}
			}
			scrolls[i] = scroll;
		}
		connect();
	}, "JSON");
	
	$("input[name='gamename']").val(username + "'s game");
	
	$("input[name='chat-input']").keydown(function(e){
		if (e.keyCode == 13 && isConnected){
			var msg = $(this).val();
			if (msg != ""){
				$(this).val("");
				chat(msg);
			}
		}
	});
	
	$("#creategame").click(function(){
		showBlock("#creategameoptions");
	});
	
	$("#creategame_final").click(function(){
		var name = $("input[name='gamename']").val();
		var packs = parseInt($("input[name='numpacks']").val());
		var maxplayers = $("input[name='maxplayers']").val();
		var pw = $("input[name='password']").val();
		var bots = $("input[name='botcount']").val();
		var timeout = $("input[name='timeout']").val();
		var cGame = { msg: "cr", n: name, p: packs, pw: pw, mp: maxplayers, id: makeid(), b: bots, t: timeout };
		send(JSON.stringify(cGame));
	});
	
	$("#cancelcreate").click(function(){
		showBlock("#gamelistwrap");
	});
	$("#cancelpwd").click(function(){
		showBlock("#gamelistwrap");
	});
	
	$("#partgame").click(function(){
		send("{\"msg\":\"pg\"}");
	});
	
	$("#startgame").click(function(){
		var startGame = { msg: "sg" };
		send(JSON.stringify(startGame));
	});
	
	$(".game").live('click', function(){
		// get game name
		var name = $(this).attr("id").match(/game_([a-zA-Z0-9]+)/)[1];		
		joinGame(name, "");
	});
	
	$("#joinpw").click(function(){
		joinGame($("input[name='gameid']").val(), $("input[name='passwd']").val());
	});
	
	$(".otherpicks").live('click', function(){
		var name = $(this).attr("id").match(/picks_([a-zA-Z0-9]+)/)[1];
		if ($("#deckslider").find($("#deckslider_" + name)).length == 1){
			deckSlideTo("#deckslider_" + name);
		} else {
			send(JSON.stringify({ msg: "sp", d: name }));
		}
	});
	
	$(".drawsimgwrap").live('mouseenter', function(){
		infobox($(this).attr("id").split("_")[1]);
	});
	
	$("#sendtodeckbuilder").click(function(){
		if (isSaving){
			return;
		}
		isSaving = true;
		
		$(this).html("Saving...");
		
		var out = [];
		var doubles = 0;
		var columns = Math.floor(($(window).width() - 460) / 150);
		
		for (var i = 0; i < deck.length; i++){
			var scroll = deck[i];
			
			var dbZ = 10;
			
			for (var j = 0; j < scroll.count; j++) {
				if (j > 0){
					doubles ++;
				}
				dbX = 250 + ((out.length - doubles) % columns) * 150; // 250 = start, 150 = image width and some padding
				dbY = 60 + Math.floor((out.length - doubles) / columns) * 150 - (15 * j);
				dbZ--;
				
				var outObj = {
					"id":	scroll.id,
					"x":	dbX,
					"y":	dbY,
					"z":	dbZ
				};
				
				out.push(outObj);
			}
		}
		
		var deckName = "Draft deck " + (Math.random().toString(36).substring(2, 7));
		$.post("/deckbuilder/p/savedeck.php", { name: deckName, scrolls: out }, function(output){
			isSaving = false;
			$("#stdbwrap").html("<a href='http://www.scrollsguide.com/deckbuilder/?d=" + output + "' target='_blank'>Click here to view your deck</a>");
		});
	});
});

function joinGame(gameID, password){
	var joinGame = { msg: "jg", d: gameID, pw: password };
	send(JSON.stringify(joinGame));
}

function onConnected(){
	// connected, now login
	isConnected = true;
	var loginMsg = { msg: "login", d: username };
	send(JSON.stringify(loginMsg));
}

function onMessage(msg){
	console.log("Received: " + msg);
	
	try {
		var msg = JSON.parse(msg);
		
		if (msg.msg == "login"){ // login
			if (msg.s){
				isConnected = true;
				
				deckSlider = $("<div/>", { id: "deckslider_" + msg.d, class: "subblock" });
				$("#deckslider").append(deckSlider);
				// start pinging
				pingInterval = setInterval(function(){ ping(); }, 5000);
				// we're now logged in, get online player list and game list
				getPlayerList();
				getGameList();
			} else { // failed to login
				log("Failed to login: " + msg.d + " is already logged in");
				socket.close();
			}
		} else if (msg.msg == "plist"){ // got player list
			parsePlayerList(msg.data, (msg.f == "g"));
		} else if (msg.msg == "glist"){
			parseGameList(msg.data);
		} else if (msg.msg == "c"){ // chat message
			log(msg.d, msg.u);
		} else if (msg.msg == "join"){ // player joined the lobby
			log(msg.d + " has joined " + msg.f + ".");
			if (msg.d != username){
				getPlayerList();
			}
		} else if (msg.msg == "part"){ // player left the lobby
			log(msg.d + " has left " + msg.f + ".");
			getPlayerList();
		} else if (msg.msg == "pg"){ // left a game
			showBlock("#gamelistwrap");
			title("Lobby");
		} else if (msg.msg == "jg"){ // joined a game
			title(msg.d);
			
			$("#gameinfo").html(msg.p + " packs, timeout " + msg.t + " seconds");
			
			$("#ingametitle").html("Players in " + msg.d);
			
			if (msg.cr){
				$("#startgame").show();
				$("#ingameplayerwrap .rightbtn_1").removeClass("rightbtn_1").addClass("rightbtn_2");
			} else {
				$("#startgame").hide();
			}
			
			showBlock("#ingameplayerwrap");
			
			log("Joined " + msg.d + ".");
		} else if (msg.msg == "cdc"){
			log("Game creator has disconnected, playtime's over :(");
			showBlock("#gamelistwrap");
			title("Lobby");
			$("#drawswrapper").slideUp(300);
		} else if (msg.msg == "sg"){ // game is starting
			finished = false;
			log("Game is starting!");
			
			deck = [];
			$("#ingameplayerwrap .subblock:eq(0)").animate({ height: "214px"}, 300);
			$("#ingameplayer").animate({ height: "168px" }, 300);
			
			$("#hoverbox").slideDown(300);
			$("#drawswrapper").slideDown(300);
		} else if (msg.msg == "pp"){ // player picked a scroll
			var div = $("#ingameplayer #player_" + msg.d);
			div.html("* " + div.html());
		} else if (msg.msg == "pl"){ // new turn, this is the pack list
			subtitle("Scrolls Draft Round " + msg.r + ", Pack " + msg.p);
			// reset everyone's indicator
			resetPickIndicator();
			var pack = msg.d;
			$("#drawscontent").attr("id", "");
			
			$("#slider").append($("<div/>", { class: "subblock", id: "drawscontent" }));
			for (var i = 0; i < pack.length; i++){
				var scroll = getScrollById(pack[i]);
				
				var divWrap = createDiv(scroll, true);
				$("#drawscontent").append($("<div />", {
					class: "drawsimgwrap",
					id: "scroll_" + scroll.id,
					html: divWrap,
					click: function(){
						// user selected a scroll
						if (canPick){
							$(".picked").removeClass("picked");
							$(this).addClass("picked").fadeTo(300, 1);
							var id = $(this).attr("id").split("_")[1];
							
							$("#drawscontent .drawsimgwrap:not(.picked)").fadeTo(300, 0.7);
							
							send("{\"msg\":\"ps\",\"d\":" + id + "}");
						}
					},
				}).append($("<div />", {
					class: "drawscounter",
					html: getScrollCount(scroll.id)
				})));
			}
			
			$("#slider").animate({ marginLeft: "-600px" }, 300, function(){
				$("#slider div:eq(0)").remove();
				$("#slider").css("margin-left", 0);
				
				// enable canPick just now, or people would be able to rapidly click scrolls from previous batch
				canPick = true;
				// start timer			
				timeoutSeconds = msg.t;
				$("#timer").html(timeoutSeconds);
				timer = setInterval(function(){ timeout(); }, 1000);
			});
		} else if (msg.msg == "ps"){ // this is the scroll that the player picked and that's added to deck
			clearInterval(timer);
			canPick = false;
			var scroll = getScrollById(msg.d);
			
			// add scroll to deck
			var inDeck = false;
			for (var i = 0; i < deck.length; i++){
				if (deck[i].id == scroll.id && !inDeck){
					// scroll already exists in deck, increase count
					inDeck = true;
					deck[i].count++;
					break;
				}
			}
			if (!inDeck){
				// scroll is not in deck yet, set count to 1
				deck.push({ id: scroll.id, count: 1 });
			}
			
			if (deckSlider.find("#scroll_" + scroll.id).length > 0){
				var counter = deckSlider.find("#scroll_" + scroll.id).find(".drawscounter");
				counter.html(parseInt(counter.html()) + 1);
			} else {
				var divWrap = createDiv(scroll, false);
				
				deckSlider.append($("<div />", {
					class: "drawsimgwrap",
					id: "scroll_" + scroll.id,
					html: divWrap
				}).append($("<div />", {
					class: "drawscounter",
					html: "1"
				})));
			
				if (!$("#yourpicks").is(":visible")){
					$("#yourpicks").slideDown(300);
				}
			}
		} else if (msg.msg == "fr"){ // finished a round
			var total = msg.m;
			direction(msg.d);
			
			log("Finished round " + msg.f + " of " + total + ", starting next round.");
		} else if (msg.msg == "fg"){ // finish game
			clearInterval(timer);
			finished = true;
			$("#stdbwrap").show();
			$("#otherplayerpicks").slideDown(300);
			$("#drawswrapper").slideUp(300);
		} else if (msg.msg == "hp"){ // password protected game
			$("input[name='gameid']").val(msg.d);
			showBlock("#passwordprompt");
		} else if (msg.msg == "wp"){ // wrong password for game
			$("input[name='passwd']").val("");
			log("Wrong password, try again.");
		} else if (msg.msg == "sp"){ // view the deck from another player
			var playerName = msg.p;
			
			var id = "deckslider_" + playerName;
			if ($("#deckslider").find("#" + id).length == 0){
				var newDeckSlider = $("<div/>", { id: id, class: "subblock" });
				$("#deckslider").append(newDeckSlider);
				
				var content = msg.d;
				for (var i = 0; i < content.length; i++){
					var scroll = getScrollById(content[i].id);
					var divWrap = createDiv(scroll, false);
					
					newDeckSlider.append($("<div />", {
						class: "drawsimgwrap",
						id: "scroll_" + scroll.id,
						html: divWrap
					}).append($("<div />", {
						class: "drawscounter",
						html: content[i].c
					})));
				}
				
				$("#deckslider").css("width", $("#deckslider").children().length * 600);
			}
			deckSlideTo("#" + id);
		} else if (msg.msg == "e"){ // error
			log("Error: " + msg.d);
		}
	} catch (exception){
		console.log(exception);
	}
}

function timeout(){
	timeoutSeconds--;
	$("#timer").html(timeoutSeconds);
	
	if (timeoutSeconds == 0){
		// pick first scroll from list
		if (canPick){
			canPick = false; // here, this should be set, or else the player
			// will be able to click just the moment this timer goes off
			// and the ps message won't have arrived at the client yet.
			var id = $("#drawscontent .drawsimgwrap:eq(0)").attr("id").split("_")[1];
			
			send("{\"msg\":\"ps\",\"d\":" + id + "}");
		}
	}
}

function createDiv(scroll, h){
	var c = "drawsimg";
	if (h){ c += " h"; }
	
	return $("<img />", {
		src: "/app/low_res/" + scroll.image + ".png",
		title: scroll.name,
		class: c
	});
}

function resetPickIndicator(){
	$("#ingameplayer .player").each(function(){
		var deze = $(this);
		deze.html(deze.html().replace("* ", ""));
	});
}

function getGameList(){
	var out = { msg: "glist" };
	send(JSON.stringify(out));
}

function parseGameList(list){
	$("#gamelist").empty();
	for (var i = 0; i < list.length; i++){
		addGame(list[i]);
	}
}

function addGame(game){
	$("#gamelist").append("<div id='game_" + game.id + "' class='game'>" + game.n + " (" + game.p + "/" + game.m + ")</div>");	
}

function getPlayerList(){
	var out = { msg: "plist" };
	send(JSON.stringify(out));
}

function parsePlayerList(list, gamelist){
	var div;
	if (gamelist && !finished){
		var otherPlayer = $("#otherplayerpicks");
		otherPlayer.html("See the picks of: ");
		div = $("#ingameplayer");
		div.empty();
		for (var i = 0; i < list.length; i++){
			var playerName = list[i];
			div.append("<div class='player' id='player_" + playerName + "'>" + playerName + "</div>");	
			otherPlayer.append($("<button/>", {
				class: "btnmain otherpicks",
				id: "picks_" + playerName,
				html: playerName
			}));
		}
	} else {
		div = $("#chat-members");	
		div.empty();
		for (var i = 0; i < list.length; i++){
			var playerName = list[i];
			div.append("<div class='chat-player'>" + playerName + "</div>");
		}
	}
}

function chat(msg){
	var m = { msg: "c", d: msg };
	send(JSON.stringify(m));
}

function send(msg){
	if (isConnected){
		console.log("Sending: " + msg);
		socket.send(msg);
	}
}

function connect() {
	if (typeof socket != 'undefined'){
		log("Already connected.");
		return;
	}
	try {		
		var host = "ws://draftserver.scrollsguide.com:9873/";
		if (window.location.hash == "#debug"){
			console.log("Connecting to localhost...");
			host = "ws://localhost:9873/";
		}
		socket = new WebSocket(host);

		socket.onopen = function(){
			onConnected(); // isConnected = true after logging in
			log("Connected.");
		}

		socket.onmessage = function(msg){
			onMessage(msg.data);
		}

		socket.onclose = function(){
			isConnected = false;
			log("Disconnected from server.");
			clearInterval(pingInterval);
		}
	} catch(exception) {
		log('Error' + exception);
	}
}

function log(text, user){
	var serverLog = $("#chat");
	
	var c = "";
	var add = "";
	if (typeof user != 'undefined'){
		add = "<span class='chat-player'>" + user + "</span>";
		if (user == username){
			c = " me";
		}
	} else {
		c = " console";
	}
	
	serverLog.append('<div class="chat-message ' + c + '"><span class="chat-time">' + timestamp() + ' </span>' + add + ' <span class="chat-msg">' + text + '</span></div>');
	
	serverLog.animate({ scrollTop: serverLog.prop("scrollHeight") - serverLog.height() }, 300);
}

function getScrollCount(id){
	for (var i = 0; i < deck.length; i++){
		if (deck[i].id == id){
			return deck[i].count;
		}
	}
	
	return 0;
}

function makeid() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for(var i = 0; i < 5; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
	}

    return text;
}

function ping(){
	var msg = { msg: "p" };
	send(JSON.stringify(msg));
}

function title(t){
	$("#serverheader").html(t);
}

function subtitle(t){
	$("#drawsheader").html(t);
}

function showBlock(id){
	var margin = -(slider.find(id).index()) * $(".rightblock").width();
	slider.animate({ marginLeft: margin + "px" }, 300);
}

function deckSlideTo(id){
	var margin = -($("#deckslider").find(id).index()) * 600;
	$("#deckslider").animate({ marginLeft: margin + "px" }, 300);	
}

function direction(d){
	if (d == 1){
		$("#direction").html("&uarr;");
	} else {
		$("#direction").html("&darr;");
	}
}

function infobox(id){
	var hoverBox = $("#hoverbox");
	var scroll = getScrollById(id);
	
	hoverBox.find(".blockheader").html(scroll.name);
	
	var costKey = "cost" + scroll.costType.toLowerCase();
	hoverBox.find(".subblock").html("<div style='font-style: italic;margin-bottom: 5px;text-align: center;'>" + caps(scroll.kind) + ": " + scroll[costKey] + " " + scroll.costType + "</div>")
	.append("<div>" + scroll.description + "</div>");
}

function timestamp() {
    var str = "";

    var t = new Date();
    var hours = t.getHours();
    var minutes = t.getMinutes();

    if (minutes < 10) { minutes = "0" + minutes; }
    str += hours + ":" + minutes;
	
    return str;
}

function getScrollById(id){
	for (var i = 0; i < scrolls.length; i++){
		if (scrolls[i].id == id){
			return scrolls[i];
		}
	}
	return false;
}

function caps(inp){
    return inp.charAt(0).toUpperCase() + inp.slice(1).toLowerCase();
}