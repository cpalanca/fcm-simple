<?php
/**
 * PHP class to send simple messages using Firebase Cloud Messaging (FCM)
 * 
 * @license GNU GPL version 3.0
 * @author Khalid H. Alharisi <coder966@gmail.com>
 * @link https://github.com/coder966/FCMSimple
 */
class FCMSimple {

	private $serverKey = "";
	private $devices = array();
	private $response;


	/**
	 * Constructor
	 * @param [string] $serverKey The server key
	 */
	public function __construct($serverKey){
		$this->serverKey = $serverKey;
	}

	/**
	 * Set the devices to send to
	 * @param [array] $deviceTokens Array of device tokens to send to
	 */
	public function setDevices(array $deviceTokens){
		$this->devices = $deviceTokens;
	}

	/**
	 * Send message to the devices
	 * @param  [mixed]  $message The message to send
	 * @return [json]      	     The response from server
	 */
	public function send($message){
		// check required data
		if(strlen($this->serverKey) < 20){
			$this->error("Server Key not set");
		}
		if(!is_array($this->devices) || count($this->devices) == 0){
			$this->error("No devices set");
		}

		// prepare message
		$fields = array(
			"registration_ids"  => $this->devices,
			"data"              => array("message" => $message),
		);
		$headers = array(
			"Authorization: key=".$this->serverKey,
			"Content-Type: application/json"
		);

		// Open connection
		$ch = curl_init();

		// Setup connection
		curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

		// Avoid problem with https certificate
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// Execute post
		$this->response = curl_exec($ch);

		// Close connection
		curl_close($ch);

		return $this->response;
	}

	/**
	 * Stop executing the script and show an error message
	 * @param  [string] $message Error message
	 */
	private function error($message){
		echo "Android send notification failed with error:\t$message";
		exit(1);
	}

	/**
	 * To return a new array of updated registered device tokens that has no bad tokens
	 * This method can fix these errors:
	 * 		1- MissingRegistration : Empty registration id
	 * 		2- InvalidRegistration : Not even a a registration id, random string
	 * 		3- NotRegistered       : The device has uninstalled the app
	 * 		4- Update canonical IDs
	 *
	 * @return [array] New array of fixed ids
	 */
	public function getUpdatedTokens(){
		$response = json_decode($this->response, true)["results"];
		$devices = $this->devices;

		for($i=0; $i<count($devices); $i++){
			if(isset($response[$i]["error"]) and (
					($response[$i]["error"] == "MissingRegistration") or
					($response[$i]["error"] == "InvalidRegistration") or
					($response[$i]["error"] == "NotRegistered"))){
				unset($devices[$i]);
			}

			if(isset($response[$i]["registration_id"])){
				$devices[$i] = $response[$i]["registration_id"];
			}
		}

		// re-index the array and remove duplicated IDs
		$devices = array_unique(array_values($devices));

		return $devices;
	}
}
