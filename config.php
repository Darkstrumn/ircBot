<?php
set_time_limit(0);
ini_set('display_errors', 'on');


$config = array(
    'botName' => '<VANGUARD-OF-LOGIC:\\>>REDACTOR SECT<<>',
    'server' => 'irc.dev',
    'port' => 6667,
    'nick' => 'V-O-L',
    'name' => 'VANGUARD-OF-LOGIC',
    'greeting' => '{NAME} Online\\\\operational.{crlf}Greetings.',
    'room' => 'dev',
    'wakeToken' => ':vol,',
    'signoffMsg' => 'End of line.',
    'auth_proc_users' => array('dperry'),
    'protected_users' => array('dperry','Darkstrumn','TheDarkone', 'V-O-L', 'vol'),
    'phone_home_to' => array('dperry','Darkstrumn','TheDarkone'),
    //	'pass' => 'meh',
    );
