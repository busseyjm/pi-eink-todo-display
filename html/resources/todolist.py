import calendar
import socket
import sqlite3
from PIL import Image, ImageDraw, ImageFont

# Constants
# Epaper and section dimensions
EPAPERDISPLAY_WIDTH = 800
EPAPERDISPLAY_HEIGHT = 480
CALENDAR_SECTION_WIDTH = 210

# List section dimensions
TODO_LIST_SQLITE = "/var/www/html/resources/todolist.db"
LIST_LINE_H_PADDING = 10
LIST_LINE_COUNT = 14
LIST_COLUMN_COUNT = 2
LIST_FONT_SIZE = 18
LIST_FONT_WEIGHT = 'Medium'
URL_FONT_SIZE = 28
URL_FONT_WEIGHT = 'Bold'

# Single day of the week section fonts
DAY_NUMBER_FONT_SIZE = 170
DAY_NUMBER_FONT_WEIGHT = 'Bold'
DOW_FONT_SIZE = 37
DOW_FONT_WEIGHT = 'Bold'
MONTH_FONT_SIZE = 27
MONTH_FONT_WEIGHT = 'Medium'

# Calendar section fonts
CAL_DOW_FONT_SIZE = 18
CAL_DOW_FONT_WEIGHT = 'Bold'
CAL_DAY_NUMBER_FONT_SIZE = 18
CAL_DAY_NUMBER_FONT_WEIGHT = 'Regular'

