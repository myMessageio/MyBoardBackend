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
class Webcontroller  {

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

	public function geteventDataFromChain($startblock,$endblock,$network,$eventName,$contractaddress){
    

		$networksMetas= json_decode(file_get_contents( __DIR__."/networks.json"));
		$ContractMeta = json_decode(file_get_contents( __DIR__."/abi.json"));		
		
		
		$MetaData=null;
		foreach($networksMetas as $key=>$val){
			if($key==$network)
			$MetaData=$val;
		}	
		if(!$MetaData){
			return array("result"=>"error","log"=>"there is no network");
		}	
		if(strtolower($MetaData->contractaddress)!=strtolower($contractaddress)){
			return array("result"=>"error","log"=>"there is no contractaddress");
		}	


		$web3 = new Web3(new HttpProvider(new HttpRequestManager($MetaData->rpc_url, 5)));
		$contract = new Contract($MetaData->rpc_url, $ContractMeta);					
		$eth = $web3->eth;

		// $eventNames=['newChannelCreated','channelSettingUpdated','channelJoined','newPostCreated','awardToThePost','voteToPost','newCommentCreated','awardToTheComment','voteToComment'];
	
		$eventtopics = Utils::jsonMethodToString($contract->events[$eventName]);
		

		$topicsHash=	Utils::sha3($eventtopics);				
		$eventParameterNames = [];
		$eventParameterTypes = [];
		$eventIndexedParameterNames = [];
		$eventIndexedParameterTypes = [];


		foreach ($contract->events[$eventName]['inputs'] as $input) {
				if ($input['indexed']) {
						$eventIndexedParameterNames[] = $input['name'];
						$eventIndexedParameterTypes[] = $input['type'];
				} else {
						$eventParameterNames[] = $input['name'];
						$eventParameterTypes[] = $input['type'];
				}
		}
		
		$logparams=[				
			"fromBlock"=>"0x".dechex($startblock),	
			"toBlock"=>	"0x".dechex($endblock),
			"topics"=>[$topicsHash],
			"address"=>$MetaData->contractaddress];

		
		$eth->batch(true);	
		$eth->getLogs($logparams);	
		$eventLogData = [];		
		$error=null;
		$eth->provider->execute(function ($err, $datas) use (&$eventLogData, $eventParameterTypes, $eventParameterNames, $eventIndexedParameterTypes, $eventIndexedParameterNames,$contract,$error)  {			
			// $insertResult=$this->db->insert("tb_test",["mytext"=>"test2"]);
	
			if($err){	
				$error=$err;
				return;
			}
			
			$result=$datas[0];	
			
			foreach ($result as $object) {
				$decodedData = array_combine($eventParameterNames, $contract->ethabi->decodeParameters($eventParameterTypes, $object->data));
			
				for ($i = 0; $i < count($eventIndexedParameterNames); $i++) {
						$decodedData[$eventIndexedParameterNames[$i]] = $contract->ethabi->decodeParameters([$eventIndexedParameterTypes[$i]], $object->topics[$i + 1])[0];
				}

				$eventLogData[] = [
						'transactionHash' => $object->transactionHash,
						'blockHash' => $object->blockHash,
						'blockNumber' => hexdec($object->blockNumber),
						'data' => $decodedData
				];
			}
		});	
		if($error){
			return array("result"=>"error","log"=> "runtimeError");
		}else{
			return array("result"=>"success","datas"=> $eventLogData);
		}
		
	}
	public function getaddwheresql(){
		$networksMetas= json_decode(file_get_contents( __DIR__."/networks.json"));		
		$addsql=" and (network,contractaddress) in (";
		 $i=0;
		foreach($networksMetas as $key=>$MetaData){
			$addsql=$addsql."('".$MetaData->network."','".$MetaData->contractaddress."')";
			$i++;
			if($i<6){
				$addsql=$addsql.",";
			}	
		}	
		$addsql=$addsql.")";
		
		return $addsql;

	}

