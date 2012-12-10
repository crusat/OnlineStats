<?php

/*
by Kuznecov Aleksey (crusat@crusat.ru)
Version: 1.01

SQL:

CREATE TABLE IF NOT EXISTS onlinestats
(
  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  created_ts int(11) unsigned NOT NULL DEFAULT 0,
  user_ip varchar(30) NOT NULL DEFAULT '',
  user_session varchar(60) NOT NULL DEFAULT '',
  user_cookie varchar(100) NOT NULL DEFAULT '',
  user_id INT(11) NOT NULL DEFAULT 0
);

*/

class OnlineStats extends CApplicationComponent
{
    public $tablename_stats = 'onlinestats';
    // params
    public $tablename_users = null;
    public $tablename_users_id = null;
    public $tablename_users_registeredtimestamp = null;
    public $tablename_users_username = null;



    public function __construct() { }

    public function init() {

    }

    public function addVisit() {
        // IP
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
            $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $user_ip = $_SERVER['REMOTE_ADDR'];
        }
        // session
        $user_session = Yii::app()->getSession()->sessionId;
        // cookies
        $user_cookie = isset(Yii::app()->request->cookies['online_stats_id']) ? Yii::app()->request->cookies['online_stats_id']->value : '';
        if ($user_cookie == '') {
            $value = md5(time() . $user_ip) . sha1(time() . $user_ip);
            $ck = new CHttpCookie('online_stats_id', $value);
            $ck->expire = time()+60*60*24*180;
            Yii::app()->request->cookies['online_stats_id'] = $ck;
            $user_cookie = $value;
        }
        // user id
        if (Yii::app()->user->isGuest) {
            $user_id = 0; // Guest
        } else {
            $user_id = Yii::app()->user->id;
        }
        // other info
        $nowtime = time();
        //$sql = "INSERT INTO $this->tablename_stats (who, created_ts) VALUES ( '$who' , $nowtime )";
        $sql = "INSERT INTO $this->tablename_stats ( user_ip, user_session, user_cookie, user_id, created_ts) VALUES ( '$user_ip', '$user_session', '$user_cookie', $user_id,  $nowtime )";
        $command = Yii::app()->db->createCommand($sql);
        $command->execute();
        return true;
    }

    public function getOnlineUsers($minutes=5) {
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $sql = "SELECT COUNT(DISTINCT user_cookie) AS count_online FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_online'] ? $row['count_online'] : 0;
        return $result;
    }

    public function getDAU() { // Daily Online Users
        $nowtime = time();
        $lasttime = $nowtime - 86400;
        $sql = "SELECT COUNT(DISTINCT user_cookie) AS count_dau FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_dau'] ? $row['count_dau'] : 0;
        return $result;
    }

    public function getMAU() { // Monthly Online Users
        $nowtime = time();
        $lasttime = $nowtime - 86400*30;
        $sql = "SELECT COUNT(DISTINCT user_cookie) AS count_mau FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_mau'] ? $row['count_mau'] : 0;
        return $result;
    }

    public function getOnlineUsersList($minutes=5) {
        if (($this->tablename_users == null)||($this->tablename_users_id == null)||($this->tablename_users_username == null)||($this->tablename_users_registeredtimestamp == null)) {
            return -1;
        }
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $sql = "SELECT $this->tablename_stats.user_ip as user_ip, $this->tablename_stats.user_session as user_session, $this->tablename_stats.user_cookie as user_cookie, $this->tablename_stats.user_id as user_id, $this->tablename_users.$this->tablename_users_username as username FROM $this->tablename_stats LEFT JOIN $this->tablename_users ON $this->tablename_users.$this->tablename_users_id=$this->tablename_stats.user_id WHERE created_ts > $lasttime GROUP BY $this->tablename_stats.user_cookie";
        //$sql = "SELECT COUNT(DISTINCT user_cookie) AS count_online FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $dataReader = Yii::app()->db->createCommand()->setText($sql)->query();
        $rows = $dataReader->readAll();
        $result = array();
        foreach ($rows as $row) {
            $result[] = array(
                'ip' => $row['user_ip'],
                'session' => $row['user_session'],
                'cookie' => $row['user_cookie'],
                'id' => $row['user_id'],
                'username' => $row['username'],
            );
        }
        return $result;
    }

    public function getOnlineUsersPeriod($from=0, $to=0) {
        $to = $to == 0 ? time() : $to;
        $sql = "SELECT COUNT(DISTINCT user_cookie) AS count_online FROM $this->tablename_stats WHERE created_ts > $from AND created_ts < $to";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_online'] ? $row['count_online'] : 0;
        return $result;
    }

    /*
     * Текущее количество активных пользователей (пользователей зарегистрировавшихся
     * более 2 суток назад).
     * Пользователи должны быть онлайн
     */
    public function getActiveUsers($minutes=5, $created_ago=172800) {
        if (($this->tablename_users == null)||($this->tablename_users_id == null)||($this->tablename_users_username == null)||($this->tablename_users_registeredtimestamp == null)) {
            return -1;
        }
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $created_time = $nowtime - $created_ago;
        $sql = "SELECT COUNT(DISTINCT user_id) AS count_active FROM $this->tablename_stats INNER JOIN $this->tablename_users ON $this->tablename_stats.user_id=$this->tablename_users.$this->tablename_users_id WHERE created_ts > $lasttime AND user_id <> 0 AND $this->tablename_users.$this->tablename_users_registeredtimestamp < $created_time";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_active'] ? $row['count_active'] : 0;
        return $result;
    }

    /*
     * Текущее количество зарегистрированных пользователей онлайн
     */
    public function getRegisteredOnlineUsers($minutes=5) {
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $sql = "SELECT COUNT(DISTINCT user_id) AS count_reg_online FROM $this->tablename_stats WHERE created_ts > $lasttime AND user_id <> 0";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_reg_online'] ? $row['count_reg_online'] : 0;
        return $result;
    }
    /*
     * Чтобы не засорять таблицу - надо удалять старую историю. Если нужна динамика - агрегируйте данные в другую таблицу.
     */
    public function removeHistory($seconds=604800) { // default 604800 - one week
        $nowtime = time();
        $lasttime = $nowtime - $seconds;
        $sql = "DELETE FROM $this->tablename_stats WHERE created_ts < $lasttime";
        $command = Yii::app()->db->createCommand($sql);
        $command->execute();
        return true;
    }

    /*
     * Visits
     */
    public function getVisits($minutes=5) {
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $sql = "SELECT COUNT(id) AS count_visits FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_visits'] ? $row['count_visits'] : 0;
        return $result;
    }

    public function getVisitsToday($timezone=0) {
        $nowtime = time();
        $lasttime = $this->dayBeginTimestamp($nowtime, $timezone);
        $sql = "SELECT COUNT(id) AS count_visits FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_visits'] ? $row['count_visits'] : 0;
        return $result;
    }

    public function dayBeginTimestamp($need_timestamp, $timezone = 0) {
        $t = floor((float)($need_timestamp + $timezone*3600) / 86400)*86400 + 1;
        return $t;
    }


}