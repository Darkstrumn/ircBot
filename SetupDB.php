<?php
    //$this->dbh = new PDO('sqlite:IrcDb_PDO.sqlite');//<<--live
    $this->dbh = new PDO('sqlite::memory');//<<--dev
    function main()
        {
        default_vhps();
        }/*</main()>*/

        
        //>>>>>create the db (initially, or always in dev-mode)
    function create_db()
        {
        $sql=<<<EOL
        CREATE TABLE VariableHardPoints (
            Id INTEGER PRIMARY KEY
            , VHP_Token TEXT
            , VHP_Replaced_BY TEXT
            , Order INTEGER
            )
EOL;
        $this->dbh->exec($sql);
        //>>>>>Initial data
        $this->dbh->exec("INSERT INTO VariableHardPoints (VHP_Token, VHP_Replaced_BY, VHP_Order) VALUES ('Labrador', 'Tank', 2);");
        }/*</create_db()>*/
    //>>>>>build initial settings data
    
    function default_config()
        {
        $lists = array(
            'botName' => '<VANGUARD-OF-LOGIC:\\>>REDACTOR SECT<<>'
            ,'server' => 'irc.dev'
            ,'port' => 6667
            ,'nick' => 'V-O-L'
            ,'name' => 'VANGUARD-OF-LOGIC'
            ,'greeting' => '{NAME} Online\\\\operational.{crlf}Greetings.'
            ,'room' => 'dev'
            ,'wakeToken' => ':vol,'
            ,'signoffMsg' => 'End of line.'
            ,'auth_proc_users' => array('dperry')
            ,'protected_users' => array('dperry','Darkstrumn','TheDarkone', 'V-O-L', 'vol')
            ,'phone_home_to' => array('dperry','Darkstrumn','TheDarkone')
            );
            
        }/*</default_config()>*/
    
    function default_vhps()
        {
        $vhp_template = "INSERT INTO VariableHardPoints (VHP_Token, VHP_Replaced_BY, Order) VALUES ('{VHP_Token}','{VHP_Replaced_BY}',{{VHP_Order}})";
        $lists = array(
            array("{greeting}",$this->config['greeting'],1)
            ,array("{room}",$this->config['room'],2)
            ,array("{signoffMsg}",$this->config['signoffMsg'],3)
            ,array("{server}",$this->config['server'],4)
            ,array("{botName}",$this->config['botName'],5)
            ,array("{nick}",$this->config['nick'],6)
            ,array("{name}",$this->config['name'],7)
            ,array("{1}",chr(1),8)
            ,array("{10}",chr(10),9)
            ,array("{13}",chr(13),10)
            ,array("{crlf}",chr(13) . chr(10),11)
            );
        //
        foreach($lists as $list)
            {
            $sql = vhp_template;
            $sql = str_ireplace("{VHP_Token}",$list[0],$sql);
            $sql = str_ireplace("{VHP_Replaced_BY}",$list[1],$sql);
            $sql = str_ireplace("{VHP_Order}",$list[2],$sql);
            $this->dbh->exec($sql);
            }//</foreach($lists as $list)>
        }/*</</function default_vhps()>*/
