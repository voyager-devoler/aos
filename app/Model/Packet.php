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
                    $response[$command]['response'] = $views->$command($result[$command]);
                else {
                    $response[$command]['response'] = $result[$command];
                }
                $response[$command]['status'] = 'ok';
            }
            Model_CurrentPlayer::getInstance()->save();
            if (!is_null(self::getNeedRequest()))
            {
                $response['needRequest'] = self::getNeedRequest();
            }
            $events = dbLink::getDB()->select('select id, type, obj_id, start_time, finish_time, params from events where player_id=?d and processed=0', Model_CurrentPlayer::getInstance()->id);
            foreach($events as $id=>$event)
            {
                if ($event['type'] == 'fleet_move')
                {
                    $events[$id]['position'] = $event['params'];
                    $events[$id]['fleet_id'] = $event['obj_id'];
                }
                if ($event['type'] == 'res_prod')
                {
                    $events[$id]['source_id'] = $event['obj_id'];
                    $events[$id]['remains'] = $event['params'];
                    
                }
                unset($events[$id]['obj_id']);
                unset ($events[$id]['params']);
            }
            $response['events'] = $events;
            $response['messages'] = dbLink::getDB()->select('select id, time, text from messages where player_id = ?d and delivered = 0', Model_CurrentPlayer::getInstance()->id);
        }
        catch (InsufficientResourceException $e)
        {
            $response[$command]['response'] = $e->getMessage();
            $response[$command]['status'] = 'error';
        }
        return $response;
    }
}