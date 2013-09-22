#!/bin/sh

cd ~/ispscan/php

/usr/bin/php explore.php de/vf
/usr/bin/php buildmap.php de/vf
/usr/bin/sudo /usr/bin/php pingnets.php de/vf
