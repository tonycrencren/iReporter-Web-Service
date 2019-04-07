<?

// helper function, which outputs error messages in JSON format
// so that the iPhone app can read them
// the function just takes in a dictionary with one key "error" and 
// encodes it in JSON, then prints it out and then exits the program
function errorJson($msg){
	print json_encode(array('error'=>$msg));
	exit();
}

// register API
function register($user, $pass) {

	//check if username exists in the database (inside the "login" table)
	$login = query("SELECT username FROM login WHERE username='%s' limit 1", $user);

	if (count($login['result'])>0) {

		//the username exists, return error to the iPhone app
		errorJson('Username already exists');
	}

	//try to insert a new row in the "login" table with the given username and password
	$result = query("INSERT INTO login(username, pass) VALUES('%s','%s')", $user, $pass);

	if (!$result['error']) {
		//registration is susccessfull, try to also directly login the new user
		login($user, $pass);
	} else {
		//for some database reason the registration is unsuccessfull
		errorJson('Registration failed');
	}

}

//login API
function login($user, $pass) {
	
	// try to match a row in the "login" table for the given username and password
	$result = query("SELECT IdUser, username FROM login WHERE username='%s' AND pass='%s' limit 1", $user, $pass);
 
	if (count($result['result'])>0) {
		// a row was found in the database for username/pass combination
		// save a simple flag in the user session, so the server remembers that the user is authorized
		$_SESSION['IdUser'] = $result['result'][0]['IdUser'];
		
		// print out the JSON of the user data to the iPhone app; it looks like this:
		// {IdUser:1, username: "Name"}
		print json_encode($result);
	} else {
		// no matching username/password was found in the login table
		errorJson('Authorization failed');
	}
	
}

//upload API
function upload($id, $photoData, $title) {

	// index.php passes as first parameter to this function $_SESSION['IdUser']
	// $_SESSION['IdUser'] should contain the user id, if the user has already been authorized
	// remember? you store the user id there in the login function
	if (!$id) errorJson('Authorization required');
 
	// check if there was no error during the file upload
	if ($photoData['error']==0) {
	
		// insert the details about the photo to the "photos" table
		$result = query("INSERT INTO photos(IdUser,title) VALUES('%d','%s')", $id, $title);
		if (!$result['error']) {
 
			// fetch the active connection to the database (it's initialized automatically in lib.php)
			global $link;
		 
			// get the last automatically generated ID in the photos table
			$IdPhoto = mysqli_insert_id($link);
		 
			// move the temporarily stored file to a convenient location
			// your photo is automatically saved by PHP in a temp folder
			// you need to move it over yourself to your own "upload" folder
			if (move_uploaded_file($photoData['tmp_name'], "upload/".$IdPhoto.".jpg")) {

				// file moved, all good, generate thumbnail
				thumb("upload/".$IdPhoto.".jpg", 180);
				
				//just print out confirmation to the iPhone app
				print json_encode(array('successful'=>1));
			} else {
				//print out an error message to the iPhone app
				errorJson('Upload on server problem');
			};
 
		} else {
			errorJson('Upload database problem.'.$result['error']);
		}
	} else {
		errorJson('Upload malfunction');
	}
}

//logout API
function logout() {

	// by saving an empty array to $_SESSION you are
	// effectively destroying all the user session data
	// ie. the server won't "remember" anymore anything about
	// the current user
	$_SESSION = array();
	
	// and to make double-sure, there's also a built-in function 
	// which wipes out the user session
	session_destroy();
}

//stream API
//
// there are 2 ways to use the function:
// 1) don't pass any parameters - then the function will fetch all photos from the database
// 2) pass a photo id as a parameter - then the function will fetch the data of the requested photo
//
// Q: what "$IdPhoto=0" means? A: It's the PHP way to say "first param of the function is $IdPhoto, 
// if there's no param sent to the function - initialize $IdPhoto with a default value of 0"
function stream($IdPhoto=0) {

	if ($IdPhoto==0) {

		// load the last 50 photos from the "photos" table, also join the "login" so that you can fetch the 
		// usernames of the photos' authors
		$result = query("SELECT IdPhoto, title, l.IdUser, username FROM photos p JOIN login l ON (l.IdUser = p.IdUser) ORDER BY IdPhoto DESC LIMIT 50");

	} else {
		//do the same as above, but just for the photo with the given id
		$result = query("SELECT IdPhoto, title, l.IdUser, username FROM photos p JOIN login l ON (l.IdUser = p.IdUser) WHERE p.IdPhoto='%d' LIMIT 1", $IdPhoto);
	}
 
	if (!$result['error']) {
		// if no error occured, print out the JSON data of the 
		// fetched photo data
		print json_encode($result);
	} else {
		//there was an error, print out to the iPhone app
		errorJson('Photo stream is broken');
	}
}