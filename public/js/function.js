//enable debug console logs
var debug = false;

function uid(){
	return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

//ajax calls
var myRequest = null;

function CreateXmlHttpReq(handler) {
	var xmlhttp = null;
	xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = handler;
	return xmlhttp;
}

function myHandler() {
  if (myRequest.readyState == 4 && myRequest.status == 200) {
	  if(debug){console.log(myRequest.responseText);}
  }
}

//read data from table (sync)
function getdata(page,param){
	dataObj = {};
	call = page + "?";
	var i = 1;
	len = Object.keys(param).length;
	for (const [key, value] of Object.entries(param)) {
		call = call + key +"="+ value;
		if(i<len){
			call = call +"&";
		}
		i++;
	}
	if(debug){console.log(call);}
	myRequest = CreateXmlHttpReq(myHandler);
	myRequest.open("GET",call, false);
	myRequest.send();
	if (myRequest.status == 502) {
		return {}
	} else if (myRequest.status != 200) {
		return {}
	} else {
		dataObj = JSON.parse(myRequest.responseText);
		if(debug){console.log(dataObj);}
		return dataObj;
	}
}

//write data in table (async)
function postdata(page,param){
	call = "";
	var i = 1;
	len = Object.keys(param).length;
	for (const [key, value] of Object.entries(param)) {
		call = call + key +"="+ value;
		if(i<len){
			call = call +"&";
		}
		i++;
	}
	myRequest = CreateXmlHttpReq(myHandler);
	myRequest.open("POST",page);
	myRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	myRequest.send(call);
	if(debug){console.log(page, call);}
}

//create/delete/get cookie (option: {expires:1, domain: 'domain.com', Secure: true, 'max-age': 1, SameSite: 'strict'}) !! the time is in days !!
function setCookie(name, value, options = {}) {
  options = {
    path: '/',
    // aggiungi altri percorsi di default se necessario
    ...options
  };
  
  if (options.expires != null) {
  	let date = new Date();
    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
    options.expires = date.toUTCString();
  }
  
  var maxAge = options["max-age"];
  if (maxAge != null) {
    options["max-age"] = maxAge * 24 * 60 * 60;
  }

  let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

  for (let optionKey in options) {
    updatedCookie += "; " + optionKey;
    let optionValue = options[optionKey];
    if (optionValue !== true) {
      updatedCookie += "=" + optionValue;
    }
  }

  document.cookie = updatedCookie;
}

function deleteCookie(name,host) {
	setCookie(name, '',
		{expires:-1,
		domain: host,
		Secure: true,
		'max-age': -3600,
		SameSite: 'strict'
	});
}

function getCookie(name) {
    var i,x,y,ARRcookies=document.cookie.split(";");

    for (i=0;i<ARRcookies.length;i++)
    {
        x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
        y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
        x=x.replace(/^\s+|\s+$/g,"");
        if (x==name)
        {
            return unescape(y);
        }
     }
}