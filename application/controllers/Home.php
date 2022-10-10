<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
Header('Access-Control-Allow-Headers: *');
header('Content-type: application/json');
require_once "vendor/autoload.php";
use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Contracts\Ethabi;
class Home extends CI_Controller {

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
  }
	public function checklastblockregist()
	{
		
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$rank=array(
			'newChannelCreated'=>1,'channelSettingUpdated'=>2,'channelJoined'=>2,'newPostCreated'=>3,'awardToThePost'=>4,'voteToPost'=>4,'newCommentCreated'=>5,'awardToTheComment'=>6,'voteToComment'=>6
		);
		$postparam= $this->input->post();	
		$checkqry="SELECT * FROM tbm_checkstartblocks where chainId= ".$postparam['chainId']." and contractaddress='".$postparam['contractaddress']."' and  network='".$postparam['network']."' and eventName ='".$postparam['eventName']."'";
		$checkres =  $mysqli->query($checkqry);	
		if($checkres){
			////if not exist insert new data rows
			if($checkres->num_rows==0){
				$insertqry=" INSERT INTO tbm_checkstartblocks (chainId,contractaddress,blocknumber,network,eventName,rank) VALUES ('".$postparam['chainId']."','".$postparam['contractaddress']."','".$postparam['blocknumber']."','".$postparam['network']."','".$postparam['eventName']."','".$rank[$postparam['eventName']]."')";		
				$insertRes=$mysqli->query($insertqry);
				if($insertRes)
			
						echo json_encode(array('result'=>'success','log'=>$insertqry));
					else
						echo json_encode(array('result'=>'error','log'=>$insertqry));

			}else{
				$items= $checkres->fetch_all(MYSQLI_ASSOC);
				
				$existItem=$items[0];
				////update
				if(!$existItem['status']&&$existItem['blocknumber']<$postparam['blocknumber']){
					$updatery="UPDATE tbm_checkstartblocks SET  blocknumber='".$postparam['blocknumber']."',status=true WHERE id=".$existItem['id'];				
					$updateRes=$mysqli->query($updatery);					
					if($updateRes){
						echo json_encode(array('result'=>'success'));
					}else{
						echo json_encode(array('result'=>'error'));
					}			

				}else{
					echo json_encode(array('result'=>'success'));
				}
				
			}
		}else{
			echo json_encode(array('result'=>'error'));
		}
		$mysqli->close();		

	}
	
	public function storePastBlockEvents(){
		$postparam= $this->input->post();
		// $this->someclass->some_method();
		$req=$this->webcontroller->geteventDataFromChain($postparam['startblock'],$postparam['startblock']+3999,$postparam['network'],$postparam['eventName'],$postparam['contractaddress']);; 
		// echo json_encode($req);
		// return;
		if($req["result"]=="error"){
			echo json_encode(array('result'=>'chainservererror','log'=>'chain connect error'));	
			return;
		}

		// if(count($req['datas'])>0){
		// 	$inf=$req['datas'][count($req['datas'])-1];
		// 	var_dump($inf);
		// }
		$errors=[];
		foreach ($req['datas'] as $inf) {
			$storeRes=$this->webcontroller->eventValueStoreInDB($inf,$postparam);
			if($storeRes["result"]=="error"){
				array_push($errors,array("log"=>$storeRes["log"],"eventName"=>$postparam["eventName"]));
			}
		}
		if(count($errors)>0){
			echo json_encode(array('result'=>'error','log'=>$errors,'eventName'=>$postparam['eventName']));	
		
		}else{
			echo json_encode(array('result'=>'success','eventName'=>$postparam['eventName']));
		}

		return;

		
	}
	function returnMysqlConnect(){
		$mysqli  =new  mysqli('localhost', 'root', '', 'test');
		//$mysqli  =new  mysqli('localhost', 'root', '', 'test');
		return $mysqli;
	}
	
	
}
