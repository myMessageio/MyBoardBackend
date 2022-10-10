<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
Header('Access-Control-Allow-Headers: *');
header('Content-type: application/json');

class Channel extends MY_Controller {

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

		/////for channel select for on create page
	public function userchannelsOnchain()
	{
		
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	

		// echo json_encode($postparam);
		// return;
		
		$mychannelqry="SELECT * FROM tbm_channels where chainId= ".$postparam['chainId']." and network='".$postparam['network']."' and  contractaddress='".$postparam['contractaddress']."' and creator='".$postparam['creator']."'";

		$mychennelRes =  $mysqli->query($mychannelqry);
		$mychennelRows = $mychennelRes->fetch_all(MYSQLI_ASSOC);

		$usechannelqry="SELECT * FROM tbm_channels A
						WHERE (channelId,contractAddress,network) in (SELECT channelId,contractAddress,network  FROM tbm_posts 
									WHERE network='".$postparam['network']."' and creator='".$postparam['creator']."' and  contractAddress='".$postparam['contractaddress']."'
									GROUP BY channelId,contractAddress,network)";
		$usechannelRes= $mysqli->query($usechannelqry);
		$usechannelRows = $usechannelRes->fetch_all(MYSQLI_ASSOC);	
		$popularchannelqry="SELECT * FROM tbm_channels 
						WHERE network='".$postparam['network']."' and  contractAddress='".$postparam['contractaddress']."' and postcount>0  order by postcount desc limit 5";
	
		$popularchannelRes= $mysqli->query($popularchannelqry);
		$popularchannelRows = $popularchannelRes->fetch_all(MYSQLI_ASSOC);	
		echo json_encode(array('result'=>true,'mychannels'=>$mychennelRows,'usedchannels'=>$usechannelRows,'popularchannels'=>$popularchannelRows));	
		$mysqli->close();	
	}
	/////for channel select for on navbar
	public function userchannelsOfAll()
	{
		
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();
		//////get selnetworksname and contracts
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$whereqry=" where  (network,contractaddress) in (" ;

		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$whereqry=$whereqry."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$whereqry=$whereqry.",";
			}
		}
		$whereqry=$whereqry.")";
		$whereqry1=$whereqry." and  creator='".$postparam['creator']."'";

		// echo json_encode($postparam);
		// return;
		
		$mychannelqry="SELECT * FROM tbm_channels ".$whereqry1;
		
		$mychennelRes =  $mysqli->query($mychannelqry);
		$mychennelRows = $mychennelRes->fetch_all(MYSQLI_ASSOC);

		$usechannelqry="SELECT * FROM tbm_channels 
						WHERE (channelId,contractaddress,network) in (SELECT channelId,contractaddress,network  FROM tbm_posts 
									".$whereqry1.")";
		$usechannelRes= $mysqli->query($usechannelqry);
	
		$usechannelRows = $usechannelRes->fetch_all(MYSQLI_ASSOC);	
		
		$whereqry2=$whereqry." and postcount>0 order by postcount desc limit 5 ";
		$popularchannelqry="SELECT * FROM tbm_channels".$whereqry2;		
		$popularchannelRes= $mysqli->query($popularchannelqry);

		$popularchannelRows = $popularchannelRes->fetch_all(MYSQLI_ASSOC);
		echo json_encode(array('result'=>true,'mychannels'=>$mychennelRows,'usedchannels'=>$usechannelRows,'popularchannels'=>$popularchannelRows));	
		$mysqli->close();		
	

	}
	public function popular(){


		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" WHERE  (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
			}
		}
		$wheresql1=$wheresql1.")";
		
		$qry="SELECT * FROM tbm_channels ".$wheresql1." limit 5 offset 0";

		$Res =  $mysqli->query($qry);
		if($Res){
			$channels = $Res->fetch_all(MYSQLI_ASSOC);
			echo json_encode(array('result'=>'success','channels'=>$channels));
		}else{
			echo json_encode(array('result'=>'error'));
		}
		$mysqli->close();		
	

	}

	public function topicfilter(){


		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	
		
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);
		$searchkey=$postparam['searchkey'];

		$wheresql1=" WHERE  (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
			}
		}
		$wheresql1=$wheresql1.")";
		if($wheresql1!=''){
			$wheresql1=$wheresql1." and (channelName like '%".$searchkey."%' or did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}

		if($postparam['topic']!="all"){
			$wheresql1=$wheresql1." and topics like '%".$postparam['topic']."%'";
		}
		
		$qry="SELECT * FROM tbm_channels ".$wheresql1." order by postcount,id limit 5 offset ".$postparam["offset"];

		$Res =  $mysqli->query($qry);
		if($Res){
			$channels = $Res->fetch_all(MYSQLI_ASSOC);
			echo json_encode(array('result'=>'success','channels'=>$channels));
		}else{
			echo json_encode(array('result'=>'error'));
		}
		$mysqli->close();		
	

	}
	public function filterbyuser(){


		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" WHERE  (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
			}
		}
		$wheresql1=$wheresql1.")";		
		$wheresql1=$wheresql1." and creator ='".$postparam['creator']."'";

		
		$qry="SELECT * FROM tbm_channels ".$wheresql1." order by postcount,id limit 5 offset ".$postparam["offset"];

		$Res =  $mysqli->query($qry);
		if($Res){
			$channels = $Res->fetch_all(MYSQLI_ASSOC);
			echo json_encode(array('result'=>'success','channels'=>$channels));
		}else{
			echo json_encode(array('result'=>'error'));
		}
		$mysqli->close();		
	

	}

	////////filter channels for creating post
	public function findforpost()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$whereqry=" where  (network,contractaddress) in (" ;

		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$whereqry=$whereqry."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$whereqry=$whereqry.",";
			}
		}
		$whereqry=$whereqry.")";

		$channelsearchqry="SELECT * FROM tbm_channels ".$whereqry." and channelName like '%".$postparam['keyword']."%'";

		$chennelsearchRes =  $mysqli->query($channelsearchqry);
		$searchRows = $chennelsearchRes->fetch_all(MYSQLI_ASSOC);		
		
		echo json_encode(array('result'=>true,'channels'=>$searchRows));	
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
		$channelry="SELECT * FROM tbm_channels where did= '".$postparam['did']."' ";

		$channelRes =  $mysqli->query($channelry);			
		if($channelRes->num_rows==1){
			$chennelRows = $channelRes->fetch_all(MYSQLI_ASSOC);
			$channel=$chennelRows[0];
			$memberqry="SELECT  creator FROM tbm_posts 
							WHERE channelId='".$channel['channelId']."' and network='".$channel['network']."' and contractaddress='".$channel['contractaddress']."'
							GROUP BY creator";
			$memberRes=$mysqli->query($memberqry);	
			$channel['postedmembers']=$memberRes->num_rows;
			echo json_encode(array('result'=>true,'channel'=>$channel));
		}else{
			echo json_encode(array('result'=>true,'channel'=>null));	
		}	
		
		
		$mysqli->close();			
	}

	public function joinedUsers(){
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	

		// echo json_encode($postparam);
		// return;
		
		$chljoinersql="SELECT * from tbm_channeljoins
					WHERE channelId='".$postparam['channelId']."'and contractaddress='".$postparam['contractaddress']."' and network='".$postparam['network']."' and joinstate=true limit 5 offset ".$postparam['offset'];
		$chljoinerRes=$mysqli->query($chljoinersql);
		if($chljoinerRes){
			$chljoinerRows = $chljoinerRes->fetch_all(MYSQLI_ASSOC);
			echo json_encode(array('result'=>'success','joinusers'=>$chljoinerRows));	

		}else{
			echo json_encode(array('result'=>'error','error'=>'error occurs in update channel data'));	
		}
	
		$mysqli->close();	

	}
	public function postedusers(){
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	

		// echo json_encode($postparam);
		// return;
		
		$chljoinersql="SELECT creator ,count(id) as postcount from tbm_posts
					WHERE channelId='".$postparam['channelId']."'and contractaddress='".$postparam['contractaddress']."' and network='".$postparam['network']."'   group by creator HAVING count(id)>0 LIMIT 5 OFFSET   ".$postparam['offset'];
		
		$chljoinerRes=$mysqli->query($chljoinersql);
		if($chljoinerRes){
			$chljoinerRows = $chljoinerRes->fetch_all(MYSQLI_ASSOC);
			echo json_encode(array('result'=>'success','creators'=>$chljoinerRows));	

		}else{
			echo json_encode(array('result'=>'error','error'=>'error occurs in update channel data'));	
		}
	
		$mysqli->close();	

	}

	public function awardlist(){
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();	

		// echo json_encode($postparam);
		// return;
		$wheresql=" WHERE contractaddress='".$postparam['contractaddress']."' and channelId='".$postparam['channelId']."' and network='".$postparam['network']."' ";
		$awardqry="SELECT A.*,B.did,B.title 
				  FROM (
					SELECT postId, channelId, network,contractAddress,postcreator,awarduser ,count(*) awards FROM tbm_userpostawards   
					 ".$wheresql." 
					GROUP BY postId, channelId, network,contractAddress,postcreator,awarduser) A
				  LEFT JOIN (SELECT * from tbm_posts  ".$wheresql." ) B 
				  on A.postId=B.postId and A.channelId=B.channelId and A.contractAddress=B.contractaddress and A.network=B.network
					limit 5 OFFSET ".$postparam['offset'];
		
	
		$awardRes=$mysqli->query($awardqry);
		if($awardRes){
			$awardRows = $awardRes->fetch_all(MYSQLI_ASSOC);
			echo json_encode(array('result'=>'success','awards'=>$awardRows));	

		}else{
			echo json_encode(array('result'=>'error','error'=>'error occurs in update channel data'));	
		}
	
		$mysqli->close();	

	}

	
}
