<?
	require_once("motp_session.php");
	
	$det = get_details();
	
	if (!$det->id) exit;
	if ($det->status==3) exit;
	
	
	$phone = $_REQUEST["phne"];
	$motp_options = get_option( 'motp_options' );
	$motpno = $_REQUEST["motpno"];
	$publicKey = $motp_options["motp_pubKey"];
	$privateKey = $motp_options["motp_privateKey"];
	$ipbn= $motp_options["motp_ipblacklist"];
	$nobn= $motp_options["motp_blacklist"];
	$retr= $motp_options["motp_retrytm"];
	$matr= $motp_options["motp_maxretry"];
	

if(empty($phone)) {
	$ret[error] = 99;
	$ret[errormsg] = "Please provide a phone number";
	echo json_encode($ret);
//echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#FF0000' style='background:#FF0000'><tr><td align='center'><font color='#FFFFFF'>Invalid Phone Number</font></td></tr></table>";
exit;
}

// Banned Number - add error
if(!empty($nobn))	{
	
	$bnset_ar= preg_split("/[\n,\s,\,]/",$nobn);
	
	for($i=0;$i< count($bnset_ar); $i++)	{
		$tocheck = trim($bnset_ar[$i]);
		if ($tocheck) {
		if($tocheck==substr($phone,0,strlen($tocheck)))	{
			 $ret[error] = 98;
			 $ret[errormsg] = "Blacklisted Number. You cannot use this number for verification";
			 	echo json_encode($ret);
		//echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#FF0000' style='background:#FF0000'><tr><td align='center'><font color='#FFFFFF'>Blacklisted Number.</font></td></tr></table>";
		exit;
		}		
		}
	}
}
$clip = $_SERVER['REMOTE_ADDR'];
// Banned IP - add error
if(!empty($ipbn))	{
	
	$bnip_ar=preg_split("/[\n,\s,\,]/",$ipbn);
	for($i=0;$i< count($bnip_ar); $i++)	{
		$tocheck = trim($bnip_ar[$i]);
		if ($tocheck) {
		if($tocheck==substr($clip,0,strlen($tocheck)))	{
		 $ret[error] = 97;
			 $ret[errormsg] = "Blacklisted IP.";
			 	echo json_encode($ret);
		//echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#FF0000' style='background:#FF0000'><tr><td align='center'><font color='#FFFFFF'>Blacklisted IP.</font></td></tr></table>";
		exit;
		}		
		}
	}
}

//if(empty($_SESSION['mxtry']))	$_SESSION['mxtry']=0;
//else $_SESSION['mxtry']= $_SESSION['mxtry']+1;
$ftry = $det->ttime;


if ($ftry)	{
		$trnums = $det->trials;
		$stry=time();
		$gpp=$stry - $ftry;
		$wttm=$retr-$gpp;
		if($gpp <= $retr)	{
				$ret[error] = 96;
				$ret[errormsg] = "Please wait <span id='clock'>$wttm</span> seconds to conduct another verification";
			 	echo json_encode($ret);
		//echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#FF0000' style='background:#FF0000'><tr><td align='center'><font color='#FFFFFF'>Please wait $wttm seconds to conduct another verification</font></td></tr></table>";
		exit;
		}
		if($trnums > $matr)	{	
		//echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' bgcolor='#FF0000' style='background:#FF0000'><tr><td align='center'><font color='#FFFFFF'>Retries exceeded limit.</font></td></tr></table>";
				$ret[error] = 96;
				$ret[errormsg] = "Retries exceeded limit";
			 	echo json_encode($ret);
			exit;
		}
	
		
	} 
	
		set_ver_field("ttime", time());
		set_ver_field("trials", $det->trials+1);
		set_ver_field("phone", $phone);

# Call mOTP API
			
            $f = trim(do_request("https://engine.dial2verify.com/mOTP_Assets/PushOTP.php?pubKey=$publicKey&phone=$phone"));
            $pin=trim(do_request("https://engine.dial2verify.com/mOTP_Assets/GetOTP.php?pubKey=$publicKey&privateKey=$privateKey&sid=$f"));
	        set_ver_field("pin", $pin);
             
                   
    		if (substr($f,0,3) <> "sid") 
            {
			$ret[error] = $f;
			$errors = array ( 2=>"mOTP pubkey / private key is wrong", 3 => "provide a valid number", 4 => "API usage overflow", 9 => "something went wrong, contact support", 11 => "phone type restricted", 12 => "you cannot use this phone type for verification", 90=>"Communication error. Please retry a few minutes later");
			if ($errors[$f])
			$ret[errormsg] = $errors[$f];
			}
			else
            { 
			$ret[result] = $f;
			$ret[resultmsg] = "You would shortly recieve a missed call from 123456XXXX. Please enter the last 4 digits above.";
           
            set_ver_field("status", 2);
			}
	
	echo json_encode($ret);
	


function do_request($url) {
$iscurl  = function_exists('curl_version') ? 'Enabled' : 'Disabled';
$isfile = file_get_contents(__FILE__) ? 'Enabled' : 'Disabled';


if ($isfile=="Enabled") {
	return @file_get_contents($url);

}
else if ($iscurl=="Enabled") {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	return $result;

}
else return 90;


}


function connect()	{
	global $cnid;
	$cnid=mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);
	mysql_select_db(DB_NAME);
}

function firesql($query,$opt)	{
	global $cnid;
	$res=mysql_query($query,$cnid);
	if($opt=='get')	{
		if(mysql_num_rows($res))	{
			return mysql_fetch_object($res);
		}
		else {
			return '';
		}
	}
	elseif($opt=='num')	{
		return mysql_num_rows($res);
	}
	elseif($opt=='res')	{
		return $res;
	}
}
?>