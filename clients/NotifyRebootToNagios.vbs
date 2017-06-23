'* Script Name:		NotifyRebootToNagios.vbs
'* Created On:		5/19/2017
'* Author:			Brijesh, Vasudevan
'* Purpose:			Notifies a Nagios via a Web post method that the server is 
'*					going down or has just come up.
'* History:			Brijesh Nagarkar 6/22/2017
'*					Modified to exclude link-local address 
'*					Automatic Private IP Addressing (APIPA)
'* Caveats:			Error handling is not done.
'*
'* Script Arugment:	< UP/DOWN > Strictly limit argument to 'UP' or 'DOWN'
'* 					Other arguments will be ignored / lead to undesired results.
'* Setup:			This scripts needs to be setup to run during startup and 
'*					shutdown by editing the local computer policy.
'*
'================
' Configurations
' Uncomment the below line and put in the right URL of your environemnt.
'================
dim UrlToPost
'UrlToPost  = http://your-monitoring-host/path_to/alert_reboot.php


'===== Begin ======

Set objArgs = Wscript.Arguments

'Get all ip addresses from system
dim Interfaces, Interface, StrIP 
Set Interfaces = GetObject("winmgmts:").InstancesOf("Win32_NetworkAdapterConfiguration")
For Each Interface in Interfaces
    if Interface.IPEnabled then
		
		if Len(StrIP) = 0  AND Left(Interface.IPAddress(i),3) <> "169" Then
			StrIP = Interface.IPAddress(i)
		Else
			If Len(Interface.IPAddress(i)) <> 0 AND Left(Interface.IPAddress(i),3) <> "169" Then
				StrIP = StrIP & "," & Interface.IPAddress(i)	
											
			End If
		End If
                                
    End if
next

DataToSend = "host_ip=" & StrIP & "&event_type=" & objArgs(0)

WScript.Echo DataToSend

dim xmlhttp 
dim res
set xmlhttp = Createobject("MSXML2.ServerXMLHTTP")
xmlhttp.Open "POST", UrlToPost, false
xmlhttp.setRequestHeader "Content-Type", "application/x-www-form-urlencoded"
xmlhttp.send DataToSend
WScript.Echo xmlhttp.responseText
Set xmlhttp = nothing