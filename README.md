# pi-eink-todo-display
A to-do list displayed on an E-Ink display and hosted on a raspberry pi.

The goal of this project is to create a common to-do list for my household, with a locally hosted website where anyone can add to or remove from the list. This website will have a mobile focus design for usage with mobile devices.

# install python3 packages
sudo apt-get update
sudo apt-get install git python3-pip libopenjp2-7 libtiff5

sudo raspi-config
Enable SPI: 3. Interface -> P4 SPI

python3 -m pip install --upgrade pip
python3 -m pip install --upgrade Pillow


# compile e-paper display python package
git clone https://github.com/waveshare/e-Paper.git
cd e-Paper/RaspberryPi_JetsonNano/python/
sudo python3 setup.py build
sudo python3 setup.py install
