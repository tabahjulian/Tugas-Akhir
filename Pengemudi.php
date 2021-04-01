
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <title>Pengemudi</title>
    <script src='https://js.arcgis.com/4.13'></script>
    <link rel="stylesheet" href="https://js.arcgis.com/4.13/esri/css/main.css">
</head>

<body>
<script>
        let map;
        let mapview;
        require(["esri/Map",
                "esri/views/MapView",
                "esri/request",
                "esri/widgets/BasemapToggle",
                "esri/layers/GeoJSONLayer",
                "esri/widgets/Popup",
                "esri/Graphic",
                "esri/symbols/SimpleMarkerSymbol",
                "esri/renderers/SimpleRenderer",
                "esri/layers/support/LabelClass"
            ],
            function (Map, MapView, request, BasemapToggle,GeoJSONLayer,Popup, Graphic, SimpleMarkerSymbol,SimpleRenderer,LabelClass) {
                map = new Map({
                    basemap: "streets-navigation-vector"
                })
                mapview = new MapView({
                    container: "mapview",
                    map: map,
                    center: [107.61614836429119, -6.879847681291412],
                    scale: 50000
                })
                //widget
                let toggle = new BasemapToggle({
                    view: mapview,
                    nextBasemap: "satellite"
                });
                mapview.ui.add(toggle, "top-right");

                
                let urlRute = "/json/rute/<?php
                if(isset($_POST["submit"])){
                    $tanggal = $_POST["tglAmbil"];
                    $waktu = $_POST["waktuAmbil"];
                    $tglWaktu = "{$tanggal}{$waktu}";
                    $forQuery = str_replace("-", "", $tglWaktu);
                    echo($forQuery);
                }
                ?>.json";
                let urlOrder = "/json/permintaan/<?php
                if(isset($_POST["submit"])){
                    $tanggal = $_POST["tglAmbil"];
                    $waktu = $_POST["waktuAmbil"];
                    $tglWaktu = "{$tanggal}{$waktu}";
                    $forQuery = str_replace("-", "", $tglWaktu);
                    echo($forQuery);
                }
                ?>.json";
                let options = {
                    responseType: "json"
                };
                let hihi = [];
                let table = document.getElementById("myTable");
                
                request(urlRute, options)
                    .then(function (response) {
                        let result = response.data;
                        console.log(result);
                        let listTruk = document.getElementById("listTruk");
                        listTruk.addEventListener("change", function () {
                            let jarakRute = document.getElementById("jarakRute");
                            let kapasitasTruk = document.getElementById("kapasitasTruk");
                            for (let i = 0; i < result.features.length; i++) {
                                if(result.features[i].attributes.Name == this.value){
                                    jarakRute.innerHTML = result.features[i].attributes.TotalDistance * 1.60934 + "  Kilometer";
                                    kapasitasTruk.innerHTML = result.features[i].attributes.Capacities;
                                }
                            }
                            let pathTruk = result.features[hihi[this.selectedIndex-1]].geometry.paths;
                            let g = {
                                type: "polyline",
                                paths: pathTruk
                            }
                            let s = {
                                type: "simple-line",
                                width: 1,
                                color: "red",
                                style: "solid"
                            }
                            let graphic = new Graphic({
                                geometry: g,
                                symbol: s
                            })
                            mapview.graphics = [];
                            mapview.graphics.add(graphic);
                            mapview.goTo(graphic);
                        })
                        for (let i = 0; i < result.features.length; i++) {
                            if (result.features[i].hasOwnProperty('geometry')) {
                            let option = document.createElement("option");
                            hihi.push(i);
                            option.textContent = result.features[i].attributes.Name
                            listTruk.appendChild(option)
                            }
                        }
                    })
        
                    request(urlOrder, options)
                    .then(function (response) {
                        let result = response.data;
 
                        let listTruk = document.getElementById("listTruk");
                        listTruk.addEventListener("change", function () {    
                            for (let k = table.rows.length -1; k >= 0 ; k--){
                                table.deleteRow(k);
                            }
                            let j = [];
                            for (let i = 0; i < result.features.length; i++) {
                                if(result.features[i].properties.RouteName == this.value){
                                    let urutan = result.features[i].properties.Sequence; 
                                    let row = table.insertRow(-1);
                                    row.onclick = rowClick;
                                    row.setAttribute("valueX",result.features[i].geometry.coordinates[0]);
                                    row.setAttribute("valueY",result.features[i].geometry.coordinates[1]);
                                    var cell1 = row.insertCell(0);
                                    var cell2 = row.insertCell(1);
                                    var cell3 = row.insertCell(2);
                                    cell1.innerHTML = result.features[i].properties.Sequence -1;
                                    cell2.innerHTML = result.features[i].properties.Name;
                                    cell3.innerHTML = result.features[i].properties.PickupQuantities;
                                    j.push(result.features[i].properties.Name);
                                }
                            }
                            let pertama = j[0];
                            let y = `'${pertama}' `
                            for (let x = 1; x < j.length; x++){
                                y += `OR NAME = '${j[x]}' `
                            }
                            layerOrder.definitionExpression = `Name = ${y}`
                        })
                    })
                    var orderRenderer = {
                        type: "simple",  // autocasts as new SimpleRenderer()
                        symbol: {
                            type: "simple-marker",
                            size: 7,
                            color: "blue",
                            outline: {  // autocasts as new SimpleLineSymbol()
                            width: 0.5,
                            color: "white"
                    }}};

                    let orderLabelClass = new LabelClass({
                    labelExpressionInfo: { expression: "$feature.Name" },
                    labelPlacement: "above-center",
                    symbol: {
                        type: "text",  // autocasts as new TextSymbol()
                        color: "black",
                        haloSize: 1,
                        haloColor: "white"
                    }
                    });
                    let layerOrder = new GeoJSONLayer({
                    url: urlOrder,
                    renderer: orderRenderer,
                    labelingInfo: orderLabelClass
                    })
                    map.add(layerOrder);
                    //Pool
                    let urlPool = "/json/Pool.json";
                    var poolRenderer = {
                        type: "simple",  // autocasts as new SimpleRenderer()
                        symbol: {
                            type: "simple-marker",  // autocasts as new SimpleMarkerSymbol()
                            style: "diamond",
                            angle: 45,
                            size: 12,
                            color: "black",
                            outline: {  // autocasts as new SimpleLineSymbol()
                            width: 0.5,
                            color: "white"
                    }
                    }
                    };
                    const poolLabelClass = new LabelClass({
                    labelExpressionInfo: { value: "Pool" },
                    labelPlacement: "above-center",
                    symbol: {
                        type: "text",  // autocasts as new TextSymbol()
                        color: "black",
                        haloSize: 1,
                        haloColor: "white"
                    }
                    });
                    let layerPool = new GeoJSONLayer({
                        url: urlPool,
                        renderer: poolRenderer,
                        labelingInfo: [ poolLabelClass ]
                    });
                    map.add(layerPool);
                    //show route
            });
