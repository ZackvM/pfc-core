<?php

class pfcdataapplication {

  public $responseCode = 400;  
  public $message = "";
  public $itemsFound = 0;
  public $rtnData = array();
  
  
  function __construct() { 
    $args = func_get_args(); 
    $nbrofargs = func_num_args(); 
  
    
    
    if (trim($args[0]) === "") { 
        //ERROR
    } else { 
        $request = explode("/",$args[0]);
        
        if (class_exists($request[1], false)) { 

          $cls = $request[1];
          if (trim($request[2]) === "") { 
              //ERROR
          } else {  
               $this->rtnData = $request[2];
            if (method_exists($cls, $request[2] )) {   

                $dpp = new pfcapplication();
                $funcName = $request[2]; 
                $z = $dpp->$funcName( $args[0] , $args[1] ,"" ,"" );

                $this->responseCode = $dpp->responseCode;
                $this->message = $dpp->message;
                $this->itemsFound = $dpp->itemsFound;
                $this->rtnData = $dpp->rtnData;
                //$this->message = "{$funcName}";
            } else { 
//              //REQUESTED METHOD DOES NOT EXIST
                $this->responseCode = 500;
                $this->message = "METHOD DOES NOT EXIST";
                $this->itemsFound = 0;
                $this->rtnData = "ZACK WAS HERE";
            }
          }
        } else { 
          //REQUESTED CLASS DOES NOT EXIST
            
        }
    }
    
  }
  
  
}


class pfcapplication {
  public $responseCode = 400;
  public $message = "";
  public $itemsFound = 0;
  public $rtnData = array();
  
function pfrpmemberemaillisting($request) { 
      $rtnData = array();
      require(genAppFiles . "/dataconn/sspdo.zck");
      $emlSQL = "SELECT pfcmemberid, concat(ifnull(memberlastname,''),', ', ifnull(memberfirstname,''), ' (', ifnull(pfrptitle,''), ')') as membername FROM pfc.sys_pfcmember_pennkey order by memberlastname";
      $emlR = $conn->prepare($emlSQL); 
      $emlR->execute(); 
      $emlDta = array(); 
      while ($r = $emlR->fetch(PDO::FETCH_ASSOC)) { 
        $rtnData[] = $r;
      }
      $this->responseCode = 200; 
      $this->itemsFound = $emlR->rowCount();
      $this->message = "";
      $this->rtnData = $rtnData;
   }
  
function pfrpactions($request) {
      //TODO: THIS FUNCTION IS THE GLOBALMENU FUNCTION FROM DATACHTNEAST AND SHOULD REALLY TO A HEADER FUNCTION OF ITS OWN
     $rows = array(); 
     $rParts = explode("/", $request); 
     $gMenu = trim($rParts[2]);
     //TO LOAD ALL METHODS IN A CLASS INTO AN ARRAY USE get_class_methods
     //$gm = new globalMenus(); 
     //if (method_exists($gm,$gMenu)) { 
     //  $SQL = $gm->$gMenu($rParts[3]);
     $SQL = "SELECT rsts.actionid as codevalue, rsts.reviewaction as menuvalue, 0 as useasdefault, '' as lookupvalue FROM pfc.appdata_project_reviewerstatus rsts where rsts.furtheractionind = 0 order by rsts.dspOrd";
       if (trim($SQL) !== "") {
         //RUN SQL - RETURN RESULTS
         require(genAppFiles . "/dataconn/sspdo.zck");
         $r = $conn->prepare($SQL); 
         $r->execute(); 
         $itemsFound = $r->rowCount();
         while ($rs = $r->fetch(PDO::FETCH_ASSOC)) { 
           $data[] = $rs;
         }
         $rows['statusCode'] = 200;
         $rows['data'] = array('MESSAGE' => '', 'ITEMSFOUND' => $itemsFound, 'DATA' => $data);
       } else { 
         $rows['statusCode'] = 503;
         $rows['data'] = array('MESSAGE' => 'NO SQL RETURNED', 'ITEMSFOUND' => 0,  'DATA' => '');
       }
     //} else {
     //   $rows['statusCode'] = 404; 
     //   $rows['data'] = array('MESSAGE' => 'MENU NOT FOUND', 'ITEMSFOUND' => 0, 'DATA' => "");
     //}
     $this->rtnData = $data;
     $this->responseCode = 200;
     $this->message = "";
     $this->itemsFound = $itemsFound;
     return $rows;
    }

function apprequest($request, $passedData, $rUsr, $rSess) {
    $pDta = json_decode($passedData, true);
    $sysid = chtndecrypt($pDta['systemid']);
    $logdUsr = chtndecrypt( $pDta['userid'] );
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
           $this->message = $pfrpMember['memid'] . " " . $pfrpMember['firstname'];
        }

        //GENERAL USER
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


        $this->rtnData = $rtnPageArr;
        $this->responseCode = 200;
    } else {
        //SYSTEM IS NOT CORRECT
        $this->message = "NOT SYSTEM ID - FAILURE";
    }

    $rows['statusCode'] = $this->responseCode;
    $rows['data'] = array("MESSAGE" => "", "ITEMS" => $this->itemsFound, "DATA" => $this->rtnData);
    return $rows;
}

function livestatuslist($request, $passedData, $rUsr, $rSession) {
    $pDta = json_decode($passedData, true);
    $mem = pfcmember(pfccryptservice( $pDta['pennkey'] , 'd', false));
    if ($mem['responseCode'] === 200) {
      require(genAppFiles .  "/dataconn/sspdo.zck");
      $sql = "select stsTbl.datastatus, stsTbl.statusmodifier, stsTbl.projstatusdsp, rvwsts.furtherActionInd, rvwsts.completionActionInd from (select distinct projtostatus.datastatus, if(trim(ifnull(projtostatus.statusmodifier,''))='',projtostatus.datastatus,trim(ifnull(projtostatus.statusmodifier,''))) as statusmodifier, concat(ifnull(projtostatus.datastatus,''),if(ifnull(projtostatus.statusmodifier,'') = '', '', concat(' / ',ifnull(projtostatus.statusmodifier,'')))) projstatusdsp from ( SELECT prj.*, ps.statusid as joinstatid, projsts.* from pfc.ut_projects prj left join pfc.appdata_project_statuses ps on ps.statusid = (SELECT statusid FROM pfc.appdata_project_statuses  where projid = prj.projectid order by statusdate desc limit 1) left join pfc.appdata_project_statuses projsts on ps.statusid =  projsts.statusid) as projtostatus) as stsTbl left join pfc.appdata_project_reviewerstatus rvwsts on stsTbl.statusmodifier = rvwsts.reviewaction order by rvwsts.projectlistingdsp ";
      $rs = $conn->prepare($sql);
      $rs->execute();
      if ($rs->rowCount() < 1) {
          $this->responseCode = 404;
      } else {
        $this->itemsFound = $rs->rowCount();
        $val = array();
        while ($r = $rs->fetch(PDO::FETCH_ASSOC)) {
            $val[] = $r;
        }
        $this->rtnData = $val;
        $this->responseCode = 200;
      }
    } else {
      $this->responseCode = 403;
    }
}

function projectsbystatus($request, $passedData, $rUsr, $rSession) {
    require(genAppFiles .  "/dataconn/sspdo.zck");
    $pDta = json_decode($passedData, true);
    $qsts = json_decode($pDta['datapayload'], true);
    $lookupSQL = "select if(ifnull(projtostatus.statusmodifier,'') = '',ifnull(projtostatus.datastatus,''), ifnull(projtostatus.statusmodifier,'')) as projectStatus , substr(concat('000000',projtostatus.projectid),-6) as dspprojectid, projtostatus.userid, projtostatus.submitByWho submittingpennkey, ifnull(projtostatus.projectpdf,'') as projectpdffilename, projtostatus.projecttitle, projtostatus.irbnbr, projtostatus.irbexpiration, projtostatus.statusby laststatusby, projtostatus.statusdate, ifnull(projtostatus.pfcapprovalnumber,'') as pfcapprovalnbr , pi.contactName as piname, ifnull(conPhn.phnnbr,'') as piphone, ifnull(conEml.eml,'') as piemail from (SELECT prj.*, ps.statusid as joinstatid, projsts.* from pfc.ut_projects prj left join pfc.appdata_project_statuses ps on ps.statusid = (SELECT statusid FROM pfc.appdata_project_statuses where projid = prj.projectid order by statusdate desc limit 1) left join pfc.appdata_project_statuses projsts on ps.statusid =  projsts.statusid) as projtostatus left join (SELECT projContId, projid, contactName  FROM pfc.ut_projects_contacts where contactType = 'PROJECT-PI') pi on projtostatus.projectid = pi.projid left join (SELECT contactid, metric as phnnbr FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'PHONE') as conPhn on pi.projContId = conPhn.contactid left join (SELECT contactid, metric as eml FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'EMAIL') as conEml on pi.projContId = conEml.contactid where if(ifnull(projtostatus.statusmodifier,'') = '',ifnull(projtostatus.datastatus,''), ifnull(projtostatus.statusmodifier,'')) = :qryStatus order by projtostatus.statusdate desc";
   $rs = $conn->prepare($lookupSQL);
   $rs->execute(array(':qryStatus' => $qsts['qryStatus']));
   if ($rs->rowCount() < 1) {
       $this->responseCode = 404;
       $this->itemsFound = $rs->rowCount();
       $this->message = "No Projects with a status of {$qsts['qryStatus']} found";
   } else {
       $this->itemsFound = $rs->rowCount();
       $projLst = array();
       while ($r = $rs->fetch(PDO::FETCH_ASSOC)) {
           $projLst[] = $r;
       }
       $this->message = $qsts['qryStatus'];
       $this->rtnData = $projLst;
       $this->responseCode = 200;
   }
   $rtn = array("MESSAGE" => $this->message, "ITEMS" => $this->itemsFound, "DATA" => $this->rtnData);
   $rows['statusCode'] = $this->responseCode;
   $rows['data'] = $rtn;
   return $rows;
}

