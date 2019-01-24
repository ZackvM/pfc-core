<?php

//phpinfo();
/* PFCData.CHTNEAST.ORG GATEWAY TO PFC/PFRP DATA MANAGEMENT INDEX */

/*
 * REQUIRED SERVER DIRECTORY STRUCTURE
 * /srv/chtneastapp = applicationTree - Can be changed if application files are moved
 *    +----accessfiles (Public/private key hold)
 *    +----appsupport (files/functions that do things to support all application frames) 
 *    +----devframe (development build/application files)
 *    +----prodframe (production build/application files)  
 *    +----dataconn (directory for data connection strings - Only to be used by PHP files under the applicationTree)
 *    +----tmp (application generated temporary files)
 *    +----publicobj (physical objects to pull 
 * 
 */


$method = $_SERVER['REQUEST_METHOD'];
if ( strtoupper($method) !== "POST" && strtoupper($method) !== "OPTIONS" ) { 
    echo "<h1>PFC/PFRP DATA MANAGEMENT CORE</h1>This is the management core for PFC/PFRP Data Management.  All Request Methods must be POSTING methods.  Thank you.";
} else { 

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers, api-token-user, api-token-key, api-token-pair, pfc-token, pfc-user-token, pfc-data-token, chtn-public,zack-override, override-user, tidal-user');
    //  }
    exit;
  }

  //CHECK USER!!! 
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: POST');
  header('Content-type: application/json; charset=utf8');
  header('Access-Control-Allow-Header: Origin, X-Requested-With, Content-Type, Accept, api-token-user, api-token-key');
  header('Access-Control-Max-Age: 3628800');

  //START SESSSION FOR ALL TRACKING 
  session_start(); 
  session_regenerate_id(true);

  //DEFINE APPLICATION  PARAMETERS
  define("uriPath","pfcdata.chtneast.org");
  define("treeTop","https://pfcdata.chtneast.org");
  define("dataPath","https://pfcdata.chtneast.org");
  define("applicationTree","/srv/chtneastapp/pfcdata/frame");
  define("genAppFiles","/srv/chtneastapp/pfccore");
  define("serverkeys","/srv/chtneastapp/pfccore/dataconn");

  //MODULUS HAS BEEN CHANGED TO DEV.CHTNEAST.ORG
  define("encryptModulus","C7D2CD63A61A810F7A220477B584415CABCF740E4FA567D0B606488D3D5C30BAE359CA3EAA45348A4DC28E8CA6E5BCEC3C37A429AB3145D70100EE3BB494B60DA522CA4762FC2519EEF6FFEE30484FB0EC537C3A88A8B2E8571AA2FC35ABBB701BA82B3CD0B2942010DECF20083A420395EF4D40E964FA447C9D5BED0E91FC35F12748BB0715572B74C01C791675AF024E961548CE4AA7F7D15610D4468C9AC961E7D6D88A6B0A61D2AD183A9DFE2E542A50C1C5E593B40EC62F8C16970017C68D2044004F608E101CD30B69310A5EE550681AB411802806409D04F2BBB3C49B1483C9B9E977FCEBA6F4C8A3CB5F53AE734FC293871DCE95F40AD7B9774F4DD3");
  define("encryptExponent","10001");

  //Include functions file
  require(genAppFiles . "/appsupport/generalfunctions.php");
  require(genAppFiles . "/dataconn/serverid.zck");

  define("serverIdent",$serverid);
  $e = cryptservice( $serverpw,'e', false );
  define("serverPW", $e );

  //DEFINE THE REQUEST PARAMETERS
  $requesterIP = $_SERVER['REMOTE_ADDR']; 
  $userAgent = $_SERVER['HTTP_USER_AGENT'];
  $host = $_SERVER['HTTP_HOST'];
  $https = ($_SERVER['HTTPS'] == 'on') ? "https://" : "http://";
  $originalRequest = str_replace("-","", strtolower($_SERVER['REQUEST_URI']));
  $request = explode("/",str_replace("-","", strtolower($_SERVER['REQUEST_URI']))); 


  $postedData = file_get_contents('php://input');
  if (trim($postedData) !== "") { 
    $passedPayLoad = trim($postedData);
  } 
   
  $responseCode = 401; 
  $msg = "";
  $datareturn = "";

  require(genAppFiles . "/dataservices/posters/pfcposter.php");
  $doer = new dataposters($originalRequest, $passedPayLoad); 
  $responseCode = $doer->responseCode; 
  $msg = $doer->message;
  $data = $doer->rtnData;

  http_response_code($responseCode);
  echo json_encode(array('responseCode' => $responseCode,'message' => $msg, 'datareturn' => $data));

}




