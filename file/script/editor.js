/*
	[Destoon B2B System] Copyright (c) 2008-2015 www.destoon.com
	This is NOT a freeware, use is subject to license.txt
*/
function EditorAPI(i, k, v) {
	var i = i ? i : 'content';
	var k = k ? k : 'len';
	var v = v ? v : '';
	switch(k) {
		case 'get':
			if(DTEditor == 'fckeditor') {
				return FCKeditorAPI.GetInstance(i).GetXHTML(true);
			} else if(DTEditor == 'kindeditor') {
				return editor.html();
			}
		break;
		case 'set':
			if(DTEditor == 'fckeditor') {
				FCKeditorAPI.GetInstance(i).SetData(v);
			} else if(DTEditor == 'kindeditor') {
				editor.html(v);
			}
		break;
		case 'ins':
			if(DTEditor == 'fckeditor') {
				var o = FCKeditorAPI.GetInstance(i);
				if(o.EditMode == FCK_EDITMODE_WYSIWYG) {o.InsertHtml(v);} else {alert(L['wysiwyg_mode']);}
			} else if(DTEditor == 'kindeditor') {
				if(editor.designMode) {editor.insertHtml(v);} else {alert(L['wysiwyg_mode']);}
			}
		break;
		case 'len':
			if(DTEditor == 'fckeditor') {
				var o = FCKeditorAPI.GetInstance(i);
				var d = o.EditorDocument;
				var l ;
				if(document.all) {
					return d.body.innerText.length;
				} else {
					var r = d.createRange(); 
					r.selectNodeContents(d.body);
					return r.toString().length;
				}
			} else if(DTEditor == 'kindeditor') {
				return editor.count('text');
			}
		break;
		default:
		break;
	}
}
/* fckeditor
if(DTEditor == 'fckeditor') {
	document.write('<div style="width:'+EDW+';height:11px;text-align:right;margin:-'+(isIE ? 15 : 13)+'px 0 0 -2px;"><img src="'+DTPath+'admin/image/resize.gif" width="11" height="11" style="cursor:n-resize;" onclick="fck_zi();" oncontextmenu="fck_zo();return false;" alt="" title="'+L['fck_zoom']+'"/></div>');
	function fck_zi() {var h = Number(Dd(FCKID+'___Frame').height.replace('px', '')); h = h + 200; Dd(FCKID+'___Frame').height = h+'px';}
	function fck_zo() {var h = Number(Dd(FCKID+'___Frame').height.replace('px', '')); h = h - 200; if(h > 200) Dd(FCKID+'___Frame').height = h+'px';}
} */
/* draft */
if(EDD == 1) {
	var draft_html = '';
	document.write('<div style="width:'+EDW+';color:#666666;">');
	document.write('<a href="javascript:" onclick="draft_get_data();" class="t">'+L['data_recovery']+'</a>');
	document.write('&nbsp;|&nbsp;');
	document.write('<a href="javascript:" onclick="draft_save_draft();" class="t">'+L['save_draft']+'</a>');
	document.write('&nbsp;|&nbsp;<span id="draft_switch"></span>&nbsp;&nbsp;<span id="draft_data_msg"></span>');
	document.write('</div>');
	function draft_get_data() {makeRequest('action=userdata&job=get&mid='+ModuleID, AJPath, '_draft_get_data');}
	function _draft_get_data() {   
		if(xmlHttp.readyState==4 && xmlHttp.status==200) {
			if(xmlHttp.responseText) {
				if(confirm(lang(L['if_cover_data'], [xmlHttp.responseText.substring(0, 19)]))) EditorAPI('content', 'set', xmlHttp.responseText.substring(19, xmlHttp.responseText.length));
			} else {
				alert(L['no_data']);
			}
		}
	}
	function draft_save_data() {
		var l = EditorAPI('content', 'len'); if(l < 10) return;
		var c = EditorAPI('content', 'get'); if(draft_html == c) return;
		makeRequest('action=userdata&mid='+ModuleID+'&content='+encodeURIComponent(c), AJPath);
		draft_html = c; var today = new Date();
		Dd('draft_data_msg').innerHTML = '<img src="'+DTPath+'file/image/clock.gif"/>'+lang(L['draft_auto_saved'], [today.getHours(), today.getMinutes(), today.getSeconds()]);
	}
	function draft_save_draft() {
		var l = EditorAPI('content', 'len');
		if(l < 10) {alert(lang(L['at_least_10_letters'], [l]));return;}
		if(confirm(L['stop_auto_save'])) {
			draft_stop();
			makeRequest('action=userdata&mid='+ModuleID+'&content='+encodeURIComponent(EditorAPI('content', 'get')), AJPath);
			Dd('draft_data_msg').innerHTML = L['draft_saved'];
			window.setTimeout(function(){Dd('draft_data_msg').innerHTML = '';}, 3000);
		}
	}
	var draft_interval;
	function draft_init() {
		draft_interval = setInterval('draft_save_data()', 30000);
		Dd('draft_data_msg').innerHTML = '';
		Dd('draft_switch').innerHTML = '<a href="javascript:" class="t" onclick="draft_stop();">'+L['stop_save']+'</a>';
	}
	function draft_stop() {
		clearInterval(draft_interval);
		Dd('draft_data_msg').innerHTML = L['draft_save_stopped'];
		Dd('draft_switch').innerHTML = '<a href="javascript:" class="t" onclick="draft_init();">'+L['start_save']+'</a>';
	}
	draft_init();
}
/* paste image*/
if((UA.indexOf('chrome') != -1 || UA.indexOf('firefox') != -1) && !get_local('editor_paste_tip')) {
	function editor_paste_tip_close() {$('#editor_paste_tip').hide();set_local('editor_paste_tip', 1);}
	document.write('<span style="border:#E7D4AC 1px solid;background:#FFFCDC;padding:2px;color:#666666;" id="editor_paste_tip">'+L['tip_image']+'<a href="javascript:editor_paste_tip_close();void(0);" class="t">'+L['tip_know']+'</a></span>');
}
if(UA.indexOf('chrome') != -1) {
	setTimeout(function() {
		if(DTEditor == 'fckeditor') {
			var o = FCKeditorAPI.GetInstance(EID);
			var d = o.EditorDocument;
		} else if(DTEditor == 'kindeditor') {
			var d = K('#'+EID);//Error
		}
		d.body.onpaste = function(e) {
			var clipboardData,items,item;
			if(e && (clipboardData=e.clipboardData) && (items=clipboardData.items) && (item=items[0]) && item.kind=='file' && item.type.match(/^image\//i)){
				var blob = item.getAsFile(),reader = new FileReader();
				reader.onload = function(){
					EditorAPI(EID, 'ins', '<img src="'+event.target.result+'">');
				}
				reader.readAsDataURL(blob);
				return false;
			}			
		}
		var f = 0;
		d.body.ondragenter = function(e) {
			if(e.dataTransfer.types == 'Files') {
				f = 1;
			}
		}
		d.body.ondrop = function (e) {
			if(f) {
				var dataTransfer = e.dataTransfer,fileList, fileext;
				if(dataTransfer && (fileList = dataTransfer.files) && fileList.length > 0) {
					for(i = 0; i < fileList.length; i++) {
						//IMG ONLY
						fileext = ext(fileList[i].name);
						if(fileext != 'jpg' && fileext != 'gif' && fileext != 'png' && fileext != 'bmp' && fileext != 'jpeg') continue;
						reader = new FileReader();
						reader.onload = function(){
							EditorAPI(EID, 'ins', '<img src="'+event.target.result+'">');
						}
						reader.readAsDataURL(fileList[i]);
					}
				}
			}
		}
		d.body.ondragover = function(e) {
			//
		}
	}, 3000);
}
/* clear tag*/
function clear_tag() {
	var html = EditorAPI(EID, 'get');
	if(html == '') return;
	var leng = html.length;
	if(leng < 100) return;
	if(!isIE && html.indexOf('mso-') != -1 && html.indexOf('<o:p>') != -1) {//Clear MS Word
		html = html.replace(/<o:p>\s*<\/o:p>/g, '');
		html = html.replace(/<o:p>.*?<\/o:p>/g, '&nbsp;');
		html = html.replace(/\s*mso-[^:]+:[^;"]+;?/gi, '');
		html = html.replace(/\s*MARGIN: 0cm 0cm 0pt\s*;/gi, '');
		html = html.replace(/\s*MARGIN: 0cm 0cm 0pt\s*"/gi, "\"");
		html = html.replace(/\s*TEXT-INDENT: 0cm\s*;/gi, '');
		html = html.replace(/\s*TEXT-INDENT: 0cm\s*"/gi, "\"");
		html = html.replace(/\s*TEXT-ALIGN: [^\s;]+;?"/gi, "\"");
		html = html.replace(/\s*PAGE-BREAK-BEFORE: [^\s;]+;?"/gi, "\"");
		html = html.replace(/\s*FONT-VARIANT: [^\s;]+;?"/gi, "\"");
		html = html.replace(/\s*tab-stops:[^;"]*;?/gi, '');
		html = html.replace(/\s*tab-stops:[^"]*/gi, '');
		html = html.replace(/\s*face="[^"]*"/gi, '');
		html = html.replace(/\s*face=[^ >]*/gi, '');
		html = html.replace(/\s*FONT-FAMILY:[^;"]*;?/gi, '');
		html = html.replace(/<(\w[^>]*) class=([^ |>]*)([^>]*)/gi, "<$1$3");
		html = html.replace(/<(\w[^>]*) style="([^\"]*)"([^>]*)/gi, "<$1$3");
		html = html.replace(/\s*style="\s*"/gi, '');
		html = html.replace(/<SPAN\s*[^>]*>\s*&nbsp;\s*<\/SPAN>/gi, '&nbsp;');
		html = html.replace(/<SPAN\s*[^>]*><\/SPAN>/gi, '');
		html = html.replace(/<(\w[^>]*) lang=([^ |>]*)([^>]*)/gi, "<$1$3");
		html = html.replace(/<SPAN\s*>(.*?)<\/SPAN>/gi, '$1');
		html = html.replace(/<FONT\s*>(.*?)<\/FONT>/gi, '$1');
		html = html.replace(/<\\?\?xml[^>]*>/gi, '');
		html = html.replace(/<\/?\w+:[^>]*>/gi, '');
		html = html.replace(/<\!--.*?-->/g, '');
		html = html.replace(/<(U|I|STRIKE)>&nbsp;<\/\1>/g, '&nbsp;');
		html = html.replace(/<H\d>\s*<\/H\d>/gi, '');
		html = html.replace(/<(\w+)[^>]*\sstyle="[^"]*DISPLAY\s?:\s?none(.*?)<\/\1>/ig, '');
		html = html.replace(/<(\w[^>]*) language=([^ |>]*)([^>]*)/gi, "<$1$3");
		html = html.replace(/<(\w[^>]*) onmouseover="([^\"]*)"([^>]*)/gi, "<$1$3");
		html = html.replace(/<(\w[^>]*) onmouseout="([^\"]*)"([^>]*)/gi, "<$1$3");
		html = html.replace(/<H(\d)([^>]*)>/gi, '<h$1>');
		html = html.replace(/<(H\d)><FONT[^>]*>(.*?)<\/FONT><\/\1>/gi, '<$1>$2<\/$1>');
		html = html.replace(/<(H\d)><EM>(.*?)<\/EM><\/\1>/gi, '<$1>$2<\/$1>');
		html = html.replace(/<PRE\s*[^>]*>(.*?)<\/PRE>/gi, '<p>$1</p>');
	}
	html = html.replace(/ on([a-z]+)=([\'|\"]?)(.*?)([\'|\"]?)/gi, '');
	html = html.replace(/<IFRAME\s*[^>]*>([\s\S]*?)<\/IFRAME>/gi, '');
	html = html.replace(/<SCRIPT\s*[^>]*>([\s\S]*?)<\/SCRIPT>/gi, '');
	html = html.replace(/<FORM\s*[^>]*>([\s\S]*?)<\/FORM>/gi, '');
	html = html.replace(/<STYLE\s*[^>]*>([\s\S]*?)<\/STYLE>/gi, '');	
	html = html.replace(/<\!--([\s\S]*?)-->/g, '');
	html = html.replace(/<LINK\s*[^>]*>/gi, '');
	html = html.replace(/<META\s*[^>]*>/gi, '');
	if(html.length != leng) try { EditorAPI(EID, 'set', html); } catch(e) {}
}
var clearTime = setTimeout(function(){var clearInter = setInterval('clear_tag()', 1000);},10000);
