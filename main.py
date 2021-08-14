from todolist import todolist
import datetime as dt

def main():
    date = dt.datetime.now()
    #date = dt.datetime(2021,9,29)
    #date = dt.datetime(1111, 5, 1)
    todo = todolist(date)
    todo.get_image().save("test2.bmp")

if __name__ == '__main__':
  main()
