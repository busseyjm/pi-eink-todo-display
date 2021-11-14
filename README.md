# pi-eink-todo-display
A to-do list displayed on an E-Ink display and hosted on a raspberry pi zero-w.

The goal of this project is to create a common to-do list for my household, with a locally hosted website where anyone on the network can add to or remove from the list. The website interface will have a mobile focus design for usage with mobile devices.


## Usage
* Connect to the website hosted on the pi at http://HOSTNAME.local
* Add/remove/reorder items to be displayed on the E-Ink display.
* Submit to update the display (rate limited to once per minute)


## Hardware
* Raspberry Pi Zero-W
* Waveshare 7.5 Inch E-Ink Display & Hat (800x480) 2 Colours
  * Any waveshare E-Ink display will work, although the main.py script will need to be updated to use the correct imports and functions, and the todolist.py will need to be changed to make it draw nicely.

## Setup
* Burn a Raspberry Pi OS image onto the storage media for your pi, typically an SD card
  * I used raspios-bullseye-armhf-lite for my raspberry pi zero w at the time of writing.

* To enable wifi without a monitor:
   1. Within the raspios directory 'boot' create the following files:
       * wpa_supplicant.conf
       * ssh
   2. Within wpa_supplicant.conf add your wifi credentials and country code. Note that the Raspberry Pi Zero W does **NOT** have a 5ghz antenna, and **must** use 2.4ghz.
     ```
     country=COUNTRY_CODE
     ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
     update_config=1

     network={
         ssid="NETWORK_NAME"
         psk="NETWORK_PASSWORD"
         key_mgmt=WPA-PSK
     }
     ```

   3. Insert the SD card into the pi, plug it in and allow it time to boot.
   4. The default configuration has the username as pi, hostname as raspberrypi, and password as raspberry. Zeroconf is enabled out of the box, so you can connect via ssh to **pi@raspberrypi.local**
   5. I suggest doing the **#ssh-problems** changes to /etc/ssh/sshd_config as soon as possible if ssh is running slowly.
   6. Enter the raspi-config tool with __sudo raspi-config__.
   6. Change your timezone under __5. Localisation Options__
      * Choose continent and a nearby listed city.
   7. Enable SPI under __3. Interface Options__
   8. Change the hostname and password if you wish under __1. System Options__
   9. __RESTART THE SYSTEM__ with __sudo reboot__

## Pre-installation
* sudo apt-get update
* sudo apt-get install git
* git clone https://github.com/busseyjm/pi-eink-todo-display.git
* cd pi-eink-todo-display

## Installation
* sudo ./install.sh

## ssh issues
I had some trouble with ssh over wifi, with ssh dropping out pretty often. 
A couple things that seemed to help were:
* Uncomment "#UseDNS no" from /etc/ssh/sshd_config
* Append "IPQoS cs0 cs0" to /etc/ssh/sshd_config
* Use a different router. Mine was the root of all my problems until the above 2 changes were made.


## Known issues
* If the __at__ command issued to overwrite "SCHEDULED" with a date/time fails, the system will never be able to update the e-ink again without manually adding a date to '/var/www/html/resources/lastupdated.txt' or until the midnight cron to update the screen runs.
