<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta http-Equiv="Cache-Control" Content="no-cache">
  <meta http-Equiv="Pragma" Content="no-cache">
  <meta http-Equiv="Expires" Content="0">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="nofollow">
  <title>Radar Server</title>
  <link rel="stylesheet" type="text/css" href="resources/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="resources/font-awesome-icons/css/all.css">
  <link rel="stylesheet" type="text/css" href="resources/leaflet.css">
  <script src="resources/leaflet.js"></script>
  <script src="resources/bootstrap.min.js"></script>
  <script src="resources/leaflet-color-markers/js/leaflet-color-markers.js"></script>
  <style>
    html, body {
    height: 100%;
    width: 100%;
  }
    #map_container {
      height: 100vh;
      position: relative;
      display: flex;
      flex-direction: column;
      background-color: #191919;
    }
    #map_bar {
      color: #fff;
      margin: 0.5rem;
      text-align: center;
    }
    @media screen and (min-width: 568px) {
      #map_title {
        text-align: left;
      }
    }
    #buttons_date_container {
      display: flex;
      flex-direction: column;
    }
    @media screen and (min-width:568px) {
      #buttons_date_container {
        flex-direction: row;
        align-items: center;
      }
    }
    #radar_controls {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      margin: 0;
      list-style: none;
      margin: 0.5rem;
    }
    #radar_controls li {
      margin: 0 0.25rem;
    }
    @media screen and (min-width:568px) {
      #radar_controls {
        margin: 0;
      }
      #radar_controls li {
        margin-right: 0.5rem;
      }
    }
    #fullscreen_btn {
      display: none;
    }
    @media screen and (min-width:568px) {
      #fullscreen_btn {
        display: block;
      }
    }
    #timestamp {
      flex: 1;
    }
    @media screen and (min-width:568px) {
      #timestamp {
        text-align: right;
      }
    }
    #mapid {
      width: 100%;
      flex: 1;
    }
    <?php
      if(empty($_GET['controlbarposition'])) {
        echo "/* No controls query string */\n";
      } else {
        if ($_GET['controlbarposition'] === "top") {
          echo "/* Controls top */\n";
        }
        if ($_GET['controlbarposition'] === "bottom") {
          echo "/* Controls bottom */\n";
          echo "#map_bar{order: 2;} #mapid{order: 1}";
        }
        if ($_GET['controlbarposition'] === "none") {
          echo "/* Controls none */\n";
          echo "#map_bar{display: none;}";
        }
      }
      if(empty($_GET['controlscolor'])) {
        echo "/* No controls query string */\n";
      } else {
        if ($_GET['controlscolor'] === "dark") {
          echo "/* Dark control bar */\n";
        }
        if ($_GET['controlscolor'] === "light") {
          echo "/* Control bar light */\n";
          echo "#map_container {background-color: #f7f7f7;} #map_bar { color: #191919;}";
        }
      }
      if(empty($_GET['fullscreen'])) {
        echo "// No full screen query\n";
      } else {
        if ($_GET['fullscreen'] === "true") {
          echo "/* Full screen enabled */\n";
        }
        if ($_GET['fullscreen'] === "false") {
          echo '#fullscreen_btn {display: none;}';
        }
      }
    ?>
  </style>
