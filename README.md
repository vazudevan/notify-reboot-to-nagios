# notify-reboot-to-nagios
> Set of tools for servers to notify nagios that it is rebooting

# Introduction
Host UP/Down checks in Nagios are mostly active with an check interval.  Any change, say a unplanned reboot that happens between these check intervals might go unnoticed, especially with the faster hardware and virtualization.

Administrators should not miss out such events, especially if the reboot was unintentional, hence these set of scrips can be used assist in notifying administrators via Nagios when the server/host goes down or comes back up.

# Functionality
* Individual servers will report coming UP / Going down events to a web service
* The received events are processed and updated in Nagios via command interface
* Notification of event to administrators as configured in Nagios
* Supports Linux and Windows hosts
* Auto-lookup hostname in Nagios using IP Address
* Works with Nagios Core or XI.

# Approach
### Server Component:
Simple http/s form data with IP Addresses and event type (UP/DOWN), is collected.  A corresponding monitored host is matched against nagios config, and passive result submitted with an event type for the matched host.  Nagios process the passive command and initiates Notification.

### Client Component:
Reterives the configured IP addresss and passes on to server when either shutting down, or coming up.  This is done using 
1.  Init script in CentOS 6X or lower.
2.  systemd script for CentOS 7
3.  VBScript in Windows (Configured to run in local computer policy during startup and shutdown)

# Requirements
* Nagios should be setup to accept passive checks for this to work.
* Set `accept_passive_service_checks` directive to `1` in nagios.cfg
* In nagios, each host needs to be configured in a seperate file with the file name same as nagios host_name directive

# Constraints
* Currently works only with individual file for each configured host in nagios.