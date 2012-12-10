OnlineStats
===========

OnlineStats - extension for Yii Framework. This is counter of visitors on your site, based on cookie.

Installation
-----

Download and unpack files to protected/extensions as this:

    <your app>/protected/extensions/onlinestats/OnlineStats.php

Execute next SQL query:

    CREATE TABLE IF NOT EXISTS onlinestats
    (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        created_ts int(11) unsigned NOT NULL DEFAULT 0,
        user_ip varchar(30) NOT NULL DEFAULT '',
        user_session varchar(60) NOT NULL DEFAULT '',
        user_cookie varchar(100) NOT NULL DEFAULT '',
        user_id INT(11) NOT NULL DEFAULT 0
    );

protected/config/main.php (or another):

    <?php
    return array(
        // ...
    	// application components
    	'components'=>array(
            // ...
            'onlinestats' => array(
                'class' => 'application.extensions.onlinestats.OnlineStats',
                'tablename_users' => 'user', // Your tablename of users (usernames, registered time and etc)
                'tablename_users_id' => 'id', // ID field in your Users table
                'tablename_users_registeredtimestamp' => 'tcreate', // Timestamp of user created in your Users table
                'tablename_users_username' => 'username', // Username in your Users table
            ),
            // ...
    	),
    	// ...
    );

Using
----

Insert in your code this for counting users (for example, to protected/components/Controller.php in "init" function and
inherit all controllers by him):

    $onlinestats = Yii::app()->onlinestats;
    $onlinestats->addVisit(); // this add user visit info to database

Now all visits is counting. For view info about them, use this functions:

    $onlinestats->getOnlineUsers(); // current online users/sessions (for last 5 minutes)
    $onlinestats->getOnlineUsers(30); // current online users/sessions (for last 30 minutes)

    // with params and other functions
    $onlinestats->getOnlineUsers($minutes=5); // current online users/sessions (for last 5 minutes)
    $onlinestats->getOnlineUsersList($minutes=5); // array of online users - just "print_r" it.
    $onlinestats->getOnlineUsersPeriod($from=0, $to=0); // Online users - from and to params
    $onlinestats->getRegisteredOnlineUsers($minutes=5); // Registered online users
    $onlinestats->getActiveUsers($minutes=5, $created_ago=172800); // User, registered early than time()-$created_ago and online
    $onlinestats->getDAU(); // Daily Online Users
    $onlinestats->getMAU(); // Monthly Online Users
    $onlinestats->removeHistory($seconds=604800); // remove old history - because your db is not trash :)

Example
---------

full code of protected/components/Controller.php:

    <?php
    class Controller extends CController
    {
        public $onlinestats;

        public function init()
        {
            // STATISTICS
            $this->onlinestats = Yii::app()->onlinestats;
            $this->onlinestats->addVisit();
            // END STATISTICS

            // ...
        }

    }

Controllers, for example - protected/controllers/SiteController.php:

    <?php
    class SiteController extends Controller{

        public $users_online = -1;

        public function actionIndex() {
            $this->users_online = $this->onlinestats->getOnlineUsers();
            // ...
        }
        // ...
    }
