<?php 

class dataposters { 

  public $responseCode = 400;
  public $message = "";
  public $rtnData = "";
  public $pagePreamble = "";
  public $pageHead = "";
  public $pageStyle = "";
  public $pageScriptr = "";
  public $pageBody = "";

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
          
          $funcName = "corecontrol";
          $dataReturned = $dp->$funcName($args[0], $args[1]); 
          $this->responseCode = $dataReturned['statusCode'];
          $this->message = $dataReturned['message']; 

          $pd = json_decode($args[1], true);
          if ($pd['functiontype'] === 'PAGE') { 
            $this->pagePreamble = $dataReturned['preamble'];
            $this->pageHead = $dataReturned['headr'];
            $this->pageStyle = $dataReturned['style'];
            $this->pageScriptr = $dataReturned['scriptr'];            
            $this->pageBody = $dataReturned['body'];            
          }
          $this->rtnData = $dataReturned['data'];
          
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
    //{"functiontype":"PAGE","functionname":"publichome","mobileindicator":"w","systemid":"YkR2cGQwc2U4VjBFdk00OG55K0hnQT09","userid":"cVA5bFRPb0o3QUp6S0tJWnpEbUx1UT09","requested":"\/pfc\/secure\/","submodule":"secureapprequest" }
    $pd = json_decode($passedData, true); 

    switch ($pd['functiontype']) { 
      case 'PAGE': 
        $pgeclass = new pagerequests();
        $pgenme = $pd['functionname'];
        $pge = $pgeclass->$pgenme($pd['mobileindicator']);
        $rows['statusCode'] = 200; 
        $rows['message'] = "RETURNED PAGE: {$pgenme}";
        $rows['data'] = $pge; 
        $rows['preamble'] = $pge['preamble'];
        $rows['headr'] = $pge['head'];
        $rows['style'] = $pge['style'];
        $rows['scriptr'] = $pge['javascriptr'];
        $rows['body'] = $pge['body'];        
      break;
      case 'APPPAGE':
        $sysid = pfccryptservice($pd['systemid'],'d',false); 
        if (  $sysid === "PFC-USER" ) {  
  
          $uid = pfccryptservice($pd['userid'],'d',false); 
          $pfcmember = pfcmember($uid); //{\"responseCode\":404,\"memid\":0,\"firstname\":\"\",\"lastname\":\"\",\"pfrptitle\":\"\",\"email\":\"\",\"pfcpennkey\":\"zacheryv\"}

          $ppgenme = $pd['functionname'];
          $pgenme = ( $ppgenme === "" ) ? ( (int)$pfcmember['memid'] === 0 ) ? 'genuserapphome' : 'memberapphome' : $ppgenme;

          if ( method_exists( 'securepagerequests', $pgenme)) { 
              $pgeclass = new securepagerequests();
              $pge = $pgeclass->$pgenme($uid, $pfcmember['memid'], $pd['mobileindicator'], $pd['requested'], $pfcmember );
              $rows['message'] = ""; 
              $rows['data'] = json_encode($pge); 
              $rows['preamble'] = $pge['preamble'];
              $rows['headr'] = $pge['head'];
              $rows['style'] = $pge['style'];
              $rows['scriptr'] = $pge['javascriptr'];
              $rows['body'] = $pge['body']; 
              $rows['statusCode'] = 200;
          } else { 
            //PAGE BUILDER DOESN'T EXIST - DISPLAY PARK PAGE
              $rows['statusCode'] = 350;
              $rows['message'] = $pgenme; 
              $rows['data'] = $pfcmember['responseCode']; 
              $rows['preamble'] = $pge['preamble'];
              $rows['headr'] = $pge['head'];
              $rows['style'] = $pge['style'];
              $rows['scriptr'] = $pge['javascriptr'];
              $rows['body'] = $pge['body']; 
          }




        } else { 
          //NOT COMING FROM SERVER REQUEST - DISALLOW!!!!! 
        }      

      break;
    }   
    return $rows;
}


}