	public function getLastBlockNumber($network){
		
		$networksMetas= json_decode(file_get_contents( __DIR__."/networks.json"));
		$MetaData=null;
		foreach($networksMetas as $key=>$val){
			if($key==$network)
			$MetaData=$val;
		}	
		if(!$MetaData){
			return array("result"=>"error","log"=>"there is no network");
		}
		$lastblocknumber=0;	
		try{
			$web3 = new Web3(new HttpProvider(new HttpRequestManager($MetaData->rpc_url, 1)));
			$eth = $web3->eth;
		
			$eth->blockNumber(function ($err, $blockNumber) use(&$lastblocknumber) {
				if(!$err){
					$lastblocknumber=$blockNumber->value;
				}
			
			});
			if($lastblocknumber>0){
				return  array("result"=>"success","blocknumber"=>$lastblocknumber);
			}else{
				return  array("result"=>"error","log"=>"error in blockNumber running");
			}
		

		}catch(Exception $e){
			return array("result"=>"error","log"=>$e->getMessage());

		}
		
	}
	public function eventValueStoreInDB($inf,$postparam){
		foreach ($inf["data"] as $key => $data){
			if(gettype($data)=="object"){
				if(gettype($data->value)=="array"){
					$inf["data"][$key]=$data->value[0];
				}else{
					$inf["data"][$key]=$data->value;
				}
				
			}
			
		}

		if($postparam['eventName']=="newChannelCreated"){
				
					$params=array(
						"transactionHash"=>$inf["transactionHash"],
						"channelName"=> $inf["data"]["channelName"],
						"channelId"=>$inf["data"]["channelId"],
						"creator"=>$inf["data"]["creator"],
						"iconImgUrl"=>$inf["data"]["iconImgUrl"],
						"timestamp"=>$inf["data"]["timestamp"],
						"did"=>$postparam['network'].$inf["transactionHash"],
						"chainId"=>$postparam['chainId'],
						"network"=>$postparam['network'],
						"contractaddress"=>$postparam['contractaddress']			
					);
					$res=$this->channelcreate($params);
					if(	$res["result"]!="success"){
							return array("result"=>"error","log"=>$res["log"]);					
						
					}

			}else if($postparam['eventName']=="channelSettingUpdated"){					
					$params=array(
						"transactionHash"=>$inf["transactionHash"],					
						"channelId"=>$inf["data"]["channelId"],
						"creator"=>$inf["data"]["creator"],
						"iconImgUrl"=>$inf["data"]["iconImgUrl"],
						"topics"=> $inf["data"]["topics"],
						"timestamp"=>$inf["data"]["timestamp"],					
						"chainId"=>$postparam['chainId'],
						"network"=>$postparam['network'],
						"contractaddress"=>$postparam['contractaddress']			
					);
	
					$res=$this->channelupdate($params);
					if(	$res["result"]!="success"){
								return array("result"=>"error","log"=>$res["log"]);	
									
					}
				

			}else if($postparam['eventName']=="channelJoined"){
		
				$params=array(
					"transactionHash"=>$inf["transactionHash"],					
					"channelId"=>$inf["data"]["channelId"],				
					"joinedState"=>$inf["data"]["joinedState"],
					"joiner"=> $inf["data"]["joiner"],
					"joincount"=>$inf["data"]["joincount"],			
					"timestamp"=>$inf["data"]["timestamp"],		
					"chainId"=>$postparam['chainId'],
					"network"=>$postparam['network'],
					"contractaddress"=>$postparam['contractaddress']			
				);
			
				$res=$this->channeljoin($params);
			
				if(	$res["result"]!="success"){
							return array("result"=>"error","log"=>$res["log"]);							
				}
				
			

			}else if($postparam['eventName']=="newPostCreated"){
		
				$params=array(
					"transactionHash"=>$inf["transactionHash"],					
					"channelId"=>$inf["data"]["channelId"],
					"postId"=>$inf["data"]["postId"],
					"title"=>$inf["data"]["title"],
					"channelpostcount"=>$inf["data"]["channelpostcount"],
					"creator"=>$inf["data"]["creator"],
					"postType"=>$inf["data"]["postType"],				
					"timestamp"=>$inf["data"]["timestamp"],					
					"chainId"=>$postparam['chainId'],
					"network"=>$postparam['network'],
					"contractaddress"=>$postparam['contractaddress'],
					"did"=>$postparam['network'].$inf["transactionHash"],					
				);
			
				$res=$this->postcreate($params);
			
				if(	$res["result"]!="success"){
							return array("result"=>"error","log"=>$res["log"]);							
				}
				

			}else if($postparam['eventName']=="awardToThePost"){		
				$params=array(
					"transactionHash"=>$inf["transactionHash"],					
					"channelId"=>$inf["data"]["channelId"],
					"postId"=>$inf["data"]["postId"],
					"awardcount"=>$inf["data"]["awardcount"],
					"postcreator"=>$inf["data"]["postcreator"],
					"awarduser"=>$inf["data"]["awarduser"],				
					"timestamp"=>$inf["data"]["timestamp"],					
					"chainId"=>$postparam['chainId'],
					"network"=>$postparam['network'],
					"contractaddress"=>$postparam['contractaddress'],
					"did"=>$postparam['network'].$inf["transactionHash"],					
				);	
				$res=$this->postaward($params);
			
				if(	$res["result"]!="success"){
							return array("result"=>"error","log"=>$res["log"]);							
				}
				
	
			}else if($postparam['eventName']=="voteToPost"){
		
				$params=array(
					"transactionHash"=>$inf["transactionHash"],					
					"channelId"=>$inf["data"]["channelId"],
					"postId"=>$inf["data"]["postId"],
					"upvotes"=>$inf["data"]["upvotes"],
					"downvotes"=>$inf["data"]["downvotes"],
					"votecount"=>$inf["data"]["votecount"],
					"votedstate"=>$inf["data"]["votedstate"],
					"user"=>$inf["data"]["user"],				
					"timestamp"=>$inf["data"]["timestamp"],					
					"chainId"=>$postparam['chainId'],
					"network"=>$postparam['network'],
					"contractaddress"=>$postparam['contractaddress'],
					"did"=>$postparam['network'].$inf["transactionHash"],					
				);
				$res=$this->postvote($params);
			
				if(	$res["result"]!="success"){
						return array("result"=>"error","log"=>$res["log"]);
							
				}

			}else if($postparam['eventName']=="newCommentCreated"){
		
				$params=array(
					"transactionHash"=>$inf["transactionHash"],					
					"channelId"=>$inf["data"]["channelId"],
					"postId"=>$inf["data"]["postId"],
					"commentId"=>$inf["data"]["commentId"],
					"parentId"=>$inf["data"]["parentId"],
					"parentcommentcount"=>$inf["data"]["parentcommentcount"],
					"postcreator"=>$inf["data"]["postcreator"],
					"creator"=>$inf["data"]["creator"],						
					"timestamp"=>$inf["data"]["timestamp"],					
					"chainId"=>$postparam['chainId'],
					"network"=>$postparam['network'],
					"contractaddress"=>$postparam['contractaddress'],
					"did"=>$postparam['network'].$inf["transactionHash"],					
				);
				$res=$this->commentcreate($params);
			
				if(	$res["result"]!="success"){
							return array("result"=>"error","log"=>$res["log"]);							
				}

			}else if($postparam['eventName']=="awardToTheComment"){
		
				$params=array(
					"transactionHash"=>$inf["transactionHash"],					
					"channelId"=>$inf["data"]["channelId"],
					"postId"=>$inf["data"]["postId"],
					"commentId"=>$inf["data"]["commentId"],				
					"awardcount"=>$inf["data"]["awardcount"],
					"commentcreator"=>$inf["data"]["commentcreator"],
					"awarduser"=>$inf["data"]["awarduser"],						
					"timestamp"=>$inf["data"]["timestamp"],					
					"chainId"=>$postparam['chainId'],
					"network"=>$postparam['network'],
					"contractaddress"=>$postparam['contractaddress'],
					"did"=>$postparam['network'].$inf["transactionHash"],					
				);
				$res=$this->commentaward($params);
			
				if(	$res["result"]!="success"){
							return array("result"=>"error","log"=>$res["log"]);							
				}
				
	
			}else if($postparam['eventName']=="voteToComment"){
		
				$params=array(
					"transactionHash"=>$inf["transactionHash"],					
					"channelId"=>$inf["data"]["channelId"],
					"postId"=>$inf["data"]["postId"],
					"commentId"=>$inf["data"]["commentId"],
					"upvotes"=>$inf["data"]["upvotes"],
					"downvotes"=>$inf["data"]["downvotes"],
					"votecount"=>$inf["data"]["votecount"],
					"votedstate"=>$inf["data"]["votedstate"],				
					"user"=>$inf["data"]["user"],						
					"timestamp"=>$inf["data"]["timestamp"],					
					"chainId"=>$postparam['chainId'],
					"network"=>$postparam['network'],
					"contractaddress"=>$postparam['contractaddress'],
					"did"=>$postparam['network'].$inf["transactionHash"],					
				);
				$res=$this->commentvote($params);
			
				if(	$res["result"]!="success"){
							return array("result"=>"error","log"=>$res["log"]);							
				}

			}
			return array("result"=>"success");


	}

