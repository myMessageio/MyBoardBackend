<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
Header('Access-Control-Allow-Headers: *');
header('Content-type: application/json');

class Comment extends MY_Controller {

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

	public function getsubcomments(){
		$mysqli  = $this->webcontroller->returnMysqlConnect();
		if ($mysqli -> connect_errno) {
		  echo  json_encode(array('result'=>'false','error'=>"Failed to connect to MySQL: " . $mysqli -> connect_error));
		  exit();
		}
		$postparam= $this->input->post();
		
		$selqry="SELECT * FROM tbm_comments where postId=".$postparam['postId']." and channelId= ".$postparam['channelId']." and network='".$postparam['network']."' and  contractaddress='".$postparam['contractaddress']."' and parentId='".$postparam['parentId']."'";	

		
		$selres =  $mysqli->query($selqry);

	
		if($selres){
			$salRows= $selres->fetch_all(MYSQLI_ASSOC);	
		
   			echo json_encode(array('result'=>'success','comments'=>$salRows));

		}else{
			echo json_encode(array('result'=>'false','error'=>'this post is not exist','qury'=>$selqry));
		}
		$mysqli->close();

	}
	
}
