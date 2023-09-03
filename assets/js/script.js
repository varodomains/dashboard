var user;
var page,zone,tld,code;
var zones = [];
var records = {};
var notifications = {};
var staked = [];
var nameservers = [];
var ds = "";

var chart;
let months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

var progress;

var css = getComputedStyle($("html")[0]);

var errorMessage = "Something went wrong. Try again?";

var hnsPrice;
var priceOptions = ["USD", "HNS"];


function log(string) {
	return console.log(string);
}

Object.defineProperty(String.prototype, 'capitalize', {
  value: function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
  },
  enumerable: false
});

function setupQR(link) {
	$("#qrcode").empty();
	var qrcode = new QRCode($("#qrcode")[0], {
		text: link,
		width: 200,
		height: 200,
		colorDark: css.getPropertyValue("--primaryForeground"),
		colorLight: css.getPropertyValue("--secondaryBackground"),
		correctLevel: QRCode.CorrectLevel.L
	});
}

async function api(data) {
	if (isStaked(zone)) {
		data["staked"] = true;
	}

	let output = new Promise(function(resolve) {
		$.post("/api", JSON.stringify(data), function(response){
			if (response) {
				let json = JSON.parse(response);

				if (json.message) {
					alert(json.message);
				}

				resolve(json);
			}
		});
	});

	return await output;
}

function serializeObject(obj) {
    var jsn = {};
    $.each(obj, function() {
        if (jsn[this.name]) {
            if (!jsn[this.name].push) {
                jsn[this.name] = [jsn[this.name]];
            }
            jsn[this.name].push(this.value || '');
        } else {
            jsn[this.name] = this.value || '';
        }
    });
    return jsn;
};

function swapPrices() {
	priceOptions.reverse();
}

function updatePrices() {
	updatePricesFor($(".section[data-section=slds]"));
	updatePricesFor($(".popover[data-name=completePurchase]"));

	if (isHandshake()) {
		$(".popover[data-name=completePurchase] input[name=handshake]").val(1);
	}
	else {
		$(".popover[data-name=completePurchase] input[name=handshake]").val(0);
	}
}

function updatePricesFor(el) {
	let els = el.find("div.price, span.total");

	$.each(els, function(k, e){
		let price = $(e).data("price");
		var newPrice;

		if (isHandshake()) {
			newPrice = Math.round(price / hnsPrice);
			$(e).addClass("HNS");
		}
		else {
			newPrice = prettyMoney(price);
			$(e).removeClass("HNS");
		}

		$(e).text(newPrice.toLocaleString("en-US"));
	});
}

function isHandshake() {
	if (priceOptions[0] === "HNS") {
		return true;
	}
	return false;
}

function linkContainsID(page) {
	switch (page) {
		case "manage":
			return true;

		default:
			return false;
	}
}

function updateMenu() {
	if (!Object.keys(zones).length) {
		$(".menu .item[data-page=manage]").addClass("disabled");
	}
	else {
		$(".menu .item[data-page=manage]").removeClass("disabled");
	}
}

function indexForZone(zone) {
	let z = zones.filter(function(d){ return d.id == zone;});

	if (z.length) {
		let i = zones.indexOf(z[0]);
		return i;
	}
	return false;
}

function dataForZone(zone) {
	let z = zones.filter(function(d){ return d.id == zone;});

	if (z.length) {
		return z[0];
	}
	return false;
}

function dataForStaked(tld) {
	let z = staked.filter(function(d){ return d.id == tld;});

	if (z.length) {
		return z[0];
	}
	return false;
}

function loadPage(noState) {
	if (!page) {
		return;
	}

	var name;
	var title = $("title").data("name");

	switch (page) {
		case "manage":
			var data = dataForZone(zone);
			name = data.name;

			if (!data) {
				data = dataForStaked(zone);
				name = data.tld;
			}

			if (!data) {
				goto("/sites");
				return;
			}

			title += " | "+name+" | "+page;
			break;

		case "tld":
			if (tld) {
				if ($(".main").data("tld").length) {
					title += " | ."+tld+" domains";
				}
			}
			break;

		default:
			title += " | "+page;
			break;
	}

	if ((!Object.keys(zones).length && !Object.keys(staked).length) && page == "manage") {
		page = "sites";
		updateMenu();
	}

	document.title = title;

	switch (page) {
		case "tld":
			afterLoad(page);
			return;

		default:
			break;
	}

	$.ajax({
		type: "GET",
		url: "/content/"+page,
		error: function(xhr, response) { 
			$(".holder").html(xhr.status);
			afterLoad(page);
		},
		success: function(content){ 
			$(".holder").html(content);
			afterLoad(page);
		}
	});

	if (!noState) {
		if (linkContainsID(page) && zone) {
			window.history.pushState(null, null, "/"+page+"/"+zone);
		}
		else if (linkContainsID(page) && tld) {
			window.history.pushState(null, null, "/"+page+"/"+tld);
		}
		else {
			window.history.pushState(null, null, "/"+page);
		}
	}
}

function isSubDomain(zone) {
	if (isStaked(zone)) {
		return false;
	}
	
	let data = dataForZone(zone);
	if (data.name.includes(".")) {
		return true;
	}
	
	return false;
}

function isSLD(zone) {
	let data = dataForZone(zone);
	if (typeof data.expiration !== "undefined") {
		return true;
	}
	return false;
}

function isStaked(zone) {
	let data = dataForStaked(zone);
	if (typeof data.tld !== "undefined") {
		return true;
	}
	return false;
}

function afterLoad(page) {
	$(".main").attr("data-page", page).data("page", page);
	$(".main").attr("data-zone", zone).data("zone", zone);
	$(".menu .item.current").removeClass("current");
	$(".menu .item .icon."+page).parent().addClass("current");

	switch (page) {
		case "manage":
			if (!isStaked(zone)) {
				showDomainSelector(true);
			}
			break;

		default:
			showDomainSelector(false);
			break;
	}

	switch (page) {
		case "sites":
			$.each(zones, function(k, data){
				var section = "domains";
				if (isSLD(data.id)) {
					section = "slds";
				}
				if (!$(".section[data-section="+section+"] #domainTable .row[data-id="+data.id+"]").length) {
					$(".section[data-section="+section+"] #domainTable").append(domainRow(data));
				}
			});

			var sections = ["slds", "domains"];
			$.each(sections, function(k, s){
				if ($(".section[data-section="+s+"] #domainTable .row").length) {
					$(".section[data-section="+s+"]").addClass("shown");
				}
			});
			break;

		case "manage":
			getZone(zone).then(function(response){
				loadRecords();

				if (response.data.staked) {
					var zoneData = dataForZone(zone);
					if (!zoneData) {
						zoneData = dataForStaked(zone);
						loadReserved();
						showNSDS();
					}
					else {
						setNS(response.data);
						setDS(response.data);

						if (!isStaked(zone)) {
							$(".section[data-section=ns] .titleAction").removeClass("hidden");
						}
					}

					$(".section[data-section=actions] #actionTable").append(manageDomainRow(zoneData));

					if ($(".section[data-section=actions] #actionTable .row").length) {
						$(".section[data-section=actions]").addClass("shown");
					}
				}
				else {
					showNSDS();
				}
			});
			break;

		case "domains":
			$("#searchDomainsTable td.sld .edit").focus();
			if (isHandshake()) {
				$("input.hnsPricing").prop("checked", true);
			}
			else {
				$("input.hnsPricing").prop("checked", false);
			}
			doSearchDomains();
			break;

		case "tld":
			doSearchDomains();
			break;

		case "notify":
			loadNotifications();
			break;

		case "staking":
			getEarnings().then(function(response){
				$("#earningsTable .loading").remove();

				if (response.success) {
					let data = response.data
					if (!$(".section[data-section=earnings] #earningsTable .row").length) {
						$(".section[data-section=earnings] #earningsTable").append(earningsRow(data));
					}
				}
			});

			getSalesFor("chart").then(function(response){
				if (response.success) {
					if (Object.keys(response.data.sales).length) {
						let labels = response.data.labels;
						salesChart.data.labels = labels;

						var i = 1;
						$.each(response.data.sales, function(tld, value){
							let data = [];

							var sales = {};
							$.each(value, function(k, sale){
								let date = new Date(sale.time * 1000);
								let label = months[date.getMonth()]+" "+date.getDate();
								
								if (!sales[label]) {
									sales[label] = 0;
								}
								sales[label] += 1;
							});

							$.each(labels, function(k, l){
								var count = 0;
								if (sales[l]) {
									count = sales[l];
								}
								data.push(count);
							});

							var dataset = {
								label: emojifyIfNeeded(tld),
								data: data,
								lineTension: 0,
								fill: false,
								borderColor: css.getPropertyValue("--chartColor"+i)
							};

							let duplicate = salesChart.data.datasets.filter(function(e){
								return e.label == dataset.label;
							});

							if (!duplicate.length) {
								salesChart.data.datasets.push(dataset);
							}

							i += 1;
						});
						salesChart.update();
						$("#salesChart").removeClass("none");
					}
					else {
						$(".section[data-section=salesChart] .box").append('<div class="empty center">There are no sales in this time period.</div>');
					}
				}

				$(".section[data-section=salesChart] .loading").remove();
			});

			getMyStaked().then(function(response){
				$("#stakedTable .loading").remove();

				if (response.success) {
					$.each(response.data, function(key, value){
						if (!$("#stakedTable .row[data-id="+value.id+"]").length) {
							$(".section[data-section=staked] #stakedTable").append(domainRow(value));
						}
					});
				}
			});

			getSalesFor("table").then(function(response){
				if (response.data.sales.length) {
					handleSalesResponse(response);
				}
				else {
					$(".section[data-section=salesTable] .box").html('<div class="empty center">There are no sales to display.</div>');
				}
				$("#salesTable .loading").remove();
				
			});
			break;

		case "settings":
			$("input[data-stripe=number]").mask("0000 0000 0000 0000");
			$("input[data-stripe=exp]").mask("00/00");
			$("input[data-stripe=cvc]").mask("000");

			getPaymentMethods().then(function(response){
				if (response.success) {
					$.each(response.data, function(key, value){
						$(".section[data-section=billing] #paymentMethodsTable").append(paymentMethodRow(value));
					});
				}

				$(".section[data-section=billing]").addClass("shown");
				updatePaymentMethods();
			});
			break;

		case "admin":
			$(".section[data-section=actions]").addClass("shown");
			break;

		case "apidocs":
			scrollToSectionIfNeeded();
			break;
	}

	$(".body").scrollTop(0);

	if (isMobile() && menuShowing()) {
		$(".hamburger").trigger("click");
	}
}

