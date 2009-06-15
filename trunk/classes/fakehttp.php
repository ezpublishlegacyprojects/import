<?php
class fakehttp extends eZHTTPTool
{

    function fakehttp()
    {
    
    }

    function hasPostVariable( $name )
    {
        true;
    }

    function postVariable()
    {
        return $this->fakeddata;
    }

    function setVariable( $name, $value )
    {
        $this->fakeddata = $value;
    }
    var $fakeddata;
}
?>