poststandard = "Skriv inlägg...";
searchstandard = "Forumsök...";
replystandard = "Svara...";
linkstandard = "Nyckelord eller skådespelare...";
guardstandard = "IMDb-länk...";

$(document).ready(function() {

	if (screen.width > 1440)
	{
		$("div#body, div#header").css("width", "80%");
	}

	$(document).click(function(e) {
		if ($(e.target).is(":input") == false)
		{
			$("input#newssubject").trigger("newsblur", ['Skapa nyhet...']);
			$("input#search").trigger("forumsearchblur", ['Forumsök...']);
			$("textarea#postfield").trigger("forumpostblur", ['Skriv inlägg...']);
		}
	});
	
	locationsearch = location.search.substr(1).replace('=', '');

	if ($("#" + locationsearch).is(":visible"))
	{
		var target = locationsearch;
		var scroll = $("#" + target).offset().top - 20;
		
		$("html, body").stop().animate({scrollTop: scroll + 'px'}, "slow");
	}

	$("a[href^='#']").click(function(e) {
		e.preventDefault();
		var scroll = $(this).offset().top - 20;
	
		$("html, body").stop().animate({scrollTop: scroll + 'px'}, "slow");
	});
	
	$("div#background").click(function() {
		closepopUp();
	});
	
	$("form#signform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		
		$.post("/takesignup.php", $(this).serialize(), function(data) {
			if (data.err)
			{
				$("div#regmess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				$("td").css({"background-color": "inherit"});
				$("tr:eq(" + (data.errfield - 1) + ") td:last").css({'background-color': '#fe7777'});
			}
			else
			{
				$("input#signup").attr("disabled", "").val("Loggar in...").after("<img src='/pic/load.gif' />").delay(2000).queue(function() {
					window.location.href = data.redir;
				});
			}
		}, "json");
	});
	
	$("div.post1, div#granskning").on({
		'mouseenter':
			function() {
				$(this).children("div").fadeTo("fast", 1.00);
			}
		, 'mouseleave':
			function() {
				$(this).children("div").fadeTo("fast", 0.00);
			}
		}
	, "fieldset.spoiler");

	$("input#review").click(function() {
		if ($(this).attr("name") == 'topicrev')
		{
			var $form = $("form#topicform");
		}
		else
		{
			var $form = $("form#postform");
		}
	
		$("div#granskning").slideUp("fast", function() {
			$.post("forums.php?review=1", $form.serialize(), function (data) {
				$("div#granskning").html(data).slideDown("fast");
			});
		});
	});
	
	$("form#postform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		var $topicid = $("input[name='topicid']").val();
		
		$("div#errormess").html("<img src='/pic/load.gif' style='vertical-align: text-bottom;' />").fadeTo("fast", 1.00, function() {
			$.post($action, $("form#postform").serialize(), function(data) {
				if (data.err)
				{
					$("div#errormess").fadeOut("fast", function() {
						$(this).html(data.err).fadeIn("fast");
					});
				}
				else
				{
					window.location.href = "/forums.php/viewtopic/" + $topicid + "/last?" + data.pid;
				}
			}, "json");
		});
	});
	
	$("form#topicform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		
		$.post($action, $(this).serialize(), function(data) {
			if (data.err)
			{
				$("div.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				$("td").css({"background-color": "inherit"});
				$("tr:eq(" + (data.errfield - 1) + ") td:last").css({'background-color': '#fe7777'});
			}
			else
			{
				window.location.href = "/forums.php/viewtopic/last/";
			}
		}, "json");
	});

	$("textarea#postfield[name='post']").focus(function() {
		var $val = $(this).val();

		if ($val == poststandard)
		{
			postActive();
		}
	}).on("forumpostblur", function(e, poststandard) {
		var $val = $(this).val();
		
		if ($val == '')
		{
			$(this).toggleClass("postactive", false).val(poststandard);
			$("div#smilies").toggleClass("smilactive", false);
			$("input#review, input#post").attr("disabled", "");
			$("div#granskning").slideUp("fast");
		}
	});
	
	$("input#search").focus(function() {
		var search = $(this).val();
		
		if (search == searchstandard)
		{
			$(this).toggleClass("searchactive", true).val("");
			$("tr#searchforums, td#searchsections").fadeIn("fast");
		}
	}).on("forumsearchblur", function(e, searchstandard) {
		var search = $(this).val();
		
		if (search == '')
		{
			$("tr#searchforums, td#searchsections").fadeOut("fast", function() {
				$("input#search").toggleClass("searchactive", false).val(searchstandard);
			});
		}
	});
	
	$("form#searchform").submit(function(e) {
		e.preventDefault();
		
		forumsearch();
	});
	
	$("form#searchform input#search").keyup(function() {
		forumsearch();
	});
	
	$("form#searchform input.search").click(function() {
		forumsearch();
	});
	
	$("img.top").click(function() {
		$("html, body").animate({scrollTop: '0px'}, "slow");
	});
	
	$("img.bottom").click(function() {
		var scroll = $("form#postform").offset().top;
	
		$("html, body").animate({scrollTop: scroll + 'px'}, "slow");
	});
	
	$("img.smilie, input[value='[*]']").click(function() {
		var textarea = $("textarea#postfield:enabled");
		var text = textarea.val();
		var start = textarea[0].selectionStart;
		var smilie = $(this).attr("title");
		
		if (smilie == undefined)
		{
			var smilie = $(this).attr("value");
		}
		
		if (text == poststandard)
		{
			var text = smilie;
		}
		else
		{
			var text = text.substr(0, start) + smilie + text.substr(start);
		}

		postActive(text);

		textarea[0].setSelectionRange(start + smilie.length, start + smilie.length);
	});
	
	$("input.btag").mouseenter(function(e) {
		var ypos = $("textarea#postfield").position().top;
		var xpos = $(this).position().left + 15;
		var id = $(this).attr("alt");
		
		$("div#" + id).css({top: ypos, left: xpos}).show();
	}).mouseleave(function() {
		var id = $(this).attr("alt");
		
		$("div#" + id).hide();
	});
	
	$("form#profform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		$("input:submit, input:button").attr("disabled", "");
		
		$("span#editres").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post($action, $("form#profform").serialize(), function(data) {
				/*$("td").css({'background-color': 'inherit'});*/
				
				if (data.err)
				{
					$("span#editres").html("<span class='err'>" + data.err + "</span>").fadeIn("fast").fadeOut("fast").fadeIn("fast");
					$("fieldset:eq(" + (data.errtable - 1) + ") tr:eq(" + (data.errfield - 1) + ") td:last").css({'background-color': '#fe7777'});
				}
				else
				{
					$("span#editres").hide();
					$("fieldset").css({'background-color': '#ccffcc'}).animate({backgroundColor: 'white'}, 2000);
				}
				$("input:submit, input:button").removeAttr("disabled");
			}, "json");
		});
	});
	
	$("tr.messhead").on({
		"mouseleave": function() {
			if ($(this).data('open'))
				return(false);
			
			$(this).css({'background-color': '#f9f9f9'});
		},
		"mouseenter": function() {
			$(this).css({'background-color': 'rgba(0, 0, 0, 0.1)'});
		}
	});
	
	$("input#selectall").click(function() {
		if ($(this).is("[name='select']"))
		{
			$("input:checkbox[name^='del']").attr("checked", "");
			$(this).attr("name", "deselect").val("Avmarkera alla");
		}
		else
		{
			$("input:checkbox[name^='del']").removeAttr("checked");
			$(this).attr("name", "select").val("Markera alla");
		}
	});
	
	$("input#deletemess").click(function() {
		$("input:checkbox:checked").each(function() {
			delMess($(this).val());
		});
	});
	
	$("div.avatar").mouseenter(function() {
		$(this).find("div").each(function(index) {
			if (index == 0)
			{
				$(this).slideDown("fast");
			}
			else
			{
				$(this).fadeIn("fast");
			}
		})
	}).mouseleave(function() {
		$(this).find("div").each(function(index) {
			if (index == 0)
			{
				$(this).slideUp("fast");
			}
			else
			{
				$(this).fadeOut("fast");
			}
		})
	});
	
	$("form#voteform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		
		$.post($action, $(this).serialize(), function(data) {
			if (data.err)
			{
				$("span#pollerr").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				$("div#poll").html(data.results);
				$("div.pollres").each(function() {
					$p = $(this).attr("alt");
					$px = $p * 3;
					$(this).animate({width: $px + "px"}, "slow");
				});
			}
		}, "json");
	});
	
	$("a#shownews").click(function() {
		$("div#oldnews").fadeOut("fast", function() {
			$(this).find("p").html("<img src='/pic/load.gif' />").end().fadeIn("fast", function() {
				$(this).load("index.php?news=1");
			});
		});
	});
	
	$("form#guardform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		
		$(this).attr("disabled", "");
		$("span#res").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post($action, $("form#guardform").serialize(), function(data) {
				$("tr:has('input[name^=del]:checked')").fadeOut("fast");
				$("form#guardform tr:gt(0) td").css({'background-color': '#ccffcc'}).animate({backgroundColor: '#f9f9f9'}, 2000);
				$("span#res").hide();
				$("input#guardupdate").removeAttr("disabled");
			});
		});
	});
	
	$("form#loginform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		
		$.post($action, $(this).serialize(), function(data) {
			if (data.err)
			{
				$("div.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				$("span#left").html(data.left);
				$("div#recover").fadeIn("fast");
				
				if (data.refresh)
				{
					window.location.href = "/login.php";
				}
			}
			else
			{
				window.location.href = data.returnto;
			}
		}, "json");
	});
	
	$("form#recoverform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		
		$.post($action, $(this).serialize(), function(data) {
			if (data.err)
			{
				$("div.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				$("div.frame").html(data.result);
			}
		}, "json");
	});
	
	$("form#supportform").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		
		$.post($action, $(this).serialize(), function(data) {
			if (data.err)
			{
				$("div.errormess").html(data.err).fadeIn("fast").fadeTo("fast", 0).fadeTo("fast", 1);
			}
			else
			{
				$("form#supportform").find("input:text, textarea").val("");
				$("div#res").html(data.res).slideDown("fast");
				$("form#supportform tr:not('.clear')").css({'background-color': '#ccffcc'}).animate({backgroundColor: '#f9f9f9'}, 2000);
			}
		}, "json");
	});
	
	$("a#expandblog").click(function() {
		$("tr#showblog td").hide().html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.get("bonus.php", {expand: 1}, function(data) {
				$("tr#showblog").replaceWith(data);
			});
		});
	});
	
	$("img.imdb").mouseenter(function(e) {
		var $pic = $(this);
		var $res = $(window).height() + $("html").scrollTop();
		var $id = $(this).attr("alt");
		var $xpos = $(this).position().left + $(this).outerWidth();
		var $ypos = $(this).position().top;
		
		$(this).fadeTo("fast", 0.50);
		
		if ($(this).offset().top + 330 > $res)
		{
			var $ypos = $ypos - ($(this).offset().top + 330 - $res);
		}
		
		$("div#imdb").stop().show().animate({top: $ypos, left: $xpos}, 500);
		
		$.post("imdbinfo.php", {id: $id}, function(data) {
			/*$("div#imdb").html(data).css({top: $ypos, left: $xpos}).show();*/
			$("div#imdb").html(data);
		});
	}).mouseleave(function(a) {
		if ($("div#imdb").is(":animated") == false || ($(a.relatedTarget).is("#imdb") == false && $(a.relatedTarget).parent("div").is("#imdb") == false))
		{
			$("div#imdb").hide().stop();
		}
		
		$(this).fadeTo("fast", 1.00);
	});
	
	$("div#imdb:not(':animated')").mouseleave(function() {
		$(this).hide().stop();
	});
	
	if (window.location.hash)
	{
		var $hash = window.location.hash.substring(1).split("|");
		
		$("input#search").focus().val($hash[0]);
		$("input[name='section'][value='" + $hash[1] + "']").attr("checked", "");
		
		$.each($hash[2].split(","), function(index, value) {
			$("input[name='forums[]'][value='" + value + "']").attr("checked", "");
		});
		
		forumsearch();
	}
	
	$("form#inviteform").submit(function(e) {
		e.preventDefault();
		
		$("input#sendinvite").attr("disabled", "");
		$("div.errormess").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post("invite.php", $("form#inviteform").serialize(), function(data) {
				if (data.err)
				{
					$("div.errormess").fadeOut("fast", function() {
						$(this).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
					});
				}
				else
				{
					$("div.errormess").fadeOut("fast");
					$("table#sentinvites tr:last").after(data.res);
					$("tr#i" + data.id).animate({backgroundColor: '#ffcccc'}, 2000);
				}
				$("input#sendinvite").removeAttr("disabled");
			}, "json");
		});
	});
	
	$("a#donated").click(function() {
		$("div#donform").slideToggle("slow");
	});
	
	$("form#donorform").submit(function(e) {
		e.preventDefault();
		
		$("div.errormess").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post("donate.php", $("form#donorform").serialize(), function(data) {
				if (data.err)
				{
					$("div.errormess").fadeOut("fast", function() {
						$(this).html(data.err).fadeIn("fast");
					});
				}
				else
				{
					$("form#donorform :input").attr("disabled", "");
					$("h3#res").html(data.res);
					$("div.errormess").fadeOut("fast");
				}
			}, "json");
		});
	});
	
	$("select[name='type']").change(function() {
		if ($(this).val() == 15 || $(this).val() == 16)
		{
			$("div#music").slideDown("fast");
			$("div#language").slideUp("fast");
		}
		else if ($(this).val() == 12 || $(this).val() == 13 || $(this).val() == 14)
		{
			$("div#language").slideDown("fast");
			$("div#music").slideUp("fast");
		}
		else
		{
			$("div#music, div#language").slideUp("fast");
		}
	});
	
	$("input#searchinput").on({
		'focus': function() {
			if ($(this).val() == linkstandard)
			{
				$(this).toggleClass("searchactive", true).val("");
			}
		},
		'blur': function() {
			if ($(this).val() == '')
			{
				$(this).toggleClass("searchactive", false).val("Nyckelord eller skådespelare...");
			}
		}
	});
	
	$("input#linksearch").click(function() {
		var $search = $("input#searchinput");
		
		if ($search.val() == linkstandard)
		{
			$search.val("");
		}
	});
	
	$("span[class^='tt']").mouseenter(function(e) {
		var $left = $(this).position().left + $(this).outerWidth() + 10;
		var $top = $(this).position().top;
		var $imdb = $(this).attr("class");
		var $res = $(window).height() + $("html").scrollTop();
		
		if ($(this).offset().top + 330 > $res)
		{
			var $top = $top - ($(this).offset().top + 330 - $res);
		}
		
		$("div#cover").show().html("<img src='getimdb.php/" + $imdb + "?l=1' />").animate({left: $left, top: $top}, 500);
	}).mouseleave(function(e) {
		if ($("div#cover").is(":animated") == false || ($(e.relatedTarget).is("#cover") == false && $(e.relatedTarget).parent("div").is("#cover") == false))
		{
			$("div#cover").hide().stop();
		}
	});
	
	$("form#rssform").submit(function(e) {
		e.preventDefault();
		
		$("input#submit").attr("disabled", "");
		$("div#rss").slideUp("fast");
		$("div.errormess").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.post("getrss.php", $("form#rssform").serialize(), function(data) {
				if (data.err)
				{
					$("div.errormess").html(data.err).fadeOut("fast").fadeIn("fast");
				}
				else
				{
					$("div.errormess").fadeOut("fast", function() {
						$("div#rss").html(data.link).slideDown("fast");
					});
				}
				$("input#submit").removeAttr("disabled");
			}, "json");
		});
	});
	
	$("input#movieguard").on({
		'focus': function() {
			if ($(this).val() == guardstandard)
			{
				$(this).toggleClass("searchactive", true).val("");
			}
		},
		'blur': function() {
			if ($(this).val() == '')
			{
				$(this).toggleClass("searchactive", false).val(guardstandard);
			}
		}
	});
	
	$(".transp").on({
		'mouseenter': function() {
			$(this).fadeTo("fast", 1.00);
		},
		'mouseleave': function() {
			$(this).fadeTo("fast", 0.30);
		}
	});
	
	$("td.reqinfo input:radio").on({
		'change reset': function(e) {
			var $type = $(this).attr("value");
	
			$("td.reqinfo input:text").each(function() {
				var $name = $(this).attr("name");
		
				if ($name == 'release')
				{
					var $val = "Releasenamn...";
				}
				else if ($name == 'imdb')
				{
					var $val = "IMDb-länk...";
				}
			
				$(this).attr("disabled", "").toggleClass("searchactive", false).val($val);
			});
			
			$("tr#tv").hide().find("input").attr("disabled", "");
			
			if (e.type == 'change')
			{
				$("td.reqinfo input[name='" + $type + "']:text").removeAttr("disabled").toggleClass("searchactive", true).val("").focus();
				
				if ($type == 'imdb')
				{
					$("tr#tv").show().find("input").removeAttr("disabled");
				}
			}
		}
	});
	
	$("form#addreq input[name='del']").click(function() {
		if ($(this).is(":checked"))
		{
			$("form#addreq input[name='reason']").removeAttr("disabled").toggleClass("searchactive", true).val("").focus();
		}
		else
		{
			$("form#addreq input[name='reason']").attr("disabled", "").toggleClass("searchactive", false).val("Anledning...");
		}
	});
	
	$("form#addreq").submit(function(e) {
		e.preventDefault();
		var $action = $(this).attr("action");
		var $submit = $(this).find("input:submit");
		var $id = $submit.data("edit");
		
		$submit.attr("disabled", "");
		$("div#errormess").fadeOut("fast");
		$("tr#r" + $id).fadeTo("fast", 0.5).children("td:eq(1)").append("<img src='/pic/load.gif' style='margin-left: 10px; vertical-align: text-bottom;' />");
		
		$.post($action, $(this).serialize(), function(data) {
			if (data.err)
			{
				$("div#errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				$("tr#r" + $id).fadeTo("fast", 1.0).children("td:eq(1)").find("img").remove();
			}
			else if (data.edit)
			{
				$("tr#r" + data.id).css({'background-color': '#ccffcc'}).fadeTo("slow", 0.00, function() {
					$(this).replaceWith(data.row);
					$("input#canceledit").trigger("click");
				});
			}
			else if (data.del)
			{
				$("tr#r" + data.id).css({'background-color': '#ffcccc'}).fadeOut("slow", function() {
					$(this).remove();
					$("input#canceledit").trigger("click");
				});
			}
			else
			{
				$("table#reqtable tr:first").after(data.row);
				$("tr#r" + data.id).css({'background-color': '#ccffcc'}).animate({backgroundColor: '#f9f9f9'}, 2000);
				$("input#canceledit").trigger("click");
			}
			$submit.removeAttr("disabled");
		}, "json");
	});
	
	$("input#canceledit").click(function() {
		$("form#addreq").fadeTo("fast", 0.00, function() {
			$("div#errormess").hide();
			$(this).attr("action", "addreq.php");
			$("td.reqinfo input:radio").removeAttr("disabled checked").trigger("reset");
			$("tr#tv input").val("");
			$("form#addreq select#cat option").removeAttr("selected").filter(":first").attr("selected", "");
			$("form#addreq input[name='points']").val(0);
			$("tr#del").hide().find("input").each(function() {
				if ($(this).is(":checkbox"))
				{
					$(this).removeAttr("checked");
				}
				else
				{
					$(this).val("");
				}
			});
			$("form#addreq input[name='del']").removeAttr("checked").trigger("click").removeAttr("checked");
			$("form#addreq input:submit").attr("name", "add").val('Lägg till').removeData("edit");
			$("input#canceledit").hide();
			
			$(this).fadeTo("fast", 1.00);
		});
	});
	
	$("table#reqtable").on({
		'mouseenter': function() {
			$(this).css("background-color", "#e8e8e8");
		},
		'mouseleave': function() {
			$(this).css("background-color", "#f9f9f9");
		}
	}, "tr");
	
	$("form#uploadform input[type='file']").change(function() {
		$("body").append("<iframe name='uploadframe' id='uploadframe' style='display: none;'></iframe>\n");
	
		var $form = $("form#uploadform");
		var $frame = $("iframe#uploadframe");
		
		$form.attr({
			action: '/preupload.php',
			target: 'uploadframe'
		});
		
		$frame.load(function() {
			var data = $.parseJSON($frame[0].contentWindow.document.body.innerHTML);
			
			if (data.err)
			{
				alert(data.err);
			}
			else
			{
				$("input[name='name']").val(data.releasename);
				$("input[name='imdb']").val(data.imdb);
			}
			
			$form.attr({
				action: '/takeupload.php'
			}).removeAttr("target");
			
			$frame.remove();
		});
		
		$form.submit();
	});
});

