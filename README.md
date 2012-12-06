OnlineStats
===========

OnlineStats - the counter of visitors on your site. This is extension of Yii Framework.

Installation
-----

Download and unpack files to protected/extensions as this:

    <your app>/protected/extensions/onlinestats/OnlineStats.php

Execute next SQL query:

    CREATE TABLE IF NOT EXISTS onlinestats
    (
        id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
        created_ts int(11) unsigned NOT NULL DEFAULT 0,
        who TEXT NOT NULL DEFAULT ''
    );

    ALTER TABLE <your_user_table> ADD last_activity INT(11) NOT NULL DEFAULT 0;

This is protected/config/main.php (or another):

    <?php
    return array(
        // ...
    	// application components
    	'components'=>array(
            // ...
            'onlinestats' => array(
                'class' => 'application.extensions.onlinestats.OnlineStats',
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
    $onlinestats->addUser(); // this add user visit info to database

Now all visits is counting. For view info about them, use this functions:

    $onlinestats->getOnlineUsers(); // current online users/sessions (for last 5 minutes)
    $onlinestats->getOnlineUsers(30); // current online users/sessions (for last 30 minutes)

    // with params and other functions
    $onlinestats->getOnlineUsers($minutes=5); // current online users/sessions (for last 5 minutes)
    $onlinestats->getOnlineUsersList($minutes=5); // array of online users - ip, session name.
    $onlinestats->getOnlineUsersPeriod($from=0, $to=0); // not last online users - from and to params
    $onlinestats->removeHistory($seconds=604800); // remove old history - because your db is not trash :)

Example
---------

Code of protected/components/Controller.php:

    <?php
    class Controller extends CController
    {
        public $onlinestats;

        public function init()
        {
            // STATISTICS
            $this->onlinestats = Yii::app()->onlinestats;
            $this->onlinestats->addUser();
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
