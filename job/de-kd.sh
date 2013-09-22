#!/bin/sh

cd ~/ispscan/php

/usr/bin/php explore.php de/kd
/usr/bin/php buildmap.php de/kd
/usr/bin/sudo /usr/bin/php pingnets.php de/kd