function BB(code) {
	var textarea = $("textarea#postfield:enabled");
	var text = textarea.val();
	var start = textarea[0].selectionStart;
	var end = textarea[0].selectionEnd;
	
	if (code[code.length - 1] == '=')
	{
		if (text == poststandard)
		{
			var text = "[" + code + "][/" + code.substr(0, code.length - 1) + "]";
		}
		else
		{
			var text = text.substr(0, start) + "[" + code + "]" + text.substr(start, end - start) + "[/" + code.substr(0, code.length - 1) + "]" + text.substr(end);
		}
	}
	else
	{
		if (text == poststandard)
		{
			var text = "[" + code + "][/" + code + "]";
		}
		else
		{
			var text = text.substr(0, start) + "[" + code + "]" + text.substr(start, end - start) + "[/" + code + "]" + text.substr(end);
		}
	}
	
	postActive(text);
	
	if (start == end)
	{
		textarea[0].setSelectionRange(start + code.length + 2, start + code.length + 2);
	}
	else
	{
		textarea[0].setSelectionRange(end + code.length * 2 + 5, end + code.length * 2 + 5);
	}
}
	

function forumsearch(page) {
	var $search = $("input#search").val();
	
	if ($search.length > 3)
	{
		var $section = $("input[name='section']:checked").val();
		var $forums = $("input[name='forums[]']:checked").map(function() { return $(this).val(); }).get();
		
		var $q = new Array(encodeURIComponent($search), $section, $forums.join(","));
		location.hash = $q.join("|");
		
		$("table#forums").hide();
		$("div#searchres").fadeOut(100, function() {
			$(this).html("<img src='/pic/load.gif' />").fadeIn(100, function() {
				$.post("forums.php?search=1&page=" + page, $("form#searchform").serialize(), function(data) {
					$("div#searchres").fadeOut(100, function() {
						$(this).html(data).show();
					});
				});
			});
		});
	}
	else
	{
		location.hash = null;
		$("div#searchres").hide();
		$("table#forums").show();
	}
}

