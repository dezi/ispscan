#!/bin/sh

cd ~/ispscan/php

/usr/bin/php explore.php de/kb
/usr/bin/php buildmap.php de/kb
/usr/bin/sudo /usr/bin/php pingnets.php de/kb
