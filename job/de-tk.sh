#!/bin/sh

cd ~/ispscan/php

/usr/bin/php explore.php de/tk
/usr/bin/php buildmap.php de/tk
/usr/bin/sudo /usr/bin/php pingnets.php de/tk
