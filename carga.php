<?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  require_once(dirname(__FILE__).'/class/ws-curl-class.php');
  require_once(dirname(__FILE__).'/class/db.php');
  require_once(dirname(__FILE__).'/class/st-php-logger.php');
  date_default_timezone_set('America/Mexico_City');


  $dbhost    = 'localhost';
  $dbuser    = 'unila';
  $dbpass    = 'unila2019.';
  $dbname    = 'unila';
  $url       = 'http://132.248.122.211/vtigercrm/webservice.php';
  $username  = "admin";
  $accessKey = "l1XsmX87trP4kHy9"; // CRM user's access key (from My preferences menu)
  $time      = date('d-m-Y');
  $fileDate  = 'fecha.dat';

  //-----------------------------------------------------------
  // Iniciamos Logs
  //-----------------------------------------------------------
  $log = new snowytech\stphplogger\logWriter('logs/log-' . $time . '.log');

  $log->info('Inica importacion de Leads');


  //-----------------------------------------------------------
  // Leemos fecha de ultima ejecucion
  //-----------------------------------------------------------
  try {
    if (!file_exists($fileDate)){
      throw new Exception("Archivo de Fecha no encontrado.");
      $log->error('Archivo de Fecha no encontrado.');
    }

    $fp    = fopen($fileDate, "r");
    $linea = fgets($fp);
    $log->info('Se obtiene ultima fecha obtenida: '.$linea);

    fclose($fp);

  }catch (Exception $e) {
    echo $e->getMessage();
  }

  $fecha = explode(" ",$linea);



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
  //$SQL   = 'SELECT * FROM leads LIMIT 30';
  $SQL = <<<EOT
      SELECT DISTINCT
        mayusculaMinuscula(SUBSTRING_INDEX(SUBSTRING_INDEX(a.name, ' ', 1), ' ', -1)) AS nombre,
        mayusculaMinuscula (TRIM( SUBSTR(a.name, LOCATE(' ', a.name)) )) AS apellidos,
        email AS correo, b.nombre AS nivel, origin AS origen,
        TRIM(REPLACE(c.nombre, 'Campus','')) AS campus,
        phone AS telefono, cellphone AS celular, d.nombre AS carrera, date AS fecha, hour AS hora
      FROM registry a
        INNER JOIN nivel_nivel b        ON a.nivel   = b.id
        INNER JOIN campus_campus c      ON a.campus  = c.id
        INNER JOIN programa_programa d  ON a.program = d.id
      WHERE  (a.date BETWEEN '{$fecha[0]}' AND DATE ) AND (a.hour BETWEEN '{$fecha[1]}' AND DATE_FORMAT(NOW( ), "%H:%i:%S" ) )
      ORDER By a.date ASC, a.hour ASC;
EOT;


  $leads = $db->query($SQL)->fetchAll();

  $type  = 'Leads';

  foreach ($leads as $lead) {
    $element = array(
      'salutationtype'   =>'Sr',
      'firstname'        => trim($lead['nombre']),
      'lastname'         => trim($lead['apellidos']),
      'email'            => trim($lead['correo']),
      'phone'            => trim($lead['telefono']),
      'nivel'            => trim($lead['nivel']),
      'leadsource'       => trim($lead['origen']),
      'cf_864'           => trim($lead['carrera']),
      'cf_858'           => trim($lead['campus']),
      'assigned_user_id' => '19x5',     //assign to user marketing, groups would have the prefix 20
    );
    /*$result = $wsC->operation("create",
                              array("elementType" => $type,
                                    "element"     => json_encode($element)),
                              "POST");*/
  }

  if ($wsC->errorMsg) {
    // ERROR handling if describe operation was not successful
    echo $wsC->errorMsg;
  }

  $fp       = fopen($fileDate, "w");
  $DateNow  = date('Y-m-d H:i:s');

  fputs($fp, $DateNow); //Guardamos la FEcha de la ultima ejecucion
  fclose($fp);

  $log->info('Guardamos fecha de ejecucion: '. $DateNow);
  $db->close();
