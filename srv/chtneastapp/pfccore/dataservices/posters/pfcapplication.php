<?php

class pfcapplication {

  public $responseCode = 400;  
  public $message = "";
  public $itemsFound = 0;
  public $rtnData = array();
  
  function __construct() { 
    $args = func_get_args(); 
    $nbrofargs = func_num_args(); 
    if ( trim($args[1]) === "" ) {
      //TODO:  WRITE OTHER DATA CATCHERS HERE
    } else {
      $request = json_decode($args[1], true);
      $funcName = $request['submodule'];
      if (function_exists($funcName)) {
        $rtnArr = $funcName($args[0], $args[1]);
        $this->responseCode = $rtnArr['statusCode'];
        $this->message = $rtnArr['message'];
        $this->itemsFound = $rtnArr['itemsfound'];
        $this->rtnData = $rtnArr['data']; 
      } else { 
      }
    }
//NON-SELF CONTAINED FUNCTIONS    
//    if (trim($args[0]) === "") { 
//    } else { 
//      $request = explode("/", $args[0]); 
//      if ( trim($args[1]) === "") { 
//        $this->responseCode = 400; 
//        $this->rtnData = json_encode(array("MESSAGE" => "DATA NAME MISSING","ITEMSFOUND" => 0, "DATA" => array()    ));
//      } else { 
//        //$dp = new $request[2](); 
//        $dp = new datacontrol(); 
//        if (method_exists($dp, 'corecontrol')) { 
//            //$funcName = trim($request[1]); 
//          $funcName = "corecontrol";
//          $dataReturned = $dp->$funcName($args[0], $args[1]); 
//          $this->responseCode = $dataReturned['statusCode'];
//          $this->message = $dataReturned['message']; 
//          $this->rtnData = json_encode($dataReturned['data']);
//        } else { 
//          $this->responseCode = 404; 
//          $this->message = ""; 
//          $this->rtnData = json_encode(array("MESSAGE" => "END-POINT FUNCTION NOT FOUND:","ITEMSFOUND" => 0, "DATA" => ""));
//        }
//      }
//    }    
}
  
  
}

function apprequest($request, $passedData) {     
    $statusCode = 500; 
    $message = ""; 
    $itemsfound = 0; 
    $dataarr = "";
    $pDta = json_decode($passedData, true); 
    $sysid = pfccryptservice($pDta['systemid'],'d',false);
    $logdUsr = pfccryptservice( $pDta['userid'],'d',false ); 
    $rqstParts = explode("/",$pDta['requested']);
    if ($sysid === "PFC-USER") {         
        //PFCMEMBER
        $pfrpMember = pfcmember($logdUsr);
        if ($pfrpMember['responseCode'] === 200) { 
            //PANEL MEMBER
            if (trim($rqstParts[3]) === "") { 
              $rqstPage = "memberapphome";
            } else { 
              parse_str(str_replace("?","", str_replace("-","",strtolower($rqstParts[3]))), $rqstDetermine);
              $rqstPage = $rqstDetermine['page'];        
            }            
            pfclogactivity($pfrpMember['memid'], $logdUsr, $pfrpMember['firstname'], $pfrpMember['lastname'], $pfrpMember['email'], $rqstPage);
            //CHECK PAGE EXISTS IN THIS USERS CONTEXT
           $allowedPages = ["memberapphome","membertemplate","projectlisting"];
           $pg = new pfcpages();
           if (in_array($rqstPage, $allowedPages)) { 
               //PULL PAGE
               $rtnPageArr = $pg->$rqstPage($pDta['requested'], $pfrpMember);
           } else { 
               //PULL NOT FOUND
               $rtnPageArr = $pg->notfound($rqstPage, $pfrpMember);
           }
           $message = $pfrpMember['memid'] . " " . $pfrpMember['firstname'];                        
        } 

//        //GENERAL USER
        if ($pfrpMember['responseCode'] === 404) { 
            $pfrpid = setPFRPID($logdUsr);
            //GENERAL AUDIENCE
            if (trim($rqstParts[3]) === "") { 
              $rqstPage = "genuserapphome";
            } else { 
              parse_str(str_replace("?","", str_replace("-","",strtolower($rqstParts[3]))), $rqstDetermine);
              $rqstPage = $rqstDetermine['page'];        
            }             
            pfclogactivity(0, $logdUsr, "", "", "", $rqstPage);
            //CHECK PAGE EXISTS IN THIS USERS CONTEXT
            $allowedPages = ["genuserapphome","myprojects","submitnewproject"];
            $pg = new pfcpages();
            if (in_array($rqstPage, $allowedPages)) { 
               //PULL PAGE
               $rtnPageArr = $pg->$rqstPage($rqstPage, $pfrpMember, $pfrpid['pfrpid'],$rqstDetermine['projid']);               
            } else { 
               //PULL NOT FOUND
               $rtnPageArr = $pg->notfound($rqstPage, $pfrpMember);
            }                        
        }

        $dataarr = $rtnPageArr;
        $statusCode = 200;
    } else { 
        //SYSTEM IS NOT CORRECT
        $message = "NOT SYSTEM ID - FAILURE";
    }
    $rows['statusCode'] = $statusCode;
    $rows['message'] = $message;
    $rows['itemsfound'] = $itemsfound;
    $rows['data'] = $rqstPage;
    //$rows['data'] = $dataarr;
    return $rows;
    
}

function generatePFCSessionKey($pennKey) { 
    require(genAppFiles .  "/dataconn/sspdo.zck");

    $updSQL = "update pfc.sys_sessionkeys set activeind = 0 where pennkey = :pennkey";
    $uR = $conn->prepare($updSQL);
    $uR->execute(array(':pennkey' => $pennKey));
    
    $delSQL = "delete from pfc.sys_sessionkeys where activeind = 0 and pennkey = :pennkey";
    $dR = $conn->prepare($delSQL);
    $dR->execute(array(':pennkey' => $pennKey));

    $ky = bin2hex(random_bytes(5));    
    $insSQL = "insert into pfc.sys_sessionkeys (sessionkey, pennkey, activeind, activeon) values (:sessionkey,:pennkey,1,now())";
    $rs = $conn->prepare($insSQL);
    $rs->execute(array(':sessionkey' => $ky, ':pennkey' => $pennKey));
    
    return chtnencrypt($ky);  
}

function pfcmember($loggedonUser) { 
    $dtnRtn = array();
    require(genAppFiles .  "/dataconn/sspdo.zck");
    $memSQL = "SELECT pfcmemberid, ifnull(memberfirstname,'') firstname, ifnull(memberlastname,'') as lastname, ifnull(pfrptitle,'') as pfrptitle, ifnull(memberEmail,'') as memberemail, pfcpennkeyref  FROM pfc.sys_pfcmember_pennkey where allowqry = 1 and pfcpennkeyref = :pennkey";
    $memR = $conn->prepare($memSQL);
    $memR->execute(array(':pennkey' => $loggedonUser));
    if ($memR->rowCount() !== 1) { 
        //BAD USER
        $dtnRtn['responseCode'] = 404;
        $dtnRtn['memid'] = 0;
        $dtnRtn['firstname'] = "";
        $dtnRtn['lastname'] = "";
        $dtnRtn['pfrptitle'] = "";
        $dtnRtn['email'] = "";
        $dtnRtn['pfcpennkey'] = $loggedonUser;
    } else { 
        $mR = $memR->fetch(PDO::FETCH_ASSOC);
        $dtnRtn['responseCode'] = 200;
        $dtnRtn['memid'] = $mR['pfcmemberid'];
        $dtnRtn['firstname'] = $mR['firstname'];
        $dtnRtn['lastname'] = $mR['lastname'];
        $dtnRtn['pfrptitle'] = $mR['pfrptitle'];
        $dtnRtn['email'] = $mR['memberemail'];
        $dtnRtn['pfcpennkey'] = $mR['pfcpennkeyref'];
    }
    return $dtnRtn;
}

function pfclogactivity($whoid, $whopennkey, $whofname, $wholname, $whoemail,$whatpage) { 
     require(genAppFiles .  "/dataconn/sspdo.zck");
     $insSQL = "insert into webcapture.tbl_siteusage (usageDateTime, loggedsession, sessionvariables, userid, firstname, lastname, email, request, sessionid)  values (now(), 'true',:pennKey, :memberid,:memFname,:memLname,:memEmail,:rqstPage,'PFCREQUEST')";
     $rs = $conn->prepare($insSQL);
     $rs->execute(
             array(
              ':pennKey' => $whopennkey
            , ':memberid' => $whoid
            , ':memFname' => $whofname
            , ':memLname' => $wholname
            , ':memEmail' => $whoemail
            , ':rqstPage' => $whatpage                  
             ));
}

function setPFRPID($logdUsr) {
  $ky = bin2hex(random_bytes(5));    
  require(genAppFiles .  "/dataconn/sspdo.zck");
  $chkSQL = "SELECT ifnull(pfrpid,'') as pfrpid FROM pfc.ut_projectUsers where pennKey = :pennkey";
  $cR = $conn->prepare($chkSQL); 
  $cR->execute(array(':pennkey' => $logdUsr));
  if ($cR->rowCount() === 0) { 
   //ADD PENNKEY/PFRPID COMBI
   $insSQL = "insert into pfc.ut_projectUsers (pennkey, pfrpid, dateadded)values (:pennkey,:key,now())";  
   $iR = $conn->prepare($insSQL); 
   $iR->execute(array(':pennkey' => $logdUsr, ':key' => $ky));
  } else { 
     $row = $cR->fetch(); 
     if ($row['pfrpid'] === "") { 
       //UPDATE RECORD   
       $updSQL = "update pfc.ut_projectUsers set pfrpid = :pfrpid, dateadded=now() where pennkey = :pennkey";
       $uR = $conn->prepare($updSQL); 
       $uR->execute(array(':pfrpid' => $ky, ':pennkey' => $logdUsr));
     } else { 
       $ky = $row['pfrpid'];
     }
  }
  $dtnRtn = array(); 
  $dtnRtn['pfrpid'] = $ky;
  return $dtnRtn;
}

class pfcpages { 
    
