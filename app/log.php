<?php
/**
 * Логгер
 */
class log extends dbLink {
    
    private static $_user_id = 0;

    private $_start_time;
    private $_request = '';
    private $_params = '';
    private $_answer_status = '';
    private $_error_code = 0;
    private $_answer_body = '';
    private $_answer_extra = '';


    public static function setUserId($user_id)
    {
        self::$_user_id = $user_id;
    }

    public function  __construct()
    {
        $this->_start_time = microtime(true);
    }

    public function setRequest($request)
    {
        if (!is_null($request))
            $this->_request = $request;
    }

    public function setParams($params)
    {
        if (!is_null($params))
        {
            $this->_params = $params;
        }
        /* костылек для подсчета друзей в игре */
        if ($this->_request == 'getUsers')
            $this->_answer_extra = count($this->_params);
    }

    public function addRecord()
    {
        $duration = (microtime(true) - $this->_start_time)*1000;
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $level = 0;
        $gold = 0;
        $silver = 0;
        $dgold = 0;
        $dsilver = 0;
        $reg_date = '0000-00-00 00:00:00';
        $sex = '';
        $donate = 0;
        $pays_count = 0;
        $last_pay_time = '0000-00-00 00:00:00';
        if (Model_CurrentUser::checkUserWasCreated())
        {
            $level = Model_CurrentUser::getInstance()->level;
            $gold = Model_CurrentUser::getInstance()->money_outgame;
            $silver = Model_CurrentUser::getInstance()->money_ingame;
            $dgold = Model_CurrentUser::getInstance()->get_dgold();
            $dsilver = Model_CurrentUser::getInstance()->get_dsilver();
            $reg_date = Model_CurrentUser::getInstance()->reg_date;
            $sex = Model_CurrentUser::getInstance()->sex;
            $donate = Model_CurrentUser::getInstance()->donate;
            $pays_count = Model_CurrentUser::getInstance()->pays_count;
            $last_pay_time = Model_CurrentUser::getInstance()->last_pay_time;
            Model_CurrentUser::getInstance()->clear_dmoney(); // сбрасывает измененния по деньгам между командами в одном пакете для статистики
        }
        if (is_array($this->_params))
        {
                $this->_params = json_encode($this->_params);
                if (strlen($this->_params)>255) $this->_params = '...too long...';
        }
        if (is_null($this->_answer_body)) $this->_answer_body = '';
        if (is_array($this->_answer_body))
        {
                $this->_answer_body = json_encode($this->_answer_body);
                if (strlen($this->_answer_body)>3072)
                    $this->_answer_body = substr($this->_answer_body,0,3000).'...too long...';
        }
        if (is_array($this->_answer_extra))
                $this->_answer_extra = json_encode ($this->_answer_extra);
        if ($this->_request == 'tutorialStep' && isset($_SESSION['ref']) && !logtutor::isStdRef($_SESSION['ref']))
        {
            $tutorlog = new logtutor(self::$_user_id, $this->_params, $_SESSION['ref']);
            $tutorlog->addRecord();
        }
        $this->getDB()->query('INSERT INTO ?_rawlog (
            day,
            user_id,
            num_in_pack,
            request,
            params,
            answer_status,
            errcode,
            answer_body,
            answer_extra,
            client_ip,
            duration,
            ud_level,
            ud_gold,
            ud_silver,
            ud_regdate,
            d_gold,
            d_silver,
            ud_sex,
            ud_donate,
            ud_payscount,
            ud_lastpaytime) VALUES (?d,?,?,?,?,?,?,?,?,INET_ATON(?),?,?,?,?,?,?,?,?,?,?,?)',
                date('ymd'),
                self::$_user_id,
                $this->_num_in_pack,
                $this->_request,
                $this->_params,
                $this->_answer_status,
                $this->_error_code,
                $this->_answer_body,
                $this->_answer_extra,
                $client_ip,
                $duration,
                $level,
                $gold,
                $silver,
                $reg_date,
                $dgold,
                $dsilver,
                $sex,
                $donate,
                $pays_count,
                $last_pay_time
        );
    }
}