$("html").on("keyup", "input[data-stripe=number]", function(e){
	switch ($(this).val()[0]) {
		case "3":
			$("input[data-stripe=cvc]").mask("0000");
			$("input[data-stripe=cvc]").attr("placeholder", "1234");
			break;

		default:
			$("input[data-stripe=cvc]").mask("000");
			$("input[data-stripe=cvc]").attr("placeholder", "123");
			break;
	}
});

$("html").on("paste", "[contenteditable]", e => {
	e.preventDefault();

	let text = e.originalEvent.clipboardData.getData("text/plain")
	$(e.target).text(text);
});

function showNSDS() {
	showZone(zone).then(function(response){
		setNS(response.data);
		setDS(response.data);
	});
}

function setNS(data) {
	$("#nsTable .loading").remove();

	if (data.custom) {
		$(".section[data-section=ns] .customNameservers").prop("checked", true);
		makeNameserversEditable(true);
	}
	else {
		$(".section[data-section=ns] .customNameservers").prop("checked", false);
		makeNameserversEditable(false);

		if (!isSubDomain(zone) && data.NS[0] == "ns1.varo." && $(".section[data-section=ns] .titleAction.hidden").length) {
			$(".section[data-section=ns] .box").append(`<div class="subtitle center">Note: If you're adding these records into Namebase, leave the "Name" field blank.</div>`);
		}
	}

	$("#nsTable .row").remove();

	$.each(data, function(key, value){
		switch (key) {
			case "NS":
				$.each(value, function(i, v){
					nameservers[i] = v;
					$("#nsTable").append(nsRow(key, v));
				});
				break;
		}		
	});

	if (data.custom) {
		padNameservers();
	}
}

function setDS(data) {
	$("#dsTable .loading").remove();
	$("#dsTable .row").remove();

	$.each(data, function(key, value){
		switch (key) {
			case "DS":
				ds = value;
				$("#dsTable").append(nsRow("DS", ds));
				break;
		}		
	});
}

function verifyPurchase(id) {
	let data = {
		action: "verifyPurchase",
		id: id
	};

	return api(data);
}

function updateNS(zone, ns) {
	let data = {
		action: "updateNS",
		zone: zone,
		ns: ns
	};

	return api(data);
}

function updateDS(zone, ds) {
	let data = {
		action: "updateDS",
		zone: zone,
		ds: ds
	};

	return api(data);
}

function resetNSDS(zone) {
	let data = {
		action: "resetNSDS",
		zone: zone
	};

	return api(data);
}

function handleSalesResponse(response, page) {
	if (response.success) {
		if (response.data.sales.length) {
			$(".section[data-section=salesTable] #salesTable .row").remove();

			$("#salesTable").data("page", response.data.page);
			$("#salesTable").attr("data-page", response.data.page);
			$("#salesPage").html($("#salesTable").data("page"));

			if (response.data.page == response.data.pages) {
				$("#salesPage").parent().find("td").last().addClass("hidden");
			}
			else {
				$("#salesPage").parent().find("td").last().removeClass("hidden");
			}

			$.each(response.data.sales, function(key, data){
				$(".section[data-section=salesTable] #salesTable").append(salesRow(data));
			});
		}
	}
	else {
		$("#salesTable").data("page", page);
		$("#salesTable").attr("data-page", page);
	}
}

function updatePaymentMethods() {
	if ($("#paymentMethods .row").length) {
		if ($("#paymentMethods .defaultPaymentMethod").length == $("#paymentMethods .defaultPaymentMethod.shown").length) {
			$("#paymentMethods .defaultPaymentMethod").first().removeClass("shown");
		}
		$(".section[data-section=billing] #paymentMethods").addClass("shown");
	}
	else {
		$(".section[data-section=billing] #paymentMethods").removeClass("shown");
	}
}

function showDomainSelector(bool) {
	if (bool) {
		$(".header .domains").css("visibility", "visible");
	}
	else {
		$(".header .domains").css("visibility", "hidden");
	}
}

function setZones() {
	$(".header .domains").empty();

	$.each(zones, function(key, data){
		if (typeof zone !== "undefined" && zone == data.id) {
			$(".header .domains").append('<option value="'+data.id+'" selected>'+data.name+'</option>');
		}
		else {
			$(".header .domains").append('<option value="'+data.id+'">'+data.name+'</option>');
		}
	});
}

function getPaymentMethods() {
	let data = {
		action: "getPaymentMethods"
	};

	return api(data);
}

function getMyStaked() {
	let data = {
		action: "getMyStaked"
	};

	return api(data);
}

function getEarnings() {
	let data = {
		action: "getEarnings"
	};

	return api(data); 
}

function getSalesFor(type, page=1) {
	let data = {
		action: "getSalesFor"+type.capitalize()
	};

	if (type == "table" && page > 1) {
		data.page = page;
	}

	return api(data); 
}

function getReserved(zone) {
	let data = {
		action: "getReserved",
		zone: zone
	};

	return api(data);
}

function getZone() {
	let data = {
		action: "getZone",
		zone: zone
	};

	return api(data);
}

function getSLDS() {
	let data = {
		action: "getSLDS"
	};

	return api(data);
}

function getZones() {
	let data = {
		action: "getZones"
	};

	return api(data);
}

function getRecords(zone) {
	let data = {
		action: "getRecords",
		zone: zone
	};

	return api(data);
}

function getNotifications(zone) {
	let data = {
		action: "getNotifications"
	};

	return api(data);
}

function getInfo() {
	let data = {
		action: "getInfo"
	};

	return api(data);
}

function loadRecords() {
	getRecords(zone).then(function(response){
		$("#dnsTable .loading").remove();

		$.each(response.data, function(key, record){
			if (!$("#dnsTable .row[data-id="+record.uuid+"]").length) {
				records[record.uuid] = record;
				$("#dnsTable").append(dnsRecordRow(record));
			}
		});

		if ($("#dnsTable .row").length) {
			$("#dnsTable .head").addClass("shown");
			$("#dnsTable .empty").remove();
		}
		else {
			$("#dnsTable").append('<div class="empty center">There are no records.</div>');
		}
	});
}

function loadNotifications() {
	getNotifications().then(function(response){
		$("#notificationTable .loading").remove();

		$.each(response.data, function(key, notification){
			if (!$("#notificationTable .row[data-id="+notification.uuid+"]").length) {
				notifications[notification.uuid] = notification;
				$("#notificationTable").append(notificationRow(notification));
			}
		});

		if ($("#notificationTable .row").length) {
			$("#notificationTable .head").addClass("shown");
			$("#notificationTable .empty").remove();
		}
		else {
			$("#notificationTable").append('<div class="empty center">There are no notifications.</div>');
		}
	});
}

function loadReserved() {
	getReserved(zone).then(function(r){
		$("#reservedTable .loading").remove();

		if (r.success) {
			$.each(r.data, function(key, value){
				if (!$("#reservedTable .row[data-id="+value.id+"]").length) {
					$(".section[data-section=reserved] #reservedTable").append(domainRow(value, true));
				}

				if ($(".section[data-section=reserved] #reservedTable .row").length) {
					$(".section[data-section=reserved]").addClass("shown");
				}
			});
		}
	});
}

function updateRecord(zone, record, column, value) {
	let data = {
		action: "updateRecord",
		zone: zone,
		record: record,
		column: column,
		value: value
	};

	return api(data);
}

function updateNotification(notification, column, value) {
	let data = {
		action: "updateNotification",
		notification: notification,
		column: column,
		value: value
	};

	return api(data);
}

function deleteRecord(zone, record) {
	let data = {
		action: "deleteRecord",
		zone: zone,
		record: record
	};

	return api(data);
}

function deleteNotification(notification) {
	let data = {
		action: "deleteNotification",
		notification: notification
	};

	return api(data);
}

function deleteReserved(zone) {
	let data = {
		action: "deleteReserved",
		zone: zone
	};

	return api(data);
}

function deleteZone(zone) {
	let data = {
		action: "deleteZone",
		zone: zone
	};

	return api(data);
}

function showZone(zone) {
	let data = {
		action: "showZone",
		zone: zone
	};
	return api(data);
}

function logout(id) {
	let data = {
		action: "logout"
	};

	return api(data);
}

function emojifyIfNeeded(name) {
	try {
		let emojified = punycode.ToUnicode(name);
		return emojified;
	}
	catch {
		return name;
	}
}