    public $response = "400";
    public $pageReturned = "";
    public $preamble = "";
    public $head = "";
    public $style = "";
    public $java = "";
    public $body = array("contentHeader" => "", "contentSection" => "", "contentFooter" => "");
    public $userInfo = array("pfcMember" => false, "pfcPennKey" => "", "pfcMemberFirstName" =>"", "pfcMemberLastName" => "", "pfcMemberId" => 0, "pfcMemberEmail" => "");

  private $color_white = "255,255,255";
  private $color_black = "0,0,0";
  private $color_grey = "224,224,224";
  private $color_lgrey = "245,245,245";
  private $color_brwgrey = "239,235,233";
  private $color_ddrwgrey = "189,185,183";
  private $color_lamber = "255,248,225";
  private $color_mamber = "204,197,175";
  private $color_mgrey = "160,160,160";
  private $color_dblue = "0,32,113";
  private $color_mblue = "13,70,160";
  private $color_lblue = "84,113,210";
  private $color_zgrey = "48,57,71";
  private $color_neongreen = "57,255,20";
  private $color_bred = "237, 35, 0";

function standardHeader() { 
  $rtnThis = <<<RTNTHIS
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<meta http-equiv="refresh" content="28800">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-16x16.png" sizes="16x16"/>
RTNTHIS;
  return $rtnThis; 
} 

function globalStyle() { 
$rtnThis = <<<RTNTHIS
@import url(https://fonts.googleapis.com/css?family=Roboto|Share+Tech+Mono|Material+Icons);
html {margin: 0; height: 100%; width: 100%; font-family: Roboto; font-size: 1vh; background: rgba({$this->color_white},1); color: rgbha({$this->color_black},1) }

#topBar {width: 100vw; position: fixed; top: 0; left: 0;background: rgba({$this->color_zgrey},1);}
#barHolderTbl {width: 100vw; border-collapse: collapse; }
#psomLogoCell {padding: 1.5vh 2vw .5vh 2vw; width: 30px; }
#PSOMLogo {height: 4vh; }

#userInfoCell {width: 15vw; text-align: right; }
#userInfoCell .infoLbl { font-size: 1.1vh; color: rgba({$this->color_white},1); font-weight: bold; }
#userInfoCell .infoInfo { font-size: 1.1vh; color: rgba({$this->color_white},1); text-align: left; }

#buttonHoldTbl {border-collapse: collapse; height: 6vh; font-size: 1.3vh;color: rgba({$this->color_white},1); }
#buttonHoldTbl .btn {padding: 0 .5vw 0 .5vw; width: 6vw; }
#buttonHoldTbl .btn a {color: rgba({$this->color_white},1); text-decoration: none;} 
#buttonHoldTbl .btn:hover {background: rgba({$this->color_lblue},1); cursor: pointer; }
#buttonHoldTbl tr td table {border-collapse: collapse; }
#buttonHoldTbl .btnIcon { width: 1vw; text-align: center; }
#buttonHoldTbl .btnIcon .material-icons {font-size: 2.3vh; }

.zackdropmenuholder {position: relative; }
.zackmenuinput {font-size: 1.2vh;width: 21.5vw;padding: 10px 0 10px 5px;border: 1px solid rgba({$this->color_mgrey},1);border-radius: 0;box-sizing: border-box; }
.inputwrapper::after {font-family: 'Material Icons';font-size: 1.1vh;content: 'menu';margin-left: -1.5em; }
.inputwrapper:hover {cursor: pointer; }
.zackdspmenu {position: absolute; left: 0; display: none; background: rgba({$this->color_lgrey},1); z-index: 2; border: 1px solid rgba({$this->color_mgrey},1);font-size: 1.1vh; max-height: 40vh; overflow: auto; }
.fllDivDsp { width: 21.5vw; }
.hlfDivDsp { width: 10vw;  }

RTNTHIS;
return $rtnThis;
}

function topAndMenuBarUser($usrPennKey = "", $pfrpid = "") { 
  $at = genAppFiles;
  $tt = pfcsecureurl; 
  $pPic = base64file( "{$at}/publicobj/graphics/psom_logo_white.png", "PSOMLogo", "image", true); 
  $buttons = <<<BUTTONS
     <table border=0 id=buttonHoldTbl><tr>
         <td class=btn id=btnBarUserHome><center><a href="{$tt}/">
           <table border=0><tr><td class=btnIcon><i class="material-icons">home</i></td><td>Home</td></tr></table>
           </a>
         </td>
         <td class=btn id=btnBarUserProjects><center>
           <a href="{$tt}/?page=my-projects&pfrpid={$pfrpid}">
           <table border=0><tr><td class=btnIcon><i class="material-icons">assignment</i></td><td>My Projects</td></tr></table>
           </a>
         </td>
         <td class=btn id=btnBarUserNew><center>
           <a href="{$tt}/?page=my-projects&pfrpid={$pfrpid}&projid=new">
           <table border=0><tr><td class=btnIcon><i class="material-icons">add_circle_outline</i></td><td>Submit New</td></tr></table>
           </a>
         </td>
       </tr>
     </table>
BUTTONS;
  $usrTbl = "<table>
<tr><td class=infoLbl>PennKey: </td><td class=infoInfo>{$usrPennKey}</td></tr>"; 
  $usrTbl .= "</table>";
  $rtnThis = <<<RTNTHIS
<div id=topBar>
<table id=barHolderTbl border=0><tr><td id=psomLogoCell>{$pPic}</td><td>{$buttons}</td><td></td><td id=userInfoCell>{$usrTbl}</td></tr></table>
</div>
RTNTHIS;
  return $rtnThis;
}

function topAndMenuBarMember($usrPennKey = "", $usrName = "", $usrEmail = "") { 
  $at = genAppFiles;
  $pPic = base64file( "{$at}/publicobj/graphics/psom_logo_white.png", "PSOMLogo", "image", true); 

  $buttons = <<<BUTTONS

<table border=0 id=buttonHoldTbl><tr>
         <td class=btn onclick="navigateSite('');"><center>
           <table border=0><tr><td class=btnIcon><i class="material-icons">home</i></td><td>Home</td></tr></table>
         </td>
         <td class=btn onclick="navigateSite('project-listing');"><center>
           <table border=0><tr><td class=btnIcon><i class="material-icons">view_list</i></td><td>Project List</td></tr></table>
         </td>
         <td class=btn><center>
           <table border=0 onclick="navigateSite('member-template');"><tr><td class=btnIcon><i class="material-icons">search</i></td><td>Search</td></tr></table>
         </td>
       </tr>
</table>

BUTTONS;


  $usrTbl = "<table>
<tr><td class=infoLbl>PennKey: </td><td class=infoInfo>{$usrPennKey}</td></tr>"; 
  $usrTbl .= (trim($usrName) !== "") ? "<tr><td class=infoLbl>Name: </td><td class=infoInfo>{$usrName}</td></tr>" : "";
  $usrTbl .= (trim($usrEmail) !== "") ? "<tr><td class=infoLbl>Email: </td><td class=infoInfo>{$usrEmail}</td></tr>" : "";
  $usrTbl .= "</table>";

  $rtnThis = <<<RTNTHIS
<div id=topBar>
<table id=barHolderTbl border=0><tr><td id=psomLogoCell>{$pPic}</td><td>{$buttons}</td><td></td><td id=userInfoCell>{$usrTbl}</td></tr></table>
</div>
RTNTHIS;
  return $rtnThis;
}

function standardFootMember() { 
    $rtnThis = <<<RTNTHIS
<div id=standardMemberFooter></div>
RTNTHIS;
return $rtnThis;
}

function jvmyprojects() { 

$tt = pfcsecureurl;
$dtaTree = dataPath;
//$dtakey = generatePFCSessionKey($this->userInfo['pfcPennKey']);
//$usrEncrypt = chtnencrypt($this->userInfo['pfcPennKey']);

$rtnThis = <<<RTNTHIS


RTNTHIS;
return $rtnThis;
}

function globaljavascriptrUser() { 
$tt = pfcsecureurl;
$dtaTree = dataPath;
$dtakey = generatePFCSessionKey($this->userInfo['pfcPennKey']);
$usrEncrypt = chtnencrypt($this->userInfo['pfcPennKey']);

$rtnThis = <<<RTNTHIS

var treeTop = '{$tt}';
var byId = function( id ) { return document.getElementById( id ); }

function navigateSite(whichURL) {
    if (whichURL) {
      window.location.href = treeTop+'/?page='+whichURL;
    } else {    
      window.location.href = treeTop+'/';
    }
}

function btoathisfile(btoadsp, passedfile) { 
  var reader = new FileReader();
  reader.onload = function(e) { 
    byId(btoadsp).innerHTML = reader.result;
  };
  reader.readAsDataURL(passedfile);
}

var httpage = getXMLHTTPRequest();
function getXMLHTTPRequest() {
try {
req = new XMLHttpRequest();
} catch(err1) {
  try {
    req = new ActiveXObject("Msxml2.XMLHTTP");
  } catch(err2) {
    try {
      req = new ActiveXObject("Microsoft.XMLHTTP");
    } catch(err3) {
      req = false;
    }
  }
}
return req;
}

function grabdocumentpdf(whichdocument) { 
   var crd = new Object(); 
   var dta = new Object(); 
   crd['qryDocument'] = whichdocument;
   dta['datapayload'] = JSON.stringify(crd);
   var passdata = JSON.stringify(dta);  
   var mlURL = "{$dtaTree}/pfcapplication/getpfrpdocument";
   httpage.open("POST",mlURL,true);
   httpage.setRequestHeader("pfc-user-token","{$usrEncrypt}");
   httpage.setRequestHeader("pfc-data-token","{$dtakey}");
   httpage.onreadystatechange = function () { 
       if (httpage.readyState === 4) {
         switch (httpage.status) { 
           case 200:
              var rcd = JSON.parse(httpage.responseText);
              var doc = JSON.parse(rcd['message']);
              var documentBaseCode = doc['DATA'];

              var objbuilder = '';
              objbuilder += '<object style="height: 77vh; width: 100%;" data="data:application/pdf;base64,';
              objbuilder += documentBaseCode;
              objbuilder += '" type="application/pdf" class="internal">';
              objbuilder += '<embed src="data:application/pdf;base64,';
              objbuilder += documentBaseCode;
              objbuilder += '" type="application/pdf"  />';
              objbuilder += '</object>';

              byId('displayThisPDF').innerHTML = objbuilder;

              byId('modalBack').style.display = 'block'
              byId('pdfDisplay').style.display = 'block';
              //var newWindow = window.open();
              //newWindow.document.write('<iframe src="data:application/pdf;base64,' + documentBaseCode + '" frameborder="0" allowfullscreen width=100% height=100%></iframe>');
              //newWindow.document.title = "PFRP Document";
            break; 
           default: 
             var rcd = httpage.responseText;
             alert(rcd);
         }
      }
    };
   httpage.send(passdata); 
}

function submitPFRPApplication() {
   var crd = new Object(); 
   var dta = new Object(); 
   alert('Your application is being submitted to the Pathology Feasibility Review Panel.  Depending on the file size of your documents this submittal could take up to two minutes (DO NOT CLICK THE REFRESH BUTTON).  Please wait and your screen will automatically be refreshed when finished.');

  var element = document.getElementsByTagName("*");
  for (var i = 0; i < element.length; i++ ) {
     if (element[i].id.substr(0,3) === 'frm' || element[i].id.substr(0,3) === 'fld') { 
        crd[element[i].id] = element[i].value;
     }
  }

  for (var i = 0; i < element.length; i++ ) {
     if (element[i].id.substr(0,3) === 'bto') { 
      if (byId('doc'+element[i].id.substr(3)).files.length !== 0) {      
       crd['doc'+element[i].id.substr(3)] = byId('doc'+element[i].id.substr(3)).files[0].name;
       crd[element[i].id] = element[i].value;
      }
     }
  }

  dta['datapayload'] = JSON.stringify(crd);
  var passdata = JSON.stringify(dta);  

   var mlURL = "{$dtaTree}/pfcapplication/savepfrpapplication";
   httpage.open("POST",mlURL,true);
   httpage.setRequestHeader("pfc-user-token","{$usrEncrypt}");
   httpage.setRequestHeader("pfc-data-token","{$dtakey}");
   httpage.onreadystatechange = function () { 
       if (httpage.readyState === 4) {
         switch (httpage.status) { 
           case 200:
           window.location = "{$tt}/?page=my-projects";
           break; 
           default: 
           var msghld = JSON.parse(httpage.responseText);
           var msg = JSON.parse(msghld['message']);
           alert(msg['MESSAGE']);
         }
      }
    };
    httpage.send(passdata);
}

function closeModal() { 
  byId('modalBack').style.display = 'none'
  byId('pdfDisplay').style.display = 'none';
  byId('emailerDisplay').style.display = 'none';
}


RTNTHIS;
return $rtnThis; 
}

function globaljavascriptr() { 
$tt = pfcsecureurl;
$dtakey = generatePFCSessionKey($this->userInfo['pfcPennKey']);
$usrEncrypt = chtnencrypt($this->userInfo['pfcPennKey']);

$rtnThis = <<<RTNTHIS

var byId = function( id ) { return document.getElementById( id ); }
var treeTop = "{$tt}";
var datakey = "{$dtakey}";
var usree = "{$usrEncrypt}";

var httpage = getXMLHTTPRequest();
var httpageone = getXMLHTTPRequest();
function getXMLHTTPRequest() {
try {
req = new XMLHttpRequest();
} catch(err1) {
        try {
	req = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(err2) {
                try {
                req = new ActiveXObject("Microsoft.XMLHTTP");
                } catch(err3) {
                  req = false;
                }
        }
}
return req;
}

function navigateSite(whichURL) {
    if (whichURL) {
      window.location.href = treeTop+'/?page='+whichURL;
    } else {    
      window.location.href = treeTop+'/';
    }
}


RTNTHIS;
return $rtnThis; 
}

function memberapphome($rqst, $usr) { 
    $tt = pfcurl;
    $securett = pfcsecureurl;
    $this->pageReturned = $rqst;
    $this->preamble = "<!DOCTYPE html>\n<html>";
    $standHead = self::standardHeader();
    $this->head = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}/?page={$rqst} //-->
{$standHead}
<title>PFRP Data Application</title>
HDR;
    $ss = self::globalStyle();
    $this->style = <<<STYLESHT
<style>
{$ss}
body {margin-top: 9vh; } 
</style>
STYLESHT;

$jvscript = self::globaljavascriptr(); 
    $this->java = <<<JAVASCRIPTR
<script lang=javascript>
{$jvscript}
</script>

JAVASCRIPTR;

    $this->userInfo = array("pfcMember" => true, "pfcPennKey" => $usr['pfcpennkey'], "pfcMemberFirstName" => $usr['firstname'], "pfcMemberLastName" => $usr['lastname'], "pfcMemberId" => $usr['memid'], "pfcMemberEmail" => $usr['email']);
    $cHeader = self::topAndMenuBarMember($usr['pfcpennkey'], "{$usr['firstname']} {$usr['lastname']}", "{$usr['email']}" );
    $cFooter = self::standardFootMember();

$pageContent = <<<RTNTHIS
        Pathology Feasibility Review Panel Application Review<p>
        The mission of the Pathology Feasibility Review Panel (PFRP) is to protect the integrity of patient diagnostic material for pathological analysis. This panel will review research protocols to ensure that only pre-authorized individuals will be granted access to remove research samples from the OR areas.  <p>Thank you for logging in to review projects.  To begin the review process, click the "Project List" button on the menu bar above.

RTNTHIS;

   $this->body = array("contentHeader" => $cHeader, "contentSection" => $pageContent, "contentFooter" => $cFooter);

   $this->response = 200;
   $rtnData['response'] = $this->response;
   $rtnData['pageReturned'] = $this->pageReturned;
   $rtnData['preamble'] = $this->preamble;
   $rtnData['pageHead'] = $this->head;
   $rtnData['stylesheet'] = $this->style;
   $rtnData['javascriptr'] = $this->java;
   $rtnData['userInfo'] = $this->userInfo;
   $rtnData['pageBody'] = $this->body; 
   return json_encode($rtnData);
}

function projectlisting($rqst, $usr) { 
    $tt = pfcurl;
    $securett = pfcsecureurl;
    $this->pageReturned = $rqst;
    $this->preamble = "<!DOCTYPE html>\n<html>";
    $standHead = self::standardHeader();
    $this->userInfo = array("pfcMember" => true, "pfcPennKey" => $usr['pfcpennkey'], "pfcMemberFirstName" => $usr['firstname'], "pfcMemberLastName" => $usr['lastname'], "pfcMemberId" => $usr['memid'], "pfcMemberEmail" => $usr['email']);
    $this->head = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}/?page={$rqst} //-->
{$standHead}
<title>PFRP Project Listing</title>
HDR;
    $ss = self::globalStyle();
    $this->style = <<<STYLESHT
<style>
{$ss}
body {margin-top: 9vh; } 

#statusMenuTbl {font-size: 1.3vh; border-collapse: collapse; color: rgba({$this->color_zgrey},1); width: 15vw;}
#statusMenuTbl .menuOption {padding: 1vh .5vw 1vh .5vw; background: rgba({$this->color_white},1); border-bottom: 2px solid rgba({$this->color_white},1); }
#statusMenuTbl .menuOption:hover {cursor: pointer; background: rgba({$this->color_lgrey},1); border-bottom: 2px solid rgba({$this->color_brwgrey},1); }

#statDspHoldTbl {width: 98vw; height: 87vh; }
#statDspHoldTbl #statDspSidePanel { width: 15vw; }

#projectDisplayDiv {height: 87vh; width: 83vw; overflow: auto; }
#projectDisplayDiv h1 {font-size: 2.5vh; font-weight: bold; color: rgba({$this->color_mgrey},1); }

#projectDisplayDiv #projectLister {width: 82vw;font-family: Roboto; empty-cells: show; }
#projectDisplayDiv #projectLister #pcounter { font-size: 1.2vh; border-bottom: 1px solid rgba({$this->color_mgrey},1); }
#projectDisplayDiv #projectLister thead th {font-size: 1vh; text-align: left; padding: 1.8vh .5vw .3vh 0; border-bottom: 1px solid rgba({$this->color_mgrey},1); }
#projectDisplayDiv #projectLister tbody {font-size: 1.3vh; }

#projectDisplayDiv #projectLister tbody tr:nth-child(even){background-color: #f2f2f2;}
#projectDisplayDiv #projectLister tbody tr:hover {background-color: #ddd;}
#projectDisplayDiv #projectLister tbody {border: 1px solid rgba({$this->color_zgrey},1); }
#projectDisplayDiv #projectLister tbody td {vertical-align: top; padding: .8vh .2vw .8vh .2vw; height: 6vh;  }

#reviewHoldingTbl {width: 95vw;}
#reviewHoldingTbl #reviewSidePanel {width: 28vw; } 
#reviewHoldingTbl #reviewPanel {width: 67vw; border-left: 2px solid rgba({$this->color_mamber},1); }

#projectDisplay {width: 28vw; font-family: Roboto;  } 
#projectDisplay #projDspProjectId { font-size: 1.8vh; font-weight: bold; }
#projectDisplay #projDspProjectTitle {font-size: 1.8vh; text-align: justify;padding-bottom: 1.5vh; }
#projectDisplay .projDspHeader {text-align: center; background: rgba({$this->color_grey},1);padding: .3vh 0 .3vh 0; font-size: 1.5vh; font-weight: bold; color: rgba({$this->color_zgrey},1);border-top: 2px solid rgba({$this->color_mamber},1); border-bottom: 2px solid rgba({$this->color_mamber},1);}  
#projectDisplay .dataline { font-size: 1.1vh; border-bottom: 1px solid rgba({$this->color_zgrey},1); padding: .8vh 0 0 .2vw; }
.datalabel {font-size: 1vh; font-weight: bold; color: rgba({$this->color_zgrey},1); width: 7vw; }


#modalBack {position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 100; background: rgba({$this->color_black},.6); display: none; }
#pdfDisplay { width: 80vw; height: 80vh; position: fixed; margin-top: -40vh; top: 50%; background: rgba({$this->color_white},1); z-index: 101; margin-left: -40vw; left: 50%; border: 8px solid rgba({$this->color_mgrey},1);box-sizing: border-box; display: none; overflow: hidden; }
#displayThisPDF {height: 75vh; width: 78vw;overflow: auto; }
#closeMod {font-size: 1.8vh; color: rgba({$this->color_mgrey},1); font-weight: bold; } 
#closeMod:hover {cursor: pointer; color: rgba({$this->color_bred},1); }

#closeMod2 {font-size: 1.8vh; color: rgba({$this->color_mgrey},1); font-weight: bold; } 
#closeMod2:hover {cursor: pointer; color: rgba({$this->color_bred},1); }


#emailerDisplay { width: 80vw; height: 80vh; position: fixed; margin-top: -40vh; top: 50%; background: rgba({$this->color_white},1); z-index: 101; margin-left: -40vw; left: 50%; border: 8px solid rgba({$this->color_mgrey},1);box-sizing: border-box; display: none; overflow: hidden; }



.reviewcomments { height: 15vh; width: 60vw;resize: none; border: 1px solid rgba({$this->color_zgrey},1); font-family: Roboto; font-size: 1.4vh; color: rgba({$this->color_zgrey},1);padding: 1vh 1vw 1vh 1vw;box-sizing: border-box; }
#emailMessage { height: 60vh; width: 78vw;resize: none; border: 1px solid rgba({$this->color_zgrey},1); font-family: Roboto; font-size: 1.4vh; color: rgba({$this->color_zgrey},1);padding: 1vh 1vw 1vh 1vw;box-sizing: border-box; }

#pfcreviewtitle {  font-size: 1.8vh; font-weight: bold; }
#pfrpDecision { background: rgba({$this->color_white},1); border: 1px solid rgba({$this->color_zgrey},1);padding: .7vh .5vw .7vh 1vw;width: 15vw; }
#pfrpCopyMe { background: rgba({$this->color_white},1); border: 1px solid rgba({$this->color_zgrey},1);padding: .7vh .5vw .7vh 1vw;width: 7vw; }
#pfrpReferEmailList { background: rgba({$this->color_white},1); border: 1px solid rgba({$this->color_zgrey},1);padding: .7vh .5vw .7vh 1vw;width: 18vw; }

#pfrpSaveButton { font-size: 1.4vh; border: 1px solid rgba({$this->color_zgrey},1);padding: 1.2vh .8vw 1.2vh .8vw; background: rgba({$this->color_mblue},1); color: rgba({$this->color_white},1);}
#pfrpSaveButton:hover {cursor: pointer;background: rgba({$this->color_lblue},1); }

#pfrpSendButton { font-size: 1.4vh; border: 1px solid rgba({$this->color_zgrey},1);padding: 1.2vh .8vw 1.2vh .8vw; background: rgba({$this->color_mblue},1); color: rgba({$this->color_white},1);}
#pfrpSendButton:hover {cursor: pointer;background: rgba({$this->color_lblue},1); }

</style>
STYLESHT;

$jvscript = self::globaljavascriptr(); 
$dtaTree = treeTop;
$pfcsecure = pfcsecureurl . "/";
    $this->java = <<<JAVASCRIPTR
<script lang=javascript>
{$jvscript}

document.addEventListener('DOMContentLoaded', function() {
      if (byId('pfrpDecision')) { 
        byId('pfrpDecision').value = 'NA';
      }
      if (byId('pfrpReferEmailList')) { 
        byId('pfrpReferEmailList').value = 0;
      } 
      if (byId('emailMessage')) { 
        byId('emailMessage').value = "";
      }
      if (byId('txtLetterComments')) { 
        byId('txtLetterComments').value = "";
      }
      if (byId('txtInternalComments')) { 
        byId('txtInternalComments').value = "";
      }
      if (byId('pfrpCopyMe')) { 
        byId('pfrpCopyMe').value = "NO";
      }
}, false);

function closeModal() { 
  byId('modalBack').style.display = 'none'
  byId('pdfDisplay').style.display = 'none';
  byId('emailerDisplay').style.display = 'none'; 
}

function grabdocumentpdf(whichdocument) { 
   var crd = new Object(); 
   var dta = new Object(); 
   crd['qryDocument'] = whichdocument;
   dta['datapayload'] = JSON.stringify(crd);
   var passdata = JSON.stringify(dta);  
   var mlURL = "{$dtaTree}/pfcapplication/getpfrpdocument";
   httpage.open("POST",mlURL,true);
   httpage.setRequestHeader("pfc-user-token",usree);
   httpage.setRequestHeader("pfc-data-token",datakey);
   httpage.onreadystatechange = function () { 
       if (httpage.readyState === 4) {
         switch (httpage.status) { 
           case 200:
              var rcd = JSON.parse(httpage.responseText);
              var doc = JSON.parse(rcd['message']);
              var documentBaseCode = doc['DATA'];
              var objbuilder = '';
              objbuilder += '<object style="height: 77vh; width: 100%;" data="data:application/pdf;base64,';
              objbuilder += documentBaseCode;
              objbuilder += '" type="application/pdf" class="internal">';
              objbuilder += '<embed src="data:application/pdf;base64,';
              objbuilder += documentBaseCode;
              objbuilder += '" type="application/pdf"  />';
              objbuilder += '</object>';
              byId('displayThisPDF').innerHTML = objbuilder;
              byId('modalBack').style.display = 'block'
              byId('pdfDisplay').style.display = 'block';
              //var newWindow = window.open();
              //newWindow.document.write('<iframe src="data:application/pdf;base64,' + documentBaseCode + '" frameborder="0" allowfullscreen width=100% height=100%></iframe>');
              //newWindow.document.title = "PFRP Document";
            break; 
           default: 
             var rcd = httpage.responseText;
             alert(rcd);
         }
      }
    };
   httpage.send(passdata); 
}

function saveReview() { 
   var crd = new Object(); 
   var dta = new Object(); 
   crd['proj'] = byId('fldprojectidreference').value;
   crd['decision'] = byId('pfrpDecision').value; 
   crd['lettercomments'] = byId('txtLetterComments').value; 
   crd['internals'] = byId('txtInternalComments').value; 
//   crd['copyme'] = byId('pfrpCopyMe').value;
   dta['datapayload'] = JSON.stringify(crd);
   var passdata = JSON.stringify(dta); 
   var mlURL = "{$dtaTree}/pfcapplication/pfrpdecision";
   httpage.open("POST",mlURL,true);
   httpage.setRequestHeader("pfc-user-token",usree);
   httpage.setRequestHeader("pfc-data-token",datakey);
   httpage.onreadystatechange = function () { 
      if (httpage.readyState === 4) {
         console.log(httpage.responseText);
         switch (httpage.status) { 
           case 200:
//               //Redirect Home
               alert('Decision Saved.  Email Sent.  You will be redirected back to the home screen.');
               window.location = "{$pfcsecure}";
            break; 
           default: 
             var rcd = httpage.responseText;
             //alert(rcd);
         }
      }
    };
   httpage.send(passdata); 
}

function referReview(whichoption) { 
  if (parseInt(whichoption) === 2) {  
      if (byId('pfrpReferEmailList')) { 
        byId('pfrpReferEmailList').value = 0;
      } 
      if (byId('emailMessage')) { 
        byId('emailMessage').value = "";
      }
    byId('modalBack').style.display = 'block'
    byId('emailerDisplay').style.display = 'block'; 
  }
}

function sendReferalMessage() { 
   var crd = new Object(); 
   var dta = new Object(); 
   crd['recip'] = byId('pfrpReferEmailList').value;
   crd['proj'] = byId('fldprojectidreference').value;
   crd['msg'] = byId('emailMessage').value;
   dta['datapayload'] = JSON.stringify(crd);
   var passdata = JSON.stringify(dta);  
   var mlURL = "{$dtaTree}/pfcapplication/sendpfrpreferal";
   httpage.open("POST",mlURL,true);
   httpage.setRequestHeader("pfc-user-token",usree);
   httpage.setRequestHeader("pfc-data-token",datakey);
   httpage.onreadystatechange = function () { 
       if (httpage.readyState === 4) {
         switch (httpage.status) { 
           case 200:
               //Redirect Home
               alert('Email Referal Sent.  You will be redirected back to the home screen.');
               window.location = "{$pfcsecure}";
            break; 
           default: 
             var rcd = httpage.responseText;
             alert(rcd);
         }
      }
    };
   httpage.send(passdata); 
}

</script>

JAVASCRIPTR;

$cHeader = self::topAndMenuBarMember($usr['pfcpennkey'], "{$usr['firstname']} {$usr['lastname']}", "{$usr['email']}" );
$cFooter = self::standardFootMember();
parse_str(str_replace("?","", str_replace("-","",strtolower($rqst))), $rqstDetermine);    



if ($rqstDetermine['projid']) {
  if (is_numeric($rqstDetermine['projid'])) { 
    ///GET PROJECT
    $passData = json_encode(array("pennkey" => chtnencrypt($this->userInfo['pfcPennKey']),"projectid" => $rqstDetermine['projid']));
    $pHold = json_decode(callpfcrestapi("POST","https://data.chtneast.org/pfcapplication/getprojectbymember",pfcaccess,$passData), true);

if ((int)$pHold['responseCode'] === 200) { 
//{"responseCode":200,"message":"{\"MESSAGE\":\"\",\"ITEMS\":0,\"DATA\":{"completeind\":0,,,,,,,,\"statuses\":[{\"datastatus\":\"SUBMITTED\",\"statusmodifier\":\"\",\"statusdate\":\"06\\\/23\\\/2018 12:35\",\"lettercomments\":\"\"},{\"datastatus\":\"IN REVIEW\",\"statusmodifier\":\"\",\"statusdate\":\"06\\\/25\\\/2018 09:40\",\"lettercomments\":\"\"},{\"datastatus\":\"IN REVIEW\",\"statusmodifier\":\"\",\"statusdate\":\"06\\\/25\\\/2018 09:41\",\"lettercomments\":\"\"}]}}","datareturn":""} 
    $head = json_decode($pHold['message'], true); 
    foreach($head['DATA']['contacts'] as $conval) { 
        switch($conval['contacttype']) { 
          case 'SUBMITTER':
              $subname = $conval['contactname'];
              $subphone = $conval['phonenbr'];
              $subemail = $conval['emailaddress'];
              break;
          case 'PROJECT-PI':
              $piname = $conval['contactname'];
              $piphone = $conval['phonenbr'];
              $piemail = $conval['emailaddress'];
              break;
        } 
    }

    $innerAns = "<table border=0 style=\"width: 28vw;\">";
    $qCount = 1;
    foreach($head['DATA']['questionanswers'] as $ans) { 
      $innerAns .= "<tr><td valign=top style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$qCount}</td><td valign=top style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$ans['question']}</td><td valign=top style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$ans['answer']}</td></tr>";
      $qCount += 1;    
    }
    $innerAns .= "</table>";

    $innerDocs = "<table border=0 style=\"width: 28vw;\">";
    $qCount = 1;
    foreach($head['DATA']['documents'] as $doc) { 
      $innerDocs .= "<tr onclick=\"grabdocumentpdf('{$doc['directorydocumentname']}');\"><td style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$qCount}</td><td style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$doc['typeofdocument']}</td><td style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">({$doc['uploadedon']}-{$doc['uploadedby']})</td></tr>";
      $qCount += 1;    
    }
    if (trim($head['DATA']['projectpdf']) !== "") { 
      $innerDocs .= "<tr onclick=\"grabdocumentpdf('{$head['DATA']['projectpdf']}');\"><td>{$qCount}</td><td>Project PDF</td><td>&nbsp;</td></tr>";
    }
    $innerDocs .= "</table>";

    $decisionSelect = "<select id=pfrpDecision onchange=\"referReview(this.value);\"><option value=\"NA\">-</option>";
    $actionHold = json_decode(callpfcrestapi("GET","https://data.chtneast.org/globalmenu/pfrpactions",pfcaccess), true);
    if ($actionHold['responseCode'] === 200) { 
      $actionVals = json_decode($actionHold['datareturn'], true);
      foreach($actionVals['DATA'] as $actionv) { 
        $decisionSelect .= "<option value=\"{$actionv['codevalue']}\">{$actionv['menuvalue']}</option>";
      } 
    } else {
      $decisionSelect .= "<option value=\"BADVALUE\">ERROR NO MENU FOUND IN SERVICE</option>";
    }
    $decisionSelect .= "</select>"; 

    $emailSelect = "<select id=pfrpReferEmailList>";
    $actionHold = json_decode(callpfcrestapi("GET","https://data.chtneast.org/pfrpmemberemaillisting",pfcaccess), true);
    if ($actionHold['responseCode'] === 200) { 
      $actionVals = json_decode($actionHold['datareturn'], true);
      foreach($actionVals['DATA'] as $actionv) { 
        $emailSelect .= "<option value=\"{$actionv['pfcmemberid']}\">{$actionv['membername']}</option>";
      } 
    } else {
      $emailSelect .= "<option value=\"BADVALUE\">ERROR NO MENU FOUND IN SERVICE</option>";
    }
    $emailSelect .= "</select>"; 

    $copySelect = "<select id=pfrpCopyMe><option value=\"NO\">NO</option><option value=\"YES\">YES</option></select>";

//<tr><td><table><tr><td class=datalabel>Copy Me on Decision Email</td></tr><tr><td>{$copySelect}</td></tr></table></td><td align=right><table id=pfrpSaveButton onclick="saveReview();"><tr><td>Save</td></tr></table></td></tr>

    $completeind = $head['DATA']['completeind'];
    if ((int)$completeind === 1) { 
    $reviewSide = <<< REVIEWSIDE
<table border=0><tr><td id=pfcreviewtitle>Pathology Feasibility Review </td></tr>
<tr><td> 
REVIEW HAS ALREADY BEEN COMPLETED.  THIS DISPLAY IS FOR REFERENCE ONLY.
</td></tr></table>
REVIEWSIDE;
    } else { 
    $reviewSide = <<< REVIEWSIDE
<table border=0><tr><td id=pfcreviewtitle>Pathology Feasibility Review</td></tr>
<tr><td> 
<table border=0>
<tr><td colspan=2 class=datalabel style="padding: 3vh 0 0 0;">Decision</td></tr>
<tr><td colspan=2>{$decisionSelect}</td></tr>
<tr><td colspan=2 class=datalabel style="padding: 3vh 0 0 0;">Comments to Include in Letter to Investigator</td></tr>
<tr><td colspan=2><TEXTAREA id=txtLetterComments class=reviewcomments></TEXTAREA></td></tr>
<tr><td colspan=2 class=datalabel style="padding: 3vh 0 0 0;">Internal PFRP Comments</td></tr>
<tr><td colspan=2><TEXTAREA id=txtInternalComments class=reviewcomments></TEXTAREA></td></tr>
<tr><td></td><td align=right><table id=pfrpSaveButton onclick="saveReview();"><tr><td>Save</td></tr></table></td></tr>
</td></tr></table>
REVIEWSIDE;
    }
    
    
    $dspThis = <<< DSPTHIS
<table border=0 id=reviewHoldingTbl>
  <tr>
  <td id=reviewSidePanel valign=top>
  
  <table border=0 id=projectDisplay>
    <tr><td colspan=2 id=projDspProjectId>Project {$head['DATA']['projectid']} <input type=hidden id=fldprojectidreference value={$head['DATA']['projectid']}></td></tr>
    <tr><td colspan=2 id=projDspProjectTitle>{$head['DATA']['projecttitle']}</td></tr>

    <tr><td colspan=2 class=projDspHeader>Details</td></tr>
    <tr><td class=datalabel>Submission Date:&nbsp;</td><td class=dataline>{$head['DATA']['submissiondate']}&nbsp;</td></tr> 
    <tr><td class=datalabel>IRB Number:&nbsp;</td><td class=dataline>{$head['DATA']['irbnbr']}&nbsp;</td></tr>
    <tr><td class=datalabel>IRB Expiration:&nbsp;</td><td class=dataline>{$head['DATA']['irbexpiration']}&nbsp;</td></tr>
    <tr><td class=datalabel>PFRP Approval Date:&nbsp;</td><td class=dataline>{$head['DATA']['approvalyear']}&nbsp;</td></tr>
    <tr><td class=datalabel>PFRP Approval Number:&nbsp;</td><td class=dataline>{$head['DATA']['pfcapprovalnumber']}&nbsp;</td></tr>
    <tr><td class=datalabel>PFRP Expiration Date:&nbsp;</td><td class=dataline>{$head['DATA']['pfcapprovalexpiration']}&nbsp;</td></tr>
    <tr><td colspan=2>Project Comments</td></tr>
    <tr><td colspan=2>{$head['DATA']['projectcomments']}</td></tr>

    <tr><td colspan=2 class=projDspHeader>Contacts</td></tr>
    <tr><td class=datalabel>Investigator: </td><td class=dataline>{$piname}</td></tr>
    <tr><td class=datalabel>Investigator's Phone: </td><td class=dataline>{$piphone}</td></tr>
    <tr><td class=datalabel>Investigator's Email: </td><td class=dataline>{$piemail}</td></tr>
    <tr><td class=datalabel>Submitter's Name: </td><td class=dataline>{$subname}</td></tr>
    <tr><td class=datalabel>Submitter's Phone: </td><td class=dataline>{$subphone}</td></tr>
    <tr><td class=datalabel>Submitter's Email: </td><td class=dataline>{$subemail}</td></tr>

    <tr><td colspan=2 class=projDspHeader>Questions Answered</td></tr>
    <tr><td colspan=2>{$innerAns}</td></tr>
    <tr><td colspan=2 class=projDspHeader>Project Doucments (Click to View)</td></tr>
    <tr><td colspan=2>{$innerDocs}</td></tr>
  </table>

  
  </td>
<td id=reviewPanel valign=top>

{$reviewSide}

</td>
</tr></table>

<div id=modalBack></div>
<div id=pdfDisplay>    <table style="width: 79vw;height: 78vh;" border=0><tr><td align=right style="height: 2vh;"><span id=closeMod  onclick="closeModal();">&times;</span></td></tr><tr><td valign=top><div id=displayThisPDF>PDF</div></td></tr></table></div>
<div id=emailerDisplay><table style="width: 79vw;height: 78vh;" border=0><tr><td align=right style="height: 2vh;"><span id=closeMod2 onclick="closeModal();">&times;</span></td></tr><tr><td valign=top><div id=emailer>

<table border=0>
<tr><td>Choose a recipient</td></tr>
<tr><td>{$emailSelect}</td></tr>
<tr><td><TEXTAREA id=emailMessage></TEXTAREA></td></tr>
<tr><td align=right><table id=pfrpSendButton onclick="sendReferalMessage();"><tr><td>Send</td></tr></table></td></tr>
</table>

</div></td></tr></table></div>

DSPTHIS;
} else {
  $m = json_decode($pHold['message'],true);  
  $dspThis = $m['MESSAGE'];
}
//LOOKUP PROJECT BY ID    
$pageContent = <<<RTNTHIS
{$dspThis}
RTNTHIS;
  } else { 
  $pageContent = <<<RTNTHIS
NOT A VALID PROJECT NUMBER
RTNTHIS;
  }
} else {
//BUILD STATUS PULL     
$sid = serverIdent;
$apk = apikey;
$passData = json_encode(array("pennkey" => chtnencrypt($this->userInfo['pfcPennKey'])));    
$statWS = json_decode(callpfcrestapi("POST","https://data.chtneast.org/pfcapplication/livestatuslist",pfcaccess,$passData), true);
if ($statWS['responseCode'] === 200) { 
    $stdta = json_decode($statWS['message'], true);   
    if ((int)$stdta['ITEMS'] < 1) {
      $statusMenu = "NO MENU ITEMS FOUND"; 
    } else {
      $statusMenu = "<table border=0 id=statusMenuTbl>";
      foreach ($stdta['DATA'] as $vl) {
        $dspStatus = str_replace(" / ","<br>&nbsp;&nbsp;&nbsp;&nbsp;",$vl['projstatusdsp']);

        $statusMenu .= "<tr><td class=\"menuOption\" onclick=\"navigateSite('project-listing&status=" . urlencode($vl['statusmodifier']) ."');\">{$dspStatus}</td></tr>"; 
      }       
      $statusMenu .= "</table>";
    }
} else { 
    //NO MENU
    $statusMenu = "USER NOT ALLOWED";
}

if (trim($rqstDetermine['status']) !== "") {
  $crd['qryStatus'] = urldecode($rqstDetermine['status']);
  $dta['datapayload'] = json_encode($crd); 
  $passData = json_encode($dta);  
  $statList = json_decode(callpfcrestapi("POST","https://data.chtneast.org/pfcapplication/projectsbystatus",pfcaccess,$passData), true);
  $statDta = json_decode($statList['message'],true);
  $statTblDta = json_decode($statDta['DATA'], true);
  $projectCount = $statDta['ITEMS'];
  $addS = "";
  if ((int)$projectCount > 1) { 
    $addS = "s";
  }

  $statusTbl = "<center><h1>" . strtoupper(urldecode($rqstDetermine['status'])) . "</h1></center>";
  $statusTbl .= "<table border=0 id=projectLister><thead><tr><td colspan=12 id=pcounter>{$projectCount} project{$addS} found ({$statDta['MESSAGE']})</td></tr>";
  $statusTbl .= "<tr><th style=\"text-align: center;\">PDF</th><th>Project #</th><th>PI Name</th><th>PI Phone</th><th>PI Email</th><th>Submitter</th><th>Project Title</th><th>IRB #</th><th>IRB Expire</th><th>Last Statused</th><th>Last Date</th><th>PFC Approval</th></tr><thead><tbody>";
  foreach($statDta['DATA'] as $sv => $vl) { 
     $pdficon = "&nbsp;";
     if (dta['DATA'][i]['projectpdffilename'].trim() !== "") { 
       $pdficon = "<i class=\"material-icons\">picture_as_pdf</i>";
     }   
     $projn = (int)$vl['dspprojectid'];
    $statusTbl .=  <<<TBLLINE
<tr onclick="navigateSite('project-listing&projid={$projn}');">
<td><center>{$pdficon}</center></td>
<td>{$vl['dspprojectid']}</td>
<td>{$vl['piname']}</td>
<td>{$vl['piphone']}</td>
<td>{$vl['piemail']}</td>
<td>{$vl['submittingpennkey']}</td>
<td style="width: 25vw;">{$vl['projectTitle']}</td>
<td>{$vl['irbNbr']}</td>
<td>{$vl['irbExpiration']}</td>
<td>{$vl['laststatusby']}</td>
<td>{$vl['statusDate']}</td>
<td>{$vl['pfcapprovalnbr']}</td>
</tr>     
TBLLINE;
  }
  $statusTbl .= "</tbody></table>";
} else { 
  $statusTbl = "";
}

$pageContent = <<<RTNTHIS
<table border=0 id=statDspHoldTbl><tr><td valign=top id=statDspSidePanel>{$statusMenu}</td><td valign=top><div id=projectDisplayDiv>{$statusTbl}</div></td></tr></table>
RTNTHIS;
}
   $this->body = array("contentHeader" => $cHeader, "contentSection" => $pageContent, "contentFooter" => $cFooter);

   $this->response = 200;
   $rtnData['response'] = $this->response;
   $rtnData['pageReturned'] = $this->pageReturned;
   $rtnData['preamble'] = $this->preamble;
   $rtnData['pageHead'] = $this->head;
   $rtnData['stylesheet'] = $this->style;
   $rtnData['javascriptr'] = $this->java;
   $rtnData['userInfo'] = $this->userInfo;
   $rtnData['pageBody'] = $this->body; 
   return json_encode($rtnData);
}

function membertemplate($rqst, $usr) { 
    $tt = pfcurl;
    $securett = pfcsecureurl;
    $this->pageReturned = $rqst;
    $this->preamble = "<!DOCTYPE html>\n<html>";
    $standHead = self::standardHeader();
    $this->head = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}/?page={$rqst} //-->
{$standHead}
<title>PFRP Data Application</title>
HDR;
    $ss = self::globalStyle();
    $this->style = <<<STYLESHT
<style>
{$ss}
body {margin-top: 9vh; } 
</style>
STYLESHT;

$jvscript = self::globaljavascriptr(); 
    $this->java = <<<JAVASCRIPTR
<script lang=javascript>
{$jvscript}
</script>

JAVASCRIPTR;

