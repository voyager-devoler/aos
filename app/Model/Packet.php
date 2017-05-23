<?php
class Model_Packet
{
    protected $_user_id = null;
    protected $_requests;
    protected static $_needRequest = null;


    public function __construct(array $in_array)
    {
        try 
        {
            if (!is_array($in_array) || !isset($in_array['device_id']) || !isset($in_array['requests']) || !is_array($in_array['requests']))
            {
                throw new ClientIllegalCommandException('Client input data error: '.print_r($data, true),602);
            }
            Model_CurrentPlayer::init($in_array['device_id']);
            $this->_requests = $in_array['requests'];
        }

        catch (ClientIllegalCommandException $e)
        {
            
        }
    }
    
    public static function setNeedRequest($request)
    {
        self::$_needRequest = $request;
    }
    
    public static function getNeedRequest()
    {
        return self::$_needRequest;
    }
    
    public function execute()
    {
        $actions = new actions();
        $views = new views();
        $response = array();
        $result = array();
        try
        {
            $w = new eventsHandler();
            $w->execute();
            // вообще так НЕЛЬЗЯ! очередь должен обрабатывать один воркер, скажем раз в секунду и менять ситуацию на карте
            // и раскидывать месседжи о результатах клиентам в хранилище месседжей откуда они потом их будут забирать и
            // актуализировать локальные данные (и нужно следить чтобы он не запускался пока не отработал предыдущий)

            foreach ($this->_requests as $command=>$params)
            {
                $result[$command] = $actions->$command($params);
                if (method_exists($views, $command))
                    $response[$command] = $views->$command($result[$command]);
                else {
                    $response[$command] = $result[$command];
                }
            }
            Model_CurrentPlayer::getInstance()->save();
            if (!is_null(self::getNeedRequest()))
            {
                $response['needRequest'] = self::getNeedRequest();
            }
        }
        catch (InsufficientResourceException $e)
        {
            $response[$command] = $e->getMessage();
        }
        return $response;
    }
}