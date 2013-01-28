$(document).ready(function() {

	$("form#feditform").submit(function(e) {
		e.preventDefault();
		$("input#fedit").attr("disabled", "");
	
		$("span#fediterr").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post("forumedit.php", $("#feditform").serialize(), function(data) {
				if (data.err)
				{
					$("span#fediterr").fadeOut("fast", function() {
						$(this).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
					});
				}
				else
				{
					$("form#feditform tr:has('[name^=name][value=]')").fadeOut("fast");
					$("span#fediterr").hide();
					$("form#feditform tr:not('.clear')").css({'background-color': '#ccffcc'}).animate({backgroundColor: 'white'}, 2000);
				}
				$("input#fedit").removeAttr("disabled");
			}, "json");
		});
	});
	
	$("form#faddform").submit(function(e) {
		e.preventDefault();
		$("input#fadd").attr("disabled", "");
		
		$("span#fadderr").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post("forumedit.php?new=1", $("form#faddform").serialize(), function(data) {
				if (data.err)
				{
					$("span#fadderr").fadeOut("fast", function() {
						$(this).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
					});
				}
				else
				{
					$("form#feditform tr:has('select'):last").after(data.row);
					$("form#feditform tr:has('select'):last").fadeIn("slow");
					$("span#fadderr").hide();
					$("form#faddform tr").css({'background-color': '#ccffcc'}).animate({backgroundColor: 'white'}, 2000);
				}
				$("input#fadd").removeAttr("disabled");
			}, "json");
		});
	});

	$("form#modtools").submit(function(e) {
		e.preventDefault();
		var locked = $("input[name='locked']:checked").val();
		
		$("form#modtools input:button, form#modtools input:submit").attr("disabled", "");
		$("div#tediterr").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post("forums.php?edittopic=1", $("form#modtools").serialize(), function(data) {
				if (data.err)
				{
					$("div#tediterr").fadeOut("fast", function() {
						$(this).html(data.err).fadeIn("fast");
						$("form#modtools input:button, form#modtools input:submit").removeAttr("disabled");
					});
				}
				else
				{
					$("div#tediterr").fadeOut("fast");
					$("form#modtools").css({'background-color': 'transparent'}).animate({backgroundColor: '#ccffcc'}, 2000);
					$("form#modtools input:button, form#modtools input:submit").removeAttr("disabled");
					
					if (locked == 'yes')
					{
						$("form#postform textarea").attr("disabled", "").val("Tråd låst, inga nya inlägg tillåtna");
					}
					else
					{
						$("form#postform textarea").removeAttr("disabled").val(poststandard);
					}
				}
			}, "json");	
		});
	});
	
	$("input#pollsubmit").click(function(e) {
		e.preventDefault();
		
		$.post("polls.php?create=1", $("form#pollform").serialize(), function(data) {
			if (data.err)
			{
				$("div.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				window.location = "index.php";
			}
		}, "json");
	});
	
	$("input#pollupdate").click(function(e) {
		e.preventDefault();
		
		$.post("polls.php?edit=1", $("form#pollform").serialize(), function(data) {
			if (data.err)
			{
				$("div.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				window.location = "index.php";
			}
		}, "json");
	});
	
	$("input#newssubject").focus(function() {
		$subject = $(this).val();
		$newsstandard = "Skapa nyhet...";
		
		if ($subject == $newsstandard)
		{
			$(this).toggleClass("newssubjectactive", true).val("");
			$("textarea.newsbody, input#addnews").fadeIn("fast");
		}
	}).on("newsblur", function(e, newsstandard) {
		$subject = $(this).val();
		
		if ($subject == '')
		{
			$("textarea#newsbody, input#addnews, span#newserr").hide();
			$("input#newssubject").toggleClass("newssubjectactive", false).val(newsstandard);
		}
	});
	
	$("input#addnews").click(function(e) {
		e.preventDefault();
	
		$.post("news.php?add=1", $("form#newsform").serialize(), function(data) {
			if (data.err)
			{
				$("span#newserr").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				$("form#newsform").after(data.news);
				$("div#news" + data.id).show("slow");
				$("div.news:eq(4)").hide("slow");
				$("input#newssubject, textarea#newsbody").val("");
				$("input#newssubject").trigger("newsblur", ["Skapa nyhet..."]);
			}
		}, "json");
	});
	
	$("div#newss").on({
		'mouseenter': function() {
			$(this).find("span.newsedit").fadeIn("fast");
		},
		'mouseleave': function() {
			$(this).find("span.newsedit").fadeOut("fast");
		}
	}, "div.news");
	
	$("form#banform").submit(function(e) {
		e.preventDefault();
		
		$.post($(this).attr("action"), $(this).serialize(), function(data) {
			if (data.err)
			{
				$("span.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				$("form#banform input:text").val("");
				$("table#bans tr:first").after(data.result);
				$("table#bans tr:not(:visible):first").fadeIn("fast");
			}
		}, "json");
	});
	
	poll();
	
	$("input#edituser").click(function(e) {
		e.preventDefault();
		
		$.post("edituser.php", $("form#userform").serialize(), function(data) {
			if (data.err)
			{
				$("span.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				var $id = $("input#userid").val();
				window.location = "userdetails.php?id=" + $id;
			}
		}, "json");
	});
	
	$("form#staffmessform").submit(function(e) {
		e.preventDefault();
		
		$.post("staffmess.php", $(this).serialize(), function(data) {
			if (data.err)
			{
				$("span.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				window.location = "staffmess.php";
			}
		}, "json");
	});
	
	$("form#adduserform").submit(function(e) {
		e.preventDefault();
		
		$.post("adduser.php", $(this).serialize(), function(data) {
			if (data.err)
			{
				$("span.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				window.location = "userdetails.php?id=" + data.id;
			}
		}, "json");
	});
	
	$("input#delstaffmess").click(function() {
		$("input[name^='del']:checked").each(function() {
			delstaffMess($(this).val());
		});
	});
	
	$("form#donationsform").submit(function(e) {
		e.preventDefault();
		
		$.post("donations.php", $(this).serialize(), function(data) {
			$("tr:has('input:radio[value=yes]:checked')").animate({backgroundColor: '#66cc33'}, 1000);
			$("tr:has('input:radio[value=no]:checked')").animate({backgroundColor: '#ff3333'}, 1000);
		});
	});
	
	$("img#staffmenu").click(function() {
		var $show = $(this).attr("alt");
		
		if ($show == 'show')
		{
			document.cookie = "staffnav=1";
			$(this).replaceWith("<img id='staffmenu' src='/pic/minus.gif' alt='hide' />");
		}
		else
		{
			document.cookie = "staffnav=0";
			$(this).replaceWith("<img id='staffmenu' src='/pic/plus.gif' alt='show' />");
		}
		$("ul#staffnav").slideToggle("fast");
	});
});

function poll() {
	$("input[name^='answer']:last").on("keyup", function() {
		if ($(this).val() != '')
		{
			var $index = $(this).index("input[name^='answer']");
			$("tr:has('input[name^=answer]'):last").after("<tr><td class='form'>Svar " + ($index + 2) + "</td><td><input type='text' size=50 name='answer[]' /></td></tr>");
		
			$(this).off("keyup").on("keyup", function() {
				if ($(this).val() == '')
				{
					$("tr:has('input[name^=answer]'):last").remove();
					$(this).off("keyup");
					poll();
				}
			});
			poll();
		}
	});
}

function delPoll(id) {
	var $head = "Radera omröstning";
	var $body = "<b>Bekräfta att du vill radera omröstningen</b><br /><br /><input type='button' value='Bekräfta' id='delpoll' />";
	
	$.when(popUp($head, $body)).done(function() {
		$("input#delpoll").click(function() {
			closepopUp();
			window.location = "polls.php?del=" + id;
		});
	});
}

function delPost(id) {
	var $head = "Radera inlägg";
	var $body = "<b>Bekräfta att du vill radera inlägget</b><br /><br /><input type='button' value='Bekräfta' id='delpost' />";
	
	$.when(popUp($head, $body)).done(function() {
		$("input#delpost").click(function() {
			closepopUp();
	
			$.post("forums.php?delete=1", {id: id}, function(data) {
				if (data.err)
				{
					$("div#errormess" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				}
				else
				{
					$("div#pb" + id).hide("slow");
					$("textarea#postfield").removeAttr("disabled");
				}
			}, "json");
		});
	});
}

function delTopic(id) {
	var $head = "Radera tråd";
	var $body = "<b>Bekräfta att du vill radera tråden</b><br /><br /><input type='button' value='Bekräfta' id='deltopic' />";
	
	$.when(popUp($head, $body)).done(function() {
		$("input#deltopic").click(function() {
			$.post("forums.php?deltopic=1", {id: id}, function(data) {
				if (data.err)
				{
					$("div#editinfo").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				}
				else
				{
					window.location = "/forums.php/viewforum/" + data.forumid + "/";
				}
			}, "json");
		});
	});
}

function delNews(id) {
	$head = "Radera nyhet";
	$body = "<div class='errormess' id='delnewserr'></div><h3>Bekräfta att du vill radera nyheten</h3><input type='button' id='delnews' value='Radera' />";

	$.when(popUp($head, $body)).done(function() {
		$("input#delnews").click(function() {
			$.post("news.php?del=1", {id: id}, function(data) {
				if (data.err)
				{
					$("div#delnewserr").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				}
				else
				{
					closepopUp();
					$("div#news" + id).hide("slow");
				}
			}, "json");
		});
	});
}

function editNews(id) {
	$.post("news.php?edit=1", {id: id}, function(data) {
		if (data.err)
		{
			$("span#newserr" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
		}
		else
		{
			$("h3#nh" + id).html(data.head);
			$("div#nb" + id).html(data.body);
			
			$("input#newsupdate" + id).click(function() {
				var $subject = $("input#newssubject" + id).val();
				var $body = $("textarea#newsbody" + id).val();
		
				$.post("news.php?takeedit=1", {id: id, subject: $subject, body: $body}, function(data) {
					if (data.err)
					{
						$("span#newserr" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
					}
					else
					{
						$("h3#nh" + id).html(data.head);
						$("div#nb" + id).html(data.body);
					}
				}, "json");
			});
		}
	}, "json");
}

function delBan(id, mail) {
	$.post("bans.php?del=1", {id: id, mail: mail}, function() {
		$("tr#b" + id).fadeOut("fast");
	});
}

function readstaffMess(id) {
	var $tr = $("tr#p" + id).is('tr');

	if ($tr)
	{
		$("tr#p" + id).hide("fast", function() {
			$(this).remove();
			$("tr#m" + id).css({'background-color': '#f9f9f9'}).on({
				"mouseleave": function() {
					$(this).css({'background-color': '#f9f9f9'});
				},
				"mouseenter": function() {
					$(this).css({'background-color': '#ededed'});
				}
			});
		});
	}
	else
	{	
		$.post("staffbox.php?view=1", {id: id}, function(data) {
			if (data.err)
			{
				$("tr#m" + id).after("<tr id='p" + id + "'><td colspan=4><div class='errormess' id='e" + id + "'></div></td></tr>");
				$("div#e" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				$("tr#p" + id).delay(2000).hide("fast");
			}
			else
			{
				$("tr#m" + id).after("<tr id='p" + id + "' style='display: none;'><td colspan=5><div class='errormess' id='e" + id + "'></div>" + data.body + "</td></tr>");
				$("tr#p" + id).show("fast");
				
				$("div.prevmess").mouseenter(function() {
					$(this).fadeTo("fast", 1.00);
				}).mouseleave(function() {
					$(this).fadeTo("fast", 0.50);
				});
				
				$("textarea#reply").focus(function() {
					$val = $(this).val();
		
					if ($val == replystandard)
					{
						$(this).toggleClass("replyactive", true, "fast").val("");
						$("div#ra" + id).fadeIn("fast");
					}
				}).blur(function() {
					$val = $(this).val();
					
					if ($val == '')
					{
						$(this).toggleClass("replyactive", false).val(replystandard);
						$("div#ra" + id).hide();
					}
				});
				
				$("input#reply" + id).click(function(e) {
					e.preventDefault();
		
					$.post("staffbox.php?takereply=1", $("form#reply" + id).serialize(), function(data) {
						if (data.err)
						{
							$("span#e" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
						}
						else
						{
							readstaffMess(id);
							$("td#a" + id).html(data.res);
							$("tr#m" + id).css({'background-color': '#ccffcc'}).animate({backgroundColor: '#f9f9f9'}, 2000);
						}
					}, "json");
				});
			}
		}, "json");
		$("tr#m" + id).css({'background-color': '#ededed'}).off("mouseenter mouseleave");
	}
}

function delstaffMess(id) {
	$.post("staffbox.php?del=1", {id: id}, function(data) {
		if ($("tr#p" + id).is(":visible"))
		{
			readstaffMess(id);
		}
		$("tr#m" + id).hide("fast", function() {
			$(this).remove();
		});
	});
}

function solveReport(id) {
	$.post("reports.php?solve=1", {id: id}, function(data) {
		$("td#u" + id).html(data.user);
		$("input#s" + id).attr("disabled", "");
		$("tr#r" + id).animate({backgroundColor: '#ccffcc'}, 1000);
	}, "json");
}

function delReport(id) {
	$.post("reports.php?del=1", {id: id}, function(data) {
		$("tr#r" + id).fadeOut("fast");
	});
}