    $this->userInfo = array("pfcMember" => true, "pfcPennKey" => $usr['pfcpennkey'], "pfcMemberFirstName" => $usr['firstname'], "pfcMemberLastName" => $usr['lastname'], "pfcMemberId" => $usr['memid'], "pfcMemberEmail" => $usr['email']);
    $cHeader = self::topAndMenuBarMember($usr['pfcpennkey'], "{$usr['firstname']} {$usr['lastname']}", "{$usr['email']}" );
    $cFooter = self::standardFootMember();

$pageContent = <<<RTNTHIS
MEMBER TEMPLATE PAGE

RTNTHIS;

   $this->body = array("contentHeader" => $cHeader, "contentSection" => $pageContent, "contentFooter" => $cFooter);

   $this->response = 200;
   $rtnData['response'] = $this->response;
   $rtnData['pageReturned'] = $this->pageReturned;
   $rtnData['preamble'] = $this->preamble;
   $rtnData['pageHead'] = $this->head;
   $rtnData['stylesheet'] = $this->style;
   $rtnData['javascriptr'] = $this->java;
   $rtnData['userInfo'] = $this->userInfo;
   $rtnData['pageBody'] = $this->body; 
   return json_encode($rtnData);
}

function myprojects($rqst, $usr, $pfrpid = "", $passedprojid = "") { 
  $tt = pfcurl;
  $securett = pfcsecureurl;
  $this->pageReturned = $rqst;
  $this->preamble = "<!DOCTYPE html>\n<html>";

  $this->head = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}/?page={$rqst} //-->
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<meta http-equiv="refresh" content="28800">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-16x16.png" sizes="16x16"/>
<title>My Projects Pathology Feasibility Review Panel</title>   
HDR;

