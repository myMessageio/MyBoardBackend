<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
Header('Access-Control-Allow-Headers: *');
header('Content-type: application/json');

class Post extends CI_Controller {

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
	 * 
	 */
	function __construct() {

		parent::__construct();
		$this->load->library('webcontroller');	
		
	}
	public function index()
	{
		  $arr = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);
		    header('Content-Type: application/json');
		echo json_encode( $arr);
		//$this->load->view('welcome');
	}

	public function list()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);
		$searchkey=$postparam['searchkey'];

		$selqry="SELECT A.*, B.did channeldid FROM tbm_posts A 
				LEFT JOIN tbm_channels B on A.channelId=B.channelId and A.contractaddress=B.contractaddress and A.network=B.network  
				WHERE (A.network,A.contractaddress) in (" ;

		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$selqry=$selqry."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$selqry=$selqry.",";
			}
		}
		$selqry=$selqry.")";
		if($searchkey!=''){
			$selqry=$selqry." and (A.title like '%".$searchkey."%' or A.did='".$searchkey."' or A.transactionHash='".$searchkey."' or A.creator='".$searchkey."')";
		}

		if ($postparam['sortType']=="Hot") {
			$selqry=$selqry." ORDER BY A.rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$selqry=$selqry." ORDER BY A.timestamp DESC ";
		}else{
			$selqry=$selqry." ORDER BY A.votes DESC ";
		}
		$selqry=$selqry." LIMIT 5 OFFSET ". $postparam['offset'];
		$selres = $mysqli->query($selqry);
		if($selres){
			$relRows = $selres->fetch_all(MYSQLI_ASSOC);	
		
			echo json_encode(array('result'=>'success','posts'=>$relRows,'sql'=>$selqry));
		}else{
			echo json_encode(array('result'=>'error','sql'=>$selqry));
		}

			
		$mysqli->close();	

	}
	public function channelposts()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		$selqry="SELECT A.*, B.did channeldid FROM tbm_posts A 
				LEFT JOIN tbm_channels B on A.channelId=B.channelId and A.contractaddress=B.contractaddress and A.network=B.network  WHERE A.network='".$postparam['channelnetwork']."' and A.contractaddress='".$postparam['channelcontract']."' and A.channelId='".$postparam['channelId']."' ";
		if($postparam['creatorjoinstate']=='joined'){
			$selqry=$selqry." and A.creator in  (SELECT joiner FROM tbm_channeljoins WHERE joinstate =TRUE and network='".$postparam['channelnetwork']."' and contractaddress='".$postparam['channelcontract']."' and channelId='".$postparam['channelId']."'  )";
		}else if($postparam['creatorjoinstate']=='unjoined'){
			$selqry=$selqry." and A.creator NOT in  (SELECT joiner FROM tbm_channeljoins WHERE joinstate =TRUE and network='".$postparam['channelnetwork']."' and contractaddress='".$postparam['channelcontract']."' and channelId='".$postparam['channelId']."'  )";
		}

		if($searchkey!=''){
			$selqry=$selqry." and (A.title like '%".$searchkey."%' or A.did='".$searchkey."' or A.transactionHash='".$searchkey."' or A.creator='".$searchkey."')";
		}

		if ($postparam['sortType']=="Hot") {
			$selqry=$selqry." ORDER BY A.rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$selqry=$selqry." ORDER BY A.timestamp DESC ";
		}else{
			$selqry=$selqry." ORDER BY A.votes DESC ";
		}
		$selqry=$selqry." LIMIT 5 OFFSET ". $postparam['offset'];
		
		$selres = $mysqli->query($selqry);
		$relRows = $selres->fetch_all(MYSQLI_ASSOC);	
		
		echo json_encode(array('result'=>'success','posts'=>$relRows));
			
		$mysqli->close();	

	}


	public function detail()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	
		// echo json_encode($postparam);	
		// return;
		
		$selqry="SELECT A.*, B.did channeldid FROM tbm_posts A 
					LEFT JOIN tbm_channels B on A.channelId=B.channelId and A.contractaddress=B.contractaddress and A.network=B.network  where A.did='".$postparam['did']."'";		
		$selres =  $mysqli->query($selqry);	
		if($selres->num_rows==1){
			$relRows = $selres->fetch_all(MYSQLI_ASSOC);
			echo json_encode(array('result'=>'success','post'=>$relRows[0]));
		

		}else{
			echo json_encode(array('result'=>'false','error'=>'this post is no or more exist'));
		}
		$mysqli->close();			
	}


	public function searchofall()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$selqry="SELECT A.*, B.did channeldid FROM tbm_posts A 
				LEFT JOIN tbm_channels B on A.channelId=B.channelId and A.contractaddress=B.contractaddress and A.network=B.network  WHERE (A.network,A.contractaddress) in (" ;

		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$selqry=$selqry."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$selqry=$selqry.",";
			}
		}
		$selqry=$selqry.")";

		if ($postparam['sortType']=="Hot") {
			$selqry=$selqry." ORDER BY A.rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$selqry=$selqry." ORDER BY A.timestamp DESC ";
		}else{
			$selqry=$selqry." ORDER BY A.votes DESC ";
		}
		$selqry=$selqry." LIMIT 5 OFFSET ". $postparam['offset'];
		
		$selres = $mysqli->query($selqry);
		$relRows = $selres->fetch_all(MYSQLI_ASSOC);	
		
		echo json_encode(array('result'=>'success','posts'=>$relRows));
			
		$mysqli->close();	
		
	}
	public function searchonchannel()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$selqry="SELECT A.*, B.did channeldid FROM tbm_posts A 
				LEFT JOIN tbm_channels B on A.channelId=B.channelId and A.contractaddress=B.contractaddress and A.network=B.network  WHERE A.network='".$postparam['channelnetwork']."' and A.contractaddress='".$postparam['channelcontract']."' and A.channelId='".$postparam['channelId']."' ";
		if($postparam['creatorjoinstate']=='joined'){
			$selqry=$selqry." and A.creator in  (SELECT joiner FROM tbm_channeljoins WHERE joinstate =TRUE and network='".$postparam['channelnetwork']."' and contractaddress='".$postparam['channelcontract']."' and channelId='".$postparam['channelId']."'  )";
		}else if($postparam['creatorjoinstate']=='unjoined'){
			$selqry=$selqry." and A.creator NOT in  (SELECT joiner FROM tbm_channeljoins WHERE joinstate =TRUE and network='".$postparam['channelnetwork']."' and contractaddress='".$postparam['channelcontract']."' and channelId='".$postparam['channelId']."'  )";
		}

		if ($postparam['sortType']=="Hot") {
			$selqry=$selqry." ORDER BY A.rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$selqry=$selqry." ORDER BY A.timestamp DESC ";
		}else{
			$selqry=$selqry." ORDER BY A.votes DESC ";
		}
		$selqry=$selqry." LIMIT 5 OFFSET ". $postparam['offset'];
		
		$selres = $mysqli->query($selqry);
		$relRows = $selres->fetch_all(MYSQLI_ASSOC);	
		
		echo json_encode(array('result'=>'success','posts'=>$relRows));
			
		$mysqli->close();	

	}



	
}
