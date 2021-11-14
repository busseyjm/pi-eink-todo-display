# installation script. will set up all permissions and packages with just a single script.
# ideally.

if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit
fi

#packages
apt-get install python3-pip python3-pil python3-numpy libopenjp2-7 libtiff5 apache2 php8.0 sqlite3 php8.0-sqlite at
python3 -m pip install --upgrade pip
python3 -m pip install --upgrade cython
python3 -m pip install --upgrade Pillow
sudo pip3 install RPi.GPIO

#allow www-data to use at
sed -i '/www-data/d' /etc/at.deny

#404 on resources folder for web requests
echo "RedirectMatch 404 ^(.*/)?resources/" >> /etc/apache2/apache2.conf

git clone https://github.com/waveshare/e-Paper.git
cd e-Paper/RaspberryPi_JetsonNano/python/
sudo python3 setup.py build
sudo python3 setup.py install
cd ../../../..

# website copy and permission updates
sqlite3 -init ./html/resources/db.sql ./html/resources/todolist.db .quit
cp -r html /var/www/html
rm /var/www/html/index.html
chmod g+w /var/www/html/resources /var/www/html/resources/todolist.db /var/www/html/resources/lastupdate.txt
adduser www-data gpio
adduser www-data spi
adduser www-data kmem

#contab for midnight refresh
ctab=crontab -l
touch crontab_new

if [[ $ctab != "no crontab for root" ]]; then
  crontab -l > crontab_new 
elif

chmod g+x /var/www/html/resources/midnightcron.sh
echo "0 0 * * * /var/www/html/resources/midnightcron.sh" >> crontab_new
crontab crontab_new
rm crontab_new

echo "Installation complete."