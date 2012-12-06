<?php

/*
by Kuznecov Aleksey (crusat@crusat.ru)

SQL:

CREATE TABLE IF NOT EXISTS onlinestats
(
  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  created_ts int(11) unsigned NOT NULL DEFAULT 0,
  who TEXT NOT NULL DEFAULT ''
);

ALTER TABLE <your_user_table> ADD last_activity INT(11) NOT NULL DEFAULT 0;

*/

class OnlineStats extends CApplicationComponent
{
    public $tablename_stats = 'onlinestats';
    // to params
    public $tablename_user = 'user';
    public $model_user;
    public $model_user_id = 'udid';




    public function __construct() { }

    public function init() {
        $this->model_user = User::model();
    }

    public function addUser() {
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
            $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $user_ip = $_SERVER['REMOTE_ADDR'];
        }
        $user_ip = $user_ip . ' ' . Yii::app()->getSession()->sessionId;
        $nowtime = time();
        $sql = "INSERT INTO $this->tablename_stats (who, created_ts) VALUES ( '$user_ip' , $nowtime )";
        $command = Yii::app()->db->createCommand($sql);
        $command->execute();
        $current_user = $this->model_user->find($this->model_user_id.'=:id', array(':id'=>Yii::app()->user->id));
        if ($current_user) {
            $current_user->last_activity = time();
            $current_user->save();
        }
        return true;
    }

    public function getOnlineUsers($minutes=5) {
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $sql = "SELECT COUNT(DISTINCT who) AS count_online FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_online'] ? $row['count_online'] : 0;
        return $result;
    }

    public function getOnlineUsersList($minutes=5) {
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $sql = "SELECT DISTINCT who FROM $this->tablename_stats WHERE created_ts > $lasttime";
        $dataReader = Yii::app()->db->createCommand()->setText($sql)->query();
        $rows = $dataReader->readAll();
        $result = array();
        foreach ($rows as $row) {
            $result[] = explode(' ', $row['who']);
        }
        return $result;
    }

    public function getOnlineUsersPeriod($from=0, $to=0) {
        $to = $to == 0 ? time() : $to;
        $sql = "SELECT COUNT(DISTINCT who) AS count_online FROM $this->tablename_stats WHERE created_ts > $from AND created_ts < $to";
        $rows = Yii::app()->db->createCommand()->setText($sql)->query();
        $row = $rows->read();
        $result = $row['count_online'] ? $row['count_online'] : 0;
        return $result;
    }

    /*
     * Текущее количество активных пользователей (пользователей зарегистрировавшихся
     * более 2 суток назад)в игре на момент опроса игры коллектором статистики.
     * Пользователи должны быть онлайн
     */
    public function getActiveUsers($minutes=5, $created_ago=172800) {
        $nowtime = time();
        $lasttime = $nowtime - $minutes*60;
        $created_time = $nowtime - $created_ago;
        $sql = "SELECT COUNT($this->model_user_id) AS count_active FROM $this->tablename_user WHERE tcreate<$created_time AND last_activity > $lasttime";
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
        $sql = "SELECT COUNT($this->model_user_id) AS count_reg_online FROM $this->tablename_user WHERE last_activity > $lasttime";
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
}

