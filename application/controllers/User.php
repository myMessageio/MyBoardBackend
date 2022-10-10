<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
Header('Access-Control-Allow-Headers: *');
header('Content-type: application/json');


class User extends CI_Controller {

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
	public function statistcs()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" WHERE (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
			}
		}
		$wheresql1=$wheresql1.") and ";
		///////posts
		$postcalqry="SELECT creator,count(*) as totalposts,
						SUM(CASE WHEN postType='paid' THEN 1 ELSE	0 END ) as paidposts,
						SUM(CASE WHEN postType='public' THEN 1 ELSE	0 END ) as publicposts,
						SUM(CASE WHEN postType='private' THEN 1 ELSE	0 END ) as privateposts,
						SUM(CASE WHEN network='bsc' THEN 1 ELSE	0 END ) as bscposts,
						SUM(CASE WHEN network='testbsc' THEN 1 ELSE	0 END ) as testbscposts,
						SUM(CASE WHEN network='polygon' THEN 1 ELSE	0 END ) as polygonposts,
						SUM(CASE WHEN network='mumbai' THEN 1 ELSE	0 END ) as mumbaiposts,
						SUM(CASE WHEN network='moonbeam' THEN 1 ELSE	0 END ) as moonbeamposts,
						SUM(CASE WHEN network='mbase' THEN 1 ELSE	0 END ) as mbaseposts
					FROM tbm_posts " ;
		
		$postcalqry=$postcalqry.$wheresql1." creator='".$postparam['account']."' GROUP BY creator";		
		$postres = $mysqli->query($postcalqry);
		
		$postcal=null;
		if($postres->num_rows==1){
			$postRows = $postres->fetch_all(MYSQLI_ASSOC);	
			$postcal=$postRows[0];
		}

		///////channels
		$channelcalqry="SELECT creator,count(*) as totalchannels,
							SUM(CASE WHEN network='bsc' THEN 1 ELSE	0 END ) as bscchannels,
							SUM(CASE WHEN network='testbsc' THEN 1 ELSE	0 END ) as testbscchannels,
							SUM(CASE WHEN network='polygon' THEN 1 ELSE	0 END ) as polygonchannels,
							SUM(CASE WHEN network='mumbai' THEN 1 ELSE	0 END ) as mumbaichannels,
							SUM(CASE WHEN network='moonbeam' THEN 1 ELSE	0 END ) as moonbeamchannels,
							SUM(CASE WHEN network='mbase' THEN 1 ELSE	0 END ) as mbasechannels
						FROM tbm_channels " ;		
		$channelcalqry=$channelcalqry.$wheresql1." creator='".$postparam['account']."' GROUP BY creator";		
		$channelres = $mysqli->query($channelcalqry);		
		$channelcal=null;
		if($channelres->num_rows==1){
			$channelRows = $channelres->fetch_all(MYSQLI_ASSOC);	
			$channelcal=$channelRows[0];
		}

		///////comments
		$commentcalqry="SELECT creator,count(*) as totalcomments
						FROM tbm_comments " ;
		$commentcalqry=$commentcalqry.$wheresql1." creator='".$postparam['account']."' GROUP BY creator";				
		$commentres = $mysqli->query($commentcalqry);		
		$commentcal=null;
		if($commentres->num_rows==1){
			$commentRows = $commentres->fetch_all(MYSQLI_ASSOC);
			$commentcal=$commentRows[0];
		}

		//////channels
		$postawardedcalqry="SELECT  postcreator,count(*) as totalpostawarded,
							SUM(CASE WHEN network='bsc' THEN 1 ELSE	0 END ) as bscawarded,
							SUM(CASE WHEN network='testbsc' THEN 1 ELSE	0 END ) as testbscawarded,
							SUM(CASE WHEN network='polygon' THEN 1 ELSE	0 END ) as polygonawarded,
							SUM(CASE WHEN network='mumbai' THEN 1 ELSE	0 END ) as mumbaiawarded,
							SUM(CASE WHEN network='moonbeam' THEN 1 ELSE	0 END ) as moonbeamawarded,
							SUM(CASE WHEN network='mbase' THEN 1 ELSE	0 END ) as mbaseawarded

						FROM tbm_userpostawards " ;		
		$postawardedcalqry=$postawardedcalqry.$wheresql1." postcreator='".$postparam['account']."' GROUP BY postcreator";		
		$postawardedres = $mysqli->query($postawardedcalqry);		
		$postawardedcal=null;
		if($postawardedres->num_rows==1){
			$postawardedRows = $postawardedres->fetch_all(MYSQLI_ASSOC);
			$postawardedcal=$postawardedRows[0];
		}


		//////channels
		$commentawardedcalqry="SELECT  commentcreator,count(*) as totalcommentawarded,

									SUM(CASE WHEN network='bsc' THEN 1 ELSE	0 END ) as bscawarded,
									SUM(CASE WHEN network='testbsc' THEN 1 ELSE	0 END ) as testbscawarded,
									SUM(CASE WHEN network='polygon' THEN 1 ELSE	0 END ) as polygonawarded,
									SUM(CASE WHEN network='mumbai' THEN 1 ELSE	0 END ) as mumbaiawarded,
									SUM(CASE WHEN network='moonbeam' THEN 1 ELSE	0 END ) as moonbeamawarded,
									SUM(CASE WHEN network='mbase' THEN 1 ELSE	0 END ) as mbaseawarded

								FROM tbm_usercommentawards " ;		
		$commentawardedcalqry=$commentawardedcalqry.$wheresql1." commentcreator='".$postparam['account']."' GROUP BY commentcreator";		
		$commentawardedres = $mysqli->query($commentawardedcalqry);	
		$commentawardedcal=null;
		if($commentawardedres->num_rows==1){
			$commentawardedRows = $commentawardedres->fetch_all(MYSQLI_ASSOC);
			$commentawardedcal=$commentawardedRows[0];
		}


		
		echo json_encode(array('result'=>'success','posts'=>$postcal,'channels'=>$channelcal,
									'comments'=>$commentcal,
									'postawarded'=>$postawardedcal,
									'commentawarded'=>$commentawardedcal));
			
		$mysqli->close();	

	}
	public function overviewposts()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" (A.creator='".$postparam['account']."' and (A.network,A.contractaddress) in (";
		$wheresql2=" WHERE (creator='".$postparam['account']."' and (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			$wheresql2=$wheresql2."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
				$wheresql2=$wheresql2.",";
			}
		}
		$wheresql1=$wheresql1."))  ";
		$wheresql2=$wheresql2."))  ";
		if($searchkey!=''){
			$wheresql1=$wheresql1." and (A.title like '%".$searchkey."%' or A.did='".$searchkey."' or A.transactionHash='".$searchkey."' or A.creator='".$searchkey."')";
			$wheresql2=$wheresql2." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}
		///////posts
		$qry="SELECT A.*,B.did as channeldid 
				FROM
					tbm_posts A
				LEFT JOIN tbm_channels B on A.network= B.network and A.contractaddress=B.contractaddress and A.channelId=B.channelId where (".$wheresql1.") or  (A.contractaddress,A.channelId,A.postId,A.network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_comments ".$wheresql2."
					GROUP BY contractaddress,channelId,postId,network) " ;
		
		if ($postparam['sortType']=="Hot") {
			$qry=$qry." ORDER BY A.rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$qry=$qry." ORDER BY A.timestamp DESC ";
		}else{
			$qry=$qry." ORDER BY A.votes DESC ";
		}
		$qry=$qry."  LIMIT 5 offset ". $postparam['offset'];
		
		$res = $mysqli->query($qry);
		
		
		if($res){
			$postRows = $res->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','posts'=>$postRows,'qry'=>$qry));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function userposts()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1="  (A.creator='".$postparam['account']."' and (A.network,A.contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
			}
		}
		$wheresql1=$wheresql1."))  ";
		if($searchkey!=''){		
				$wheresql1=$wheresql1." and (A.title like '%".$searchkey."%' or A.did='".$searchkey."' or A.transactionHash='".$searchkey."' or A.creator='".$searchkey."')";
		
		}

		///////posts
		$qry="SELECT A.*,B.did as channeldid 
				FROM
					tbm_posts A
				LEFT JOIN tbm_channels B on A.network= B.network and A.contractaddress=B.contractaddress and A.channelId=B.channelId WHERE ".$wheresql1  ;
		if ($postparam['sortType']=="Hot") {
			$qry=$qry." ORDER BY rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$qry=$qry." ORDER BY timestamp DESC ";
		}else{
			$qry=$qry." ORDER BY votes DESC ";
		}
		$qry=$qry."  LIMIT 5 offset ". $postparam['offset'];
		
		$res = $mysqli->query($qry);
		
		
		if($res){
			$postRows = $res->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','posts'=>$postRows));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function usercommentposts()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" WHERE (creator='".$postparam['account']."' and (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
			}
		}
		$wheresql1=$wheresql1.")   ";

		if($searchkey!=''){
			$wheresql1=$wheresql1." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}
		$wheresql1=$wheresql1." )  ";
		///////posts
		$qry="SELECT A.*,B.did as channeldid 
				FROM
					tbm_posts A
				LEFT JOIN tbm_channels B on A.network= B.network and A.contractaddress=B.contractaddress and A.channelId=B.channelId where  (A.contractaddress,A.channelId,postId,A.network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_comments ".$wheresql1."
					GROUP BY contractaddress,channelId,postId,network) " ;
		
		if ($postparam['sortType']=="Hot") {
			$qry=$qry." ORDER BY rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$qry=$qry." ORDER BY timestamp DESC ";
		}else{
			$qry=$qry." ORDER BY votes DESC ";
		}
		$qry=$qry."  LIMIT 5 offset ". $postparam['offset'];
		
		$res = $mysqli->query($qry);
		
		
		if($res){
			$postRows = $res->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','posts'=>$postRows,'qry'=>$qry));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function upvotedposts()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" WHERE (A.creator='".$postparam['account']."' and (A.network,A.contractaddress) in (";
		$wheresql2=" WHERE (creator='".$postparam['account']."' and (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			$wheresql2=$wheresql2."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
				$wheresql2=$wheresql2.",";
			}
		}

	
		$wheresql1=$wheresql1.")";
		$wheresql2=$wheresql2.") ";
		if($searchkey!=''){
			$wheresql1=$wheresql1." and (A.title like '%".$searchkey."%' or A.did='".$searchkey."' or A.transactionHash='".$searchkey."' or A.creator='".$searchkey."')";
			$wheresql2=$wheresql2." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}

		$wheresql1=$wheresql1." and A.votes>0)   ";
		$wheresql2=$wheresql2." and votes>0)   ";
		///////posts
		$qry="SELECT A.*,B.did as channeldid 
				FROM
					tbm_posts A
				LEFT JOIN tbm_channels B on A.network= B.network and A.contractaddress=B.contractaddress and A.channelId=B.channelId ".$wheresql1." or  (A.contractaddress,A.channelId,A.postId,A.network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_comments ".$wheresql2."
					GROUP BY contractaddress,channelId,postId,network) " ;
		if ($postparam['sortType']=="Hot") {
			$qry=$qry." ORDER BY rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$qry=$qry." ORDER BY timestamp DESC ";
		}else{
			$qry=$qry." ORDER BY votes DESC ";
		}
		$qry=$qry."  LIMIT 5 offset ". $postparam['offset'];
		
		$res = $mysqli->query($qry);
		
		
		if($res){
			$postRows = $res->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','posts'=>$postRows,'qry'=>$qry));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function downvotedposts()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" WHERE (A.creator='".$postparam['account']."' and (A.network,A.contractaddress) in (";
		$wheresql2=" WHERE (creator='".$postparam['account']."' and (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			$wheresql2=$wheresql2."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
				$wheresql2=$wheresql2.",";
			}
		}

	
		$wheresql1=$wheresql1.")";
		$wheresql2=$wheresql2.") ";
		if($searchkey!=''){
			$wheresql1=$wheresql1." and (A.title like '%".$searchkey."%' or A.did='".$searchkey."' or A.transactionHash='".$searchkey."' or A.creator='".$searchkey."')";
			$wheresql2=$wheresql2." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}

		$wheresql1=$wheresql1." and A.votes<0)   ";
		$wheresql2=$wheresql2." and votes<0)   ";
		///////posts
		$qry="SELECT A.*,B.did as channeldid 
				FROM
					tbm_posts A
				LEFT JOIN tbm_channels B on A.network= B.network and A.contractaddress=B.contractaddress and A.channelId=B.channelId ".$wheresql1." or  (A.contractaddress,A.channelId,A.postId,A.network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_comments ".$wheresql2."
					GROUP BY contractaddress,channelId,postId,network) " ;
		if ($postparam['sortType']=="Hot") {
			$qry=$qry." ORDER BY rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$qry=$qry." ORDER BY timestamp DESC ";
		}else{
			$qry=$qry." ORDER BY votes DESC ";
		}
		$qry=$qry."  LIMIT 5 offset ". $postparam['offset'];
		
		$res = $mysqli->query($qry);
		
		
		if($res){
			$postRows = $res->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','posts'=>$postRows,'qry'=>$qry));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	
		///////channels
				
	

	}
	public function awardedposts()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		$selnetworks=explode(",", $postparam['selnetworksstr']);
		$selcontractaddrs=explode(",", $postparam['selcontractaddrstr']);

		$wheresql1=" WHERE (A.creator='".$postparam['account']."' and (A.network,A.contractaddress) in (";
		$wheresql2=" WHERE (creator='".$postparam['account']."' and (network,contractaddress) in (";
		for ($i=0; $i < count($selnetworks) ; $i++) { 
			$wheresql1=$wheresql1."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			$wheresql2=$wheresql2."('".$selnetworks[$i]."','".$selcontractaddrs[$i]."')";
			if($i<count($selnetworks)-1){
				$wheresql1=$wheresql1.",";
				$wheresql2=$wheresql2.",";
			}
		}	

		$wheresql1=$wheresql1.")";
		$wheresql2=$wheresql2.") ";
		if($searchkey!=''){
			$wheresql1=$wheresql1." and (A.title like '%".$searchkey."%' or A.did='".$searchkey."' or A.transactionHash='".$searchkey."' or A.creator='".$searchkey."')";
			$wheresql2=$wheresql2." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}

		$wheresql1=$wheresql1." and A.awardcount<0)   ";
		$wheresql2=$wheresql2." and awardcount<0)   ";
		///////posts
		$qry="SELECT A.*,B.did as channeldid 
				FROM
					tbm_posts A
				LEFT JOIN tbm_channels B on A.network= B.network and A.contractaddress=B.contractaddress and A.channelId=B.channelId ".$wheresql1." or  (A.contractaddress,A.channelId,A.postId,A.network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_comments ".$wheresql2."
					GROUP BY contractaddress,channelId,postId,network) " ;

		if ($postparam['sortType']=="Hot") {
			$qry=$qry." ORDER BY rate DESC ";
		}elseif ($postparam['sortType']=="New") {
			$qry=$qry." ORDER BY timestamp DESC ";
		}else{
			$qry=$qry." ORDER BY votes DESC ";
		}
		$qry=$qry."  LIMIT 5 offset ". $postparam['offset'];
		
		$res = $mysqli->query($qry);
		
		
		if($res){
			$postRows = $res->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','posts'=>$postRows,'qry'=>$qry));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function postcomments()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		///////posts
		$postcommentsqry="SELECT * FROM tbm_comments where (contractaddress,channelId,postId,network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_posts where did='".$postparam['postdid']."')";
		$postcommentsqry=$postcommentsqry." and creator='".$postparam['account']."' ";
		if($searchkey!=''){
			$postcommentsqry=$postcommentsqry." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}
		
		$postcommentsRses = $mysqli->query($postcommentsqry);
		
		
		if($postcommentsRses){
			$commentsRows = $postcommentsRses->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','comments'=>$commentsRows));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function upvotedcomments()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		///////posts
		$postcommentsqry="SELECT * FROM tbm_comments where (contractaddress,channelId,postId,network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_posts where did='".$postparam['postdid']."') and votes>0";
		if($searchkey!=''){
			$postcommentsqry=$postcommentsqry." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}
		$postcommentsqry=$postcommentsqry." and creator='".$postparam['account']."' ";
		$postcommentsRses = $mysqli->query($postcommentsqry);
		
		
		if($postcommentsRses){
			$commentsRows = $postcommentsRses->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','comments'=>$commentsRows));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function downvotedcomments()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		///////posts
		$postcommentsqry="SELECT * FROM tbm_comments where (contractaddress,channelId,postId,network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_posts where did='".$postparam['postdid']."') and votes<0";
		if($searchkey!=''){
			$postcommentsqry=$postcommentsqry." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}
		$postcommentsqry=$postcommentsqry." and creator='".$postparam['account']."' ";
		$postcommentsRses = $mysqli->query($postcommentsqry);
		
		
		if($postcommentsRses){
			$commentsRows = $postcommentsRses->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','comments'=>$commentsRows));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}
	public function awardedcomments()
	{
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}

		$postparam= $this->input->post();
		$searchkey=$postparam['searchkey'];
		///////posts
		$postcommentsqry="SELECT * FROM tbm_comments where (contractaddress,channelId,postId,network) in 
					(SELECT  contractaddress,channelId,postId,network FROM tbm_posts where did='".$postparam['postdid']."') and awardcount>0";
		if($searchkey!=''){
			$postcommentsqry=$postcommentsqry." and (did='".$searchkey."' or transactionHash='".$searchkey."' or creator='".$searchkey."')";
		}
		$postcommentsqry=$postcommentsqry." and creator='".$postparam['account']."' ";
		$postcommentsRses = $mysqli->query($postcommentsqry);
		
		
		if($postcommentsRses){
			$commentsRows = $postcommentsRses->fetch_all(MYSQLI_ASSOC);	
			echo json_encode(array('result'=>'success','comments'=>$commentsRows));
		}else{
			echo  json_encode(array('result'=>'error'));
		}

		///////channels
				
		
			
		$mysqli->close();	

	}

	
}
