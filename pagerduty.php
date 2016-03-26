<?php


class PagerDutyWhosOncall {

	public $options;
    public function __construct( $options=array() ){
        $default_options = array(
            'baseurl' => NULL, 
            'apikey' => NULL,
            'debug' => false,
            'timezone' => 'UTC', // This is the timezone that will be passed to pagerduty. https://developer.pagerduty.com/documentation/rest/types#timezone
        );
        
        $this->options = array_merge($default_options, $options);

	}
	
	public function apiCall($path, $parameters = null) {

		$context = stream_context_create(array(
			'http' => array(
				'header' => "Content-type: application/json",
				'header'  => "Authorization: Token token=" . $this->options['apikey'])
			)
		);

		$params = null;
		if (isset($parameters)){
			foreach ($parameters as $key => $value) {
				if (isset($params)) {
					$params .= '&';
				} else {
					$params = '?';
				}
				$params .= sprintf('%s=%s', $key, $value);
			}
		}
	
		$respose = file_get_contents($this->options['baseurl'] . $path . $params, false, $context);
		if($this->options['debug'] ==true){
			echo $respose;
		}
	
	
		return  $respose;
	}




	public function whoIsOnCallOverride($scheduleid, $time = null) {
		//this function checks to see if any override entries exist. Used by the whoIsOncall function
   
	
		$since = date('c', isset($time) ? $time : time() );
		$until = date('c', isset($time) ? $time+60 : time()+60 ); // adds 60 seconds to since. sometimes pagerduty does not give any entries otherwise
		
		$parameters = array(
			'since' => $since,
			'until' => $until,
			'overflow' => 'true',
			'time_zone'=> $this->options['timezone'],
		);
	
		$json = $this->apiCall(sprintf('/schedules/%s/overrides', $scheduleid), $parameters) ;
		if($this->options['debug'] ==true){
			echo 'Json supplied by Pagerduty'.PHP_EOL;
			print_r($json);
			echo PHP_EOL;
		}
		if (false === ($scheddata = json_decode($json))) {
			if($this->options['debug'] ==true){
				echo "There was an error with the data from PagerDuty, please try again later.\n".PHP_EOL;
			}
			return false;
		}
	
		if (isset($scheddata->overrides['0'])){
			if ($scheddata->overrides['0']->user->name == "") {
				if($this->options['debug'] ==true){
					echo "No data from Pagerduty for that date, sorry.\n".PHP_EOL;
				}
				return false;
			}
		} else { return false;}
	
	
		$phonenumber = $this->getPhoneNumberForOncall( $scheddata->overrides['0']->user->id );

		$oncalldetails = array();
		$oncalldetails['id'] = $scheddata->overrides['0']->user->id;
		$oncalldetails['person'] = $scheddata->overrides['0']->user->name;
		$oncalldetails['email'] = $scheddata->overrides['0']->user->email;
		$oncalldetails['phone'] = $phonenumber;
		$oncalldetails['type'] = 'override oncall rota'; // Declairing the entry type as an override. 
		$oncalldetails['start'] = strtotime($scheddata->overrides['0']->start);
		$oncalldetails['end'] = strtotime($scheddata->overrides['0']->end);

		return $oncalldetails;

	}

	public function whoIsOnCall($scheduleid, $timestamp = null) {

		$since = date('c', isset($time) ? $timestamp : time());
		$until = date('c', isset($time) ? $timetsamp+60 : time()+60);
		$parameters = array(
			'since' => $since,
			'until' => $until,
			'overflow' => 'true',
			'time_zone'=> $this->options['timezone'],
		);
	
		$json = $this->apiCall(sprintf('/schedules/%s/entries', $scheduleid), $parameters);
		if($this->options['debug'] ==true){
			echo 'Json supplied by Pagerduty'.PHP_EOL;
			print_r($json);
			echo PHP_EOL;
		}
		if (false === ($scheddata = json_decode($json))) {
			if($this->options['debug'] ==true){
				echo "There was an error with the data from PagerDuty, please try again later.\n".PHP_EOL;
			}
			return false;
		}

		if ($scheddata->entries['0']->user->name == "") {
			if($this->options['debug'] ==true){
				echo "No data from Pagerduty for that date, sorry.\n".PHP_EOL;
			}
			return false;
		}
	
		//check to see if there are any overrides!
		$overrideoncall= $this->whoIsOnCallOverride($scheduleid);
		if (is_array($overrideoncall)){
			return $overrideoncall;
		}
		$phonenumber = $this->getPhoneNumberForOncall( $scheddata->entries['0']->user->id );

		$oncalldetails = array();
		$oncalldetails['id'] = $scheddata->entries['0']->user->id;
		$oncalldetails['person'] = $scheddata->entries['0']->user->name;
		$oncalldetails['email'] = $scheddata->entries['0']->user->email;
		$oncalldetails['phone'] = $phonenumber;
		$oncalldetails['type'] = 'standard oncall rota';
		$oncalldetails['start'] = strtotime($scheddata->entries['0']->start);
		$oncalldetails['end'] = strtotime($scheddata->entries['0']->end);

		return $oncalldetails;

	}



	// Returns an array of all contact details
	public function getContactDetails($userid){
		$json = $this->apiCall("users/".$userid."/contact_methods");

		return(json_decode($json,true) );
	
	}

	// return a phone number in full international format
	public function getPhoneNumberForOncall($userid){
		$contactarray= $this->getContactDetails($userid);
		$foundaphone =0;
		foreach($contactarray['contact_methods'] as $contactdetails){

			if ($contactdetails['type'] == 'phone'){
				return "+".$contactdetails['country_code'] .$contactdetails['phone_number'] ;
			}
		}
	}



}