$this->userInfo = array("pfcMember" => false, "pfcPennKey" => $usr['pfcpennkey'], "pfcMemberFirstName" => "", "pfcMemberLastName" => "", "pfcMemberId" => $pfrpid, "pfcMemberEmail" => "");      
$cHeader = self::topAndMenuBarUser($this->userInfo['pfcPennKey'], $this->userInfo['pfcMemberId']);

$ss = self::globalStyle();
  $this->style = <<<STYLESHT
<style>
{$ss}
body {margin-top: 9vh; }
#projectDspHold {font-family: Roboto; font-size: 1.3vh; border-collapse: collapse;}
#projectNbrTitle {font-size: 1.8vh; font-weight: bold; border-bottom: 3px solid rgba({$this->color_mgrey},1); color: rgba({$this->color_mgrey},1);} 
#projectVariablesTbl { }
.projectFieldLabel {font-size: 1.2vh; font-weight: bold; color: rgba({$this->color_zgrey},1); }
.projectDataField {font-size: 1.2vh;width: 15vw;padding: 10px 0 10px 5px;border: 1px solid rgba({$this->color_zgrey},1); border-bottom: 3px solid rgba({$this->color_lblue},1);   border-radius: 0;box-sizing: border-box;background: rgba({$this->color_white},1); }
.projectDataField[readonly] {background: rgba({$this->color_lgrey},1); }
#submitterTbl { }
#submitterTitle {font-size: 1.4vh; font-weight: bold; color: rgba($this->color_mgrey,1); border-bottom: 3px solid rgba({$this->color_mgrey},1);padding-top: 2vh; } 
#piTbl { }
#piTitle {font-size: 1.4vh; font-weight: bold; color: rgba($this->color_mgrey,1); border-bottom: 3px solid rgba({$this->color_mgrey},1);padding-top: 2vh; } 
#fldPISalutations {width: 10vw; }
#frmprojectcomments { width: 100%; height: 5vh; padding: 12px 20px; box-sizing: border-box; border: 1px solid rgba({$this->color_zgrey},1);border-radius: 0; background-color: rgba({$this->color_white},1); resize: none; font-size: 1.2vh; }
#frmprojectcomments[readonly] {background: rgba({$this->color_lgrey},1); }
.dspQuestion {font-size: 1.4vh; border-bottom: 1px solid rgba({$this->color_grey},1); }
.pdficondsp:hover {cursor: pointer;}
#sbmtBtn {border: 1px solid rgba({$this->color_zgrey},1); background: rgba({$this->color_lblue},1);color: rgba({$this->color_white},1);  }
#sbmtBtn:hover {background: rgba({$this->color_dblue},1);cursor: pointer; }
#sbmtBtn {text-align: center;padding: 1vh 1vw 1vh 1vw; }
#myprojectstitle {font-size: 1.4vh; font-weight: bold; color: rgba($this->color_mgrey,1); border-bottom: 3px solid rgba({$this->color_mgrey},1);padding-top: 2vh; } 
#projectListingTbl { width: 80vw; font-family: Roboto; font-size: 1.1vh; border-collapse: collapse;}
#projectListingTbl .projListCell { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
.rowhov:hover {background: rgba({$this->color_grey},1);cursor: pointer; }
.qstionSelect {background: rgba({$this->color_white},1); border: 1px solid rgba({$this->color_zgrey},1);padding: .7vh .5vw .7vh 1vw;width: 10vw; }
.reqAstrk {font-size: 1.8vh; color: rgba({$this->color_bred},1); }
#modalBack {position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 100; background: rgba({$this->color_black},.6); display: none; }

