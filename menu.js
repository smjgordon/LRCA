var openMenus = new Array();
// use to suppress hiding of the menu we've just opened
var menuShowing = '';
// use to keep track of which menus we have open at the moment
var openMenus = [];

function showMenu(menuName) {
	var menu = document.getElementById(menuName);
	menuShowing = menuName;
	if (openMenus[menuName]) {
		menu.style.visibility = 'hidden';
		menu.style.display = 'none';
		openMenus[menuName] = false;
	} else {
		while (true) {
			menu.style.visibility = 'visible';
			menu.style.display = 'block';
			openMenus[menuName] = true;
			if (menuName.indexOf('.') != -1) {
				menuName = menuName.substring(0, menuName.indexOf('.'));
				menu = document.getElementById(menuName);
			} else {
				break;
			}
		}
	}
	return false;
}

function hideMenus() {
	for (var menuName in openMenus) {
		if (openMenus[menuName] && !menuShowing.startsWith(menuName)) {
			var menu = document.getElementById(menuName);
			menu.style.visibility = 'hidden';
			menu.style.display = 'none';
			openMenus[menuName] = false;
		}
	}
	menuShowing = '';
}