function getmyprojects($request, $passedData, $rUsr, $rSession) {
   $pDta = json_decode($passedData, true);
   $pKey = chtndecrypt( $pDta['pennkey'] );
   if ($pKey === "") {
       //ERROR - EMPTY PENNKEY
   } else {
     require(genAppFiles .  "/dataconn/sspdo.zck");
     $prjSQL = "SELECT substr(concat('000000',ifnull(pj.projectid,'')),-6) as projectid, pj.projectpdf, pj.projecttitle, pj.irbnbr, pj.irbexpiration, pj.completeind, pj.pfcapprovalnumber, pj.pfcapprovalexpiration, date_format(pj.submitonwhen,'%m/%d/%Y') as projectcreationdate, cn.contactname as projectpi, ps.datastatus, ps.statusmodifier, date_format(ps.statusdate, '%m/%d/%Y %H:%i') as statusdate FROM pfc.ut_projects pj left join (SELECT contactname, projid FROM pfc.ut_projects_contacts  where contactType = 'PROJECT-PI') cn on pj.projectid = cn.projid left join pfc.appdata_project_statuses ps on ps.statusid = (select sbst.statusid from pfc.appdata_project_statuses sbst where sbst.projid = pj.projectid order by sbst.statusdate desc limit 1) where pennkey = :pennkey order by submitonwhen desc";
     $prjR = $conn->prepare($prjSQL);
     $prjR->execute(array(':pennkey' => $pKey));

   if ($prjR->rowCount() < 1) {
       $this->responseCode = 404;
       $this->itemsFound = $prjR->rowCount();
       $this->message = "No Projects for this user found";
   } else {
       $this->itemsFound = $prjR->rowCount();
       $projLst = array();
       while ($r = $prjR->fetch(PDO::FETCH_ASSOC)) {
           $projLst[] = $r;
       }
       $this->message = "";
       $this->rtnData = $projLst;
       $this->responseCode = 200;
   }
   }
   $rtn = array("MESSAGE" => $this->message, "ITEMS" => $this->itemsFound, "DATA" => $this->rtnData);
   $rows['statusCode'] = $this->responseCode;
   $rows['data'] = $rtn;
   return $rows;
}

function getprojectbymember($request, $passedData, $rUsr, $rSession) {
   $pDta = json_decode($passedData, true);
   $pKey = pfccryptservice( $pDta['pennkey'],'d',false );
   $pid = $pDta['projectid'];
   require(genAppFiles .  "/dataconn/sspdo.zck");
   $memberChkSQL = "SELECT pfcmemberid FROM pfc.sys_pfcmember_pennkey where allowQry = 1 and pfcpennkeyref = :memberkey";
   $memberChkR = $conn->prepare($memberChkSQL);
   $memberChkR->execute(array(':memberkey' => $pKey));
   if ($memberChkR->rowCount() < 1) {
     $this->responseCode = 400;
     $this->message = "USER NOT ALLOWED";
   } else {

       $projSQL = "SELECT usr.pennkey, usr.firstname, usr.lastname, substr(concat('000000',ifnull(pj.projectid,'')), -6) as projectid, pj.projectpdf, pj.projecttitle, pj.irbnbr, pj.irbexpiration, ifnull(pj.approvalyear,'') as approvalyear, pj.completeind, ifnull(pj.pfcapprovalnumber,'') as pfcapprovalnumber, ifnull(pj.pfcapprovalexpiration,'') as pfcapprovalexpiration, date_format(pj.submitonwhen, '%m/%d/%Y') as submissiondate, cmt.projcomments, cmt.bywhom FROM pfc.ut_projectUsers usr left join pfc.ut_projects pj on usr.pennkey = pj.pennkey left join pfc.ut_projects_comments cmt on pj.projectid = cmt.projectid where pj.projectid = :projnbr";
   $projR = $conn->prepare($projSQL);
   $projR->execute(array(':projnbr' => $pid));
   if ($projR->rowCount() < 1) {
       $this->responseCode = 404;
       $this->itemsFound = 0;
       $this->message = "NO PROJECT FOUND WITH PROJECT NUMBER: {$pid} FOR THIS USER";
       $this->rtnData = "";
   } else {
       $proj = $projR->fetch();

       if ($proj['completeind'] <> 1) {
           //CHANGE INTERNAL STATUS TO 'IN REVIEW' - SINCE IT WAS CLICKED IF THE COMPLETE IND IS NOT TRUE
           $updSQL = "insert into pfc.appdata_project_statuses(datastatus, statusmodifier, statusdate, projid, applicationModule, statusby) values (:datastatus, :statusmodifier, now(), :projid, 'PROJECT-PFRP-EDIT', :statusby) ";
           $updR = $conn->prepare($updSQL);
           $updR->execute(array(':datastatus' => 'IN REVIEW', ':statusmodifier' => '', ':projid' => $pid, ':statusby' => $pKey));
       }
       $projDta = array();
       $projDta['pennkey'] = $proj['pennkey'];
       $projDta['firstname'] = $proj['firstname'];
       $projDta['lastname'] = $proj['lastname'];
       $projDta['projectid'] = $proj['projectid'];
       $projDta['projectpdf'] = $proj['projectpdf'];
       $projDta['projecttitle'] = $proj['projecttitle'];
       $projDta['irbnbr'] = $proj['irbnbr'];
       $projDta['irbexpiration'] = $proj['irbexpiration'];
       $projDta['approvalyear'] = $proj['approvalyear'];
       $projDta['completeind'] = $proj['completeind'];
       $projDta['pfcapprovalnumber'] = $proj['pfcapprovalnumber'];
       $projDta['pfcapprovalexpiration'] = $proj['pfcapprovalexpiration'];
       $projDta['submissiondate'] = $proj['submissiondate'];
       $projDta['projectcomments'] = $proj['projcomments'];
       $projDta['bywhom'] = $proj['bywhom'];
       $docSQL = "SELECT ucase(ifnull(doc.typeofdocument,'')) as typeofdocument, ifnull(originaldocumentname,'PFRP-INTERNAL-DOCUMENT') as originaldocumentname, directorydocumentname, date_format(uploadedon,'%m/%d/%Y') as uploadedon, uploadedby FROM pfc.ut_projects_documents doc where projectid = :projid";
       $docR = $conn->prepare($docSQL);
       $docR->execute(array(':projid' => $pid));
       $docs = array();
       while ($dr = $docR->fetch(PDO::FETCH_ASSOC)) {
         $docs[] = $dr;
       }
       $projDta['documents'] = $docs;
       $contSQL = "SELECT cnt.projcontid, cnt.contactname, cnt.salutation, cnt.degrees, cnt.contacttype, phn.metric as phonenbr, eml.metric as emailaddress FROM pfc.ut_projects_contacts cnt left join (SELECT metric, contactid FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'PHONE') phn on cnt.projcontid = phn.contactid left join (SELECT metric, contactid FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'EMAIL') eml on cnt.projcontid = eml.contactid where projid = :projid";
       $contR = $conn->prepare($contSQL);
       $contR->execute(array(':projid' => $pid));
       $contacts = array();
       $contacts = $contR->fetchAll(PDO::FETCH_ASSOC);
       $projDta['contacts'] = $contacts;
       $answerSQL = "SELECT qa.answerid, qs.question, if(qa.answer = 1, 'YES','NO') as answer FROM pfc.ut_projects_questionanswers qa left join pfc.sys_project_questions qs on qa.questionid = qs.questionapplicationid where qa.projectid = :projid order by qs.dspord";
       $qaR = $conn->prepare($answerSQL);
       $qaR->execute(array(':projid' => $pid));
       $qanda = array();
       $qanda = $qaR->fetchAll(PDO::FETCH_ASSOC);
       $projDta['questionanswers'] = $qanda;
       $prjStsSQL = "SELECT datastatus, ifnull(statusmodifier,'') as statusmodifier, date_format(statusdate,'%m/%d/%Y %H:%i') as statusdate, ifnull(lettercomments,'') as lettercomments FROM pfc.appdata_project_statuses where projid = :projid";
       $prjStsR = $conn->prepare($prjStsSQL);
       $prjStsR->execute(array(':projid' => $pid));
       $proStatus = array();
       $projStatus = $prjStsR->fetchAll(PDO::FETCH_ASSOC);
       $projDta['statuses'] = $projStatus;
       $this->message = "";
       $this->itemsFound = 1;
       $this->rtnData = $projDta;
       $this->responseCode = 200;
   }
   }
}