function domainRow(data, reserved=false) {
	if (reserved) {
		return `
			<div class="row" data-id="${data.id}">
				<div class="items">
					<div class="select">${emojifyIfNeeded(data.name)}</div>
					<div class="link" data-action="transferReserved">Transfer</div>
					<div class="actionHolder item">
						<div class="actions">
						<div class="circle"></div>
						<div class="icon delete" data-action="deleteReserved"></div>
					</div>
					</div>
				</div>
			</div>
		`;
	}
	else if (isSLD(data.id)) {
		let expiration = data.expiration * 1000;
		let daysUntilExpiration = Math.round(((expiration - Date.now()) / 1000) / 86400);
		
		var autoRenew = "";
		var state = "Expires";
		if (data.renew) {
			autoRenew = " checked";
		}
		let date = new Date(expiration).toLocaleDateString("en-US");

		let live = true;
		if (data.live == false) {
			live = false;
			autoRenew = " disabled";
		}

		if (!data.renew) {
			if (daysUntilExpiration <= 0) {
				state = "Expired";
				date += '<div class="icon error"></div>';
			}
			else if (daysUntilExpiration <= 30) {
				date += '<div class="icon warning"></div>';
			}
		}

		return `
			<div class="row" data-id="${data.id}" data-live="${live}">
				<div class="items">
					<div class="select">${emojifyIfNeeded(data.name)}</div>
					<div>${state}: ${date}</div>
					<div class="flex">Auto Renew: 
						<label class="cl-switch custom">
							<input type="checkbox" class="autoRenew"${autoRenew}>
							<span class="switcher"></span>
						</label>
					</div>
					<div class="link" data-action="manageDomain">Manage</div>
				</div>
			</div>
		`;
	}
	else if (data.tld) {
		return `
			<div class="row" data-id="${data.id}" data-tld="${data.tld}">
				<div class="items">
					<div class="select">${emojifyIfNeeded(data.tld)}</div>
					<div class="link" data-action="tldLink">Direct Link</div>
					<div class="link" data-action="manageDomain">Manage</div>
				</div>
			</div>
		`;
	}
	else {
		return `
			<div class="row" data-id="${data.id}">
				<div class="items">
					<div class="select">${emojifyIfNeeded(data.name)}</div>
					<div class="link" data-action="manageDomain">Manage</div>
					<div class="actionHolder item">
						<div class="actions">
							<div class="circle"></div>
							<div class="icon delete" data-action="deleteDomain"></div>
						</div>
					</div>
				</div>
			</div>
		`;
	}
}

function earningsRow(data) {
	var items = [];
	$.each(Object.keys(data), function(k, n){
		items.push(`
			<div class="flex column center">
				<div class="subtitle">${n}</div>
				<div class="money">${data[n]}</div>
			</div>
		`);
	});
	return `
		<div class="row">
			<div class="items ignore">${items.join('')}</div>
		</div>
	`;
}

function manageDomainRow(data) {
	if (isSLD(data.id)) {
		let expiration = data.expiration * 1000;
		let daysUntilExpiration = Math.round(((expiration - Date.now()) / 1000) / 86400);
		
		var autoRenew = "";
		var state = "Expires";
		if (data.renew) {
			autoRenew = " checked";
		}
		let date = new Date(expiration).toLocaleDateString("en-US");

		let live = true;
		var disabled = "";
		if (data.live == false) {
			live = false;
			disabled = " disabled";
			autoRenew = disabled;
		}

		if (!data.renew) {
			if (daysUntilExpiration <= 0) {
				state = "Expired";
				date += '<div class="icon error"></div>';
			}
			else if (daysUntilExpiration <= 30) {
				date += '<div class="icon warning"></div>';
			}
		}
		return `
			<div class="row" data-id="${data.id}" data-live="${live}">
				<div class="items">
					<div class="secondary">${state}: ${date}</div>
					<div class="flex">Auto Renew: <label class="cl-switch custom"><input type="checkbox" class="autoRenew"${autoRenew}><span class="switcher"></span></label></div>
					<div class="link${disabled}" data-action="renewDomain">Renew</div>
					<div class="link" data-action="transferDomain">Transfer</div>
				</div>
			</div>
		`;
	}
	else if (data.tld) {
		var live = "";
		if (data.live) {
			live = " checked";
		}
		return `
			<div class="row" data-id="${data.id}" data-tld="${data.tld}">
				<div class="items">
					<div>Sales: <label class="cl-switch custom"><input type="checkbox" class="salesEnabled"${live}><span class="switcher"></span></label></div>
					<div class="link" data-action="changePrice">Change Price</div>
					<div class="link" data-action="addReserved">Reserve Names</div>
					<div class="link" data-action="giftDomain">Gift Domain</div>
				</div>
			</div>
		`;
	}
}

function dnsRecordRow(record) {
	let prio = record.prio || "";
	return `
		<div class="row" data-id="${record.uuid}">
			<div class="mobileItems">
				<div>
					<div><div class="subtitle">${record.type} </div><div class="name truncate">${record.name}</div></div>
					<div><div class="subtitle">Value</div><div class="content truncate">${record.content}</div></div>
			    </div>
			    <div class="actionHolder">
		            <div class="actions">
		                <div class="icon edit" data-action="editRecord"></div>
		            </div>
		        </div>
		    </div>
		    <div class="items">
		        <div class="type item">${record.type}</div>
		        <div class="name item"><div class="edit">${record.name}</div></div>
		        <div class="content item"><div class="edit">${record.content}</div></div>
		        <div class="prio item"><div class="edit">${prio}</div></div>
		        <div class="ttl item"><div class="edit">${record.ttl}</div></div>
		        <div class="actionHolder item">
		            <div class="actions">
		                <div class="circle"></div>
		                <div class="icon delete" data-action="deleteRecord"></div>
		            </div>
		        </div>
		    </div>
		</div>
	`;
}

function notificationRow(notification) {
	let name = notification.name;
	if (!name) {
		name = "&nbsp;";
	}
	return `
		<div class="row" data-id="${notification.uuid}">
			<div class="mobileItems">
				<div>
					<div><div class="subtitle">${notification.type.toUpperCase()} </div><div class="name truncate">${name}</div></div>
					<div><div class="subtitle">Value</div><div class="value truncate">${notification.value}</div></div>
			    </div>
			    <div class="actionHolder">
		            <div class="actions">
		                <div class="icon edit" data-action="editNotification"></div>
		            </div>
		        </div>
		    </div>
		    <div class="items">
		        <div class="type item">${notification.type.toUpperCase()}</div>
		        <div class="name item"><div class="edit">${notification.name}</div></div>
		        <div class="value item"><div class="edit">${notification.value}</div></div>
		        <div class="actionHolder item">
		            <div class="actions">
		                <div class="circle"></div>
		                <div class="icon delete" data-action="deleteNotification"></div>
		            </div>
		        </div>
		    </div>
		</div>
	`;
}

function nsRow(type, value) {
	return `
		<div class="row">
			<div class="items">
				<div class="type item">
					<div>${type}</div>
				</div>
				<div class="value item">
					<div class="edit select">${value}</div>
				</div>
			</div>
		</div>
	`;
}

function searchDomainsRow(data) {
	var available = "Available";
	var unavailable = "";
	var hidden = "";
	if (!data.available) {
		available = "Unavailable";
		unavailable = " disabled";
		hidden = " hidden";
	}
	return `
		<div class="row" data-domain="${data.domain}">
			<div class="items">
				<div>${emojifyIfNeeded(data.domain)}<div class="status ${available.toLowerCase()}">${available}</div></div>
				<div class="actions${hidden}">
				<div class="price" data-price="${data.price}">${data.price}</div>
				<div class="buy button${unavailable}" data-action="buyDomain" title="${available}">Buy</div>
			</div>
		</div>
	`;
}

function paymentMethodRow(data) {
	var def = " shown";
	if (data.default) {
		def = "";
	}
	return `
		<div class="row" data-id="${data.id}">
			<div class="items">
				<div>${data.brand} *${data.last4} (${data.expiration})</div>
				<div class="link defaultPaymentMethod${def}" data-action="defaultPaymentMethod">Make Default</div>
				<div class="actionHolder item">
					<div class="actions">
						<div class="circle"></div>
						<div class="icon delete" data-action="deletePaymentMethod"></div>
					</div>
				</div>
			</div>
		</div>
	`;
}

function salesRow(data) {
	let name = data.name+"."+data.tld;
	let type = data.type.toUpperCase();
	let date = new Date(data.time * 1000).toLocaleDateString("en-US");
	let price = prettyMoney(data.price / 100);
	let total = prettyMoney(data.total / 100);
	let fee = prettyMoney(data.fee / 100);
	let net = prettyMoney(total - fee);
	let title = emojifyIfNeeded(name);
	return `
		<div class="row">
			<div class="items">
				<div>${date}</div>
				<div>${type}</div>
				<div>${name}</div>
				<div class="money">${net}</div>
			</div>
		</div>
	`;
}

function scrollEditables() {
	$("td.editing").each(function(e){
		let element = $(this).find(".edit");

		element.scrollLeft(0);
	});
}

function editField(element) {
	if (element.length) {
		let column = element[0].classList[0];
		let id = element.closest(".row").data("id");

		if (element.closest(".table").attr("id") === "dnsTable") {
			let type = records[id]["type"];

			if (column == "prio" && type !== "MX") {
				return;
			}
		}

		let field = element.find(".edit");
		field.attr("contenteditable", true);

		let value = field.text();
		let length = value.length;

		if (length) {
			setCursor(field, length);
		}
		field.focus();
	}
}

