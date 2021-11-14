from todolist import todolist
import datetime as dt

from waveshare_epd import epd7in5_V2

def main():
    epd = epd7in5_V2.EPD()
    epd.init()
    epd.Clear()

    date = dt.datetime.now()
    todo = todolist(date)
    image = todo.get_image()
    #rotate the image 180 degrees (if needed for wiring etc)
    image = image.rotate(180)
    epd.display(epd.getbuffer(image))
    epd7in5_V2.epdconfig.module_exit()

if __name__ == '__main__':
  main()