function getaproject($request, $passedData, $rUsr, $rSession) {
   $pDta = json_decode($passedData, true);
   $pKey = chtndecrypt( $pDta['pennkey'] );
   $pid = $pDta['projectid'];
   $pfrpid = $pDta['pfrpid'];
   require(genAppFiles .  "/dataconn/sspdo.zck");
   $projSQL = "SELECT usr.pennkey, usr.firstname, usr.lastname, substr(concat('000000',ifnull(pj.projectid,'')), -6) as projectid, pj.projectpdf, pj.projecttitle, pj.irbnbr, pj.irbexpiration, pj.approvalyear, pj.completeind, pj.pfcapprovalnumber, pfcapprovalexpiration, date_format(pj.submitonwhen, '%m/%d/%Y') as submissiondate, cmt.projcomments, cmt.bywhom FROM pfc.ut_projectUsers usr left join pfc.ut_projects pj on usr.pennkey = pj.pennkey left join pfc.ut_projects_comments cmt on pj.projectid = cmt.projectid where usr.pennkey = :pennkey and usr.pfrpid = :pfrpid and pj.projectid = :projnbr";
   $projR = $conn->prepare($projSQL);
   $projR->execute(array(':pennkey' => $pKey, ':pfrpid' => $pfrpid, ':projnbr' => $pid));
   if ($projR->rowCount() < 1) {
       $this->responseCode = 404;
       $this->itemsFound = 0;
       $this->message = "NO PROJECT FOUND WITH PROJECT NUMBER: {$pid} FOR THIS USER";
       $this->rtnData = "";
   } else {
       $proj = $projR->fetch();
       $projDta = array();
       $projDta['pennkey'] = $proj['pennkey'];
       $projDta['firstname'] = $proj['firstname'];
       $projDta['lastname'] = $proj['lastname'];
       $projDta['projectid'] = $proj['projectid'];
       $projDta['projectpdf'] = $proj['projectpdf'];
       $projDta['projecttitle'] = $proj['projecttitle'];
       $projDta['irbnbr'] = $proj['irbnbr'];
       $projDta['irbexpiration'] = $proj['irbexpiration'];
       $projDta['approvalyear'] = $proj['approvalyear'];
       $projDta['completeind'] = $proj['completeind'];
       $projDta['pfcapprovalnumber'] = $proj['pfcapprovalnumber'];
       $projDta['pfcapprovalexpiration'] = $proj['pfcapprovalexpiration'];
       $projDta['submissiondate'] = $proj['submissiondate'];
       $projDta['projectcomments'] = $proj['projcomments'];
       $projDta['bywhom'] = $proj['bywhom'];
       $docSQL = "SELECT ucase(ifnull(doc.typeofdocument,'')) as typeofdocument, ifnull(originaldocumentname,'PFRP-INTERNAL-DOCUMENT') as originaldocumentname, directorydocumentname, date_format(uploadedon,'%m/%d/%Y') as uploadedon, uploadedby FROM pfc.ut_projects_documents doc where projectid = :projid";
       $docR = $conn->prepare($docSQL);
       $docR->execute(array(':projid' => $pid));
       $docs = array();
       while ($dr = $docR->fetch(PDO::FETCH_ASSOC)) {
         $docs[] = $dr;
       }
       $projDta['documents'] = $docs;
       $contSQL = "SELECT cnt.projcontid, cnt.contactname, cnt.salutation, cnt.degrees, cnt.contacttype, phn.metric as phonenbr, eml.metric as emailaddress FROM pfc.ut_projects_contacts cnt left join (SELECT metric, contactid FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'PHONE') phn on cnt.projcontid = phn.contactid left join (SELECT metric, contactid FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'EMAIL') eml on cnt.projcontid = eml.contactid where projid = :projid";
       $contR = $conn->prepare($contSQL);
       $contR->execute(array(':projid' => $pid));
       $contacts = array();
       $contacts = $contR->fetchAll(PDO::FETCH_ASSOC);
       $projDta['contacts'] = $contacts;
       $answerSQL = "SELECT qa.answerid, qs.question, if(qa.answer = 1, 'YES','NO') as answer FROM pfc.ut_projects_questionanswers qa left join pfc.sys_project_questions qs on qa.questionid = qs.questionapplicationid where qa.projectid = :projid order by qs.dspord";
       $qaR = $conn->prepare($answerSQL);
       $qaR->execute(array(':projid' => $pid));
       $qanda = array();
       $qanda = $qaR->fetchAll(PDO::FETCH_ASSOC);
       $projDta['questionanswers'] = $qanda;
       $prjStsSQL = "SELECT datastatus, ifnull(statusmodifier,'') as statusmodifier, date_format(statusdate,'%m/%d/%Y %H:%i') as statusdate, ifnull(lettercomments,'') as lettercomments FROM pfc.appdata_project_statuses where projid = :projid";
       $prjStsR = $conn->prepare($prjStsSQL);
       $prjStsR->execute(array(':projid' => $pid));
       $proStatus = array();
       $projStatus = $prjStsR->fetchAll(PDO::FETCH_ASSOC);
       $projDta['statuses'] = $projStatus;
       $this->message = "";
       $this->rtnData = $projDta;
       $this->responseCode = 200;
   }
   $rtn = array("MESSAGE" => $this->message, "ITEMS" => $this->itemsFound, "DATA" => $this->rtnData);
   $rows['statusCode'] = $this->responseCode;
   $rows['data'] = $rtn;
   return $rows;
}

function requireddocumentlist($request, $passedData, $rUsr, $rSession) {
   require(genAppFiles .  "/dataconn/sspdo.zck");
   $docSQL = "SELECT  docid, documenttype, requiredind FROM pfc.appdata_project_document_type where requiredind = 1 and dspind = 1 order by orderind";
   $docR = $conn->prepare($docSQL);
   $docR->execute();
   $documentListing = array();
   if ($docR->rowCount() > 0) {
       while ($dr = $docR->fetch(PDO::FETCH_ASSOC)) {
          $documentListing[] = $dr;
       }
       $this->itemsFound = $docR->rowCount();
       $this->rtnData = $documentListing;
       $this->responseCode = 200;
  }
   $rtn = array("MESSAGE" => $this->message, "ITEMS" => $this->itemsFound, "DATA" => $this->rtnData);
   $rows['statusCode'] = $this->responseCode;
   $rows['data'] = $rtn;
   return $rows;

}

