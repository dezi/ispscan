#/bin/sh
rm -f top-1m.csv.zip
wget http://s3.amazonaws.com/alexa-static/top-1m.csv.zip
unzip top-1m.csv.zip
grep -P "de$" top-1m.csv > top-de.csv
grep -P "(net|org|com|tv)$" top-1m.csv > top-xx.csv
