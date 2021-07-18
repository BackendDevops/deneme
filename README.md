# Deneme Project

First use crontab -e 
    * * * * path/to/script/fetch.php
    * * * 10 path/to/script/failed.php
    * * * * path/to/script/approve.php
 

 You can also use manual cron files which names start with cron_

 fetch.php => fetches order list and loops order list and logs
 approve.php => approves pending type orders

 failed.php => resync failed orders

 ApiLimit class limiting max 30 request in 60 seconds

