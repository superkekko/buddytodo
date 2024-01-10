//check device type
function mobileType (){
	const userAgent = window.navigator.userAgent.toLowerCase();
	if(/iphone|ipad|ipod/.test( userAgent )){
		return "ios";
	} else if(/android/.test( userAgent )){
		return "android";
	}
}

//check if pwa is already installed
function pwaInstalled(){
	if(('standalone' in window.navigator) && (window.navigator.standalone)){
		return true
	}else{
		return false
	}
}

// service worker
function registerServiceWorker() {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').then(function(registration) {
      console.log('Service worker registration succeeded:', registration);
      return true;
    },function(error) {
      console.log('Service worker registration failed:', error);
      return false;
    });
  } else {
    console.log('Service workers are not supported.');
    return false;
  }
}

//checks if Push notification and service workers are supported by your browser
function isPushNotificationSupported() {
  return "serviceWorker" in navigator && "PushManager" in window;
}

//asks user consent to receive push notifications and returns the response of the user, one of granted, default, denied
function initializePushNotifications() {
  // request user grant to show notification
  return Notification.requestPermission(function(result) {
  	console.log(result);
  	$('#push-notification').modal('hide');
    return result;
  });
}

async function requestNotificationPermission() {
	$('#push-notification').modal('hide');
	const permission = await Notification.requestPermission();
	//alert("Mex: "+permission);
	setCookie('pwa-permission', permission, {expires:1, domain: window.location.host, Secure: true, 'max-age': 1, SameSite: 'Lax'})
}

async function notification(){
	const pushNotificationSuported = isPushNotificationSupported();
	
	if (pushNotificationSuported) {
		const permissionStatus = await navigator.permissions.query({ name: 'notifications' });
		//alert("Mex: "+permissionStatus.state);
		let cookiePermissionStatus;
		
		if(typeof getCookie('pwa-permission') === 'undefined'){
			cookiePermissionStatus = 'prompt';
		}else{
			cookiePermissionStatus = getCookie('pwa-permission');
		}
		if(permissionStatus.state == 'granted' || cookiePermissionStatus == 'granted'){
			if(typeof getCookie('pwa-notification-sent') === 'undefined'){
				readNotification();
			}
			await setBadge();
		}else if (permissionStatus.state == 'prompt' || permissionStatus.state == 'default' ){
			$('#push-notification').modal('show');
			setCookie('pwa-notification-request', true, {expires:1, domain: window.location.host, Secure: true, 'max-age': 1, SameSite: 'Lax'})
		}
	}
	
	await new Promise(resolve => setTimeout(resolve, 10000));
	await notification();
}

async function readNotification(){
	let response = await fetch("/read?type=notification");

	if (response.status != 200) {
		return false;
	} else {
		// Otteniamo e mostriamo il messaggio
		let result = await response.json();
		if (typeof result !== 'undefined'){
			result.forEach(function(item) {
			    console.log(item);
			    sendNotification('BuddyToDo notification', "Expired task: "+item.name, "/img/logo.png", '/img/logo-96-monochrome.png');
			    setCookie('pwa-notification-sent', true, {expires:1, domain: window.location.host, Secure: true, 'max-age': 1, SameSite: 'Lax'})
			});
		}
	}
}

//shows a notification
function sendNotification(titleIn, bodyIn, iconIn, badgeIn, linkIn="") {
  const options = {
    body: bodyIn,
    icon: iconIn,
    vibrate: [200, 100, 200],
    badge: badgeIn,
    data:{
    	link: linkIn	
    }
  };
  navigator.serviceWorker.ready.then(function(serviceWorker) {
    serviceWorker.showNotification(titleIn, options);
  });
}

//show badge icon function
async function setBadge(count="") {
if ('setAppBadge' in navigator) {
  try {
  		if(count==""){
  			let response = await fetch("/read?type=notificationCount");
	
			let result = await response.json();
			await navigator.setAppBadge(result['count']);
  		}else{
  			await navigator.setAppBadge(count);
  		}	
	} catch (error) {
      console.error('Failed to set app badge:', error);
    }
  }
}

// clear badge icon function
async function clearBadge() {
  if ('clearAppBadge' in navigator) {
    try {
      await navigator.clearAppBadge();
    } catch (error) {
      console.error('Failed to clear app badge:', error);
    }
  }
}