class securepagerequests {

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


function projectlisting( $usr, $usrmemid, $mobileind, $rqst, $memberinfo ) { 
$tt = pfcurl;
$securett = pfcsecureurl;
$preamb = "<!DOCTYPE html>\n<html>";
$standhead = self::standardHeader();
$reviewhead = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}{$rqst} //-->
{$standhead}
<title>PFRP Project Listing</title>
HDR;

$ss = self::globalStyle();
$style= <<<STYLESHT
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
$jvcontent = <<<JAVASCRIPTR
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

$cHeader = self::topAndMenuBarMember($usr, "{$memberinfo['firstname']} {$memberinfo['lastname']}", "{$memberinfo['email']}" );
$pgContent = $cHeader;


parse_str(str_replace("?","", str_replace("-","",strtolower($rqst))), $rqstDetermine);    

if ($rqstDetermine['projid']) {
  if (is_numeric($rqstDetermine['projid'])) { 
    ///GET PROJECT
    $passData = json_encode(array("pennkey" => pfccryptservice($usr),"projectid" => $rqstDetermine['projid']));
    $pHold = json_decode(callpfcrestapi("POST","https://pfcdata.chtneast.org/pfcapplication/getprojectbymember",$passData), true);
//
if ((int)$pHold['responseCode'] === 200) { 
//{"responseCode":200,"message":"{\"MESSAGE\":\"\",\"ITEMS\":0,\"DATA\":{"completeind\":0,,,,,,,,\"statuses\":[{\"datastatus\":\"SUBMITTED\",\"statusmodifier\":\"\",\"statusdate\":\"06\\\/23\\\/2018 12:35\",\"lettercomments\":\"\"},{\"datastatus\":\"IN REVIEW\",\"statusmodifier\":\"\",\"statusdate\":\"06\\\/25\\\/2018 09:40\",\"lettercomments\":\"\"},{\"datastatus\":\"IN REVIEW\",\"statusmodifier\":\"\",\"statusdate\":\"06\\\/25\\\/2018 09:41\",\"lettercomments\":\"\"}]}}","datareturn":""} 
    $head = $pHold['datareturn']; 
    foreach($head['contacts'] as $conval) { 
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
    foreach($head['questionanswers'] as $ans) { 
      $innerAns .= "<tr><td valign=top style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$qCount}</td><td valign=top style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$ans['question']}</td><td valign=top style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$ans['answer']}</td></tr>";
      $qCount += 1;    
    }
    $innerAns .= "</table>";

    $innerDocs = "<table border=0 style=\"width: 28vw;\">";
    $qCount = 1;
    foreach($head['documents'] as $doc) { 
      $innerDocs .= "<tr onclick=\"grabdocumentpdf('{$doc['directorydocumentname']}');\"><td style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$qCount}</td><td style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">{$doc['typeofdocument']}</td><td style=\"border-bottom: 1px solid rgba({$this->color_zgrey},1);\">({$doc['uploadedon']}-{$doc['uploadedby']})</td></tr>";
      $qCount += 1;    
    }
    if (trim($head['projectpdf']) !== "") { 
      $innerDocs .= "<tr onclick=\"grabdocumentpdf('{$head['DATA']['projectpdf']}');\"><td>{$qCount}</td><td>Project PDF</td><td>&nbsp;</td></tr>";
    }
    $innerDocs .= "</table>";

    $decisionSelect = "<select id=pfrpDecision onchange=\"referReview(this.value);\"><option value=\"NA\">-</option>";
    $actionHold = json_decode(callpfcrestapi("POST","https://pfcdata.chtneast.org/pfcapplication/pfrpactions",""), true);
    
    if ($actionHold['responseCode'] === 200) { 
      $actionVals = $actionHold['datareturn'];
      foreach($actionVals as $actionv) { 
        $decisionSelect .= "<option value=\"{$actionv['codevalue']}\">{$actionv['menuvalue']}</option>";
      } 
    } else {
      $decisionSelect .= "<option value=\"BADVALUE\">ERROR NO MENU FOUND IN SERVICE</option>";
    }
    $decisionSelect .= "</select>"; 

    $emailSelect = "<select id=pfrpReferEmailList>";
    $actionHold = json_decode(callpfcrestapi("POST","https://pfcdata.chtneast.org/pfcapplication/pfrpmemberemaillisting",""), true);
    if ($actionHold['responseCode'] === 200) { 
      $actionVals = $actionHold['datareturn'];
      foreach($actionVals as $actionv) { 
        $emailSelect .= "<option value=\"{$actionv['pfcmemberid']}\">{$actionv['membername']}</option>";
      } 
    } else {
      $emailSelect .= "<option value=\"BADVALUE\">ERROR NO MENU FOUND IN SERVICE</option>";
    }
    $emailSelect .= "</select>"; 

    $copySelect = "<select id=pfrpCopyMe><option value=\"NO\">NO</option><option value=\"YES\">YES</option></select>";

//<tr><td><table><tr><td class=datalabel>Copy Me on Decision Email</td></tr><tr><td>{$copySelect}</td></tr></table></td><td align=right><table id=pfrpSaveButton onclick="saveReview();"><tr><td>Save</td></tr></table></td></tr>

    $completeind = $head['completeind'];
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
    <tr><td colspan=2 id=projDspProjectId>Project {$head['projectid']} <input type=hidden id=fldprojectidreference value={$head['projectid']}></td></tr>
    <tr><td colspan=2 id=projDspProjectTitle>{$head['projecttitle']}</td></tr>

    <tr><td colspan=2 class=projDspHeader>Details</td></tr>
    <tr><td class=datalabel>Submission Date:&nbsp;</td><td class=dataline>{$head['submissiondate']}&nbsp;</td></tr> 
    <tr><td class=datalabel>IRB Number:&nbsp;</td><td class=dataline>{$head['irbnbr']}&nbsp;</td></tr>
    <tr><td class=datalabel>IRB Expiration:&nbsp;</td><td class=dataline>{$head['irbexpiration']}&nbsp;</td></tr>
    <tr><td class=datalabel>PFRP Approval Date:&nbsp;</td><td class=dataline>{$head['approvalyear']}&nbsp;</td></tr>
    <tr><td class=datalabel>PFRP Approval Number:&nbsp;</td><td class=dataline>{$head['pfcapprovalnumber']}&nbsp;</td></tr>
    <tr><td class=datalabel>PFRP Expiration Date:&nbsp;</td><td class=dataline>{$head['pfcapprovalexpiration']}&nbsp;</td></tr>
    <tr><td colspan=2>Project Comments</td></tr>
    <tr><td colspan=2>{$head['projectcomments']}</td></tr>

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
$pgContent .= <<<RTNTHIS
{$dspThis}
RTNTHIS;
  } else { 
  $pgContent = <<<RTNTHIS
NOT A VALID PROJECT NUMBER
RTNTHIS;
  }
} else {

  //BUILD STATUS PULL     
$apk = serverPW;
$passData = json_encode(array("pennkey" => pfccryptservice($usr)));    
$statWS = json_decode(callpfcrestapi("POST","https://pfcdata.chtneast.org/pfcapplication/livestatuslist",$passData),true);
//"responseCode":200,"message":"livestatuslist","itemsfound":5,"datareturn":[{"datastatus":"SUBMITTED","statusmodifier":"SUBMITTED","projstatusdsp":"SUBMITTED","furtherActionInd":1,"completionActionInd":0}
if ($statWS['responseCode'] === 200) { 
    //$stdta = json_decode($statWS['message'], true);   
    if ((int)$statWS['itemsfound'] < 1) {
      $statusMenu = "NO MENU ITEMS FOUND"; 
    } else {
      $statusMenu = "<table border=0 id=statusMenuTbl>";
      foreach ($statWS['datareturn'] as $vl) {
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
  $crd['pennkey'] = pfccryptservice($usr);
  $dta['datapayload'] = json_encode($crd); 
  $passData = json_encode($dta);  
  $statList = json_decode(callpfcrestapi("POST","https://pfcdata.chtneast.org/pfcapplication/projectsbystatus",$passData), true);

//{"responseCode":200,"message":"projectsbystatus","itemsfound":1,"datareturn":[{"projectStatus":"IN REVIEW","dspprojectid":"000325","userId":31,"submittingpennkey":"zacheryv","projectpdffilename":"projectApplication325.pdf","projectTitle":"Test Test","irbNbr":"1111-111-1111","irbExpiration":"2019-12-15","laststatusby":"linus","statusDate":"2018-12-05 11:16:40","pfcapprovalnbr":"","piname":"LiVolsi, Virginia","piphone":"215-555-1212","piemail":"zacheryv@mail.med.upenn.edu"}]}
//  $statDta = json_decode($statList['message'],true);
//  $statTblDta = json_decode($statDta['DATA'], true);
  $projectCount = $statList['itemsfound'];
  $addS = "";
  if ((int)$projectCount > 1) { 
    $addS = "s";
  }

  $statusTbl = "<center><h1>" . strtoupper(urldecode($rqstDetermine['status'])) . "</h1></center>";
  $statusTbl .= "<table border=0 id=projectLister><thead><tr><td colspan=12 id=pcounter>{$projectCount} project{$addS} found</td></tr>";
  $statusTbl .= "<tr><th style=\"text-align: center;\">PDF</th><th>Project #</th><th>PI Name</th><th>PI Phone</th><th>PI Email</th><th>Submitter</th><th>Project Title</th><th>IRB #</th><th>IRB Expire</th><th>Last Statused</th><th>Last Date</th><th>PFC Approval</th></tr><thead><tbody>";
  foreach($statList['datareturn'] as $sv => $vl) { 
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

$pgContent .= <<<RTNTHIS
<table border=0 id=statDspHoldTbl><tr><td valign=top id=statDspSidePanel>{$statusMenu}</td><td valign=top><div id=projectDisplayDiv>{$statusTbl}</div></td></tr></table>
RTNTHIS;

}

$cFooter = self::standardFootMember();
$pgContent .= $cFooter;

return array(
         "preamble" => htmlspecialchars($preamb)
       , "head" => htmlspecialchars($reviewhead)
       , "style" => htmlspecialchars($style)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       );
}




function memberapphome( $usr, $usrmemid, $mobileind, $rqst, $memberinfo ) { 
$securett = pfcsecureurl;

$preamb = "<!DOCTYPE html>\n<html>";

$standHead = self::standardHeader();

$head = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}{$rqst} //-->
{$standHead}
<title>PFRP Data Application</title>
HDR;

$ss = self::globalStyle();
$style = <<<STYLESHT
<style>
{$ss}
body {margin-top: 9vh; } 
</style>
STYLESHT;

$jvscript = self::globaljavascriptr($usr); 
$jvcontent = <<<JAVASCRIPTR
<script lang=javascript>
{$jvscript}
</script>
JAVASCRIPTR;

$cHeader = self::topAndMenuBarMember($usr, "{$memberinfo['firstname']} {$memberinfo['lastname']}", "{$memberinfo['email']}" );
$pgContent = $cHeader;

$pageContent = <<<RTNTHIS
        Pathology Feasibility Review Panel Application Review<p>
        The mission of the Pathology Feasibility Review Panel (PFRP) is to protect the integrity of patient diagnostic material for pathological analysis. This panel will review research protocols to ensure that only pre-authorized indiduals will be granted access to remove research samples from the OR areas.  <p>Thank you for logging in to review projects.  To begin the review process, click the "Project List" button on the menu bar above.

RTNTHIS;
$pgContent .= $pageContent;

$cFooter = self::standardFootMember();
$pgContent .= $cFooter;


return array(
         "preamble" => htmlspecialchars($preamb)
       , "head" => htmlspecialchars($head)
       , "style" => htmlspecialchars($style)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       );
}

function standardFootMember() { 
    $rtnThis = <<<RTNTHIS
<div id=standardMemberFooter></div>
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

function globaljavascriptr($usr) { 
$tt = "https://hosting.med.upenn.edu/pfc/secure";
$dtakey = generatePFCSessionKey($usr);
$usrEncrypt = pfccryptservice($usr,'e',false);

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

function genuserapphome( $usr, $usrmemid, $mobileind, $rqststr, $memberinfo) { 
//  $tt = pfcurl;
  $securett = pfcsecureurl;
  $rqst = $rqststr; 
  $preamb = "<!DOCTYPE html>\n<html>";
  $head   = <<<HDR
<!-- <META http-equiv="refresh" content="0;URL={$tt}"> //-->
<!-- SCIENCESERVER IDENTIFICATION: {$securett}{$rqst} //-->
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
$style = <<<STYLESHT
<style>
{$ss}
body {margin-top: 9vh; } 
</style>
STYLESHT;
$jvscript = self::globaljavascriptrUser($usr); 
$jvcontent = <<<JAVASCRIPTR
<script lang=javascript>
{$jvscript}
</script>
JAVASCRIPTR;
//  $this->userInfo = array("pfcMember" => false, "pfcPennKey" => $usr['pfcpennkey'], "pfcMemberFirstName" => "", "pfcMemberLastName" => "", "pfcMemberId" => $pfrpid, "pfcMemberEmail" => "");      
$pgContent = self::topAndMenuBarUser($usr);
$pgContent .= "<center><table style=\"width: 80vw;\"><tr><td>MAIN WELCOME SCREEN</td></tr></table>";
return array(
         "preamble" => htmlspecialchars($preamb)
       , "head" => htmlspecialchars($head)
       , "style" => htmlspecialchars($style)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       );
}
    
    
function templatehome($systemidentifier, $mobileindicator, $useridentifier) {
     return array(
         "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
       , "head" => htmlspecialchars($preamb)
       , "style" => htmlspecialchars($sty)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       );
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


function globaljavascriptrUser($usr) {
$tt = pfcsecureurl;
$dtaTree = dataPath;
$dtakey = generatePFCSessionKey($usr);
$usrEncrypt = pfccryptservice($usr,'e',false);

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
      return array(
         "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
       , "head" => htmlspecialchars($preamb)
       , "style" => htmlspecialchars($sty)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       );
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
      return array(
         "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
       , "head" => htmlspecialchars($preamb)
       , "style" => htmlspecialchars($sty)
       , "javascriptr" => $jvcontent
       , "body" => htmlspecialchars($pgContent)
       );
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

        return array(
            "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
            , "head" => htmlspecialchars($preamb)
            , "style" => htmlspecialchars($sty)
            , "javascriptr" => $jvcontent
            , "body" => htmlspecialchars($pgContent)
            );
    }

    function park($mobilelayout = "", $rqstPage = "") {
        return array(
            "preamble" => htmlspecialchars("<!DOCTYPE html><html>")
            , "head" => htmlspecialchars(park_header())
            , "style" => htmlspecialchars(park_style($mobilelayout))
            , "javascriptr" => htmlspecialchars("")
            , "body" => htmlspecialchars(park_body())
            );
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


/* DATA FUNCTIONS */

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

function generatePFCSessionKey($pennKey) { 
    require(serverkeys .  "/sspdo.zck");

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
    
    return pfccryptservice($ky,'e',false);  
}


