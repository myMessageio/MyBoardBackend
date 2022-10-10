<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/userguide3/general/urls.html
	 */
	public function __construct()
  {
    parent::__construct();
    $this->load->library('webcontroller');  
    $mysqli  = $this->webcontroller->returnMysqlConnect();
    $checkqry="select * from tbm_check where checkname='transactioncheck' and state=true";
    $checkRes=$mysqli->query( $checkqry);   
    try{
      if($checkRes->num_rows==0){
        $this->checkRegistedBlocks($mysqli);
      }      
    }catch(Exception $e){
      $updateqry="Update  tbm_check set state=false  where checkname='transactioncheck'";
      $updateRes=$mysqli->query( $updateqry); 
    }    
    $mysqli->close();	   
  }
  public function checkRegistedBlocks($mysqli){
    ///update check_ rows;
    $addwheresql=$this->webcontroller->getaddwheresql();
    $existcheckqry="SELECT * FROM tbm_checkstartblocks where status=true ".$addwheresql." order by rank ";  
		$existcheckRes =  $mysqli->query($existcheckqry);
   
    if($existcheckRes->num_rows>0){     
      $updateqry="Update  tbm_check set state=true  where checkname='transactioncheck'";
      $updateRes=$mysqli->query( $updateqry);  
    } else{
      return;
    }
    $exists = $existcheckRes->fetch_all(MYSQLI_ASSOC);   

    foreach($exists as $checkitem){          
      $lastblocknumber=0;
      $trycount=0;
     /////getblocknumber
      while($lastblocknumber<1&&$trycount<3){
        $res=$this->webcontroller->getLastBlockNumber($checkitem["network"]);      
    
        if($res["result"]=="success"){ 
          if($res['blocknumber']){
            if(gettype($res['blocknumber'])=="array"){
              $lastblocknumber=$res['blocknumber'][0];
            }else{
              $lastblocknumber=$res['blocknumber'];
            }
          }
        }     
        
        $trycount++;
      }
      if($lastblocknumber==0){
     
        break;
      }
      $startblock=(int)$checkitem["blocknumber"];
      ////check


      $trycount=0;
      $datacount=0;
      while($startblock<=$lastblocknumber&&$trycount<4){  
   
        $errors=[];     
        $endblock=$startblock+999;
        if($endblock>$lastblocknumber){
          $endblock=$lastblocknumber;
        }
        $errorstate=true;
        $req=$this->webcontroller->geteventDataFromChain($startblock,$endblock,$checkitem["network"],$checkitem["eventName"],$checkitem["contractaddress"]); 
        if($req["result"]=="error"){     
          array_push($errors,array("log"=>$req["log"],"eventName"=>$checkitem["eventName"]));                 
        }else{
          $successcount=0;
          foreach ($req['datas'] as $inf) {                     
            $storeRes=$this->webcontroller->eventValueStoreInDB($inf,$checkitem);
            if($storeRes["result"]=="error"){
              array_push($errors,array("log"=>$storeRes["log"],"eventName"=>$checkitem["eventName"]));             
            }else{
              $successcount++;   
              $datacount++;        

            } 
          }        
          if(count($req['datas'])==$successcount){
            $errorstate=false;
          }
        }   
        if($errorstate || count($errors)>0){        
          $trycount++;
        }else{
          $trycount=0;
          $startblock=$endblock+1;      
        }        
    
      }

      ////update db check data
 
      if($trycount<4){
        $updatery="UPDATE tbm_checkstartblocks SET  blocknumber='".$lastblocknumber."',status=false WHERE id=".$checkitem['id'];	
        if($datacount==0){
          $updatery="UPDATE tbm_checkstartblocks SET  blocknumber='".$lastblocknumber."',status=true WHERE id=".$checkitem['id'];		
        }
        $updateRes=$mysqli->query($updatery);        			
      }
    }

    $updateqry="Update  tbm_check set state=false  where checkname='transactioncheck'";
    $updateRes=$mysqli->query( $updateqry); 
  

  }
	
}
