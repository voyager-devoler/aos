<?php
class log2file
{
    private $_log;

    public function  __construct($filename)
    {
        $this->_log = fopen(LOG_DIR.$filename, 'a');
    }

    public function write($text, $addTime = false)
    {
        if ($addTime)
            $time = '['.date('Y-m-d H:i:s').'] ';
        else
            $time='';
        fwrite($this->_log, $time.$text."\n");
        echo $time.$text.'<br>';
    }

    public function close()
    {
        fclose($this->_log);
    }
}
?>
