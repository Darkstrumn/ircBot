<?php
//examples of execution:
//php /cygdrive/c/Users/dperry.PAPA-SCF1/Documents/hexChat/AddOns/ircBot-master/ircBot-master/IRCBot.php
//php /cygdrive/c/mnt/Development/php/gitrepos/ircBot/IRCBot.php
require_once('config.php'); //<<--server and app >config\\settings<

class IRCBot
    {
    //This is going to hold our TCP/IP connection
    var $socket;

    //This is going to hold all of the messages both server and client
    var $ex = array();

    /*
    This is the workhorse function, grabs the data from the server and displays on the browser
    */
    function main($first = null)
        {
        $this->running = true;
        $null_shield_cnt = 0;
        while($this->running != false)
            {
            $data = fgets($this->socket, 128);
            if(empty($data)){$null_shield_cnt++;} else {$null_shield_cnt = 0;}//<<--increment or reset
            if($null_shield_cnt >= 100){$this->running = false;}//<<--null failsafe (sometimes, a quit leads to a endless stream of nulls, improper close//disconnect... .)
            echo "data:\n";
            echo nl2br($data);
            flush();
            try{$this->ex = explode(' ', $data);}catch(Exception $err){$this->ex = $this->ex = explode(' ', $data)[0];}
            //
            if($first)//>>>>>init, join default channel, and send salutation
                {
                $first = false;
                $this->send_data('JOIN', '#dev');
                sleep(2);
                $this->send_data('PRIVMSG', '#dev :' . $this->vhp_token_processor($this->config['greeting']));
                }
            else{;}
            //</if>

            if($this->ex[0] == 'PING')//>>>>>keep-alive
                {
                echo "**keep-alive**\n";
                $this->send_data('PONG', $this->ex[1]); //Plays ping-pong with the server to stay connected.
                }
            else{;}
            //</if($this->ex[0] == 'PING')>

            $user = null;
            if(preg_match("/^:(.+)\!/", $this->ex[0], $matches))
                {
                echo "matches:\n";
                var_dump($matches);
                $user = $matches[1];
                $this->on_event_parser($cmd_line);
                }
            else{;}
            //</if(preg_match("/^:(.+)\!/", $this->ex[0], $matches))>

            var_dump($this->ex);        
            $this->cmd_parser($this->ex);
            }//</while($this->running != false)>
        }/*</main()>*/
//=============================================================================
//== base functions
//=============================================================================
    function __construct($config)
        {
        $this->running = true;
        $this->config = $config;
        $this->socket = fsockopen($this->config['server'], $this->config['port']);
        //open the database
        $this->dbh = new PDO('sqlite:IrcDb_PDO.sqlite');

        $this->dbh->exec("INSERT INTO Dogs (Breed, Name, Age) VALUES ('Labrador', 'Tank', 2);".
        "INSERT INTO Dogs (Breed, Name, Age) VALUES ('Husky', 'Glacier', 7); " .
        "INSERT INTO Dogs (Breed, Name, Age) VALUES ('Golden-Doodle', 'Ellie', 4);");

        $result = $this->dbh->query('SELECT * FROM Dogs');
        foreach($result as $row)
            {
            echo "**" . $row['Id'] . " " . $row['Breed'] . " " . $row['Name'] . " " . $row['Age'] . "\r\n";
            }
        
        return;
        $this->nick = $this->config['nick'];
        $this->login($this->config);
        $this->main(true);
        }/*</__construct($config)>*/

    function login($config)
        {
        $this->send_data('USER', $config['nick'] . ' ' . $config['botName'] . ' ' . $config['nick'] . ' :' . $config['name']);
        $this->send_data('NICK', $config['nick']);
        }/*</login($config)>*/

    function on_event_parser($cmd_line)
        {
        /*
        */
        $cmd_issuer = (explode('@', (explode('!', $cmd_line[0])[1]))[0]);//<<":nick!~user@idaddress" -> "user@idaddress" -> "user"
        $event = str_replace(array(chr(10), chr(13)), '', $cmd_line[1]);
        $subject = str_replace(array(chr(10), chr(13)), '', $cmd_line[2]);
        //
        switch(strtolower($event))
            {
            case '':
                break;
            default:
                break;
            }//</switch()>
        }/*</on_event_parser($cmd_line)>*/

    function vhp_token_processor($vhd_template)
        {
        $content = $vhd_template;
        $content = str_ireplace("{greeting}",$this->config['greeting'],$content);
        $content = str_ireplace("{room}",$this->config['room'],$content);
        $content = str_ireplace("{signoffMsg}",$this->config['signoffMsg'],$content);
        $content = str_ireplace("{server}",$this->config['server'],$content);
        $content = str_ireplace("{botName}",$this->config['botName'],$content);
        $content = str_ireplace("{nick}",$this->config['nick'],$content);
        $content = str_ireplace("{name}",$this->config['name'],$content);
        $content = str_ireplace("{1}",chr(1),$content);
        $content = str_ireplace("{10}",chr(10),$content);
        $content = str_ireplace("{13}",chr(13),$content);
        return($content);
        }/*</vhp_token_processor($vhd_template)>*/
        
    function cmd_parser($cmd_line)
        {
                
        if(isset($cmd_line[3]) && $cmd_line[3] == $this->config['wakeToken'])//<<--wake token
            {
            echo "cmd_line: ";
            var_dump($cmd_line);
            $command = str_replace(array(chr(10), chr(13)), '', $cmd_line[4]);
            $cmd_issuer = (explode('@', (explode('!', $cmd_line[0])[1]))[0]);//<<":nick!~user@idaddress" -> "user@idaddress" -> "user"
            echo "Command: " . $command;
            switch(strtolower($command)) //List of commands the bot responds to from a user.
                {
                case ':!issue':
                    //>>>>>RESTRICTED, Only authorized personnel beyond this point!
                    $result = $this->bot_issue($cmd_line);
                    $this->speak($cmd_line,$this->vhp_token_processor($result));
                    break;
                case ':!proc':
                    //>>>>>RESTRICTED, Only authorized personnel beyond this point!
                    $result = $this->bot_eval($cmd_line);
                    $this->speak($cmd_line,$this->vhp_token_processor($result));
                    break;
                case 'say':
                    $this->speak($cmd_line,null);
                    break;
                case ':!twiddle':
                    $this->twiddle($cmd_line[5], $user);
                    break;
                case ':!join':
                    $this->join_channel($cmd_line[4]);
                    break;
                case ':!die':
                    $this->send_data('QUIT', ":" . $this->config['signoffMsg']);
                    $this->running = false;
                    break;
                case ':!op':
                    $this->op_user();
                    break;
                case ':!deop':
                    $this->op_user('', '', false);
                    break;
                case ':!voice':
                    $this->voice_user();
                    break;
                case ':!devoice':
                    $this->voice_user('', '', false);
                    break;
                case ':!protect':
                    $this->protect_user();
                    break;
                }//</switch(strtolower($command))>
            }//</if(isset($this->ex[3]) && $this->ex[3] == $this->config['wakeToken'])>
        }/*</cmd_parser($cmd_line)>*/
    
    function bot_phone_home($message)
        {
        //>>>>>parse list of "masters" and the list of users in channel
        foreach($this->config['phone_home_to'] as $master_user)
            {
            
            }//</foreach($this->config['phone_home_to'] as $master_user)>
        echo "speak:false\n";
        $this->send_data('PRIVMSG #dev' . " :", $args);
        }/*</bot_phone_home($message)>*/
    
    function bot_issue($bot_orders)
        {
        rtrim($cmd_issuer = (explode('@', (explode('!', $bot_orders[0])[1]))[0]));//<<":nick!~user@idaddress" -> "user@idaddress" -> "user"
        rtrim($bot_code = explode('!issue', implode(' ',$bot_orders))[1]);
        rtrim($bot_cmd = explode(':', $bot_code)[0]);
        rtrim($bot_args = explode(':', $bot_code)[1]);
        echo "bot_orders:\n";
        var_dump($bot_orders);
        echo "cmd_issuer:\n";
        var_dump($cmd_issuer);
        echo "bot_code:\n";
        var_dump($bot_code);
        //>>>>>RESTRICTED, Only authorized personnel beyond this point!
        if(in_array($cmd_issuer, $this->config['auth_proc_users']))
            {
            $this->send_data($bot_cmd, $bot_args);
            $result = "$bot_cmd issued... .";
            }
        else
            {
            echo "<hr><strong>Unable to comply, $cmd_issuer is not authorized to issue such commands to me.</strong><hr>/n";
            $result = "Unable to comply, $cmd_issuer is not authorized to issue such commands to me.";
            }//</if(in_array($cmd_issuer, $this->config['auth_proc_users']))>
        echo "result:\n";
        var_dump($result);
        return($result);
        }/*</bot_eval($bot_code)>*/
        
    function bot_eval($bot_orders)
        {
        $cmd_issuer = rtrim((explode('@', (explode('!', $bot_orders[0])[1]))[0]));//<<":nick!~user@idaddress" -> "user@idaddress" -> "user"
        $bot_code = rtrim(explode('!proc', implode(' ',$bot_orders))[1]);
        echo "bot_orders:\n";
        var_dump($bot_orders);
        echo "cmd_issuer:\n";
        var_dump($cmd_issuer);
        echo "bot_code:\n";
        var_dump($bot_code);
        //>>>>>RESTRICTED, Only authorized personnel beyond this point!
        if(in_array($cmd_issuer, $this->config['auth_proc_users']))
            {
            $result = eval("return(" . $this->vhp_token_processor($bot_code) . ");");
            }
        else
            {
            echo "<hr><strong>Unable to comply, $cmd_issuer is not authorized to issue such commands to me.</strong><hr>/n";
            $result = "Unable to comply, $cmd_issuer is not authorized to issue such commands to me.";
            }//</if(in_array($cmd_issuer, $this->config['auth_proc_users']))>
        echo "result:\n";
        var_dump($result);
        return($result);
        }/*</bot_eval($bot_code)>*/
 
    function speak($cmd_line, $args = null)//<<--args allows overrides of args pulled form cmd_line
        {
        if(empty($args))
            {
            //
            for($i = 5; $i < count($cmd_line); $i++)
                {
                //print($this->ex[$i]);
                $args .= $cmd_line[$i] . ' ';
                }//</for($i = 5; $i < count($cmd_line); $i++)>
            }//</if(empty($args))>
        //
        if($cmd_line[2] == $this->nick)
            {
            echo "speak:true\n";
            preg_match('/:(.*)!/', $cmd_line[0], $matches);
            $this->send_data('PRIVMSG ' . $matches[1] . " :", $this->vhp_token_processor($args));
            }
        else
            {
            echo "speak:false\n";
            $this->send_data('PRIVMSG #dev' . " :", $this->vhp_token_processor($args));
            }//</if($cmd_line[2] == $this->nick)>
        }/*</speak()>*/        

    function send_data($cmd, $msg = null) //displays stuff to the browser and sends data to the server.
        {
        $cmd = strtoupper($cmd);
        $msg = ($msg == null ? '' : $msg);
        fputs($this->socket, $cmd . ' ' . $this->vhp_token_processor($msg) . "\r\n");
        echo 'send_data(<strong>' . $cmd . ' ' . $this->vhp_token_processor($msg) . '</strong>)<br />\n';
        }/*</send_data($cmd, $msg = null)>*/

    function send_action($chan = null, $msg = null) //displays stuff to the browser and sends data to the server.
        {
        $chan = ($chan == null ? '#dev' : $chan);
        $msg = ($msg == null ? '' : $msg);
        $VHPTemplate = "{CHAN} " . chr(1) . ":ACTION {MSG}" . chr(1) . "";
        $content = str_ireplace("{CHAN}",$chan,$VHPTemplate);
        $content = str_ireplace("{MSG}",$this->vhp_token_processor($msg),$content);
        echo 'send_data(<strong>' . $content . "\r\n" . '</strong>)<br />\n';
        fputs($this->socket, $content . "\r\n");
        }/*</send_data($cmd, $msg = null)>*/

    function join_channel($channel) //Joins a channel, used in the join function.
        {
        //
        if(is_array($channel))
            {
            //
            foreach($channel as $chan)
                {
                $this->send_data('JOIN', $chan);
                }//</foreach($channel as $chan)>
            }
        else
            {
            $this->send_data('JOIN', $channel);
            }//</if(is_array($channel))>
        }/*</join_channel($channel)>*/

    function protect_user($user = '')
        {
        //
        if($user == '')
            {
            //
            if(php_version() >= '5.3.0')
                {
                $user = strstr($this->ex[0], '!', true);
                }
            else
                {
                $length = strstr($this->ex[0], '!');
                $user = substr($this->ex[0], 0, $length);
                }//</if(php_version() >= '5.3.0')>
            }
        else{;}
            //</if($user == '')>
        $this->send_data('MODE', $this->ex[2] . ' +a ' . $user);
        }/*</protect_user($user = '')>*/

    function op_user($channel = '', $user = '', $op = true)
        {
        //
        if($channel == '' || $user == '')
            {
            //
            if($channel == '')
                {
                $channel = $this->ex[2];
                }
            else{;}
                //</if($channel == '')>
            //
            if($user == '')
                {
                //
                if(php_version() >= '5.3.0')
                    {
                    $user = strstr($this->ex[0], '!', true);
                    }
                else
                    {
                    $length = strstr($this->ex[0], '!');
                    $user = substr($this->ex[0], 0, $length);
                    }//</if(php_version() >= '5.3.0')>
                }//</if($user == '')>
            }
        else{;}
            //</if($channel == '' || $user == '')>
        //
        if($op)
            {
            $this->send_data('MODE', $channel . ' +o ' . $user);
            }
        else
            {
            $this->send_data('MODE', $channel . ' -o ' . $user);
            }//</if($op)>
        }/*</op_user($channel = '', $user = '', $op = true)>*/

    function args2str($carry, $item)
        {
        return ($carry . " " . $item);
        }/*</args2str($carry, $item)>*/

    function shields($target)//<<--aux function
        {
        $ret = false;
        //>>>>>shields system
        if(in_array($target, $this->config['protected_users']))
            {
            $ret = true;
            echo ">>Protected_User<< detected. <" . $target . ">.";
            }
        else{;}
        //</if($target != $this->nick)>
        }/*</twiddle($target, $initiator)>*/
//=============================================================================
//== /base functions
//=============================================================================
//== aux functions
//=============================================================================
/*
WIP, still truncates after first space in message, as if the : is not found in the action... working on it.
*/
    function twiddle($target, $initiator)//<<--aux function
        {
        //>>>>>shields
        if(!$this->shields($target))
            {
        echo "<" . $initiator . "> >twiddles//flips< <" . $target . ">' bit!";
            $this->send_action("#dev","</ME> >twiddles////flips< <$target>' bit!");
            }
        else
            {
        echo ">>Protected_User<< detected. <" . $this->config['nick'] . "> >twiddles//flips< <" . $initiator . ">' bit!";
            $this->send_action("#dev","</ME> >twiddles////flips< <$initiator>' bit!");
            }//</if($target != $this->nick)>
        }/*</twiddle($target, $initiator)>*/
//=============================================================================
//== /aux functions
//=============================================================================
    }/*</class::IRCBot>*/

$bot = new IRCBot($config);