function editPost(id) {
	var body = $("input#pe" + id).val();

	$("div#posttext" + id).html("<textarea class='postedit' id='b" + id + "'>" + body + "</textarea><br /><input type='button' value='Ändra' id='editpost' /> <input type='button' value='Avbryt' id='editabort' />");
	
	$("input#editabort").click(function() {
		$.post("forums.php?review=1", {post: body}, function(data) {
			$("div#posttext" + id).html(data);
			$("div#errormess" + id).hide();
		});
	});
	
	$("input#editpost").click(function() {
		var body = $.trim($("textarea#b" + id).val());

		$.post("forums.php?edit=1", {id: id, body: body}, function(data) {
			if (data.err)
			{
				$("div#errormess" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				$("div#posttext" + id).html(data.post);
				$("div#errormess" + id).hide();
				$("input#pe" + id).val(body);
			}
		}, "json");
	});
}

function report(id, type) {
	$.post("/report.php", {id: id, type: type}, function(data) {
		var $head = data.head;
		var $body = data.body;
		
		$.when(popUp($head, $body)).done(function() {
			$("form#report").submit(function(e) {
				e.preventDefault();
				var $action = $(this).attr("action");
			
				$.post($action, $(this).serialize(), function(data) {
					if (data.err)
					{
						$("span#reporterror").html(data.err).show("highlight", "slow");
					}
					else
					{
						$("form#report").html(data.res);
					}
				}, "json");
			});
		});
	}, "json");
}

function popUp(header, html) {
	var def = $.Deferred();
	
	$("body").append("<div id='popuptest'>" + html + "</div>");

	$("div#background").fadeIn("fast", function() {
		$("div#popup").html("<h2 class='popup'>" + header + "</h2><div id='popupbody'>" + html + "</div>").show("drop", "fast", def.resolve);
		
		var $width = $("div#popuptest").outerWidth();
		
		if ($width < 500)
			var $width = 500;
						
		var $leftmargin = $width / 2 + 4;
		$("div#popuptest").remove();
		
		$("div#popup").animate({width: $width + 'px', height: '300px', margin: '-162px 0px 0px ' + -$leftmargin + 'px'}, 400, 'easeInOutCubic');
	});
	
	return def.promise();
}

function closepopUp() {
	if ($("div#popup").is(":visible"))
	{
		$("div#popup").hide("drop", "slow", function() {
			$(this).css({'width': '0px', 'height': '0px', 'margin': '0px'});
		});
		$("div#background").hide();
	}
}

function Disable() {
	$("form#postform input, form#postform textarea").attr("disabled", "");
}

function postActive(text) {
	$("textarea#postfield:enabled").toggleClass("postactive", true, "fast").val(text);
	$("div#smilies").toggleClass("smilactive", true, "fast");
	$("input#review, input#post").removeAttr("disabled");
	
	var scroll = $("form#postform").offset().top;
	
	$("html, body").animate({scrollTop: scroll + 'px'}, "slow", function() {
		$("textarea#postfield").focus();
	});
}

function Quote(id) {
	var body = $("input#pe" + id).val();
	var user = $("#u" + id).text();
	var textarea = $("textarea#postfield:enabled");
	var text = textarea.val();
	var start = textarea[0].selectionStart;
		
	if (text == poststandard)
	{
		var text = "[quote=" + user + "]" + body + "[/quote]";
	}
	else
	{
		var text = text.substr(0, start) + "[quote=" + user + "]" + body + "[/quote]" + text.substr(start);
	}

	postActive(text);
}

function readMess(id) {
	var $tr = $("tr#p" + id).is("tr");

	if ($tr)
	{
		$("tr#p" + id).hide("fast", function() {
			$(this).remove();
			$("tr#m" + id).css({'background-color': '#f9f9f9'}).data('open', false)
		});
	}
	else
	{
		var $pic = $("tr#m" + id + " img:first");
	
		if ($pic.is("[src*='unread']"))
		{
			$pic.replaceWith("<img src='/pic/mess_read.png' title='Läst meddelande' />");
		}
		
		var $unread = $("img[src^='/mess_unread']").size();
		
		if ($unread == 0)
		{
			$("div#newmess").slideUp("fast");
		}
		
		$.post("messages.php?view=1", {id: id}, function(data) {
			if (data.err)
			{
				$("tr#m" + id).after("<tr id='p" + id + "'><td colspan=4><div class='errormess' id='e" + id + "'></div></td></tr>");
				$("div#e" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
				$("tr#p" + id).delay(2000).hide("fast");
			}
			else
			{
				$("tr#m" + id).after("<tr id='p" + id + "' class='messhow'><td colspan=4><div class='errormess' id='e" + id + "'></div>" + data.body + "</td></tr>");
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
				}).on("messblur", function(e, replystandard) {
					$val = $(this).val();
					
					if ($val == '')
					{
						$(this).toggleClass("replyactive", false).val(replystandard);
						$("div#ra" + id).hide();
					}
				});
				
				$(document).click(function(e) {
					if ($(e.target).is(":input") == false)
					{
						$("textarea#reply").trigger("messblur", ['Svara...']);
					}
				});
				
				$("form#reply" + id).submit(function(e) {
					e.preventDefault();
					var $action = $(this).attr("action");
		
					$.post($action, $(this).serialize(), function(data) {
						if (data.err)
						{
							$("span#e" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
						}
						else
						{
							$("tr#m" + id + " img").replaceWith("<img src='/pic/mess_answered.png' title='Besvarat meddelande' />");
							$("tr#m" + id).css({'background-color': '#ccffcc'}).animate({backgroundColor: '#f9f9f9'}, 2000);
			
							if (data.del)
							{
								delMess(id);
							}
							else
							{	
								readMess(id);
							}
						}
					}, "json");
				});
			}
		}, "json");
		$("tr#m" + id).css({'background-color': 'rgba(0, 0, 0, 0.1)'}).data('open', true);
	}
}

function delMess(id) {
	$.post("messages.php?del=1", {id: id}, function(data) {
		if (data.err)
		{
			$("div#e" + id).html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
		}
		else
		{
			if ($("tr#p" + id).is(":visible"))
			{
				readMess(id);
			}
			$("tr#m" + id).hide("fast", function() {
				$(this).remove();
			});
		}
	}, "json");
}

function sendMess(userid) {
	$.post("/messages.php?send=1", {userid: userid}, function(data) {
		if (data.err)
		{
			popUp("Fel", data.err);
		}
		else
		{
			$.when(popUp(data.head, data.body)).done(function() {
				$("form#message").submit(function(e) {
					e.preventDefault();
					$action = $(this).attr("action");
		
					$.post($action, $(this).serialize(), function(data) {
						if (data.err)
						{
							$("span#messerr").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
						}
						else
						{
							$("div#popupbody").html("<h1>Skickat</h1>Meddelandet har blivit skickat");
						}
					}, "json");
				});
				
				$("img.msmilie").click(function() {
					var textarea = $("textarea#messfield");
					var text = textarea.val();
					var start = textarea[0].selectionStart;
					var smilie = $(this).attr("title");
					var text = text.substr(0, start) + smilie + text.substr(start);

					textarea.val(text);
					textarea[0].setSelectionRange(start + smilie.length, start + smilie.length);
					textarea.focus();
				});
				$("input#messubject").focus();
			});
		}
	}, "json");
}

function guard(topicid, posterid) {
	$.post("forums.php?guard=1", {topicid: topicid, posterid: posterid}, function(data) {
		if (data.type == 'startuser' || data.type == 'stopuser')
		{
			var $link = eval('data.' + (data.type == 'startuser' ? 'stopuser' : 'startuser'));
			$("span[id^='guard" + posterid + "'] > a").text($link);
		}
		else if (data.type == 'starttopic' || data.type == 'stoptopic')
		{
			var $link = eval('data.' + (data.type == 'starttopic' ? 'stoptopic' : 'starttopic'));
			$("span#guard > a").text($link);
		}
		else if (data.type == 'usertotopic')
		{
			$("span[id^='guard']:gt(0) > a").text(data.startuser);
			$("span#guard > a").text(data.stoptopic);
		}
		else if (data.type == 'topictouser')
		{
			$("span[id^='guard" + posterid + "'] > a").text(data.stopuser);
			$("span#guard > a").text(data.starttopic);
		}
	}, "json");
}

function ipLogg(id) {
	if ($("div#iplogg").is(":visible"))
	{
		$("div#iplogg").slideUp("fast");
	}
	else
	{
		$("div#iplogg").html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
			$.get("userdetails.php", {id: id, iplogg: 1}, function(data) {
				$("div#iplogg").fadeOut("fast", function() {
					$(this).html(data).slideDown("fast");
				});
			});
		});
	}
}

