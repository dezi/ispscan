#!/bin/sh

cd ~/ispscan/php

/usr/bin/php explore.php de/tf
/usr/bin/php buildmap.php de/tf
/usr/bin/sudo /usr/bin/php pingnets.php de/tf