function sortUrutan() {
  var table, rows, switching, i, x, y, shouldSwitch;
  table = document.getElementById("myTable");
  switching = true;
  /*Make a loop that will continue until
  no switching has been done:*/
  while (switching) {
        //start by saying: no switching is done:
        switching = false;
        rows = table.rows;
        /*Loop through all table rows (except the
        first, which contains table headers):*/
        for (i = 0; i < (rows.length); i++) {
        //start by saying there should be no switching:
        shouldSwitch = false;
        /*Get the two elements you want to compare,
        one from current row and one from the next:*/
        x = rows[i].getElementsByTagName("TD")[0];
        y = rows[i + 1].getElementsByTagName("TD")[0];
        //check if the two rows should switch place:
        if (Number(x.innerHTML) > Number(y.innerHTML)) {
            //if so, mark as a switch and break the loop:
            shouldSwitch = true;
            break;
        }
        }   
        if (shouldSwitch) {
            /*If a switch has been marked, make the switch
            and mark that a switch has been done:*/
        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
        switching = true;
        }
        }
        }
        function sortVolume() {
        var table, rows, switching, i, x, y, shouldSwitch;
        table = document.getElementById("myTable");
        switching = true;
        /*Make a loop that will continue until
        no switching has been done:*/
        while (switching) {
            //start by saying: no switching is done:
            switching = false;
            rows = table.rows;
            /*Loop through all table rows (except the
            first, which contains table headers):*/
            for (i = 0; i < (rows.length); i++) {
            //start by saying there should be no switching:
            shouldSwitch = false;
            /*Get the two elements you want to compare,
            one from current row and one from the next:*/
            x = rows[i].getElementsByTagName("TD")[2];
            y = rows[i + 1].getElementsByTagName("TD")[2];
            //check if the two rows should switch place:
            if (Number(x.innerHTML) > Number(y.innerHTML)) {
                //if so, mark as a switch and break the loop
                shouldSwitch = true;
                break;
            }
            }
            if (shouldSwitch) {
            /*If a switch has been marked, make the switch
            and mark that a switch has been done:*/
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            }
        }
        }
        let table = document.getElementById("myTable");
        if (table != null) {
        for (var i = 0; i < table.rows.length; i++) {
            for (var j = 0; j < table.rows[i].cells.length; j++)
            table.rows[i].cells[j].onclick = function () {
                tableText(this);
            };
        }
        }
        function tableText(tableCell) {
        alert(tableCell.innerHTML);
        }
        function rowClick(){
            let pelangCoordX = this.getAttribute("valueX");
            let pelangCoordY = this.getAttribute("valueY");
            pelangCoord = [pelangCoordX, pelangCoordY];
            mapview.popup.open({
                title: this.cells[1].innerHTML,
                location: pelangCoord
            });
        } 
    </script>
    <header class="navbar navbar-expand navbar-dark bg-dark flex-column flex-md-row bd-navbar"
        style="border-bottom: 5px inset orange;">
        <div class="container">
            <a class="navbar-brand">Tabah Juliansah</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-item nav-link" href="index.html">Home </a>
                    <a class="nav-item nav-link" href="Pelanggan.php">Pelanggan</a>
                    <a class="nav-item nav-link active" href="pengemudi.php">Pengemudi</a>
                </div>
            </div>
    </header>
    <div class="container-fluid row text-warning" style="height: 92vh;">
        <div id="mapview" class="container col-9">
        </div>
        <div class="container col-3 pt-3 px-4" style="border-left: 2px inset lightskyblue">
            <form method="POST">
            <div class="row">
                <div class="col-sm-6">
                    <label for="tglAmbil">Tanggal</label>
                    <input type="date" name="tglAmbil" class="form-control" id="tglAmbil">
                </div>
                <div class="col-sm-6">
                    <label for="waktuAmbil">Waktu </label>
                    <select class="form-control custom-select" name="waktuAmbil" id="waktuAmbil">
                        <option selected>Pilih Waktu Penjemputan</option>
                        <option value="00"> 00:00 - 08.00</option>
                        <option value="08"> 08:00 - 16.00</option>
                        <option value="16"> 16:00 - 24.00</option>
                    </select>
                </div>
            </div>
            <div class="row mt-2 pr-3 float-right">
                <button type="submit" name="submit" class="btn btn-warning">Submit</button>
            </div>
            </form>
            <div class="row mt-5">
                <div class="col-sm-6">
                    <label for="listTruk">Nopol Truk</label>
                    <select class="form-control custom-select" name="listTruk" id="listTruk">
                        <option selected>Pilih Truk</option>
                    </select>
                </div>
                <div class="col-sm-6 mt-4">
                <table class="table">
            <thead>
                <tr>
                    <th>Kapasitas</th>
                    <th id="kapasitasTruk"></th>
                </tr>
            </thead>
            </table>
                </div>
            </div>
            <div class="row mt-5">
            <table class="table">
            <thead>
                <tr>
                    <th scope="col" onclick="sortUrutan()" style="cursor: pointer;">Urutan</th>
                    <th scope="col">Tujuan</th>
                    <th scope="col" onclick="sortVolume()" style="cursor: pointer;">Volume (M<sup>3</sup>)</th>
                </tr>
            </thead>
            <tbody id="myTable">
            </tbody>
            </table>
            </div>
            <div class="row mt-5">
            <table class="table">
            <thead>
                <tr>
                    <th>Jarak</th>
                    <th id="jarakRute"></th>
                </tr>
            </thead>
            </table>
            </div>
        </div>
    </div>

    <script>
    <?php
    if(isset($_POST["submit"])){
            $tanggal = $_POST["tglAmbil"];
            $waktu = $_POST["waktuAmbil"];
            $tglWaktu = "{$tanggal}{$waktu}";
            $forQuery = str_replace("-", "", $tglWaktu);
            echo "document.querySelector('#tglAmbil').value='{$tanggal}';";
            echo "document.querySelector('#waktuAmbil').value='{$waktu  }'";
        }
    ?>
    </script>
</body>

</html>