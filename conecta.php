<?php

  require_once("WS_Curl_class.php");
  //
  // provide user's credentials
  //
  // CRM URL
  $url = "http://132.248.122.211/vtigercrm/webservice.php";
  //
  // CRM user (CRM login name)
  $username = "admin";
  //
  // CRM user's access key (from My preferences menu)
  $accessKey = "l1XsmX87trP4kHy9";
  //
  //========== EXAMPLE 1: Login Start ==================================================================+
  //
  // create web service object
  $wsC = new WS_Curl_Class($url, $username, $accessKey);
  //
  // Login
  // the Login procedure requires two API operations which are exercised by the Curl Class in one step
  // step 1: get a token (session id)
  // step 2: use token for login
  if (!$wsC->login()) {
  	// ERROR handling if Login was not successful
  	echo $wsC->errorMsg;
  }


  //=========== Modulos a los que puede acceder el usuario actual -------------------------------------------
  // call listTypes API operation
/*  $listTypes = $wsC->operation("listTypes", array(), "GET");
  if (!$listTypes) {
  	// ERROR handling if listTypes operation was not successful
  	echo $wsC->errorMsg;
  }
  else {
  	echo "<b>Modules which can be reached by web services with current user</b><br>";
  	echo "<table border='1'>";
  		foreach ($listTypes['types'] as $value){
  			echo "<tr>";
  				echo "<td>".$value."</td>";
  			echo "</tr>";
  		}
  	echo "</table></br>";
  }



  $describe = $wsC->operation("describe", array("elementType" => "Contacts"), "GET");

  if (!$describe) {
  	// ERROR handling if describe operation was not successful
  	echo $wsC->errorMsg;
  }
  else {
  	echo "<b>Contacts Fields:</b><br>";
  	echo "<table border='1'>";
  		echo "<tr>";
  			echo "<th>Field Label</th>";
  			echo "<th>CRM Field Name</th>";
  		echo "</tr>";
  		foreach ($describe['fields'] as $pkey => $value){
  			//show field properties
  			if ($pkey == 'fields') {
  				foreach ($describe['fields'] as $fkey => $fvalue){
  					echo "<tr>";
  						echo "<td>".$fvalue['label']."</td>";
  						echo "<td>".$fvalue['name']."</td>";
  					echo "</tr>";
  				}
  			}
  		}
  	echo "</table></br>";
  }
*/

$first_contact_id ='';
$Contact_Details = $wsC->operation("query", array("query" => "SELECT * FROM Contacts limit 1;"), "GET");
if ($wsC->errorMsg) {
	// ERROR handling if describe operation was not successful
	echo $wsC->errorMsg;
}
else {
	//keep Contact ID for next example 5
	$first_contact_id = $Contact_Details[0]['id'];
	echo "contact id: ".$first_contact_id."<br>";
}

if ($first_contact_id !='') {
	$Contact_Details = $wsC->operation("retrieve", array("id" => $first_contact_id), "GET");
	if ($wsC->errorMsg) {
		// ERROR handling if describe operation was not successful
		echo $wsC->errorMsg;
	}
	else {
		echo "First Name: ".$Contact_Details['firstname']."<br>";
		echo "Last Name: ".$Contact_Details['lastname']."<br>";
		echo "Related Account ID: ".$Contact_Details['account_id']."<br>";
	}
}



$Contact_Count = $wsC->operation("query", array("query" => "SELECT count(*) FROM Leads;"), "GET");
if ($wsC->errorMsg) {
	// ERROR handling if describe operation was not successful
	echo $wsC->errorMsg;
}
else {
	$number_of_contacts = $Contact_Count[0]['count'];
	echo "Contacts Count: ".$number_of_contacts."<br>";
	//if there are more than 100 entries we have to loop to get all contacts
	$num_loop_pages = ceil($number_of_contacts / 100);
	for ($i = 0; $i < $num_loop_pages; ++$i) {
		$offset = $i*100;
		$wsquery = "SELECT * FROM Leads limit ".$offset.", 100;";
		$Contact_List[$i] = $wsC->operation("query", array("query" => $wsquery), "GET");
		if ($wsC->errorMsg) {
			// ERROR handling if query operation was not successful
			echo $wsC->errorMsg;
		}
	}
	//show table entries for every Contact
	echo "<b>List of Contacts</b><br>";
		echo "<table border='1'>";
			echo "<tr>";
				echo "<th>Contact NO</th>";
				echo "<th>Last Name</th>";
				echo "<th>First Name</th>";
        echo "<th>Correo</th>";
			echo "</tr>";
		foreach ($Contact_List as $valueblocks){
			foreach ($valueblocks as $value){
				echo "<tr>";
					echo "<td>".$value["lead_no"]."</td>";
					echo "<td>".$value["lastname"]."</td>";
					echo "<td>".$value["firstname"]."</td>";
          echo "<td>".$value["email"]."</td>";
				echo "</tr>";
			}
		}
		echo "</table></br>";

}