function sendpfrpreferal($request, $passedData, $rUsr, $rSession) {
    $pDta = json_decode($passedData, true);
    $emlArr = json_decode($pDta['datapayload'], true);
    require(genAppFiles .  "/dataconn/sspdo.zck");
    $rUsr = pfccryptservice($_SERVER['HTTP_PFC_TOKEN'],'d',false);
    $chkSQL = "SELECT pfcmemberid, memberemail FROM pfc.sys_pfcmember_pennkey where pfcpennkeyref = :pennkey";
    $chkR = $conn->prepare($chkSQL);
    $chkR->execute(array(':pennkey' => $rUsr));
    if ($chkR->rowCount() < 1) {
      $this->responseCode = 400;
      $this->message = "USER ({$rUsr}) NOT ALLOWED";
    } else {
      $from = array();
      $from[] = $chkR->fetch(PDO::FETCH_ASSOC);
      $senderEmail = $from[0]['memberemail'];
      $recipChkSQL = "SELECT memberemail FROM pfc.sys_pfcmember_pennkey where pfcmemberid = :recipcode";
      $recipR = $conn->prepare($recipChkSQL);
      $recipR->execute(array(':recipcode' => $emlArr['recip']));
      if ($recipR->rowCount() < 1) {
        $this->responseCode = 500;
        $this->message = "RECIPIENT EMAIL NOT FOUND ({$emlArr['recip']})";
      } else {
        $rTo = array();
        $rTo[] = $recipR->fetch(PDO::FETCH_ASSOC);
        $recipEmail = $rTo[0]['memberemail'];
        if (trim($emlArr['proj']) === "") {
          $this->responseCode = 500;
          $this->message = "NO PROJECT ID LISTED";
        }  else {
           $at = genAppFiles;
           $pPic = base64file( "{$at}/publicobj/graphics/psom_logo_blue.png", "PSOMLogo", "image", true, " style=\"width: 1.5in;\" ");
           $header = "<table border=0 style=\"width: 7.75in;\"><tr><td>{$pPic}</td><td style=\"text-align: center;font-size: 1.2vh;font-family: tahoma, arial; font-weight: bold;\">Pathology Feasibility Review Panel (PFRP)<br>PROJECT REFERAL EMAIL</td><td style=\"width: 1.5in;\">&nbsp;</td></tr></table>";
           $footer = <<<HTMLFOOT
<table style="width: 720px;font-family: tahoma, arial; font-size: .8vh;padding-top: 50px;"><tr><td><center>Pathology Feasibility Review Panel (PFRP)<br>Hospital of the University of Pennsylvania<br>3400 Spruce Street, 6 FOUNDERS<br>Philadelphia, Pennsylvania 19104<br>(215) 662-4570</td></tr></table>
HTMLFOOT;
        //Format Referal Email
        $emailBdy = <<<EMAILBODY
                <table><tr><td>PFRP Project ({$emlArr['proj']}) has been referred to you by {$senderEmail}. Please DO NOT response to this email, but redirect all comments to the referenced email address.   Their comments are below:</td></tr>
                <tr><td>{$emlArr['msg']}</td></tr>
                </table>
EMAILBODY;
        $email = $header . $emailBdy . $footer;
        $notifyList = array();
        $notifyList[] = $recipEmail;
         $emlSQL = "insert into serverControls.emailthis (towhoaddressarray,sbjtline,msgbody,htmlind,wheninput, bywho) values(:recipArr,:subject,:htmlstuff,1,now(),:bywho)";
         $emlR = $conn->prepare($emlSQL);
         $emlR->execute(array(":recipArr" => json_encode($notifyList), ":subject" => "PFRP REFERAL ({$emlArr['proj']})",":htmlstuff" => $email, ":bywho" => "PFRP-SYSTEM"));         
         $this->responseCode = 200;
         $this->message = "";
        }
      }
    }
}

function pfrpdecision($request, $passedData, $rUsr, $rSession) {
    
    ////START HERE 2019-01-29///////
    //LETTERS FOR APPROVED PBRF and DENIAL not sending
       $rUsr = pfccryptservice($_SERVER['HTTP_PFC_TOKEN'],'d',false);       
       $this->message = $passedData;
       
    $decDta = json_decode($passedData, true);
    $decArr = json_decode($decDta['datapayload'], true);
    $projid = (int)$decArr['proj'];
    $decisionid = $decArr['decision'];
    $lettercomments = $decArr['lettercomments'];
    $internalcomments = $decArr['internals'];


    require(genAppFiles .  "/dataconn/sspdo.zck");
    $chkAppSQL = "SELECT pfcpennkeyref, memberfirstname, memberlastname, memberemail, ifnull(lettersignline,'') as lettersignline FROM pfc.sys_pfcmember_pennkey where allowqry = 1 and approver = 1 and pfcpennkeyref = :pennkey";
    $chkApp = $conn->prepare($chkAppSQL);
    $chkApp->execute(array(':pennkey' => $rUsr));
      
    if ($chkApp->rowCount() < 1) {
      $this->responseCode = 403;
      $this->message = "USER IS NOT ALLOWED TO PERFORM THIS ACTION";
      $this->itemsFound = 0;
    } else {
   
      $chkProjStsSQL = "SELECT * FROM pfc.ut_projects where completeind = 0 and projectid = :projid";
      $chkProjStsR = $conn->prepare($chkProjStsSQL);
      $chkProjStsR->execute(array(':projid' => $projid));

      if ($chkProjStsR->rowCount() < 1) {
        $this->responseCode = 404;
        $this->message = "Project is already marked complete";
        $this->itemsFound = 0;
      } else {

        $decSQL = "SELECT reviewaction, projectstatus, furtheractionind, completionactionind FROM pfc.appdata_project_reviewerstatus where actionid = :decision";
        $decR = $conn->prepare($decSQL);
        $decR->execute(array(':decision' => $decisionid));
        
        if ($decR->rowCount() < 1) {
          $this->responseCode = 500;
          $this->message = "DECISION METRIC NOT FOUND";
          $this->itemsFound = 0;
        } else {

          $decision = array();
          $decision = $decR->fetch(PDO::FETCH_ASSOC);
          $statusmodifier = $decision['reviewaction'];
          $datastatus = $decision['projectstatus'];
          $completeind = $decision['completionactionind'];
          
          if ((int)$completeind <> 1) {
            $this->responseCode = 500;
            $this->message = "REVIEW DECISION MUST BE A COMPLETION STATUS";
            $this->itemsFound = 0;
          } else {
              
            $statUpdSQL = "insert into pfc.appdata_project_statuses (datastatus, statusmodifier, furtherinfo, lettercomments, projid, applicationmodule, statusby, statusdate) values(:datastatus, :statusmodifier, :furtherinfo, :lettercomments, :projid, 'PFRP-REVIEW-PAGE-STATUS-UPDATE', :statusby, now())";
            $statUpdR = $conn->prepare($statUpdSQL);
            $statUpdR->execute(array(
                ':datastatus' => $datastatus
              , ':statusmodifier' => $statusmodifier
              , ':furtherinfo' => trim($internalcomments)
              , ':lettercomments' => trim($lettercomments)
              , ':projid' => $projid
              , ':statusby' => $rUsr
            ));
            
            if ($datastatus === "REVIEW COMPLETE (APPROVED)") {
              $yrApprSQL = "SELECT count(1) as yrapprove FROM pfc.ut_projects where approvalyear = :appyear";
              $yrApprR = $conn->prepare($yrApprSQL);
              $cYear = (int)date('y');
              $yrApprR->execute(array(':appyear' => $cYear));
              $yrCnt = $yrApprR->fetch(PDO::FETCH_ASSOC);
              $ttlAprYr = $yrCnt['yrapprove'];
              $apprNbr = "PFRP-" . $cYear  . "-" . substr(('0000' . ((int)$ttlAprYr + 1)), -4);
              $updProjSQL = "update pfc.ut_projects set approvalYear = :apryr, completeind = 1, pfcapprovalnumber = :approvalnbr, pfcapprovalexpiration = date_format(date_add(now(), INTERVAL 1 year), '%Y-%m-%d') where projectid = :projid";
              $updProjR = $conn->prepare($updProjSQL);
              $updProjR->execute(array(':apryr' => $cYear, ':approvalnbr' => $apprNbr, ':projid' => $projid));

              $letterElements = array('projid' => $projid, 'datastatus' => $datastatus, 'statusmodifier' => $statusmodifier, 'reviewer' => $rUsr, 'lettercomments' => trim($lettercomments));
              $letterTxt = buildpfrletter($letterElements);
              $this->message = "FINISHED " . $letterTxt;
              $this->rtnData = $letterElements;
              $this->itemsFound = 0;
              //BUILD LETTERS (APPROVAL) X
              //BUILD PICKUP FORM X
              //UPDATE PROJECT DOCUMENTS X
              //EMAIL LETTER AND PICKUP FORM TO SUBMITTER X
              $this->responseCode = 200;
              
            } else {
                
              $updProjSQL = "update pfc.ut_projects set completeind = 1 where projectid = :projid";
              $updProjR = $conn->prepare($updProjSQL);
              $updProjR->execute(array(':projid' => $projid));
              $letterElements = array('projid' => $projid, 'datastatus' => $datastatus, 'statusmodifier' => $statusmodifier, 'reviewer' => $rUsr, 'lettercomments' => trim($lettercomments));
              $letterTxt = buildpfrletter($letterElements);
              $this->message = "FINISHED " . $letterTxt ;
              $this->rtnData = $letterElements;
              $this->itemsFound = 0;
              //BUILD LETTERS (DENIAL) X
              //UPDATE PROJECT DOCUMENTS X
              //EMAIL LETTER TO INVESTIGATOR SUBMITTER X
              $this->responseCode = 200;
            }
         }

        }

      }

    }

}

