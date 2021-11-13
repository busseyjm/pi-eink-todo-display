<?php 
$sqlitefile = "/var/www/html/resources/todolist.db";
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <!-- FontAwesome CSS for icons -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">

    <style>
      div:empty { display: none }
    </style>

    <title>To-do List</title>
  </head>
  <body>
    <div class="container display-list">
      <br>
      <div class="list"></div>
      <hr/> 
      <div class="new-items"></div>
    </div>
    <div class="container">
      <button class="btn-block btn-lg btn-success submit-button">Submit</button>
      <div class="alert alert-warning"></div>
    </div>
    <!-- Optional JavaScript -->
    <!-- jQuery (non-slim for ajax) first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  </body>
</html>

<script>

class Listitem {
  constructor( id, task, is_active ) {
    this.task = task;
    this.id = id;
    this.is_active = is_active;
  }
  createlistitemhtml() {
    var html = "";
    //containing div
    html += `<div class="todo-list-item btn-group btn-group-lg input-group mb-3" data-rowid=` + this.id + `>`;
    //active signifies the text box can be typed into
    if (this.is_active) {
      //reorder buttons, up then down, disabled
      html += `<button class="btn btn-outline-primary reorder-up" disabled><i class="fa fa-arrow-up"></i></button>`;
      html += `<button class="btn btn-outline-primary reorder-down" disabled><i class="fa fa-arrow-down"></i></button>`;
      //active item, can be typed into and be 'submitted'
      html += `<input type="text" class="form-control" placeholder="New List Item">`;
      html += `<button class="btn btn-outline-success add-row"><i class="fa fa-check"></i></button>`;
    } else {
      //reorder buttons, up then down, disabled
      html += `<button class="btn btn-outline-primary reorder-up"><i class="fa fa-arrow-up"></i></button>`;
      html += `<button class="btn btn-outline-primary reorder-down"><i class="fa fa-arrow-down"></i></button>`;
      //inactive item, cannot be typed into and can be 'deleted'
      html += `<input type="text" class="form-control" placeholder="` + this.task + `" readonly>`;
      html += `<button class="btn btn-outline-danger delete-row"><i class="fa fa-trash-alt"></i></button>`;
    }
    html += `</div>`;
    return html;
  }
};

//items that have been added in the current 'session'
var newitems = new Set();
//items that have not been deleted this session, but also haven't been added
// (pre-existing items)
var unaltereditems = new Set();
//items that did exist before this session, but have been deleted
// (items created and then deleted in an single session, but never comitted are ignored)
var deleteditems = new Set();
//current order of the list, 0th index being highest on the list, values are item ids
var listorder = [];
//the next id to use for future items, incremented every new item
var nextlistindex = 1;
//the object that contains what the actual list items are, worked out at 'submission' time
var newtasks = new Map();
//tracks whether there's a database-changing change this session
var startingorder = [];