function createZone() {
	let table = $("#createZoneTable");
	let domain = table.find("td.domain .edit").text();

	let data = {
		action: "createZone",
		domain: domain
	};

	return api(data);
}

function doCreateZone() {
	let row = $("#createZoneTable tr");

	createZone().then(function(r){
		if (!r.success) {
			let fields = r.fields;

			$.each(fields, function(key, field){
				row.find("td."+field).addClass("error");
			});
		}
		else {
			loadZones();
		}
	});
}

function searchDomains(query, tld) {
	let data = {
		action: "searchDomains",
		query: query
	};

	if (tld.length) {
		data.tld = tld;
	}

	return api(data);
}

function doSearchDomains(searching=false) {
	let table = $("#searchDomainsTable");
	let tld = table.data("tld");
	let row = table.find("tr");
	let query = table.find("td.sld .edit").text();

	if (table.hasClass("loading")) {
		return;
	}

	$(".section[data-section=slds] #domainTable .row").remove();
	$(".section[data-section=slds] .loading").remove();
	$(".section[data-section=slds] .box").append(`<div class="loading subtitle center">Loading...</div>`);
	table.addClass("loading");

	if (searching) {
		$(".section[data-section=slds] .titleHolder .title").html("Search Results");
	}

	$.each(row.find("td.error"), function(key, r) {
		$(r).removeClass("error");
	});

	if (query.length) {
		searchDomainsResult(query, tld);
	}
	else {
		randomDomainsResult(tld);
	}
}

function searchDomainsResult(query, tld) {
	searchDomains(query, tld).then(function(r){
		domainResult(r);
	});
}

function randomDomainsResult(t=false) {
	getRandomAvailableNames(t).then(function(response){
		domainResult(response);
	});
}

function gotDomainResult() {
	$("#searchDomainsTable").removeClass("loading");
	$(".section[data-section=slds] .loading").remove();
}