function getpfrpdocument($request, $passedData, $rUsr, $rSession) {

    $docDta = json_decode($passedData, true);
    $docArr = json_decode($docDta['datapayload'], true);
    $rUsr = pfccryptservice($_SERVER['HTTP_PFC_TOKEN'],'d',false);
    
    require(genAppFiles .  "/dataconn/sspdo.zck");
    $memSQL = "SELECT pfcmemberId FROM pfc.sys_pfcmember_pennkey where allowqry = 1 and pfcpennkeyref = :pennkey";
    $memR = $conn->prepare($memSQL);
    $memR->execute(array(':pennkey' => $rUsr));
    if ($memR->rowCount() < 1) {
      $chkSQL = "SELECT pj.projectid, originalDocumentName, directorydocumentname FROM pfc.ut_projects_documents dc left join pfc.ut_projects pj on dc.projectid = pj.projectid where directorydocumentname = :docname and pennkey = :userpkey union SELECT projectid, 'systemgeneratedpdf', projectpdf FROM pfc.ut_projects where pennkey = :userpkeyprj and projectpdf = :docnameprj";
      $r = $conn->prepare($chkSQL);
      $r->execute(array(':docname' => $docArr['qryDocument'], ':userpkey' => $rUsr,':docnameprj' => $docArr['qryDocument'], ':userpkeyprj' => $rUsr     ));
    } else {
      $chkSQL = "SELECT pj.projectid, originalDocumentName, directorydocumentname FROM pfc.ut_projects_documents dc left join pfc.ut_projects pj on dc.projectid = pj.projectid where directorydocumentname = :docname union SELECT projectid, 'systemgeneratedpdf', projectpdf FROM pfc.ut_projects where projectpdf = :docnameprj";
      $r = $conn->prepare($chkSQL);
      $r->execute(array(':docname' => $docArr['qryDocument'],':docnameprj' => $docArr['qryDocument'] ));
    }
    
    if ($r->rowCount() > 0) {
       $documentLink = genAppFiles . "/publicobj/documents/pfrp/{$docArr['qryDocument']}";
       $documentBaseCode = base64file($documentLink, "PFRPDOCUMENT","DOCPDF", false);
       $this->rtnData = $documentBaseCode;
       $this->responseCode = 200;
    } else {
        $this->message = "USER NOT ALLOWED ACCESS TO THIS DOCUMENT";
        $this->rtnData = $docArr['qryDocument'];
    } 
}

function _clean($str) {
  return is_array($str) ? array_map('_clean',$str) : str_replace('\\','\\\\',strip_tags(trim(htmlspecialchars((get_magic_quotes_gpc() ? stripslashes($str) : $str), ENT_QUOTES))));
}

function savepfrpapplication($request, $passedData, $rUsr, $rSession) {
   $pDta = json_decode($passedData, true);
   $vals = json_decode($pDta['datapayload'], true);
   $aKeys = array_keys($vals);
   if (
       //TODO:  CHECK THESE FIELDS DYNAMICALLY
       trim($vals['fldProjecTitle']) === ""
    || trim($vals['fldprojectirbnbr']) === ""
    || trim($vals['fldprojectirbexp']) === ""
    || trim($vals['frmcontactsubmitterlname']) === ""
    || trim($vals['frmcontactsubmitterfname']) === ""
    || trim($vals['frmcontactsubmitterphone']) === ""
    || trim($vals['frmcontactsubmitteremail']) === ""
    || trim($vals['frmcontactpifname']) === ""
    || trim($vals['frmcontactpilname']) === ""
    || trim($vals['fldPISalutations']) === ""
    || trim($vals['frmcontactpiphone']) === ""
    || trim($vals['frmcontactpiemail']) === ""
    || trim($vals['fldAnswerIdqstnOne']) === ""
    || trim($vals['fldAnswerIdqstnTwo']) === ""
    || trim($vals['fldAnswerIdqstnThree']) === ""
    || trim($vals['fldAnswerIdqstnFour']) === ""
    || trim($vals['docPROJECT-PROTOCOL']) === ""
    || trim($vals['btoPROJECT-PROTOCOL']) === ""
    || trim($vals['docIRB-APPROVAL']) === ""
    || trim($vals['btoIRB-APPROVAL']) === ""
    || trim($vals['docCONSENT-FORM']) === ""
    || trim($vals['btoCONSENT-FORM']) === ""
   ) {
     //BAD FORM
     $this->responseCode = 400;
     $this->message = "ALL FIELDS MARKED WITH A RED ASTERISK ARE REQUIRED";

   } else {
     //CONTINUE ON
     //Validate Dates
     if (!validateDate($vals['fldprojectirbexp'], 'm/d/Y')) {
       $this->responseCode = 500;
       $this->message = "NOT A VALID DATE (mm/dd/YYYY)";
     } else {
       $d = DateTime::createFromFormat('m/d/Y',$vals['fldprojectirbexp']);
       $irbexpiration = $d->format('Y-m-d');
       //Validate Emails
       if (!validateThisEmail($vals['frmcontactsubmitteremail']) || !validateThisEmail($vals['frmcontactpiemail'])) {
         $this->responseCode = 500;
         $this->message = "ALL EMAIL ADDRESSES MUST BE VALID";
       } else {
         $rqstr = $rUsr;
         $ccemail = $vals['frmcontactsubmitteremail'];
         $projuserid = addpfcusr($rqstr, trim($vals['frmcontactsubmitterfname']), trim($vals['frmcontactsubmitterlname']));
         $projectid = addpfcprojectdetails($vals, $projuserid, $rqstr, $irbexpiration);
         addpfccontacts($vals, $projectid, $rqstr);
         if (trim($vals['frmprojectcomments']) !== "") {
             addprojectcomments($vals, $projectid, $rqstr);
         }
         addquestionanswers($vals, $projectid, $rqstr);
         documentreconstruction($vals, $projectid, $rqstr);
         $rtnprojDoc = buildpfrpapplicationpdf($vals, $projectid, $rqstr);
         require(genAppFiles .  "/dataconn/sspdo.zck");
         $notifySQL = "SELECT memberemail FROM pfc.sys_pfcmember_pennkey where notifyonsubmission = 1";
         $notifyR = $conn->prepare($notifySQL);
         $notifyR->execute();
         $notifyList = array();
         while ($nr = $notifyR->fetch(PDO::FETCH_ASSOC)) {
           $notifyList[] = $nr['memberemail'];
         }
         $notifyList[] = $ccemail;
         $emlSQL = "insert into serverControls.emailthis (towhoaddressarray,sbjtline,msgbody,htmlind,wheninput, bywho) values(:recipArr,:subject,:htmlstuff,1,now(),:bywho)";
         $emlR = $conn->prepare($emlSQL);
         $emlR->execute(array(":recipArr" => json_encode($notifyList), ":subject" => "PFRP Application Submission ({$projectid})",":htmlstuff" => $rtnprojDoc, ":bywho" => "PFRP-SYSTEM"));
         $this->message =  "{$projectid}";
         $this->responseCode = 200;
       }
     }
   }

   $rtn = array("MESSAGE" => $this->message, "ITEMS" => $this->itemsFound, "DATA" => $this->rtnData);
   $rows['statusCode'] = $this->responseCode;
   $rows['data'] = $rtn;
   return $rows;
}

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