class todolist:
    # Dictionary that holds 2 letter days of the week.
    dow_dict = dict(zip(range(0,7),(calendar.weekheader(2).split(' '))))

    # Variables for Pillow.
    image = Image.new('1', (EPAPERDISPLAY_WIDTH, EPAPERDISPLAY_HEIGHT), 1)
    draw = ImageDraw.Draw(image)

    # Variables for the calendar
    cal = calendar.Calendar()
    # Change this to change the first day of the week on the calendar.
    cal.setfirstweekday(calendar.SUNDAY)

    # Construct the .local (zeroconf) url for the host device
    local_url = "http://{}.local".format(socket.gethostname().lower())

    # Coordinates of each line in the to-do list section
    list_coords = dict()
    # Length of each line in the to-do list section
    line_len = (((EPAPERDISPLAY_WIDTH - CALENDAR_SECTION_WIDTH)
            - ((LIST_COLUMN_COUNT+1) * LIST_LINE_H_PADDING))
            / LIST_COLUMN_COUNT)

    def __init__(self, date):
        self.date = date

    def getFont(self, size, weight):
        return ImageFont.truetype('/var/www/html/resources/fonts/BitterPro-{}.ttf'.format(weight), 
                                size)

    def get_image(self):
        """
        Draws the image and returns it
        """
        self.__draw_layout()
        self.__draw_today(self.date)
        self.__draw_calendar(self.date)
        self.__draw_list()
        return self.image


    def __draw_layout(self):
        """
        Draws the general layout of the display, creating lines separating
        each section, and drawing the list lines for the to-do list
        """
        # Vertical line separating calendar/date section from the todo list.
        self.draw.line(
        [
            (CALENDAR_SECTION_WIDTH,0),
            (CALENDAR_SECTION_WIDTH,EPAPERDISPLAY_HEIGHT)
        ], 
        0, 3)

        # Horizontal line separating the date from the calendar.
        self.draw.line(
        [
            (0, (EPAPERDISPLAY_HEIGHT/2)),
            (CALENDAR_SECTION_WIDTH, (EPAPERDISPLAY_HEIGHT/2))
        ], 
        0, 3)

        # Horizontal line separating URL from to-do list
        # Draw the URL
        self.draw.text(
            ((((EPAPERDISPLAY_WIDTH - CALENDAR_SECTION_WIDTH)/2)
            + CALENDAR_SECTION_WIDTH),
            0),
            self.local_url, 
            font=self.getFont(
                URL_FONT_SIZE, 
                URL_FONT_WEIGHT
            ),
            anchor='ma'
        )
        
        # Use the bounding box of the URL to get the bottom y coord
        # for use as the horizontal line, and for getting the boundaries
        # of the to-do section.
        url_bbox = self.draw.textbbox(
            ((((EPAPERDISPLAY_WIDTH - CALENDAR_SECTION_WIDTH)/2)
            + CALENDAR_SECTION_WIDTH),
            0),
            self.local_url, 
            font=self.getFont(
                URL_FONT_SIZE, 
                URL_FONT_WEIGHT
            ),
            anchor='ma'
        )

        # Draw Horizontal separator between URL and To-do
        self.draw.line(
        [
            (CALENDAR_SECTION_WIDTH, url_bbox[3]),
            (EPAPERDISPLAY_WIDTH, url_bbox[3])
        ], 
        0, 3)


        # Horizontal To-do list lines
        # Calculate the line coordinates, based off length and padding
        list_offset_x = self.line_len + LIST_LINE_H_PADDING
        list_offset_y = ((EPAPERDISPLAY_HEIGHT-url_bbox[3])
                        /(LIST_LINE_COUNT+1))
        list_origin_x = CALENDAR_SECTION_WIDTH + LIST_LINE_H_PADDING
        list_origin_y = url_bbox[3] + list_offset_y

        for x in range(0,LIST_COLUMN_COUNT):
            for y in range(0,LIST_LINE_COUNT):
                self.list_coords[x,y] = (
                    (list_origin_x + x*list_offset_x),
                    (list_origin_y + y*list_offset_y)
                )
                self.draw.line(
                [
                    self.list_coords[x,y],
                    (self.list_coords[x,y][0]+self.line_len, 
                    self.list_coords[x,y][1])
                ], 
                0, 1)


    def __draw_list(self):
        """
        Draws the to-do list from the to-do list file onto the list lines
        Will truncate lines that are too long to draw without overlapping
        """
        # Read the todo list and put it in a zipped dictionary with the
        # line starting coordinates
        conn = sqlite3.connect(TODO_LIST_SQLITE)

        cur = conn.cursor()
        cur.execute("""      
            SELECT todoitem    
            FROM todolist_items
            INNER JOIN listorder
            ON todolist_items.id = listorder.item_id;""")
        linesql = cur.fetchall()
        lines = []
        for row in linesql:
            lines.append(row[0])

        line_dict = dict(zip(lines, self.list_coords))
        for key in line_dict:
            too_long = False
            # Going to be modifying the data, but I don't want to inadvertently 
            # change the key, copy it to something I don't mind changing
            item = key
            # Work out the dimensions of the current item, if it is too long
            # truncate it by a character until it isn't. If an item is wildly 
            # too long (100+ characters), immediately truncate it to 100 chars 
            # and go from there.
            if len(item) > 100:
                item = item[0:100]

            # Get the bounding box, work out the x axis length
            item_bb = self.draw.textbbox(
                self.list_coords[line_dict[key]],
                "- " + item, 
                font=self.getFont(
                    LIST_FONT_SIZE, 
                    LIST_FONT_WEIGHT
                ),
                anchor='ls'
            )
            # Truncate the line until it fits, recalculate bounding box 
            # to check if it fits yet
            while((item_bb[2]-item_bb[0])>self.line_len):
                too_long = True
                item = item[0:-1]
                item_bb = self.draw.textbbox(
                    self.list_coords[line_dict[key]],
                    "- " + item, 
                    font=self.getFont(
                        LIST_FONT_SIZE, 
                        LIST_FONT_WEIGHT
                    ),
                    anchor='ls'
                )
            # Truncate once more and add an elipsis to signify truncation
            if too_long:
                item = item[0:-1]
                item = item + "..."

            # Draw the item to the line, prepended with a hyphen for looks
            self.draw.text(
                self.list_coords[line_dict[key]],
                "- " + item, 
                font=self.getFont(
                    LIST_FONT_SIZE, 
                    LIST_FONT_WEIGHT
                ),
                anchor='ls'
            )

    def __draw_today(self, date):
        """
        Draws todays date onto the image in the format:
        DAY OF THE WEEK
        DAY NUMBER
        MONTH, YEAR
        """
        # Text formatting for each segment.
        dow_text = date.strftime('%A')
        month_text = date.strftime('%B %Y')
        day_text = date.strftime('%-d')

        # Fonts for each segment, based off constants.
        day_font = self.getFont(DAY_NUMBER_FONT_SIZE, DAY_NUMBER_FONT_WEIGHT)
        dow_font = self.getFont(DOW_FONT_SIZE, DOW_FONT_WEIGHT)
        month_font = self.getFont(MONTH_FONT_SIZE, MONTH_FONT_WEIGHT)

        # Very useful Pillow text anchor reference.
        # https://pillow.readthedocs.io/en/stable/handbook/text-anchors.html

        # Day of the week text segment.
        # Use a top middle anchor.
        # These anchors make text alignment very simple.
        dow_x = (CALENDAR_SECTION_WIDTH/2)
        dow_y = 0
        self.draw.text((dow_x,dow_y), 
                        dow_text, font=dow_font, fill=0, anchor='ma')

        # Large day number text segment.
        # Use a middle-middle anchor for the text placement
        day_x = (CALENDAR_SECTION_WIDTH/2)
        day_y = (EPAPERDISPLAY_HEIGHT/4)
        self.draw.text((day_x,day_y), 
                        day_text, font=day_font, fill=0, anchor='mm')

        # Month and Year text segment.
        # Using a middle descender anchor.
        dow_x = (CALENDAR_SECTION_WIDTH/2)
        dow_y = (EPAPERDISPLAY_HEIGHT/2)
        self.draw.text((dow_x,dow_y), 
                        month_text, font=month_font, fill=0, anchor='md')


    def __draw_calendar(self, date):
        """ 
        Draws the calendar section.
        The calendar is a 7x7 grid (including the days of the week)
        """

        day_font = self.getFont(CAL_DAY_NUMBER_FONT_SIZE, 
                                CAL_DAY_NUMBER_FONT_WEIGHT)
        dow_font = self.getFont(CAL_DOW_FONT_SIZE, CAL_DOW_FONT_WEIGHT)

        # Calculate the 49 points for the calendar render and store them.
        cal_offset_x = (CALENDAR_SECTION_WIDTH/8)
        cal_offset_y = (EPAPERDISPLAY_HEIGHT/16)
        cal_origin_x = 0 + cal_offset_x
        cal_origin_y = (EPAPERDISPLAY_HEIGHT/2) + cal_offset_y

        calendar_coords = dict()

        for x in range(0,7):
            for y in range(0,7):
                calendar_coords[x,y] = (
                    (cal_origin_x + x*cal_offset_x),
                    (cal_origin_y + y*cal_offset_y)
                )

        # Calculate the calendar for the current month. Massage it into a
        # string with proper padding based on the specified day of the week
        # as the first day of the week. This will be used with the coords
        # to draw the calendar.
        month = ""
        firstday, daycount = calendar.monthrange(date.year,date.month)

        # Days of the week
        for x in range(self.cal.firstweekday,self.cal.firstweekday+7):
            month += self.dow_dict[x%7] + " "

        # Padded days before the 1st day of the month
        # Padding is (first weekday of the month - first weekday of the week)%7
        for x in range(0,((firstday-self.cal.firstweekday)%7)):
            month += "- " 

        # Days of the month
        for x in range(1,daycount + 1):
            month += str(x) + " "

        # Padded days of the month after the last day
        # 42 = Up to 6 weeks across the month, minus the days not in the month
        for x in range(0, (42 - (firstday-self.cal.firstweekday + daycount))):
            month += "- " 

        # Draw the calendar.
        # Days of the Week (Limited to 2 chars) first
        # First week padded to the first day with empty cells
        # Last week padded after the last day with empty cells
        montharr = month.split(' ')

        # Days of the Week. Mo Tu We...
        for x in range(0,7):
            self.draw.text(
                calendar_coords[x,0],
                montharr[x], 
                font=dow_font, 
                fill=0,
                anchor='mm'
            )

        # Days of the Month. 1 2 3 4...
        for y in range(1,7):
            for x in range(0,7):
                # Hyphens are just used for padding, not actually drawn
                if (montharr[(y*7)+x] == "-"):
                    continue
                # Highlight the current date, if this day is today
                # Do that by drawing a black rectangle around 'today', and 
                # change 'today's font color to white.
                # Rectangle coords determined by finding the bounding box
                # of 'today's date with a slightly larger font size to make
                # the highlighting look better.
                if (int(montharr[(y*7)+x]) == date.day):
                    today_rect_coords = self.draw.textbbox(
                        calendar_coords[x,y],
                        montharr[(y*7)+x], 
                        font=self.getFont(
                            int(CAL_DAY_NUMBER_FONT_SIZE*1.50), 
                            CAL_DAY_NUMBER_FONT_WEIGHT
                        ),
                        anchor='mm'
                    )
                    self.draw.rectangle(
                        today_rect_coords,
                        fill=0
                    )
                    self.draw.text(
                        calendar_coords[x,y],
                        montharr[(y*7)+x], 
                        font=day_font, 
                        fill=1,
                        anchor='mm'
                    )
                    continue
                self.draw.text(
                    calendar_coords[x,y],
                    montharr[(y*7)+x], 
                    font=day_font, 
                    fill=0,
                    anchor='mm'
                )

