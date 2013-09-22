#!/bin/sh

cd ~/ispscan/php

/usr/bin/php explore.php de/um
/usr/bin/php buildmap.php de/um
/usr/bin/sudo /usr/bin/php pingnets.php de/um
