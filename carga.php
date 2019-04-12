<?php

  require_once("class/WS_Curl_class.php");
  require_once("class/db.php");
  require_once("class/st-php-logger.php");


  $dbhost    = '132.248.122.211';
  $dbuser    = 'unila';
  $dbpass    = 'unila2019.';
  $dbname    = 'unila';
  $url       = "http://132.248.122.211/vtigercrm/webservice.php";
  $username  = "admin";
  $accessKey = "l1XsmX87trP4kHy9"; // CRM user's access key (from My preferences menu)
  $time      = date('d-m-Y');


  //-----------------------------------------------------------
  // Iniciamos Logs
  //-----------------------------------------------------------
  $log = new snowytech\stphplogger\logWriter('logs/log-' . $time . '.log');

  $log->info('Inica importacion de Leads');


  //-----------------------------------------------------------
  // Obtenemos Conexion a fuente de datos externa
  //-----------------------------------------------------------
  $db = new db($dbhost, $dbuser, $dbpass, $dbname);


   //-------------------------------------------------------------
   // Conectamos a Api del CRM
   //-------------------------------------------------------------
   $wsC = new WS_Curl_Class($url, $username, $accessKey);
   if (!$wsC->login()) {
     echo $wsC->errorMsg;  // ERROR handling if Login was not successful
   }


  //--------------------------------------------------------------
  // Conectamos a vista de Origen de datos de leads
  //--------------------------------------------------------------
  $leads = $db->query('SELECT * FROM leads LIMIT 5')->fetchAll();
  $type  = 'Leads';

  foreach ($leads as $lead) {
    $element = array(
      'lastname'=>trim($lead['apellidos']),
      'firstname'=>trim($lead['nombre']),
      'email'=>trim($lead['email']),
      'phone'=>trim($lead['telefono']),
      'assigned_user_id'=> '19x5',     //assign to user marketing, groups would have the prefix 20
    );
  }

  $result = $wsC->operation("create", array("elementType" => $type, "element" => json_encode($element)), "POST");

  if ($wsC->errorMsg) {
    // ERROR handling if describe operation was not successful
    echo $wsC->errorMsg;
  }

  $log->info('Termina importacion de Leads');
