var users = {};

function adminAction(element, column, action) {
	let row = element.closest("tr");
	let id = row.data("id");
	let value = element.text();

	switch (action) {
		case "cancelUpdateEmail":
			if (id) {
				let originalValue = users[id];
				element.html(originalValue);
				row.find("td."+column).removeClass("error");
			}
			break;

		case "updateEmail":
			row.find("td."+column).removeClass("error");

			updateEmail(id, value).then(function(r){
				if (!r.success) {
					let fields = r.fields;

					$.each(fields, function(key, field){
						row.find("td."+field).addClass("error");
					});

					if (r.alert) {
						alert(r.alert);
					}
				}
				else {
					users[id] = r.data.value;
					element.text(users[id]);
				}
			});
			break;

		case "unimpersonate":
			$.cookie("session", $.cookie("admin"), { expires: 365 });
			$.removeCookie("admin");
			goto("/admin");
			break;
	}

	makeUneditable(element);
}

function updateEmail(user, email) {
	let data = {
		action: "updateEmail",
		user: user,
		email: email
	};

	return api(data);
}

function resetPassword(user) {
	let data = {
		action: "resetPassword",
		user: user
	};

	return api(data);
}

function impersonate(user) {
	let data = {
		action: "impersonate",
		user: user
	};

	return api(data);
}

$(function(){
	let getUsers = $("#userTable").find("tr");

	$.each(getUsers, function(k, user){
		let id = $(user).find(".id").html();
		let email = $(user).find(".email .edit").html();

		users[id] = email;
	});

	$("html").on("click", function(e){
		let target = $(e.target);
		let action = target.data("action");
		let parent = target.parent();
		let row = target.closest("tr");

		if (target.is(".link")) {
			switch (action) {
				case "resetPassword":
					resetPassword(row.data("id")).then(function(r){
						if (r.success) {
							alert(r.data.password);
						}
					});
					break;

				case "impersonate":
					impersonate(row.data("id")).then(function(r){
						if (r.success) {
							goto("/");
						}
					});
					break;
			}
		}
	});
});

!function(e){"function"==typeof define&&define.amd?define(["jquery"],e):"object"==typeof exports?e(require("jquery")):e(jQuery)}(function(e){var n=/\+/g;function o(e){return r.raw?e:encodeURIComponent(e)}function i(o,i){var t=r.raw?o:function(e){0===e.indexOf('"')&&(e=e.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{return e=decodeURIComponent(e.replace(n," ")),r.json?JSON.parse(e):e}catch(e){}}(o);return e.isFunction(i)?i(t):t}var r=e.cookie=function(n,t,c){if(void 0!==t&&!e.isFunction(t)){if("number"==typeof(c=e.extend({},r.defaults,c)).expires){var u=c.expires,a=c.expires=new Date;a.setTime(+a+864e5*u)}return document.cookie=[o(n),"=",function(e){return o(r.json?JSON.stringify(e):String(e))}(t),c.expires?"; expires="+c.expires.toUTCString():"",c.path?"; path="+c.path:"",c.domain?"; domain="+c.domain:"",c.secure?"; secure":""].join("")}for(var d,f=n?void 0:{},p=document.cookie?document.cookie.split("; "):[],s=0,m=p.length;s<m;s++){var v=p[s].split("="),x=(d=v.shift(),r.raw?d:decodeURIComponent(d)),k=v.join("=");if(n&&n===x){f=i(k,t);break}n||void 0===(k=i(k))||(f[x]=k)}return f};r.defaults={},e.removeCookie=function(n,o){return void 0!==e.cookie(n)&&(e.cookie(n,"",e.extend({},o,{expires:-1})),!e.cookie(n))}});