</head>
<body>
  <div id="map_container">
    <div id="map_bar">
      <?php
        if ((empty($_GET['title'])) || ($_GET['title'] ===""))  {
          echo "<!-- No map title -->\n";
        } else {
          $title = $_GET['title'];
          echo "<h4 id='map_title'>$title</h4>\n";
        }
      ?>
      <div id="buttons_date_container">
        <ul id="radar_controls">
          <li><button onclick="stop(); showFrame(animationPosition - 1); return;" class="btn btn-primary" title="Jump to previous frame."><i class="fa fa-arrow-left"></i></button></li>
          <li><button onclick="playStop();" class="btn btn-primary" >Play / Pause</button></li>
          <li><button onclick="stop(); showFrame(animationPosition + 1); return;" class="btn btn-primary" title="Jump to next frame."><i class="fa fa-arrow-right"></i></button></li>
          <li><button onclick="openFullscreen(); return;" class="btn btn-secondary" id="fullscreen_btn" title="Open full screen."><i class="fas fa-expand"></i></button></li>
        </ul>
        <span id="timestamp"></span>
      </div>
    </div>
    <div id="mapid"></div>
  </div>
  <script>
    var elem = document.getElementById("map_container");
    function openFullscreen() {
      if (elem.requestFullscreen) {
        elem.requestFullscreen();
      } else if (elem.webkitRequestFullscreen) { /* Safari */
        elem.webkitRequestFullscreen();
      } else if (elem.msRequestFullscreen) { /* IE11 */
        elem.msRequestFullscreen();
      }
    }

    var map = L.map('mapid', {
      <?php
        if(empty($_GET['startcenter'])) {
          echo "center: [50.37,-4.22],\n";
        } else {
          $center = $_GET['startcenter'];
          echo "center: [$center],\n";
        }
        if(empty($_GET['static'])) {
          echo "// No static query string\n";
        } else {
          if ($_GET['static'] === "false") {
            echo "// Not a static map\n";
          }
          if ($_GET['static'] === "true") {
            echo "zoomControl: false,\n";
            echo "boxZoom: false,\n";
            echo "doubleClickZoom: false,\n";
            echo "dragging: false,\n";
            echo "scrollWheelZoom: false,\n";
          }
        }
        if(empty($_GET['zoom'])) {
          echo "zoom: 7\n";
        } else {
          echo "zoom: " . $_GET['zoom'] . "\n";
        }
      ?>
    });

    var mbAttr = 'Imagery © <a href="https://www.mapbox.com/">Mapbox</a> | <a href="https://rainviewer.com">RainViewer.com</a>';
    var mbUrl = 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1Ijoid2lsbHdvb2QiLCJhIjoiY2tjdzZ0ejRjMGI3YTJ4bXMwY2Fkc2lnYSJ9.3hzSCTwd-mD2Ia3GtxoUew';
    var osmAttr = '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a> | <a href="https://rainviewer.com">RainViewer.com</a>';
    var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

    <?php
      if(empty($_GET['mapstyle'])) {
        echo "L.tileLayer(mbUrl, {id: 'mapbox/light-v9',tileSize: 512,zoomOffset: -1,attribution: mbAttr}).addTo(map);\n";
      } else {
        if ($_GET['mapstyle'] === "dark") {
          echo "L.tileLayer(mbUrl, {id: 'mapbox/dark-v9',tileSize: 512,zoomOffset: -1,attribution: mbAttr}).addTo(map);\n";
        }
        if ($_GET['mapstyle'] === "light") {
          echo "L.tileLayer(mbUrl, {id: 'mapbox/light-v9',tileSize: 512,zoomOffset: -1,attribution: mbAttr}).addTo(map);\n";
        }
        if ($_GET['mapstyle'] === "osm") {
          echo "L.tileLayer(osmUrl, {maxzoom: 19,attribution: osmAttr}).addTo(map);\n";
        }
      }
      if ((empty($_GET['markerlon'])) && (empty($_GET['markerlat'])) && ($_GET['markerlon'] == "") && ($_GET['markerlat'] == "")) {
        echo "// No map markers\n";
      } else {
        // 50.37,-4.22
        $marker_lon = $_GET['markerlon'];
        $marker_lat = $_GET['markerlat'];
        $marker_style = $_GET['markerstyle'];
        echo "L.marker([$marker_lon, $marker_lat], {icon: $marker_style}).addTo(map)\n";
        if ((empty($_GET['markerpopup'])) && ($_GET['markerpopup'] == "")) {
          echo "// No map marker popup\n";
        } else {
          $popup_content = $_GET['markerpopup'];
          $popup_content = htmlentities($popup_content, ENT_QUOTES);
          echo ".bindPopup('$popup_content')";
        }
      }
    ?>

    /**
     * RainViewer radar animation part
     * @type {number[]}
     */
    var timestamps = [];
    var radarLayers = [];
    var animationPosition = 0;
    var animationTimer = false;

    /**
     * Load actual radar animation frames timestamps from RainViewer API
     */
    var apiRequest = new XMLHttpRequest();
    apiRequest.open("GET", "https://api.rainviewer.com/public/maps.json", true);
    apiRequest.onload = function(e) {

      // save available timestamps and show the latest frame: "-1" means "timestamp.lenght - 1"
      timestamps = JSON.parse(apiRequest.response);
      showFrame(-1);
    };
    apiRequest.send();

    /**
     * Animation functions
     * @param ts
     */
    function addLayer(ts) {
      if (!radarLayers[ts]) {
        radarLayers[ts] = new L.TileLayer(
          <?php
          echo "'https://tilecache.rainviewer.com/v2/radar/' + ts + '/256/{z}/{x}/{y}/";
            if(empty($_GET['radarstyle'])) {
              echo "6/";
            } else {
              if ($_GET['radarstyle'] == 1) {
                echo "1/";
              }
              if ($_GET['radarstyle'] == 2) {
                echo "2/";
              }
              if ($_GET['radarstyle'] == 3) {
                echo "3/";
              }
              if ($_GET['radarstyle'] == 4) {
                echo "4/";
              }
              if ($_GET['radarstyle'] == 5) {
                echo "5/";
              }
              if ($_GET['radarstyle'] == 6) {
                echo "6/";
              }
              if ($_GET['radarstyle'] == 7) {
                echo "7/";
              }
              if ($_GET['radarstyle'] == 8) {
                echo "8/";
              }
            }
            if(empty($_GET['smoothing'])) {
              echo "1";
            } else {
              if ($_GET['smoothing'] == 0) {
                echo "0";
              }
              if ($_GET['smoothing'] == 1) {
                echo "1";
              }
            }
            echo "_";
            if(empty($_GET['snow'])) {
              echo "1";
            } else {
              if ($_GET['snow'] == 0) {
                echo "0";
              }
              if ($_GET['snow'] == 1) {
                echo "1";
              }
            }
          echo ".png',";
          ?> 
          {
          tileSize: 256,
          opacity: 0.001,
          zIndex: ts
        });
      }
      if (!map.hasLayer(radarLayers[ts])) {
          map.addLayer(radarLayers[ts]);
      }
    }

    /**
     * Display particular frame of animation for the @position
     * If preloadOnly parameter is set to true, the frame layer only adds for the tiles preloading purpose
     * @param position
     * @param preloadOnly
     */
    function changeRadarPosition(position, preloadOnly) {
      while (position >= timestamps.length) {
        position -= timestamps.length;
      }
      while (position < 0) {
        position += timestamps.length;
      }

      var currentTimestamp = timestamps[animationPosition];
      var nextTimestamp = timestamps[position];

      addLayer(nextTimestamp);

      if (preloadOnly) {
        return;
      }

      animationPosition = position;

      if (radarLayers[currentTimestamp]) {
          radarLayers[currentTimestamp].setOpacity(0);
      }
      radarLayers[nextTimestamp].setOpacity(100);

      document.getElementById("timestamp").innerHTML = (new Date(nextTimestamp * 1000)).toString();
    }

    /**
     * Check and show particular frame position from the timestamps list
     */
    function showFrame(nextPosition) {
      var preloadingDirection = nextPosition - animationPosition > 0 ? 1 : -1;

      changeRadarPosition(nextPosition);

      // preload next next frame (typically, +1 frame)
      // if don't do that, the animation will be blinking at the first loop
      changeRadarPosition(nextPosition + preloadingDirection, true);
    }

    /**
     * Stop the animation
     * Check if the animation timeout is set and clear it.
     */
      function stop() {
        if (animationTimer) {
          clearTimeout(animationTimer);
          animationTimer = false;
          return true;
        }
        return false;
      }

      function play() {
        showFrame(animationPosition + 1);

        // Main animation driver.
        <?php
        if(empty($_GET['speed'])) {
          echo 'animationTimer = setTimeout(play, 1000);';
        } else {
          echo 'animationTimer = setTimeout(play, '.$_GET['speed'].');';
        }
        ?>
      }

      function playStop() {
        if (!stop()) {
          play();
        }
      }

      function reload() {
        document.location.reload();
      }
      <?php
        if ((empty($_GET['refreshrate']))) {
          echo '// No refresh rate';
          echo 'setTimeout(reload, 100000);';
        } else {
          $refresh_rate = $_GET['refreshrate'];
          echo 'setTimeout(reload, '.$refresh_rate.');';
        }
      ?>
  </script>
</body>
</html>
