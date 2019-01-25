<?php 

class dataposters { 

  public $responseCode = 400;
  public $message = "";
  public $rtnData = "";

function __construct() { 
    $args = func_get_args(); 
    $nbrofargs = func_num_args(); 
    $this->rtnData = $args[0];    
    if (trim($args[0]) === "") { 
    } else { 
      $request = explode("/", $args[0]); 
      if ( trim($args[1]) === "") { 
        $this->responseCode = 400; 
        $this->rtnData = json_encode(array("MESSAGE" => "DATA NAME MISSING","ITEMSFOUND" => 0, "DATA" => array()    ));
      } else { 
        //$dp = new $request[2](); 
        $dp = new datacontrol(); 
        if (method_exists($dp, 'corecontrol')) { 
            //$funcName = trim($request[1]); 
          $funcName = "corecontrol";
          $dataReturned = $dp->$funcName($args[0], $args[1]); 
          $this->responseCode = $dataReturned['statusCode'];
          $this->message = $dataReturned['message']; 
          $this->rtnData = json_encode($dataReturned['data']);
        } else { 
          $this->responseCode = 404; 
          $this->message = ""; 
          $this->rtnData = json_encode(array("MESSAGE" => "END-POINT FUNCTION NOT FOUND:","ITEMSFOUND" => 0, "DATA" => ""));
        }
      }
    }
}

}

class datacontrol {

function corecontrol($request, $passedData) { 
    //{"functiontype":"PAGE","functionname":"publichome","mobileindicator":"w"}
    $pd = json_decode($passedData, true); 

    switch ($pd['functiontype']) { 
      case 'PAGE': 
        $pgeclass = new pagerequests();
        $pgenme = $pd['functionname'];
        $pge = $pgeclass->$pgenme($pd['mobileindicator']);
        $rows['statusCode'] = 200; 
        $rows['message'] = "RETURNED PAGE: {$pgenme}";
        $rows['data'] = $pge;           
      break;
    }   
    return $rows;
}


}


class pagerequests {

   function contactpfrp($mobilelayout) {
      $rqst = __FUNCTION__;
      $pgH = new pgHeader();
      $pgS = new pgStyles();
      $jv = new pgScriptr();
      $bdy = new pgBody();
      $preamb = $pgH->$rqst($mobilelayout);
      $sty = $pgS->$rqst($mobilelayout);
      $jvcontent = $jv->$rqst($mobilelayout);
      $pgContent = $bdy->$rqst($mobilelayout);
      return json_encode(array(
         "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
       , "head" => htmlspecialchars($preamb)
       , "style" => htmlspecialchars($sty)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       ));
    }

    function pfrpprocess($mobilelayout) {
      $rqst = __FUNCTION__;
      $pgH = new pgHeader();
      $pgS = new pgStyles();
      $jv = new pgScriptr();
      $bdy = new pgBody();
      $preamb = $pgH->$rqst($mobilelayout);
      $sty = $pgS->$rqst($mobilelayout);
      $jvcontent = $jv->$rqst($mobilelayout);
      $pgContent = $bdy->$rqst($mobilelayout);
      return json_encode(array(
         "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
       , "head" => htmlspecialchars($preamb)
       , "style" => htmlspecialchars($sty)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       ));
    }

    function publichome($mobilelayout = "") {
      $rqst = __FUNCTION__;
      $pgH = new pgHeader();
      $pgS = new pgStyles();
      $jv = new pgScriptr();
      $bdy = new pgBody();

      $preamb = $pgH->$rqst($mobilelayout);
      $sty = $pgS->$rqst($mobilelayout);
      $jvcontent = $jv->$rqst($mobilelayout);
      $pgContent = $bdy->$rqst($mobilelayout);

        return json_encode(array(
            "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
            , "head" => htmlspecialchars($preamb)
            , "style" => htmlspecialchars($sty)
            , "javascriptr" => $jvcontent
            , "body" => htmlspecialchars($pgContent)
            ));
    }

    function park($mobilelayout = "", $rqstPage = "") {
        return json_encode(array(
            "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
            , "head" => htmlspecialchars(park_header())
            , "style" => htmlspecialchars(park_style($mobilelayout))
            , "javascriptr" => htmlspecialchars("")
            , "body" => htmlspecialchars(park_body())
            ));
    }

}


class pgHeader {

 function contactpfrp($mobileIndicator) {
  $rtnThis = <<<RTNTHIS
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Pathology Feasibility Review Panel</title>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-16x16.png" sizes="16x16"/>
RTNTHIS;
    return $rtnThis;
 }