#pdfDisplay { width: 80vw; height: 80vh; position: fixed; margin-top: -40vh; top: 50%; background: rgba({$this->color_white},1); z-index: 101; margin-left: -40vw; left: 50%; border: 8px solid rgba({$this->color_mgrey},1);box-sizing: border-box; display: none; overflow: hidden; }
#displayThisPDF {height: 75vh; width: 78vw;overflow: auto; }
#closeMod {font-size: 1.8vh; color: rgba({$this->color_mgrey},1); font-weight: bold; } 
#closeMod:hover {cursor: pointer; color: rgba({$this->color_bred},1); }



</style>
STYLESHT;

$jvscript = self::globaljavascriptrUser();
$this->java = <<<JAVASCRIPTR
<script lang=javascript>
{$jvscript}
</script>

JAVASCRIPTR;

  if (trim($passedprojid) !== "") { 

   //CHECK USER HAS PERMISSION FOR THIS PROJECT - OR CREATE NEW PROJECT
   if ($passedprojid !== 'new' && is_numeric($passedprojid)) { 
     $passData = json_encode(array("pennkey" => chtnencrypt($this->userInfo['pfcPennKey']),"projectid" => $passedprojid, "pfrpid" => $pfrpid));
     $pHold = json_decode(callpfcrestapi("POST","https://data.chtneast.org/pfcapplication/getaproject",pfcaccess,$passData), true);
     $projectData  = json_decode($pHold['message'], true);    
     $frmprojectid = "# {$projectData['DATA']['projectid']}";
     $frmprojectpdf = $projectData['DATA']['projectpdf'];
     $frmprojecttitle = $projectData['DATA']['projecttitle'];
     $frmprojectirb = $projectData['DATA']['irbnbr'];
     $frmprojectirbexpiration = $projectData['DATA']['irbexpiration'];
     $frmprojectapprovalyear = $projectData['DATA']['approvalyear'];
     $frmprojectcompleteind = $projectData['DATA']['completeind'];
     $frmprojectpfcapprovalnumber = $projectData['DATA']['pfcapprovalnumber'];
     $frmprojectpfcapprovalexpiration = $projectData['DATA']['pfcapprovalexpiration'];
     $frmprojectidsubmission = $projectData['DATA']['submissiondate'];
     $frmprojectidcomment = $projectData['DATA']['projectcomments'];
     $frmprojectsubmitter = $projectData['DATA']['bywhom']; 
     foreach ($projectData['DATA']['contacts'] as $cvals) {
       switch ($cvals['contacttype']) {
         case 'SUBMITTER':
           $subName = explode(",",$cvals['contactname']);
           $subphone = $cvals['phonenbr'];
           $subemail = $cvals['emailaddress'];
         break;
         case 'PROJECT-PI':
           $piName = explode(",",$cvals['contactname']);
           $piphone = $cvals['phonenbr'];
           $piemail = $cvals['emailaddress'];
           $piSalut = $cvals['salutation'];
         break;
       }
     }

     $qstn = $projectData['DATA']['questionanswers'];
     foreach ($projectData['DATA']['questionanswers'] as $qval) { 
       $yesno = "<input type=text value=\"{$qval['answer']}\" READONLY class=projectDataField>";  
       $qColumns .= "<tr><td colspan=3 class=dspQuestion>{$qval['question']}</td><td>{$yesno}</td></tr>";
     }

     if (trim($frmprojectpdf) !== "" ) { 
       $docRows = "<tr><td>PROJECT PDF</td><td onclick=\"grabdocumentpdf('{$frmprojectpdf}');\" class=\"pdficondsp\"><i class=\"material-icons\">picture_as_pdf</i></td><td></td></tr>";
     }
     foreach ($projectData['DATA']['documents'] as $dvals) {
       $docRows .= "<tr><td>{$dvals['typeofdocument']} ({$dvals['originaldocumentname']})</td><td onclick=\"grabdocumentpdf('{$dvals['directorydocumentname']}');\" class=\"pdficondsp\"><i class=\"material-icons\">picture_as_pdf</i></td><td>{$dvals['uploadedon']} ({$dvals['uploadedby']})</td></tr>";
     }
     
     $pbrpMetrics = <<<PBRPM
             <tr><td class=projectFieldLabel style="padding-top: 2vh;">Approval Year</td><td class=projectFieldLabel style="padding-top: 2vh;">PFRP Approval #</td><td class=projectFieldLabel style="padding-top: 2vh;">PFRP Approval Expiration</td><td></td></tr>
<tr>
  <td><input type=text id=frmprojectapprvyr class=projectDataField READONLY value="{$frmprojectapprovalyear}"></td>
  <td><input type=text id=frmprojectapprnbr class=projectDataField READONLY value="{$frmprojectpfcapprovalnumber}"></td>
  <td><input type=text id=frmprojectappexp class=projectDataField READONLY value="{$frmprojectpfcapprovalexpiration}"></td>
  <td></td>
</tr>
PBRPM;
     
     $RO = " READONLY ";
   } else { 

       $qstnHold = json_decode(callpfcrestapi("GET","https://data.chtneast.org/globalmenu/pbrpyesnoqstn",pfcaccess), true);
       $qstD = json_decode($qstnHold['datareturn'], true);
       foreach($qstD['DATA'] as $qVals) {
           $yesno = "<select id=\"fldAnswerId{$qVals['codevalue']}\" class=\"qstionSelect\"><option value=\"NO\">NO</option><option value=\"YES\">YES</option></select>";
           $qColumns .= "<tr><td colspan=3 class=dspQuestion>{$qVals['menuvalue']} <span class=reqAstrk>*</span></td><td>{$yesno}</tr>";
       }
        
       $docHold = json_decode(callpfcrestapi("POST","https://data.chtneast.org/pfcapplication/requireddocumentlist",pfcaccess), true);
       $requiredDocs = json_decode($docHold['message'],true);       
       foreach ($requiredDocs['DATA'] as $rqd) { 

        if ($rqd['docid'] === 'ADDITIONAL-DOCUMENT') { 
         $docRows .= "<tr><td class=dspQuestion><div><label for=\"file\">Upload {$rqd['documenttype']}: &nbsp;</label><input type=\"file\" id=\"doc{$rqd['docid']}\" name=\"doc{$rqd['docid']}\" accept=\".pdf\" onchange=\"btoathisfile('bto{$rqd['docid']}', this.files[0]);\"><TEXTAREA id=\"bto{$rqd['docid']}\" style=\"display: none;\"></textarea></div></td></tr>";
        } else { 
         $docRows .= "<tr><td class=dspQuestion><div><label for=\"file\">Upload {$rqd['documenttype']}: <span class=reqAstrk>*</span>&nbsp;</label><input type=\"file\" id=\"doc{$rqd['docid']}\" name=\"doc{$rqd['docid']}\" accept=\".pdf\" onchange=\"btoathisfile('bto{$rqd['docid']}', this.files[0]);\"><TEXTAREA id=\"bto{$rqd['docid']}\" style=\"display: none;\"></textarea></div></td></tr>";
        }

       }
       $RO = "";
       $submitbtn = "<tr><td colspan=4 align=right><button onclick=\"submitPFRPApplication();\" id=sbmtBtn>Submit<br>Application</button></td></tr>";
   }

$saluHold = json_decode(callpfcrestapi("GET","https://data.chtneast.org/globalmenu/standardsalutations",pfcaccess), true);
if ($saluHold['responseCode'] === 200) { 
  $saluVal = json_decode($saluHold['datareturn'], true);
  foreach($saluVal['DATA'] as $saluv) { 
      $saluMenu .= "<option value=\"{$saluv['menuvalue']}\">{$saluv['menuvalue']}</option>";
} 
} else {
  $saluMenu = "ERROR NO MENU FOUND IN SERVICE";
}

$salutList = "<select id=fldPISalutations class=qstionSelect style=\"width: 15vw;padding: .7vh 0 .7vh .3vw;\">{$saluMenu}</select>";

$cpiTbl = <<<CONTACTFORMPI
<table border=0 id=piTbl>
  <tr><td colspan=3 id=piTitle>PRINCIPAL INVESTIGATOR CONTACT INFORMATION</td></tr>
  <tr><td class=projectFieldLabel>First Name <span class=reqAstrk>*</span></td><td class=projectFieldLabel>Last Name<span class=reqAstrk>*</span></td></tr>
  <tr><td><input type=text id=frmcontactpifname value="{$piName[1]}" class=projectDataField {$RO}></td>
      <td><input type=text id=frmcontactpilname value="{$piName[0]}" class=projectDataField {$RO}></td>
  </tr>
  <tr><td colspan=2>
   
   <table>
    <tr><td class=projectFieldLabel>Salutations: <span class=reqAstrk>*</span></td><td>{$salutList}</td></tr>
    <tr><td class=projectFieldLabel>Phone: <span class=reqAstrk>*</span></td><td><input type=text id=frmcontactpiphone class=projectDataField value="{$piphone}" {$RO}></td></tr>
    <tr><td class=projectFieldLabel>Email: <span class=reqAstrk>*</span></td><td><input type=text id=frmcontactpiemail class=projectDataField value="{$piemail}" {$RO}></td></tr>
    </table>

</td></tr>
</table>
CONTACTFORMPI;

$csubmTbl = <<<CONTACTFORMSUBMITTER

<table border=0 id=submitterTbl>
  <tr><td colspan=2 id=submitterTitle>SUBMITTER CONTACT INFORMATION</td></tr>
  <tr><td class=projectFieldLabel>First Name <span class=reqAstrk>*</span></td><td class=projectFieldLabel>Last Name <span class=reqAstrk>*</span></td></tr>
  <tr><td><input type=text id=frmcontactsubmitterfname class=projectDataField value="{$subName[1]}" {$RO}></td>
      <td><input type=text id=frmcontactsubmitterlname class=projectDataField value="{$subName[0]}" {$RO}>
      </td>
  </tr>
  <tr><td colspan=2>

<table>
<tr><td class=projectFieldLabel>Phone: <span class=reqAstrk>*</span></td><td><input type=text id=frmcontactsubmitterphone value="{$subphone}" class=projectDataField style="width: 27vw;"  {$RO}></td></tr>
<tr><td class=projectFieldLabel>Email: <span class=reqAstrk>*</span></td><td><input type=text id=frmcontactsubmitteremail value="{$subemail}" class=projectDataField style="width: 27vw;" {$RO}></td></tr>
</table>

</td></tr>
</table>
CONTACTFORMSUBMITTER;

$projHDTbl = <<<PRJHDTBL
<table border=0 id=projectVariablesTbl>
<tr><td colspan=4 class=projectFieldLabel style="padding-top: 2vh; ">Project Title <span class=reqAstrk>*</span></td></tr>
<tr><td colspan=4><input type=text id=fldProjecTitle value="{$frmprojecttitle}" class=projectDataField style="width: 61vw;" {$RO}></td></tr> 
<tr><td class=projectFieldLabel>IRB # <span class=reqAstrk>*</span></td><td class=projectFieldLabel>IRB Expiration <span class=reqAstrk>*</span></td><td class=projectFieldLabel>Submitter</td><td>Submitted On</td></tr>
<tr>
  <td><input id=fldprojectirbnbr value="{$frmprojectirb}" class=projectDataField style="width: 15vw;" {$RO}></td>
  <td><input id=fldprojectirbexp value="{$frmprojectirbexpiration}" class=projectDataField style="width: 15vw;" {$RO}></td>
  <td><input id=fldprojectsubmitter value="{$frmprojectsubmitter}" class=projectDataField style="width: 15vw;" READONLY></td>
  <td><input id=fldprojectsubmiton value="{$frmprojectidsubmission}" class=projectDataField style="width: 15vw;" READONLY></td>
</tr>
<tr><td colspan=2 valign=top>
{$csubmTbl}
</td><td colspan=2 valign=top>
{$cpiTbl}
</td></tr>
<tr><td colspan=4 class=projectFieldLabel>Project Comments</td></tr>
<tr><td colspan=4><TEXTAREA id=frmprojectcomments {$RO}>{$frmprojectidcomment}</TEXTAREA></td></tr>

<tr><td colspan=4 class=projectFieldLabel style="padding-top: 2vh;">Questions</td></tr>
{$qColumns}

<tr><td colspan=4 class=projectFieldLabel style="padding-top: 2vh;">Project Documents</td></tr>
<tr><td colspan=4>
<table border=0>
{$docRows}
</table>
</td></tr>
{$pbrpMetrics}

{$submitbtn}
</table>
PRJHDTBL;


$bdy = <<<PROJECTTABLE
<center><table border=0 id=projectDspHold>
<tr><td id=projectNbrTitle>Project {$frmprojectid}</td></tr>
<tr><td>{$projHDTbl}</td></tr>
</table>

<div id=modalBack></div>
<div id=pdfDisplay>
<table style="width: 79vw;height: 78vh;" border=0><tr><td align=right style="height: 2vh;"><span id=closeMod onclick="closeModal();">&times;</span></td></tr>
<tr><td valign=top>
<div id=displayThisPDF>
PDF
</div>
</td></tr>
</table>
</div>

PROJECTTABLE;


  } else {
    //LIST OF MY PROJECTS  
    $passData = json_encode(array("pennkey" => chtnencrypt($this->userInfo['pfcPennKey'])));    
    $projWS = json_decode(callpfcrestapi("POST","https://data.chtneast.org/pfcapplication/getmyprojects",pfcaccess,$passData), true);
    $projDta = json_decode($projWS['message'],true);
    if ((int)$projDta['ITEMS'] === 0) { 
      $bdy = "NO PROJECT FOUND";
    } else { 

      $bdyTbl = "<center><table border=0><tr><td id=myprojectstitle>My PFRP Projects</td></tr><tr><td><p><table border=0 id=projectListingTbl><tr><th class=projListCell><center>Complete</th><th class=projListCell><center>Project<br>PDF</th><th class=projListCell>PFRP #</th><th class=projListCell>PI's Name</th><th class=projListCell>Project Title</th><th class=projListCell>IRB#</th><th class=projListCell>IRB<br>Expiration</th><th class=projListCell>PFRP Status</th><th class=projListCell>PFRP Condition</th><th class=projListCell>Status<br>Date</th><th class=projListCell>Submission<br>Date</th><th class=projListCell>PFRP<br>Approval #</th><th class=projListCell>PFRP<br>Approval Expiration</th></tr> ";
      foreach($projDta['DATA'] as $k => $v) { 
        $completechk = "";
        if ((int)$v['completeind'] === 1) { 
          $completechk = "<i class=\"material-icons\">check</i>";
        }
        $pdfico = "";
        if (trim($v['projectpdf']) !== "") { 
          $pdfico = "<i class=\"material-icons\">picture_as_pdf</i>";
        }
        $pid = (int)$v['projectid'];
        $bdyTbl .= "<tr onClick=\"navigateSite('my-projects&pfrpid={$pfrpid}&projid={$pid}');\" class=rowhov>" 
                 . "<td class=projListCell><center>{$completechk}</td>"
                 . "<td class=projListCell><center>{$pdfico}</td>"
                 . "<td class=projListCell>{$v['projectid']}</td>"
                 . "<td class=projListCell>{$v['projectpi']}</td>"
                 . "<td class=projListCell>{$v['projecttitle']}</td>"
                 . "<td class=projListCell>{$v['irbnbr']}</td>"
                 . "<td class=projListCell>{$v['irbexpiration']}</td>"
                 . "<td class=projListCell>{$v['datastatus']}</td>"
                 . "<td class=projListCell>{$v['statusmodifier']}</td>"
                 . "<td class=projListCell>{$v['statusdate']}</td>"
                 . "<td class=projListCell>{$v['projectcreationdate']}</td>"
                 . "<td class=projListCell>{$v['pfcapprovalnumber']}</td>"
                 . "<td class=projListCell>{$v['pfcapprovalexpiration']}</td>"
                 . "</tr>";
      }
      $bdyTbl .= "</table></td></tr></table>";
      $bdy = $bdyTbl;
    }
  }
  $this->body = array("contentHeader" => $cHeader, "contentSection" => $bdy, "contentFooter" => "");
  $this->response = 200;
  $rtnData['response'] = $this->response;
  $rtnData['pageReturned'] = $this->pageReturned;
  $rtnData['preamble'] = $this->preamble;
  $rtnData['pageHead'] = $this->head;
  $rtnData['stylesheet'] = $this->style;
  $rtnData['javascriptr'] = $this->java;
  $rtnData['userInfo'] = $this->userInfo;
  $rtnData['pageBody'] = $this->body; 
  return json_encode($rtnData);        
}

