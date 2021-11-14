#!/bin/bash
python3 /var/www/html/resources/main.py >/dev/null 2>/dev/null &
/bin/date +"%Y-%m-%d %H:%M:%S" > /var/www/html/resources/lastupdate.txt