 function pfrpprocess($mobileIndicator) {
  $rtnThis = <<<RTNTHIS
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Pathology Feasibility Review Panel</title>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-16x16.png" sizes="16x16"/>
RTNTHIS;
    return $rtnThis;
 }

function publichome($mobileIndicator) {
  $rtnThis = <<<RTNTHIS
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Pathology Feasibility Review Panel</title>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-32x32.png" sizes="32x32"/>
<link rel="icon" type="image/png" href="https://www.upenn.edu/sites/default/files/favicons/favicon-16x16.png" sizes="16x16"/>
RTNTHIS;
    return $rtnThis;
}

}

class pgStyles {

  public $color_white = "255,255,255";
  public $color_black = "0,0,0";
  public $color_grey = "224,224,224";
  public $color_lgrey = "245,245,245";
  public $color_brwgrey = "239,235,233";
  public $color_ddrwgrey = "189,185,183";
  public $color_lamber = "255,248,225";
  public $color_mamber = "204,197,175";
  public $color_mgrey = "160,160,160";
  public $color_dblue = "0,32,113";
  public $color_mblue = "13,70,160";
  public $color_lblue = "84,113,210";
  public $color_zgrey = "48,57,71";
  public $color_neongreen = "57,255,20";
  public $color_bred = "237, 35, 0";
  public $color_nicered = "153,0,51";
  public $color_darkvariant = "0,0,81";
  public $color_primary = "26,35,126";

function pfrpprocess($mobileIndicator) {
  $appPath = genAppFiles;
if ($mobile !== "m") {
    $rtnThis = <<<RTNTHIS

\n@import url(https://fonts.googleapis.com/css?family=Roboto|Share+Tech+Mono|Material+Icons);
html {margin: 0; height: 100%; width: 100%; font-family: Roboto; font-size: 1vh; color: rgba({$this->color_zgrey},1);}
body {background-color: rgba({$this->color_mgrey},1);border:0px;margin:0px; }

#pageContentHolder {  width: 60vw; height: 100vh; position: fixed; top: 0vh; left: 20vw; background: rgba({$this->color_white},1); -webkit-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); -moz-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); border-left: 1px solid rgba({$this->color_zgrey},1); overflow: auto;}
.menuIconSqr { width: 4vw; text-align: center; padding: 1vh 0 0 0; }
.menuIconSqr .material-icons {font-size: 2.5vh; }
.menuIconSqr .material-icons:hover { color: rgba({$this->color_neongreen},1); cursor: pointer; }

#pageContentHolder #darkheadr {background: rgba({$this->color_darkvariant},1); color: rgba({$this->color_white},1); font-size: 1.2vh; padding: 1.1vh 0 1.1vh 0; text-align: right; z-index: 11; width: 60vw; position: absolute;  }
#pageContentHolder #liteheadr {background: rgba({$this->color_primary},1); color: rgba({$this->color_white},1);-webkit-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45);-moz-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); z-index: 10; position: absolute; top: 3vh; width: 60vw; }

#pageContentHolder #menuHolder {position: absolute; top: -5vh; background: #fff; z-index: 9; width: 60vw; border-bottom: 1px solid rgba({$this->color_zgrey},1); transition: top 1s; }
#pageContentHolder #liteheadr #applicationTitle {font-size: 2.3vh; padding: 3vh .8vw 2.2vh 0; }

#menuOptionsHolder td { width: 3vw; transition: background .5s, color .5s;  }
#menuOptionsHolder td:hover {cursor: pointer;background: rgba({$this->color_lblue},1);color: rgba({$this->color_neongreen},1);  }


#mainContent {padding: 15vh 1vw 0 1vw; font-size: 2.2vh; line-height: 2.2em; text-align: justify; }
#mainContent #title {font-size: 3.1vh; color: rgba({$this->color_zgrey},1); font-weight: bold; }
#mainContent #pfrpprocessdiagram {width: 40vw; }
RTNTHIS;
} else {
$rtnThis = <<<RTNTHIS
body {background-color:#000054;color:#FFF;border:0px;margin:0px;}
.wrapper {position:absolute;top:15%;left:0px;right:0px;width:100%;height:auto;}
.message {float:left;width: 90%;height:auto;font-size: 3vh;text-align:justify;font-family:Arial,Helvetica,sans-serif;margin-top:25px;margin-left: 3vw;margin-right: 3vw;line-height: 1.8em;}
RTNTHIS;
}
return $rtnThis;
}

function contactpfrp($mobileIndicator) {
$appPath = genAppFiles;
if ($mobile !== "m") {
    $rtnThis = <<<RTNTHIS
\n@import url(https://fonts.googleapis.com/css?family=Roboto|Share+Tech+Mono|Material+Icons);
html {margin: 0; height: 100%; width: 100%; font-family: Roboto; font-size: 1vh; color: rgba({$this->color_zgrey},1);}
body {background-color: rgba({$this->color_mgrey},1);border:0px;margin:0px; }

#pageContentHolder {  width: 60vw; height: 100vh; position: fixed; top: 0vh; left: 20vw; background: rgba({$this->color_white},1); -webkit-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); -moz-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); border-left: 1px solid rgba({$this->color_zgrey},1); overflow: auto;}
.menuIconSqr { width: 4vw; text-align: center; padding: 1vh 0 0 0; }
.menuIconSqr .material-icons {font-size: 2.5vh; }
.menuIconSqr .material-icons:hover { color: rgba({$this->color_neongreen},1); cursor: pointer; }

#pageContentHolder #darkheadr {background: rgba({$this->color_darkvariant},1); color: rgba({$this->color_white},1); font-size: 1.2vh; padding: 1.1vh 0 1.1vh 0; text-align: right; z-index: 11; width: 60vw; position: absolute;  }
#pageContentHolder #liteheadr {background: rgba({$this->color_primary},1); color: rgba({$this->color_white},1);-webkit-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45);-moz-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); z-index: 10; position: absolute; top: 3vh; width: 60vw; }

#pageContentHolder #menuHolder {position: absolute; top: -5vh; background: #fff; z-index: 9; width: 60vw; border-bottom: 1px solid rgba({$this->color_zgrey},1); transition: top 1s; }
#pageContentHolder #liteheadr #applicationTitle {font-size: 2.3vh; padding: 3vh .8vw 2.2vh 0; }

#menuOptionsHolder td { width: 3vw; transition: background .5s, color .5s;  }
#menuOptionsHolder td:hover {cursor: pointer;background: rgba({$this->color_lblue},1);color: rgba({$this->color_neongreen},1);  }

#mainFormHolder {padding: 15vh 0 0 0;  }
#contactFormTbl {font-family: Roboto; font-size: 1.8vh; }

#contactFormTbl #preamble { font-size: 2.2vh; padding: 0 2vw 1vh 2vw; }
#contactFormTbl .fieldLabel {font-size: 1.5vh; font-weight: bold; padding: 1vh 2vw 0 2vw;}
#contactFormTbl .fieldHolder {padding: 0 2vw 1vh 2vw; }
#contactFormTbl .standardField {font-family: Roboto; font-size: 1.8vh;border: none;  width: 20vw; padding: 1vh 0 1vh 0; box-sizing: border-box; background: rgba({$this->color_white},1); -webkit-transition: width 0.4s ease-in-out; transition: width 0.4s ease-in-out;resize: none;}
#contactFormTbl .standardField:focus { outline: none; -webkit-box-shadow: none; box-shadow: none; border-bottom: 3px solid rgba({$this->color_brwgrey},1); width: 54vw;}

.standardButton {border: 1px solid rgba({$this->color_zgrey},1); padding: 10px; transition: background .5s , color .5s; }
.standardButton:hover {cursor: pointer; background: rgba({$this->color_lblue},1); color: rgba({$this->color_neongreen},1); }

RTNTHIS;
} else {
$rtnThis = <<<RTNTHIS
body {background-color:#000054;color:#FFF;border:0px;margin:0px;}
.wrapper {position:absolute;top:15%;left:0px;right:0px;width:100%;height:auto;}
.message {float:left;width: 90%;height:auto;font-size: 3vh;text-align:justify;font-family:Arial,Helvetica,sans-serif;margin-top:25px;margin-left: 3vw;margin-right: 3vw;line-height: 1.8em;}
RTNTHIS;
}
return $rtnThis;
}

function publichome($mobileIndicator) {

$appPath = genAppFiles;
$icon = $appPath . "/publicobj/graphics/unsplash-testtube.jpg";
$testtube = base64file($icon, "upennicon", "image", false);

if ($mobile !== "m") {
    $rtnThis = <<<RTNTHIS

\n@import url(https://fonts.googleapis.com/css?family=Roboto|Share+Tech+Mono|Material+Icons);
html {margin: 0; height: 100%; width: 100%; font-family: Roboto; font-size: 1vh; color: rgba({$this->color_zgrey},1);}
body {background-color: rgba({$this->color_mgrey},1);border:0px;margin:0px;overflow: auto; position: relative; }

#pageContentHolder {  width: 60vw; height: 100vh; position: fixed; top: 0vh; left: 20vw; background: rgba({$this->color_white},1); -webkit-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); -moz-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45);  overflow: hidden; border-left: 1px solid rgba({$this->color_zgrey},1);}
.menuIconSqr { width: 4vw; text-align: center; padding: 1vh 0 0 0; }
.menuIconSqr .material-icons {font-size: 2.5vh; }
.menuIconSqr .material-icons:hover { color: rgba({$this->color_neongreen},1); cursor: pointer; }

#pageContentHolder #darkheadr {background: rgba({$this->color_darkvariant},1); color: rgba({$this->color_white},1); font-size: 1.2vh; padding: 1.1vh 0 1.1vh 0; text-align: right; z-index: 11; width: 60vw; position: absolute;  }
#pageContentHolder #liteheadr {background: rgba({$this->color_primary},1); color: rgba({$this->color_white},1);-webkit-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45);-moz-box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); box-shadow: 6px 9px 25px -7px rgba(0,0,0,0.45); z-index: 10; position: absolute; top: 3vh; width: 60vw; }

#pageContentHolder #menuHolder {position: absolute; top: -5vh; background: #fff; z-index: 9; width: 60vw; border-bottom: 1px solid rgba({$this->color_zgrey},1); transition: top 1s; }

#pageContentHolder #liteheadr #applicationTitle {font-size: 2.3vh; padding: 3vh .8vw 2.2vh 0; }
#headerPic { background: url(data:image/gif;base64,{$testtube}) no-repeat center center fixed; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover; height: 45vh; position: relative; }
#headerPic #byLineHolder {position: absolute; left: 0px; bottom: 6vh;width: 100%;background: rgba({$this->color_white},.7) }
#headerPic #byLineHolder #superline {font-size: 3.8vh; font-weight: bold; width: 98%; text-align: right; padding: 2vh 0 0 0; }
#headerPic #byLineHolder #subline {width: 98%; text-align: right; padding: 0 0 2vh 0; font-size: 2vh; }
#maintext {-webkit-columns: 2 5vw; -moz-columns: 2 5vw; columns: 2 5vw; padding: 1vh 1vw 1vh 1vw; text-align: justify; font-size: 1.8vh; line-height: 2.1em;-webkit-column-gap: 2vw; -moz-column-gap: 2vw; column-gap: 2vw; -webkit-column-rule: 1px dotted rgba({$this->color_mgrey},1); -moz-column-rule: 1px dotted rgba({$this->color_mgrey},1); column-rule: 1px dotted rgba({$this->color_mgrey},1);}
.firstcharacter {  color: rgba({$this->color_nicered},1);float: left;font-family: Roboto;font-size: 9vh;line-height: 7vh; padding-top: 4px;padding-right: 8px;padding-left: 3px; }
#pennICO {text-align: right;padding: 1vh 2vw 0 0; }
#upennicon {height: 6vh; }

#menuOptionsHolder td { width: 3vw; transition: background .5s, color .5s;  }
#menuOptionsHolder td:hover {cursor: pointer;background: rgba({$this->color_lblue},1);color: rgba({$this->color_neongreen},1);  }

#crightLine {width: 60vw; text-align: center; position: absolute; bottom: 0; padding: 0 0 .8vh 0; }

RTNTHIS;
} else {
    $rtnThis = <<<RTNTHIS
body {background-color:#000054;color:#FFF;border:0px;margin:0px;}
.wrapper {position:absolute;top:15%;left:0px;right:0px;width:100%;height:auto;}
.message {float:left;width: 90%;height:auto;font-size: 3vh;text-align:justify;font-family:Arial,Helvetica,sans-serif;margin-top:25px;margin-left: 3vw;margin-right: 3vw;line-height: 1.8em;}
RTNTHIS;
}
return $rtnThis;
}

}

class pgScriptr {

function contactpfrp($mobileIndicator) {
$topTree = pfcurl;
if ($mobileIndicator !== "m") {
    //FULL SITE
    $rtnThis = <<<RTNTHIS

var byId = function ( id ) {return document.getElementById( id ) ; };

function makeMenuAppear() {
  if (byId('menuHolder').style.top.trim() === "" || parseInt(byId('menuHolder').style.top) < 10) {
     byId('menuHolder').style.top = "11vh";
  } else {
    byId('menuHolder').style.top = "-5vh";
  }
}

function navigateSite(whichpage) {
if (whichpage.trim() === "") {
  whichpage = "publichome";
}
window.location.href = '{$topTree}/?page='+whichpage;
}

function goSecure() {
    window.location.href = "https://hosting.med.upenn.edu/pfc/secure/";
}

RTNTHIS;
} else {
   //MOBILE SITE
   $rtnThis = <<<RTNTHIS

RTNTHIS;
}
return $rtnThis;
}

function pfrpprocess($mobileIndicator) {
$topTree = pfcurl;
if ($mobileIndicator !== "m") {
    //FULL SITE
    $rtnThis = <<<RTNTHIS

var byId = function ( id ) {return document.getElementById( id ) ; };

function makeMenuAppear() {
  if (byId('menuHolder').style.top.trim() === "" || parseInt(byId('menuHolder').style.top) < 10) {
     byId('menuHolder').style.top = "11vh";
  } else {
    byId('menuHolder').style.top = "-5vh";
  }
}

function navigateSite(whichpage) {
if (whichpage.trim() === "") {
  whichpage = "publichome";
}
window.location.href = '{$topTree}/?page='+whichpage;
}

function goSecure() {
    window.location.href = "https://hosting.med.upenn.edu/pfc/secure/";
}

RTNTHIS;
} else {
   //MOBILE SITE
   $rtnThis = <<<RTNTHIS

RTNTHIS;
}
return $rtnThis;
}

function publichome($mobileIndicator) {
  $topTree = pfcurl;
if ($mobileIndicator !== "m") {
    //FULL SITE
    $rtnThis = <<<RTNTHIS

var byId = function ( id ) {return document.getElementById( id ) ; };
//{$topTree}


function makeMenuAppear() {
  if (byId('menuHolder').style.top.trim() === "" || parseInt(byId('menuHolder').style.top) < 10) {
     byId('menuHolder').style.top = "11vh";
  } else {
    byId('menuHolder').style.top = "-5vh";
  }
}

//TOPTREE: {$topTree}

function navigateSite(whichpage) {
if (whichpage.trim() === "") {
  whichpage = "publichome";
}
window.location.href = '{$topTree}/?page='+whichpage;
}

function goSecure() {
    window.location.href = "https://hosting.med.upenn.edu/pfc/secure/";
}

RTNTHIS;
} else {
   //MOBILE SITE
   $rtnThis = <<<RTNTHIS

RTNTHIS;
}
return $rtnThis;
}

}

class pgBody {

function contactpfrp($mobileIndicator) {

 if ($mobileIndicator !== "m") {
     //FULL SITE
$rtnThis = <<<RTNTHIS
<div id=pageContentHolder>
<div id=menuHolder><table border=0 id=menuOptionsHolder><tr>
<td onclick="navigateSite('');"> <table><tr><td><i class="material-icons">home</i></td><td>Home</td></tr></table></td>
<td onclick="navigateSite('pfrp-process');"> <table><tr><td><i class="material-icons">dashboard</i></td><td>PFRP Process</td></tr></table></td>
<td onclick="navigateSite('contact-pfrp');"> <table><tr><td><i class="material-icons">chat_bubble_outline</i></td><td>Contact PFRP</td></tr></table></td>
<td onclick="goSecure();"> <table><tr><td><i class="material-icons">account_box</i></td><td>Login</td></tr></table></td>
</tr></table>
</div>
<div id=darkheadr>Hospital of the University of Pennsylvania&nbsp;&nbsp;</div>
<div id=liteheadr><table border=0 width=100% cellpadding=0 cellspacing=0>
      <tr>
          <td class=menuIconSqr><i class="material-icons" onclick="makeMenuAppear();">menu</i></td>
          <td id=applicationTitle>Pathology Feasibility Review Panel</td>
          <td class=menuIconSqr style=""><!-- <i class="material-icons">search</i> //--> </td></tr></table>
</div>

<!-- CONTENT HERE //-->
<div id=mainFormHolder>
<h1>Contact PFRP</h1>If you have questions or comments, please call Diane McGarvey (215 662-4570) or Fred Valdivieso (215-614-4744).
</div>
<!-- CONTENT ENDS //-->

</div>
RTNTHIS;
} else {
$rtnThis = <<<RTNTHIS
<h1>Contact PFRP</h1>If you have questions or comments, please call Diane McGarvey (215 662-4570) or Fred Valdivieso (215-614-4744).
RTNTHIS;
}
return $rtnThis;
}


function contactpfrp_formLAYOUT($mobileIndicator) {

$cptchaRS = json_decode(callrestapi("GET","https://data.chtneast.org/generatecaptcha", serverIdent, apikey), true);
$cptchaHd = json_decode($cptchaRS['datareturn'], true);
$appPath = genAppFiles;
$icon = $appPath . "/publicobj/graphics/psom_logo_blue.png";
$cptcha = $appPath . "/tmp/{$cptchaHd['DATA']}";
$cap = base64file($cptcha,"captchaimg","image", true);
$capKey = "<input type=hidden id=fldCaptchaKey value={$cptchaHd['MESSAGE']}>";
$icon = base64file($icon, "upennicon", "image", true);

$captTbl = "<table border=0>"
                 . "<tr>"
                 . "<td width=30px>{$capKey} {$cap}  </td><td width=30px><table class=standardButton><tr><td><center>Get Different Code</td></tr></table></td><td></td>"
                 . "</tr>"
                 . "<tr><td colspan=3 class=fieldLabel>Type the above code in the space below</td></tr>"
                 . "<tr>"
                 . "<td colspan=3><input type=text id=fldCaptchaCode class=standardField placeholder=\"Type Captcha Code here\"></td></tr>"
                 . "</table>";


 if ($mobileIndicator !== "m") {
     //FULL SITE
    $rtnThis = <<<RTNTHIS
<div id=pageContentHolder>
<div id=menuHolder><table border=0 id=menuOptionsHolder><tr>
<td onclick="navigateSite('');"> <table><tr><td><i class="material-icons">home</i></td><td>Home</td></tr></table></td>
<td onclick="navigateSite('pfrp-process');"> <table><tr><td><i class="material-icons">dashboard</i></td><td>PFRP Process</td></tr></table></td>
<td onclick="navigateSite('contact-pfrp');"> <table><tr><td><i class="material-icons">chat_bubble_outline</i></td><td>Contact PFRP</td></tr></table></td>
<td onclick="goSecure();"> <table><tr><td><i class="material-icons">account_box</i></td><td>Login</td></tr></table></td>
</tr></table>
</div>
<div id=darkheadr>Hospital of the University of Pennsylvania&nbsp;&nbsp;</div>
<div id=liteheadr><table border=0 width=100% cellpadding=0 cellspacing=0>
      <tr>
          <td class=menuIconSqr><i class="material-icons" onclick="makeMenuAppear();">menu</i></td>
          <td id=applicationTitle>Pathology Feasibility Review Panel</td>
          <td class=menuIconSqr style=""><!-- <i class="material-icons">search</i> //--> </td></tr></table>
</div>

<!-- CONTENT HERE //-->
<div id=mainFormHolder>
<table border=0 width=100% id=contactFormTbl>
    <tr>
        <td id=preamble>Do you have any questions or concerns? <p>Please enter your information below and we will respond as quickly as possible.</td></tr>
   <tr><td class=fieldLabel>Name (First & Last)</td></tr>
   <tr><td class=fieldHolder><input type=text id=conName class=standardField placeholder="Name (First & Last)"></td></tr>
   <tr><td class=fieldLabel>Your Email Address</td></tr>
   <tr><td class=fieldHolder><input type=text id=conEmail class=standardField placeholder="Email"></td></tr>
   <tr><td class=fieldLabel>Best Phone Number to reach you</td></tr>
   <tr><td class=fieldHolder><input type=text id=conPhone class=standardField placeholder="Phone Number"></td></tr>
   <tr><td class=fieldLabel>Your question or comments</td></tr>
   <tr><td class=fieldHolder><textarea id=conComments class=standardField style="height: 80px;" placeholder="Comments"></textarea></td></tr>
<tr><td style="padding: 0 0 0 2vw;">{$captTbl}</td></tr>
<tr><td align=right><table class=standardButton onclick="sendContactEmail();"><tr><td>Send Comments</td></tr></table></td></tr>
</table>
</div>
<!-- CONTENT ENDS //-->

</div>
RTNTHIS;

 } else {
     //MOBILE SITE


 }
    return $rtnThis;
}


function pfrpprocess($mobileIndicator) {
$appPath = genAppFiles;
$icon = $appPath . "/publicobj/graphics/psom_logo_blue.png";
$icon = base64file($icon, "upennicon", "image", true);
$pgraph = $appPath . "/publicobj/graphics/pfcProcessDiagram.png";
$processDiagram = base64file($pgraph, "pfrpprocessdiagram", "image", true);

 if ($mobileIndicator !== "m") {
     //FULL SITE
    $rtnThis = <<<RTNTHIS

<div id=pageContentHolder>
<div id=menuHolder><table border=0 id=menuOptionsHolder><tr>
<td onclick="navigateSite('');"> <table><tr><td><i class="material-icons">home</i></td><td>Home</td></tr></table></td>
<td onclick="navigateSite('pfrp-process');"> <table><tr><td><i class="material-icons">dashboard</i></td><td>PFRP Process</td></tr></table></td>
<td onclick="navigateSite('contact-pfrp');"> <table><tr><td><i class="material-icons">chat_bubble_outline</i></td><td>Contact PFRP</td></tr></table></td>
<td onclick="goSecure();"> <table><tr><td><i class="material-icons">account_box</i></td><td>Login</td></tr></table></td>
</tr></table>
</div>
<div id=darkheadr>Hospital of the University of Pennsylvania&nbsp;&nbsp;</div>
<div id=liteheadr><table border=0 width=100% cellpadding=0 cellspacing=0>
      <tr>
          <td class=menuIconSqr><i class="material-icons" onclick="makeMenuAppear();">menu</i></td>
          <td id=applicationTitle>Pathology Feasibility Review Panel</td>
          <td class=menuIconSqr style=""><!-- <i class="material-icons">search</i> //--> </td></tr></table>
</div>

<!-- CONTENT HERE //-->

   <div id=mainContent>
<span id=title>The PFRP Process and How to Apply</span><p>
Study personnel should submit an application to the PFRP.  The PFRP process will take 2-3 business days and the PI will be notified promptly of the decision.<p>If an exception is granted, the PI will receive a Specimen Retrieval Form with a PFRP approval code. The Study team will be granted access to the ORs and authorized staff must present this Specimen Retrieval Form to the OR staff to obtain the research tissue. <p>If an exception is <b>not</b> granted, the study team will be directed to pick up the research material in the Pathology gross room after the specimens have been accessioned for diagnostic purposes.  Please contact Dee McGarvey at the CHTN (215-662-4570) or Fred Valdivieso (215-614-4744) at TTAB for assistance with these procedures.<p><p>The following diagram shows the process flow for this new policy: <p><center>{$processDiagram}

   </div>

<!-- CONTENT ENDS //-->

</div>
RTNTHIS;

 } else {
     //MOBILE SITE


 }
    return $rtnThis;
}

function publichome($mobileIndicator) {

//$appPath = genAppFiles;
//$icon = $appPath . "/publicobj/graphics/psom_logo_blue.png";
//$icon = base64file($icon, "upennicon", "image", true);

 if ($mobileIndicator !== "m") {
     //FULL SITE
    $yr = date('Y');
    $rtnThis = <<<RTNTHIS

<div id=pageContentHolder>

<div id=darkheadr>Hospital of the University of Pennsylvania&nbsp;&nbsp;</div>
<div id=liteheadr><table border=0 width=100% cellpadding=0 cellspacing=0>
      <tr>
          <td class=menuIconSqr><i class="material-icons" onclick="makeMenuAppear();">menu</i></td>
          <td id=applicationTitle>Pathology Feasibility Review Panel</td>
          <td class=menuIconSqr style=""><!-- <i class="material-icons">search</i> //--> </td></tr></table>
</div>

<div id=headerPic>
  <div id=byLineHolder>
  <div id=superline>Pathology Feasibility Review Panel</div>
  <div id=subline>Helping you collect Research Samples</div>
  </div>
</div>

<div id=maintext><span class="firstcharacter">T</span>he mission of the <b>Pathology Feasibility Review Panel</b> (PFRP) is to protect the integrity of patient diagnostic material for pathological analysis. This panel will review research protocols to ensure that only pre-authorized studies  will be granted access to remove research samples from the OR areas. <p>Existing HUP policy dictates that all tissue specimens from the ORs first go to Surgical Pathology for accessioning, processing prior to distribution of tissue to researchers for research studies.  The PFRP in parallel to an IRB submission, will determine whether an exception to this policy should be granted.  The PFRP process will take 2-3 business days and the PI will be notified promptly of the decision. <p>Your cooperation with this new process is essential to protecting our patients and ensuring that the integrity of diagnostic specimens are not compromised, and also to facilitate appropriate tissue acquisition for important research studies by Penn investigators.</div>

<div id=pennICO>{$icon}</div>
<div id=menuHolder><table border=0 id=menuOptionsHolder><tr>
<td onclick="navigateSite('');"> <table><tr><td><i class="material-icons">home</i></td><td>Home</td></tr></table></td>
<td onclick="navigateSite('pfrp-process');"> <table><tr><td><i class="material-icons">dashboard</i></td><td>PFRP Process</td></tr></table></td>
<td onclick="navigateSite('contact-pfrp');"> <table><tr><td><i class="material-icons">chat_bubble_outline</i></td><td>Contact PFRP</td></tr></table></td>
<td onclick="goSecure();"> <table><tr><td><i class="material-icons">account_box</i></td><td>Login</td></tr></table></td>
</tr></table>

</div>

<div id=crightLine>&copy; 2017-{$yr} Trustees of the University of Pennsylvania</div>

</div>

RTNTHIS;
} else {
    //MOBILE DISPLAY
    $rtnThis = <<<RTNTHIS
<span id=headr>Pathology Feasibility Review Panel</span><p><span id=subline>Helping you collect Research Samples</span><br>The mission of the <b>Pathology Feasibility Review Panel</b> (PFRP) is to protect the integrity of patient diagnostic material for pathological analysis. This panel will review research protocols to ensure that only pre-authorized studies  will be granted access to remove research samples from the OR areas. <p>Existing HUP policy dictates that all tissue specimens from the ORs first go to Surgical Pathology for accessioning, processing prior to distribution of tissue to researchers for research studies.  The PFRP in parallel to an IRB submission, will determine whether an exception to this policy should be granted.  The PFRP process will take 2-3 business days and the PI will be notified promptly of the decision. <p>Your cooperation with this new process is essential to protecting our patients and ensuring that the integrity of diagnostic specimens are not compromised, and also to facilitate appropriate tissue acquisition for important research studies by Penn investigators.
RTNTHIS;
}
    return $rtnThis;
}

}



///PARKING PAGE ELEMENTS
function park_body($mobile) {
    $topTree = pfcurl;
    $rtnThis = <<<RTNTHIS
<div class="wrapper">
            <div class="message"><center>University of Pennsylvania Pathology Feasibility Committee (PFC)</center><p>You have reached the servers for the University of Pennsylvania Pathology Feasibility Committee.  The webpage which you request cannot be found or is being upgraded to serve you better.   If you need assistance, please call Diane McGarvey at (215) 662-4570 or email us at dfitzsim@pennmedicine.upenn.edu
            <p>
            <!-- To go back to the main page, <a href="{$topTree}">Click Here</a> //-->
            </div>
</div>
RTNTHIS;
    return $rtnThis;
}

function park_style($mobile) {

if ($mobile !== "m") {
    $rtnThis = <<<RTNTHIS

body {
    background-color:#3686be;
    color:#FFF;
    border:0px;
    margin:0px;
    overflow:hidden;
}
.wrapper {
    position:absolute;
    top:15%;
    left:0px;
    right:0px;
    width:100%;
    height:auto;
}
.message {
    float:left;
    width:100%;
    height:auto;
    font-size:36px;
    text-align:center;
    font-family:Arial,Helvetica,sans-serif;
    margin-top:25px;
}

RTNTHIS;
} else {
    $rtnThis = <<<RTNTHIS

body {
    background-color:#000054;
    color:#FFF;
    border:0px;
    margin:0px;

}
.wrapper {
    position:absolute;
    top:15%;
    left:0px;
    right:0px;
    width:100%;
    height:auto;
}
.message {
    float:left;
    width: 90%;
    height:auto;
    font-size: 3vh;
    text-align:justify;
    font-family:Arial,Helvetica,sans-serif;
    margin-top:25px;
    margin-left: 3vw;
    margin-right: 3vw;
    line-height: 1.8em;
}

RTNTHIS;

}

    return $rtnThis;
}

function park_header() {
    $rtnThis = <<<RTNTHIS

<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Pathology Feasibility Review (PAGE NOT FOUND)</title>

RTNTHIS;
    return $rtnThis;
}

function publichome_header() {
    $rtnThis = <<<RTNTHIS

<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<title>Pathology Feasibility Review Panel</title>

RTNTHIS;
    return $rtnThis;
}

function publichome_style($mobile) {

if ($mobile !== "m") {
    $rtnThis = <<<RTNTHIS
body {background-color:#3686be;color:#FFF;border:0px;margin:0px;overflow:hidden;}
.wrapper {position:absolute;top:15%;left:0px;right:0px;width:100%;height:auto;}
.message {float:left;width:100%;height:auto;font-size:36px;text-align:center;font-family:Arial,Helvetica,sans-serif;margin-top:25px;}
RTNTHIS;
} else {
    $rtnThis = <<<RTNTHIS
body {background-color:#000054;color:#FFF;border:0px;margin:0px;}
.wrapper {position:absolute;top:15%;left:0px;right:0px;width:100%;height:auto;}
.message {float:left;width: 90%;height:auto;font-size: 3vh;text-align:justify;font-family:Arial,Helvetica,sans-serif;margin-top:25px;margin-left: 3vw;margin-right: 3vw;line-height: 1.8em;}

RTNTHIS;
}
  return $rtnThis;
}

function publichome_body($mobile) {
if ($mobile !== "m") {
    //FULL SITE
    $rtnThis = <<<RTNTHIS

<div class="wrapper">
            <div class="message"><center>University of Pennsylvania Pathology Feasibility Committee (PFC)</center><p>You have reached the servers for the University of Pennsylvania Pathology Feasibility Committee.  We are working to upgrade our servers and application systems.  We will be back online by June 20th, 2018.  If you need immediate assistance, please call Diane McGarvey at (215) 662-4570 or email us at dfitzsim@pennmedicine.upenn.edu
            </div>
</div>

RTNTHIS;
} else {
    //MOBILE DISPLAY
    $rtnThis = <<<RTNTHIS

<div class="wrapper">
            <div class="message"><center>University of Pennsylvania Pathology Feasibility Committee (PFC)</center><p>You have reached the servers for the University of Pennsylvania Pathology Feasibility Committee.  We are working to upgrade our servers and application systems.  We will be back online by June 20th, 2018.  If you need immediate assistance, please call Diane McGarvey at (215) 662-4570 or email us at dfitzsim@pennmedicine.upenn.ed
            </div>
</div>

RTNTHIS;

}
    return $rtnThis;
}
