from todolist import todolist
import datetime as dt

from waveshare_epd import epd7in5_V2

def main():
    epd = epd7in5_V2.EPD()
    epd.init()
    epd.Clear()

    date = dt.datetime.now()
    #date = dt.datetime(2021,9,29)
    #date = dt.datetime(1111, 5, 1)
    todo = todolist(date)
    image = todo.get_image()
    #image.save("test2.bmp")
    epd.display(epd.getbuffer(image))
    epd7in5_V2.epdconfig.module_exit()

if __name__ == '__main__':
  main()
