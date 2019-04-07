<?
/* iReporter complete web demo project
 *
 * index.php takes care to check the "command" request
 * and call the proper API function to process the user request
 * 
 */
 
// this line starts the server session - that means the server will "remember" the user
// between different API calls - ie. once the user is authorized, he will stay logged in for a while
session_start();

// the requre lines include the lib and api source files
require("lib.php");
require("api.php");

// this instructs the client (in this case the iPhone app) 
// that the server will output JSON data
header("Content-Type: application/json");

// the iPhone app sends over what "command" of the API it wants executed
// the tutorial covers "login","register","upload", "logout" and "stream"
// so using a switch statement for this taks makes most sense

// the functions you call inside the switch are found in the api.php file
switch ($_POST['command']) {
	case "login":
		login($_POST['username'], $_POST['password']); 
		break;
 
	case "register":
		register($_POST['username'], $_POST['password']); 
		break;
 
	case "upload":
		upload($_SESSION['IdUser'], $_FILES['file'], $_POST['title']);
		break;
	
	case "logout":
		logout();
		break;

	case "stream":
		stream((int)$_POST['IdPhoto']);
		break;
		
	}

// this line is redundant as the file ends anyway, 
// but just making sure no more code gets executed
exit();