function genuserapphome($rqst, $usr, $pfrpid = "") { 
  $tt = pfcurl;
  $securett = pfcsecureurl;
  $this->pageReturned = $rqst;

  $this->preamble = "<!DOCTYPE html>\n<html>";

  $this->head = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}/?page={$rqst} //-->
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<meta http-equiv="refresh" content="28800">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-16x16.png" sizes="16x16"/>
<title>Pathology Feasibility Review Panel Application</title>   
HDR;

$ss = self::globalStyle();
  $this->style = <<<STYLESHT
<style>
{$ss}
body {margin-top: 9vh; } 
</style>
STYLESHT;

$jvscript = self::globaljavascriptrUser(); 
$this->java = <<<JAVASCRIPTR
<script lang=javascript>
{$jvscript}
</script>

JAVASCRIPTR;

  $this->userInfo = array("pfcMember" => false, "pfcPennKey" => $usr['pfcpennkey'], "pfcMemberFirstName" => "", "pfcMemberLastName" => "", "pfcMemberId" => $pfrpid, "pfcMemberEmail" => "");      
  $cHeader = self::topAndMenuBarUser($this->userInfo['pfcPennKey'], $this->userInfo['pfcMemberId']);
  $bdy = "<center><table style=\"width: 80vw;\"><tr><td>MAIN WELCOME SCREEN</td></tr></table>";
  $this->body = array("contentHeader" => $cHeader, "contentSection" => $bdy, "contentFooter" => "");
  $this->response = 200;
  $rtnData['response'] = $this->response;
  $rtnData['pageReturned'] = $this->pageReturned;
  $rtnData['preamble'] = $this->preamble;
  $rtnData['pageHead'] = $this->head;
  $rtnData['stylesheet'] = $this->style;
  $rtnData['javascriptr'] = $this->java;
  $rtnData['userInfo'] = $this->userInfo;
  $rtnData['pageBody'] = $this->body; 
  return json_encode($rtnData);        
}
    
