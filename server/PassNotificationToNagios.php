<?php
/**
* Receives reboot events and submits passive command to Nagios
* Will work only if nagios host configuration is kept in individual files per host.
*
* @package           PassNotificationToNagios.php
* @author            Brijesh
* @author            Vasudevan
* @license           MIT
*/

/**
* Configurations
* Uncomment and use config relevant to your enviornment.
*/
//Path to nagios command file
$cmd_file="/usr/local/nagios/var/rw/nagios.cmd"; 

// path to host object configuration files.  One host per file, with file name same as monitored hostname.
$nagios_host_cfg="/usr/local/nagios/etc/hosts";  

/**
* End Configuration
*/

/*
* Log events to syslog.  
* If you want custom logs change the log level in the log_event() function below. 
* You can configure to local7 (LOG_LOCAL7) to and have it directed specific file. use log_rotation.
* man syslog.conf for more details.
*
* LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG
* LOG_LOCAL7 not availabe on windows.
*/
function log_event($message) {
	openlog("RebootEvents", LOG_PID, LOG_LOCAL0);
	syslog($level, $message);
	closelog();
}

//$output = var_export($_POST, true);
//error_log($output, 0,"/var/log/reboot.log");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //cleanup
        
    $host_ip=trim($_POST['host_ip']);
    $event_type=trim(strtoupper($_POST['event_type']));
    $nagios_host_object="";
    $host_ips="";
    $host_status = 0;
    $err_msg="";
    $host_staus_msg="Just Coming Up";
	
// Lets get client IP from where this was called, taking all IPs as this might have traversed through proxy, or loadbalancers.
// just in case host_ip was not passed, we can use the client IP from where this was called.
	$client_ip = getenv('HTTP_CLIENT_IP')?:
        getenv('HTTP_X_FORWARDED_FOR')?:
        getenv('HTTP_X_FORWARDED')?:
        getenv('HTTP_FORWARDED_FOR')?:
        getenv('HTTP_FORWARDED')?:
        getenv('REMOTE_ADDR'); 
    
	if(empty($event_type) || empty($host_ip) )
	{
		$err_msg = "Event Type or Host IP is found empty";
		log_event($err_msg);
		echo $err_msg;
		exit;
	}
	
	if(!file_exists($cmd_file))
	{
		$err_msg = "$cmd_file does not exists";
		log_event($err_msg);
		echo $err_msg;
		exit;
	}
	
	if(!file_exists($nagios_host_cfg))
	{
		$err_msg = "$nagios_host_cfg does not exists";
		log_event($err_msg);
		echo $err_msg;
		exit;
	}
	
    if($event_type == 'DOWN')
    {
        $host_status=1;
        $host_staus_msg="Going Down";

    }
	else if($event_type == 'UP')
	{
		$host_status = 0;
		$host_staus_msg="Just Coming Up";
	}
	else
	{
		$err_msg = "Invalid Event Type Detected ($event_type)";
		log_event($err_msg);
		echo $err_msg;
		exit;
	}
		
    if(empty($host_ip))
    {
        if(!empty($client_ip))
        {
            $host_ips = array($client_ip);
			$err_msg="Empty IP address passed to the script. Continuing using auto-detected IP address($client_ip)";
			log_event($err_msg);
        }
        else
        {
			$err_msg="Empty IP address was passed to the script. auto-detected IP address is empty as well";
			log_event($err_msg);
            exit;
        }
    }
    else
    {   
        $host_ips=explode(",",$host_ip);
    }
    $nagios_host_cfg.="/*.cfg";
	
    foreach( $host_ips as $ip)
    {
		$ip = trim($ip);
		if(!empty($ip))
		{
            $ip = "$ip" . "\$";
			
			$nagios_host_object = trim((shell_exec("grep -l $ip $nagios_host_cfg")));
			if(!empty($nagios_host_object))
			{	
				$nagios_host_object = trim(basename($nagios_host_object,".cfg"));
				echo $nagios_host_object . "\n";
				if(!empty($nagios_host_object))
				{	
					break;
				} 
			}
		}
    }      

	if(empty($nagios_host_object))
	{
		$err_msg = "Nagios host name for IP(s) $host_ip could not be found in $nagios_host_cfg";
		log_event($err_msg);
		exit;		
	}
    $nagios_host_object = preg_replace("/\r\n|\r|\n/", '', $nagios_host_object);
    $nagios_host_object = trim($nagios_host_object);
    //[<timestamp>] PROCESS_HOST_CHECK_RESULT;<host_name>;<host_status>;<plugin_output>
    $cmd= "[" . time() . "] PROCESS_HOST_CHECK_RESULT;" . $nagios_host_object . ";" . $host_status . ";" . $host_staus_msg;
    shell_exec("echo \"$cmd\" >> $cmd_file");	
    $err_msg="$host_staus_msg";
    log_event($err_msg);
    //echo "I got " . $event_type . " NagiOS XI Host: " . $nagios_host_object . " " . $host_ip . " detected IP " . $client_ip ;
}