$( document ).ready(function() {
  var sourceditems = new Map();

  //load from the sqlite db and create elements according to them
  <?php
    $db = new SQLite3($sqlitefile);
    $query = $db->query('
      SELECT id, todoitem 
      FROM todolist_items
      INNER JOIN listorder 
      ON todolist_items.id = listorder.item_id;
    ');
    while ($row = $query->fetchArray()) {
      echo "sourceditems.set(";
      echo "{$row['id']}, \"{$row['todoitem']}\"";
      echo ");\n";
      echo "listorder.push(Number({$row['id']}));\n";
    }
    //get the index for the next id that can be added to the db
    $index = $db->querySingle('
      SELECT MAX(id)
      FROM todolist_items;
    ');
    $index = $index + 1;
    echo "nextlistindex = $index;\n";

  ?>

  startingorder = [...listorder];
  listorder.forEach(function(item) {
    var id = item;
    var task = sourceditems.get(id);
    unaltereditems.add(id);
    createlistitem(id, task, false);
  });
  createlistitem(nextlistindex++);
});

//has the list changed AT ALL? (order, items)
function listchanged() {
  return (JSON.stringify(startingorder) != JSON.stringify(listorder));
}

//if the list has changed, show the user that there exist unsubmitted changes
function checkunsubmitted() {
  if (listchanged()) {
    $('.alert').html("Unsubmitted changes");
  } else {
    $('.alert').html("");
  }
}

//creates a listitem within the list
function createlistitem(id, task, is_active=true) {
  //create the class instance
  var newitem = new Listitem( id, task, is_active );

  //add the html from the class to the document
  if (is_active) {
    $('.new-items').append(newitem.createlistitemhtml());
  } else {
    $('.list').append(newitem.createlistitemhtml());
  }

  //attach button functions
  var $el = $("[data-rowid=" + id + "]");
  //up arrow
  $el.find('.reorder-up').click(moveitemup);
  //down arrow
  $el.find('.reorder-down').click(moveitemdown);
  //delete or submit button
  if (is_active) {
    $el.find('.add-row').click(submitlistitem);
  } else {
    $el.find('.delete-row').click(deletelistitem)
  }

};

//attached to an add-row button to convert it to a 'submitted' item
//sent from the 'add-row' classed button
function submitlistitem() {
  var text = $(this).parent().find("input").val();
  //don't allow empty items to be added, prevents hassle later on
  if (text == '') return;

  //remove click event function
  $(this).off("click");

  //change class of the button
  $(this).removeClass("add-row");
  $(this).addClass("delete-row");
  //change icon and colour of the button
  $(this).removeClass("btn-outline-success");
  $(this).addClass("btn-outline-danger");
  $(this).find("i").removeClass("fa-check");
  $(this).find("i").addClass("fa-trash-alt");

  //enable up/down arrows
  $(this).parent().find('.reorder-up').prop("disabled", false);
  $(this).parent().find('.reorder-down').prop("disabled", false);

  //change the input into the placeholder text
  $(this).parent().find("input").prop("placeholder", text);
  $(this).parent().find("input").prop("readonly", true);
  //clear the input
  $(this).parent().find("input").val = "";

  //attach delete click event function
  $(this).click(deletelistitem);

  //create a new active item
  createlistitem(nextlistindex++);

  //add it to backend tracking new items
  var id = $(this).parent().attr('data-rowid');
  newitems.add(Number(id));

  $('.list').append($(this).parent());

  //this needs to stay consistent to where the 'new list item' is being added
  // currently it is being added to the end of the list, so push is fine
  // (as opposed to adding it to the top/front)
  listorder.push(Number(id));
  checkunsubmitted();
};

//removes an item from the list
function deletelistitem() {
  //backend sets/maps handling
  var id = Number($(this).parent().attr('data-rowid'));

  var orderindex = listorder.indexOf(id);
  listorder.splice(orderindex, 1);

  if (unaltereditems.has(id)) {
    unaltereditems.delete(id);
    deleteditems.add(id);
  }

  if (newitems.has(id)) {
    newitems.delete(id);
    newtasks.delete(id);
  }

  $(this).parent().remove();
  checkunsubmitted();
};

//reorders a list item downwards
function moveitemdown() {
  var $el = $(this).parent();
  if (!($el.is(':last-child'))) {
    $el.next().after($el);
    //backend handling
    var id = Number($(this).parent().attr('data-rowid'));
    var orderindex = listorder.indexOf(id);
    //es6 'destructuring assignment', one line array swap
    [ listorder[orderindex], listorder[orderindex+1] ] =
      [ listorder[orderindex+1], listorder[orderindex] ];
  }
  checkunsubmitted();
}

//reorders a list item upwards
function moveitemup() {
  var $el = $(this).parent();
  if (!($el.is(':first-child'))) {
    $el.prev().before($el);
    //backend handling
    var id = Number($(this).parent().attr('data-rowid'));
    var orderindex = listorder.indexOf(id);
    //es6 'destructuring assignment', one line array swap
    [ listorder[orderindex-1], listorder[orderindex] ] = 
      [ listorder[orderindex], listorder[orderindex-1] ];
  }
  checkunsubmitted();
}

//calls the ajax post submit
function submitchanges() { 
  newitems.forEach(function(value, key) {
    var $el = $("[data-rowid=" + key + "]");
    var text = $el.find("input").val();
    newtasks.set(key,text);
  });
  ajaxpost();
}
$('.submit-button').click(submitchanges);


function ajaxpost() {
  $.post("submit.php",
  {
    'newitems': JSON.stringify([...newitems]),
    'unaltereditems': JSON.stringify([...unaltereditems]),
    'deleteditems': JSON.stringify([...deleteditems]),
    'newtasks': JSON.stringify([...newtasks]),
    'startingorder': JSON.stringify([...startingorder]),
    'listorder': JSON.stringify(listorder)
  },
  function(data, status){
    //reload if the post script returns
    window.location.reload();
  });
};

</script>

 