function regexEscape(string) {
	return string.replace(/[.*+\'\`\-\_?^$\{\}\(\)\|\[\\\]\/\#\&\!\+\@\:\~\=]/g, '\\$&');
}

function domainResult(response) {
	let table = $("#searchDomainsTable");
	let row = table.find("tr");

	gotDomainResult();

	if (response.success) {
		let result = response.data;

		$.each(result, function(key, data){
			if (!$(".section[data-section=slds] #domainTable .row[data-domain="+regexEscape(data.domain)+"]").length) {
				$(".section[data-section=slds] #domainTable").append(searchDomainsRow(data));
			}
		});
	}
	else {
		let fields = response.fields;

		$.each(fields, function(key, field){
			row.find("td."+field).addClass("error");
		});
	}

	updatePrices();
	showNoSearchResultsIfNeeded();
}

function showNoSearchResultsIfNeeded() {
	if (!$(".section[data-section=slds] #domainTable").find(".row").length) {
		$(".section[data-section=slds] .box").append(`<div class="subtitle center">There are no results.</div>`);
	}
}

function getRandomAvailableNames(tld) {
	let data = {
		action: "randomAvailableNames"
	};

	if (tld) {
		data.tld = tld;
	}

	return api(data);
}

function addRecord() {
	let table = $(".section[data-section=record] .dnsTable");

	let type = table.find("div.type select").val();
	let name = table.find("div.name .edit").text();
	let content = table.find("div.content .edit").text();
	let prio = table.find("div.prio .edit").text();
	let ttl = table.find("div.ttl .edit").text();

	let data = {
		action: "addRecord",
		zone: zone,
		type: type,
		name: name,
		content: content,
		prio: prio,
		ttl: ttl
	};

	return api(data);
}

function doAddRecord() {
	let row = $(".section[data-section=record] .dnsTable .row");

	$.each(row.find("div.error"), function(key, r) {
		$(r).removeClass("error");
	});

	addRecord().then(function(r){
		if (!r.success) {
			let fields = r.fields;

			$.each(fields, function(key, field){
				row.find("."+field).addClass("error");
			});
		}
		else {
			loadRecords();
			let fields = row.find(".edit");
			$.each(fields, function(key, field){
				$(field).html('');
			});
			row.find("div.name .edit").focus();
		}
	});
}

function addNotification() {
	let table = $(".section[data-section=notification] .notificationTable");

	let type = table.find("div.type select").val();
	let name = table.find("div.name .edit").text();
	let value = table.find("div.value .edit").text();

	let data = {
		action: "addNotification",
		type: type,
		name: name,
		value: value,
	};

	return api(data);
}

function doAddNotification() {
	let row = $(".section[data-section=notification] .notificationTable .row");

	$.each(row.find("div.error"), function(key, r) {
		$(r).removeClass("error");
	});

	addNotification().then(function(r){
		if (!r.success) {
			let fields = r.fields;

			$.each(fields, function(key, field){
				row.find("."+field).addClass("error");
			});
		}
		else {
			loadNotifications();
			let fields = row.find(".edit");
			$.each(fields, function(key, field){
				$(field).html('');
			});
			row.find("div.name .edit").focus();
		}
	});
}

function setCursor(element, position) {
	var range = document.createRange();
	var selection = window.getSelection();
	range.setStart(element[0].childNodes[0], position);
	range.collapse(true);
	selection.removeAllRanges();
	selection.addRange(range);
}

function makeUneditable(element) {
	element.attr("contenteditable", false);
	element.parent().removeClass("editing");
	element.parent().find(".actions").remove();
}

function handleAction(element, column, action) {
	let row = element.closest("tr");
	if (!row.length) {
		row = element.closest(".row");
	}

	let id = row.data("id");
	let value = element.text();
	var type,index,items,popover;

	if (element.hasClass("disabled")) {
		return;
	}

	switch (action) {
		case "cancelEditRecord":
			if (id) {
				let originalValue = records[id][column];
				element.html(originalValue);
				row.find("div."+column).removeClass("error");
			}
			break;

		case "cancelEditNotification":
			if (id) {
				let originalValue = notifications[id][column];
				element.html(originalValue);
				row.find("div."+column).removeClass("error");
			}
			break;

		case "cancelEditNSDS":
			type = row.find(".type div").text();
			index = row.index();

			if (type == "NS") {
				let originalValue = nameservers[index] || '';
				element.html(originalValue);
			}
			else {
				let originalValue = ds;
				element.html(ds);
			}
			break;

		case "updateRecord":
			row.find("div."+column).removeClass("error");

			updateRecord(zone, id, column, value).then(function(r){
				if (!r.success) {
					let fields = r.fields;

					$.each(fields, function(key, field){
						row.find("div."+field).addClass("error");
					});
				}
				else {
					records[id][column] = r.data.value;
					element.text(records[id][column]);
				}
			});
			break;

		case "updateNotification":
			row.find("div."+column).removeClass("error");

			updateNotification(id, column, value).then(function(r){
				if (!r.success) {
					let fields = r.fields;

					$.each(fields, function(key, field){
						row.find("div."+field).addClass("error");
					});
				}
				else {
					notifications[id][column] = r.data.value;
					element.text(notifications[id][column]);
				}
			});
			break;

		case "acceptNSDS":
			type = row.find(".type").text();
			index = row.index();

			if (type == "NS") {
				nameservers[index] = value;
			}
			else {
				ds = value;
			}
			break;

		case "updateNS":
			var nsRecords = [];
			$.each($("#nsTable .row"), function(k, v){
				type = $(v).find(".type").text();
				value = $(v).find(".value").text();

				if (type == "NS") {
					nsRecords.push(value);
				}
			}).promise().done(function(){
				let ns = JSON.stringify(nsRecords);
				updateNS(zone, ns).then(function(response){
					getZone(zone).then(function(zoneResponse){
						setNS(zoneResponse.data);
					});
				});
			});
			break;

		case "updateDS":
			var dsRecord = "";
			$.each($("#dsTable .row"), function(k, v){
				type = $(v).find(".type").text();
				value = $(v).find(".value").text();

				if (type == "DS") {
					dsRecord = value;
				}
			}).promise().done(function(){
				let ds = dsRecord;

				updateDS(zone, ds).then(function(response){
					getZone(zone).then(function(zoneResponse){
						setDS(zoneResponse.data);
					});
				});
			});
			break;

		case "editRecord":
			items = row.find(".items");
			popover = $(".popover[data-name=editRecord]");
			type = items.find(".item.type").text();

			popover.find("input[name=zone]").val(zone);
			popover.find("input[name=record]").val(id);

			popover.find("input[name=type]").val(type);
			popover.find("input[name=name]").val(items.find(".item.name").text());
			popover.find("input[name=content]").val(items.find(".item.content").text());
			popover.find("input[name=prio]").val(items.find(".item.prio").text());
			popover.find("input[name=ttl]").val(items.find(".item.ttl").text());

			if (type === "MX") {
				popover.find("input[name=prio]").closest("tr").removeClass("none");
			}
			else {
				popover.find("input[name=prio]").closest("tr").addClass("none");
			}

			showPopover(action);
			break;

		case "deleteRecord":
			row.remove();
			hideTooltip(id);
			deleteRecord(zone, id);
			break;

		case "editNotification":
			items = row.find(".items");
			popover = $(".popover[data-name=editNotification]");
			type = items.find(".item.type").text();

			popover.find("input[name=notification]").val(id);

			popover.find("input[name=type]").val(type);
			popover.find("input[name=name]").val(items.find(".item.name").text());
			popover.find("input[name=value]").val(items.find(".item.value").text());

			showPopover(action);
			break;

		case "deleteNotification":
			row.remove();
			hideTooltip(id);
			deleteNotification(id);
			break;

		case "deleteDomain":
			row.remove();
			hideTooltip(id);

			let i = indexForZone(id);
			delete zones[i];
			zones.splice(i, 1);
			setZones();
			deleteZone(id).then(function(){
				updateMenu();
			});

			if (!Object.keys(zones).length) {
				$(".section[data-section=domains]").removeClass("shown");
			}
			break;

		case "deleteReserved":
			row.remove();
			hideTooltip(id);

			deleteReserved(id);

			if (!$(".section[data-section=reserved] .row").length) {
				$(".section[data-section=reserved]").removeClass("shown");
			}
			break;

		case "deletePaymentMethod":
			deletePaymentMethod(id).then(function(response){
				if (response.success) {
					row.remove();
					hideTooltip(id);
					updatePaymentMethods();
				}
				else {
					errorMessage();
				}
			});
			break;

		case "buyDomain":
			let domain = row.data("domain");
			var price = prettyMoney(row.find(".price").data("price"));
			$(".popover[data-name=completePurchase] .titleHolder .title").html("Purchase");
			$(".popover[data-name=completePurchase] input[name=type]").val("register");
			$(".popover[data-name=completePurchase] td.domain div").html(emojifyIfNeeded(domain));
			$(".popover[data-name=completePurchase] input[name=domain]").val(domain);
			$(".popover[data-name=completePurchase] td.price div.price").attr("data-price", price).data("price", price);
			$(".popover[data-name=completePurchase] td.price div.price").html();
			$(".popover[data-name=completePurchase] span.total").attr("data-price", price).data("price", price);
			$(".popover[data-name=completePurchase] span.total").html(price);
			$(".popover[data-name=completePurchase] td.years select").val("1");
			$(".popover[data-name=completePurchase] .submit").removeClass("disabled");
			updatePrices();
			showPopover("completePurchase");
			element.removeClass("disabled");
			break;

		case "close":
			let name = element.closest(".popover").data("name");
			close(name);
			break;

		case "dashboard":
			openURL("/login");
			break;

		case "shopTLD":
			let tld = element.data("tld");
			openURL(`/tld/${tld}`);
			break;

		default:
			adminAction(element, column, action);
			break;
	}

	makeUneditable(element);
}

function roundUp(num) {
    return +(Math.round(num + "e+2")  + "e-2");
}

function prettyMoney(p) {
	var price = roundUp(p);
	let split = price.toString().split(".");

	switch (split.length) {
		case 1:
			price = price + ".00";
			break;

		case 2:
			if (split[1].length == 1) {
				price = `${price}0`;
			}
			break;
	}

	return price;
}

function errorMessage() {
	alert(errorMessage);
}

function changeZone(id) {
	zone = id;
	$(".domains").val(zone);
}

function showPopover(name) {
	let z = topMostPopoverZ();
	$("#blackout").addClass("shown");
	let popover = $(".popover[data-name="+name+"]").clone();
	let cell = $('<div class="blackoutTable"><div class="blackoutCell"></div></div>');
	cell.find(".blackoutCell").append(popover);
	cell.find(".popover").addClass("shown");
	cell.find(".popover").css("z-index", z + 1);
	$("#blackout").append(cell);
	cell.find(".popover").find("input:not([readonly]),textarea").first().focus();
}

function topMostPopoverZ() {
	var topMost = 1;

	var popovers = $(".popover.shown");
	if (!popovers.length) {
		return 999;
	}
	else {
		$.each(popovers, function(k, p){
			let z = $(p).css("z-index");
			if (z > topMost) {
				topMost = z;
			}
		});

		return parseInt(topMost);
	}
}

function topMostPopover() {
	var popovers = $(".popover.shown").sort(function(a,b){
		return $(b).css("z-index") - $(a).css("z-index");
	});

	return popovers.first();
}

function close(name) {
	if (name) {
		$(".popover[data-name="+name+"].shown").closest(".blackoutTable").remove();
	}
	else {
		$("#blackout").empty();
	}

	if (!$(".popover.shown").length) {
		$("#blackout").removeClass("shown");
	}

	clearTimersIfNeeded();
}

function clearTimersIfNeeded() {
	if (typeof expiryTimer != "undefined") {
		clearInterval(expiryTimer);
	}
	if (typeof verifyTimer != "undefined") {
		clearInterval(verifyTimer);
	}
}

function editActions(table, neg, pos) {
	var icon = "";
	switch (table) {
		case "nsTable":
			icon = "check";
			break;

		default:
			icon = "save";
			break;
	}
	return `
		<div class="actions">
			<div class="icon cancel" data-action="${neg}"></div>
			<div class="icon ${icon}" data-action="${pos}"></div>
		</div>
	`;
}

function newTab(e) {
	if (e && ((e.ctrlKey || e.metaKey) || e.button == 1)) {
		return true;
	}
	return false;
}

function openURL(page, e = null) {
	if (newTab(e)) {
		window.open(page);
	}
	else {
		window.location = page;
	}
}

function addPaymentMethod(token) {
	let data = {
		action: "addPaymentMethod",
		token: token
	};

	return api(data);
}

function deletePaymentMethod(card) {
	let data = {
		action: "deletePaymentMethod",
		card: card
	};

	return api(data);
}

function defaultPaymentMethod(card) {
	let data = {
		action: "defaultPaymentMethod",
		card: card
	};

	return api(data);
}

function checkCoupon(domain, coupon) {
	let data = {
		action: "checkCoupon",
		domain: domain,
		coupon: coupon
	};

	return api(data);
}

function autoRenew(domain, state) {
	let data = {
		action: "autoRenew",
		zone: domain,
		state: state
	};

	return api(data);
}

function salesEnabled(domain, state) {
	let data = {
		action: "salesEnabled",
		zone: domain,
		state: state
	};

	return api(data);
}

function isKey(e, key) {
	if (e.which == key || e.keyCode == key) {
		return true
	}
	return false;
}

$("html").on("click", ".blackoutCell", function(e){
	if ($(e.target).hasClass("blackoutCell")) {
		close();
	}
});

$(window).on("popstate", function(e) {
	let split = e.target.location.pathname.substring(1).split("/");

	if (split.length > 1) {
		page = split[0];

		if (linkContainsID(page)) {
			switch (page) {
				case "manage":
					zone = split[1];
					break;
			}
		}

		setZones();
	}
	else {
		page = split[0];
	}

	loadPage(1);
});

$("html").on("mousedown", ".icon.delete", function(e) {
	if (!isKey(e, 1)) {
		return;
	}

	let container = $(e.target).parent().find(".circle");
	let action = $(e.target).data("action");

	progress = new ProgressBar.Circle(container[0], {
	    color: css.getPropertyValue("--deleteColor"),
	    strokeWidth: 5,
	    text: {
	        value: ''
	    },
	    duration: 2000,
	});
	progress.animate(1.0, function(){
		if (container.children().length) {
			handleAction($(e.target), null, action);
		}
	});
});

$("html").on("mouseup", ".icon.delete", function(e){
	let container = $(e.target).parent().find(".circle");

	if (container.children().length) {
		progress.destroy();
	}
});

$("html").on("mouseout", ".icon.delete", function(e){
    $(this).mouseup();
});

$("html").on("click", function(e){
	scrollEditables();

	var p;

	var popover;

	let target = $(e.target);
	let action = target.data("action");
	let parent = target.parent();
	let grandParent = parent.parent();
	let row = target.closest("tr");
	if (!row.length) {
		row = target.closest(".row");
	}
	var section;

	var domain,price;

	let form = target.closest("form");

	let type = target[0].nodeName;
	if (type == "TH") {
		return;
	}

	if (grandParent.is(".editable .row")) {
		if (!target.hasClass("editing") && !target.hasClass("actionHolder")) {
			editField(target);
		}
	}
	else if (target.is(".link")) {
		if (target.hasClass("disabled")) {
			return;
		}

		switch (action) {
			case "manageDomain":
				changeZone(row.data("id"));
				page = "manage";
				loadPage();
				break;

			case "transferDomain":
				let zoneInfo = dataForZone(zone);
				$("#transferDomain input[name=zone]").val(zone);
				$("#transferDomain input[name=reserved]").val(0);
				$("#transferDomain .domain").html(zoneInfo.name);
				showPopover(action);
				break;

			case "transferReserved":
				let name = row.find(".items > .select").text();
				$("#transferDomain input[name=zone]").val(row.data("id"));
				$("#transferDomain input[name=reserved]").val(1);
				$("#transferDomain .domain").html(name);
				showPopover("transferDomain");
				break;

			case "changePrice":
				price = prettyMoney(dataForStaked(zone).price / 100);
				$("#changePrice input[name=price]").val(price);
				$("#changePrice input[name=zone]").val(zone);
				showPopover(action);
				break;

			case "addReserved":
				$("#addReserved input[name=zone]").val(zone);
				showPopover(action);
				break;

			case "addNotifications":
				showPopover(action);
				break;

			case "newNotification":
				popover = $(".popover[data-name=newNotification]");

				popover.find("select[name=type]").trigger("change");
				showPopover(action);
				break;

			case "newRecord":
				popover = $(".popover[data-name=newRecord]");

				popover.find("input[name=zone]").val(zone);
				popover.find("select[name=type]").trigger("change");
				showPopover(action);
				break;

			case "deleteRecord":
				let z = form.find("input[name=zone]").val();
				let r = form.find("input[name=record]").val();

				close("editRecord");
				$(".section[data-section=records] #dnsTable .row[data-id="+r+"]").remove();
				deleteRecord(z, r);
				break;

			case "deleteNotification":
				let n = form.find("input[name=notification]").val();

				close("editNotification");
				$(".section[data-section=notifications] #notificationTable .row[data-id="+n+"]").remove();
				deleteNotification(n);
				break;

			case "giftDomain":
				$("#giftDomain input[name=zone]").val(zone);
				showPopover(action);
				break;

			case "regenerateKey":
				regenerateKey().then(r => {
					if (r.success) {
						$("input[name=api]").val(r.key);
					}
				});
				break;

			case "tldLink":
				let link = "/tld/"+row.data("tld");
				openURL(link, e);
				break;

			case "defaultPaymentMethod":
				defaultPaymentMethod(row.data("id")).then(function(r){
					if (r.success) {
						row.closest(".table").find(".defaultPaymentMethod:not(.shown)").addClass("shown");
						row.find(".defaultPaymentMethod").removeClass("shown");
					}
					else {
						errorMessage();
					}
				});
				break;

			case "applyCoupon":
				let field = row.find(".edit");
				let code = field.text();
				domain = field.closest("form").find("input[name=domain]").val();
				break;

			case "moreSales":
				let table = $("#salesTable");
				let direction = target.data("direction");
				var oldPage = parseInt(table.data("page"));
				var newPage = parseInt(table.data("page"));

				switch (direction) {
					case "+":
						newPage += 1;
						break;

					case "-":
						if (oldPage > 1) {
							newPage -= 1;
						}
						break;
				}

				getSalesFor("table", newPage).then(function(response){
					handleSalesResponse(response, oldPage);
				});
				break;

			case "accountAction":
			case "accountActionAlt":
				p = target.data("page");
				swapAccountAction(p);
				break;

			case "scrollToSection":
				section = target.data("section");
				$(".body").animate({scrollTop: $(`.section[data-section=${section}]`).position().top - 20});
				break;

			case "renewDomain":
				let domainInfo = dataForZone(zone);
				domain = domainInfo.name;
				price = prettyMoney(domainInfo.price);
				$(".popover[data-name=completePurchase] .titleHolder .title").html("Renew");
				$(".popover[data-name=completePurchase] input[name=type]").val("renew");
				$(".popover[data-name=completePurchase] td.domain div").html(emojifyIfNeeded(domain));
				$(".popover[data-name=completePurchase] input[name=domain]").val(domain);
				$(".popover[data-name=completePurchase] td.price div.price").attr("data-price", price).data("price", price);
				$(".popover[data-name=completePurchase] td.price div.price").html(price);
				$(".popover[data-name=completePurchase] span.total").attr("data-price", price).data("price", price);
				$(".popover[data-name=completePurchase] span.total").html(price);
				$(".popover[data-name=completePurchase] td.years select").val("1");
				$(".popover[data-name=completePurchase] .submit").removeClass("disabled");
				updatePrices();
				showPopover("completePurchase");
				break;

			case "sectionAnchor":
				section = target.closest(".section").data("section");
				window.history.pushState(null, null, `/${page}#${section}`);
				break;
		}
	}
	else if (target.is(".actions .add")) {
		$.each(row.find("td.error"), function(key, r) {
			$(r).removeClass("error");
		});

		switch (action) {
			case "createZone":
				doCreateZone();
				break;

			case "addRecord":
				doAddRecord();
				break;

			case "addNotification":
				doAddNotification();
				break;
		}
	}
	else if (target.is(".actions .search")) {
		$.each(row.find("td.error"), function(key, r) {
			$(r).removeClass("error");
		});

		switch (action) {
			case "searchDomains":
				doSearchDomains(true);
				break;
		}
	}
	else if (parent.is(".actions")) {
		let edit = target.parent().parent().find(".edit");
		let cell = target.closest("div.item");

		if (cell.length) {
			let column = cell[0].classList[0];

			if (edit.length) {
				handleAction(edit, column, action);
			}
			else {
				if (action && !action.startsWith("delete")) {
					handleAction(target, column, action);
				}
			}
		}
		else {
			handleAction(target, null, action);
		}
	}
	else if (target.hasClass("action")) {
		handleAction(target, null, action);
	}
	else if (target.hasClass("editing")) {
		target.find(".edit").focus();
	}
});

$("html").on("focus", ".editable .edit", function(e){
	let table = $(e.target).closest(".table");
	let id = table.attr("id");
	if (id == "addRecordTable" || id == "addNotificationTable") {
		return;
	}

	let neg = table.data("neg");
	let pos = table.data("pos");

	if (!$(e.target).parent().hasClass("editing")) {
		$(e.target).parent().addClass("editing");
		$(e.target).parent().append(editActions(id, neg, pos));
	}
});

$("html").on("keydown", function(e){
	if (isKey(e, 27)) {
		let topMost = topMostPopover().data("name");
		if (topMost) {
			close(topMost);
		}
	}
});

$("html").on("keydown", ".edit[contenteditable=true]", function(e){
	if (isKey(e, 13)) {
		e.preventDefault();
	}
});

$("html").on("keyup", "#searchDomainsTable .edit[contenteditable=true]", function(e){
	if (isKey(e, 13)) {
		doSearchDomains(true);
	}
});

$("html").on("keyup", "#createZoneTable .edit[contenteditable=true]", function(e){
	if (isKey(e, 13)) {
		doCreateZone();
	}
});

$("html").on("keyup", ".section[data-section=record] .dnsTable .edit[contenteditable=true]", function(e){
	if (isKey(e, 13)) {
		doAddRecord();
	}
});

$("html").on("keyup", ".section[data-section=notification] .notificationTable .edit[contenteditable=true]", function(e){
	if (isKey(e, 13)) {
		doAddNotification();
	}
});

$("html").on("keyup", ".editable .edit[contenteditable=true]", function(e){
	var action;

	let table = $(e.target).closest(".table");

	if (isKey(e, 13)) {
		action = table.data("pos");
	}
	else if (isKey(e, 27)) {
		action = table.data("neg");
	}

	if (action) {
		let column = $(e.target).parent()[0].classList[0];
		handleAction($(e.target), column, action);
	}
});

$("html").on("change input focus blur paste", "input", function(e){
	let form = $(e.target).closest("form");
	cleanFields(form);
});

$("html").on("click", ".menu .item", function(e){
	if ($(this).hasClass("disabled")) {
		return;
	}

	let newPage = $(this).data("page");
	switch (newPage) {
		case "support":
			openURL("https://support.hshub.io", e);
			break;

		case "discord":
			openURL(discordLink, e);
			break;

		case "logout":
			logout().then(function(){
				goto("/");
			});
			break;

		case "sites":
			page = $(this).data("page");
			loadPage();
			loadZones();
			break;

		default:
			page = $(this).data("page");
			loadPage();
			break;
	}
});

$("html").on("change", ".header .domains", function(e){
	zone = $(this).val();
	loadPage();
});

$("html").on("change", ".autoRenew", function(e){
	var row = $(this).closest("tr");
	if (!row.length) {
		row = $(this).closest(".row");
	}
	let domain = row.data("id");
	let checked = $(this).is(":checked");
	autoRenew(domain, checked);
});

$("html").on("change", ".salesEnabled", function(e){
	var row = $(this).closest("tr");
	if (!row.length) {
		row = $(this).closest(".row");
	}
	let domain = row.data("id");
	let checked = $(this).is(":checked");
	salesEnabled(domain, checked).then(response => {
		dataForStaked(zone).live = checked;
	});
});

$("html").on("change", ".customNameservers", function(e){
	let checked = $(this).is(":checked");

	if (!checked) {
		resetNSDS(zone).then(function(response){
			getZone(zone).then(function(zoneResponse){
				setNS(zoneResponse.data);
				setDS(zoneResponse.data);
			});
		});
	}

	canEditNameservers(checked);
});

$("html").on("change", ".hnsPricing", function(e){
	let checked = $(this).prop("checked");
	$("input.hnsPricing").prop("checked", checked);
	swapPrices();
	updatePrices();
});

function generate2fa() {
	let data = {
		action: "generate2fa"
	};

	return api(data);
}

function regenerateKey() {
	let data = {
		action: "regenerateKey"
	}

	return api(data);
}

$("html").on("change", ".2fa", function(e) {
	let checked = $(this).prop("checked");
	if (checked) {
		generate2fa().then(function(response){
			if (response.success) {
				let data = response.data;
				let link = data.link;
				$(".popover[data-name=setup2fa] input[name=code]").val(data.code);
				showPopover("setup2fa");
				setupQR(link);
			}
			else {
				$(e.target).prop("checked", false);
			}
		});
	}
});

function canEditNameservers(bool) {
	if (bool) {
		customNameservers();
	}
	else {
		showNSDS();
	}

	makeNameserversEditable(bool);
}

function makeNameserversEditable(bool) {
	if (bool) {
		$("#nsTable").addClass("editable");
		$(".section[data-section=ns] .sectionAction").addClass("shown");

		$("#dsTable").addClass("editable");
		$(".section[data-section=ds] .sectionAction").addClass("shown");
	}
	else {
		$("#nsTable").removeClass("editable");
		$(".section[data-section=ns] .sectionAction").removeClass("shown");

		$("#dsTable").removeClass("editable");
		$(".section[data-section=ds] .sectionAction").removeClass("shown");
	}
}

function customNameservers() {
	nameservers = [];
	ds = "";

	var rows = $("#nsTable .row");
	rows.push($("#dsTable .row"));
	$.each(rows, function(k, row){
		$(row).find(".edit").text('');
	});

	padNameservers();
}

function padNameservers() {
	var rows = $("#nsTable .row");
	let count = rows.length;
	let add = 4 - count;
	for (var i = 0; i < add; i++) {
		$("#nsTable").append(nsRow("NS", ""));
	}
}

$("html").on("change", ".popover[data-name=completePurchase] td.years select", function(e){
	let years = $(this).val();
	let total = years * $(".popover[data-name=completePurchase] td.price div.price").data("price");
	$(".popover[data-name=completePurchase] span.total").html(prettyMoney(total));
	$(".popover[data-name=completePurchase] span.total").attr("data-price", total).data("price", total);
	updatePrices();
});

$("html").on("change", ".section[data-section=record] .dnsTable div.type select", function(e){
	let cell = $(".section[data-section=record] .dnsTable div.prio");
	let field = cell.find(".edit");

	if ($(this).val() == "MX") {
		cell.addClass("editing");
		field.attr("contenteditable", true);
	}
	else {
		cell.removeClass("editing");
		field.attr("contenteditable", false);
		field.text('');
	}

	$(this).closest(".row").data("type", $(this).val()).attr("data-type", $(this).val());
	$(".section[data-section=record] .dnsTable div.name .edit").focus();
});

$("html").on("change", ".popover[data-name=newRecord] select[name=type]", function(e){
	let table = $(this).closest("table");
	let prio = table.find("tr.prio");

	if ($(this).val() == "MX") {
		prio.removeClass("none");
	}
	else {
		prio.find("input").val('');
		prio.addClass("none");
	}

	$(this).closest("table").data("type", $(this).val()).attr("data-type", $(this).val());
	$(this).closest("table").find("tr.name input").focus();
});

$("html").on("change", ".section[data-section=notification] .notificationTable div.type select", function(e){
	$(this).closest(".row").data("type", $(this).val()).attr("data-type", $(this).val());
	$(".section[data-section=notification] .notificationTable div.name .edit").focus();
});

$("html").on("change", ".popover[data-name=newNotification] select[name=type]", function(e){
	$(this).closest("table").data("type", $(this).val()).attr("data-type", $(this).val());
	$(this).closest("table").find("tr.name input").focus();
});

$("html").on("submit", "form", function(e){
	e.preventDefault();

	if (typeof e.originalEvent === "object") {
		return;
	}

	let form = $(e.target);

	$.each(form.find("input.error"), function(key, r) {
		$(r).removeClass("error");
	});

	if (form.attr("id") === "completePurchase" && !user.length) {
		showPopover("account");
		swapAccountAction("login");
		resetButton(form);
		return;
	}

	if (form.attr("id") === "addPaymentMethod") {
		var fromPopover = false;
		if (form.closest(".popover").length) {
			fromPopover = true;
		}

		Stripe.card.createToken(form, function(status, response){
			if (response.error) {
				let param = response.error.param;
				form.find("input[data-stripe="+param+"]").addClass("error");
				alert(response.error.message);
				afterSubmit(form);
			}
			else {
				let token = response.id

				addPaymentMethod(token).then(function(r){
					if (r.success) {
						if (fromPopover) {
							close("addPaymentMethod");
						}

						if (page == "settings") {
							cleanFields(form);
							form[0].reset();
							$(".section[data-section=billing] #paymentMethodsTable").append(paymentMethodRow(r.data));
							updatePaymentMethods();
						}
					}
					else {
						errorMessage();
					}
					afterSubmit(form);
				});
			}
		});
	}
	else {
		let data = serializeObject($(this).serializeArray());
		api(data).then(function(r){
			if (!r.success) {
				let fields = r.fields;

				$.each(fields, function(key, field){
					form.find("input[name="+field+"]").addClass("error");
				});

				afterSubmit(form, r);
			}
			else {
				afterSubmit(form, r);
			}
		});
	}
});

function cleanFields(form) {
	form.find("input").removeAttr("data-com-onepassword-filled");
}

function afterLogin(form) {
	let redirect = form.find("input[name=redirect]").val();

	switch (page) {
		case "tld":
			close("account");
			break;

		default:
			if (redirect.length) {
				goto(redirect);
			}
			else {
				goto("/sites");
			}
			break;
	}
}

function afterSubmit(form, response) {
	resetButton(form);

	let action = form.find(".submit").data("action");
	switch (action) {
		case "login":
		case "signup":
			if (response.success) {
				user = response.uuid;
				$(".main").data("user", user);
				$(".main").attr("data-user", user);

				if (response.twofactor) {
					swapAccountAction("twofactor");
				}
				else {
					afterLogin(form);
				}
			}
			break;

		case "verify2fa":
			if (response.success) {
				afterLogin(form);
			}
			break;

		case "reset":
			if (response.newPassword) {
				goto("/login");
			}
			break;

		case "addPaymentMethod":
			cleanFields(form);
			break;

		case "completePurchase":
			if (!isHandshake() && response.needsPaymentMethod) {
				showAddPaymentMethod();
			}

			if (response.success) {
				if (response.data) {
					close(action);
					showHNSPayment(form, response.data);
				}
				else {
					finishedPurchase();
				}
			}
			break;

		case "transferDomain":
			if (response.success) {
				close(action);

				if (isStaked(zone)) {
					let zoneID = form.find("input[name=zone]").val();
					$("#reservedTable").find(".row[data-id="+zoneID+"]").remove();
				}
				else {
					page = "sites";
					loadPage();
					loadZones();
				}
			}
			break;

		case "addReserved":
			if (response.success) {
				close(action);
				loadReserved();
			}
			break;

		case "changePrice":
			if (response.success) {
				let zone = form.find("input[name=zone]").val();
				let price = form.find("input[name=price]").val() * 100;
				dataForStaked(zone).price = price;
				close(action);
			}
			break;

		case "giftDomain":
			if (response.success) {
				close(action);
			}
			break;

		case "addNotifications":
		case "newNotification":
			if (response.success) {
				close(action);
				loadNotifications();
			}
			break;

		case "editNotification":
			if (response.success) {
				let data = response.data;
				let notification = data.notification;
				let row = $("#notificationTable .row[data-id="+notification+"]");

				let name = data.name;
				if (!name) {
					name = "&nbsp;";
				}
				row.find(".mobileItems .name").html(name);
				row.find(".mobileItems .value").text(data.value);

				row.find(".items .name .edit").text(data.name);
				row.find(".items .value .edit").text(data.value);

				notifications[notification].name = data.name;
				notifications[notification].value = data.value;

				close(action);
			}
			break;

		case "settings":
			cleanFields(form);

			if (response.success) {
				form.find("input[type=password]").val('');

				if (response.newPassword) {
					logout().then(function(){
						goto("/login");
					});
				}
			}
			break;

		case "editRecord":
			if (response.success) {
				let data = response.data;
				let record = data.record;
				let row = $("#dnsTable .row[data-id="+record+"]");

				row.find(".mobileItems .name").text(data.name);
				row.find(".mobileItems .content").text(data.content);

				row.find(".items .name .edit").text(data.name);
				row.find(".items .content .edit").text(data.content);
				if (data.prio) {
					row.find(".items .prio .edit").text(data.prio);
				}
				row.find(".items .ttl .edit").text(data.ttl);

				records[record].name = data.name;
				records[record].content = data.content;
				records[record].prio = data.prio;
				records[record].ttl = data.ttl;

				close(action);
			}
			break;

		case "newRecord":
			if (response.success) {
				loadRecords();
				close(action);
			}
			break;

		case "setup2fa":
			if (response.success) {
				close(action);
			}
			break;

		case "appearance":
			if (response.success) {
				let newTheme = response.data.theme;
				let currentTheme = $("html").data("theme");

				if (newTheme !== currentTheme) {
					$("html").data("theme", newTheme).attr("data-theme", newTheme);
				}
			}
			break;
	}
}

function finishedPurchase() {
	switch (page) {
		case "tld":
			goto("/sites");
			break;

		default:
			close();
			page = "sites";
			loadPage();
			loadZones();
			break;
	}
}

function showAddPaymentMethod() {
	$("input[data-stripe=number]").mask("0000 0000 0000 0000");
	$("input[data-stripe=exp]").mask("00/00");
	$("input[data-stripe=cvc]").mask("000");
	showPopover("addPaymentMethod");
}

function showHNSPayment(form, data) {
	let type = form.find("input[name=type]").val();
	var action,outcome;

	switch (type) {
		case "register":
			action = "registration";
			outcome = "will appear in your account";
			break;

		case "renew":
			action = "renewal";
			outcome = "expiration date will be updated";
			break;
	}
	
	$(".popover[data-name=hnsPayment] .action").text(action);
	$(".popover[data-name=hnsPayment] .outcome").text(outcome);
	$(".popover[data-name=hnsPayment] .amount").text(data.amount);
	$(".popover[data-name=hnsPayment] .address").text(data.address);
	$(".popover[data-name=hnsPayment] .domain").text(emojifyIfNeeded(data.domain));
	$(".popover[data-name=hnsPayment] .expiry").data("expires", data.expires).attr("data-expires", data.expires);
	updatePurchaseExpiry();
	showPopover("hnsPayment");

	var expiryTimer = setInterval(function(){
		updatePurchaseExpiry();
	}, 1000);

	var verifyTimer = setInterval(function(){
		verifyPurchase(data.id).then(function(response){
			if (response.success) {
				if (response.data.paid) {
					clearInterval(expiryTimer);
					clearInterval(verifyTimer);
					finishedPurchase();
				}
				else if (response.data.expired) {
					clearInterval(expiryTimer);
					clearInterval(verifyTimer);
					$("form#hnsPayment .subtitle").text("Your invoice has expired. Please try again if you would still like to purchase this domain.");
					$("#hnsPaymentTable").addClass("none");
				}
			}
		});
	}, 10000);
}

function updatePurchaseExpiry() {
	let now = Date.now() / 1000;
	let expires = $(".popover[data-name=hnsPayment] .expiry").data("expires");
	let time = secondsToHuman(expires - now, true);
	let string = Object.values(time).join(" ");
	$(".expiresin .expiry").text(string);
}

$("html").on("click", ".submit", function(e){
	submitForm($(this));
});

$("html").on("keyup", "input", function(e){
	if (isKey(e, 13)) {
		submitForm($(this).closest("form").find(".submit"));
	}
});

function swapAccountAction(action) {
	let form = $("#accountForm");

	if (action === "twofactor") {
		action = "verify2fa";
	}

	form.find("input[name=action]").val(action);
	form.find(".submit").data("action", action);
	form.find(".submit").attr("data-action", action);
	form.find(".submit").html(action.capitalize());
	form.find("input").addClass("none");
	
	switch (action) {
		case "login":
			form.find(".link[data-action=accountAction]").data("page", "signup");
			form.find(".link[data-action=accountAction]").attr("data-page", "signup");
			form.find(".link[data-action=accountAction] span").html("signup");
			form.find(".link[data-action=accountAction]").removeClass("hidden");
			form.find(".link[data-action=accountActionAlt]").removeClass("hidden");
			form.find("input[name=email]").removeClass("none");
			form.find("input[name=password]").removeClass("none");
			break;

		case "signup":
			form.find(".link[data-action=accountAction]").data("page", "login");
			form.find(".link[data-action=accountAction]").attr("data-page", "login");
			form.find(".link[data-action=accountAction] span").html("login");
			form.find(".link[data-action=accountAction]").removeClass("hidden");
			form.find(".link[data-action=accountActionAlt]").addClass("hidden");
			form.find("input[name=email]").removeClass("none");
			form.find("input[name=password]").removeClass("none");
			break;

		case "forgot":
			form.find(".link[data-action=accountAction]").data("page", "signup");
			form.find(".link[data-action=accountAction]").attr("data-page", "signup");
			form.find(".link[data-action=accountAction] span").html("signup");
			form.find(".link[data-action=accountAction]").removeClass("hidden");
			form.find(".link[data-action=accountActionAlt]").addClass("hidden");
			form.find("input[name=email]").removeClass("none");
			break;

		case "reset":
			form.find(".link[data-action=accountAction]").addClass("hidden");
			form.find(".link[data-action=accountActionAlt]").addClass("hidden");
			form.find("input[name=password]").attr("autocomplete", "new-password").attr("name", "new-password");
			form.find("input[name=new-password]").removeClass("none");
			form.find("input[name=code]").val(code);
			break;

		case "verify2fa":
			form.find(".link").addClass("hidden");
			form.find("input[name=twofactor]").removeClass("none");
			form.find(".submit").html("Verify");
			break;
	}

	if (page === "tld") {
		form.find(".link.hidden").addClass("none");
		form.find(".link.none:not(.hidden)").removeClass("none");
	}

	form.removeClass("hidden");
	form.find("input:visible").first().focus();
}

function submitForm(el) {
	let form = el.closest("form");
	if (!form.length) {
		handleAction(el, null, el.data("action"));
		return;
	}

	if (el.hasClass("disabled")) {
		return;
	}

	el.addClass("disabled");
	el.closest("form").submit();
}

function resetButton(form) {
	let button = form.find(".submit");
	button.removeClass("disabled");
}

function goto(page) {
	window.location = page;
}

function loadZones() {
	zones = [];
	staked = [];

	getSLDS().then(function(s){
		let d = s.data;

		$.each(d, function(k, zone){
			zones.push(zone);
		});
		
		getZones().then(function(z){
			let d = z.data;

			$.each(d, function(k, zone){
				zones.push(zone);
			});

			if (zones.length > 1) {
				zones.sort(function(a, b){
					return a.name.localeCompare(b.name);
				});
			}

			getMyStaked().then(function(s){
				let t = s.data;

				$.each(t, function(k, tld){
					staked.push(tld);
				});

				if (staked.length > 1) {
					staked.sort(function(a, b){
						return a.tld.localeCompare(b.tld);
					});
				}

				setZones();
				loadPage(1);
				updateMenu();
			});
		});
	});
}

function secondsToHuman(seconds, withSeconds=false) {
	let h = Math.floor((seconds % 86400) / 3600);
	let m = Math.floor((seconds % 3600) / 60);
	let s = parseInt(seconds - (h * 3600) - (m * 60));

	var time = {}
	if (h) {
		time.h = h+"h";
	}
	if (m) {
		time.m = m+"m";
	}

	if (withSeconds) {
		if (s) {
			time.s = s+"s";
		}
	}
	
	return time;
}

function updateInfo() {
	getInfo().then(function(response){
		if (response.success) {
			let blocks = response.data.nextUpdate;
			let price = response.data.price;

			if (price) {
				hnsPrice = price;
			}

			if (!blocks) {
				$("#nextUpdate span").html("6h");
			}
			else {
				let seconds = (blocks * 10) * 60;
				let time = secondsToHuman(seconds);
				let string = Object.values(time).join(" ");
				$("#nextUpdate span").html(string);
			}
		}
	});
}

function showTooltip(e, id, message) {
	let tooltip = $('<div class="tooltip" data-id="'+id+'" />');
	tooltip.html(message);

	let arrow = $('<div class="arrow" />');
	tooltip.append(arrow)

	$("body").append(tooltip);
	setTooltipPosition(e, tooltip);
}

function hideTooltip(id) {
	$(".tooltip[data-id="+id+"]").remove();
}

function setTooltipPosition(e, tooltip) {
	var hx = window.innerWidth / 2;
	var hy = window.innerHeight / 2;

	let w = tooltip.outerWidth();
	let h = tooltip.outerHeight();
	
	var x  = $(e.target)[0].getBoundingClientRect().left + $(window)["scrollLeft"]() - ((w / 2) - 4);
	var y  = $(e.target)[0].getBoundingClientRect().top + $(window)["scrollTop"]() - (h + 12);

	tooltip.css({ top: y, left: x });
	tooltip.find(":after").css({ left: (w / 2) - 4 });
}

$("html").on("mouseenter", ".icon.delete", function(e){
	let id = $(this).closest(".row").data("id");
	showTooltip(e, id, "Hold to delete");
});

$("html").on("mouseleave", ".icon.delete", function(e){
	let id = $(this).closest(".row").data("id");
	hideTooltip(id);
});

$("html").on("mouseenter", "#domainTable .row .icon.error, #actionTable .row .icon.error", function(e){
	let id = $(this).closest(".row").data("id");
	let domainInfo = dataForZone(id);
	let graceEnd = new Date(domainInfo.expiration * 1000);
	graceEnd.setDate(graceEnd.getDate() + 30);
	let daysUntilGraceEnd = Math.floor(((graceEnd.getTime() - Date.now()) / 1000) / 86400);
	showTooltip(e, id, `Grace period ends in ${daysUntilGraceEnd} days`);
});

$("html").on("mouseleave", "#domainTable .row .icon.error, #actionTable .row .icon.error", function(e){
	let id = $(this).closest(".row").data("id");
	hideTooltip(id);
});

$("html").on("mouseenter", "#domainTable .row .icon.warning, #actionTable .row .icon.warning", function(e){
	let id = $(this).closest(".row").data("id");
	let domainInfo = dataForZone(id);
	let expiration = new Date(domainInfo.expiration * 1000);
	let daysUntilExpiration = Math.floor(((expiration.getTime() - Date.now()) / 1000) / 86400);
	showTooltip(e, id, `Expires in ${daysUntilExpiration} days`);
});

$("html").on("mouseleave", "#domainTable .row .icon.warning, #actionTable .row .icon.warning", function(e){
	let id = $(this).closest(".row").data("id");
	hideTooltip(id);
});

function isMobile() {
	if ($(".hamburger").is(":visible")) {
		return true;
	}
	return false;
}

function menuShowing() {
	if ($(".menu").is(":visible")) {
		return true;
	}
	return false;
}

$("html").on("click", ".hamburger", function(e){
	$(this).toggleClass("open");

	if ($(this).hasClass("open")) {
		$(".menu").addClass("shown");
	}
	else {
		$(".menu").removeClass("shown");
	}
});

function setupUpdateInfo() {
	updateInfo();
	setInterval(updateInfo, 300000);
}

function scrollToSectionIfNeeded() {
	if (window.location.hash) {
		let section = window.location.hash.substring(1);
		$(`.link[data-action=scrollToSection][data-section=${section}]`).click();
	}
}

$(function(){
	user = $(".main").data("user");
	page = $(".main").data("page");
	zone = $(".main").data("zone");
	tld = $(".main").data("tld");
	code = $(".main").data("code");

	switch (page) {
		case "login":
		case "signup":
		case "forgot":
		case "reset":
		case "twofactor":
			swapAccountAction(page);
			break;

		case "tld":
			loadPage(1);
			setupUpdateInfo();
			break;

		case "sites":
		case "manage":
		case "staking":
			loadZones();
			setupUpdateInfo();
			break;

		default:
			loadPage(1);
			setupUpdateInfo();
			break;
	}
});