PHP PagerDuty WhosOnCall
===============
(c) 2016 Jamie Murphy <jamiemurphyit@gmail.com>
Installation
-----------
``` sh
$ composer require itjamie/pagerdutywhosoncall
```

Basic Usage
-----------

```php


	require 'vendor/autoload.php'; // if using php composer

	date_default_timezone_set('UTC'); // set the timezone of your php env to be the same you pass to pagerduty
	$pagerdutyapi = new PagerDutyWhosOncall(array(
            'baseurl' => 'https://instancename.pagerduty.com/api/v1/', 
            'apikey' => 'placeholderforyourapi',
            'debug' => false,
            'timezone' => 'UTC' // This is the timezone that will be passed to pagerduty. https://developer.pagerduty.com/documentation/rest/types#timezone
            )
	);
        
	$scheduleid = "abc123";


	echo ' Oncall Now :'.PHP_EOL;
	$result = $pagerdutyapi->whoIsOnCall( $scheduleid );
    var_dump($result);

	echo ' Oncall in one hour :'.PHP_EOL;
	$time = time()+60*60;
	$result = $pagerdutyapi->whoIsOnCall( $scheduleid, $time );
    var_dump($result);

```


Credits
-----------

Thanks to ozzd/PagerdutyPHP for some of the original code. 