function buildpfrletter($letterelements) { 
  $ORAppPDF = "";
  $at = genAppFiles;
  require(genAppFiles .  "/dataconn/sspdo.zck");
  $pPic = base64file( "{$at}/publicobj/graphics/psom_logo_blue.png", "PSOMLogo", "image", true, " style=\"width: 1.5in;\" ");
   
  $projSQL = "SELECT projecttitle, irbNbr, date_format(irbexpiration,'%m/%d/%Y') as irbexpiration, ifnull(pfcapprovalnumber,'-') as pfcapprovalnumber, ifnull(pfcapprovalnumber,'') as pfcapprovalnumber, ifnull(date_format(pfcapprovalexpiration,'%m/%d/%Y'),'') as pfcapprovalexpiration, ifnull(pi.contactname,'') as contactname, pi.salutation, phn.metric as phonenbr, eml.metric as piemail FROM pfc.ut_projects pj left join (SELECT projcontid, projid, contactname, salutation FROM pfc.ut_projects_contacts where contacttype = 'PROJECT-PI') as pi on pj.projectid = pi.projid left join (SELECT contactid, metric FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'PHONE') phn on pi.projcontid = phn.contactid left join (SELECT contactid, metric FROM pfc.ut_projects_contacts_metrics where typeOfContMet = 'EMAIL') eml on pi.projcontid = eml.contactid where projectid = :projid"; 
  $projR = $conn->prepare($projSQL);
  $projR->execute(array(':projid' => $letterelements['projid']));
  $projDta = array(); 
  $projDta = $projR->fetch(PDO::FETCH_ASSOC); 
 
  $reviewerSQL = "SELECT replace(lettersignline,'\n','<br>') as lettersignline, pfrptitle FROM pfc.sys_pfcmember_pennkey where pfcpennkeyref = :pennkey";
  $reviewerR = $conn->prepare($reviewerSQL); 
  $reviewerR->execute(array(':pennkey' => $letterelements['reviewer'])); 
  $reviewer = array(); 
  $reviewer = $reviewerR->fetch(PDO::FETCH_ASSOC); 

  $header = <<<HEADER
    <table border=0 style="width: 7.75in;font-family: Tahoma, arial; font-size: 1vh;">
      <tr><td>{$pPic}</td></tr>
      <tr><td align=right style="font-size: .8vh; border-top: 1px solid #000;"><b>Hospital of the University of Pennlvania</b><br>Department of Pathology &amp; Laboratory Medicine<br>Pathology Feasibility Review Panel<br>3400 Spruce Street, 568 DULLES<br>Philadelphia, Pennsylvania 19104<br>(215) 662-4570</td></tr>
    </table>
HEADER;

  $today = date('D, F j, Y');
  $now = date('Y-m-d H:i');
  $projTitle = $projDta['projecttitle'];
  $projPFCAppNbr = $projDta['pfcapprovalnumber'];
  $projIRBNbr = $projDta['irbNbr'];
  $projIRBExp = $projDta['irbexpiration'];
  $pfcApprovalNbr = $projDta['pfcapprovalnumber'];
  $pfcApprovalExp = $projDta['pfcapprovalexpiration'];
  $piphone = $projDta['phonenbr'];
  $piemail = $projDta['piemail'];
  $refDsp = (trim($projPFCAppNbr) === "-") ? "" : trim($projPFCAppNbr);
  $refDsp .= ($refDsp === "") ? trim($projIRBNbr) : "/{$projIRBNbr}";
  $piname = explode(",",$projDta['contactname']);
  $saluation = $projDta['salutation'];
  $lcomments = (trim($letterelements['lettercomments']) === "") ? "" : "<tr><td style=\"padding: 1vh .5vw 0 .5vw; font-size: 1.2vh; text-align: justify; line-height: 1.8em; \">{$letterelements['lettercomments']}</td></tr>";
//  $lcomments = "<tr><td> " . json_encode($letterelements) . "</td></tr>";
  $reviewerSign = $reviewer['lettersignline'];
  $reviewerPFRPTitle = $reviewer['pfrptitle'];



  switch ($letterelements['statusmodifier']) { 
    case "Approved for OR Pickup":

//APPRVAL LETTER - OR PICKUP  
$bdy = <<<BODYTEXT
            
        <table border=0 style="width: 7.75in;font-family: Tahoma, arial; font-size: 1vh;">
          <tr><td style=" font-size: 1.2vh;">{$today}</td></tr>
          <tr><td style="padding-top: 2vh;  font-size: 1.2vh;font-weight: bold;">RE: {$projTitle} [{$refDsp}]</td></tr>
          <tr><td style="padding-top: 2vh; font-size: 1.2vh;">Dear {$saluation} {$piname[1]} {$piname[0]}:</td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">The Pathology Feasibility Review Panel has reviewed your application for an exclusion to <i>Hospital of the University of Pennsylvania (HUP) Policy on Surgical Specimens</i>.  We are pleased to notify you that your request to obtain specimens from the HUP operating rooms has been approved.  We have attached the approved "Specimen Retrieval Form" for your use when collecting samples from the operating rooms.  </td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">The new policy on release of these specimens for research indicates that the "Specimen Retrieval Form" must be completed and submitted to the OR staff prior to a sample being released to you.</td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">Please be advised that all individuals that enter the perioperative areas must be aware of all necessary standard operating procedures (SOPs) and be properly attired.  </td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">Please feel free to contact the Pathology Biospecimen Research Facility at (215) 662-4570, or email diane.mcgarvey@uphs.upenn.edu or valdivie@pennmedicine.upenn.edu, if you have any questions or concerns.</td></tr>
          {$lcomments}
          <tr><td style="padding-top: 1vh; font-size: 1.2vh;">Sincerely,</td></tr>
          <tr><td style="padding-top: 3.5vh; font-size: 1.2vh;">{$reviewerSign}<br><b>{$reviewerPFRPTitle}</b></td></tr>
          <tr><td style="font-size: .8vh; padding-top: 1vh;">{DIGITALLY SIGNED: {$now}}</td></tr>  
        </table>

BODYTEXT;

$pdfFileName = "APP_OR_{$letterelements['projid']}.pdf";
$docType = 'approvalletter';

require(   genAppFiles . "/appsupport/bcodeLib/qrlib.php" ) ;
$bcArr = array('PFRPApprovalNbr' => $pfcApprovalNbr, 'PFRPExpirationDate' => $pfcApprovalExp, 'IRB' => $projIRBNbr, 'IRBExp' => $projIRBExp);
$codeContents = json_encode($bcArr);

$pngAbsoluteFilePath = genAppFiles . "/tmp/" . date('YmdHis') . ".png";
if (!file_exists($pngAbsoluteFilePath)) {
  QRcode::png($codeContents, $pngAbsoluteFilePath, QR_ECLEVEL_L, 2);
}
$bcodedsp = base64file( $pngAbsoluteFilePath, "PFRPBcode", "image", true, " ");

$ORForm = <<<ORTRNFRM
<table border=0 style="width: 7.75in;height: 10in;font-family: Tahoma, arial; font-size: 1vh;">
<tr><td>  
       <table border=0 style="width: 7.75in;font-family: Tahoma, arial;">
           <tr><td>{$bcodedsp}</td><td style="width: 2.5in; font-size: 12pt; font-weight: bold; border: 1px solid #000;background: rgba(211,211,211,1);text-align: center; padding: 8px;">Place Patient Label/Sticker Here</td></tr>
       </table> 
</td></tr>
<tr><td style="padding-top: 30px;padding-bottom: 20px;text-align: center;"><span style="font-size: 16pt; font-weight: bold;">RESEARCH SPECIMEN RETRIEVAL FORM</span><br><span style="font-size: 14pt;font-style: oblique;">This form must be filled out by the <u>Sample Retriever</u> before any<br>specimens are released from the Operating Room.</span></td></tr>
<tr><td>
   <table border=0 style="width: 7.75in;font-family: Tahoma, arial; font-size: 11pt;">
     <tr><td style="font-weight: bold; width: 2.8in; padding-top: 20px;">Technician/Retriever&apos;s Name (Print): </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr>
     <tr><td style="font-weight: bold; width: 2.8in; padding-top: 20px;">Technician/Retriever&apos;s Phone: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr>
     <tr><td style="font-weight: bold; width: 2.8in; padding-top: 20px;">Technician/Retriever&apos;s Email: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr>
     <tr><td style="font-weight: bold; width: 2.8in; padding-top: 20px;">Technician/Retriever&apos;s Signature: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr>
   </table>
</td></tr>
<tr><td>
   <table border=0 style="width: 7.75in;">
      <tr><td style="font-size: 11pt; font-weight: bold; width: 1.8in;">Principal Investigator: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">{$saluation} {$piname[1]} {$piname[0]}</td>
          <td style="font-size: 11pt; font-weight: bold; width: 1in;">Phone/Email: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">{$piphone} / {$piemail}</td></tr>
   </table>
</td></tr>
<tr><td>
   <table border=0 style="width: 7.75in;">
      <tr><td style="font-size: 11pt; font-weight: bold; width: .9in;">Study Title: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">{$projTitle}</td></tr>
   </table>
</td></tr>

<tr><td>
   <table border=0 style="width: 7.75in;font-family: Tahoma, arial; font-size: 11pt;">
      <tr><td style="font-weight: bold;">IRB # : Expiration </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">{$projIRBNbr} : {$projIRBExp}</td><td style="font-weight: bold;">PFRP# : Expiration </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">{$pfcApprovalNbr} : {$pfcApprovalExp}</td></tr>
   </table>
</td></tr>
<tr><td>
   <table border=0>
      <tr><td style="width: .5in; height: .5in; border: 1px solid rgba(0,0,0,1);">&nbsp;</td><td style="font-size: 12pt;">Check this box if this is a Research-Only Procedure.  No other clinical specimens will result from this procedure.</td></tr>
   </table>
</td></tr>
<tr><td style="font-size: 11pt; font-weight: bold;">Anatomic Site of Specimen(s) Retrieved (i.e. Duodenum, Esophagus, Heart Explant, etc): </td></tr>
<tr><td style="height: .4in; border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr>
<tr><td style="height: .4in; border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr>
<tr><td><table style="width: 7.75in;"><tr><td style="height: .4in; width: 2.8in;font-size: 11pt; font-weight: bold;">Attending Surgeon/Proceduralist: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr></table></td></tr>
<tr><td>
<table style="width: 7.75in;">
<tr><td style="font-size: 11pt; font-weight: bold; width: 1in;">OR Room #: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td><td style="font-size: 11pt; font-weight: bold; width: 1in;">Pick-up Date: </td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td><td style="font-size: 11pt; font-weight: bold; width: 1in;">Pick-up Time:</td><td style="border-bottom: 1px solid rgba(0,0,0,1);">&nbsp;</td></tr>
</table>
</td></tr>
<tr><td style="width: 7.75in;background: rgba(211,211,211,1); font-size: 10pt; font-style: italics; text-align: justify; padding: 15px; box-sizing: border-box; border: 1px solid rgba(0,0,0,1); line-height: 1.8em;">
The original signed form MUST be sent to Surgical Pathology regardless of whether specimens are being placed in the OR Pathology Refrigerator.  Please place it in the bin labeled "Research Specimen Retrieval Forms" located in the OR Pathology Room.
</td></tr>
<tr><td>
<table style="border-top: 1px solid rgba(211,211,211,1); width: 7.75in;"><tr><td>{$pPic}</td><td style="font-size: 9pt; text-align: right;">
<b>Hospital of the University of Pennlvania</b><br>Department of Pathology &amp; Laboratory Medicine<br>Pathology Feasibility Review Panel<br>3400 Spruce Street, 568 DULLES<br>Philadelphia, Pennsylvania 19104<br>(215) 662-4570
</td></tr>
</table>
ORTRNFRM;

$ORDocFile = genAppFiles . "/tmp/ORDOC{$letterelements['projid']}.html";
$ORDhandle = fopen($ORDocFile, 'w');
$ORTdata = "<html><head></head><body>{$ORForm}</body></html>";
fwrite($ORDhandle, $ORTdata);
fclose;

$ORAppPDF = genAppFiles . "/publicobj/documents/pfrp/ORD{$letterelements['projid']}.pdf";

 //
$linuxCmd = "wkhtmltopdf  --load-error-handling ignore {$ORDocFile} {$ORAppPDF}";
$output = shell_exec($linuxCmd);
                   

$docInsSQL = "insert into pfc.ut_projects_documents (projectid, typeofdocument, directorydocumentname, uploadedon, uploadedby) values (:projectid, :typeofdocument, :directorydocumentname, now(), 'AUTO-SYSTEM')";
$docInsR = $conn->prepare($docInsSQL);
$docInsR->execute(array(':projectid' => $letterelements['projid'], ':typeofdocument' => "ORTranFrm", ':directorydocumentname' => "ORD{$letterelements['projid']}.pdf"));

      break;
    case "Approved Use PBRF":
//APROVAL LETTER - PBRF

$bdy = <<<BODYTEXT
        <table border=0 style="width: 7.75in;font-family: Tahoma, arial; font-size: 1vh;">
          <tr><td style=" font-size: 1.2vh;">{$today}</td></tr>
          <tr><td style="padding-top: 2vh;  font-size: 1.2vh;font-weight: bold;">RE: {$projTitle} [{$refDsp}]</td></tr>
          <tr><td style="padding-top: 2vh; font-size: 1.2vh;">Dear {$saluation} {$piname[1]} {$piname[0]}:</td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">The Pathology Feasibility Review Panel has reviewed your application to obtain research samples from surgical specimens.  </td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">Your study is not applicable for an exclusion to Hospital of the University of Pennsylvania (HUP) policy, as the specimens that you intend to collect for research purposes must be submitted to Surgical Pathology before any research material may be released.  We have approved your request to obtain specimens; however, these speicmens must be obtained from the Pathology Biospecimen Research Facility (PBRF). </td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">Please make arrangements with the PBRF by contaacting either Diane McGarvey (215) 662 4570 (diane.mcgarvey@uphs.upenn.edu) or Fred Valdisieso (215) 615-4744 (federico.valdisieso@uphs.upenn.edu) regarding the collection of these materials </td></tr>
          {$lcomments}
          <tr><td style="padding-top: 1vh; font-size: 1.2vh;">Sincerely,</td></tr>
          <tr><td style="padding-top: 3.5vh; font-size: 1.2vh;">{$reviewerSign}<br><b>{$reviewerPFRPTitle}</b></td></tr>
          <tr><td style="font-size: .8vh; padding-top: 1vh;">{DIGITALLY SIGNED: {$now}}</td></tr>  
        </table>

BODYTEXT;

$pdfFileName = "APP_PBRF_{$letterelements['projid']}.pdf";
$docType = 'approvalletter';

        break; 
    
    
    case "Denied":
//DENIED LETTER
$bdy = <<<BODYTEXT

        <table border=0 style="width: 7.75in;font-family: Tahoma, arial; font-size: 1vh;">
          <tr><td style=" font-size: 1.2vh;">{$today}</td></tr>
          <tr><td style="padding-top: 2vh;  font-size: 1.2vh;font-weight: bold;">RE: {$projTitle} [{$refDsp}]</td></tr>
          <tr><td style="padding-top: 2vh; font-size: 1.2vh;">Dear {$saluation} {$piname[1]} {$piname[0]}:</td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">The Pathology Feasibility Review Panel (PFRP) has reviewed your application for an exclusion to the Hospital of the University of Pennsylvania (HUP) policy on surgical specimens.  The specimens that you requested for research purposes must be submitted to pathology before any research material may be collected.  At this time, we cannot grant your study permission to obtain the requested biosamples.  </td></tr>
          {$lcomments}
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">We encourage you to modify your biosample requirements and resubmit an application to the panel.</td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh; text-align: justify; line-height: 1.8em;">Please feel free to contact the PFRP by contacting Diane McGarvey at (215) 662-4570 (diane.mcgarvey@uphs.upenn.edu) or Federico Valdivieso (215) 662-4744 (valdivie@pennmedicine.upenn.edu) if you have any further questions or concerns.  </td></tr>
          <tr><td style="padding-top: 1vh; font-size: 1.2vh;">Sincerely,</td></tr>
          <tr><td style="padding-top: 3.5vh; font-size: 1.2vh;">{$reviewerSign}<br><b>{$reviewerPFRPTitle}</b></td></tr>
          <tr><td style="font-size: .8vh; padding-top: 1vh;">{DIGITALLY SIGNED: {$now}}</td></tr>  
        </table>

BODYTEXT;
$pdfFileName = "APP_DENIAL_{$letterelements['projid']}.pdf";
$docType = 'deniedletter';

        break; 
    default:

  }

$htmldoc = $header . $bdy;
$docFile = genAppFiles . "/tmp/letter{$letterelements['projid']}.html";
$handle = fopen($docFile, 'w');
$data = "<html><head></head><body>{$htmldoc}</body></html>";
fwrite($handle, $data);
fclose;
$appPDF = genAppFiles . "/publicobj/documents/pfrp/{$pdfFileName}";
$linuxCmd = "wkhtmltopdf --load-error-handling ignore {$docFile} {$appPDF}";
$output = shell_exec($linuxCmd);
 
$docInsSQL = "insert into pfc.ut_projects_documents (projectid, typeofdocument, directorydocumentname, uploadedon, uploadedby) values (:projectid, :typeofdocument, :directorydocumentname, now(), 'AUTO-SYSTEM')";
$docInsR = $conn->prepare($docInsSQL);
$docInsR->execute(array(':projectid' => $letterelements['projid'], ':typeofdocument' => $docType, ':directorydocumentname' => $pdfFileName));

//EMAIL IT HERE
//$ORAppPDF = OR PICKUP FORM

$emailList = array($piemail, "zacheryv@mail.med.upenn.edu");
if (trim($ORAppPDF) !== "") { 
  //EMAIL ATTACHMENT
  $emlInsSQL = "insert into serverControls.emailthis (towhoaddressarray, sbjtline, msgbody, srverattachment, attachmentname, htmlind, wheninput, bywho) values (:towhoaddressarray, :sbjtline, :msgbody, :srverattachment, :attachmentname, 1, now(), 'PFRP-APPLICATION')";
  $emlR = $conn->prepare($emlInsSQL); 
  $projectdsp = substr(('000000' . $letterelements['projid']),-6);
  $emlR->execute(array(':towhoaddressarray' => json_encode($emailList), ':sbjtline' => "PFRP PROJECT DECISION ({$projectdsp})", ':msgbody' => $htmldoc, ':srverattachment' => $ORAppPDF, ':attachmentname' => 'OR PICKUP FORM'));
} else { 
//EMAIL ONLY
    
  $emlInsSQL = "insert into serverControls.emailthis (towhoaddressarray, sbjtline, msgbody, htmlind, wheninput, bywho) values (:towhoaddressarray, :sbjtline, :msgbody, 1, now(), 'PFRP-APPLICATION')";
  $emlR = $conn->prepare($emlInsSQL); 
  $projectdsp = substr(('000000' . $letterelements['projid']),-6);
  $emlR->execute(array(':towhoaddressarray' => json_encode($emailList), ':sbjtline' => "PFRP PROJECT DECISION ({$projectdsp})",':msgbody' => $htmldoc,));
}

return  "LETTER FOR PROJECT {$letterelements['projid']}";

  
}

