function format_timer(ms) {
   var s = ("0" + Math.floor((ms / (      1000)) % 60)).slice(-2);
   var m = ("0" + Math.floor((ms / (   60*1000)) % 60)).slice(-2);
   var h = ("0" + Math.floor( ms / (60*60*1000)      )).slice(-2);
   return h + ":" + m + ":" + s;
}

function incrementTimer(start) {
	let current_dt = Date.now();
	let start_dt = new Date(start.replace(' ', 'T'));
	$("#counter").html(format_timer(current_dt - start_dt));
}

window.counterInterval = setInterval(incrementTimer, 1000, start_dt);