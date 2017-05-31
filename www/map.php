<?php
require_once '../config.php';
if (!isset($_GET['type']))
    $type = 'sea';
else
    $type = $_GET['type'];
if (isset($_GET['cellx']) && isset($_GET['celly']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1')
{
    dbLink::getDB()->query('update map set content=? where x=?d and y=?d',$type,$_GET['cellx'],$_GET['celly']);
}
list ($island1_x,$island1_y) = Model_Settings::get()->getIsland1Point();
list ($island2_x,$island2_y) = Model_Settings::get()->getIsland2Point();
list ($portal_in_x,$portal_in_y) = Model_Settings::get()->getPortalInPoint();
list ($portal_out_x,$portal_out_y) = Model_Settings::get()->getPortalOutPoint();
?>
<html>
  <head>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
    <style>
        .mapcell {
            border:1px solid white;
            float: left;
            width: 18px;
            height: 18px;
        }
        .sea {
            background-color: #44aaff;
        }
        .shallow {
            background-color: #aaddff;
        }
        .land {
            background-color: #99cc00;
        }
        .portal1
        {
            background-color: #0000ff;
        }
        .portal2
        {
            background-color: #ffff00;
        }
        .resources
        {
            background-color: #ff9933;
        }
        .shift_cell {
            width:9px;
            height:18px;
            float:left;    
        }
        .fleet {
            background-image: url(ship.png);
        }
    </style>
  </head>
  <body>
    <div class="container">
        <div class="row">
            <a class="btn sea" href="/map.php?type=sea">sea</a> <a class="btn shallow" href="/map.php?type=shallow">shallow</a> <a class="btn land" href="/map.php?type=land">land</a>
            <a class="btn portal1">entrance</a> <a class="btn portal2">exit</a> <a class="btn resources">res. point</a>
        </div>

<?php
$map = dbLink::getDB()->select('select y as ARRAY_KEY1, x as ARRAY_KEY2, content from map');
$map[$island1_y][$island1_x]['content'] = 'resources';
$map[$island2_y][$island2_x]['content'] = 'resources';
$map[$portal_in_y][$portal_in_x]['content'] = 'portal1';
$map[$portal_out_y][$portal_out_x]['content'] = 'portal2';
$fleets = dbLink::getDB()->selectCol('select position from fleets');
foreach ($fleets as $fleet)
{
    list($fx,$fy) = explode(',',$fleet);
    $map[$fy][$fx]['content'].=' fleet';
}
foreach ($map as $y=>$map_row)
{
    echo "<div class='row'>";
    if ($y%2 == 0)
        echo "<div class='shift_cell'> </div>";
    foreach ($map_row as $x=>$map_cell)
    {
        echo "<a class='btn btn-xs mapcell {$map_cell['content']}' href='/map.php?type={$type}&cellx={$x}&celly={$y}'> </a>";
    }
    echo "</div>";
}
?>
    </div>
  </body>
</html>