function notfound($rqst, $usr) { 
  $tt = pfcurl;
  $securett = pfcsecureurl;
  $at = genAppFiles;
  $pPic = base64file( "{$at}/publicobj/graphics/psom_logo_white.png", "PSOMLogo404", "image", true); 
  $this->pageReturned = $rqst;
  $this->preamble = "<!DOCTYPE html>\n<html>";
  $this->head = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}/?page={$rqst} //-->
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<meta http-equiv="refresh" content="28800">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-16x16.png" sizes="16x16"/>
<title>PFRP-PAGE NOT FOUND</title>
HDR;
  $this->style = <<<STYLESHT
<style type="text/css">
@import url(https://fonts.googleapis.com/css?family=Roboto|Share+Tech+Mono|Material+Icons);
html {margin: 0; height: 100%; width: 100%; font-family: Roboto; font-size: 2vh; }
body {background-color:#3686be;color:#FFF;border:0px;margin:0px; overflow:hidden;}

#PSOMLogo404 { height: 6vh; }
#errDspTbl {position: fixed;width: 50vw; height: 30vh; margin-left: -25vw; margin-top: -15vh; top: 50%; left: 50%; }
#errDspTbl #pfrpLine {text-align: center; font-size: 2.8vh; font-weight: bold;padding: 2vh 0 0 0; }
#errDspTbl #errorLine {padding: 3vh 0 0 0; border-bottom: 2px solid rgba({$this->color_white},1); }
#errDspTbl #errorDesc {padding: 1vh 1vw 1vh 1vw;text-align: justify; }
</style>
STYLESHT;
  $this->java = <<<JAVASCRIPTR
                                
JAVASCRIPTR;
  $pageContent = <<<RTNTHIS
<table border=0 id=errDspTbl>
<tr><td><center>{$pPic}</td></tr>
<tr><td id=pfrpLine>University of Pennsylvania Pathology Feasibility Review Panel</td></tr>
<tr><td id=errorLine>ERROR: 404 - PAGE NOT FOUND</td></tr>
<tr><td id=errorDesc>The page requested, "{$rqst}", was not found on this server.  If you believe that this is in error please contact the system administrator, Zachery von Menchhofen at (215) 662-4570.  Click the back button in your browser to return to the previous page.</td></tr>
</table>
RTNTHIS;

  $this->body = array("contentHeader" => "", "contentSection" => $pageContent, "contentFooter" => "");
  $this->response = 200;
  $rtnData['response'] = $this->response;
  $rtnData['pageReturned'] = $this->pageReturned;
  $rtnData['preamble'] = $this->preamble;
  $rtnData['pageHead'] = $this->head;
  $rtnData['stylesheet'] = $this->style;
  $rtnData['javascriptr'] = $this->java;
  $rtnData['userInfo'] = $this->userInfo;
  $rtnData['pageBody'] = $this->body; 
  return json_encode($rtnData);        
}
    
}