function buildpfrpapplicationpdf($prjVal, $projectid, $pennkey) { 
  $at = genAppFiles;
  require(genAppFiles .  "/dataconn/sspdo.zck");
  $pPic = base64file( "{$at}/publicobj/graphics/psom_logo_blue.png", "PSOMLogo", "image", true, " style=\"width: 1.5in;\" ");
  $header = "<table border=0 style=\"width: 7.75in;\"><tr><td>{$pPic}</td><td style=\"text-align: center;font-size: 1.2vh;font-family: tahoma, arial; font-weight: bold;\">Pathology Feasibility Review Panel (PFRP)<br>Application Submission</td><td style=\"width: 1.5in;\">&nbsp;</td></tr></table>";
  $dspProjId = substr(('000000' . $projectid), -6);
  $usrSQL = "SELECT projectUserId FROM pfc.ut_projectUsers where pennkey = :pennkey";
  $usrR = $conn->prepare($usrSQL); 
  $usrR->execute(array(':pennkey' => $pennkey)); 
  $usr = $usrR->fetch(PDO::FETCH_ASSOC);
  $usrpfrpid = ("PFRP-" . substr(("0000" .  $usr['projectUserId']), -4));
  $subTime = date('m/d/Y H:i:s');
//{\"fldProjecTitle\":\"\",\"fldprojectirbnbr\":\"\",\"fldprojectirbexp\":\"\",\"fldprojectsubmitter\":\"\",\"fldprojectsubmiton\":\"\",\"frmcontactsubmitterfname\":\"\",\"frmcontactsubmitterlname\":\"\",\"frmcontactsubmitterphone\":\"\",\"frmcontactsubmitteremail\":\"\",\"frmcontactpifname\":\"\",\"frmcontactpilname\":\"\",\"fldPISalutations\":\"\",\"frmcontactpiphone\":\"\",\"frmcontactpiemail\":\"\",\"fldAnswerIdqstnOne\":\"NO\",\"fldAnswerIdqstnTwo\":\"NO\",\"fldAnswerIdqstnThree\":\"NO\",\"fldAnswerIdqstnFour\":\"NO\",\"docPROJECT-PROTOCOL\":\"SPECIFICATION(1).pdf\",\"btoPROJECT-PROTOCOL\":
   //IRB-APPROVAL
   //PROJECT-PROTOCOL
   //CONSENT-FORM
   //ADDITIONAL-DOCUMENT
//TODO:  Make Questions Dynamic!!
$htmlBody = <<<HTMLBODY
<table border=0 style="width: 720px"><tr><td align=right><table style="font-family: tahoma, arial; font-size: 1vh;"><tr><td><b>User</b>: </td><td>{$pennkey} / {$usrpfrpid}&nbsp;</td><td style="border-left: 1px solid #000054;">&nbsp;<b>PFRP Project #</b>: </td><td>{$dspProjId}</td></tr></table> </td></tr></table>
<table border=0 style="width: 720px">
<tr><td style="font-family: tahoma, arial; font-size: 1.3vh; font-weight: bold; border-bottom: 1px solid #000054;">Project</td></tr>
<tr><td style="font-family: tahoma, arial; font-size: 1vh;padding: 5px;">{$prjVal['fldProjecTitle']}</td></tr>
<tr><td><table><tr><td style="font-family: tahoma, arial; font-size: 1vh; font-weight: bold;">IRB #:&nbsp;</td><td style="font-family: tahoma, arial; font-size: 1vh;">{$prjVal['fldprojectirbnbr']}&nbsp;&nbsp;&nbsp;</td><td style="font-family: tahoma, arial; font-size: 1vh; font-weight: bold;">IRB Expiry:&nbsp;</td><td style="font-family: tahoma, arial; font-size: 1vh;">{$prjVal['fldprojectirbexp']}&nbsp;&nbsp;&nbsp;</td><td style="font-family: tahoma, arial; font-size: 1vh; font-weight: bold;">PFRP Submission Date:&nbsp;</td><td style="font-family: tahoma, arial; font-size: 1vh;">{$subTime}</td></tr></table> </td></tr>
<tr><td style="font-family: tahoma, arial; font-size: 1.3vh; font-weight: bold;padding-top: 8px;">Application Questions</td></tr>
<tr><td>
<table border=0 style="font-family: tahoma, arial; font-size: 1vh;">
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>1.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">Will you be using human tissue in research?</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['fldAnswerIdqstnOne']}</td></tr>
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>2.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">Could the human tissue obtained from clinical procedures be used for research without compromising diagnosis or patient care?</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['fldAnswerIdqstnTwo']}</td></tr>
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>3.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">Will this project require a source of human tissue from a research-only procedure, with no clinical specimens being submitted to pathology?</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['fldAnswerIdqstnThree']}</td></tr>
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>4.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">Will this project require the collection of human material(s) directly from the operating room?</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['fldAnswerIdqstnFour']}</td></tr>
</table>
</td></tr>
<tr><td style="font-family: tahoma, arial; font-size: 1.3vh; font-weight: bold;padding-top: 8px;">Uploaded Documents</td></tr>
<tr><td>
<table border=0 style="font-family: tahoma, arial; font-size: 1vh;">
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>1.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">PROJECT-PROTOCOL</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['docPROJECT-PROTOCOL']}</td></tr>
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>2.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">IRB-APPROVAL</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['docIRB-APPROVAL']}</td></tr>
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>3.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">CONSENT-FORM</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['docCONSENT-FORM']}</td></tr>
<tr><td valign=top style="border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;"><b>4.</b></td><td valign=top style="border-bottom: 1px solid #ccc;">ADDITIONAL-DOCUMENTATION</td><td valign=top style="border-bottom: 1px solid #ccc;">{$prjVal['docADDITIONAL-DOCUMENT']}</td></tr>
</table>
</td></tr>
<tr><td style="font-family: tahoma, arial; font-size: 1.3vh; font-weight: bold;padding-top: 8px;">Additional Comments</td></tr>
<tr><td style="font-family: tahoma, arial; font-size: 1vh;padding: 5px;">{$prjVal['frmprojectcomments']}&nbsp;</td></tr>
</table>
<table style="font-family: tahoma, arial; font-size: 1vh;">
<tr><td colspan=2 style="font-family: tahoma, arial; font-size: 1.3vh; font-weight: bold;padding-top: 8px;">Submitter</td></tr>
<tr><td><b>Name</b>:&nbsp;</td><td>{$prjVal['frmcontactsubmitterlname']}, {$prjVal['frmcontactsubmitterfname']} ({$pennkey})&nbsp;</td></tr>
<tr><td><b>Phone</b>:&nbsp;</td><td>{$prjVal['frmcontactsubmitterphone']}&nbsp;</td></tr> 
<tr><td><b>Email</b>:&nbsp;</td><td>{$prjVal['frmcontactsubmitteremail']}&nbsp;</td></tr> 
<tr><td colspan=2 style="font-family: tahoma, arial; font-size: 1.3vh; font-weight: bold;padding-top: 8px;">Principal Investigator</td></tr>
<tr><td><b>Name</b>:&nbsp;</td><td>{$prjVal['frmcontactpilname']}, {$prjVal['frmcontactpifname']}&nbsp;</td></tr>
<tr><td><b>Phone</b>:&nbsp;</td><td>{$prjVal['frmcontactpiphone']}&nbsp;</td></tr> 
<tr><td><b>Email</b>:&nbsp;</td><td>{$prjVal['frmcontactpiemail']}&nbsp;</td></tr> 
</table>
<p>
HTMLBODY;
$footer = <<<HTMLFOOT
<table style="width: 720px;font-family: tahoma, arial; font-size: .8vh;"><tr><td><center>Pathology Feasibility Review Panel (PFRP)<br>Hospital of the University of Pennsylvania<br>3400 Spruce Street, 6 FOUNDERS<br>Philadelphia, Pennsylvania 19104<br>(215) 662-4570</td></tr></table>
HTMLFOOT;
$htmldoc = <<<HTMLDOC
<table border=0 style="width: 720px"><tr><td style="border-bottom: 2px solid #000054;">{$header}</td></tr><tr><td>{$htmlBody}</td></tr><tr><td style="border-top: 1px solid #000054;text-align: center;">{$footer}</td></tr></table>
HTMLDOC;
$docFile = genAppFiles . "/tmp/projApp{$projectid}.html";
$handle = fopen($docFile, 'w');
$data = "<html><head></head><body>{$htmldoc}</body></html>";
fwrite($handle, $data);
fclose;
$appFileName = "projectApplication{$projectid}.pdf";
$appPDF = genAppFiles . "/publicobj/documents/pfrp/{$appFileName}";
$linuxCmd = "wkhtmltopdf --load-error-handling ignore {$docFile} {$appPDF}";
$output = shell_exec($linuxCmd);
$updSQL = "update pfc.ut_projects set projectPDF = :pdfFileName where projectid = :projectid";
$uR = $conn->prepare($updSQL); 
$uR->execute(array(':pdfFileName' => $appFileName, ':projectid' => $projectid));
return $htmldoc;
}