function delFriend(userid, type) {
	$.post("friends.php?del=1", {userid: userid, type: type}, function(data) {
		$("div#" + (type == 'block' ? "b" : "f") + userid).slideUp("fast");
		$("span#friendactions").html(data);
	});
}

function addFriend(userid, type) {
	$.post("friends.php?add=1", {userid: userid, type: type}, function(data) {
		if (data.err)
		{
			alert(data.err);
		}
		else
		{
			$("span#friendactions").html(data.result);
		}
	}, "json");
}

function delinvite(id) {
	$.get("invite.php", {del: id}, function(data) {
		if (data.err)
		{
			$("div.errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
		}
		else
		{
			$("tr#i" + id).remove();
		}
	}, "json");
}

function loadPage(uri, id) {
	if (!id)
	{
		$("div#background").fadeIn("fast", function() {
			$("div#popup").css({'width': '16px', 'height': '16px', 'margin': '-20px 0px 0px -20px'}).html("<img src='/pic/load.gif' />").show("drop", "fast", function() {
				$.get(uri, function(data) {
					$("body").append("<div id='popuptest'>" + data + "</div>");

					var $width = $("div#popuptest").outerWidth();
					
					if ($width < 500)
						var $width = 500;
					
					var $leftmargin = $width / 2 + 4;
					$("div#popuptest").remove();
		
					$("div#popup").html(data).animate({width: $width + 'px', height: '300px', margin: '-154px 0px 0px ' + -$leftmargin + 'px'}, 400, 'easeInOutCubic');
				});
			});
		});
	}
	else
	{
		if ($("div#" + id).is(":visible"))
		{
			$("img#p" + id).replaceWith("<img src='/pic/plus.gif' id='p" + id + "' style='vertical-align: text-bottom;' />");
			$("div#" + id).slideUp("fast");
		}
		else
		{
			$("div#" + id).html("<img src='/pic/load.gif' />").fadeIn("fast", function() {
				$.get(uri, function(data) {
					$("div#" + id).fadeOut("fast", function() {
						$("img#p" + id).replaceWith("<img src='/pic/minus.gif' id='p" + id + "' style='vertical-align: text-bottom;' />");
						$(this).html(data).slideDown("slow");
					});
				});
			});
		}
	}
}

function bookmark(id) {
	$.post("bookmark.php", {id: id}, function(data) {
		$("div#b" + id).html(data);
	});
}

function catMark(type) {
	$("td[title='" + type + "'] :checkbox").each(function() {
		if ($(this).is(":checked") == true)
		{
			$(this).removeAttr("checked");
		}
		else
		{
			$(this).attr("checked", "");
		}
	});
}

function showMagnet(id) {
	if ($("div#magnet").is(":visible") == true)
	{
		$("div#magnet").hide("fast");
	}
	else
	{
		var $confirm = confirm("Detta kommer registreras som en hämtning. Fortsätt?");
		
		if ($confirm)
		{
			$.get("download.php/" + id, {show: 1}, function(data) {
				$("div#magnet").html(data).show("fast");
			});
		}
	}
}

function vote(id) {
	$.get("reqvote.php", {id: id}, function(data) {
		if (data.err)
		{
			alert(data.err);
		}
		else
		{
			$.when(popUp(data.head, data.body)).done(function() {
				$("form#reqvote").submit(function(e) {
					e.preventDefault();
					
					$.post("reqvote.php?id=" + id, $(this).serialize(), function(data) {
						if (data.err)
						{
							alert(data.err);
						}
						else
						{
							closepopUp();
							
							$("tr#r" + id).css({'background-color': '#ccffcc'}).animate({backgroundColor: '#f9f9f9'}, 2000);
							
							$("td#v" + id + ", td#p" + id).fadeTo("slow", 0.00, function() {
								$("td#v" + id).html(data.votes);
								$("td#p" + id).html(data.points);
								$("td#v" + id + ", td#p" + id).fadeTo("slow", 1.00);
							});
						}
					}, "json");
				});
			});
		}
	}, "json");
}

function editReq(id) {
	$("form#addreq").fadeTo("fast", 0.00, function() {
		$("div#errormess").hide();
		$.get("addreq.php", {edit: id}, function(data) {
			if (data.err)
			{
				$("div#errormess").html(data.err).fadeIn("fast").fadeOut("fast").fadeIn("fast");
			}
			else
			{
				$("form#addreq").attr("action", "addreq.php?edit=" + id);
				$("td.reqinfo input[value!='" + data.type + "']:radio").attr("disabled", "");
				$("td.reqinfo input[value='" + data.type + "']:radio").removeAttr("disabled").attr("checked", "").trigger("change");
			
				if (data.type == 'release')
				{
					$("td.reqinfo input[name='" + data.type + "']:text").val(data.release).focus();
				}
				else if (data.type == 'imdb')
				{
					$("td.reqinfo input[name='" + data.type + "']:text").val(data.link).focus();
				}
			
				$("form#addreq input[name='season']").val(data.season);
				$("form#addreq input[name='episode']").val(data.episode);
				$("form#addreq select#cat option").removeAttr("selected").filter("[value='" + data.cat + "']").attr("selected", "");
				$("form#addreq input[name='points']:text").val(data.points);
				$("tr#del").show();
				$("form#addreq input:submit").attr("name", "edit").val('Ändra').data("edit", id);
				$("form#addreq input#canceledit").show();
			}
			$("form#addreq").fadeTo("fast", 1.00);
		}, "json");
	});
}