	public function channelcreate($params)
	{
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}				
		$checkqry="SELECT * FROM tbm_channels where channelId= '".$params["channelId"]."' and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
		$checkres =  $mysqli->query($checkqry);
		if($checkres->num_rows>1){
			$delqry="DELETE FROM tbm_channels where channelId= '".$params["channelId"]."' and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
			$delres=$mysqli->query($delqry);
		}
		if($checkres->num_rows==1){
			$checkRows=$checkres->fetch_all(MYSQLI_ASSOC);
			if($checkRows[0]['transactionHash']!==$params['transactionHash']){
				$delqry="DELETE FROM tbm_channels where channelId= '".$params["channelId"]."' and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
				$delres=$mysqli->query($delqry);
			}else{
				return array("result"=>"success","log"=>"this channel is exist");
			}		
		}
		$insertqry=" INSERT INTO tbm_channels (channelId,channelName,iconImgUrl,creator,transactionHash,did,network,createdTimestamp,updatedTimestamp,contractaddress,chainId) VALUES ('".$params['channelId']."','".$params['channelName']."','".$params['iconImgUrl']."','".$params['creator']."','".$params['transactionHash']."','".$params['did']."','".$params['network']."','".$params['timestamp']."','".$params['timestamp']."','".$params['contractaddress']."','".$params['chainId']."')";
		$insertRes=$mysqli->query($insertqry);
		
