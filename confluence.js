$(function($){
	Settings.getSettings(function(){
		$("." + Settings.elementClass).each(function(){
			var $el = $(this);
			var $pageId = $el.data('pageid');
			Confluence.getContent($pageId, function(content){
				$el.html(content);
			});
		});
	});
});

var Settings = {
	httpProtocol: 	"",
	websiteUrl: 	"",
	apiName: 		"",
	apiVersion: 	"",
	element: 		"",
	elementClass: 	"",

	getSettings: function(callback) {
		$.ajax({
			type: 'GET',
			url: '/wp-admin/admin-ajax.php',
			data: { action: 'getConfluenceSettings' },
			dataType: 'json',
			success: function(data) { 
				Settings.httpProtocol 	= data.protocol;
				Settings.websiteUrl 	= data.websiteUrl;
				Settings.apiName		= data.apiName;
				Settings.apiVersion		= data.apiVersion;
				Settings.element		= data.element;
				Settings.elementClass	= data.elementClass;				
				if(typeof(callback) == "function") callback.apply(this);
			}, 
			error: function(MLHttpRequest, textStatus, errorThrown) { console.log(errorThrown); }
		});
	}
}

var Confluence = {
	currentSpaceKey: "",

	getContent: function (id, userCallback) {
		var callback = {
			callbackFunction: Confluence.getContentCallback, 
			callbackArguments: [userCallback]
		};
		Confluence.doAjax("content", id, false, callback);
	},
	
	getContentCallback: function (content, userCallback) {
		Confluence.currentSpaceKey = content.space.key;
		var formattedContent = Confluence.formatContent(content.body.value);
		if(typeof(userCallback) == "function") {
			userCallback.call(this, formattedContent);
		}
	},
	
	formatContent: function (content) {
		content = content.replace(/ (id|style|class)="[a-z0-9\s-_]*"/gi, "");
		content = content.replace(/\<ac:parameter[a-z0-9\s:=>"#]*\<\/ac:parameter\>/gi, Confluence.formatContent_Parameters);
		content = content.replace(/\<ac:macro(.)*?\>/gi, "").replace(/<\/ac:macro\>/gi, "");
		content = content.replace(/\<ac:link\>(.)*?\<\/ac:link\>/gi, Confluence.formatContent_Links);
		content = content.replace(/\<ac:image(.)*?\<\/ac:image\>/gi, Confluence.formatContent_Images);
		content = content.replace(/\<ac:rich-text-body(.)*?\>/gi, "").replace(/<\/ac:rich-text-body\>/gi, "");
		content = content.replace(/\<ac:default-parameter(.)*?\<\/ac:default-parameter\>/gi, "");
		content = content.replace(/\<p\>(\s|&nbsp;)*?\<\/p\>/gi, "");
		return content;
	},
	
	formatContent_Parameters: function (content) {
		if(content.match('"title"')) {
			matches = content.replace(/\<ac:parameter ac:name="title"\>([a-z0-9\s]*)\<\/ac:parameter\>/gi, "$1");
			return "<h2>" + matches + "</h2>";
		}
		return "";
	},

	formatContent_Links: function(content) {
		var title = content.match(/title="(.)*?"/ig)[0].replace('title="', '').replace('"', '');	
		var spaceKey = content.match(/ri:space-key="(.)*?"/ig);
		if(spaceKey) spaceKey = spaceKey[0].replace('ri:space-key="', '').replace('"', '');
		var url = Confluence.getPageUrlFromTitleAndSpace(title, spaceKey);
		return "<a href=\"" + url + "\">" + title + "</a>";
	},
	
	formatContent_Images: function(content) {
		return "";
	},
		
	doAjax: function (method, option, attribs, callback) {
		var apiUrl = Confluence.buildApiUrl() + method + "/" + option;
		apiUrl += ".json";					
		$.ajax({
			url: apiUrl,
			data: attribs,
			dataType: "jsonp",
			jsonp: "jsonp-callback",
			error: function(request, status, error) {
				console.log("Ajax error: " + status);
				console.log(error);
			},
			success: function(data, status, request) {				
				if(typeof(callback.callbackFunction) == "function") {
					if(typeof(callback.callbackArguments) == "undefined") {
						callback.callbackArguments = [];
					}
					callback.callbackArguments.unshift(data);
					callback.callbackFunction.apply(this, callback.callbackArguments);
				}
			},
			jsonpCallback: "jsonpCallback"
		});
	},
	
	buildApiUrl: function () {
		return Settings.httpProtocol + "://" + Settings.websiteUrl + "/rest/" + Settings.apiName + "/" + Settings.apiVersion + "/";
	},
	
	getPageUrlFromTitleAndSpace: function (title, space, callback) {
		if(!space) space = Confluence.currentSpaceKey; 
		return Settings.httpProtocol + "://" + Settings.websiteUrl + "/display/" + space + "/" + title.replace(" ", "+", "ig");
	}	
}