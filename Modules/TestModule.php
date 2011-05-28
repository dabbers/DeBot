<?php
class m_TestModule extends Module
{
    public function init()
    {
        $this->m_oBot['modules']['Cmds']->addCommandLink( 'testmod', 'TestModule', 'testmod' );
    }
    
    public function testmod()
    {
        $this->m_oBot->msg( $this->m_oBot['in']->Channel, 'Hello Reloaded module, from $oBot this time.' );
    }
}

?>