		if($insertRes){
			$insertedId=$mysqli->insert_id;
				return array("result"=>"success","log"=>"insert successfully");
		}else{
		
			return array("result"=>"error","log"=>"faild in DB insert");
		}
   		

		
		$mysqli->close();		

	}
	public function channelupdate($params)
	{
		
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}
		$checkqry="SELECT * FROM tbm_channels where channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
		$checkres =  $mysqli->query($checkqry);
		if($checkres->num_rows!=1){
			return array("result"=>"error","log"=>"this channel is not exist");
		}
		$updateqry="UPDATE tbm_channels SET topics='".$params['topics']."' , iconImgUrl='".$params['iconImgUrl']."' where channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
		$updateRes=$mysqli->query($updateqry);			
		if($updateRes){				
				return array("result"=>"success");
		}else{		
			return array("result"=>"error","log"=>"faild in DB insert");
		}
		

		
		$mysqli->close();		

	}

	public function channeljoin($params)
	{
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}	

		$checkqry="SELECT * FROM tbm_channels where channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
		$checkres =  $mysqli->query($checkqry);
		if($checkres->num_rows!=1){
			return array("result"=>"error","log"=>"this channel is not exist");
		}
		
		
		$chlupdateqry="UPDATE tbm_channels SET joincount='".$params['joincount']."' WHERE channelId='".$params['channelId']."' and contractaddress='".$params['contractaddress']."'  and network='".$params['network']."'";
		$chlupdateRes=$mysqli->query($chlupdateqry);
		if($chlupdateRes){
			$joinchlqry="SELECT * FROM tbm_channeljoins where channelId=".$params['channelId']." and joiner= '".$params['joiner']."' and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";
			$joinchkres=$mysqli->query($joinchlqry);
			if($joinchkres->num_rows>1){
				$delqry="Delete * FROM tbm_channeljoins where channelId=".$params['channelId']." and joiner= '".$params['joiner']."' and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";
				$mysqli->query($delqry);
			}

			if($joinchkres->num_rows==0||$joinchkres->num_rows>1){
				$insertqry=" INSERT INTO tbm_channeljoins (channelId,joiner,joinstate,network,chainId,timestamp,contractaddress) VALUES ('".$params['channelId']."','".$params['joiner']."',".($params['joinedState']?"true":"false").",'".$params['network']."','".$params['chainId']."','".$params['timestamp']."','".$params['contractaddress']."')";
				$insertRes=$mysqli->query($insertqry);		
				if($insertRes){
	
					return array("result"=>"success");						
				}else{			
					return array("result"=>"error","log"=>"faild in channeljoins DB insert");
				}

			}else if($joinchkres->num_rows==1){
				$updateqry="UPDATE tbm_channeljoins SET joinstate=".($params['joinedState']?"true":"false")." , timestamp='".$params['timestamp']."'  where  channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and joiner='".$params['joiner']."'";
				$updateRes=$mysqli->query($updateqry);		
				if($updateRes){
	
					return array("result"=>"success");						
				}else{			
				
					return array("result"=>"error","log"=>"faild in channeljoins  update");
				}

			}

		}else{
			return array("result"=>"error","log"=>"error occurs in channel  update");	
		}
	
		$mysqli->close();		
	
	}
	public function postcreate($params)
	{
		
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}
	
		// return $params);	
		$params['rate']=1*($params['timestamp']-1134028003)/45000;
		$checkqry="SELECT * FROM tbm_posts where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	

		
		$checkres =  $mysqli->query($checkqry);
		
		if($checkres->num_rows>1){
			$delqry="DELETE FROM tbm_posts where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
			$delres=$mysqli->query($delqry);
		}

		//check existed post 
		if($checkres->num_rows==1){
			$checkRows=$checkres->fetch_all(MYSQLI_ASSOC);
			if($checkRows[0]['transactionHash']!==$params['transactionHash']){
				$delqry="DELETE FROM tbm_posts where  postId='".$params['postId']."' and channelId= '".$params["channelId"]."' and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
				$delres=$mysqli->query($delqry);
			}else{
				return array("result"=>"success","log"=>"this post is exist");
			}		
		}
		//insert post data and update channel db
		$insertqry=" INSERT INTO tbm_posts (channelId,postId,creator,timestamp,transactionHash,network,chainId,contractaddress,did,title,postType,rate) VALUES ('".$params['channelId']."','".$params['postId']."','".$params['creator']."','".$params['timestamp']."','".$params['transactionHash']."','".$params['network']."','".$params['chainId']."','".$params['contractaddress']."','".$params['did']."','".$params['title']."','".$params['postType']."','".$params['rate']."')";
		$insertRes=$mysqli->query($insertqry);		
		if($insertRes){
			$chlupdateqry="UPDATE tbm_channels SET postcount='".$params['channelpostcount']."' WHERE channelId='".$params['channelId']."' and contractaddress='".$params['contractaddress']."'  and network='".$params['network']."'";
			$chlupdateRes=$mysqli->query($chlupdateqry);
			if($chlupdateRes){
				return array("result"=>"success","log"=>"this post is inserted successfully");
			}else{
				return array("result"=>"error","log"=>"Error occurs in channel update");
			}
		}else{				
			return array("result"=>"error","log"=>"faild in post DB insert");
		}
   		

		
		$mysqli->close();		

	}
	public function postvote($params)
	{
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}
	
		$votes=$params['upvotes']-$params['downvotes'];
		$params['votes']=$votes;
		$y=0;
		if($votes>0){
			$y=1;
		}else if ($votes<0) {
			$y=-1;
		}
		$z=abs($votes);
		if($z<1){
			$z=1;
		}
		$params['rate']=log10($z)+$y*($params['timestamp']-1134028003)/45000;
		// return $params);	
		// return;
		
		$checkqry="SELECT * FROM tbm_posts where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	

		
		$checkres =  $mysqli->query($checkqry);

	
		if($checkres->num_rows==1){
			$postupdateqry="UPDATE tbm_posts SET votes='".$params['votes']."' , votecount='".$params['votecount']."' , upvotes='".$params['upvotes']."' , downvotes='".$params['downvotes']."' , rate='".$params['rate']."'   where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
			$postupdateRes=$mysqli->query($postupdateqry);
			//////post update			
			if($postupdateRes){
				$votechkqry="SELECT * FROM tbm_userpostvotes where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and user='".$params['user']."'";
				$votechkres=$mysqli->query($votechkqry);
				if($votechkres->num_rows>1){
					$delkqry="DELETE FROM tbm_userpostvotes where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and user='".$params['user']."'";
					$mysqli->query($delkqry);
				}
				if($votechkres->num_rows==0||$votechkres->num_rows>1){
					$insertqry=" INSERT INTO tbm_userpostvotes (channelId,postId,user,votestate,network,chainId,contractaddress) VALUES ('".$params['channelId']."','".$params['postId']."','".$params['user']."','".$params['votedstate']."','".$params['network']."','".$params['chainId']."','".$params['contractaddress']."')";
					$insertRes=$mysqli->query($insertqry);		
					if($insertRes){
		
						return array("result"=>"success");						
					}else{			
						return array("result"=>"error","log"=>"faild in userpostvvote DB insert");
					}

				}else{
					$updateqry="UPDATE tbm_userpostvotes SET votestate='".$params['votedstate']."'  where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and user='".$params['user']."'";
					$updateRes=$mysqli->query($updateqry);		
					if($updateRes){
		
						return array("result"=>"success");						
					}else{			
						return array("result"=>"error",'sql'=>$updateRes,"log"=>"faild in userpostvote update");
					}

				}
				
			}else{
				return array("result"=>"error","log"=>"error in post update");
			}
   		

		}else{
			return array("result"=>"error","log"=>"this post is exist");
		}
		$mysqli->close();			
	}
	public function postaward($params)
	{
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}
	
		// return $params);	
		// return;
		
		$postcheckqry="SELECT * FROM tbm_posts where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
		$postcheckres =  $mysqli->query($postcheckqry);	
		if($postcheckres->num_rows==1){
			$postupdateqry="UPDATE tbm_posts SET awardcount='".$params['awardcount']."'    where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
			$postupdateRes=$mysqli->query($postupdateqry);			
			if($postupdateRes){	
				///checkexisted
				$awardcheckqry="SELECT * FROM tbm_userpostawards where channelId=".$params['channelId']." and postId= ".$params['postId']." and postcreator= '".$params['postcreator']."' and awarduser= '".$params['awarduser']."' and network='".$params['network']."' and transactionHash='".$params['transactionHash']."' and  contractaddress='".$params['contractaddress']."'";
				$awardcheckRes=$mysqli->query($awardcheckqry);
			
				if($awardcheckRes->num_rows>1){
					$delqry="DELETE  FROM tbm_userpostawards where channelId=".$params['channelId']." and postId= ".$params['postId']." and postcreator= '".$params['postcreator']."' and awarduser= '".$params['awarduser']."' and network='".$params['network']."' and transactionHash='".$params['transactionHash']."' and  contractaddress='".$params['contractaddress']."'";
					$mysqli->query($delqry);
				}else if( $awardcheckRes->num_rows==1){
					return array("result"=>"success","log"=>"this userpostaward data is exist");
				}


				
				$insertqry=" INSERT INTO tbm_userpostawards (channelId,postId,postcreator,awarduser,timestamp,network,chainId,contractaddress,transactionHash) VALUES ('".$params['channelId']."','".$params['postId']."','".$params['postcreator']."','".$params['awarduser']."','".$params['timestamp']."','".$params['network']."','".$params['chainId']."','".$params['contractaddress']."','".$params['transactionHash']."')";
				$insertRes=$mysqli->query($insertqry);		
				if($insertRes){
	
					return array("result"=>"success");						
				}else{			
					return array("result"=>"error","log"=>"faild in userpostawarde DB insert");
				}

				
			}else{
				return array("result"=>"error","log"=>"error in post data update");
			}
   		

		}else{
			return array("result"=>"error","log"=>"this post is exist");
		}
		$mysqli->close();			
	}

	public function commentcreate($params)
	{
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}
	
		$commentcheckqry="SELECT * FROM tbm_comments where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."'";	
		$commentcheckres =  $mysqli->query($commentcheckqry);
		if($commentcheckres->num_rows>1){
			$delqry="DELETE FROM tbm_comments where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."'";
			$delres=$mysqli->query($delqry);
		}

		//check existed post 
		if($commentcheckres->num_rows==1){
			$checkRows=$commentcheckres->fetch_all(MYSQLI_ASSOC);
			if($checkRows[0]['transactionHash']!==$params['transactionHash']){
				$delqry="DELETE FROM tbm_comments where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."'";
				$delres=$mysqli->query($delqry);
			}else{
				return array("result"=>"success","log"=>"this comment is exist");
			}		
		}
		


		$postcheckqry="SELECT * FROM tbm_posts where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
		$postcheckres =  $mysqli->query($postcheckqry);
		if($postcheckres->num_rows==1){
			$updateRes=null;
			if($params['parentId']=='0'){
				$postupdateqry="UPDATE tbm_posts SET commentcount='".$params['parentcommentcount']."' where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' ";	
				$updateRes=$mysqli->query($postupdateqry);	

			}else{
				$commentupdateqry="UPDATE tbm_comments SET subcommentcount='".$params['parentcommentcount']."' where postId=".$params['postId']." and channelId= ".$params['channelId']." and commentId= ".$params['commentId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."'";	
				$updateRes=$mysqli->query($commentupdateqry);	
			}
			
			if($updateRes){			
				$insertqry=" INSERT INTO tbm_comments (channelId,postId,commentId,creator,parentId,postcreator,timestamp,transactionHash,network,contractaddress,did) VALUES ('".$params['channelId']."','".$params['postId']."','".$params['commentId']."','".$params['creator']."','".$params['parentId']."','".$params['postcreator']."','".$params['timestamp']."','".$params['transactionHash']."','".$params['network']."','".$params['contractaddress']."','".$params['did']."')";
					$insertRes=$mysqli->query($insertqry);		
				if($insertRes){
	
					return array("result"=>"success");						
				}else{			
					return array("result"=>"error","log"=>"faild in comment insert");
				}
				
			}else{
				return array("result"=>"error","log"=>"error in post updating");
			}
			

		}else{
			return array("result"=>"error","log"=>"this post is not exist");
		}
		
		$mysqli->close();			
	}


	public function commentaward($params)
	{
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}
	

		// return $params);
		// return;
		
		$checkqry="SELECT * FROM tbm_comments where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."'";	
		$checkres =  $mysqli->query($checkqry);
		if($checkres->num_rows==1){
			$commentupdateqry="UPDATE tbm_comments SET awardcount='".$params['awardcount']."' where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."'";	
			$updateRes=$mysqli->query($commentupdateqry);	
			
			if($updateRes){
				$awardcheckqry="Select * from tbm_usercommentawards where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."' and commentcreator='".$params['commentcreator']."' and awarduser='".$params['awarduser']."' and transactionHash='".$params['transactionHash']."'";
				$awardcheckres=$mysqli->query($awardcheckqry);
				if($awardcheckres->num_rows>1){
					$deleteqry="DELETE FROM tbm_usercommentawards WHERE postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."' and commentcreator='".$params['commentcreator']."' and awarduser='".$params['awarduser']."' and transactionHash='".$params['transactionHash']."'";
					$mysqli->query($deleteqry);
				}
				if($awardcheckres->num_rows==1){
					return array("result"=>"success","log"=>"this usercommentaward data is exist");
				}

					
				$insertqry="INSERT INTO tbm_usercommentawards (channelId,postId,commentId,commentcreator,awarduser,timestamp,network,chainId,contractaddress,transactionHash) VALUES ('".$params['channelId']."','".$params['postId']."','".$params['commentId']."','".$params['commentcreator']."','".$params['awarduser']."','".$params['timestamp']."','".$params['network']."','".$params['chainId']."','".$params['contractaddress']."','".$params['transactionHash']."')";
				$insertRes=$mysqli->query($insertqry);				
				if($insertRes){
	
					return array("result"=>"success");						
				}else{			
					return array("result"=>"error","log"=>"faild in DB insert",'qury'=>$insertqry);
				}
				
			}else{
				return array("result"=>"error","log"=>"error in post updating");
			}
   		

		}else{
			return array("result"=>"error","log"=>"this comment is not exist",'qury'=>$checkqry);
		}
		$mysqli->close();			
	}

	public function commentvote($params)
	{
		$mysqli  = $this->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  return array("result"=>"error","log"=>"Failed to connect to MySQL: " . $mysqli -> connect_error);
		  exit();
		}	
	
		$votes=$params['upvotes']-$params['downvotes'];

		// return $params);
		// return;
		$params['votes']=$votes;

		
		$checkqry="SELECT * FROM tbm_comments where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."'";	
		$checkres =  $mysqli->query($checkqry);
		if($checkres->num_rows==1){
			$commentupdateqry="UPDATE tbm_comments SET  votes='".$params['votes']."' , votecount='".$params['votecount']."' , upvotes='".$params['upvotes']."' , downvotes='".$params['downvotes']."' where postId=".$params['postId']." and channelId= ".$params['channelId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and commentId='".$params['commentId']."'";	
			$commentUpdateRes=$mysqli->query($commentupdateqry);			
			if($commentUpdateRes){
				$votechkqry="SELECT * FROM tbm_usercommentvotes where postId=".$params['postId']." and channelId= ".$params['channelId']." and commentId= ".$params['commentId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and user='".$params['user']."'";
				$votechkres=$mysqli->query($votechkqry);
				if($votechkres->num_rows>1){
					$delqry="DELETE FROM tbm_usercommentvotes where postId=".$params['postId']." and channelId= ".$params['channelId']." and commentId= ".$params['commentId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and user='".$params['user']."'";
					$mysqli->query($delqry);
				}

				if($votechkres->num_rows==0||$votechkres->num_rows>1){
					$insertqry=" INSERT INTO tbm_usercommentvotes (channelId,postId,commentId,user,votestate,network,contractaddress,chainId) VALUES ('".$params['channelId']."','".$params['postId']."','".$params['commentId']."','".$params['user']."','".$params['votestate']."','".$params['network']."','".$params['contractaddress']."','".$params['chainId']."')";
					$insertRes=$mysqli->query($insertqry);				
					if($insertRes){
		
						return array("result"=>"success");						
					}else{			
						return array("result"=>"error","log"=>"faild in DB insert");
					}
				}else{
					$updateqry="UPDATE tbm_usercommentvotes SET votestate='".$params['votedstate']."'  where postId=".$params['postId']." and channelId= ".$params['channelId']." and commentId= ".$params['commentId']." and network='".$params['network']."' and  contractaddress='".$params['contractaddress']."' and user='".$params['user']."'";
					$updateRes=$mysqli->query($updateqry);		
					if($updateRes){
		
						return array("result"=>"success");						
					}else{			
						return array("result"=>"error",'sql'=>$updateRes,"log"=>"faild in user post table update");
					}

				}
				
			}else{
				return array("result"=>"error","log"=>"error in post updating");
			}
   		

		}else{
			return array("result"=>"error","log"=>"this comment is not exist");
		}
		$mysqli->close();			
	}


	function returnMysqlConnect(){
		//$mysqli  =new  mysqli('globaldefi.fatcowmysql.com', 'kernan', 'Ffang1234!@#$', 'dbmymessage');
		$mysqli  =new  mysqli('localhost', 'root', '', 'test');
		return $mysqli;
	}
	
	
}
